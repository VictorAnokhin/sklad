<?php

namespace App\Services;

use App\Models\AiKnowledgeBase;
use App\Models\Field;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Сервис для выполнения запросов к базе данных проекта
 * для AI-консультанта (function calling).
 *
 * Каждый метод должен возвращать массив, который легко сериализуется в JSON.
 */
class DbQueryService
{
    // ── Goods (товары, таблица comp + descript) ───────────────────────

    /**
     * Поиск товаров по названию или артикулу.
     *
     * @param  int     $fid     ID проекта (firma в таблице comp)
     * @param  string  $query   Поисковый запрос
     * @param  int     $limit   Максимум результатов
     * @return array<int, array<string, mixed>>
     */
    public function searchGoods(int $fid, string $query, int $limit = 5): array
    {
        try {
            $results = DB::table('comp')
                ->leftJoin('descript', function ($join) {
                    $join->on('descript.pnum', '=', 'comp.id')
                         ->whereColumn('descript.firma', '=', 'comp.firma');
                })
                ->where('comp.firma', $fid)
                ->where('comp.web', '1')
                ->where(function ($q) use ($query) {
                    $q->where('descript.name', 'like', "%{$query}%")
                      ->orWhere('descript.name_ua', 'like', "%{$query}%")
                      ->orWhere('descript.name_en', 'like', "%{$query}%")
                      ->orWhere('comp.nickname', 'like', "%{$query}%")
                      ->orWhere('comp.namedoc', 'like', "%{$query}%")
                      ->orWhere('descript.description', 'like', "%{$query}%");
                })
                ->select(
                    'comp.id',
                    'comp.nickname',
                    'comp.pay',
                    'comp.sklad',
                    'comp.nfoto',
                    DB::raw("COALESCE(NULLIF(descript.name, ''), NULLIF(descript.name_ua, ''), NULLIF(descript.name_en, ''), comp.nickname, comp.namedoc, CONCAT('Товар #', comp.id)) as name"),
                    DB::raw("COALESCE(NULLIF(descript.description, ''), NULLIF(descript.description_ua, ''), NULLIF(descript.description_en, ''), '') as description")
                )
                ->orderByDesc('comp.top')
                ->orderByDesc('comp.hit')
                ->limit($limit)
                ->get();

            return $results->map(function ($item): array {
                return [
                    'id' => (int) $item->id,
                    'name' => $item->name ?? '',
                    'code' => trim((string) ($item->nickname ?? '')),
                    'price' => (float) ($item->pay ?? 0),
                    'in_stock' => (int) ($item->sklad ?? 0) === 1,
                    'description' => mb_substr($item->description ?? '', 0, 500),
                ];
            })->toArray();
        } catch (Throwable $e) {
            Log::warning('DbQuery: searchGoods failed.', [
                'fid' => $fid,
                'query' => $query,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Получить товар по ID.
     */
    public function getGoodsById(int $fid, int $goodsId): ?array
    {
        try {
            $item = DB::table('comp')
                ->leftJoin('descript', function ($join) {
                    $join->on('descript.pnum', '=', 'comp.id')
                         ->whereColumn('descript.firma', '=', 'comp.firma');
                })
                ->where('comp.id', $goodsId)
                ->where('comp.firma', $fid)
                ->select(
                    'comp.id',
                    'comp.nickname',
                    'comp.pay',
                    'comp.pay1',
                    'comp.sklad',
                    'comp.ostatok',
                    'comp.nfoto',
                    'comp.htmldescr',
                    DB::raw("COALESCE(NULLIF(descript.name, ''), NULLIF(descript.name_ua, ''), NULLIF(descript.name_en, ''), comp.nickname, comp.namedoc, CONCAT('Товар #', comp.id)) as name"),
                    DB::raw("COALESCE(NULLIF(descript.description, ''), NULLIF(descript.description_ua, ''), NULLIF(descript.description_en, ''), '') as description"),
                    DB::raw("COALESCE(NULLIF(descript.description_ua, ''), '') as description_ua"),
                    DB::raw("COALESCE(NULLIF(descript.description_en, ''), '') as description_en"),
                )
                ->first();

            if (! $item) {
                return null;
            }

            return [
                'id' => (int) $item->id,
                'name' => $item->name ?? '',
                'code' => trim((string) ($item->nickname ?? '')),
                'price' => (float) ($item->pay ?? 0),
                'price_wholesale' => (float) ($item->pay1 ?? 0),
                'in_stock' => (int) ($item->sklad ?? 0) === 1,
                'stock_quantity' => (int) ($item->ostatok ?? 0),
                'description' => $item->description ?? '',
                'description_ua' => $item->description_ua ?? '',
                'description_en' => $item->description_en ?? '',
                'meta_description' => $item->htmldescr ?? '',
            ];
        } catch (Throwable $e) {
            Log::warning('DbQuery: getGoodsById failed.', [
                'fid' => $fid,
                'goods_id' => $goodsId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    // ── News (новости, таблица news) ──────────────────────────────────

    /**
     * Поиск новостей проекта.
     *
     * @param  int     $fid    ID проекта (firma в таблице news)
     * @param  string  $query  Поисковый запрос
     * @param  int     $limit  Максимум результатов
     * @return array<int, array<string, mixed>>
     */
    public function searchNews(int $fid, string $query, int $limit = 5): array
    {
        try {
            $results = DB::table('news')
                ->where('firma', $fid)
                ->where(function ($q) use ($query) {
                    $q->where('title', 'like', "%{$query}%")
                      ->orWhere('title_ua', 'like', "%{$query}%")
                      ->orWhere('title_en', 'like', "%{$query}%")
                      ->orWhere('kratko', 'like', "%{$query}%")
                      ->orWhere('txt', 'like', "%{$query}%");
                })
                ->orderByDesc('hot')
                ->orderByDesc('id')
                ->limit($limit)
                ->get();

            return $results->map(function ($item): array {
                $title = trim((string) ($item->title ?? ''))
                    ?: trim((string) ($item->title_ua ?? ''))
                    ?: trim((string) ($item->title_en ?? ''));

                $excerpt = trim((string) ($item->kratko ?? ''));
                $body = trim((string) ($item->txt ?? ''));

                return [
                    'id' => (int) $item->id,
                    'title' => $title,
                    'excerpt' => mb_substr($excerpt ?: $body, 0, 300),
                    'body_preview' => mb_substr($body, 0, 500),
                    'date' => $item->dt ?? '',
                ];
            })->toArray();
        } catch (Throwable $e) {
            Log::warning('DbQuery: searchNews failed.', [
                'fid' => $fid,
                'query' => $query,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Получить новость по ID.
     */
    public function getNewsById(int $fid, int $newsId): ?array
    {
        try {
            $item = DB::table('news')
                ->where('id', $newsId)
                ->where('firma', $fid)
                ->first();

            if (! $item) {
                return null;
            }

            $title = trim((string) ($item->title ?? ''))
                ?: trim((string) ($item->title_ua ?? ''))
                ?: trim((string) ($item->title_en ?? ''));

            return [
                'id' => (int) $item->id,
                'title' => $title,
                'title_ru' => $item->title ?? '',
                'title_ua' => $item->title_ua ?? '',
                'title_en' => $item->title_en ?? '',
                'excerpt' => $item->kratko ?? '',
                'excerpt_ua' => $item->kratko_ua ?? '',
                'excerpt_en' => $item->kratko_en ?? '',
                'body' => $item->txt ?? '',
                'body_ua' => $item->txt_ua ?? '',
                'body_en' => $item->txt_en ?? '',
                'date' => $item->dt ?? '',
            ];
        } catch (Throwable $e) {
            Log::warning('DbQuery: getNewsById failed.', [
                'fid' => $fid,
                'news_id' => $newsId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    // ── Project (проект, таблица project) ─────────────────────────────

    /**
     * Получить информацию о проекте.
     */
    public function getProjectInfo(int $fid): ?array
    {
        try {
            $project = DB::table('project')->where('id', $fid)->first();

            if (! $project) {
                return null;
            }

            return [
                'id' => (int) $project->id,
                'name' => $project->name ?? '',
                'url' => $project->url ?? '',
                'phone' => $project->phone ?? '',
                'email' => $project->email ?? '',
                'full_name' => $project->full_name ?? '',
                'inn' => $project->inn ?? '',
                'address' => $project->address ?? '',
                'description' => $project->description ?? '',
            ];
        } catch (Throwable $e) {
            Log::warning('DbQuery: getProjectInfo failed.', [
                'fid' => $fid,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    // ── Docs (документы, таблица docs) ────────────────────────────────

    /**
     * Поиск документов проекта.
     *
     * @param  int     $fid    ID проекта (firma в таблице docs)
     * @param  string  $query  Поисковый запрос
     * @param  int     $limit  Максимум результатов
     * @return array<int, array<string, mixed>>
     */
    public function searchDocs(int $fid, string $query, int $limit = 5): array
    {
        try {
            $results = DB::table('docs')
                ->where('firma', $fid)
                ->where(function ($q) use ($query) {
                    $q->where('title', 'like', "%{$query}%")
                      ->orWhere('body', 'like', "%{$query}%");
                })
                ->limit($limit)
                ->get(['id', 'title', 'body']);

            return $results->map(function ($item): array {
                return [
                    'id' => (int) $item->id,
                    'title' => $item->title ?? '',
                    'body_preview' => mb_substr($item->body ?? '', 0, 500),
                ];
            })->toArray();
        } catch (Throwable $e) {
            Log::warning('DbQuery: searchDocs failed.', [
                'fid' => $fid,
                'query' => $query,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    // ── Knowledge Base (база знаний AI) ────────────────────────────────

    /**
     * Поиск по базе знаний AI.
     *
     * @param  int     $fid    ID проекта
     * @param  string  $query  Поисковый запрос
     * @param  int|null $firma ID компании (опционально)
     * @param  int     $limit  Максимум результатов
     * @return array<int, array<string, mixed>>
     */
    public function searchKnowledgeBase(int $fid, string $query, ?int $firma = null, int $limit = 5): array
    {
        try {
            $results = AiKnowledgeBase::forFid($fid)
                ->forFirma($firma)
                ->active()
                ->where(function ($q) use ($query) {
                    $q->where('title', 'like', "%{$query}%")
                      ->orWhere('content', 'like', "%{$query}%");
                })
                ->limit($limit)
                ->get(['id', 'title', 'content', 'category', 'source']);

            return $results->map(function (AiKnowledgeBase $item): array {
                return [
                    'id' => $item->id,
                    'title' => $item->title,
                    'content' => $item->content,
                    'category' => $item->category,
                    'source' => $item->source,
                ];
            })->toArray();
        } catch (Throwable $e) {
            Log::warning('DbQuery: searchKnowledgeBase failed.', [
                'fid' => $fid,
                'query' => $query,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    // ── Field (категории товаров, таблица field) ───────────────────────

    /**
     * Получить категории/разделы товаров проекта.
     */
    public function getGoodsCategories(int $fid): array
    {
        try {
            $categories = DB::table('field')
                ->where('keyfield', 'catalog')
                ->where('firma', $fid)
                ->orderBy('num')
                ->orderBy('id')
                ->get(['id', 'val', 'valua', 'valen', 'idkeyfield']);

            return $categories->map(function ($item): array {
                return [
                    'id' => (int) $item->id,
                    'name' => $item->val ?? '',
                    'name_ua' => $item->valua ?? '',
                    'name_en' => $item->valen ?? '',
                    'parent_id' => $item->idkeyfield !== null && $item->idkeyfield !== '' && $item->idkeyfield !== '0'
                        ? (int) $item->idkeyfield
                        : null,
                ];
            })->toArray();
        } catch (Throwable $e) {
            Log::warning('DbQuery: getGoodsCategories failed.', [
                'fid' => $fid,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    // ── Определение tools для DeepSeek function calling ────────────────

    /**
     * Получить список инструментов (tools) для DeepSeek function calling.
     *
     * @return array<int, array<string, mixed>>
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
                        'properties' => [],
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
                        'properties' => [],
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
                default => ['error' => "Unknown function: {$name}"],
            };

            return json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } catch (Throwable $e) {
            Log::warning('DbQuery: executeTool failed.', [
                'function' => $name,
                'arguments' => $arguments,
                'error' => $e->getMessage(),
            ]);

            return json_encode([
                'error' => 'Internal error while executing function.',
                'message' => config('app.debug') ? $e->getMessage() : null,
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * Сформировать system prompt для функции "консультант по БД".
     */
    public function buildDbConsultantSystemPrompt(string $language, int $fid, ?int $firma): string
    {
        $answerLanguage = match ($language) {
            'ua' => 'українській',
            'en' => 'английском',
            default => 'русском',
        };

        return <<<PROMPT
Ты — консультант по базе данных проекта. Отвечай на {$answerLanguage} языке.

Твоя задача — помогать пользователям находить информацию в базе данных проекта.
У тебя есть доступ к следующим данным через функции:

1. **search_goods(query, limit)** — поиск товаров по названию, артикулу или описанию
2. **get_goods_by_id(goods_id)** — детальная информация о товаре
3. **get_goods_categories()** — список категорий товаров
4. **search_news(query, limit)** — поиск новостей проекта
5. **get_project_info()** — информация о проекте
6. **search_docs(query, limit)** — поиск документов/статей
7. **search_knowledge_base(query, limit)** — поиск по базе знаний AI

Правила:
- Используй функции для поиска информации, не выдумывай данные.
- Если пользователь спрашивает про товары — используй search_goods.
- Если пользователь спрашивает про новости — используй search_news.
- Если пользователь спрашивает про проект в целом — используй get_project_info.
- Если данных не найдено — честно сообщи об этом.
- Предлагай пользователю уточнить запрос, если搜索结果 пустой.
- Для навигации по категориям используй get_goods_categories.
- Отвечай кратко, структурированно и полезно.
PROMPT;
    }
}
