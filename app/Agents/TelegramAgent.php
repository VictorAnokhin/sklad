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
use App\Traits\ChatLoopDetectionTrait;
use App\Traits\ChatSessionManagerTrait;
use Illuminate\Support\Facades\Log;
use Throwable;

class TelegramAgent
{
    use ChatLoopDetectionTrait;
    use ChatSessionManagerTrait;

    const ANALYST_FID = 1;

    /** @var array<string, int> Счётчик вызовов tools для rate limiting */
    private array $toolCallCount = [];

    /** @var int Максимальное количество вызовов tools за один диалог */
    private const MAX_TOOL_CALLS = 25;

    private AiClientInterface $ai;

    public function __construct(
        private TelegramBotService $bot,
        private AiClientFactory $aiFactory,
        private AiKnowledgeService $knowledgeService,
        private AgentOrchestrator $orchestrator,
        private AnalystService $analyst,
    ) {
        $this->ai = $this->aiFactory->make('telegram');
        // Настройки трейта ChatSessionManagerTrait
        $this->telegramTokenPrefix = 'tg_agent_';
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
        $fid = $message['fid'] ?? self::ANALYST_FID;

        if (empty($text)) {
            return '';
        }

        if (str_starts_with($text, '/')) {
            return $this->handleCommand($chatId, $text, $username);
        }

        return $this->handleAiDialog($chatId, $text, $message, $fid);
    }

    /**
     * Выполнить задачу, делегированную от другого агента.
     * Сохраняет данные под fid из задачи.
     */
    public function executeTask(AgentTask $task): array
    {
        $this->toolCallCount = [];

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
            default                           => $this->sendPlainAndReturn($chatId, "Неизвестная команда. Используйте /help"),
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

        $this->sendMarkdownWithFallback($chatId, $welcome);

        return $welcome;
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

        $this->sendMarkdownWithFallback($chatId, $help);

        return $help;
    }

    private function cmdClear(int|string $chatId): string
    {
        ChatSession::where('session_token', 'like', 'tg_agent_' . $chatId . '%')
            ->where('status', 'active')
            ->update(['status' => 'archived']);

        $this->resolveSession($chatId, true);

        return $this->sendPlainAndReturn($chatId, "🧹 История диалога очищена. Задавайте новый вопрос!");
    }

    private function cmdNew(int|string $chatId): string
    {
        ChatSession::where('session_token', 'like', 'tg_agent_' . $chatId . '%')
            ->where('status', 'active')
            ->update(['status' => 'archived']);

        $this->resolveSession($chatId, true);

        return $this->sendPlainAndReturn($chatId, "🆕 Начинаем новый диалог. Задавайте вопрос!");
    }

    // ── Обработка задач от TelegramChatService ────────────────────

    /**
     * Изучить сайт: парсит, сохраняет источник, возвращает отчёт.
     * Сохраняет данные под fid из задачи.
     */
    private function executeStudyWebsite(AgentTask $task): array
    {
        $url = $task->input_data['url'] ?? '';
        $query = $task->input_data['query'] ?? '';
        $fid = $task->fid;

        try {
            $scraper = app(WebScraperService::class);
            $result = $scraper->fetchUrl($url);

            if (isset($result['error'])) {
                return $this->taskResult($task, '❌ Не удалось загрузить сайт: ' . $result['error']);
            }

            $content = $result['content'] ?? '';
            $title = $result['title'] ?? $url;

            // Сохраняем источник с fid из задачи
            $sourceResult = $this->analyst->executeTool('save_source', [
                'url' => $url,
                'title' => $title,
                'summary' => mb_substr($content, 0, 500),
                'content_type' => 'website',
                'fid' => $fid,
            ]);

            // Сохраняем в базу знаний с fid из задачи
            $kbResult = $this->analyst->executeTool('save_knowledge', [
                'title' => "Анализ сайта: {$title}",
                'content' => "URL: {$url}\n\n{$content}",
                'category' => 'analysis',
                'fid' => $fid,
            ]);

            $summary = mb_substr($content, 0, 2000);

            $report = "📄 *Отчёт по изучению сайта*\n\n"
                . "**URL:** {$url}\n"
                . "**Заголовок:** {$title}\n"
                . "**Проект (fid):** {$fid}\n"
                . "**Содержимое:**\n{$summary}\n\n"
                . "✅ Источник сохранён\n"
                . "✅ Информация добавлена в базу знаний";

            return $this->taskResult($task, $report);

        } catch (Throwable $e) {
            Log::error('TelegramAgent: study_website failed.', [
                'url' => $url,
                'fid' => $fid,
                'error' => $e->getMessage(),
            ]);

            return $this->taskResult($task, '❌ Ошибка при изучении сайта: ' . $e->getMessage());
        }
    }

    /**
     * Сохранить данные в базу знаний. Сохраняет под fid из задачи.
     */
    private function executeSaveToKnowledge(AgentTask $task): array
    {
        $query = $task->input_data['query'] ?? '';
        $fid = $task->fid;

        $result = $this->analyst->executeTool('save_knowledge', [
            'title' => 'Запись из TelegramAgent',
            'content' => $query,
            'category' => 'analysis',
            'fid' => $fid,
        ]);

        return $this->taskResult($task, "✅ Данные сохранены в базу знаний (fid={$fid}).\n{$result}");
    }

    /**
     * Массовый анализ. Передаёт fid дальше.
     */
    private function executeMassAnalysis(AgentTask $task): array
    {
        $fid = $task->fid;

        $backendTask = $this->delegateToBackend(
            fid: $fid,
            taskType: 'mass_analysis',
            inputData: $task->input_data,
            sessionToken: $task->session_token,
        );

        return $this->taskResult($task,
            "⏳ Задача массового анализа передана BackendAgent (ID: {$backendTask->uuid}). "
            . "Проект fid={$fid}. Результат будет доступен позже."
        );
    }

    /**
     * Сложный вопрос — анализируем через AI.
     * Использует fid из задачи.
     */
    private function executeComplexQuestion(AgentTask $task): array
    {
        $query = $task->input_data['query'] ?? '';
        $chatId = $task->input_data['chat_id'] ?? 0;
        $fid = $task->fid;

        try {
            // Создаём временную сессию для AI с fid из задачи
            $session = $this->resolveSession($chatId, false, $fid);
            $this->saveUserMessage($session, $query);

            // Загружаем knowledge для контекста с привязкой к fid
            $knowledgeContext = $this->knowledgeService->getContext(null);

            $history = $session->getHistoryForAi(20);

            $instructions = $this->buildAnalystPrompt($knowledgeContext, $fid);
            $tools = $this->analyst->getTools();

            $toolExecutor = function (string $name, array $arguments) use ($fid): string {
                $this->checkToolRateLimit();
                // Передаём fid из задачи в каждый вызов tool
                $arguments['fid'] = $fid;
                return $this->analyst->executeTool($name, $arguments);
            };

            try {
                $result = $this->ai->chatWithTools(
                    $instructions,
                    $history,
                    $tools,
                    $toolExecutor,
                    ['max_tool_iterations' => 10],
                );
            } catch (\RuntimeException $e) {
                Log::warning('TelegramAgent tools request failed, retrying without tools.', [
                    'task_uuid' => $task->uuid,
                    'fid' => $fid,
                    'error' => $e->getMessage(),
                ]);

                $result = $this->ai->chat(
                    $instructions,
                    $history,
                    ['max_tokens' => config('ai.channels.telegram.max_tokens', 2000)],
                );
            }

            $answer = $result['answer'] ?? '⚠️ Не удалось получить ответ.';
            $this->saveAssistantMessage($session, $answer, $result);

            if ($this->shouldAskHuman($answer, $query)) {
                return $this->requestHumanAnswer($task, $query);
            }

            return $this->taskResult($task, $answer);

        } catch (Throwable $e) {
            Log::error('TelegramAgent: complex_question failed.', [
                'fid' => $fid,
                'error' => $e->getMessage(),
            ]);
            return $this->taskResult($task, '❌ Ошибка обработки запроса: ' . $e->getMessage());
        }
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

    /**
     * Сформировать результат задачи.
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

    private function handleAiDialog(int|string $chatId, string $text, array $message, int $fid): string
    {
        $this->sendTyping($chatId);

        // Определяем fid из контекста, если не задан явно
        if ($fid === self::ANALYST_FID) {
            $detectedFid = $this->detectFidFromContext($text);
            if ($detectedFid !== null) {
                $fid = $detectedFid;
            }
        }

        $session = $this->resolveSession($chatId, false, $fid);

        // Трансформация команд выбора
        if (preg_match('/^\/(choose|go)\s*(\d+)/i', $text, $m)) {
            $text = "Я выбираю вариант {$m[2]}. Выполни это действие и покажи результат.";
        }

        // Ручной выход из цикла
        if ($this->isBreakKeyword($text)) {
            return $this->breakTheLoop($chatId, $session);
        }

        $this->saveUserMessage($session, $text);

        // Loop detection
        if ($this->detectLoop($session, $text)) {
            return $this->breakTheLoop($chatId, $session);
        }

        // Загружаем knowledge base с привязкой к fid
        $knowledgeContext = $this->knowledgeService->getContext(null);

        // Загружаем историю
        $history = $session->getHistoryForAi(20);

        // Формируем system prompt с динамическим fid
        $systemPrompt = $this->buildAnalystPrompt($knowledgeContext, $fid);

        // Получаем инструменты аналитика
        $tools = $this->analyst->getTools();

        // Executor с передачей fid и rate limiting
        $toolExecutor = function (string $name, array $arguments) use ($fid): string {
            $this->checkToolRateLimit();
            $arguments['fid'] = $fid;
            return $this->analyst->executeTool($name, $arguments);
        };

        // Вызываем AI с function calling. Если tools недоступны, fallback на обычный chat.
        try {
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
        } catch (\RuntimeException $e) {
            Log::warning('TelegramAgent direct tools request failed, retrying without tools.', [
                'chat_id' => $chatId,
                'fid' => $fid,
                'error' => $e->getMessage(),
            ]);

            $result = $this->ai->chat(
                $systemPrompt,
                $history,
                [
                    'temperature' => 0.3,
                    'max_tokens' => config('ai.channels.telegram.max_tokens', 2000),
                ],
            );
        }

        $answer = $result['answer'] ?? '⚠️ Не удалось получить ответ. Попробуйте переформулировать вопрос.';

        // Проверка на повторение ответа
        if ($this->isAnswerRepeatOfLast($session, $answer)) {
            return $this->breakTheLoop($chatId, $session);
        }

        // Сохраняем ответ
        $this->saveAssistantMessage($session, $answer, $result);

        $this->sendMarkdownWithFallback($chatId, $answer);

        return $answer;
    }

    private function sendPlainAndReturn(int|string $chatId, string $message): string
    {
        $this->bot->sendMessage($chatId, $message);

        return $message;
    }

    private function sendMarkdownWithFallback(int|string $chatId, string $message): void
    {
        try {
            $this->bot->sendMarkdown($chatId, $message);
        } catch (Throwable $markdownError) {
            Log::warning('TelegramAgent: markdown send failed, retrying as plain text.', [
                'chat_id' => $chatId,
                'error' => $markdownError->getMessage(),
            ]);

            $this->bot->sendMessage($chatId, $message);
        }
    }

    private function sendTyping(int|string $chatId): void
    {
        try {
            $this->bot->sendChatAction($chatId, 'typing');
        } catch (Throwable $e) {
            Log::debug('TelegramAgent: sendChatAction skipped.', [
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function shouldAskHuman(string $answer, string $query): bool
    {
        $text = mb_strtolower($answer . "\n" . $query);

        foreach ([
            'не нашел',
            'не нашёл',
            'нет информации',
            'недостаточно информации',
            'не удалось найти',
            'не могу найти',
            'уточните у оператора',
        ] as $marker) {
            if (mb_stripos($text, $marker) !== false) {
                return true;
            }
        }

        return false;
    }

    private function requestHumanAnswer(AgentTask $task, string $question): array
    {
        $operatorChatId = $task->input_data['operator_chat_id']
            ?? config('services.telegram.operator_chat_id', '');

        if (! $operatorChatId) {
            return $this->taskResult(
                $task,
                'Не нашёл быстрый ответ и не смог спросить оператора: TELEGRAM_OPERATOR_CHAT_ID не настроен.'
            );
        }

        $message = "❓ Запрос из веб-чата\n"
            . "Задача: {$task->uuid}\n"
            . "fid: {$task->fid}\n\n"
            . "{$question}\n\n"
            . "Ответьте командой:\n"
            . "/answer {$task->uuid} ваш ответ";

        $this->bot->sendMessage($operatorChatId, $message);

        return [
            'status' => 'waiting_human',
            'message' => 'Я передал вопрос оператору в Telegram. Ответ появится в этом чате, когда оператор ответит.',
            'task_uuid' => $task->uuid,
        ];
    }

    /**
     * Попытаться определить fid из текста запроса.
     */
    private function detectFidFromContext(string $text): ?int
    {
        if (preg_match('/fid[=:\s]*(\d+)/i', $text, $m)) {
            return (int) $m[1];
        }

        return null;
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

    // ── System Prompt ─────────────────────────────────────────────

    private function buildAnalystPrompt(string $knowledgeContext = '', int $currentFid = self::ANALYST_FID): string
    {
        $prompt = <<<PROMPT
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
- Текущий проект (fid): {$currentFid}
- Все сохраняемые данные привязываются к проекту fid={$currentFid}
- Если в запросе явно указан fid — используй его
- Если не указан — попробуй определить из контекста (упоминание проекта, компании)
- По умолчанию используй fid={$currentFid}
- Сохраняй данные под тем fid, которому соответствует информация

ТВОИ ИНСТРУМЕНТЫ:

🌐 ИЗУЧЕНИЕ САЙТОВ:
1. fetch_url(url) — Загрузить содержимое веб-страницы
2. save_source(url, title, summary, content_type, fid) — Сохранить источник в БД (указывай fid={$currentFid})
3. search_sources(query) — Поиск по сохранённым источникам (ищет по всем проектам)

📝 ПУБЛИКАЦИИ:
4. publish_article(title, content, summary, fid) — Опубликовать аналитическую статью (указывай fid={$currentFid})
5. publish_news(title, content, summary, source_url, fid) — Опубликовать новость (указывай fid={$currentFid})
6. publish_review(title, content, summary, rating, fid) — Опубликовать обзор с оценкой (указывай fid={$currentFid})

📊 ИССЛЕДОВАНИЯ:
7. start_research(topic, fid) — Начать исследование (указывай fid={$currentFid})
8. complete_research(research_id, summary) — Завершить с отчётом
9. list_researches() — Список исследований (по всем проектам)
10. get_research_sources(research_id) — Источники исследования

💾 БАЗА ЗНАНИЙ:
11. save_knowledge(title, content, category, fid) — Сохранить заметку в БЗ (указывай fid={$currentFid})

АЛГОРИТМ РАБОТЫ ЗНАТОКА-ПИСАТЕЛЯ:

1. Когда нужно изучить сайт/ресурс:
   → Используй fetch_url для загрузки
   → Проанализируй содержимое
   → Используй save_source для сохранения (с текущим fid)
   → Используй save_knowledge для ключевых выводов (с текущим fid)

2. Когда нужно опубликовать:
   → Для аналитической статьи: publish_article (с текущим fid)
   → Для новости: publish_news (с текущим fid)
   → Для обзора: publish_review (с текущим fid)

3. Когда нужно найти информацию:
   → Используй search_sources для поиска (по всем проектам)
   → Используй list_researches для списка исследований

4. Когда нужно сделать исследование:
   → start_research (с текущим fid) → fetch_url (многократно) → save_source → complete_research

ПРАВИЛА:
- Перед fetch_url объясни пользователю, что начинаешь загрузку
- После fetch_url суммируй содержимое
- Сохраняй источники через save_source с правильным fid
- Публикации сохраняй в базу знаний (publish_*) с правильным fid
- Используй эмодзи для наглядности (🌐 📝 📊 💾 🔍 📡)
- Не выдумывай данные — используй только то, что получил из функций
- Ответы давай на русском языке

⚠️ ЗАЩИТА ОТ ЗАЦИКЛИВАНИЯ:
- НЕ повторяй список вариантов, если пользователь уже сделал выбор.
- Если пользователь не может определиться — предложи конкретный вариант по умолчанию (Вариант 1) и сразу начинай выполнение.
- Ты НЕ должен генерировать нумерованные списки вариантов более 2 раз подряд.
- Не задавай уточняющие вопросы, если их можно избежать — сразу действуй.
- Если пользователь вводит «--break», «стоп», «хватит» — это команда принудительного выхода из цикла.
- Команда /choose N означает, что пользователь выбрал вариант N — сразу выполняй его.
PROMPT;

        $knowledgeSection = $knowledgeContext !== ''
            ? "\n\n{$knowledgeContext}"
            : '';

        return $prompt . $knowledgeSection;
    }
}
