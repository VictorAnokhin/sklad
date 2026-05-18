<?php

namespace App\Services;

use App\Contracts\AiClientInterface;
use App\Models\AgentTask;
use App\Models\ChatSession;
use App\Models\ChatMessage;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class TelegramChatService
{
    /**
     * Префикс для session_token, чтобы отличать Telegram-сессии от веб-чата.
     */
    private const TELEGRAM_TOKEN_PREFIX = 'tg_';

    /**
     * FID проекта аналитика (AV8 Capital research).
     */
    private const ANALYST_FID = 12;

    /** Максимальное количество повторяющихся ответов AI до принудительного прерывания */
    private const MAX_CONSECUTIVE_SIMILAR_ANSWERS = 2;

    /** Минимальная длина ответа для проверки на повторение */
    private const MIN_ANSWER_LENGTH_FOR_COMPARISON = 30;

    private readonly AiClientInterface $ai;

    public function __construct(
        private readonly AiClientFactory $aiFactory,
        private readonly TelegramBotService $bot,
        private readonly AnalystService $analyst,
        private readonly AiKnowledgeService $knowledgeService,
        private readonly AgentOrchestrator $orchestrator,
    ) {
        $this->ai = $this->aiFactory->make('telegram');
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
        $this->resolveSession($chatId);

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
        $session = $this->resolveSession($chatId);

        $session->update(['status' => 'archived']);
        $this->resolveSession($chatId, true);

        return "🧹 История диалога очищена. Можете задавать новые вопросы!";
    }

    private function cmdNew(int|string $chatId): string
    {
        ChatSession::where('session_token', 'like', self::TELEGRAM_TOKEN_PREFIX . $chatId . '%')
            ->where('status', 'active')
            ->update(['status' => 'archived']);

        $this->resolveSession($chatId, true);

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
            $this->bot->sendChatAction($chatId, 'typing');

            $fid = $this->resolveFid($message);

            // Получаем или создаём сессию
            $session = $this->resolveSession($chatId);
            if ((int) $session->fid !== $fid) {
                $session->update(['fid' => $fid]);
                $session->refresh();
            }

            // ════════════════════════════════════════════════════════════════
            //  ТРАНСФОРМАЦИЯ КОМАНД ВЫБОРА
            // ════════════════════════════════════════════════════════════════
            // /choose 1, /go 2 → "Я выбираю вариант 1"
            if (preg_match('/^\/(choose|go)\s*(\d+)/i', $text, $m)) {
                $text = "Я выбираю вариант {$m[2]}. Выполни это действие и покажи результат.";
            }

            // ════════════════════════════════════════════════════════════════
            //  РУЧНОЙ ВЫХОД ИЗ ЦИКЛА
            // ════════════════════════════════════════════════════════════════
            if ($this->isBreakKeyword($text)) {
                return $this->breakTheLoop($chatId, $session);
            }

            // Сохраняем сообщение пользователя
            $this->saveUserMessage($session, $text);

            // Обновляем заголовок сессии
            $session->updateTitle($text);

            // ════════════════════════════════════════════════════════════════
            //  ДЕТЕКЦИЯ ЦИКЛИЧНОСТИ
            // ════════════════════════════════════════════════════════════════
            if ($this->detectLoop($session, $text)) {
                return $this->breakTheLoop($chatId, $session);
            }

            // Загружаем контекст из Базы Знаний (инструкции + записи)
            $knowledgeContext = $this->loadKnowledgeContext();

            // ════════════════════════════════════════════════════════════════
            //  ДЕЛЕГИРОВАНИЕ TelegramAgent
            // ════════════════════════════════════════════════════════════════
            if ($this->shouldDelegateToAgent($text)) {
                $taskType = $this->detectTaskType($text);

                $inputData = [
                    'query' => $text,
                    'chat_id' => $chatId,
                    'language' => 'ru',
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

                return "⏳ Задача принята. Я делегировал её TelegramAgent (ID: {$task->uuid}). "
                    . "Он изучит ресурс, сохранит данные и предоставит отчёт. Я сообщу, когда результат будет готов.";
            }

            // Загружаем историю для AI
            $history = $session->getHistoryForAi(20);

            // Формируем system prompt эксперта-аналитика
            $instructions = $this->buildAnalystPrompt($knowledgeContext);

            // Получаем инструменты аналитика
            $tools = $this->analyst->getTools();

            // Создаём executor для вызова инструментов
            $toolExecutor = function (string $name, array $arguments): string {
                return $this->analyst->executeTool($name, $arguments);
            };

            // Отправляем запрос с поддержкой function calling
            $result = $this->ai->chatWithTools(
                $instructions,
                $history,
                $tools,
                $toolExecutor,
                ['max_tool_iterations' => 10],
            );

            $answer = $result['answer'] ?? '⚠️ Не удалось получить ответ. Попробуйте переформулировать вопрос.';

            // ════════════════════════════════════════════════════════════════
            //  ПРОВЕРКА: не повторяет ли AI ответ
            // ════════════════════════════════════════════════════════════════
            if ($this->isAnswerRepeatOfLast($session, $answer)) {
                return $this->breakTheLoop($chatId, $session);
            }

            // Сохраняем ответ AI
            $this->saveAssistantMessage($session, $answer, $result);

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

    // ── Loop Detection ─────────────────────────────────────────────────────

    /**
     * Детекция циклического поведения на основе истории сообщений.
     */
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
            Log::warning('TelegramChatService: detected AI loop', [
                'session_id' => $session->id,
                'similar_count' => $similarCount,
            ]);
            return true;
        }

        return false;
    }

    /**
     * Проверить, повторяет ли новый ответ последнее сообщение ассистента.
     */
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

    /**
     * Сравнить два текста на схожесть.
     *
     * Определяет повторяющиеся ответы AI — как текстуально похожие,
     * так и структурно (например, повторяющиеся списки вариантов выбора).
     */
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
            Log::debug('TelegramChatService: detected choice list repetition.', [
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

    /**
     * Принудительно прервать цикл.
     */
    private function breakTheLoop(int|string $chatId, ChatSession $session): string
    {
        $session->messages()
            ->where('role', 'assistant')
            ->orderByDesc('created_at')
            ->limit(self::MAX_CONSECUTIVE_SIMILAR_ANSWERS)
            ->delete();

        $message = "⚠️ *Кажется, я зашёл в тупик.* Давайте начнём диалог заново.\n\n"
            . "Напишите ваш вопрос конкретно, например:\n"
            . "• «Изучи протокол Suilend»\n"
            . "• «Собери данные о https://example.com»\n"
            . "• «Сделай анализ DeFi рынка»\n\n"
            . "Или используйте /clear чтобы очистить историю, /new для нового диалога.";

        $this->saveAssistantMessage($session, $message, [
            'model' => 'loop_breaker',
            'usage' => [],
            'provider' => $this->ai->getProviderName(),
        ]);

        return $message;
    }

    // ── Делегирование TelegramAgent ────────────────────────────────────────

    /**
     * Определить, нужно ли делегировать задачу TelegramAgent.
     */
    private function shouldDelegateToAgent(string $text): bool
    {
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
    private function detectTaskType(string $text): string
    {
        return match (true) {
            preg_match('/изучи сайт|проанализируй сайт|просмотри сайт|спарси/i', $text) => 'study_website',
            preg_match('/сохрани.*баз[уе].*знан/i', $text) => 'save_to_knowledge',
            preg_match('/массов|все товар|все проект/i', $text) => 'mass_analysis',
            default => 'complex_question',
        };
    }

    // ── Управление сессиями ────────────────────────────────────────────────

    private function resolveSession(int|string $chatId, bool $forceNew = false): ChatSession
    {
        $token = self::TELEGRAM_TOKEN_PREFIX . $chatId;

        if (! $forceNew) {
            $session = ChatSession::where('session_token', $token)
                ->where('status', 'active')
                ->first();

            if ($session !== null) {
                $defaultFid = $this->defaultFid();
                if ($defaultFid !== self::ANALYST_FID && (int) $session->fid === self::ANALYST_FID) {
                    $session->update(['fid' => $defaultFid]);
                }

                return $session;
            }
        }

        // При forceNew=true (cmdClear / cmdNew) старый session_token уже занят archived-записью,
        // поэтому генерируем уникальный токен с суффиксом.
        $newToken = $forceNew
            ? $token . '_' . now()->timestamp
            : $token;

        return ChatSession::create([
            'session_token' => $newToken,
            'fid' => $this->defaultFid(),
            'language' => 'ru',
            'page' => 'telegram_analyst',
            'status' => 'active',
        ]);
    }

    /**
     * Resolve Telegram project context. Default is still 12 for the analyst bot,
     * but production can point TelegramChat at the same project knowledge base as
     * the web chat via AI_TELEGRAM_FID.
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
            $session = ChatSession::where('session_token', self::TELEGRAM_TOKEN_PREFIX . $chatId)
                ->where('status', 'active')
                ->first();

            if ($session && (int) $session->fid > 0 && (int) $session->fid !== self::ANALYST_FID) {
                return (int) $session->fid;
            }
        }

        return $this->defaultFid();
    }

    private function defaultFid(): int
    {
        $configured = (int) config('ai.channels.telegram.fid', self::ANALYST_FID);

        return $configured > 0 ? $configured : self::ANALYST_FID;
    }

    /**
     * Загрузить контекст из Базы Знаний без привязки к fid.
     * Telegram-бот читает информацию всех проектов.
     */
    private function loadKnowledgeContext(): string
    {
        try {
            return $this->knowledgeService->getContext(null);
        } catch (Throwable $e) {
            Log::warning('TelegramChatService: failed to load knowledge context.', [
                'error' => $e->getMessage(),
            ]);

            return '';
        }
    }

    // ── Сохранение сообщений ────────────────────────────────────────────────

    private function saveUserMessage(ChatSession $session, string $text): ChatMessage
    {
        return ChatMessage::create([
            'chat_session_id' => $session->id,
            'fid' => $session->fid ?: $this->defaultFid(),
            'firma' => null,
            'role' => 'user',
            'content' => $text,
        ]);
    }

    /**
     * @param  array{answer: string, model: string, usage: array<string, mixed>}  $result
     */
    private function saveAssistantMessage(ChatSession $session, string $answer, array $result): ChatMessage
    {
        return ChatMessage::create([
            'chat_session_id' => $session->id,
            'fid' => $session->fid ?: $this->defaultFid(),
            'firma' => null,
            'role' => 'assistant',
            'content' => $answer,
            'metadata' => [
                'model' => $result['model'] ?? null,
                'usage' => $result['usage'] ?? null,
                'provider' => $this->ai->getProviderName(),
                'source' => 'telegram_analyst',
                'tools_used' => true,
            ],
        ]);
    }

    // ── System prompt эксперта-аналитика ───────────────────────────────────

    /**
     * Сформировать system prompt для AI-эксперта.
     */
    private function buildAnalystPrompt(string $knowledgeContext = ''): string
    {
        $prompt = <<<'PROMPT'
ТЫ — AI-ЭКСПЕРТ AV8 Capital. Твоя специализация — глубокая аналитика, исследования и накопление базы знаний.

ТВОЙ ID ПРОЕКТА (fid): 12
Все сохраняемые данные привязываются к проекту fid=12.

🧠 КОНФИГУРАЦИЯ:
Твои правила работы и инструкции задаются администратором через панель /settings → База знаний → категория «telegram_instruction».
Эти инструкции переданы ниже в разделе «🧠 ИНСТРУКЦИИ». Следуй им как основной конфигурации — они имеют наивысший приоритет.

📚 БАЗА ЗНАНИЙ ПРОЕКТА:
Записи из базы знаний содержат проверенную информацию. Используй их для ответов на вопросы.

🤖 ДЕЛЕГИРОВАНИЕ TELEGRAM AGENT:
У тебя есть помощник — TelegramAgent (знаток-писатель).
- Если нужно изучить сайт, протокол, ресурс — делегируй задачу через фразу "⏳ Задача принята. Я делегировал её TelegramAgent."
- TelegramAgent умеет: парсить сайты, сохранять источники, публиковать статьи/новости/обзоры
- После выполнения TelegramAgent вернёт отчёт, и ты сможешь его проанализировать
- Если данных недостаточно — запроси у TelegramAgent дополнительное изучение

ТВОИ ИНСТРУМЕНТЫ (через функции):

1. fetch_url(url) — Загрузить содержимое веб-страницы по URL.
   Используй для быстрого ознакомления с ресурсом. Для глубокого изучения делегируй TelegramAgent.

2. save_source(url, title, summary, content_type) — Сохранить источник в БД аналитика.
   content_type: website, news, documentation, protocol, social, api, other.

3. search_sources(query) — Искать по сохранённым источникам.

4. start_research(topic) — Начать новое исследование по теме.

5. complete_research(research_id, summary) — Завершить исследование с итоговым отчётом.

6. list_researches() — Показать все исследования.

7. save_knowledge(title, content, category) — Сохранить аналитическую заметку в базу знаний.
   Категории: defi, protocol, token, market, news, analysis, strategy, security.
   Сохраняется в analyst_sources и AiKnowledgeBase.

8. get_research_sources(research_id) — Получить все источники исследования.

АЛГОРИТМ РАБОТЫ ЭКСПЕРТА:

1. Анализ и исследования:
   → Используй search_sources чтобы найти уже собранную информацию
   → Используй fetch_url для быстрой проверки
   → Для глубокого изучения — делегируй TelegramAgent
   → Используй start_research/complete_research для структурирования

2. База знаний:
   → Используй save_knowledge для сохранения ценных инсайтов
   → Используй search_sources для поиска в ранее сохранённом

3. Взаимодействие с TelegramAgent:
   → Если задача требует детального изучения сайта — делегируй
   → После получения отчёта — проанализируй и сохрани выводы
   → При необходимости — запроси дополнительное изучение

ПРАВИЛА ЭКСПЕРТА:
- Ты НЕ работаешь напрямую с товарами, заказами и клиентами — это зона FrontendAgent/BackendAgent
- Твоя задача — анализ, исследования, накопление базы знаний
- Используй эмодзи для наглядности (📊 🔍 💾 📡 🌐 📝)
- Всегда сохраняй источники через save_source после анализа
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
