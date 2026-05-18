<?php

namespace App\Agents;

use App\Contracts\AiClientInterface;
use App\Models\AgentTask;
use App\Models\ChatSession;
use App\Services\AgentOrchestrator;
use App\Services\AiClientFactory;
use App\Services\TelegramBotService;
use App\Services\AiKnowledgeService;
use App\Services\AnalystService;
use App\Services\WebScraperService;
use App\Models\ChatMessage;
use Illuminate\Support\Facades\Log;
use Throwable;

class TelegramAgent
{
    const ANALYST_FID = 12;

    /** Максимальное количество повторяющихся ответов AI до принудительного прерывания */
    const MAX_CONSECUTIVE_SIMILAR_ANSWERS = 2;

    /** Минимальная длина ответа для проверки на повторение */
    const MIN_ANSWER_LENGTH_FOR_COMPARISON = 30;

    private readonly AiClientInterface $ai;

    public function __construct(
        private TelegramBotService $bot,
        private AiClientFactory $aiFactory,
        private AiKnowledgeService $knowledgeService,
        private AgentOrchestrator $orchestrator,
        private AnalystService $analyst,
    ) {
        $this->ai = $this->aiFactory->make('telegram');
    }

    /**
     * Переключить AI-клиент на другой канал.
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

    /**
     * Обработать входящее сообщение из Telegram.
     * Вызывается ТОЛЬКО из ProcessAgentJob для фоновых задач.
     */
    public function handleMessage(array $message): string
    {
        $chatId = $message['chat']['id'] ?? 0;
        $text = trim($message['text'] ?? '');
        $username = $message['from']['username'] ?? ($message['chat']['username'] ?? '');

        if (empty($text)) {
            return '';
        }

        if (str_starts_with($text, '/')) {
            return $this->handleCommand($chatId, $text, $username);
        }

        return $this->handleAiDialog($chatId, $text, $message);
    }

    /**
     * Выполнить задачу, делегированную от другого агента (TelegramChatService, BackendAgent).
     */
    public function executeTask(AgentTask $task): array
    {
        return match ($task->task_type) {
            'send_message' => $this->sendTelegramMessage($task),
            'forward_to_user' => $this->forwardToUser($task),
            'study_website' => $this->executeStudyWebsite($task),
            'save_to_knowledge' => $this->executeSaveToKnowledge($task),
            'mass_analysis' => $this->executeMassAnalysis($task),
            'complex_question' => $this->executeComplexQuestion($task),
            default => throw new \InvalidArgumentException("TelegramAgent: unknown task_type '{$task->task_type}'"),
        };
    }

    /**
     * Делегировать задачу BackendAgent.
     */
    public function delegateToBackend(
        int $fid,
        string $taskType,
        array $inputData,
        ?string $sessionToken = null,
    ): AgentTask {
        return $this->orchestrator->createTask(
            sourceAgent: 'telegram',
            targetAgent: 'backend',
            fid: $fid,
            taskType: $taskType,
            inputData: $inputData,
            sessionToken: $sessionToken,
        );
    }

    // ════════════════════════════════════════════════════════════════
    //  PRIVATE
    // ════════════════════════════════════════════════════════════════

    private function handleCommand(int|string $chatId, string $text, string $username): string
    {
        return match (true) {
            str_starts_with($text, '/start')  => $this->cmdStart($chatId, $username),
            str_starts_with($text, '/help')   => $this->cmdHelp($chatId),
            str_starts_with($text, '/clear')  => $this->cmdClear($chatId),
            str_starts_with($text, '/new')    => $this->cmdNew($chatId),
            default                           => $this->bot->sendMessage($chatId, "Неизвестная команда. Используйте /help"),
        };
    }

    private function cmdStart(int|string $chatId, string $username): string
    {
        $welcome = "👋 *Добро пожаловать! Я — TelegramAgent, знаток-писатель AV8 Capital.*\n\n"
            . "Мои возможности:\n"
            . "🌐 *Изучать сайты* — парсить, сохранять, систематизировать\n"
            . "📝 *Публиковать* — статьи, новости, обзоры\n"
            . "📊 *Анализировать* — товары, продажи, клиентов\n"
            . "🔍 *Искать* — информацию в базе знаний\n"
            . "📰 *Работать с новостями*\n\n"
            . "Команды: /help — помощь, /clear — очистить историю, /new — новый диалог.";

        return $this->bot->sendMarkdown($chatId, $welcome);
    }

    private function cmdHelp(int|string $chatId): string
    {
        $help = "📖 *Команды:*\n"
            . "/start — приветствие\n"
            . "/help — эта справка\n"
            . "/clear — очистить историю диалога\n"
            . "/new — начать новый диалог\n"
            . "/choose N — выбрать вариант N из предложенного списка\n"
            . "/go N — то же, что /choose\n"
            . "—break — принудительный выход из цикла\n\n"
            . "💡 *Примеры запросов:*\n"
            . "• «Изучи сайт example.com и сохрани данные»\n"
            . "• «Опубликуй статью о DeFi на Sui»\n"
            . "• «Напиши обзор протокола Suilend»\n"
            . "• «Сделай анализ рынка»";

        return $this->bot->sendMarkdown($chatId, $help);
    }

    private function cmdClear(int|string $chatId): string
    {
        // Архивируем текущую активную сессию (если есть)
        ChatSession::where('session_token', 'like', 'tg_agent_' . $chatId . '%')
            ->where('status', 'active')
            ->update(['status' => 'archived']);

        // Создаём новую сессию с уникальным токеном
        $this->resolveSession($chatId, true);

        return $this->bot->sendMessage($chatId, "🧹 История диалога очищена. Задавайте новый вопрос!");
    }

    private function cmdNew(int|string $chatId): string
    {
        // Архивируем все активные сессии для этого чата
        ChatSession::where('session_token', 'like', 'tg_agent_' . $chatId . '%')
            ->where('status', 'active')
            ->update(['status' => 'archived']);

        // Создаём новую сессию с уникальным токеном
        $this->resolveSession($chatId, true);

        return $this->bot->sendMessage($chatId, "🆕 Начинаем новый диалог. Задавайте вопрос!");
    }

    // ── Обработка задач от TelegramChatService ────────────────────

    /**
     * Изучить сайт: парсит, сохраняет источник, возвращает отчёт.
     */
    private function executeStudyWebsite(AgentTask $task): array
    {
        $url = $task->input_data['url'] ?? '';
        $query = $task->input_data['query'] ?? '';

        try {
            // Загружаем страницу через WebScraperService
            $scraper = app(WebScraperService::class);
            $result = $scraper->fetchUrl($url);

            if (isset($result['error'])) {
                return $this->taskResult($task, '❌ Не удалось загрузить сайт: ' . $result['error']);
            }

            $content = $result['content'] ?? '';
            $title = $result['title'] ?? $url;

            // Сохраняем источник
            $sourceResult = $this->analyst->executeTool('save_source', [
                'url' => $url,
                'title' => $title,
                'summary' => mb_substr($content, 0, 500),
                'content_type' => 'website',
            ]);

            // Сохраняем в базу знаний
            $kbResult = $this->analyst->executeTool('save_knowledge', [
                'title' => "Анализ сайта: {$title}",
                'content' => "URL: {$url}\n\n{$content}",
                'category' => 'analysis',
            ]);

            $summary = mb_substr($content, 0, 2000);

            $report = "📄 *Отчёт по изучению сайта*\n\n"
                . "**URL:** {$url}\n"
                . "**Заголовок:** {$title}\n"
                . "**Содержимое:**\n{$summary}\n\n"
                . "✅ Источник сохранён\n"
                . "✅ Информация добавлена в базу знаний";

            return $this->taskResult($task, $report);

        } catch (Throwable $e) {
            Log::error('TelegramAgent: study_website failed.', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            return $this->taskResult($task, '❌ Ошибка при изучении сайта: ' . $e->getMessage());
        }
    }

    /**
     * Сохранить данные в базу знаний.
     */
    private function executeSaveToKnowledge(AgentTask $task): array
    {
        $query = $task->input_data['query'] ?? '';

        $result = $this->analyst->executeTool('save_knowledge', [
            'title' => 'Запись из TelegramAgent',
            'content' => $query,
            'category' => 'analysis',
        ]);

        return $this->taskResult($task, "✅ Данные сохранены в базу знаний.\n{$result}");
    }

    /**
     * Массовый анализ.
     */
    private function executeMassAnalysis(AgentTask $task): array
    {
        $query = $task->input_data['query'] ?? '';

        // Делегируем BackendAgent для массового анализа
        $backendTask = $this->delegateToBackend(
            fid: $task->fid,
            taskType: 'mass_analysis',
            inputData: $task->input_data,
            sessionToken: $task->session_token,
        );

        return $this->taskResult($task,
            "⏳ Задача массового анализа передана BackendAgent (ID: {$backendTask->uuid}). "
            . "Результат будет доступен позже."
        );
    }

    /**
     * Сложный вопрос — анализируем через AI.
     */
    private function executeComplexQuestion(AgentTask $task): array
    {
        $query = $task->input_data['query'] ?? '';
        $chatId = $task->input_data['chat_id'] ?? 0;

        try {
            $fid = $task->fid;

            // Создаём временную сессию для AI
            $session = $this->resolveSession($chatId);
            $this->saveUserMessage($session, $query);

            // Загружаем knowledge для контекста (без привязки к fid)
            $knowledgeContext = $this->knowledgeService->getContext(null);

            // Получаем историю
            $history = $session->getHistoryForAi(20);

            // Формируем промпт
            $instructions = $this->buildAnalystPrompt($knowledgeContext);
            $tools = $this->analyst->getTools();

            $toolExecutor = function (string $name, array $arguments): string {
                return $this->analyst->executeTool($name, $arguments);
            };

            $result = $this->ai->chatWithTools(
                $instructions,
                $history,
                $tools,
                $toolExecutor,
                ['max_tool_iterations' => 10],
            );

            $answer = $result['answer'] ?? '⚠️ Не удалось получить ответ.';
            $this->saveAssistantMessage($session, $answer, $result);

            return $this->taskResult($task, $answer);

        } catch (Throwable $e) {
            Log::error('TelegramAgent: complex_question failed.', [
                'error' => $e->getMessage(),
            ]);
            return $this->taskResult($task, '❌ Ошибка обработки запроса: ' . $e->getMessage());
        }
    }

    /**
     * Сформировать результат задачи. Доставкой результата в исходный канал
     * занимается ProcessAgentTask после завершения executeTask().
     */
    private function taskResult(AgentTask $task, string $message): array
    {
        return [
            'answer' => $message,
            'message' => $message,
            'task_uuid' => $task->uuid,
        ];
    }

    // ── Обработка диалога ─────────────────────────────────────────

    private function handleAiDialog(int|string $chatId, string $text, array $message): string
    {
        $this->bot->sendChatAction($chatId, 'typing');

        $fid = $message['fid'] ?? self::ANALYST_FID;

        // Определяем fid из контекста, если не задан явно
        if ($fid === self::ANALYST_FID) {
            $detectedFid = $this->detectFidFromContext($text);
            if ($detectedFid !== null) {
                $fid = $detectedFid;
            }
        }

        $session = $this->resolveSession($chatId);

        // ════════════════════════════════════════════════════════════════
        //  ТРАНСФОРМАЦИЯ КОМАНД ВЫБОРА
        // ════════════════════════════════════════════════════════════════
        if (preg_match('/^\/(choose|go)\s*(\d+)/i', $text, $m)) {
            $text = "Я выбираю вариант {$m[2]}. Выполни это действие и покажи результат.";
        }

        // ════════════════════════════════════════════════════════════════
        //  РУЧНОЙ ВЫХОД ИЗ ЦИКЛА
        // ════════════════════════════════════════════════════════════════
        if ($this->isBreakKeyword($text)) {
            return $this->breakTheLoop($chatId, $session);
        }

        $this->saveUserMessage($session, $text);

        // Loop detection
        if ($this->detectLoop($session, $text)) {
            return $this->breakTheLoop($chatId, $session);
        }

        // Загружаем knowledge base (без привязки к fid)
        $knowledgeContext = $this->knowledgeService->getContext(null);

        // Загружаем историю
        $history = $session->getHistoryForAi(20);

        // Формируем system prompt с инструментами
        $systemPrompt = $this->buildAnalystPrompt($knowledgeContext);

        // Получаем инструменты аналитика
        $tools = $this->analyst->getTools();

        // Executor для вызова инструментов
        $toolExecutor = function (string $name, array $arguments): string {
            return $this->analyst->executeTool($name, $arguments);
        };

        // Вызываем AI с function calling
        $result = $this->ai->chatWithTools(
            instructions: $systemPrompt,
            messages: $history,
            tools: $tools,
            toolExecutor: $toolExecutor,
            options: [
                'temperature' => 0.3,
                'fid' => $fid,
                'max_tool_iterations' => 10,
            ],
        );

        $answer = $result['answer'] ?? '⚠️ Не удалось получить ответ. Попробуйте переформулировать вопрос.';

        // Проверка на повторение ответа
        if ($this->isAnswerRepeatOfLast($session, $answer)) {
            return $this->breakTheLoop($chatId, $session);
        }

        // Сохраняем ответ
        $this->saveAssistantMessage($session, $answer, $result);

        return $this->bot->sendMarkdown($chatId, $answer);
    }

    /**
     * Попытаться определить fid из текста запроса.
     * Анализирует упоминания проектов, fid-номеров и т.д.
     */
    private function detectFidFromContext(string $text): ?int
    {
        // Явное упоминание fid
        if (preg_match('/fid[=:\s]*(\d+)/i', $text, $m)) {
            return (int) $m[1];
        }

        // По ключевым словам можно добавить маппинг проектов
        // Пока возвращаем ANALYST_FID по умолчанию
        return null;
    }

    // ── Loop Detection ────────────────────────────────────────────

    private function detectLoop(ChatSession $session, string $currentUserText): bool
    {
        $recentAssistantMessages = $session->messages()
            ->where('role', 'assistant')
            ->orderByDesc('created_at')
            ->limit(self::MAX_CONSECUTIVE_SIMILAR_ANSWERS + 1)
            ->get()
            ->pluck('content')
            ->toArray();

        if (count($recentAssistantMessages) < 2) {
            return false;
        }

        $similarCount = 0;
        for ($i = 0; $i < count($recentAssistantMessages) - 1; $i++) {
            if ($this->areTextsSimilar($recentAssistantMessages[$i], $recentAssistantMessages[$i + 1])) {
                $similarCount++;
            }
        }

        if ($similarCount >= self::MAX_CONSECUTIVE_SIMILAR_ANSWERS) {
            Log::warning('TelegramAgent: detected AI loop', [
                'session_id' => $session->id,
                'similar_count' => $similarCount,
            ]);
            return true;
        }

        return false;
    }

    private function isAnswerRepeatOfLast(ChatSession $session, string $newAnswer): bool
    {
        $lastAssistantMessage = $session->messages()
            ->where('role', 'assistant')
            ->orderByDesc('created_at')
            ->first();

        if (! $lastAssistantMessage) {
            return false;
        }

        return $this->areTextsSimilar($lastAssistantMessage->content, $newAnswer);
    }

    private function areTextsSimilar(string $text1, string $text2): bool
    {
        $t1 = trim($text1);
        $t2 = trim($text2);

        if ($t1 === '' && $t2 === '') {
            return true;
        }

        if ($t1 === '' || $t2 === '') {
            return false;
        }

        // 1. Полное совпадение
        if ($t1 === $t2) {
            return true;
        }

        // 2. Короткие ответы — высокий порог схожести
        if (mb_strlen($t1) < self::MIN_ANSWER_LENGTH_FOR_COMPARISON || mb_strlen($t2) < self::MIN_ANSWER_LENGTH_FOR_COMPARISON) {
            similar_text($t1, $t2, $percent);
            return $percent > 80;
        }

        // 3. Стандартное текстуальное сравнение
        similar_text($t1, $t2, $percent);
        if ($percent > 85) {
            return true;
        }

        // 4. Детекция вопросов выбора (главная причина зацикливания)
        $questionWords = [
            'Что именно', 'Хотите', 'уточнить', 'Запустить', 'выполнить',
            '?:', 'выбери', 'выбирай', 'Вариант', 'вариант',
            'Выберите', 'pick', 'choose', 'какой', 'Что скажешь',
            'куда нырнём', 'направление', 'предпочитаешь',
        ];
        $t1HasQuestion = false;
        $t2HasQuestion = false;

        foreach ($questionWords as $word) {
            if (mb_stripos($t1, $word) !== false) {
                $t1HasQuestion = true;
            }
            if (mb_stripos($t2, $word) !== false) {
                $t2HasQuestion = true;
            }
        }

        // Если оба сообщения содержат вопросительные слова выбора
        if ($t1HasQuestion && $t2HasQuestion && $percent > 50) {
            return true;
        }

        // 5. Детекция повторяющихся нумерованных списков выбора
        $choicePattern = '/(?:^|\n)\s*(?:\d+[\.\)]|Вариант\s*\d+|—)\s*/miu';
        $hasChoiceList1 = preg_match($choicePattern, $t1);
        $hasChoiceList2 = preg_match($choicePattern, $t2);

        if ($hasChoiceList1 && $hasChoiceList2 && $percent > 40) {
            Log::debug('TelegramAgent: detected choice list repetition.', [
                'percent' => $percent,
            ]);
            return true;
        }

        return false;
    }

    /**
     * Проверить, является ли текст командой ручного выхода из цикла.
     */
    private function isBreakKeyword(string $text): bool
    {
        $normalized = mb_strtolower(trim($text));

        $keywords = [
            '--break',
            'стоп',
            'хватит',
            'выйти из цикла',
            'остановись',
            'прекрати',
            'break',
            '/stop',
            '/exit',
        ];

        foreach ($keywords as $keyword) {
            if ($normalized === $keyword) {
                return true;
            }
        }

        // Также срабатывает на фразы, начинающиеся с этих слов
        $prefixes = [
            '--break',
            'стоп',
            'хватит',
            'выйти из цикла',
            'остановись',
        ];

        foreach ($prefixes as $prefix) {
            if (str_starts_with($normalized, $prefix)) {
                return true;
            }
        }

        return false;
    }

    private function breakTheLoop(int|string $chatId, ChatSession $session): string
    {
        $session->messages()
            ->where('role', 'assistant')
            ->orderByDesc('created_at')
            ->limit(self::MAX_CONSECUTIVE_SIMILAR_ANSWERS)
            ->delete();

        $message = "⚠️ *Кажется, я зашёл в тупик.* Давайте начнём диалог заново.\n\n"
            . "Напишите ваш вопрос конкретно, например:\n"
            . "• «Изучи сайт example.com»\n"
            . "• «Опубликуй статью о DeFi»\n"
            . "• «Найди информацию в базе знаний»\n\n"
            . "Или используйте /clear чтобы очистить историю, /new для нового диалога.";

        $this->saveAssistantMessage($session, $message, [
            'model' => 'loop_breaker',
            'usage' => [],
            'provider' => $this->ai->getProviderName(),
        ]);

        return $this->bot->sendMarkdown($chatId, $message);
    }

    // ── Сессии ────────────────────────────────────────────────────

    private function resolveSession(int|string $chatId, bool $forceNew = false): ChatSession
    {
        $token = 'tg_agent_' . $chatId;
        $newToken = $forceNew
            ? $token . '_' . now()->timestamp
            : $token;

        if (!$forceNew) {
            $session = ChatSession::resolveByToken($token);
            if ($session) {
                return $session;
            }
        }

        return ChatSession::createSession([
            'session_token' => $newToken,
            'fid' => self::ANALYST_FID,
            'language' => 'ru',
            'status' => 'active',
        ]);
    }

    private function saveUserMessage(ChatSession $session, string $text): ChatMessage
    {
        return ChatMessage::create([
            'chat_session_id' => $session->id,
            'fid' => $session->fid,
            'role' => 'user',
            'content' => $text,
        ]);
    }

    private function saveAssistantMessage(ChatSession $session, string $answer, array $result): ChatMessage
    {
        return ChatMessage::create([
            'chat_session_id' => $session->id,
            'fid' => $session->fid,
            'role' => 'assistant',
            'content' => $answer,
            'metadata' => [
                'model' => $result['model'] ?? null,
                'usage' => $result['usage'] ?? null,
                'provider' => $this->ai->getProviderName(),
            ],
        ]);
    }

    // ── Отправка сообщений (задачи от других агентов) ────────────

    private function sendTelegramMessage(AgentTask $task): array
    {
        $chatId = $task->input_data['chat_id'];
        $message = $task->input_data['message'];

        $this->bot->sendMarkdown($chatId, $message);

        return ['sent' => true, 'chat_id' => $chatId];
    }

    private function forwardToUser(AgentTask $task): array
    {
        $chatId = $task->input_data['chat_id'];
        $text = $task->input_data['text'] ?? $task->input_data['message'] ?? '';

        $this->bot->sendMessage($chatId, $text);

        return ['sent' => true, 'chat_id' => $chatId];
    }

    // ── System Prompt ЗНАТОК-ПИСАТЕЛЬ ────────────────────────────

    private function buildAnalystPrompt(string $knowledgeContext = ''): string
    {
        $prompt = <<<'PROMPT'
ТЫ — TelegramAgent, ЗНАТОК-ПИСАТЕЛЬ AV8 Capital.

ТВОЯ РОЛЬ:
Ты — исследователь и автор. Твоя задача — изучать сайты, протоколы, ресурсы, публиковать статьи, новости и обзоры.
Ты работаешь по запросу от TelegramChatService (Эксперта) или напрямую от пользователя.

🧠 КОНФИГУРАЦИЯ:
Твои правила работы задаются в /settings → База знаний → категория «telegram_instruction».
Следуй им как основной конфигурации.

📚 БАЗА ЗНАНИЙ ПРОЕКТА:
Используй базу знаний для поиска информации и сохранения результатов.

🔍 ОПРЕДЕЛЕНИЕ ПРОЕКТА (fid):
- Если в запросе явно указан fid — используй его
- Если не указан — попробуй определить из контекста (упоминание проекта, компании)
- По умолчанию используй fid=12 (AV8 Capital research)
- Сохраняй данные под тем fid, которому соответствует информация

ТВОИ ИНСТРУМЕНТЫ:

🌐 ИЗУЧЕНИЕ САЙТОВ:
1. fetch_url(url) — Загрузить содержимое веб-страницы
2. save_source(url, title, summary, content_type) — Сохранить источник в БД
3. search_sources(query) — Поиск по сохранённым источникам

📝 ПУБЛИКАЦИИ:
4. publish_article(title, content, summary, fid) — Опубликовать аналитическую статью
5. publish_news(title, content, summary, source_url, fid) — Опубликовать новость
6. publish_review(title, content, summary, rating, fid) — Опубликовать обзор с оценкой

📊 ИССЛЕДОВАНИЯ:
7. start_research(topic) — Начать исследование
8. complete_research(research_id, summary) — Завершить с отчётом
9. list_researches() — Список исследований
10. get_research_sources(research_id) — Источники исследования

💾 БАЗА ЗНАНИЙ:
11. save_knowledge(title, content, category) — Сохранить заметку в БЗ

АЛГОРИТМ РАБОТЫ ЗНАТОКА-ПИСАТЕЛЯ:

1. Когда нужно изучить сайт/ресурс:
   → Используй fetch_url для загрузки
   → Проанализируй содержимое
   → Используй save_source для сохранения
   → Используй save_knowledge для ключевых выводов

2. Когда нужно опубликовать:
   → Для аналитической статьи: publish_article
   → Для новости: publish_news
   → Для обзора: publish_review (с оценкой)

3. Когда нужно найти информацию:
   → Используй search_sources для поиска
   → Используй list_researches для списка исследований

4. Когда нужно сделать исследование:
   → start_research → fetch_url (многократно) → save_source → complete_research

ПРАВИЛА:
- Перед fetch_url объясни пользователю, что начинаешь загрузку
- После fetch_url суммируй содержимое
- Сохраняй источники через save_source
- Публикации сохраняй в базу знаний (publish_*)
- Используй эмодзи для наглядности (🌐 📝 📊 💾 🔍 📡)
- Не выдумывай данные — используй только то, что получил из функций
- Ответы давай на русском языке

⚠️ ЗАЩИТА ОТ ЗАЦИКЛИВАНИЯ:
- НЕ повторяй список вариантов, если пользователь уже сделал выбор. Если он написал «Вариант 1» или просто «1» — сразу выполняй выбранное действие, а не показывай список снова.
- Если пользователь не может определиться — предложи конкретный вариант по умолчанию (Вариант 1) и сразу начинай выполнение.
- Ты НЕ должен генерировать нумерованные списки вариантов более 2 раз подряд. Если ты уже показывал список вариантов в предыдущем ответе — не повторяй его, а сразу приступай к выполнению.
- Не задавай уточняющие вопросы, если их можно избежать — сразу действуй.
- Если пользователь вводит «--break», «стоп», «хватит» — это команда принудительного выхода из цикла, и диалог будет сброшен.
- Команда /choose N (например /choose 1) означает, что пользователь выбрал вариант N — сразу выполняй его.
PROMPT;

        $knowledgeSection = $knowledgeContext !== ''
            ? "\n\n{$knowledgeContext}"
            : '';

        return $prompt . $knowledgeSection;
    }
}
