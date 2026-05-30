<?php

namespace App\Services;

use App\Contracts\AiClientInterface;
use App\Models\ChatMessage;
use App\Models\ChatSession;
use App\Models\AiTool;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Throwable;

class ChatService
{
    private const ANALYST_FID = 1;
    private const SHARED_AI_CHANNEL = 'web_chat';

    private AiClientInterface $ai;

    /**
     * Перехваченные ui_action из вызовов инструментов модели.
     * Заполняется в toolExecutor, сбрасывается перед каждым sendMessage.
     *
     * @var array<int, array{type: string, path: string, label: string, source: string}>
     */
    private array $capturedActions = [];

    public function __construct(
        private readonly AiKnowledgeService $knowledgeService,
        private readonly WebChatKnowledgeCurator $knowledgeCurator,
        private readonly WebChatIntentDetector $intentDetector,
        private readonly DbQueryService $dbQuery,
        private readonly AiClientFactory $aiFactory,
        private readonly ManagerAiBridgeClient $managerAiBridge,
    ) {
        $this->ai = $this->aiFactory->make(self::SHARED_AI_CHANNEL);
    }

    /**
     * Переключить AI-клиента на другой канал/провайдер.
     *
     * @param  string  $channel  Ключ канала из config('ai.channels')
     * @return $this
     */
    public function useChannel(string $channel): static
    {
        $this->ai = $this->aiFactory->make($channel);

        return $this;
    }

    /**
     * Переключить AI-клиента на конкретного провайдера.
     *
     * @param  string  $provider  Ключ провайдера (deepseek, openai, atoma)
     * @param  string|null  $model  Опционально: модель
     * @return $this
     */
    public function useProvider(string $provider, ?string $model = null): static
    {
        $this->ai = $this->aiFactory->makeForProvider($provider);

        if ($model !== null) {
            $this->ai->setModel($model);
        }

        return $this;
    }

    /**
     * Получить текущего AI-клиента.
     */
    public function getAiClient(): AiClientInterface
    {
        return $this->ai;
    }

    // ── Управление сессиями ─────────────────────────────────────────────

    /**
     * Создать или получить существующую сессию.
     *
     * @param  array<string, mixed>  $params
     */
    public function resolveSession(array $params = []): ChatSession
    {
        $token = trim((string) ($params['session_token'] ?? ''));

        if ($token !== '') {
            $session = ChatSession::resolveByToken($token);
            if ($session !== null) {
                return $session;
            }
        }

        return ChatSession::createSession([
            'user_id' => isset($params['user_id']) ? (int) $params['user_id'] : null,
            'fid' => isset($params['fid']) ? (int) $params['fid'] : null,
            'firma' => isset($params['firma']) ? (int) $params['firma'] : null,
            'wallet' => trim((string) ($params['wallet'] ?? '')),
            'language' => trim((string) ($params['language'] ?? 'ru')),
            'page' => trim((string) ($params['page'] ?? '')),
        ]);
    }

    /**
     * Получить сессии пользователя.
     *
     * @return Collection<int, ChatSession>
     */
    public function getUserSessions(?int $userId, string $sessionToken = '', ?int $fid = null, int $limit = 50): Collection
    {
        $query = ChatSession::active()->orderByDesc('created_at');

        if ($userId !== null) {
            $query->where('user_id', $userId);
        } elseif ($sessionToken !== '') {
            $query->where('session_token', $sessionToken);
        } else {
            return collect();
        }

        if ($fid !== null) {
            $query->where('fid', $fid);
        }

        return $query->limit($limit)->get();
    }

    /**
     * Найти сессию по токену.
     */
    public function findSession(string $sessionToken): ?ChatSession
    {
        return ChatSession::where('session_token', $sessionToken)->first();
    }

    /**
     * Архивировать сессию.
     */
    public function archiveSession(string $sessionToken): bool
    {
        $session = $this->findSession($sessionToken);
        if ($session === null) {
            return false;
        }

        return $session->update(['status' => 'archived']);
    }

    /**
     * Удалить сессию и все её сообщения.
     */
    public function deleteSession(string $sessionToken): bool
    {
        $session = $this->findSession($sessionToken);
        if ($session === null) {
            return false;
        }

        return $session->delete() !== null;
    }

    // ── Отправка сообщений ──────────────────────────────────────────────

    /**
     * Отправить сообщение в чат и получить ответ от AI.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function sendMessage(array $payload): array
    {
        $session = $this->resolveSession($payload);

        $message = trim((string) ($payload['message'] ?? ''));
        $language = trim((string) ($payload['language'] ?? 'ru'));
        $page = trim((string) ($payload['page'] ?? 'unknown'));
        $wallet = trim((string) ($payload['wallet'] ?? ''));
        $fid = $this->resolveFid($payload, $session);
        $firma = isset($payload['firma']) ? (int) $payload['firma'] : null;
        $useDbTools = (bool) ($payload['use_db_tools'] ?? true);

        // Определяем firma
        if ($firma === null || $firma <= 0) {
            $firma = $session->firma ?? null;
        }
        if ($firma === null || $firma <= 0) {
            $firma = (int) (session('fid', 0)) ?: null;
        }

        // Обновляем данные сессии
        $session->update([
            'fid' => $fid,
            'firma' => $firma,
            'wallet' => $wallet ?: $session->wallet,
            'language' => $language,
            'page' => $page ?: $session->page,
        ]);

        // Обновляем заголовок сессии первым сообщением
        $session->updateTitle($message);

        // Сохраняем сообщение пользователя
        $this->saveMessage($session->id, $fid, $firma, 'user', $message);

        $intent = $this->intentDetector->detect($message, $page, $language);

        if ($this->shouldDelegateProductKnowledgeGap($fid, $message, $intent)) {
            return $this->delegateKnowledgeGapToManagerAi(
                payload: $payload,
                session: $session,
                fid: $fid,
                firma: $firma,
                question: $message,
                localAnswer: 'В базе знаний проекта пока нет сохранённой информации по этому товарному запросу.',
                intent: array_merge($intent, [
                    'type' => 'product_lookup',
                    'reason' => 'product_kb_miss',
                ]),
            );
        }

        if ($useDbTools && $fid > 0 && $this->isCatalogNavigationIntent($intent)) {
            return $this->handleCatalogNavigationRequest($session, $fid, $firma, $message, $language, $page, $intent);
        }

        if ($useDbTools && $fid > 0 && $this->shouldResolveProductSelectionNow($session, $message)) {
            return $this->handleProductSelectionSearchRequest($session, $fid, $firma, $message, $language, $intent);
        }

        // Загружаем контекст из базы знаний
        $knowledgeContext = $this->loadKnowledgeContext($fid);

        // Формируем system prompt
        $instructions = $this->buildSystemPrompt($language, $fid, $knowledgeContext, $useDbTools, $intent);

        // Загружаем историю для AI
        $history = $session->getHistoryForAi(20);

        // Добавляем текущее сообщение с контекстом страницы
        $history[] = [
            'role' => 'user',
            'content' => "Контекст страницы: {$page}\n".
                'Кошелек пользователя: '.($wallet !== '' ? $wallet : 'не подключен')."\n".
                "ID проекта (fid): {$fid}\n".
                ($firma !== null && $firma > 0 ? "ID компании (firma): {$firma}\n" : '').
                "Намерение: {$intent['type']} ({$intent['reason']})\n".
                "Тема: {$intent['topic']}\n".
                "Вопрос пользователя: {$message}",
        ];

        // Получаем опции канала из конфига
        $channelConfig = $this->aiFactory->getChannelConfig(self::SHARED_AI_CHANNEL);
        $options = [
            'temperature' => $channelConfig['temperature'] ?? 0.35,
            'max_tokens'  => $channelConfig['max_tokens'] ?? 700,
        ];

        // ── Отправка с function calling или обычный запрос ──
        if ($useDbTools && $fid > 0 && ($intent['needs_tools'] ?? true)) {
            // Базовые инструменты из DbQueryService
            $tools = $this->filterToolsForIntent($this->dbQuery->getTools(), $intent['type']);

            // Пользовательские инструменты из таблицы ai_tools
            $customTools = AiTool::getToolsForPrompt($fid);
            if (!empty($customTools)) {
                $tools = array_merge($tools, $this->filterToolsForIntent($customTools, $intent['type']));
            }
            $tools = $this->dedupeToolsByName($tools);

            // Сбрасываем перехваченные действия перед новым раундом
            $this->capturedActions = [];

            $toolExecutor = function (string $name, array $arguments) use ($fid, $firma): string {
                $resultJson = $this->dbQuery->executeTool($fid, $firma, $name, $arguments);

                // Перехватываем ui_action из инструментов каталога
                if (in_array($name, ['open_catalog_category', 'search_catalog_products'], true)) {
                    try {
                        $decoded = json_decode($resultJson, true);
                        if (is_array($decoded) && !empty($decoded['ui_action'])) {
                            $this->capturedActions[] = $decoded['ui_action'];
                        }
                    } catch (Throwable $e) {
                        Log::debug('ChatService: failed to parse open_catalog_category result.', [
                            'error' => $e->getMessage(),
                        ]);
                    }
                }

                return $resultJson;
            };

            $result = !empty($tools)
                ? $this->ai->chatWithTools($instructions, $history, $tools, $toolExecutor, $options)
                : $this->ai->chat($instructions, $history, $options);
        } else {
            $result = $this->ai->chat($instructions, $history, $options);
        }

        $answer = $this->sanitizePublicAnswer((string) ($result['answer'] ?? ''));
        $result['answer'] = $answer;

        if ($this->shouldDelegateKnowledgeGap($knowledgeContext, $answer, $intent)) {
            return $this->delegateKnowledgeGapToManagerAi(
                payload: $payload,
                session: $session,
                fid: $fid,
                firma: $firma,
                question: $message,
                localAnswer: $answer,
                intent: $intent,
            );
        }

        // Сохраняем ответ ассистента
        $this->saveMessage($session->id, $fid, $firma, 'assistant', $answer, [
            'model' => $result['model'] ?? null,
            'usage' => $result['usage'] ?? null,
            'provider' => $this->ai->getProviderName(),
            'db_tools_used' => $useDbTools && $fid > 0 && ($intent['needs_tools'] ?? true),
            'intent' => $intent,
        ]);

        // ── Курирование знаний веб-чата ──
        $knowledgeCuration = $this->knowledgeCurator->curateFromTurn(
            fid: $fid,
            question: $message,
            answer: $answer,
            page: $page,
            language: $language,
            recentHistory: $session->getHistoryForAi(8),
        );

        return [
            'session_token' => $session->session_token,
            'answer' => $answer,
            'provider' => $this->ai->getProviderName(),
            'model' => $result['model'],
            'usage' => $result['usage'],
            'db_tools_enabled' => $useDbTools && $fid > 0,
            'intent' => $intent,
            'knowledge_curation' => $knowledgeCuration,
            'actions' => $this->capturedActions,
            'billing' => [
                'paid_by' => 'project',
                'sui_gas_sponsor_available' => $this->suiGasSponsorAvailable(),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $intent
     */
    private function shouldDelegateKnowledgeGap(string $knowledgeContext, string $answer, array $intent): bool
    {
        if (! $this->managerAiBridge->enabled()) {
            return false;
        }

        if (($intent['type'] ?? '') === WebChatIntentDetector::SMALL_TALK) {
            return false;
        }

        $normalized = mb_strtolower($answer);
        $gapMarkers = [
            'не знаю',
            'нет информации',
            'недостаточно данных',
            'не хватает данных',
            'не нашел',
            'не найден',
            'не могу подтвердить',
            'не могу ответить',
            'i do not know',
            'no information',
            'not enough data',
        ];

        foreach ($gapMarkers as $marker) {
            if (mb_stripos($normalized, $marker) !== false) {
                return true;
            }
        }

        return trim($knowledgeContext) === ''
            && (bool) ($intent['needs_tools'] ?? true)
            && in_array($intent['type'] ?? '', [
                WebChatIntentDetector::FAQ,
                WebChatIntentDetector::HOW_TO,
                WebChatIntentDetector::SUPPORT,
                WebChatIntentDetector::RESEARCH,
                WebChatIntentDetector::PUBLISH_NEWS,
            ], true);
    }

    /**
     * @param  array<string, mixed>  $intent
     */
    private function shouldDelegateProductKnowledgeGap(int $fid, string $message, array $intent): bool
    {
        if ($fid <= 0 || ! $this->managerAiBridge->enabled()) {
            return false;
        }

        if (! $this->isProductInformationRequest($message, $intent)) {
            return false;
        }

        try {
            foreach ($this->productKnowledgeSearchQueries($message, $intent) as $query) {
                if ($this->knowledgeService->search($fid, $query, 3)->isNotEmpty()) {
                    return false;
                }
            }

            return true;
        } catch (Throwable $e) {
            Log::debug('ChatService: product KB lookup failed before ManagerAI delegation.', [
                'fid' => $fid,
                'error' => $e->getMessage(),
            ]);

            return true;
        }
    }

    /**
     * Build several KB lookup queries from a natural product question.
     *
     * A full user sentence like "Как заказать рамку с рекламой?" often does not
     * appear verbatim in the KB, while product records contain shorter terms
     * such as "АвтоРамка" or "рекламой". We use conservative word stems only for
     * the pre-delegation existence check; the final answer still uses the normal
     * KB context loaded later in the flow.
     *
     * @param  array<string, mixed>  $intent
     * @return array<int, string>
     */
    private function productKnowledgeSearchQueries(string $message, array $intent): array
    {
        $queries = [];
        $this->appendKnowledgeQuery($queries, $message);
        $this->appendKnowledgeQuery($queries, (string) ($intent['topic'] ?? ''));

        $terms = $this->productKnowledgeTerms($message.' '.(string) ($intent['topic'] ?? ''));
        foreach ($terms as $term) {
            $this->appendKnowledgeQuery($queries, $term);
        }

        for ($i = 0, $count = count($terms); $i < $count - 1; $i++) {
            $this->appendKnowledgeQuery($queries, $terms[$i].' '.$terms[$i + 1]);
        }

        return array_slice($queries, 0, 12);
    }

    /**
     * @return array<int, string>
     */
    private function productKnowledgeTerms(string $text): array
    {
        $stopWords = [
            'как', 'что', 'это', 'есть', 'для', 'или', 'при', 'про', 'под', 'над',
            'мне', 'нам', 'вам', 'его', 'её', 'она', 'они', 'без', 'через',
            'можно', 'нужно', 'надо', 'хочу', 'заказ', 'заказать', 'купить',
            'сколько', 'стоит', 'цена', 'стоимость', 'товар', 'продукт',
            'with', 'from', 'this', 'that', 'price', 'product', 'order',
        ];

        $words = preg_split('/[^\p{L}\p{N}_-]+/u', mb_strtolower($text)) ?: [];
        $terms = [];

        foreach ($words as $word) {
            $word = trim($word, " \t\n\r\0\x0B_-");
            if (mb_strlen($word) < 4 || in_array($word, $stopWords, true)) {
                continue;
            }

            $this->appendKnowledgeQuery($terms, mb_strlen($word) >= 5 ? mb_substr($word, 0, -1) : $word);
            if (mb_strlen($word) >= 6) {
                $this->appendKnowledgeQuery($terms, mb_substr($word, 0, -2));
            }
        }

        return array_slice($terms, 0, 8);
    }

    /**
     * @param  array<int, string>  $queries
     */
    private function appendKnowledgeQuery(array &$queries, string $query): void
    {
        $query = trim(preg_replace('/\s+/u', ' ', mb_strtolower($query)) ?? '');
        if (mb_strlen($query) < 2 || in_array($query, $queries, true)) {
            return;
        }

        $queries[] = $query;
    }

    /**
     * @param  array<string, mixed>  $intent
     */
    private function isProductInformationRequest(string $message, array $intent): bool
    {
        $text = mb_strtolower($message);

        foreach ([
            'товар',
            'продукт',
            'наименование',
            'название',
            'описание',
            'артикул',
            'цена',
            'стоимость',
            'сколько стоит',
            'наличие',
            'ссылка',
            'купить',
            'заказать',
            'номерн',
            'знак',
            'product',
            'price',
            'description',
        ] as $marker) {
            if (mb_stripos($text, $marker) !== false) {
                return true;
            }
        }

        return in_array($intent['type'] ?? '', [
            WebChatIntentDetector::HOW_TO,
            WebChatIntentDetector::FAQ,
        ], true) && ($intent['reason'] ?? '') === 'catalog_navigation_request';
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $intent
     * @return array<string, mixed>
     */
    private function delegateKnowledgeGapToManagerAi(
        array $payload,
        ChatSession $session,
        int $fid,
        ?int $firma,
        string $question,
        string $localAnswer,
        array $intent,
    ): array {
        try {
            $managerResult = $this->managerAiBridge->sendKnowledgeGapRequest(array_merge($payload, [
                'session_token' => $session->session_token,
                'original_question' => $question,
                'local_answer' => $localAnswer,
                'fid' => $fid,
                'firma' => $firma,
            ]));

            $answer = (string) ($managerResult['answer'] ?? 'Запрос передан manager-ai для пополнения базы знаний.');

            $this->saveMessage($session->id, $fid, $firma, 'assistant', $answer, [
                'provider' => 'manager-ai',
                'intent' => $intent,
                'knowledge_gap_delegated' => true,
                'manager_ai' => $managerResult['manager_ai'] ?? null,
            ]);

            return array_merge($managerResult, [
                'session_token' => $session->session_token,
                'answer' => $answer,
                'db_tools_enabled' => false,
                'intent' => $intent,
                'knowledge_curation' => [
                    'saved' => false,
                    'reason' => 'delegated_to_manager_ai',
                ],
                'actions' => [[
                    'type' => 'read_knowledge_base',
                    'command' => 'await_manager_ai_ingest',
                    'fid' => $fid,
                    'session_token' => $session->session_token,
                    'source' => 'manager-ai',
                ]],
                'billing' => [
                    'paid_by' => 'project',
                    'sui_gas_sponsor_available' => $this->suiGasSponsorAvailable(),
                ],
            ]);
        } catch (Throwable $e) {
            Log::warning('ChatService: ManagerAI knowledge-gap delegation failed.', [
                'fid' => $fid,
                'session_token' => $session->session_token,
                'error' => $e->getMessage(),
            ]);

            if (! $this->managerAiBridge->fallbackToLocal()) {
                throw $e;
            }

            $this->saveMessage($session->id, $fid, $firma, 'assistant', $localAnswer, [
                'provider' => $this->ai->getProviderName(),
                'intent' => $intent,
                'knowledge_gap_delegation_failed' => true,
            ]);

            return [
                'session_token' => $session->session_token,
                'answer' => $localAnswer,
                'provider' => $this->ai->getProviderName(),
                'model' => null,
                'usage' => [],
                'db_tools_enabled' => false,
                'intent' => $intent,
                'knowledge_curation' => [
                    'saved' => false,
                    'reason' => 'manager_ai_unavailable',
                ],
                'actions' => [],
                'billing' => [
                    'paid_by' => 'project',
                    'sui_gas_sponsor_available' => $this->suiGasSponsorAvailable(),
                ],
            ];
        }
    }

    /**
     * Определить fid веб-чата без жесткой привязки к аналитическому проекту.
     *
     * Приоритет:
     * 1. fid из payload (например, data-fid виджета);
     * 2. fid существующей chat-сессии;
     * 3. session('fid') текущего Laravel-пользователя.
     */
    private function resolveFid(array $payload, ChatSession $session): int
    {
        $candidates = [
            $payload['fid'] ?? null,
            $session->fid ?? null,
            session('fid'),
        ];

        foreach ($candidates as $candidate) {
            $fid = (int) $candidate;
            if ($fid > 0) {
                return $fid;
            }
        }

        return self::ANALYST_FID;
    }

    private function isCatalogNavigationIntent(array $intent): bool
    {
        return ($intent['reason'] ?? '') === 'catalog_navigation_request';
    }

    private function shouldResolveProductSelectionNow(ChatSession $session, string $message): bool
    {
        $messageText = mb_strtolower($message);
        $hasConcreteMaterial = preg_match('/\b(алюмин|алюмини|пластик|пластиков|металл|метал)\w*/iu', $messageText) === 1;
        if (!$hasConcreteMaterial) {
            return false;
        }

        $historyText = mb_strtolower(implode("\n", array_column($session->getHistoryForAi(8), 'content')));

        return str_contains($historyText, 'номер')
            || str_contains($historyText, 'знак')
            || str_contains($historyText, 'сувенир')
            || str_contains($historyText, 'каталог');
    }

    /**
     * @return array<string, mixed>
     */
    private function handleProductSelectionSearchRequest(
        ChatSession $session,
        int $fid,
        ?int $firma,
        string $message,
        string $language,
        array $intent,
    ): array {
        $historyText = mb_strtolower(implode("\n", array_column($session->getHistoryForAi(8), 'content')));
        $messageText = mb_strtolower($message);

        $queryParts = ['номерные знаки'];
        $filters = [];
        $excludeTerms = [];

        if (str_contains($historyText, 'сувенир') || str_contains($messageText, 'сувенир')) {
            $queryParts[] = 'сувенирные';
            $filters[] = 'сувенирные';
        }

        if (preg_match('/алюмин\w*/iu', $messageText)) {
            $queryParts[] = 'алюминий';
            $filters[] = 'алюминий';
        }

        if (preg_match('/пластик\w*/iu', $messageText)) {
            $queryParts[] = 'пластик';
            $filters[] = 'пластик';
        }

        if (preg_match('/\b(нет|не|без)\b/iu', $messageText) && str_contains($historyText, 'пластик')) {
            $excludeTerms[] = 'пластик';
            $filters = array_values(array_filter($filters, fn (string $item): bool => $item !== 'пластик'));
        }

        $query = implode(' ', array_values(array_unique($queryParts)));
        $this->capturedActions = [];

        $resultJson = $this->dbQuery->executeTool($fid, $firma, 'search_catalog_products', [
            'query' => $query,
            'category_query' => 'номерные знаки',
            'filters' => array_values(array_unique($filters)),
            'exclude_terms' => array_values(array_unique($excludeTerms)),
            'locale' => $language !== '' ? $language : 'ru',
        ]);

        $toolResult = json_decode($resultJson, true);
        if (!is_array($toolResult)) {
            $toolResult = [
                'success' => false,
                'message' => 'Не удалось обработать подбор товаров.',
            ];
        }

        if (!empty($toolResult['ui_action']) && is_array($toolResult['ui_action'])) {
            $this->capturedActions[] = $toolResult['ui_action'];
        }

        $answer = (string) ($toolResult['message'] ?? '');
        if ($answer === '') {
            $answer = !empty($this->capturedActions)
                ? 'Такие варианты подходят? Показываю в каталоге.'
                : 'Не удалось подобрать товары по этим признакам.';
        }

        if (!empty($this->capturedActions) && !str_contains($answer, '?')) {
            $answer .= ' Такие подходят?';
        }

        $this->saveMessage($session->id, $fid, $firma, 'assistant', $answer, [
            'provider' => 'db_tools',
            'db_tools_used' => true,
            'tool' => 'search_catalog_products',
            'intent' => $intent,
            'actions' => $this->capturedActions,
        ]);

        return [
            'session_token' => $session->session_token,
            'answer' => $answer,
            'provider' => 'db_tools',
            'model' => null,
            'usage' => null,
            'db_tools_enabled' => true,
            'intent' => $intent,
            'knowledge_curation' => null,
            'actions' => $this->capturedActions,
            'billing' => [
                'paid_by' => 'project',
                'sui_gas_sponsor_available' => $this->suiGasSponsorAvailable(),
            ],
        ];
    }

    /**
     * For catalog navigation, do not wait for the model to choose the tool.
     * The backend tool returns a safe ui_action that the frontend can execute.
     *
     * @return array<string, mixed>
     */
    private function handleCatalogNavigationRequest(
        ChatSession $session,
        int $fid,
        ?int $firma,
        string $message,
        string $language,
        string $page,
        array $intent,
    ): array {
        $this->capturedActions = [];

        $resultJson = $this->dbQuery->executeTool($fid, $firma, 'open_catalog_category', [
            'query' => $message,
            'locale' => $language !== '' ? $language : 'ru',
        ]);

        $toolResult = json_decode($resultJson, true);
        if (!is_array($toolResult)) {
            $toolResult = [
                'success' => false,
                'message' => 'Не удалось обработать запрос каталога.',
            ];
        }

        if (!empty($toolResult['ui_action']) && is_array($toolResult['ui_action'])) {
            $this->capturedActions[] = $toolResult['ui_action'];
        }

        $answer = (string) ($toolResult['message'] ?? '');
        if ($answer === '') {
            $answer = !empty($this->capturedActions)
                ? 'Открываю подходящий раздел каталога.'
                : 'Не удалось подобрать раздел каталога.';
        }

        $this->saveMessage($session->id, $fid, $firma, 'assistant', $answer, [
            'provider' => 'db_tools',
            'db_tools_used' => true,
            'tool' => 'open_catalog_category',
            'intent' => $intent,
            'actions' => $this->capturedActions,
        ]);

        return [
            'session_token' => $session->session_token,
            'answer' => $answer,
            'provider' => 'db_tools',
            'model' => null,
            'usage' => null,
            'db_tools_enabled' => true,
            'intent' => $intent,
            'knowledge_curation' => null,
            'actions' => $this->capturedActions,
            'billing' => [
                'paid_by' => 'project',
                'sui_gas_sponsor_available' => $this->suiGasSponsorAvailable(),
            ],
        ];
    }

    // ── История сообщений ───────────────────────────────────────────────

    /**
     * Получить историю сообщений сессии.
     *
     * @return Collection<int, ChatMessage>
     */
    public function getSessionHistory(string $sessionToken, int $limit = 50): Collection
    {
        $session = $this->findSession($sessionToken);
        if ($session === null) {
            return collect();
        }

        return $session->messages()
            ->orderBy('created_at')
            ->limit($limit)
            ->get();
    }

    // ── Внутренние методы ───────────────────────────────────────────────

    /**
     * @param  array<string, mixed>|null  $metadata
     */
    private function saveMessage(int $sessionId, ?int $fid, ?int $firma, string $role, string $content, ?array $metadata = null): ChatMessage
    {
        return ChatMessage::create([
            'chat_session_id' => $sessionId,
            'fid' => $fid,
            'firma' => $firma,
            'role' => $role,
            'content' => $content,
            'metadata' => $metadata,
        ]);
    }

    private function loadKnowledgeContext(int $fid): string
    {
        if ($fid <= 0) {
            $fid = (int) session('fid', 0);
        }

        if ($fid <= 0) {
            return '';
        }

        try {
            $context = $this->knowledgeService->getContext($fid);

            if ($context === '') {
                $sessionFid = (int) session('fid', 0);
                if ($sessionFid > 0 && $sessionFid !== $fid) {
                    Log::info('Knowledge base context empty for fid {fid}, trying session fid {sessionFid}.', [
                        'fid' => $fid,
                        'sessionFid' => $sessionFid,
                    ]);

                    $context = $this->knowledgeService->getContext($sessionFid);
                }
            }

            return $context;
        } catch (Throwable $e) {
            Log::warning('Failed to load knowledge base context.', [
                'fid' => $fid,
                'error' => $e->getMessage(),
            ]);
            return '';
        }
    }

    /**
     * Keep tools aligned with the detected intent so the model cannot wander into
     * unrelated database actions during a simple support or FAQ turn.
     *
     * @param  array<int, array<string, mixed>>  $tools
     * @return array<int, array<string, mixed>>
     */
    private function filterToolsForIntent(array $tools, string $intentType): array
    {
        $allowed = $this->allowedToolNamesForIntent($intentType);

        return array_values(array_filter($tools, function (array $tool) use ($allowed): bool {
            $name = (string) data_get($tool, 'function.name', '');

            return $name !== '' && in_array($name, $allowed, true);
        }));
    }

    /**
     * Keep one definition per function name. Later definitions win, so an
     * ai_tools record from /settings can override the built-in schema/description
     * while execution still routes through DbQueryService::executeTool().
     *
     * @param  array<int, array<string, mixed>>  $tools
     * @return array<int, array<string, mixed>>
     */
    private function dedupeToolsByName(array $tools): array
    {
        $byName = [];

        foreach ($tools as $tool) {
            $name = (string) data_get($tool, 'function.name', '');
            if ($name === '') {
                continue;
            }

            $byName[$name] = $tool;
        }

        return array_values($byName);
    }

    /**
     * @return array<int, string>
     */
    private function allowedToolNamesForIntent(string $intentType): array
    {
        return match ($intentType) {
            WebChatIntentDetector::SMALL_TALK => [
                'get_project_info',
            ],
            WebChatIntentDetector::HOW_TO,
            WebChatIntentDetector::FAQ => [
                'search_knowledge_base',
                'search_docs',
                'search_news',
                'get_project_info',
                'get_goods_categories',
                'search_goods',
                'open_catalog_category',
                'search_catalog_products',
            ],
            WebChatIntentDetector::SUPPORT => [
                'search_knowledge_base',
                'search_docs',
                'get_project_info',
                'get_goods_categories',
                'search_goods',
                'open_catalog_category',
                'search_catalog_products',
                'save_to_knowledge_base',
            ],
            WebChatIntentDetector::WALLET_ACTION => [
                'search_knowledge_base',
                'search_docs',
                'get_project_info',
            ],
            WebChatIntentDetector::RESEARCH => [
                'search_knowledge_base',
                'search_docs',
                'search_news',
                'fetch_and_save_page',
                'save_to_knowledge_base',
            ],
            WebChatIntentDetector::PUBLISH_NEWS => [
                'search_knowledge_base',
                'search_docs',
                'search_news',
                'fetch_and_save_page',
                'save_to_knowledge_base',
            ],
            default => [
                'search_knowledge_base',
                'search_docs',
                'get_project_info',
            ],
        };
    }

    /**
     * Сформировать system prompt для AI.
     */
    private function buildSystemPrompt(string $language, int $fid, string $knowledgeContext = '', bool $useDbTools = true, array $intent = []): string
    {
        $answerLanguage = match ($language) {
            'ua' => 'українській',
            'en' => 'английском',
            default => 'русском',
        };

        $knowledgeSection = $knowledgeContext !== ''
            ? "\n\nБаза знаний проекта (используй эти данные для ответа):\n{$knowledgeContext}"
            : '';

        $learningInstruction = <<<'LEARN'

ВАЖНО: Ты можешь помогать проекту накапливать знания.
Если пользователь задаёт вопрос, ответ на который будет полезен другим пользователям этого же проекта,
дай точный ответ по сути, без лишних деталей.
Не выдумывай информацию — если не знаешь, скажи об этом честно.
LEARN;

        $dbToolsInstruction = $useDbTools && $fid > 0 ? <<<'DBTOOLS'

ДОПОЛНИТЕЛЬНЫЕ ВОЗМОЖНОСТИ: У тебя есть доступ к базе данных проекта через функции.

Ты МОЖЕШЬ и ДОЛЖЕН использовать эти функции, когда пользователь спрашивает:
- О товарах, ценах, наличии — используй search_goods или get_goods_by_id
- О категориях товаров — используй get_goods_categories
- Об определённом типе номеров, разделе каталога или марке авто — используй open_catalog_category. Особенно для фраз "покажи ...", "найди ...", "подбери ...", "открой ...". Этот инструмент сам найдёт подходящий раздел или вернёт поиск по каталогу и ui_action для перехода.
- Для диалогового подбора товара используй search_catalog_products. Если пользователь говорит "хочу заказать номерные знаки", сначала уточни 1 важный параметр (например стандартные или сувенирные). Когда пользователь уточнил тип/материал/назначение, собери итоговый query только из положительных признаков и вызови search_catalog_products. Отвергнутые признаки клади в exclude_terms, например "нет, на алюминии" после вопроса про пластик означает filters=["алюминий"], exclude_terms=["пластик"].
- О новостях проекта — используй search_news
- О проекте в целом (контакты, описание) — используй get_project_info
- О документах/статьях — используй search_docs
- Если вопрос похож на тот, что уже задавали — используй search_knowledge_base

НОВЫЕ ВОЗМОЖНОСТИ (парсинг сайтов и сохранение знаний):

1. fetch_and_save_page — Когда пользователь даёт URL сайта или просит проанализировать/сохранить страницу
2. save_to_knowledge_base — Когда пользователь предоставляет полезную информацию

ВАЖНО: Всегда используй функции для получения реальных данных из БД.
НЕ выдумывай названия товаров, цены или другие данные — запроси их через функции.
DBTOOLS
        : '';

        $intentType = (string) ($intent['type'] ?? WebChatIntentDetector::FAQ);
        $intentTopic = (string) ($intent['topic'] ?? '');
        $intentInstruction = $this->intentInstruction($intentType);

        return <<<PROMPT
Ты AI-аналитик и помощник AV8 Capital. Отвечай на {$answerLanguage} языке.

Твоя задача: вести диалог в вебчате laravel-api, анализировать проект по текущему fid, помогать посетителям пользоваться AV8 Capital, собирать знания и готовить материалы локально в рамках веб-чата.

Контекст сессии:
- ID проекта (fid): {$fid}
- Намерение пользователя: {$intentType}
- Тема намерения: {$intentTopic}

Маршрут ответа для этого намерения:
{$intentInstruction}

Правила:
- Веди себя как внимательный консультант, а не как справочник: сначала улови намерение пользователя, потом дай следующий полезный шаг.
- Отвечай коротко и только на конкретный вопрос пользователя.
- Пиши естественно: без канцелярита, без "как ИИ", без длинных вступлений и без повторения вопроса.
- Если пользователь выглядит потерянным, предложи один конкретный следующий шаг вместо списка из многих вариантов.
- Не перечисляй всё, что знаешь по теме. Добавляй детали только если они нужны для прямого ответа.
- Обычно достаточно 2-5 коротких предложений или 3-5 пунктов.
- Пошаговый ответ давай только когда пользователь спрашивает "как сделать" или просит инструкцию.
- Если нужно уточнение, задай один короткий вопрос. Не задавай несколько вопросов сразу.
- Не обещай доходность и не давай персональную финансовую рекомендацию.
- Не проси seed phrase, private key, mnemonic или секреты кошелька.
- Любая операция с активами требует подписи пользователя или админа в кошельке.
- Если пользователь спрашивает про депозит: объясни, что он выбирает whitelisted token, вводит сумму, подписывает транзакцию и получает AV8/fund share по политике эмиссии.
- Если пользователь спрашивает про вывод: объясни, что нужен баланс AV8/fund share и подпись вывода.
- Если пользователь спрашивает про админку: объясни, что whitelist, веса корзины, RWA minting и rebalance доступны только админам с правами/owner cap.
- Если данных не хватает, попроси открыть нужную страницу или подключить кошелёк.
- Не выдумывай onchain-состояние. Если точный баланс или объект не передан в контексте, скажи, где его увидеть в интерфейсе.
- Если в База знаний проекта есть информация по вопросу — используй её в первую очередь.
- Если вопрос похож на FAQ или прошлые обращения, сначала используй базу знаний/функции поиска, а не отвечай по памяти модели.
- Если пользователь просит подготовить, написать или опубликовать статью/новость/обзор, подготовь качественный черновик в веб-чате и используй доступные функции базы знаний/публикации только для текущего fid.
- Для аналитических запросов сохраняй полезные выводы в базу знаний текущего проекта fid={$fid}.
- Ты можешь парсить веб-страницы по URL и сохранять их содержимое в базу знаний проекта (функция fetch_and_save_page).
- Если пользователь просит изучить сайт или сохранить информацию — используй эту возможность.
- Если пользователь делится полезной информацией — предложи сохранить её в базу знаний (функция save_to_knowledge_base).{$knowledgeSection}{$dbToolsInstruction}{$learningInstruction}
PROMPT;
    }

    private function intentInstruction(string $intentType): string
    {
        return match ($intentType) {
            WebChatIntentDetector::SMALL_TALK => '- Ответь тепло и коротко. Не запускай длинные объяснения и не предлагай лишние функции.',
            WebChatIntentDetector::FAQ => '- Найди короткий фактологический ответ. Если есть база знаний или docs, используй их. Ответ 2-5 предложений.',
            WebChatIntentDetector::HOW_TO => '- Дай один понятный следующий шаг или короткую инструкцию. Не перегружай вариантами.',
            WebChatIntentDetector::SUPPORT => '- Признай проблему, попроси один недостающий факт при необходимости и предложи ближайшее действие. Не обвиняй пользователя.',
            WebChatIntentDetector::RESEARCH => '- Сначала ищи существующие данные. Если пользователь дал URL, используй парсинг и сохранение. Заверши кратким отчётом, что найдено и что сохранено.',
            WebChatIntentDetector::PUBLISH_NEWS => '- Собери проверяемые факты из базы/источников. Если публикационный tool недоступен в вебчате, подготовь качественный черновик для текущего проекта.',
            WebChatIntentDetector::WALLET_ACTION => '- Будь осторожен: не выдумывай onchain-состояние, не проси секреты, объясняй только безопасный следующий шаг и необходимость подписи в кошельке.',
            default => '- Ответь по сути и используй базу знаний при наличии.',
        };
    }

    private function suiGasSponsorAvailable(): bool
    {
        return trim((string) config('services.sui.gas_sponsor_private_key', '')) !== ''
            || trim((string) config('services.shinami.gas_access_key', '')) !== '';
    }

    private function sanitizePublicAnswer(string $answer): string
    {
        $answer = trim($answer);

        if ($answer === '') {
            return 'Консультант временно не смог подготовить ответ. Попробуйте переформулировать вопрос.';
        }

        $answer = preg_replace('/\bDeepSeek\b/iu', 'консультант', $answer) ?? $answer;

        return preg_replace('/\bAI[-\s]?ассистент\b/iu', 'консультант', $answer) ?? $answer;
    }
}
