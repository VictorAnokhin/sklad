<?php

namespace App\Services;

use App\Models\ChatMessage;
use App\Models\ChatSession;
use App\Models\Field;
use App\Models\Goods;
use App\Models\News;
use App\Models\Project;
use App\Models\Doc;
use App\Models\AiKnowledgeBase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DbQueryService
{
    private AiKnowledgeService $knowledgeService;

    public function __construct()
    {
        $this->knowledgeService = app(AiKnowledgeService::class);
    }

    /**
     * Поиск товаров по названию, артикулу или описанию.
     *
     * @param  int    $fid    ID проекта
     * @param  string $query  Поисковый запрос
     * @param  int    $limit  Максимальное количество результатов
     * @return array
     */
    public function searchGoods(int $fid, string $query, int $limit = 5): array
    {
        try {
            $goods = Goods::where('fid', $fid)
                ->where(function ($q) use ($query) {
                    $q->where('goods_name', 'like', "%{$query}%")
                      ->orWhere('goods_article', 'like', "%{$query}%")
                      ->orWhere('goods_description', 'like', "%{$query}%");
                })
                ->limit($limit)
                ->get(['id', 'goods_name', 'goods_article', 'goods_description', 'goods_price', 'goods_oldprice', 'goods_currency']);

            if ($goods->isEmpty()) {
                return ['found' => 0, 'items' => []];
            }

            return [
                'found' => $goods->count(),
                'items' => $goods->map(fn (Goods $g) => [
                    'id' => $g->id,
                    'name' => $g->goods_name,
                    'article' => $g->goods_article,
                    'description' => $g->goods_description,
                    'price' => (float) $g->goods_price,
                    'old_price' => $g->goods_oldprice ? (float) $g->goods_oldprice : null,
                    'currency' => $g->goods_currency,
                ])->toArray(),
            ];
        } catch (\Throwable $e) {
            Log::error("DbQueryService.searchGoods error: " . $e->getMessage());
            return ['found' => 0, 'items' => [], 'error' => 'Database query failed'];
        }
    }

    /**
     * Диалоговый поиск товаров: собирает поисковую фразу, подбирает категорию/фильтры
     * и возвращает ui_action, который фронт открывает на /goods.
     *
     * @param  array<int, string>  $filters
     * @param  array<int, string>  $excludeTerms
     * @return array<string, mixed>
     */
    public function searchCatalogProducts(
        int $fid,
        string $query,
        string $categoryQuery = '',
        array $filters = [],
        array $excludeTerms = [],
        string $locale = 'ru',
    ): array {
        $query = $this->normalizeProductSearchQuery($query, $excludeTerms);
        $categoryQuery = trim($categoryQuery);

        if ($query === '' && $categoryQuery === '' && $filters === []) {
            return [
                'success' => false,
                'message' => 'Уточните, какой товар нужно найти.',
            ];
        }

        try {
            $category = $categoryQuery !== ''
                ? $this->findBestCatalogCandidate($fid, $categoryQuery, $locale)
                : null;
            $queryCategory = $this->findBestCatalogCandidate($fid, $query, $locale);
            if ($this->shouldPreferQueryCategory($category, $queryCategory)) {
                $category = $queryCategory;
            }

            $filterTerms = $this->catalogFilterTermsFromQuery($query, $categoryQuery, $filters, $category);
            $matchedFilters = $this->matchCatalogFilters($fid, $category, $filterTerms, $locale);
            if ($filterTerms !== [] && $matchedFilters === []) {
                return $this->catalogClarificationAction(
                    'Нашёл несколько возможных разделов. Уточните категорию по названию.',
                    $this->catalogClarificationCandidatesForCategory($fid, $category, $locale),
                );
            }

            $categoryForUrl = $this->catalogCategoryForMatchedFilters($fid, $category, $matchedFilters, $locale);
            $path = $categoryForUrl !== null ? $this->buildCatalogPath($categoryForUrl) : '';
            $hk = $this->serializeCatalogFilterPairs($matchedFilters);
            $queryForUrl = ($hk !== '' || ($categoryForUrl !== null && $filterTerms === [])) ? '' : $query;

            $url = $this->buildCatalogProductsUrl($path, $queryForUrl, $hk);

            $found = $this->countCatalogProductsForSearch($fid, $queryForUrl, $matchedFilters);

            return [
                'success' => true,
                'found' => $found,
                'query' => $query,
                'category' => $categoryForUrl !== null ? [
                    'id' => $categoryForUrl['id'] ?? null,
                    'name' => $categoryForUrl['name'] ?? '',
                    'path' => $path,
                ] : null,
                'filters' => $matchedFilters,
                'ui_action' => [
                    'type' => 'navigate',
                    'path' => $path,
                    'url' => $url,
                    'label' => 'Показать товары',
                    'source' => 'search_catalog_products',
                ],
                'message' => $found > 0
                    ? 'Нашёл подходящие товары, показываю в каталоге.'
                    : 'Открываю поиск по каталогу, чтобы проверить подходящие варианты.',
            ];
        } catch (\Throwable $e) {
            Log::error('DbQueryService.searchCatalogProducts error: ' . $e->getMessage());

            return $this->catalogSearchAction($query !== '' ? $query : $categoryQuery, 'Открываю поиск по каталогу.', [
                'error' => 'Не удалось выполнить точный подбор товаров.',
            ]);
        }
    }

    /**
     * Получить детальную информацию о товаре по ID.
     *
     * @param  int $fid     ID проекта
     * @param  int $goodsId ID товара
     * @return array|null
     */
    public function getGoodsById(int $fid, int $goodsId): ?array
    {
        try {
            $goods = Goods::where('fid', $fid)
                ->where('id', $goodsId)
                ->first(['id', 'goods_name', 'goods_article', 'goods_description', 'goods_price', 'goods_oldprice', 'goods_currency', 'cat_id', 'goods_images', 'goods_params']);

            if (!$goods) {
                return null;
            }

            return [
                'id' => $goods->id,
                'name' => $goods->goods_name,
                'article' => $goods->goods_article,
                'description' => $goods->goods_description,
                'price' => (float) $goods->goods_price,
                'old_price' => $goods->goods_oldprice ? (float) $goods->goods_oldprice : null,
                'currency' => $goods->goods_currency,
                'category_id' => $goods->cat_id,
                'images' => $goods->goods_images,
                'params' => $goods->goods_params,
            ];
        } catch (\Throwable $e) {
            Log::error("DbQueryService.getGoodsById error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Поиск новостей проекта.
     *
     * @param  int    $fid   ID проекта
     * @param  string $query Поисковый запрос
     * @param  int    $limit Максимальное количество результатов
     * @return array
     */
    public function searchNews(int $fid, string $query, int $limit = 5): array
    {
        try {
            $news = News::where('fid', $fid)
                ->where(function ($q) use ($query) {
                    $q->where('news_title', 'like', "%{$query}%")
                      ->orWhere('news_body', 'like', "%{$query}%")
                      ->orWhere('news_meta_description', 'like', "%{$query}%");
                })
                ->orderBy('news_date', 'desc')
                ->limit($limit)
                ->get(['id', 'news_title', 'news_body', 'news_date', 'news_image']);

            if ($news->isEmpty()) {
                return ['found' => 0, 'items' => []];
            }

            return [
                'found' => $news->count(),
                'items' => $news->map(fn (News $n) => [
                    'id' => $n->id,
                    'title' => $n->news_title,
                    'body_preview' => mb_substr(strip_tags($n->news_body), 0, 500),
                    'date' => $n->news_date,
                    'image' => $n->news_image,
                ])->toArray(),
            ];
        } catch (\Throwable $e) {
            Log::error("DbQueryService.searchNews error: " . $e->getMessage());
            return ['found' => 0, 'items' => [], 'error' => 'Database query failed'];
        }
    }

    /**
     * Получить новость по ID.
     *
     * @param  int $fid    ID проекта
     * @param  int $newsId ID новости
     * @return array|null
     */
    public function getNewsById(int $fid, int $newsId): ?array
    {
        try {
            $news = News::where('fid', $fid)
                ->where('id', $newsId)
                ->first(['id', 'news_title', 'news_body', 'news_date', 'news_image']);

            if (!$news) {
                return null;
            }

            return [
                'id' => $news->id,
                'title' => $news->news_title,
                'body' => $news->news_body,
                'date' => $news->news_date,
                'image' => $news->news_image,
            ];
        } catch (\Throwable $e) {
            Log::error("DbQueryService.getNewsById error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Получить информацию о текущем проекте.
     *
     * @param  int $fid ID проекта
     * @return array|null
     */
    public function getProjectInfo(int $fid): ?array
    {
        try {
            $project = Project::where('fid', $fid)->first();

            if (!$project) {
                return null;
            }

            return [
                'name' => $project->name,
                'description' => $project->description,
                'email' => $project->email,
                'phone' => $project->phone,
                'address' => $project->address,
            ];
        } catch (\Throwable $e) {
            Log::error("DbQueryService.getProjectInfo error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Поиск документов/статей проекта.
     *
     * @param  int    $fid   ID проекта
     * @param  string $query Поисковый запрос
     * @param  int    $limit Максимальное количество результатов
     * @return array
     */
    public function searchDocs(int $fid, string $query, int $limit = 5): array
    {
        try {
            $docs = Doc::where('fid', $fid)
                ->where(function ($q) use ($query) {
                    $q->where('doc_title', 'like', "%{$query}%")
                      ->orWhere('doc_body', 'like', "%{$query}%");
                })
                ->limit($limit)
                ->get(['id', 'doc_title', 'doc_body', 'doc_date']);

            if ($docs->isEmpty()) {
                return ['found' => 0, 'items' => []];
            }

            return [
                'found' => $docs->count(),
                'items' => $docs->map(fn (Doc $d) => [
                    'id' => $d->id,
                    'title' => $d->doc_title,
                    'body_preview' => mb_substr(strip_tags($d->doc_body), 0, 1000),
                    'date' => $d->doc_date,
                ])->toArray(),
            ];
        } catch (\Throwable $e) {
            Log::error("DbQueryService.searchDocs error: " . $e->getMessage());
            return ['found' => 0, 'items' => [], 'error' => 'Database query failed'];
        }
    }

    /**
     * Поиск по базе знаний AI.
     *
     * @param  int    $fid   ID проекта
     * @param  string $query Поисковый запрос
     * @param  int    $limit Максимальное количество результатов
     * @return array
     */
    public function searchKnowledgeBase(int $fid, string $query, int $limit = 5): array
    {
        try {
            $kb = AiKnowledgeBase::where('fid', $fid)
                ->where('active', true)
                ->where(function ($q) use ($query) {
                    $q->where('title', 'like', "%{$query}%")
                      ->orWhere('content', 'like', "%{$query}%");
                })
                ->limit($limit)
                ->get(['id', 'title', 'content', 'category']);

            if ($kb->isEmpty()) {
                return ['found' => 0, 'items' => []];
            }

            return [
                'found' => $kb->count(),
                'items' => $kb->map(fn (AiKnowledgeBase $k) => [
                    'id' => $k->id,
                    'title' => $k->title,
                    'content' => $k->content,
                    'category' => $k->category,
                ])->toArray(),
            ];
        } catch (\Throwable $e) {
            Log::error("DbQueryService.searchKnowledgeBase error: " . $e->getMessage());
            return ['found' => 0, 'items' => [], 'error' => 'Database query failed'];
        }
    }

    /**
     * Получить категории товаров.
     *
     * @param  int $fid ID проекта
     * @return array
     */
    public function getGoodsCategories(int $fid): array
    {
        try {
            $categories = Field::getCatalogTree($fid, 'ru');

            if ($categories->isEmpty()) {
                return ['found' => 0, 'items' => []];
            }

            return [
                'found' => $categories->count(),
                'items' => $categories->map(fn (array $c) => [
                    'id' => $c['id'] ?? null,
                    'name' => $c['name'] ?? '',
                    'description' => $c['description'] ?? '',
                    'children' => $c['children'] ?? [],
                ])->toArray(),
            ];
        } catch (\Throwable $e) {
            Log::error("DbQueryService.getGoodsCategories error: " . $e->getMessage());
            return ['found' => 0, 'items' => [], 'error' => 'Database query failed'];
        }
    }

    /**
     * Найти раздел каталога по смыслу запроса и вернуть UI-действие для фронта.
     *
     * @param  int     $fid     ID проекта
     * @param  string  $query   Запрос пользователя: тип номера, категория или описание
     * @param  string  $locale  ru|ua|en
     * @return array
     */
    public function openCatalogCategory(int $fid, string $query, string $locale = 'ru'): array
    {
        $query = trim($query);
        if ($query === '') {
            return [
                'success' => false,
                'message' => 'Уточните, какой тип номера или раздел каталога нужно открыть.',
            ];
        }

        $catalogQuery = $this->cleanCatalogNavigationQuery($query);
        $searchQuery = $catalogQuery !== '' ? $catalogQuery : $query;

        try {
            $tree = Field::getCatalogTree($fid, $locale);
            $candidates = $this->flattenCatalogTree($tree->all());

            if ($candidates === []) {
                return [
                    'success' => false,
                    'message' => 'Каталог проекта пока пуст.',
                ];
            }

            if ($this->isGenericCatalogQuery($catalogQuery)) {
                $baseCategory = $this->findNumberPlateCategory($candidates);
                return $this->catalogClarificationAction(
                    'Уточните категорию номерных знаков.',
                    $this->catalogClarificationCandidatesForCategory($fid, $baseCategory, $locale),
                );
            }

            $normalizedQuery = $this->normalizeCatalogSearchText($catalogQuery);
            $best = null;
            $bestScore = 0;

            foreach ($candidates as $candidate) {
                $haystack = $this->normalizeCatalogSearchText(implode(' ', [
                    $candidate['name'] ?? '',
                    $candidate['link'] ?? '',
                    $candidate['name_ru'] ?? '',
                    $candidate['name_ua'] ?? '',
                    $candidate['name_en'] ?? '',
                    $candidate['description'] ?? '',
                    $candidate['description_ru'] ?? '',
                    $candidate['description_ua'] ?? '',
                    $candidate['description_en'] ?? '',
                    implode(' ', array_column($candidate['_parentPath'] ?? [], 'name')),
                ]));

                $score = $this->catalogMatchScore($normalizedQuery, $haystack);
                if (
                    $score > $bestScore
                    || (
                        $score === $bestScore
                        && $best !== null
                        && count($candidate['_parentPath'] ?? []) > count($best['_parentPath'] ?? [])
                    )
                ) {
                    $best = $candidate;
                    $bestScore = $score;
                }
            }

            if ($best === null || $bestScore < 2) {
                $baseCategory = $this->queryMentionsNumberPlates($catalogQuery)
                    ? $this->findNumberPlateCategory($candidates)
                    : null;
                return $this->catalogClarificationAction(
                    'Нашёл несколько возможных разделов. Уточните категорию по названию.',
                    $this->catalogClarificationCandidatesForCategory($fid, $baseCategory, $locale),
                );
            }

            $path = $this->buildCatalogPath($best);
            $filterTerms = $this->catalogFilterTermsFromQuery($searchQuery, '', [], $best);
            $matchedFilters = $this->matchCatalogFilters($fid, $best, $filterTerms, $locale);
            if ($filterTerms !== [] && $matchedFilters === []) {
                return $this->catalogClarificationAction(
                    'Нашёл несколько возможных разделов. Уточните категорию по названию.',
                    $this->catalogClarificationCandidatesForCategory($fid, $best, $locale),
                );
            }

            $hk = $this->serializeCatalogFilterPairs($matchedFilters);
            $url = $this->buildCatalogProductsUrl($path, '', $hk);

            return [
                'success' => true,
                'category' => [
                    'id' => $best['id'],
                    'name' => $best['name'],
                    'path' => $path,
                    'score' => $bestScore,
                ],
                'filters' => $matchedFilters,
                'ui_action' => [
                    'type' => 'navigate',
                    'path' => $path,
                    'url' => $url,
                    'label' => 'Открыть раздел',
                    'source' => 'open_catalog_category',
                ],
                'message' => "Открываю раздел «{$best['name']}».",
            ];
        } catch (\Throwable $e) {
            Log::error("DbQueryService.openCatalogCategory error: " . $e->getMessage());

            return $this->catalogSearchAction($searchQuery, 'Открываю поиск по каталогу.', [
                'error' => 'Не удалось подобрать точный раздел каталога.',
            ]);
        }
    }

    // ── Парсинг веб-страниц и сохранение в базу знаний ─────────────────────

    /**
     * Загрузить веб-страницу и сохранить её содержимое в базу знаний.
     *
     * @param  int    $fid      ID проекта
     * @param  string $url      URL страницы для парсинга
     * @param  string $category Категория знания
     * @return array
     */
    public function fetchAndSavePage(int $fid, string $url, string $category = 'web_page'): array
    {
        try {
            $result = $this->knowledgeService->fetchAndSavePage($fid, $url, $category);

            if (! $result['success']) {
                return [
                    'success' => false,
                    'error' => $result['error'] ?? 'Не удалось обработать страницу.',
                ];
            }

            return [
                'success' => true,
                'id' => $result['record']->id,
                'title' => $result['title'] ?? 'Без заголовка',
                'message' => 'Страница успешно сохранена в базу знаний.',
            ];
        } catch (\Throwable $e) {
            Log::error("DbQueryService.fetchAndSavePage error: " . $e->getMessage());
            return ['success' => false, 'error' => 'Ошибка: ' . $e->getMessage()];
        }
    }

    /**
     * Сохранить информацию в базу знаний проекта.
     *
     * @param  int    $fid      ID проекта
     * @param  string $title    Заголовок
     * @param  string $content  Содержание
     * @param  string $category Категория
     * @return array
     */
    public function saveToKnowledgeBase(int $fid, string $title, string $content, string $category = 'manual'): array
    {
        try {
            $result = $this->knowledgeService->saveInformation($fid, $title, $content, $category);

            if (! $result['success']) {
                return [
                    'success' => false,
                    'error' => $result['error'] ?? 'Не удалось сохранить информацию.',
                ];
            }

            return [
                'success' => true,
                'id' => $result['record']->id,
                'title' => $title,
                'message' => 'Информация успешно сохранена в базу знаний.',
            ];
        } catch (\Throwable $e) {
            Log::error("DbQueryService.saveToKnowledgeBase error: " . $e->getMessage());
            return ['success' => false, 'error' => 'Ошибка: ' . $e->getMessage()];
        }
    }

    // ── Tools для DeepSeek Function Calling ──────────────────────────────

    /**
     * Получить список инструментов (functions) для DeepSeek function calling.
     *
     * @return array
     */
    public function getTools(): array
    {
        return [
            [
                'type' => 'function',
                'function' => [
                    'name' => 'search_goods',
                    'description' => 'Поиск товаров проекта по названию, артикулу или описанию. Возвращает список товаров с ценами.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'query' => [
                                'type' => 'string',
                                'description' => 'Поисковый запрос (название, артикул или описание товара)',
                            ],
                            'limit' => [
                                'type' => 'integer',
                                'description' => 'Максимальное количество результатов (по умолчанию 5)',
                                'default' => 5,
                            ],
                        ],
                        'required' => ['query'],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_goods_by_id',
                    'description' => 'Получить детальную информацию о товаре по его ID.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'goods_id' => [
                                'type' => 'integer',
                                'description' => 'ID товара',
                            ],
                        ],
                        'required' => ['goods_id'],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'search_news',
                    'description' => 'Поиск новостей проекта. Возвращает заголовки, даты и превью内容 новостей.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'query' => [
                                'type' => 'string',
                                'description' => 'Поисковый запрос для поиска по заголовкам и содержанию новостей',
                            ],
                            'limit' => [
                                'type' => 'integer',
                                'description' => 'Максимальное количество результатов (по умолчанию 5)',
                                'default' => 5,
                            ],
                        ],
                        'required' => ['query'],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_project_info',
                    'description' => 'Получить информацию о текущем проекте (название, описание, контакты).',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => (object) [],
                        'required' => [],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'search_docs',
                    'description' => 'Поиск документов/статей проекта. Возвращает заголовки и содержимое документов.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'query' => [
                                'type' => 'string',
                                'description' => 'Поисковый запрос для поиска по документам',
                            ],
                            'limit' => [
                                'type' => 'integer',
                                'description' => 'Максимальное количество результатов (по умолчанию 5)',
                                'default' => 5,
                            ],
                        ],
                        'required' => ['query'],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'search_knowledge_base',
                    'description' => 'Поиск по базе знаний AI проекта. Используй когда пользователь задаёт вопрос, на который уже мог быть дан ответ ранее.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'query' => [
                                'type' => 'string',
                                'description' => 'Поисковый запрос',
                            ],
                            'limit' => [
                                'type' => 'integer',
                                'description' => 'Максимальное количество результатов (по умолчанию 5)',
                                'default' => 5,
                            ],
                        ],
                        'required' => ['query'],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_goods_categories',
                    'description' => 'Получить список категорий/разделов товаров проекта.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => (object) [],
                        'required' => [],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'open_catalog_category',
                    'description' => 'Найти подходящий раздел каталога по названию, типу номера, марке авто или описанию. Возвращает ui_action для навигации на фронте. Если точного раздела нет, возвращает ui_action на поиск /goods?q=... Используй когда пользователь просит показать, найти, подобрать или открыть товары/номера определённого типа (например "покажи еврономер", "найди укрномер", "mitsubishi l200", "квадратные", "укороченные").',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'query' => [
                                'type' => 'string',
                                'description' => 'Запрос: тип номера, марка/модель авто, название раздела или описание того, что нужно найти',
                            ],
                            'locale' => [
                                'type' => 'string',
                                'description' => 'Язык (ru, ua, en). По умолчанию ru',
                                'default' => 'ru',
                            ],
                        ],
                        'required' => ['query'],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'search_catalog_products',
                    'description' => 'Найти товары в каталоге по собранным из диалога признакам: тип товара, категория, материал, назначение. Возвращает ui_action для перехода на /goods с поиском q и подходящими фильтрами hk. Используй после уточняющих вопросов, когда пользователь выбрал вариант товара (например: номерные знаки, сувенирные, алюминий, не пластик).',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'query' => [
                                'type' => 'string',
                                'description' => 'Итоговая поисковая фраза без отрицаний, например "номерные знаки сувенирные алюминий"',
                            ],
                            'category_query' => [
                                'type' => 'string',
                                'description' => 'Название категории, если понятно из диалога, например "номерные знаки" или "сувенирные номера"',
                            ],
                            'filters' => [
                                'type' => 'array',
                                'description' => 'Положительные значения фильтров/признаки, например ["сувенирные", "алюминий"]',
                                'items' => ['type' => 'string'],
                                'default' => [],
                            ],
                            'exclude_terms' => [
                                'type' => 'array',
                                'description' => 'Отрицательные признаки, которые пользователь отверг, например ["пластик"]. Не добавляй их в query.',
                                'items' => ['type' => 'string'],
                                'default' => [],
                            ],
                            'locale' => [
                                'type' => 'string',
                                'description' => 'Язык (ru, ua, en). По умолчанию ru',
                                'default' => 'ru',
                            ],
                        ],
                        'required' => ['query'],
                    ],
                ],
            ],
            // ── Новые инструменты: парсинг и сохранение в базу знаний ──
            [
                'type' => 'function',
                'function' => [
                    'name' => 'fetch_and_save_page',
                    'description' => 'Загрузить веб-страницу по URL, извлечь текст и сохранить его в базу знаний проекта. Используй когда пользователь просит сохранить информацию с сайта или проанализировать страницу.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'url' => [
                                'type' => 'string',
                                'description' => 'Полный URL страницы для парсинга (https://...)',
                            ],
                            'category' => [
                                'type' => 'string',
                                'description' => 'Категория знания (по умолчанию web_page). Можно указать: faq, manual, docs, token, fund, invest, news',
                                'default' => 'web_page',
                            ],
                        ],
                        'required' => ['url'],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'save_to_knowledge_base',
                    'description' => 'Сохранить информацию в базу знаний проекта. Используй когда пользователь предоставляет полезную информацию, которую следует запомнить для других пользователей.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'title' => [
                                'type' => 'string',
                                'description' => 'Заголовок или тема информации',
                            ],
                            'content' => [
                                'type' => 'string',
                                'description' => 'Содержание информации для сохранения',
                            ],
                            'category' => [
                                'type' => 'string',
                                'description' => 'Категория знания (по умолчанию manual). Можно указать: faq, manual, docs, token, fund, invest, news, chat_export',
                                'default' => 'manual',
                            ],
                        ],
                        'required' => ['title', 'content'],
                    ],
                ],
            ],
        ];
    }

    /**
     * Выполнить функцию по имени с переданными аргументами.
     *
     * @param  int         $fid    ID проекта
     * @param  int|null    $firma  ID компании
     * @param  string      $name   Имя функции
     * @param  array<string, mixed>  $arguments  Аргументы функции
     * @return string  JSON-строка с результатом
     */
    public function executeTool(int $fid, ?int $firma, string $name, array $arguments): string
    {
        try {
            $result = match ($name) {
                'search_goods' => $this->searchGoods(
                    $fid,
                    (string) ($arguments['query'] ?? ''),
                    (int) ($arguments['limit'] ?? 5),
                ),
                'get_goods_by_id' => $this->getGoodsById(
                    $fid,
                    (int) ($arguments['goods_id'] ?? 0),
                ),
                'search_news' => $this->searchNews(
                    $fid,
                    (string) ($arguments['query'] ?? ''),
                    (int) ($arguments['limit'] ?? 5),
                ),
                'get_project_info' => $this->getProjectInfo($fid),
                'search_docs' => $this->searchDocs(
                    $fid,
                    (string) ($arguments['query'] ?? ''),
                    (int) ($arguments['limit'] ?? 5),
                ),
                'search_knowledge_base' => $this->searchKnowledgeBase(
                    $fid,
                    (string) ($arguments['query'] ?? ''),
                    (int) ($arguments['limit'] ?? 5),
                ),
                'get_goods_categories' => $this->getGoodsCategories($fid),
                'open_catalog_category' => $this->openCatalogCategory(
                    $fid,
                    (string) ($arguments['query'] ?? ''),
                    (string) ($arguments['locale'] ?? 'ru'),
                ),
                'search_catalog_products' => $this->searchCatalogProducts(
                    $fid,
                    (string) ($arguments['query'] ?? ''),
                    (string) ($arguments['category_query'] ?? ''),
                    array_values(array_filter((array) ($arguments['filters'] ?? []), fn ($value) => is_scalar($value) && trim((string) $value) !== '')),
                    array_values(array_filter((array) ($arguments['exclude_terms'] ?? []), fn ($value) => is_scalar($value) && trim((string) $value) !== '')),
                    (string) ($arguments['locale'] ?? 'ru'),
                ),
                'fetch_and_save_page' => $this->fetchAndSavePage(
                    $fid,
                    (string) ($arguments['url'] ?? ''),
                    (string) ($arguments['category'] ?? 'web_page'),
                ),
                'save_to_knowledge_base' => $this->saveToKnowledgeBase(
                    $fid,
                    (string) ($arguments['title'] ?? ''),
                    (string) ($arguments['content'] ?? ''),
                    (string) ($arguments['category'] ?? 'manual'),
                ),
                default => throw new \InvalidArgumentException("Unknown tool: {$name}"),
            };

            return json_encode($result, JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            Log::error("DbQueryService.executeTool error: " . $e->getMessage());
            return json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
    }

    // ── Helpers для openCatalogCategory ─────────────────────────────────────

    /**
     * @param  array<int, string>  $excludeTerms
     */
    private function normalizeProductSearchQuery(string $query, array $excludeTerms = []): string
    {
        $query = trim(preg_replace('/\s+/u', ' ', $query) ?? $query);

        foreach ($excludeTerms as $term) {
            $term = trim((string) $term);
            if ($term === '') {
                continue;
            }

            $query = preg_replace('/\b(не|нет|без)\s+' . preg_quote($term, '/') . '\b/iu', ' ', $query) ?? $query;
            $query = preg_replace('/\b' . preg_quote($term, '/') . '\b/iu', ' ', $query) ?? $query;
        }

        $query = preg_replace('/\b(не|нет|без|или)\b/iu', ' ', $query) ?? $query;
        $query = preg_replace('/\s+/u', ' ', $query) ?? $query;

        return trim($query);
    }

    /**
     * @param  array<int, string>  $filters
     * @return array<int, string>
     */
    private function catalogFilterTermsFromQuery(string $query, string $categoryQuery = '', array $filters = [], ?array $category = null): array
    {
        $terms = array_values(array_filter(array_map(fn ($value) => trim((string) $value), $filters)));
        $categoryText = $category !== null ? $this->catalogCandidateSearchText($category) : '';

        foreach ([$query, $categoryQuery] as $source) {
            $normalized = $this->normalizeCatalogSearchText($source);
            foreach (explode(' ', $normalized) as $word) {
                if (
                    mb_strlen($word) < 4
                    || $this->isGenericCatalogWord($word)
                    || ($categoryText !== '' && str_contains($categoryText, $word))
                ) {
                    continue;
                }

                $terms[] = $word;
            }
        }

        return array_values(array_unique(array_filter($terms)));
    }

    private function shouldPreferQueryCategory(?array $category, ?array $queryCategory): bool
    {
        if ($queryCategory === null) {
            return false;
        }

        if ($category === null) {
            return true;
        }

        $categoryId = (int) ($category['id'] ?? 0);
        $queryCategoryId = (int) ($queryCategory['id'] ?? 0);
        if ($categoryId <= 0 || $queryCategoryId <= 0 || $categoryId === $queryCategoryId) {
            return false;
        }

        foreach (($queryCategory['_parentPath'] ?? []) as $parent) {
            if ((int) ($parent['id'] ?? 0) === $categoryId) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<int, array>  $candidates
     */
    private function findNumberPlateCategory(array $candidates): ?array
    {
        foreach ($candidates as $candidate) {
            $text = $this->catalogCandidateSearchText($candidate);
            if (str_contains($text, 'nomernye znaki') || str_contains($text, 'number plates')) {
                return $candidate;
            }
        }

        return null;
    }

    private function queryMentionsNumberPlates(string $query): bool
    {
        $words = array_filter(explode(' ', $this->normalizeCatalogSearchText($query)));

        foreach ($words as $word) {
            if (in_array($word, ['nomer', 'nomera', 'nomernye', 'znak', 'znaki', 'avtonomer', 'avtonomera', 'avtonomery'], true)) {
                return true;
            }
        }

        return false;
    }

    private function findBestCatalogCandidate(int $fid, string $query, string $locale = 'ru'): ?array
    {
        $tree = Field::getCatalogTree($fid, $locale);
        $candidates = $this->flattenCatalogTree($tree->all());
        if ($candidates === []) {
            return null;
        }

        $normalizedQuery = $this->normalizeCatalogSearchText($this->cleanCatalogNavigationQuery($query));
        $best = null;
        $bestScore = 0;

        foreach ($candidates as $candidate) {
            $haystack = $this->normalizeCatalogSearchText(implode(' ', [
                $this->catalogCandidateSearchText($candidate),
            ]));

            $score = $this->catalogMatchScore($normalizedQuery, $haystack);
            if (
                $score > $bestScore
                || (
                    $score === $bestScore
                    && $best !== null
                    && count($candidate['_parentPath'] ?? []) > count($best['_parentPath'] ?? [])
                )
            ) {
                $best = $candidate;
                $bestScore = $score;
            }
        }

        return $best !== null && $bestScore >= 2 ? $best : null;
    }

    private function catalogCandidateSearchText(array $candidate): string
    {
        return $this->normalizeCatalogSearchText(implode(' ', [
            $candidate['name'] ?? '',
            $candidate['link'] ?? '',
            $candidate['name_ru'] ?? '',
            $candidate['name_ua'] ?? '',
            $candidate['name_en'] ?? '',
            $candidate['description'] ?? '',
            $candidate['description_ru'] ?? '',
            $candidate['description_ua'] ?? '',
            $candidate['description_en'] ?? '',
            implode(' ', array_column($candidate['_parentPath'] ?? [], 'name')),
        ]));
    }

    private function findCatalogCandidateById(int $fid, int $categoryId, string $locale = 'ru'): ?array
    {
        if ($categoryId <= 0) {
            return null;
        }

        $tree = Field::getCatalogTree($fid, $locale);
        $candidates = $this->flattenCatalogTree($tree->all());

        foreach ($candidates as $candidate) {
            if ((int) ($candidate['id'] ?? 0) === $categoryId) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * @param  array<int, string>  $filters
     * @return array<int, array{group_id: int, value_id: int, label: string}>
     */
    private function matchCatalogFilters(int $fid, ?array $category, array $filters, string $locale = 'ru'): array
    {
        $terms = array_values(array_filter(array_map(fn ($value) => trim((string) $value), $filters)));
        if ($terms === [] || !DB::getSchemaBuilder()->hasTable('filter')) {
            return [];
        }

        $catalogIds = [];
        if ($category !== null) {
            $catalogIds[] = (int) ($category['id'] ?? 0);
            $catalogIds = array_merge($catalogIds, $this->catalogDescendantIds($fid, (int) ($category['id'] ?? 0), $locale));
            foreach (($category['_parentPath'] ?? []) as $parent) {
                $catalogIds[] = (int) ($parent['id'] ?? 0);
            }
        }
        $catalogIds = array_values(array_unique(array_filter($catalogIds, fn (int $id) => $id > 0)));

        $matched = [];
        foreach ($terms as $term) {
            $normalizedTerm = $this->normalizeCatalogSearchText($term);
            if ($normalizedTerm === '') {
                continue;
            }

            $query = DB::table('filter')
                ->where('keyfield', 'filter')
                ->where('idfilter', '>', 0);

            if ($catalogIds !== []) {
                $query->whereIn('idkeyfield', $catalogIds);
            }

            $rows = $query
                ->orderBy('num')
                ->orderBy('id')
                ->limit(200)
                ->get(['id', 'idfilter', 'idkeyfield', 'val', 'valru', 'valen']);

            foreach ($rows as $row) {
                $label = $locale === 'en'
                    ? ((string) ($row->valen ?: $row->val ?: $row->valru))
                    : ((string) ($row->valru ?: $row->val ?: $row->valen));
                $normalizedLabel = $this->normalizeCatalogSearchText($label);

                if ($this->catalogFilterTermMatches($normalizedTerm, $normalizedLabel)) {
                    $matched[(int) $row->idfilter . ':' . (int) $row->id] = [
                        'group_id' => (int) $row->idfilter,
                        'value_id' => (int) $row->id,
                        'category_id' => (int) $row->idkeyfield,
                        'label' => $label,
                    ];
                    break;
                }
            }
        }

        return array_values($matched);
    }

    /**
     * @return array<int, int>
     */
    private function catalogDescendantIds(int $fid, int $categoryId, string $locale = 'ru'): array
    {
        if ($categoryId <= 0) {
            return [];
        }

        $tree = Field::getCatalogTree($fid, $locale);
        $candidates = $this->flattenCatalogTree($tree->all());
        $ids = [];

        foreach ($candidates as $candidate) {
            foreach (($candidate['_parentPath'] ?? []) as $parent) {
                if ((int) ($parent['id'] ?? 0) === $categoryId) {
                    $ids[] = (int) ($candidate['id'] ?? 0);
                    break;
                }
            }
        }

        return array_values(array_unique(array_filter($ids, fn (int $id) => $id > 0)));
    }

    /**
     * @param  array<int, array{group_id: int, value_id: int, label: string}>  $filters
     */
    private function serializeCatalogFilterPairs(array $filters): string
    {
        $pairs = [];
        foreach ($filters as $filter) {
            $groupId = (int) ($filter['group_id'] ?? 0);
            $valueId = (int) ($filter['value_id'] ?? 0);
            if ($groupId > 0 && $valueId > 0) {
                $pairs[] = $groupId . ':' . $valueId;
            }
        }

        return implode(',', array_values(array_unique($pairs)));
    }

    private function buildCatalogProductsUrl(string $path, string $query = '', string $hk = ''): string
    {
        $path = trim($path, '/');
        $query = trim($query);
        $hk = trim($hk);

        if ($path === '') {
            $url = '/goods';
            $params = [];
            if ($query !== '') {
                $params['q'] = $query;
            }
            if ($hk !== '') {
                $params['hk'] = $hk;
            }

            return $params !== []
                ? $url . '?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986)
                : $url;
        }

        if ($hk !== '') {
            $segments = array_values(array_filter(explode('/', $path)));
            $top = $segments[0] ?? '';
            $child = $segments[1] ?? '';
            $filterPath = str_replace(',', '-', $hk);

            $url = $child !== ''
                ? '/goods/' . $top . '/f-' . $child . ';f-' . $filterPath
                : '/goods/' . $top . '/f-;f-' . $filterPath;
        } else {
            $url = '/goods/' . $path;
        }

        if ($query !== '') {
            $url .= '?' . http_build_query(['q' => $query], '', '&', PHP_QUERY_RFC3986);
        }

        return $url;
    }

    private function catalogCategoryForMatchedFilters(int $fid, ?array $category, array $matchedFilters, string $locale = 'ru'): ?array
    {
        $categoryId = (int) ($category['id'] ?? 0);
        $filterCategoryId = 0;

        foreach ($matchedFilters as $filter) {
            $candidateId = (int) ($filter['category_id'] ?? 0);
            if ($candidateId > 0 && $candidateId !== $categoryId) {
                $filterCategoryId = $candidateId;
                break;
            }
        }

        if ($filterCategoryId <= 0) {
            return $category;
        }

        return $this->findCatalogCandidateById($fid, $filterCategoryId, $locale) ?? $category;
    }

    /**
     * @param  array<int, array{group_id: int, value_id: int, label: string}>  $filters
     */
    private function countCatalogProductsForSearch(int $fid, string $query, array $filters): int
    {
        $qOk = mb_strlen(trim($query)) >= 2;
        if (!$qOk && $filters === []) {
            return 0;
        }

        $dbQuery = DB::table('comp')
            ->leftJoin('descript as d', function ($join) use ($fid) {
                $join->on('d.pnum', '=', 'comp.id')
                    ->where('d.firma', '=', $fid);
            })
            ->where('comp.firma', $fid);

        if ($qOk) {
            $dbQuery->where(function ($search) use ($query) {
                $search->where('d.name', 'LIKE', "%{$query}%")
                    ->orWhere('d.name_ua', 'LIKE', "%{$query}%")
                    ->orWhere('d.name_en', 'LIKE', "%{$query}%")
                    ->orWhere('d.description', 'LIKE', "%{$query}%")
                    ->orWhere('d.description_ua', 'LIKE', "%{$query}%")
                    ->orWhere('d.description_en', 'LIKE', "%{$query}%")
                    ->orWhere('comp.htmlkeyspop', 'LIKE', "%{$query}%");
            });
        }

        foreach ($filters as $filter) {
            $needle = (int) $filter['group_id'] . ':' . (int) $filter['value_id'];
            $dbQuery->where('comp.htmlkeyspop', 'LIKE', '%' . $needle . '%');
        }

        return (int) $dbQuery->limit(101)->count();
    }

    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    private function catalogSearchAction(string $query, string $message, array $extra = []): array
    {
        $query = trim($query);
        $searchUrl = $query !== '' ? '/goods?q=' . rawurlencode($query) : '/goods';

        return array_merge([
            'success' => true,
            'message' => $message,
            'ui_action' => [
                'type' => 'navigate',
                'path' => '',
                'url' => $searchUrl,
                'label' => 'Искать в каталоге',
                'source' => 'open_catalog_category',
            ],
        ], $extra);
    }

    /**
     * @param  array<int, array{id: mixed, name: mixed, path: string}>  $candidates
     * @return array<string, mixed>
     */
    private function catalogClarificationAction(string $message, array $candidates): array
    {
        return [
            'success' => false,
            'message' => $message,
            'candidates' => $candidates,
        ];
    }

    /**
     * @param  array<int, array>  $candidates
     * @return array<int, array{id: mixed, name: mixed, path: string}>
     */
    private function catalogClarificationCandidates(array $candidates): array
    {
        return array_slice(array_values(array_filter(array_map(function (array $item): ?array {
            if (($item['_parentPath'] ?? []) === []) {
                return null;
            }

            return [
                'id' => $item['id'] ?? null,
                'name' => $item['name'] ?? '',
                'path' => $this->buildCatalogPath($item),
            ];
        }, $candidates))), 0, 8);
    }

    /**
     * @return array<int, array{id: mixed, name: mixed, path: string}>
     */
    private function catalogClarificationCandidatesForCategory(int $fid, ?array $category, string $locale = 'ru'): array
    {
        $tree = Field::getCatalogTree($fid, $locale);
        $candidates = $this->flattenCatalogTree($tree->all());

        if ($category === null) {
            return $this->catalogClarificationCandidates($candidates);
        }

        $parentPath = $category['_parentPath'] ?? [];
        $nearestParent = is_array($parentPath) && $parentPath !== [] ? end($parentPath) : null;
        $categoryId = is_array($nearestParent)
            ? (int) ($nearestParent['id'] ?? 0)
            : (int) ($category['id'] ?? 0);
        $out = [];

        foreach ($candidates as $candidate) {
            if ((int) ($candidate['id'] ?? 0) === $categoryId && ($candidate['_parentPath'] ?? []) !== []) {
                $out[] = $candidate;
                continue;
            }

            foreach (($candidate['_parentPath'] ?? []) as $parent) {
                if ((int) ($parent['id'] ?? 0) === $categoryId) {
                    $out[] = $candidate;
                    break;
                }
            }
        }

        return $this->catalogClarificationCandidates($out !== [] ? $out : $candidates);
    }

    private function cleanCatalogNavigationQuery(string $query): string
    {
        $query = mb_strtolower(trim($query));
        $query = preg_replace('/\b(покажи|показать|найди|найти|подбери|подобрать|открой|открыть|show|find|open)\b/iu', ' ', $query) ?? $query;
        $query = preg_replace('/\b(мне|пожалуйста|каталог|раздел|товары|товар|в|на|по)\b/iu', ' ', $query) ?? $query;
        $query = preg_replace('/\s+/u', ' ', $query) ?? $query;

        return trim($query);
    }

    private function isGenericCatalogQuery(string $query): bool
    {
        $normalized = $this->normalizeCatalogSearchText($query);
        $words = array_unique(array_filter(explode(' ', $normalized)));

        if ($words === []) {
            return true;
        }

        return count(array_diff($words, $this->genericCatalogWords())) === 0;
    }

    /**
     * @return array<int, string>
     */
    private function genericCatalogWords(): array
    {
        return [
            'gos',
            'gosudarstvennye',
            'nomer',
            'nomera',
            'nomernye',
            'znak',
            'znaki',
            'avtonomer',
            'avtonomera',
            'avtonomery',
        ];
    }

    private function isGenericCatalogWord(string $word): bool
    {
        return in_array($word, $this->genericCatalogWords(), true);
    }

    private function catalogFilterTermMatches(string $normalizedTerm, string $normalizedLabel): bool
    {
        if ($normalizedTerm === '' || $normalizedLabel === '') {
            return false;
        }

        if (str_contains($normalizedLabel, $normalizedTerm) || str_contains($normalizedTerm, $normalizedLabel)) {
            return true;
        }

        $termWords = array_filter(explode(' ', $normalizedTerm));
        $labelWords = array_filter(explode(' ', $normalizedLabel));

        foreach ($termWords as $termWord) {
            if (mb_strlen($termWord) < 4 || $this->isGenericCatalogWord($termWord)) {
                continue;
            }

            foreach ($labelWords as $labelWord) {
                if (mb_strlen($labelWord) < 4) {
                    continue;
                }

                if (str_starts_with($termWord, $labelWord) || str_starts_with($labelWord, $termWord)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Развернуть дерево каталога в плоский список, сохраняя путь родительских узлов.
     *
     * @param  array<int, array>  $tree
     * @param  array<int, array>  $parentPath  Накопленный путь родителей (для рекурсии)
     * @return array<int, array>
     */
    private function flattenCatalogTree(array $tree, array $parentPath = []): array
    {
        $result = [];

        foreach ($tree as $node) {
            $children = $node['children'] ?? [];
            unset($node['children']);

            // Сохраняем slug каждого родителя для построения полного пути
            $node['_parentPath'] = array_map(function (array $parent): array {
                return [
                    'id' => (int) ($parent['id'] ?? 0),
                    'name' => (string) ($parent['name'] ?? ''),
                    'slug' => $this->getSectionSlug($parent),
                ];
            }, $parentPath);

            $result[] = $node;

            if ($children !== []) {
                $newParentPath = array_merge($parentPath, [$node]);
                $result = array_merge($result, $this->flattenCatalogTree($children, $newParentPath));
            }
        }

        return $result;
    }

    /**
     * Собрать путь к разделу (цепочка родитель → ребенок).
     *
     * @param  array  $node  Элемент каталога
     * @return string  Например "avtonomera/mitsubishi/l200"
     */
    private function buildCatalogPath(array $node): string
    {
        $segments = [];

        // Родительские сегменты из _parentPath
        $parentPath = $node['_parentPath'] ?? [];
        foreach ($parentPath as $parent) {
            if (isset($parent['slug'])) {
                $segments[] = $parent['slug'];
            }
        }

        // Сегмент текущего узла
        $segments[] = $this->getSectionSlug($node);

        return implode('/', $segments);
    }

    /**
     * Получить slug для секции (link > name).
     */
    private function getSectionSlug(array $section): string
    {
        $link = trim((string) ($section['link'] ?? ''));
        if ($link !== '') {
            return rawurlencode($link);
        }

        $name = trim((string) ($section['name'] ?? ''));
        if ($name === '') {
            return (string) ($section['id'] ?? '0');
        }

        $slug = mb_strtolower($name);
        $slug = preg_replace('/\s+/u', '-', $slug) ?? $slug;

        return rawurlencode($slug);
    }

    /**
     * Нормализовать текст для поискового сравнения.
     */
    private function normalizeCatalogSearchText(string $text): string
    {
        $text = mb_strtolower(trim($text));

        $map = [
            'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd',
            'е' => 'e', 'ё' => 'e', 'ж' => 'zh', 'з' => 'z', 'и' => 'i',
            'й' => 'y', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n',
            'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't',
            'у' => 'u', 'ф' => 'f', 'х' => 'kh', 'ц' => 'ts', 'ч' => 'ch',
            'ш' => 'sh', 'щ' => 'shch', 'ъ' => '', 'ы' => 'y', 'ь' => '',
            'э' => 'e', 'ю' => 'yu', 'я' => 'ya',
        ];

        $text = strtr($text, $map);
        $text = preg_replace('/[^a-z0-9\s]/u', ' ', $text) ?? $text;
        $text = preg_replace('/\s+/', ' ', $text) ?? $text;

        return trim($text);
    }

    /**
     * Оценка совпадения запроса с текстом раздела.
     *
     * @return int  Количество баллов (чем больше, тем точнее)
     */
    private function catalogMatchScore(string $normalizedQuery, string $haystack): int
    {
        $queryWords = array_values(array_filter(
            array_unique(array_filter(explode(' ', $normalizedQuery))),
            fn (string $word) => !$this->isGenericCatalogWord($word),
        ));
        $haystackWords = array_unique(array_filter(explode(' ', $haystack)));

        if ($queryWords === [] || $haystackWords === []) {
            return 0;
        }

        $score = 0;

        // Полное совпадение запроса
        if (str_contains($haystack, $normalizedQuery)) {
            $score += 20;
        }

        foreach ($queryWords as $word) {
            if (mb_strlen($word) < 2) {
                continue;
            }

            // Слово найдено в haystack
            if (str_contains($haystack, $word)) {
                $score += 5;
            }

            // Слово начинается так же
            foreach ($haystackWords as $hw) {
                if (str_starts_with($hw, $word)) {
                    $score += 3;
                    break;
                }
            }
        }

        return $score;
    }

    /**
     * Построить системный промпт для консультанта по БД.
     *
     * @param  string   $language Язык
     * @param  int      $fid      ID проекта
     * @param  int|null $firma    ID компании
     * @return string
     */
    public function buildDbConsultantSystemPrompt(string $language, int $fid, ?int $firma): string
    {
        $answerLanguage = match ($language) {
            'ua' => 'українською мовою',
            'en' => 'in English',
            default => 'на русском языке',
        };

        return <<<PROMPT
Ты — консультант по базе данных проекта. Твоя задача — помогать пользователям находить информацию в базе данных.

Ты отвечаешь {$answerLanguage}.

Правила работы:
1. Используй предоставленные функции для поиска информации.
2. Если данные не найдены, сообщи об этом пользователю.
3. Отвечай только на основе найденных данных.
4. Если нужно уточнить запрос — попроси пользователя уточнить.
5. Ты также можешь парсить веб-страницы и сохранять информацию в базу знаний проекта.

Доступные функции:
- search_goods — поиск товаров
- get_goods_by_id — детальная информация о товаре
- search_news — поиск новостей
- get_project_info — информация о проекте
- search_docs — поиск документов
- search_knowledge_base — поиск по базе знаний
- get_goods_categories — список категорий товаров
- open_catalog_category — найти подходящий раздел каталога по названию/типу и вернуть ссылку для перехода
- fetch_and_save_page — загрузить веб-страницу и сохранить в базу знаний
- save_to_knowledge_base — сохранить информацию в базу знаний
PROMPT;
    }
}
