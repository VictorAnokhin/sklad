<?php

namespace App\Services;

use App\Contracts\AiClientInterface;
use App\Models\AgentTask;
use App\Models\ChatMessage;
use App\Models\ChatSession;
use App\Traits\ChatLoopDetectionTrait;
use App\Traits\ChatSessionManagerTrait;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class TelegramChatService
{
    use ChatLoopDetectionTrait;
    use ChatSessionManagerTrait;

    /**
     * FID проекта аналитика по умолчанию.
     */
    private const ANALYST_FID = 1;

    /** @var array<string, int> Счётчик вызовов tools для rate limiting */
    private array $toolCallCount = [];

    /** @var int Максимальное количество вызовов tools за один диалог */
    private const MAX_TOOL_CALLS = 25;

    /** @var int Кэширование knowledge context в секундах */
    private const KNOWLEDGE_CACHE_TTL = 300;

    private AiClientInterface $ai;

    public function __construct(
        private readonly AiClientFactory $aiFactory,
        private readonly TelegramBotService $bot,
        private readonly AnalystService $analyst,
        private readonly AiKnowledgeService $knowledgeService,
        private readonly AgentOrchestrator $orchestrator,
        private readonly WebChatIntentDetector $intentDetector,
    ) {
        $this->ai = $this->aiFactory->make('telegram');
        // Настройки трейта ChatSessionManagerTrait
        $this->telegramTokenPrefix = 'tg_';
        $this->defaultAnalystFid = self::ANALYST_FID;
    }

    /**
     * Получить имя провайдера для метаданных (требуется трейтами).
     */
    protected function getProviderNameForMetadata(): string
    {
        return $this->ai?->getProviderName() ?? 'unknown';
    }

    /**
     * Получить экземпляр бота (требуется ChatLoopDetectionTrait::breakTheLoop).
     */
    protected function getBot(): TelegramBotService
    {
        return $this->bot;
    }

    /**
     * Переключить AI-клиент на другой канал (например, 'web_chat', 'agent').
     */
    public function useChannel(string $channel): static
    {
        $this->ai = $this->aiFactory->make($channel);
        return $this;
    }

    /**
     * Переключить AI-клиент на конкретного провайдера.
     */
    public function useProvider(string $provider, ?string $model = null): static
    {
        $this->ai = $this->aiFactory->makeForProvider($provider, $model);
        return $this;
    }

    /**
     * Получить текущий AI-клиент.
     */
    public function getAiClient(): AiClientInterface
    {
        return $this->ai;
    }

    // ── Публичный API ──────────────────────────────────────────────────────

    /**
     * Обработать входящее сообщение из Telegram.
     *
     * @param  array<string, mixed>  $message  Объект message от Telegram
     * @return string Ответ, который будет отправлен пользователю
     */
    public function handleMessage(array $message): string
    {
        $chatId = $message['chat']['id'];
        $text = trim((string) ($message['text'] ?? ''));
        $userId = $message['from']['id'] ?? null;
        $username = $message['from']['username'] ?? $message['from']['first_name'] ?? "User{$chatId}";

        if ($text === '') {
            Log::info('Telegram: empty message, skipping.', ['chat_id' => $chatId]);
            return '';
        }

        // ── Команды ──────────────────────────────────────────────────────
        if (str_starts_with($text, '/')) {
            if (preg_match('/^\/answer\s+([0-9a-f-]{36})\s+(.+)/isu', $text, $matches)) {
                return $this->completeHumanAnswer($chatId, $matches[1], trim($matches[2]));
            }

            $response = $this->handleCommand($chatId, $text, $userId, $username);
            if ($response !== null) {
                return $response;
            }
        }

        // ── Основной диалог с AI-аналитиком ────────────────────────────────
        return $this->handleAiDialog($chatId, $text, $message);
    }

    // ── Команды ────────────────────────────────────────────────────────────

    private function handleCommand(
        int|string $chatId,
        string $text,
        int|string|null $userId,
        string $username,
    ): ?string {
        return match (true) {
            $text === '/start'  => $this->cmdStart($chatId, $username),
            $text === '/help'   => $this->cmdHelp($chatId),
            $text === '/clear'  => $this->cmdClear($chatId),
            $text === '/new'    => $this->cmdNew($chatId),
            default             => null,
        };
    }

    private function cmdStart(int|string $chatId, string $username): string
    {
        $fid = $this->resolveFidForSession($chatId);
        $this->resolveSession($chatId, false, $fid);

        $name = ucfirst(mb_strtolower($username));

        return "👋 Привет, {$name}!\n\n"
            . "Я — AI-эксперт AV8 Capital. 📊\n\n"
            . "Мои возможности:\n"
            . "🔍 *Глубокий анализ* — изучаю сайты, протоколы, проекты\n"
            . "📰 *Исследования* — собираю данные из открытых источников\n"
            . "💾 *База знаний* — накапливаю и систематизирую информацию\n"
            . "📊 *Аналитика* — DeFi, токены, рынки, тренды\n"
            . "🤖 *Делегирование* — могу поручить TelegramAgent детальное изучение\n\n"
            . "📌 *Команды:*\n"
            . "/help — список команд\n"
            . "/new — начать новый диалог\n"
            . "/clear — очистить историю\n\n"
            . "Напишите тему для исследования или URL для анализа!";
    }

    private function cmdHelp(int|string $chatId): string
    {
        return "📋 *Доступные команды:*\n\n"
            . "/start — приветствие и запуск\n"
            . "/help — этот список\n"
            . "/new — начать новый диалог\n"
            . "/clear — очистить историю\n"
            . "/choose N — выбрать вариант N из предложенного списка\n"
            . "/go N — то же, что /choose\n"
            . "—break — принудительный выход из цикла\n\n"
            . "💡 *Примеры запросов:*\n"
            . "• «Изучи протокол Suilend на Sui»\n"
            . "• «Собери информацию о https://example.com»\n"
            . "• «Сделай анализ рынка DeFi за неделю»\n"
            . "• «Какие исследования уже есть?»";
    }

    private function cmdClear(int|string $chatId): string
    {
        ChatSession::where('session_token', 'like', 'tg_' . $chatId . '%')
            ->where('status', 'active')
            ->update(['status' => 'archived']);

        $fid = $this->resolveFidForSession($chatId);
        $this->resolveSession($chatId, true, $fid);

        return "🧹 История диалога очищена. Можете задавать новые вопросы!";
    }

    private function cmdNew(int|string $chatId): string
    {
        ChatSession::where('session_token', 'like', 'tg_' . $chatId . '%')
            ->where('status', 'active')
            ->update(['status' => 'archived']);

        $fid = $this->resolveFidForSession($chatId);
        $this->resolveSession($chatId, true, $fid);

        return "🔄 Начинаю новый диалог! Предыдущая история сохранена. Чем могу помочь?";
    }

    // ── AI диалог ──────────────────────────────────────────────────────────

    /**
     * Обработать текстовое сообщение через AI с function calling.
     */
    private function handleAiDialog(
        int|string $chatId,
        string $text,
        array $message,
    ): string {
        try {
            $this->toolCallCount = [];
            $this->sendTyping($chatId);

            $fid = self::ANALYST_FID;

            // Получаем или создаём сессию с привязкой к fid
            $session = $this->resolveSession($chatId, false, $fid);

            // Трансформация команд выбора
            if (preg_match('/^\/(choose|go)\s*(\d+)/i', $text, $m)) {
                $text = "Я выбираю вариант {$m[2]}. Выполни это действие и покажи результат.";
            }

            // Ручной выход из цикла
            if ($this->isBreakKeyword($text)) {
                return $this->breakTheLoop($chatId, $session);
            }

            // Сохраняем сообщение пользователя
            $this->saveUserMessage($session, $text);

            // Обновляем заголовок сессии
            $session->updateTitle($text);

            // Детекция цикличности
            if ($this->detectLoop($session, $text)) {
                return $this->breakTheLoop($chatId, $session);
            }

            $intent = $this->intentDetector->detect($text, 'telegram', 'ru');

            // Загружаем контекст из Базы Знаний с привязкой к fid (с кэшированием)
            $knowledgeContext = $this->loadKnowledgeContext();

            // Делегирование TelegramAgent
            if ($this->shouldDelegateToAgent($text, $intent)) {
                return $this->delegateToAgent($text, $chatId, self::ANALYST_FID, $session, $intent);
            }

            // Загружаем историю для AI
            $history = $session->getHistoryForAi(20);

            // Формируем system prompt с динамическим fid
            $instructions = $this->buildAnalystPrompt($knowledgeContext, $fid, $intent);

            // Получаем инструменты аналитика
            $tools = $this->analyst->getTools();

            // Executor с передачей fid и rate limiting
            $toolExecutor = function (string $name, array $arguments): string {
                $this->checkToolRateLimit();
                $arguments['fid'] = self::ANALYST_FID;
                return $this->analyst->executeTool($name, $arguments);
            };

            // Отправляем запрос с поддержкой function calling.
            // Если провайдер отклоняет tools, отвечаем обычным AI-запросом.
            try {
                $result = $this->ai->chatWithTools(
                    $instructions,
                    $history,
                    $tools,
                    $toolExecutor,
                    ['max_tool_iterations' => 10],
                );
            } catch (RuntimeException $e) {
                Log::warning('Telegram AI tools request failed, retrying without tools.', [
                    'chat_id' => $chatId,
                    'fid' => $fid,
                    'error' => $e->getMessage(),
                ]);

                $result = $this->ai->chat(
                    $instructions,
                    $history,
                    ['max_tokens' => config('ai.channels.telegram.max_tokens', 2000)],
                );
            }

            $answer = $result['answer'] ?? '⚠️ Не удалось получить ответ. Попробуйте переформулировать вопрос.';

            // Проверка: не повторяет ли AI ответ
            if ($this->isAnswerRepeatOfLast($session, $answer)) {
                return $this->breakTheLoop($chatId, $session);
            }

            // Сохраняем ответ AI
            $this->saveAssistantMessage($session, $answer, $result);

            // Автообучение: сохраняем ценные диалоги в базу знаний проекта
            $this->autoLearn($fid, $history, $answer);

            return $answer;

        } catch (RuntimeException $e) {
            Log::error('Telegram AI dialog failed.', [
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
            ]);

            return '⚠️ Извините, AI-сервис временно недоступен. Попробуйте позже.';
        } catch (Throwable $e) {
            Log::error('Telegram: unexpected error in AI dialog.', [
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return '⚠️ Произошла непредвиденная ошибка. Попробуйте позже.';
        }
    }

    private function sendTyping(int|string $chatId): void
    {
        try {
            $this->bot->sendChatAction($chatId, 'typing');
        } catch (Throwable $e) {
            Log::debug('Telegram: sendChatAction skipped.', [
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function completeHumanAnswer(int|string $chatId, string $taskUuid, string $answer): string
    {
        if ($answer === '') {
            return 'Ответ пустой. Используйте: /answer <uuid> <текст ответа>';
        }

        $task = AgentTask::where('uuid', $taskUuid)
            ->where('status', 'waiting_human')
            ->first();

        if (! $task) {
            return "Задача {$taskUuid} не найдена или уже закрыта.";
        }

        $session = $task->session_token ? ChatSession::resolveByToken($task->session_token) : null;

        if ($session) {
            ChatMessage::create([
                'chat_session_id' => $session->id,
                'fid' => $task->fid,
                'firma' => $session->firma,
                'role' => 'assistant',
                'content' => $answer,
                'metadata' => [
                    'source' => 'telegram_human_answer',
                    'task_uuid' => $task->uuid,
                    'source_chat_id' => (string) $chatId,
                ],
            ]);
        }

        $result = [
            'answer' => $answer,
            'message' => $answer,
            'human_answer' => true,
            'task_uuid' => $task->uuid,
        ];

        $this->orchestrator->updateTaskStatus($task->id, 'completed', $result);
        $this->orchestrator->sendTaskResult($task->fresh(), $result);

        return "✅ Ответ передан в веб-чат по задаче {$task->uuid}.";
    }

    /**
     * Проверка лимита вызовов tools.
     */
    private function checkToolRateLimit(): void
    {
        $this->toolCallCount['total'] = ($this->toolCallCount['total'] ?? 0) + 1;
        if ($this->toolCallCount['total'] > self::MAX_TOOL_CALLS) {
            throw new \RuntimeException('Превышен лимит вызовов инструментов за один запрос.');
        }
    }

    // ── Делегирование TelegramAgent ────────────────────────────────────────

    /**
     * Делегировать задачу TelegramAgent с передачей fid.
     */
    private function delegateToAgent(string $text, int|string $chatId, int $fid, ChatSession $session, array $intent = []): string
    {
        $taskType = $this->detectTaskType($text, $intent);

        $inputData = [
            'query' => $this->buildDelegatedQuery($text, $intent, $taskType),
            'question' => $text,
            'chat_id' => $chatId,
            'language' => 'ru',
            'channel' => 'telegram',
            'intent' => $intent,
        ];

        if ($taskType === 'study_website') {
            $extractedUrl = $this->extractUrlFromText($text);
            if ($extractedUrl) {
                $inputData['url'] = $extractedUrl;
            }
        }

        $task = $this->orchestrator->createTask(
            sourceAgent: 'telegram_expert',
            targetAgent: 'telegram',
            fid: $fid,
            taskType: $taskType,
            inputData: $inputData,
            sessionToken: $session->session_token,
        );

        if (($intent['type'] ?? '') === WebChatIntentDetector::PUBLISH_NEWS) {
            return "⏳ Принял. Передал задачу автору-аналитику: он проверит источники, подготовит материал и при необходимости опубликует статью или новость в проекте fid={$fid}. ID задачи: {$task->uuid}.";
        }

        return "⏳ Принял. Передал задачу аналитику-помощнику: он проверит источники, сохранит полезные данные и вернёт результат сюда. ID задачи: {$task->uuid}.";
    }

    /**
     * Определить, нужно ли делегировать задачу TelegramAgent.
     */
    private function shouldDelegateToAgent(string $text, array $intent = []): bool
    {
        if (in_array(($intent['type'] ?? ''), [WebChatIntentDetector::RESEARCH, WebChatIntentDetector::PUBLISH_NEWS], true)) {
            return true;
        }

        $delegateKeywords = [
            'изучи сайт', 'проанализируй сайт', 'просмотри сайт',
            'сохрани в базу знаний',
            'массовый анализ',
            'изучи', 'спарси', 'просмотри',
        ];

        foreach ($delegateKeywords as $keyword) {
            if (mb_stripos($text, $keyword) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Извлечь URL из текста запроса.
     */
    private function extractUrlFromText(string $text): ?string
    {
        if (preg_match('/https?:\/\/[^\s]+/i', $text, $matches)) {
            return rtrim($matches[0], ',.!?:;');
        }

        if (preg_match('/\b[a-z0-9]([a-z0-9\-]*[a-z0-9])?(\.[a-z0-9]([a-z0-9\-]*[a-z0-9])?)*\.[a-z]{2,}(?:\/[^\s]*)?/i', $text, $matches)) {
            return rtrim($matches[0], ',.!?:;');
        }

        return null;
    }

    /**
     * Определить тип задачи для TelegramAgent.
     */
    private function detectTaskType(string $text, array $intent = []): string
    {
        if (($intent['type'] ?? '') === WebChatIntentDetector::PUBLISH_NEWS) {
            return 'complex_question';
        }

        return match (true) {
            preg_match('/изучи сайт|проанализируй сайт|просмотри сайт|спарси/i', $text) => 'study_website',
            preg_match('/сохрани.*баз[уе].*знан/i', $text) => 'save_to_knowledge',
            preg_match('/массов|все товар|все проект/i', $text) => 'mass_analysis',
            default => 'complex_question',
        };
    }

    private function buildDelegatedQuery(string $text, array $intent, string $taskType): string
    {
        if (($intent['type'] ?? '') === WebChatIntentDetector::PUBLISH_NEWS) {
            $topic = trim((string) ($intent['topic'] ?? ''));

            return "Задача из Telegram-чата. Нужно подготовить и при необходимости опубликовать материал для проекта fid=1.\n"
                . ($topic !== '' ? "Тема: {$topic}\n" : '')
                . "Исходный запрос пользователя: {$text}\n\n"
                . "Работай как аналитик и помощник AV8 Capital: проверь сохранённые источники/знания, при необходимости загрузи открытые источники, затем подготовь законченный текст. "
                . "Если запрос явно просит новость или статью — используй publish_news или publish_article с fid=1. "
                . "Не переводи задачу оператору из-за пустого TELEGRAM_OPERATOR_CHAT_ID.";
        }

        if (($intent['type'] ?? '') === WebChatIntentDetector::RESEARCH && $taskType === 'complex_question') {
            return "Задача из Telegram-чата. Проведи исследование для проекта fid=1.\n"
                . "Исходный запрос пользователя: {$text}\n\n"
                . "Сначала проверь сохранённые источники/знания, затем при необходимости загрузи открытые источники. "
                . "Сохрани полезные данные и верни короткий человеческий отчёт без технических заготовок.";
        }

        return $text;
    }

    // ── Управление сессиями ────────────────────────────────────────────────

    /**
     * Resolve Telegram project context.
     *
     * @param  array<string, mixed>  $message
     */
    private function resolveFid(array $message): int
    {
        $fid = (int) ($message['fid'] ?? 0);
        if ($fid > 0) {
            return $fid;
        }

        $chatId = $message['chat']['id'] ?? null;
        if ($chatId !== null) {
            $session = ChatSession::where('session_token', 'like', 'tg_' . $chatId . '%')
                ->where('status', 'active')
                ->orderByDesc('created_at')
                ->first();

            if ($session && (int) $session->fid > 0 && (int) $session->fid !== $this->defaultAnalystFid) {
                return (int) $session->fid;
            }
        }

        return self::ANALYST_FID;
    }

    /**
     * Определить fid для сессии при выполнении команд.
     */
    private function resolveFidForSession(int|string $chatId): int
    {
        $session = ChatSession::where('session_token', 'like', 'tg_' . $chatId . '%')
            ->where('status', 'active')
            ->orderByDesc('created_at')
            ->first();

        if ($session && (int) $session->fid > 0) {
            return (int) $session->fid;
        }

        return self::ANALYST_FID;
    }

    private function defaultFid(): int
    {
        $configured = (int) config('ai.channels.telegram.fid', self::ANALYST_FID);

        return $configured > 0 ? $configured : $this->defaultAnalystFid;
    }

    /**
     * Загрузить контекст из Базы Знаний с привязкой к fid и кэшированием.
     */
    private function loadKnowledgeContext(?int $fid = null): string
    {
        $cacheKey = $fid === null
            ? 'telegram_knowledge_context_global'
            : 'telegram_knowledge_context_fid_' . $fid;

        try {
            return Cache::remember($cacheKey, self::KNOWLEDGE_CACHE_TTL, function () use ($fid): string {
                return $this->knowledgeService->getContext($fid);
            });
        } catch (Throwable $e) {
            Log::warning('TelegramChatService: failed to load knowledge context.', [
                'fid' => $fid,
                'error' => $e->getMessage(),
            ]);

            return '';
        }
    }

    /**
     * Автообучение: сохраняет ценные диалоги в базу знаний проекта.
     */
    private function autoLearn(int $fid, array $history, string $answer): void
    {
        if ($fid <= 0 || empty($history)) {
            return;
        }

        try {
            $this->knowledgeService->autoLearn($fid, array_merge($history, [
                ['role' => 'assistant', 'content' => $answer],
            ]));
        } catch (Throwable $e) {
            Log::debug('TelegramChatService: autoLearn skipped.', [
                'fid' => $fid,
                'error' => $e->getMessage(),
            ]);
        }
    }

    // ── System prompt эксперта-аналитика ───────────────────────────────────

    /**
     * Сформировать system prompt для AI-эксперта с динамическим fid.
     */
    private function buildAnalystPrompt(string $knowledgeContext = '', int $currentFid = self::ANALYST_FID, array $intent = []): string
    {
        $intentType = (string) ($intent['type'] ?? WebChatIntentDetector::FAQ);
        $intentTopic = (string) ($intent['topic'] ?? '');
        $intentInstruction = $this->intentInstruction($intentType);

        $prompt = <<<PROMPT
ТЫ — AI-АНАЛИТИК И ПОМОЩНИК AV8 Capital.

Твоя специализация — человеческое общение, глубокая аналитика, исследования, накопление базы знаний и подготовка материалов.
Ты ведёшь себя одинаково в Telegram и вебчате laravel-api: спокойно, по делу, без роботизированных заготовок.

ТВОЙ ID ПРОЕКТА (fid): {$currentFid}
Все сохраняемые данные привязываются к проекту fid={$currentFid}.

ТЕКУЩЕЕ НАМЕРЕНИЕ ПОЛЬЗОВАТЕЛЯ:
- type: {$intentType}
- topic: {$intentTopic}

МАРШРУТ ОТВЕТА:
{$intentInstruction}

🧠 КОНФИГУРАЦИЯ:
Твои правила работы и инструкции задаются администратором через панель /settings → База знаний → категория «telegram_instruction».
Эти инструкции переданы ниже в разделе «🧠 ИНСТРУКЦИИ». Следуй им как основной конфигурации — они имеют наивысший приоритет.

📚 БАЗА ЗНАНИЙ ПРОЕКТА:
Записи из базы знаний содержат проверенную информацию. Используй их для ответов на вопросы.
Контекст загружен для проекта fid={$currentFid}.

🤖 ДЕЛЕГИРОВАНИЕ TELEGRAM AGENT:
У тебя есть помощник — TelegramAgent (знаток-писатель).
- Если нужно изучить сайт, протокол, ресурс — делегируй задачу
- TelegramAgent умеет: парсить сайты, сохранять источники, публиковать статьи/новости/обзоры
- После выполнения TelegramAgent вернёт отчёт, и ты сможешь его проанализировать

ТВОИ ИНСТРУМЕНТЫ (через функции):

1. fetch_url(url) — Загрузить содержимое веб-страницы по URL.
2. save_source(url, title, summary, content_type, fid) — Сохранить источник в БД (fid={$currentFid}).
3. search_sources(query) — Искать по сохранённым источникам (по всем проектам).
4. start_research(topic, fid) — Начать новое исследование (fid={$currentFid}).
5. complete_research(research_id, summary) — Завершить исследование с итоговым отчётом.
6. list_researches() — Показать все исследования (по всем проектам).
7. save_knowledge(title, content, category, fid) — Сохранить заметку в БЗ (fid={$currentFid}).
8. get_research_sources(research_id) — Получить все источники исследования.
9. publish_article(title, content, summary, fid) — Опубликовать аналитическую статью в базе знаний и разделе новостей.
10. publish_news(title, content, summary, source_url, fid) — Опубликовать новостной материал в базе знаний и разделе новостей.
11. publish_review(title, content, summary, rating, fid) — Опубликовать обзор/рецензию.

АЛГОРИТМ РАБОТЫ ЭКСПЕРТА:

1. Анализ и исследования:
   → Используй search_sources чтобы найти уже собранную информацию
   → Используй fetch_url для быстрой проверки
   → Для глубокого изучения — делегируй TelegramAgent
   → Используй start_research/complete_research для структурирования

2. База знаний:
   → Используй save_knowledge для сохранения ценных инсайтов (с fid={$currentFid})
   → Используй search_sources для поиска в ранее сохранённом

3. Взаимодействие с TelegramAgent:
   → Если задача требует детального изучения сайта — делегируй
   → После получения отчёта — проанализируй и сохрани выводы

4. Публикации:
   → Если пользователь просит «новость», «новостную статью», «материал», «опубликуй» — подготовь готовый текст и вызови publish_news или publish_article.
   → Не отвечай техническими заготовками вроде «начинаю загрузку» как финальным результатом.
   → Если источников недостаточно, сначала используй search_sources/fetch_url, затем публикуй только проверяемые факты.
   → После публикации кратко сообщи заголовок, fid и что материал сохранён в разделе новостей.

ПРАВИЛА ЭКСПЕРТА:
- Твоя задача — анализ, исследования, накопление базы знаний и помощь пользователю следующим понятным шагом
- Пиши естественно: без «как ИИ», без канцелярита, без повторения вопроса пользователя
- Обычно отвечай 2-5 короткими предложениями или 3-5 пунктами
- Если нужно уточнение — задай один короткий вопрос
- Используй эмодзи для наглядности (📊 🔍 💾 📡 🌐 📝)
- Всегда сохраняй источники через save_source с правильным fid
- Не выдумывай данные — используй только то, что получил из функций
- Ответы давай на русском языке

⚠️ ЗАЩИТА ОТ ЗАЦИКЛИВАНИЯ:
- НЕ повторяй список вариантов, если пользователь уже сделал выбор.
- Если пользователь не может определиться — предложи конкретный вариант по умолчанию.
- Ты НЕ должен генерировать нумерованные списки вариантов более 2 раз подряд.
- Не задавай уточняющие вопросы, если их можно избежать — сразу действуй.
- Команда /choose N означает, что пользователь выбрал вариант N — сразу выполняй его.
PROMPT;

        $knowledgeSection = $knowledgeContext !== ''
            ? "\n\n{$knowledgeContext}"
            : '';

        return $prompt . $knowledgeSection;
    }

    private function intentInstruction(string $intentType): string
    {
        return match ($intentType) {
            WebChatIntentDetector::SMALL_TALK => '- Ответь тепло и коротко. Не запускай исследование без явной просьбы.',
            WebChatIntentDetector::FAQ => '- Дай короткий фактологический ответ. Если есть база знаний или sources, используй их.',
            WebChatIntentDetector::HOW_TO => '- Дай один понятный следующий шаг или короткую инструкцию. Не перегружай вариантами.',
            WebChatIntentDetector::SUPPORT => '- Признай проблему, попроси один недостающий факт при необходимости и предложи ближайшее действие.',
            WebChatIntentDetector::RESEARCH => '- Сначала ищи существующие данные. Если нужно глубокое изучение, делегируй TelegramAgent и не имитируй процесс словами.',
            WebChatIntentDetector::PUBLISH_NEWS => '- Подготовка и публикация материалов должна идти через publish_news/publish_article или делегирование TelegramAgent.',
            WebChatIntentDetector::WALLET_ACTION => '- Не выдумывай onchain-состояние, не проси секреты, объясняй только безопасный следующий шаг.',
            default => '- Ответь по сути и используй базу знаний при наличии.',
        };
    }
}
