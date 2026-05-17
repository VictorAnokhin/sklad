<?php

namespace App\Services;

use App\Contracts\AiClientInterface;
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

    private readonly AiClientInterface $ai;

    public function __construct(
        private readonly AiClientFactory $aiFactory,
        private readonly TelegramBotService $bot,
        private readonly AnalystService $analyst,
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
            // Если команда не распознана — продолжаем как обычный диалог
        }

        // ── Основной диалог с AI-аналитиком ────────────────────────────────
        return $this->handleAiDialog($chatId, $text, $userId, $username);
    }

    // ── Команды ────────────────────────────────────────────────────────────

    /**
     * Обработать команду. Возвращает ответ или null, если команда не распознана.
     */
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

    /**
     * /start — приветствие и информация.
     */
    private function cmdStart(int|string $chatId, string $username): string
    {
        // Создаём новую сессию для этого чата
        $this->resolveSession($chatId);

        $name = ucfirst(mb_strtolower($username));

        return "👋 Привет, {$name}!\n\n"
            . "Я — AI-аналитик AV8 Capital. 📊\n\n"
            . "Мои возможности:\n"
            . "🔍 *Изучать сайты и протоколы* — отправь URL, я проанализирую\n"
            . "📰 *Собирать новости* — из открытых источников\n"
            . "💾 *Сохранять данные* — в базу знаний проекта (fid=12)\n"
            . "📊 *Анализировать* — DeFi-протоколы, токены, рынки\n"
            . "📋 *Исследовать* — темы по запросу с отчётом\n\n"
            . "📌 *Команды:*\n"
            . "/help — список команд\n"
            . "/new — начать новый диалог\n"
            . "/clear — очистить историю\n\n"
            . "Просто напиши *тему для исследования* или *URL сайта* для анализа!";
    }

    /**
     * /help — список команд.
     */
    private function cmdHelp(int|string $chatId): string
    {
        return "📋 *Доступные команды:*\n\n"
            . "/start — приветствие и запуск\n"
            . "/help — этот список\n"
            . "/new — начать новый диалог\n"
            . "/clear — очистить историю\n\n"
            . "💡 *Примеры запросов:*\n"
            . "• «Изучи протокол Suilend на Sui»\n"
            . "• «Собери информацию о https://example.com»\n"
            . "• «Сделай анализ рынка DeFi за неделю»\n"
            . "• «Какие исследования уже есть?»";
    }

    /**
     * /clear — очистить историю текущего диалога.
     */
    private function cmdClear(int|string $chatId): string
    {
        $session = $this->resolveSession($chatId);

        // Архивируем старую сессию и создаём новую
        $session->update(['status' => 'archived']);
        $this->resolveSession($chatId, true);

        return "🧹 История диалога очищена. Можете задавать новые вопросы!";
    }

    /**
     * /new — начать новый диалог.
     */
    private function cmdNew(int|string $chatId): string
    {
        // Архивируем все активные сессии этого чата
        ChatSession::where('session_token', 'like', self::TELEGRAM_TOKEN_PREFIX . $chatId . '%')
            ->where('status', 'active')
            ->update(['status' => 'archived']);

        // Создаём новую сессию
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
        int|string|null $userId,
        string $username,
    ): string {
        try {
            // Показываем "печатает..."
            $this->bot->sendChatAction($chatId, 'typing');

            // Получаем или создаём сессию для этого чата
            $session = $this->resolveSession($chatId);

            // Сохраняем сообщение пользователя с fid=12
            $this->saveUserMessage($session, $text);

            // Обновляем заголовок сессии
            $session->updateTitle($text);

            // Загружаем историю для AI
            $history = $session->getHistoryForAi(20);

            // Формируем system prompt аналитика
            $instructions = $this->buildAnalystPrompt();

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

            $answer = $result['answer'];

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

    // ── Управление сессиями ────────────────────────────────────────────────

    /**
     * Получить или создать сессию чата для Telegram chat_id.
     */
    private function resolveSession(int|string $chatId, bool $forceNew = false): ChatSession
    {
        $token = self::TELEGRAM_TOKEN_PREFIX . $chatId;

        if (! $forceNew) {
            $session = ChatSession::where('session_token', $token)
                ->where('status', 'active')
                ->first();

            if ($session !== null) {
                return $session;
            }
        }

        // Создаём новую сессию с fid=12 для аналитика
        return ChatSession::create([
            'session_token' => $token,
            'fid' => self::ANALYST_FID,
            'language' => 'ru',
            'page' => 'telegram_analyst',
            'status' => 'active',
        ]);
    }

    // ── Сохранение сообщений ────────────────────────────────────────────────

    private function saveUserMessage(ChatSession $session, string $text): ChatMessage
    {
        return ChatMessage::create([
            'chat_session_id' => $session->id,
            'fid' => self::ANALYST_FID,
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
            'fid' => self::ANALYST_FID,
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

    // ── System prompt аналитика ────────────────────────────────────────────

    /**
     * Сформировать system prompt для AI-аналитика.
     */
    private function buildAnalystPrompt(): string
    {
        return <<<'PROMPT'
ТЫ — AI-АНАЛИТИК AV8 Capital. Твоя основная задача — сбор и анализ данных из открытых источников.

ТВОЙ ID ПРОЕКТА (fid): 12
Все сохраняемые данные привязываются к проекту fid=12.

ТВОИ ВОЗМОЖНОСТИ (через функции):

1. fetch_url(url) — Загрузить содержимое веб-страницы по URL.
   Используй когда пользователь просит изучить сайт, протокол, документацию.
   После загрузки проанализируй содержимое и сохрани результат через save_source.

2. save_source(url, title, summary, content_type) — Сохранить источник в БД.
   Всегда сохраняй полезные источники после анализа.
   content_type: website, news, documentation, protocol, social, api, other.

3. search_sources(query) — Искать по сохранённым источникам.
   Используй когда пользователь спрашивает о ранее изученном.

4. start_research(topic) — Начать новое исследование по теме.
   Создаёт сессию исследования, к которой прикрепляются источники.

5. complete_research(research_id, summary) — Завершить исследование с итоговым отчётом.
   В summary напиши полный анализ: ключевые выводы, метрики, риски.

6. list_researches() — Показать все исследования.

7. save_knowledge(title, content, category) — Сохранить аналитическую заметку в базу знаний.
   Категории: defi, protocol, token, market, news, analysis, strategy, security.

8. get_research_sources(research_id) — Получить все источники исследования.

АЛГОРИТМ РАБОТЫ:

1. Когда пользователь просит изучить что-то:
   → Используй start_research, чтобы создать исследование
   → Используй fetch_url для загрузки страниц
   → После загрузки используй save_source для каждого источника
   → В конце используй complete_research с полным анализом

2. Когда пользователь даёт URL:
   → Используй fetch_url для загрузки
   → Проанализируй содержимое
   → Используй save_source для сохранения
   → Если это часть исследования — привяжи через research_id

3. Когда спрашивают о сохранённых данных:
   → Используй search_sources или list_researches

4. Ценные инсайты и выводы:
   → Используй save_knowledge для сохранения в базу знаний

ПРАВИЛА:
- Перед fetch_url объясни пользователю, что начинаешь загрузку
- После fetch_url кратко суммируй содержимое
- Всегда сохраняй источники через save_source после анализа
- Используй эмодзи для наглядности (🌐 📊 📝 💾 🔍 📡)
- Если сайт не загрузился — предложи альтернативный источник
- Не выдумывай данные — используй только то, что получил из функций
- Ответы давай на русском языке
PROMPT;
    }
}
