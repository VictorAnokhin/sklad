<?php

namespace App\Agents;

use App\Contracts\AiClientInterface;
use App\Models\AgentTask;
use App\Models\ChatSession;
use App\Services\AgentOrchestrator;
use App\Services\AiClientFactory;
use App\Services\TelegramBotService;
use App\Services\AiKnowledgeService;
use App\Models\ChatMessage;
use Illuminate\Support\Facades\Log;

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
     * Сохраняет текущую логику TelegramChatService.
     */
    public function handleMessage(array $message): string
    {
        $chatId = $message['chat']['id'] ?? 0;
        $text = trim($message['text'] ?? '');
        $username = $message['from']['username'] ?? ($message['chat']['username'] ?? '');

        if (empty($text)) {
            return '';
        }

        // ── Команды ──
        if (str_starts_with($text, '/')) {
            return $this->handleCommand($chatId, $text, $username);
        }

        // ── Диалог: DeepSeek + tools ──
        return $this->handleAiDialog($chatId, $text, $message);
    }

    /**
     * Выполнить задачу, делегированную от другого агента (например, от BackendAgent).
     */
    public function executeTask(AgentTask $task): array
    {
        return match ($task->task_type) {
            'send_message' => $this->sendTelegramMessage($task),
            'forward_to_user' => $this->forwardToUser($task),
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
        $welcome = "👋 *Добро пожаловать в AV8 Capital AI Analyst!*\n\n"
            . "Я — бизнес-аналитик. Могу:\n"
            . "📊 Анализировать товары и продажи\n"
            . "📰 Работать с новостями\n"
            . "🔍 Искать информацию в базе знаний\n"
            . "👥 Находить клиентов\n"
            . "📦 Создавать заказы\n"
            . "🌐 Изучать сайты\n\n"
            . "Просто напишите свой вопрос.\n"
            . "Команды: /help — помощь, /clear — очистить историю, /new — новый диалог.";

        return $this->bot->sendMarkdown($chatId, $welcome);
    }

    private function cmdHelp(int|string $chatId): string
    {
        $help = "📖 *Команды:*\n"
            . "/start — приветствие\n"
            . "/help — эта справка\n"
            . "/clear — очистить историю диалога\n"
            . "/new — начать новый диалог\n\n"
            . "💡 *Примеры вопросов:*\n"
            . "• «Покажи последние товары»\n"
            . "• «Найди клиента по телефону +380501234567»\n"
            . "• «Создай заказ для клиента 123»\n"
            . "• «Изучи сайт example.com»\n"
            . "• «Какой баланс у клиента 123?»";

        return $this->bot->sendMarkdown($chatId, $help);
    }

    private function cmdClear(int|string $chatId): string
    {
        $session = $this->resolveSession($chatId);
        if ($session) {
            $session->messages()->delete();
        }

        return $this->bot->sendMessage($chatId, "🧹 История диалога очищена.");
    }

    private function cmdNew(int|string $chatId): string
    {
        $this->resolveSession($chatId, forceNew: true);

        return $this->bot->sendMessage($chatId, "🆕 Начинаем новый диалог. Задавайте вопрос!");
    }

    private function handleAiDialog(int|string $chatId, string $text, array $message): string
    {
        // Показываем "печатает..."
        $this->bot->sendChatAction($chatId, 'typing');

        // Определяем fid
        $fid = $message['fid'] ?? self::ANALYST_FID;

        // Создаём/получаем сессию
        $session = $this->resolveSession($chatId);

        // Сохраняем сообщение пользователя
        $this->saveUserMessage($session, $text);

        // ════════════════════════════════════════════════════════════════
        //  ДЕТЕКЦИЯ ЦИКЛИЧНОСТИ: проверяем, не зациклился ли AI
        // ════════════════════════════════════════════════════════════════
        if ($this->detectLoop($session, $text)) {
            return $this->breakTheLoop($chatId, $session);
        }

        // Загружаем knowledge base
        $knowledgeContext = $this->knowledgeService->getContext($fid);

        // Определяем, нужно ли делегировать BackendAgent
        if ($this->shouldDelegateToBackend($text)) {
            $task = $this->delegateToBackend(
                fid: $fid,
                taskType: $this->detectTaskType($text),
                inputData: [
                    'query' => $text,
                    'chat_id' => $chatId,
                    'language' => 'ru',
                ],
                sessionToken: $session->session_token,
            );

            return $this->bot->sendMarkdown($chatId,
                "⏳ Задача принята. Я делегировал её BackendAgent (ID: {$task->uuid}). "
                . "Я сообщу, когда результат будет готов."
            );
        }

        // Загружаем историю
        $history = $session->getHistoryForAi(20);

        // Формируем system prompt с инструментами БД
        $systemPrompt = $this->buildAnalystPrompt($knowledgeContext);

        // Вызываем AI с поддержкой function calling
        $result = $this->ai->chat(
            instructions: $systemPrompt,
            messages: $history,
            options: [
                'temperature' => 0.3,
                'fid' => $fid,
            ],
        );

        $answer = $result['answer'] ?? '⚠️ Не удалось получить ответ. Попробуйте переформулировать вопрос.';

        // ════════════════════════════════════════════════════════════════
        //  ПРОВЕРКА: не вернул ли AI такой же ответ, как в прошлый раз
        // ════════════════════════════════════════════════════════════════
        if ($this->isAnswerRepeatOfLast($session, $answer)) {
            // Ответ повторяется — не обращаемся к AI снова, а прерываем цикл
            return $this->breakTheLoop($chatId, $session);
        }

        // Сохраняем ответ ассистента
        $this->saveAssistantMessage($session, $answer, $result);

        // Отправляем ответ
        return $this->bot->sendMarkdown($chatId, $answer);
    }

    /**
     * Детекция циклического поведения на основе истории сообщений.
     *
     * Анализирует последние N сообщений и определяет, не зациклился ли AI
     * на повторении одних и тех же вопросов или утверждений.
     */
    private function detectLoop(ChatSession $session, string $currentUserText): bool
    {
        // Получаем последние 6 сообщений ассистента
        $recentAssistantMessages = $session->messages()
            ->where('role', 'assistant')
            ->orderByDesc('created_at')
            ->limit(self::MAX_CONSECUTIVE_SIMILAR_ANSWERS + 1)
            ->get()
            ->pluck('content')
            ->toArray();

        // Если меньше 2 сообщений — нет смысла проверять
        if (count($recentAssistantMessages) < 2) {
            return false;
        }

        // Проверяем, не повторяются ли последние сообщения ассистента
        $similarCount = 0;
        for ($i = 0; $i < count($recentAssistantMessages) - 1; $i++) {
            if ($this->areTextsSimilar($recentAssistantMessages[$i], $recentAssistantMessages[$i + 1])) {
                $similarCount++;
            }
        }

        // Если все последовательные ответы ассистента похожи друг на друга — цикл
        if ($similarCount >= self::MAX_CONSECUTIVE_SIMILAR_ANSWERS) {
            Log::warning('TelegramAgent: detected AI loop', [
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
     * Использует несколько эвристик: длина, ключевые слова, процент совпадения.
     */
    private function areTextsSimilar(string $text1, string $text2): bool
    {
        $t1 = trim($text1);
        $t2 = trim($text2);

        // Если оба пустые — считаем повторением
        if ($t1 === '' && $t2 === '') {
            return true;
        }

        // Если один пустой, другой нет — не повтор
        if ($t1 === '' || $t2 === '') {
            return false;
        }

        // Если тексты идентичны — 100% повторение
        if ($t1 === $t2) {
            return true;
        }

        // Если тексты слишком короткие — сравниваем посимвольно
        if (mb_strlen($t1) < self::MIN_ANSWER_LENGTH_FOR_COMPARISON || mb_strlen($t2) < self::MIN_ANSWER_LENGTH_FOR_COMPARISON) {
            $similarity = similar_text($t1, $t2, $percent);
            return $percent > 80;
        }

        // Для длинных текстов: извлекаем ключевые фразы и сравниваем
        $similarity = similar_text($t1, $t2, $percent);
        if ($percent > 85) {
            return true;
        }

        // Дополнительная проверка: если оба текста содержат одинаковые вопросительные конструкции
        $questionWords = ['Что именно', 'Хотите', 'уточнить', 'Запустить', 'выполнить', '?:'];
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

        // Если оба текста — вопросы с переспросом, и они похожи более чем на 60% — считаем циклом
        if ($t1HasQuestion && $t2HasQuestion && $percent > 60) {
            return true;
        }

        return false;
    }

    /**
     * Принудительно прервать цикл: отправить пользователю сообщение о проблеме.
     */
    private function breakTheLoop(int|string $chatId, ChatSession $session): string
    {
        // Не сохраняем циклический ответ — очищаем последнее сообщение ассистента
        // и отправляем финальное предложение начать заново
        $session->messages()
            ->where('role', 'assistant')
            ->orderByDesc('created_at')
            ->limit(self::MAX_CONSECUTIVE_SIMILAR_ANSWERS)
            ->delete();

        $message = "⚠️ *Кажется, я зашёл в тупик.* Давайте начнём диалог заново.\n\n"
            . "Напишите ваш вопрос конкретно, например:\n"
            . "• «Покажи список товаров»\n"
            . "• «Найди клиента по номеру телефона»\n"
            . "• «Какой баланс у клиента?»\n"
            . "• «Создай заказ»\n\n"
            . "Или используйте /clear чтобы очистить историю, /new для нового диалога.";

        $this->saveAssistantMessage($session, $message, [
            'model' => 'loop_breaker',
            'usage' => [],
            'provider' => $this->ai->getProviderName(),
        ]);

        return $this->bot->sendMarkdown($chatId, $message);
    }

    /**
     * Определить, нужно ли делегировать задачу BackendAgent.
     */
    private function shouldDelegateToBackend(string $text): bool
    {
        $delegateKeywords = [
            'изучи сайт', 'проанализируй сайт', 'сохрани в базу знаний',
            'массовый анализ', 'создай клиента', 'добавь клиента',
            'создай заказ', 'оформи заказ',
            'изучи', 'спарси',
        ];

        foreach ($delegateKeywords as $keyword) {
            if (mb_stripos($text, $keyword) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Определить тип задачи для BackendAgent.
     */
    private function detectTaskType(string $text): string
    {
        return match (true) {
            preg_match('/изучи сайт|проанализируй сайт|спарси/i', $text) => 'study_website',
            preg_match('/сохрани.*баз[уе].*знан/i', $text) => 'save_to_knowledge',
            preg_match('/массов|все товар|все проект/i', $text) => 'mass_analysis',
            preg_match('/создай клиент|добавь клиент|новый клиент/i', $text) => 'create_client',
            preg_match('/создай заказ|оформи заказ|новый заказ/i', $text) => 'create_order',
            preg_match('/найди клиент|поиск клиент/i', $text) => 'find_client',
            preg_match('/баланс|долг/i', $text) => 'get_client_balance',
            preg_match('/заказ.*номер|найди заказ/i', $text) => 'find_order',
            default => 'complex_question',
        };
    }

    private function resolveSession(int|string $chatId, bool $forceNew = false): ChatSession
    {
        $token = 'tg_' . $chatId;

        if (!$forceNew) {
            $session = ChatSession::resolveByToken($token);
            if ($session) {
                return $session;
            }
        }

        return ChatSession::createSession([
            'session_token' => $token,
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

    /**
     * Отправить сообщение в Telegram (задача от другого агента).
     */
    private function sendTelegramMessage(AgentTask $task): array
    {
        $chatId = $task->input_data['chat_id'];
        $message = $task->input_data['message'];

        $this->bot->sendMarkdown($chatId, $message);

        return ['sent' => true, 'chat_id' => $chatId];
    }

    /**
     * Переслать сообщение пользователю Telegram.
     */
    private function forwardToUser(AgentTask $task): array
    {
        $chatId = $task->input_data['chat_id'];
        $text = $task->input_data['text'] ?? $task->input_data['message'] ?? '';

        $this->bot->sendMessage($chatId, $text);

        return ['sent' => true, 'chat_id' => $chatId];
    }

    private function buildAnalystPrompt(string $knowledgeContext = ''): string
    {
        $prompt = <<<'PROMPT'
Ты — AI-аналитик AV8 Capital. У тебя есть доступ к базе данных компании и к базе знаний проекта.

📚 БАЗА ЗНАНИЙ (настраивается в /settings):
Записи из базы знаний добавляются администратором через панель настроек.
Они содержат проверенную информацию о проекте, инструкции, ответы на частые вопросы.
Если контекст из базы знаний передан ниже — используй его в первую очередь для ответа.
Каждая запись в базе знаний может быть привязана к определённым инструментам (tool_keys).
Это значит, что данный контекст актуален только при работе с указанными инструментами.

🔧 ИНСТРУМЕНТЫ ВЗАИМОДЕЙСТВИЯ (настраиваются в /settings):
В панели настроек (/settings) администратор может создавать кастомные инструменты (function calling).
Эти инструменты описывают JSON-схемы функций, которые AI может вызывать для взаимодействия с окружением.
Если тебе переданы инструменты — используй их для выполнения задач пользователя.
Каждый инструмент имеет:
- key — уникальное имя функции
- name — человекочитаемое название
- description — описание, когда и зачем вызывать этот инструмент
- schema — JSON-схема с параметрами

🗄️ РАБОТА С БАЗОЙ ДАННЫХ:
Ты можешь использовать следующие встроенные инструменты для работы с БД:
1. query_db(sql) — выполнить SQL-запрос (только SELECT)
2. get_tables — показать список доступных таблиц
3. get_table_schema(table) — показать структуру таблицы

Ключевые таблицы:
- comp — товары (id, nickname, firma, pay, sklad, web, hit)
- descript — описания товаров (pnum, firma, name, description)
- users — клиенты (id, name, secondname, fathername, phone, email, city, firma)
- document — заказы (id, num, client1, data, summa, type, firma)
- z_body — строки заказов (docid, pnum, pcount, pprice, psumma)
- news — новости
- firma — компании

ПРАВИЛА:
- Отвечай на русском языке, если не указано иное.
- Если в базе знаний есть информация по вопросу — используй её в первую очередь.
- Не выдумывай данные — используй только то, что получил из функций и базы знаний.
- Если пользователь задаёт вопрос, на который можно ответить сразу — отвечай сразу, не задавай уточняющих вопросов без необходимости.
- НЕ повторяй один и тот же вопрос или ответ несколько раз. Если ты уже спрашивал уточнение — запомни это и не спрашивай то же самое снова.

ИСПОЛЬЗОВАНИЕ ИНСТРУМЕНТОВ БД:
- Ты можешь и должен использовать query_db, get_tables, get_table_schema для ответа на вопросы пользователя о данных.
- Если пользователь просит "показать", "найти", "вывести", "список", "сколько", "какой" — это прямое указание использовать инструменты БД.
- Пример: пользователь пишет "покажи товары" → ты вызываешь query_db("SELECT * FROM comp LIMIT 10").
- Пример: пользователь пишет "какая структура таблицы comp" → ты вызываешь get_table_schema("comp").
- Пример: пользователь пишет "найди клиента Иванова" → ты вызываешь query_db с LIKE-поиском.
- НЕ нужно спрашивать подтверждения перед выполнением запроса, если запрос пользователя очевиден.

СОХРАНЕНИЕ ЗНАНИЙ:
- Если пользователь делится полезной информацией, на которую нет ответа в БД — предложи сохранить через save_knowledge.
PROMPT;

        $knowledgeSection = $knowledgeContext !== ''
            ? "\n\n📄 КОНТЕКСТ ИЗ БАЗЫ ЗНАНИЙ ПРОЕКТА:\n{$knowledgeContext}"
            : '';

        return $prompt . $knowledgeSection;
    }
}
