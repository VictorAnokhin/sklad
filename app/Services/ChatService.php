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
    private const SHARED_AI_CHANNEL = 'telegram';

    private AiClientInterface $ai;

    public function __construct(
        private readonly AiKnowledgeService $knowledgeService,
        private readonly WebChatKnowledgeCurator $knowledgeCurator,
        private readonly WebChatIntentDetector $intentDetector,
        private readonly DbQueryService $dbQuery,
        private readonly AiClientFactory $aiFactory,
        private readonly AgentOrchestrator $orchestrator,
    ) {
        // Web chat and Telegram share the same analyst channel by default.
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
        $fid = self::ANALYST_FID;
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

        if ($this->shouldCreateEditorialTask($intent, $message)) {
            return $this->delegateEditorialTask($session, $fid, $firma, $message, $language, $page, $intent);
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

            $toolExecutor = function (string $name, array $arguments) use ($fid, $firma): string {
                return $this->dbQuery->executeTool($fid, $firma, $name, $arguments);
            };

            $result = !empty($tools)
                ? $this->ai->chatWithTools($instructions, $history, $tools, $toolExecutor, $options)
                : $this->ai->chat($instructions, $history, $options);
        } else {
            $result = $this->ai->chat($instructions, $history, $options);
        }

        // Сохраняем ответ ассистента
        $this->saveMessage($session->id, $fid, $firma, 'assistant', $result['answer'], [
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
            answer: (string) $result['answer'],
            page: $page,
            language: $language,
            recentHistory: $session->getHistoryForAi(8),
        );

        if ($this->shouldDelegateToTelegramAgent($message, (string) $result['answer'], $knowledgeContext, $intent)) {
            $task = $this->orchestrator->createTask(
                sourceAgent: 'telegram_expert',
                targetAgent: 'telegram',
                fid: $fid,
                taskType: 'complex_question',
                inputData: [
                    'query' => $message,
                    'question' => $message,
                    'language' => $language,
                    'response_channel' => 'web_chat',
                    'channel' => 'web_chat',
                    'page' => $page,
                    'intent' => $intent,
                ],
                sessionToken: $session->session_token,
                priority: 1,
            );

            $delegatedAnswer = "⏳ В базе проекта не нашлось достаточной информации. Я передал вопрос TelegramAgent для глубокого поиска и сохранения результата в базе знаний.";

            $this->saveMessage($session->id, $fid, $firma, 'assistant', $delegatedAnswer, [
                'source' => 'telegram_agent_delegation',
                'task_uuid' => $task->uuid,
            ]);

            return [
                'session_token' => $session->session_token,
                'answer' => $delegatedAnswer,
                'provider' => $this->ai->getProviderName(),
                'model' => $result['model'],
                'usage' => $result['usage'],
                'db_tools_enabled' => $useDbTools && $fid > 0,
                'delegated' => true,
                'task_uuid' => $task->uuid,
                'intent' => $intent,
                'knowledge_curation' => $knowledgeCuration,
                'billing' => [
                    'paid_by' => 'project',
                    'sui_gas_sponsor_available' => $this->suiGasSponsorAvailable(),
                ],
            ];
        }

        return [
            'session_token' => $session->session_token,
            'answer' => $result['answer'],
            'provider' => $this->ai->getProviderName(),
            'model' => $result['model'],
            'usage' => $result['usage'],
            'db_tools_enabled' => $useDbTools && $fid > 0,
            'intent' => $intent,
            'knowledge_curation' => $knowledgeCuration,
            'billing' => [
                'paid_by' => 'project',
                'sui_gas_sponsor_available' => $this->suiGasSponsorAvailable(),
            ],
        ];
    }

    private function shouldDelegateToTelegramAgent(string $question, string $answer, string $knowledgeContext, array $intent = []): bool
    {
        if (in_array(($intent['type'] ?? ''), [WebChatIntentDetector::RESEARCH, WebChatIntentDetector::PUBLISH_NEWS], true)) {
            return true;
        }

        if ($knowledgeContext !== '') {
            return false;
        }

        $text = mb_strtolower($question . "\n" . $answer);

        foreach ([
            'не знаю',
            'нет информации',
            'не нашел',
            'не нашёл',
            'недостаточно данных',
            'недостаточно информации',
            'не удалось найти',
            'уточните',
        ] as $marker) {
            if (mb_stripos($text, $marker) !== false) {
                return true;
            }
        }

        return false;
    }

    private function shouldCreateEditorialTask(array $intent, string $message): bool
    {
        $type = (string) ($intent['type'] ?? '');

        if ($type === WebChatIntentDetector::PUBLISH_NEWS) {
            return true;
        }

        return $type === WebChatIntentDetector::RESEARCH;
    }

    /**
     * Передать редакционную задачу в TelegramAgent, который умеет готовить и
     * публиковать статьи/новости через AnalystService tools.
     *
     * @return array<string, mixed>
     */
    private function delegateEditorialTask(
        ChatSession $session,
        int $fid,
        ?int $firma,
        string $message,
        string $language,
        string $page,
        array $intent,
    ): array {
        $task = $this->orchestrator->createTask(
            sourceAgent: 'telegram_expert',
            targetAgent: 'telegram',
            fid: $fid,
            taskType: 'complex_question',
            inputData: [
                'query' => $this->buildEditorialTaskQuery($message, $intent),
                'question' => $message,
                'language' => $language,
                'response_channel' => 'web_chat',
                'channel' => 'web_chat',
                'page' => $page,
                'intent' => $intent,
            ],
            sessionToken: $session->session_token,
            priority: 2,
        );

        $answer = $intent['type'] === WebChatIntentDetector::PUBLISH_NEWS
            ? "⏳ Принял задачу для аналитика-помощника. Передал её TelegramAgent: он подготовит материал, проверит источники и при необходимости опубликует статью или новость в проекте fid={$fid}."
            : "⏳ Принял исследовательскую задачу для аналитика-помощника. Передал её TelegramAgent: он проверит источники, сохранит полезные данные и вернёт результат в этот чат.";

        $this->saveMessage($session->id, $fid, $firma, 'assistant', $answer, [
            'source' => 'telegram_agent_editorial_delegation',
            'task_uuid' => $task->uuid,
            'intent' => $intent,
            'provider' => $this->ai->getProviderName(),
        ]);

        return [
            'session_token' => $session->session_token,
            'answer' => $answer,
            'provider' => $this->ai->getProviderName(),
            'model' => $this->ai->getModel(),
            'usage' => [],
            'db_tools_enabled' => false,
            'delegated' => true,
            'task_uuid' => $task->uuid,
            'intent' => $intent,
            'knowledge_curation' => ['saved' => false, 'reason' => 'delegated_editorial_task'],
            'billing' => [
                'paid_by' => 'project',
                'sui_gas_sponsor_available' => $this->suiGasSponsorAvailable(),
            ],
        ];
    }

    private function buildEditorialTaskQuery(string $message, array $intent): string
    {
        $type = (string) ($intent['type'] ?? WebChatIntentDetector::PUBLISH_NEWS);
        $topic = trim((string) ($intent['topic'] ?? ''));
        $taskKind = $type === WebChatIntentDetector::PUBLISH_NEWS
            ? 'подготовить и опубликовать новостной материал'
            : 'подготовить аналитический материал';

        return "Задача из веб-чата laravel-api. Нужно {$taskKind} для проекта fid=1.\n"
            . ($topic !== '' ? "Тема: {$topic}\n" : '')
            . "Исходный запрос пользователя: {$message}\n\n"
            . "Работай как аналитик и помощник AV8 Capital: сначала проверь уже сохранённые источники/знания, при необходимости загрузи открытые источники, затем подготовь законченный текст. "
            . "Если запрос явно просит публикацию новости или статьи — используй publish_news или publish_article с fid=1. "
            . "Не передавай задачу оператору из-за пустого TELEGRAM_OPERATOR_CHAT_ID.";
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
            ],
            WebChatIntentDetector::SUPPORT => [
                'search_knowledge_base',
                'search_docs',
                'get_project_info',
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

Твоя задача: одинаково вести диалог в вебчате laravel-api и Telegram: анализировать проекты, помогать посетителям пользоваться AV8 Capital, собирать знания, готовить материалы и передавать редакционные задачи TelegramAgent для подготовки и публикации статей/новостей.

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
- Если пользователь просит подготовить, написать или опубликовать статью/новость/обзор, такая задача должна выполняться через TelegramAgent и публикационные tools проекта fid=1.
- Для аналитических запросов сохраняй полезные выводы в базу знаний проекта fid=1.
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
            WebChatIntentDetector::PUBLISH_NEWS => '- Собери проверяемые факты из базы/источников. Если публикационный tool недоступен в вебчате, подготовь качественный черновик и предложи отправить в TelegramAgent для публикации.',
            WebChatIntentDetector::WALLET_ACTION => '- Будь осторожен: не выдумывай onchain-состояние, не проси секреты, объясняй только безопасный следующий шаг и необходимость подписи в кошельке.',
            default => '- Ответь по сути и используй базу знаний при наличии.',
        };
    }

    private function suiGasSponsorAvailable(): bool
    {
        return trim((string) config('services.sui.gas_sponsor_private_key', '')) !== ''
            || trim((string) config('services.shinami.gas_access_key', '')) !== '';
    }
}
