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

        // ── Диалог: DeepSeek + tools (как сейчас TelegramChatService) ──
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

        // Загружаем историю
        $history = $session->getHistoryForAi(20);

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

        // Сохраняем ответ ассистента
        $this->saveAssistantMessage($session, $answer, $result);

        // Отправляем ответ
        return $this->bot->sendMarkdown($chatId, $answer);
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
        $dbToolsInstruction = <<<'DBTOOLS'
Ты — бизнес-аналитик AV8 Capital. У тебя есть доступ к базе данных компании.

Ты можешь использовать следующие инструменты для работы с БД:
1. **query_db(sql)** — выполнить SQL-запрос к базе данных (только SELECT)
2. **get_tables** — показать список доступных таблиц
3. **get_table_schema(table)** — показать структуру таблицы

Когда тебя просят найти данные — используй инструменты.
Отвечай на русском языке, если не указано иное.

Ключевые таблицы:
- `comp` — товары (id, nickname, firma, pay, sklad, web, hit)
- `descript` — описания товаров (pnum, firma, name, description)
- `users` — клиенты (id, name, secondname, fathername, phone, email, city, firma)
- `document` — заказы (id, num, client1, data, summa, type, firma)
- `z_body` — строки заказов (docid, pnum, pcount, pprice, psumma)
- `news` — новости
- `firma` — компании
DBTOOLS;

        $learningInstruction = <<<'LEARN'
ВАЖНО: Если пользователь делится полезной информацией или задаёт вопрос,
на который нет ответа в БД, но ответ может быть полезен другим —
сохрани эту информацию через инструмент save_knowledge.
LEARN;

        $knowledgeSection = $knowledgeContext !== ''
            ? "\n\nКонтекст из базы знаний:\n{$knowledgeContext}"
            : '';

        return $dbToolsInstruction . "\n" . $learningInstruction . $knowledgeSection;
    }
}
