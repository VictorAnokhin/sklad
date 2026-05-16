<?php

namespace App\Services;

use App\Models\ChatMessage;
use App\Models\ChatSession;
use App\Models\Goods;
use App\Models\GoodsCategory;
use App\Models\News;
use App\Models\Project;
use App\Models\Doc;
use App\Models\AiKnowledgeBase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DbQueryService
{
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
     * @param  int      $fid   ID проекта
     * @param  string   $query Поисковый запрос
     * @param  int|null $firma ID компании
     * @param  int      $limit Максимальное количество результатов
     * @return array
     */
    public function searchKnowledgeBase(int $fid, string $query, ?int $firma = null, int $limit = 5): array
    {
        try {
            $kb = AiKnowledgeBase::where('fid', $fid)
                ->where('active', true)
                ->where(function ($q) use ($query) {
                    $q->where('title', 'like', "%{$query}%")
                      ->orWhere('content', 'like', "%{$query}%");
                })
                ->when($firma, fn ($q, $f) => $q->where('firma', $f))
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
            $categories = GoodsCategory::where('fid', $fid)
                ->get(['id', 'cat_name', 'cat_description']);

            if ($categories->isEmpty()) {
                return ['found' => 0, 'items' => []];
            }

            return [
                'found' => $categories->count(),
                'items' => $categories->map(fn (GoodsCategory $c) => [
                    'id' => $c->id,
                    'name' => $c->cat_name,
                    'description' => $c->cat_description,
                ])->toArray(),
            ];
        } catch (\Throwable $e) {
            Log::error("DbQueryService.getGoodsCategories error: " . $e->getMessage());
            return ['found' => 0, 'items' => [], 'error' => 'Database query failed'];
        }
    }

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
                    $firma,
                    (int) ($arguments['limit'] ?? 5),
                ),
                'get_goods_categories' => $this->getGoodsCategories($fid),
                default => throw new \InvalidArgumentException("Unknown tool: {$name}"),
            };

            return json_encode($result, JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            Log::error("DbQueryService.executeTool error: " . $e->getMessage());
            return json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
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

Доступные функции:
- search_goods — поиск товаров
- get_goods_by_id — детальная информация о товаре
- search_news — поиск новостей
- get_project_info — информация о проекте
- search_docs — поиск документов
- search_knowledge_base — поиск по базе знаний
- get_goods_categories — список категорий товаров
PROMPT;
    }
}
