<?php

namespace App\Services;

use App\Models\AnalystResearch;
use App\Models\AnalystSource;
use Illuminate\Support\Facades\Log;
use Throwable;

class AnalystService
{
    /**
     * FID по умолчанию для сохранения данных, если явно не указан.
     * Используется, когда запрос приходит без привязки к проекту.
     */
    private const DEFAULT_FID = 1;

    public function __construct(
        private readonly WebScraperService $scraper,
    ) {}

    /**
     * Получить список tools для DeepSeek function calling.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getTools(): array
    {
        return [
            $this->toolFetchUrl(),
            $this->toolSaveSource(),
            $this->toolSearchSources(),
            $this->toolStartResearch(),
            $this->toolCompleteResearch(),
            $this->toolListResearches(),
            $this->toolSaveKnowledge(),
            $this->toolGetResearchSources(),
            $this->toolPublishArticle(),
            $this->toolPublishNews(),
            $this->toolPublishReview(),
        ];
    }

    /**
     * Исполнить tool по имени.
     *
     * @param  array<string, mixed>  $arguments  Аргументы должны содержать 'fid' для сохранения данных
     */
    public function executeTool(string $name, array $arguments): string
    {
        return match ($name) {
            'fetch_url' => $this->executeFetchUrl($arguments),
            'save_source' => $this->executeSaveSource($arguments),
            'search_sources' => $this->executeSearchSources($arguments),
            'start_research' => $this->executeStartResearch($arguments),
            'complete_research' => $this->executeCompleteResearch($arguments),
            'list_researches' => $this->executeListResearches($arguments),
            'save_knowledge' => $this->executeSaveKnowledge($arguments),
            'get_research_sources' => $this->executeGetResearchSources($arguments),
            'publish_article' => $this->executePublishArticle($arguments),
            'publish_news' => $this->executePublishNews($arguments),
            'publish_review' => $this->executePublishReview($arguments),
            default => json_encode(['error' => "Unknown tool: {$name}"]),
        };
    }

    /**
     * Извлечь FID из аргументов или вернуть DEFAULT_FID.
     */
    private function resolveFid(array $args): int
    {
        return (int) ($args['fid'] ?? self::DEFAULT_FID);
    }

    // ── Определения tools ──────────────────────────────────────────────────

    private function toolFetchUrl(): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => 'fetch_url',
                'description' => 'Загрузить содержимое веб-страницы по URL. Возвращает извлечённый текст, заголовок и тип контента. Используй для сбора информации с сайтов, документации протоколов, новостей.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'url' => [
                            'type' => 'string',
                            'description' => 'Полный URL страницы для загрузки (https://...)',
                        ],
                    ],
                    'required' => ['url'],
                ],
            ],
        ];
    }

    private function toolSaveSource(): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => 'save_source',
                'description' => 'Сохранить источник данных (ссылку/страницу) в базу знаний аналитика. Используй после fetch_url, чтобы сохранить полезную информацию.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'url' => [
                            'type' => 'string',
                            'description' => 'URL источника',
                        ],
                        'title' => [
                            'type' => 'string',
                            'description' => 'Заголовок/название источника',
                        ],
                        'summary' => [
                            'type' => 'string',
                            'description' => 'Краткое резюме содержимого (2-5 предложений)',
                        ],
                        'content_type' => [
                            'type' => 'string',
                            'description' => 'Тип контента: website, news, documentation, protocol, social, api, other',
                            'enum' => ['website', 'news', 'documentation', 'protocol', 'social', 'api', 'other'],
                        ],
                        'fid' => [
                            'type' => 'integer',
                            'description' => 'ID проекта (fid), к которому относится источник',
                            'default' => 1,
                        ],
                    ],
                    'required' => ['url', 'title', 'summary', 'content_type'],
                ],
            ],
        ];
    }

    private function toolSearchSources(): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => 'search_sources',
                'description' => 'Поиск по сохранённым источникам. Позволяет найти ранее собранную информацию по ключевым словам. Ищет по ВСЕМ проектам.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'query' => [
                            'type' => 'string',
                            'description' => 'Поисковый запрос',
                        ],
                        'limit' => [
                            'type' => 'integer',
                            'description' => 'Максимум результатов (по умолч. 10)',
                            'default' => 10,
                        ],
                    ],
                    'required' => ['query'],
                ],
            ],
        ];
    }

    private function toolStartResearch(): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => 'start_research',
                'description' => 'Начать новое исследование по теме. Создаёт сессию исследования, к которой можно прикреплять источники.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'topic' => [
                            'type' => 'string',
                            'description' => 'Тема исследования (например: "Анализ протокола Suilend", "Обзор DeFi на Sui")',
                        ],
                        'fid' => [
                            'type' => 'integer',
                            'description' => 'ID проекта (fid)',
                            'default' => 1,
                        ],
                    ],
                    'required' => ['topic'],
                ],
            ],
        ];
    }

    private function toolCompleteResearch(): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => 'complete_research',
                'description' => 'Завершить исследование с итоговым анализом. Сохраняет финальный отчёт. Используй после того, как собрал достаточно источников.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'research_id' => [
                            'type' => 'integer',
                            'description' => 'ID исследования (полученный из start_research)',
                        ],
                        'summary' => [
                            'type' => 'string',
                            'description' => 'Итоговый анализ: ключевые выводы, метрики, риски, возможности',
                        ],
                    ],
                    'required' => ['research_id', 'summary'],
                ],
            ],
        ];
    }

    private function toolListResearches(): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => 'list_researches',
                'description' => 'Получить список всех исследований (завершённых и в процессе) по ВСЕМ проектам.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'limit' => [
                            'type' => 'integer',
                            'description' => 'Максимум результатов (по умолч. 20)',
                            'default' => 20,
                        ],
                    ],
                    'required' => [],
                ],
            ],
        ];
    }

    private function toolSaveKnowledge(): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => 'save_knowledge',
                'description' => 'Сохранить аналитическую заметку или вывод в базу знаний проекта. Используй для сохранения ценных инсайтов, которые могут пригодиться в будущем.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'title' => [
                            'type' => 'string',
                            'description' => 'Заголовок заметки',
                        ],
                        'content' => [
                            'type' => 'string',
                            'description' => 'Содержание заметки (анализ, выводы, метрики)',
                        ],
                        'category' => [
                            'type' => 'string',
                            'description' => 'Категория: defi, protocol, token, market, news, analysis, strategy, security',
                            'default' => 'analysis',
                        ],
                        'fid' => [
                            'type' => 'integer',
                            'description' => 'ID проекта (fid), к которому относится знание',
                            'default' => 1,
                        ],
                    ],
                    'required' => ['title', 'content'],
                ],
            ],
        ];
    }

    private function toolGetResearchSources(): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => 'get_research_sources',
                'description' => 'Получить все источники, прикреплённые к исследованию.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'research_id' => [
                            'type' => 'integer',
                            'description' => 'ID исследования',
                        ],
                    ],
                    'required' => ['research_id'],
                ],
            ],
        ];
    }

    // ── Исполнение tools ───────────────────────────────────────────────────

    private function executeFetchUrl(array $args): string
    {
        $url = trim((string) ($args['url'] ?? ''));

        if ($url === '') {
            return json_encode(['error' => 'URL is required']);
        }

        $result = $this->scraper->fetchUrl($url);

        return json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    private function executeSaveSource(array $args): string
    {
        $fid = $this->resolveFid($args);

        try {
            $source = AnalystSource::create([
                'fid' => $fid,
                'url' => $args['url'] ?? '',
                'title' => $args['title'] ?? '',
                'summary' => $args['summary'] ?? '',
                'content_type' => $args['content_type'] ?? 'website',
                'content' => $args['content'] ?? null,
            ]);

            return json_encode([
                'success' => true,
                'id' => $source->id,
                'fid' => $fid,
                'message' => "Источник сохранён в проекте fid={$fid}: {$source->title}",
            ], JSON_UNESCAPED_UNICODE);

        } catch (Throwable $e) {
            Log::error('Analyst: save_source failed.', ['fid' => $fid, 'error' => $e->getMessage()]);
            return json_encode(['error' => 'Ошибка сохранения источника: ' . $e->getMessage()]);
        }
    }

    private function executeSearchSources(array $args): string
    {
        $query = trim((string) ($args['query'] ?? ''));
        $limit = min((int) ($args['limit'] ?? 10), 50);
        $fid = $this->resolveFid($args);

        if ($query === '') {
            return json_encode(['error' => 'Query is required']);
        }

        try {
            // Поиск по ВСЕМ проектам (не фильтруем по fid)
            $sources = AnalystSource::search($query)
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get(['id', 'fid', 'title', 'url', 'summary', 'content_type', 'created_at']);

            return json_encode([
                'found' => $sources->count(),
                'search_fid' => $fid,
                'searched_all_projects' => true,
                'sources' => $sources->toArray(),
            ], JSON_UNESCAPED_UNICODE);

        } catch (Throwable $e) {
            Log::error('Analyst: search_sources failed.', ['error' => $e->getMessage()]);
            return json_encode(['error' => 'Ошибка поиска: ' . $e->getMessage()]);
        }
    }

    private function executeStartResearch(array $args): string
    {
        $topic = trim((string) ($args['topic'] ?? ''));
        $fid = $this->resolveFid($args);

        if ($topic === '') {
            return json_encode(['error' => 'Topic is required']);
        }

        try {
            $research = AnalystResearch::create([
                'fid' => $fid,
                'topic' => $topic,
                'status' => 'in_progress',
            ]);

            return json_encode([
                'success' => true,
                'id' => $research->id,
                'fid' => $fid,
                'topic' => $research->topic,
                'message' => "Исследование «{$topic}» начато в проекте fid={$fid}. ID: {$research->id}",
            ], JSON_UNESCAPED_UNICODE);

        } catch (Throwable $e) {
            Log::error('Analyst: start_research failed.', ['fid' => $fid, 'error' => $e->getMessage()]);
            return json_encode(['error' => 'Ошибка создания исследования: ' . $e->getMessage()]);
        }
    }

    private function executeCompleteResearch(array $args): string
    {
        $researchId = (int) ($args['research_id'] ?? 0);
        $summary = trim((string) ($args['summary'] ?? ''));

        if ($researchId <= 0) {
            return json_encode(['error' => 'research_id is required']);
        }

        if ($summary === '') {
            return json_encode(['error' => 'summary is required']);
        }

        try {
            $research = AnalystResearch::find($researchId);

            if ($research === null) {
                return json_encode(['error' => "Исследование #{$researchId} не найдено"]);
            }

            $research->complete($summary);

            return json_encode([
                'success' => true,
                'id' => $research->id,
                'fid' => $research->fid,
                'topic' => $research->topic,
                'sources_count' => $research->sources()->count(),
                'message' => "Исследование «{$research->topic}» завершено.",
            ], JSON_UNESCAPED_UNICODE);

        } catch (Throwable $e) {
            Log::error('Analyst: complete_research failed.', ['error' => $e->getMessage()]);
            return json_encode(['error' => 'Ошибка завершения исследования: ' . $e->getMessage()]);
        }
    }

    private function executeListResearches(array $args): string
    {
        $limit = min((int) ($args['limit'] ?? 20), 50);
        $fid = $this->resolveFid($args);

        try {
            // Показываем исследования ВСЕХ проектов
            $researches = AnalystResearch::orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();

            return json_encode([
                'found' => $researches->count(),
                'listed_all_projects' => true,
                'researches' => $researches->map(fn (AnalystResearch $r) => [
                    'id' => $r->id,
                    'fid' => $r->fid,
                    'topic' => $r->topic,
                    'summary' => $r->summary,
                    'status' => $r->status,
                    'sources_count' => $r->sources()->count(),
                    'created_at' => $r->created_at?->toIso8601String(),
                    'updated_at' => $r->updated_at?->toIso8601String(),
                ])->values()->toArray(),
            ], JSON_UNESCAPED_UNICODE);

        } catch (Throwable $e) {
            Log::error('Analyst: list_researches failed.', ['error' => $e->getMessage()]);
            return json_encode(['error' => 'Ошибка получения списка: ' . $e->getMessage()]);
        }
    }

    private function executeSaveKnowledge(array $args): string
    {
        $title = trim((string) ($args['title'] ?? ''));
        $content = trim((string) ($args['content'] ?? ''));
        $category = trim((string) ($args['category'] ?? 'analysis'));
        $fid = $this->resolveFid($args);

        if ($title === '' || $content === '') {
            return json_encode(['error' => 'title and content are required']);
        }

        try {
            // Сохраняем как в analyst_sources (для контекста аналитика)
            $source = AnalystSource::create([
                'fid' => $fid,
                'url' => null,
                'title' => $title,
                'content' => $content,
                'summary' => mb_substr($content, 0, 500),
                'content_type' => 'analysis',
                'metadata' => ['category' => $category],
            ]);

            // Также сохраняем в базу знаний AI (AiKnowledgeBase), если есть модель
            $kbSaved = false;
            if (class_exists(\App\Models\AiKnowledgeBase::class)) {
                try {
                    \App\Models\AiKnowledgeBase::create([
                        'fid' => $fid,
                        'title' => $title,
                        'content' => $content,
                        'category' => $category,
                        'source' => 'analyst',
                        'active' => true,
                    ]);
                    $kbSaved = true;
                } catch (Throwable $e) {
                    Log::warning('Analyst: failed to save to AiKnowledgeBase.', ['fid' => $fid, 'error' => $e->getMessage()]);
                }
            }

            return json_encode([
                'success' => true,
                'source_id' => $source->id,
                'fid' => $fid,
                'knowledge_base_saved' => $kbSaved,
                'message' => "Знание «{$title}» сохранено в проекте fid={$fid}.",
            ], JSON_UNESCAPED_UNICODE);

        } catch (Throwable $e) {
            Log::error('Analyst: save_knowledge failed.', ['fid' => $fid, 'error' => $e->getMessage()]);
            return json_encode(['error' => 'Ошибка сохранения знания: ' . $e->getMessage()]);
        }
    }

    private function executeGetResearchSources(array $args): string
    {
        $researchId = (int) ($args['research_id'] ?? 0);

        if ($researchId <= 0) {
            return json_encode(['error' => 'research_id is required']);
        }

        try {
            $research = AnalystResearch::find($researchId);

            if ($research === null) {
                return json_encode(['error' => "Исследование #{$researchId} не найдено"]);
            }

            $sources = $research->sources()
                ->orderBy('created_at', 'desc')
                ->get(['id', 'fid', 'title', 'url', 'summary', 'content_type', 'created_at']);

            return json_encode([
                'research_id' => $research->id,
                'topic' => $research->topic,
                'status' => $research->status,
                'summary' => $research->summary,
                'sources_count' => $sources->count(),
                'sources' => $sources->toArray(),
            ], JSON_UNESCAPED_UNICODE);

        } catch (Throwable $e) {
            Log::error('Analyst: get_research_sources failed.', ['error' => $e->getMessage()]);
            return json_encode(['error' => 'Ошибка получения источников: ' . $e->getMessage()]);
        }
    }

    // ── Инструменты публикации (для TelegramAgent) ──────────────────────────

    private function toolPublishArticle(): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => 'publish_article',
                'description' => 'Опубликовать аналитическую статью в базу знаний проекта. Статья становится доступна всем агентам и в /settings → База знаний.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'title' => [
                            'type' => 'string',
                            'description' => 'Заголовок статьи',
                        ],
                        'content' => [
                            'type' => 'string',
                            'description' => 'Полный текст статьи',
                        ],
                        'summary' => [
                            'type' => 'string',
                            'description' => 'Краткое описание (2-3 предложения)',
                        ],
                        'fid' => [
                            'type' => 'integer',
                            'description' => 'ID проекта (fid), к которому относится статья. Определи из контекста',
                            'default' => 1,
                        ],
                    ],
                    'required' => ['title', 'content', 'summary'],
                ],
            ],
        ];
    }

    private function toolPublishNews(): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => 'publish_news',
                'description' => 'Опубликовать новость в базу знаний проекта. Новость становится доступна всем агентам.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'title' => [
                            'type' => 'string',
                            'description' => 'Заголовок новости',
                        ],
                        'content' => [
                            'type' => 'string',
                            'description' => 'Текст новости',
                        ],
                        'summary' => [
                            'type' => 'string',
                            'description' => 'Краткое описание',
                        ],
                        'source_url' => [
                            'type' => 'string',
                            'description' => 'URL источника новости (если есть)',
                        ],
                        'fid' => [
                            'type' => 'integer',
                            'description' => 'ID проекта (fid)',
                            'default' => 1,
                        ],
                    ],
                    'required' => ['title', 'content'],
                ],
            ],
        ];
    }

    private function toolPublishReview(): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => 'publish_review',
                'description' => 'Опубликовать обзор/рецензию на продукт, протокол или сервис в базу знаний.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'title' => [
                            'type' => 'string',
                            'description' => 'Заголовок обзора',
                        ],
                        'content' => [
                            'type' => 'string',
                            'description' => 'Полный текст обзора: анализ, метрики, плюсы/минусы, выводы',
                        ],
                        'summary' => [
                            'type' => 'string',
                            'description' => 'Краткое резюме',
                        ],
                        'rating' => [
                            'type' => 'integer',
                            'description' => 'Оценка от 1 до 10',
                            'default' => null,
                        ],
                        'fid' => [
                            'type' => 'integer',
                            'description' => 'ID проекта (fid)',
                            'default' => 1,
                        ],
                    ],
                    'required' => ['title', 'content', 'summary'],
                ],
            ],
        ];
    }

    /**
     * Сохранить публикацию в AiKnowledgeBase и в analyst_sources.
     */
    private function savePublication(string $title, string $content, string $summary, int $fid, string $category, ?string $sourceUrl = null, ?int $rating = null): array
    {
        $saved = [
            'knowledge_base' => false,
            'analyst_source' => false,
        ];

        // Сохраняем в AiKnowledgeBase
        try {
            \App\Models\AiKnowledgeBase::create([
                'fid' => $fid,
                'title' => $title,
                'content' => $content,
                'category' => $category,
                'source' => 'telegram_agent',
                'active' => true,
            ]);
            $saved['knowledge_base'] = true;
        } catch (Throwable $e) {
            Log::warning("Analyst: savePublication (AiKnowledgeBase, {$category}) failed.", [
                'fid' => $fid,
                'error' => $e->getMessage(),
            ]);
        }

        // Сохраняем в analyst_sources
        try {
            $extra = ['category' => $category];
            if ($rating !== null) {
                $extra['rating'] = $rating;
            }
            if ($sourceUrl !== null) {
                $extra['source_url'] = $sourceUrl;
            }

            \App\Models\AnalystSource::create([
                'fid' => $fid,
                'url' => $sourceUrl,
                'title' => $title,
                'content' => $content,
                'summary' => $summary,
                'content_type' => $category,
                'metadata' => $extra,
            ]);
            $saved['analyst_source'] = true;
        } catch (Throwable $e) {
            Log::warning("Analyst: savePublication (AnalystSource, {$category}) failed.", [
                'fid' => $fid,
                'error' => $e->getMessage(),
            ]);
        }

        return $saved;
    }

    private function executePublishArticle(array $args): string
    {
        $title = trim((string) ($args['title'] ?? ''));
        $content = trim((string) ($args['content'] ?? ''));
        $summary = trim((string) ($args['summary'] ?? ''));
        $fid = $this->resolveFid($args);

        if ($title === '' || $content === '') {
            return json_encode(['error' => 'title and content are required']);
        }

        $saved = $this->savePublication($title, $content, $summary, $fid, 'article');

        return json_encode([
            'success' => $saved['knowledge_base'] || $saved['analyst_source'],
            'title' => $title,
            'fid' => $fid,
            'category' => 'article',
            'saved_to' => $saved,
            'message' => "Статья «{$title}» опубликована в проекте fid={$fid}.",
        ], JSON_UNESCAPED_UNICODE);
    }

    private function executePublishNews(array $args): string
    {
        $title = trim((string) ($args['title'] ?? ''));
        $content = trim((string) ($args['content'] ?? ''));
        $summary = trim((string) ($args['summary'] ?? ''));
        $sourceUrl = trim((string) ($args['source_url'] ?? ''));
        $fid = $this->resolveFid($args);

        if ($title === '' || $content === '') {
            return json_encode(['error' => 'title and content are required']);
        }

        $saved = $this->savePublication(
            $title, $content, $summary, $fid,
            category: 'news',
            sourceUrl: $sourceUrl ?: null,
        );

        return json_encode([
            'success' => $saved['knowledge_base'] || $saved['analyst_source'],
            'title' => $title,
            'fid' => $fid,
            'category' => 'news',
            'saved_to' => $saved,
            'message' => "Новость «{$title}» опубликована в проекте fid={$fid}.",
        ], JSON_UNESCAPED_UNICODE);
    }

    private function executePublishReview(array $args): string
    {
        $title = trim((string) ($args['title'] ?? ''));
        $content = trim((string) ($args['content'] ?? ''));
        $summary = trim((string) ($args['summary'] ?? ''));
        $fid = $this->resolveFid($args);
        $rating = isset($args['rating']) ? (int) $args['rating'] : null;

        if ($title === '' || $content === '') {
            return json_encode(['error' => 'title and content are required']);
        }

        $saved = $this->savePublication($title, $content, $summary, $fid, category: 'review', rating: $rating);

        return json_encode([
            'success' => $saved['knowledge_base'] || $saved['analyst_source'],
            'title' => $title,
            'fid' => $fid,
            'category' => 'review',
            'rating' => $rating,
            'saved_to' => $saved,
            'message' => "Обзор «{$title}» опубликован в проекте fid={$fid}.",
        ], JSON_UNESCAPED_UNICODE);
    }
}
