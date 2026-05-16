<?php

namespace App\Services;

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

    public function __construct(
        private readonly DeepSeekClient $deepseek,
        private readonly TelegramBotService $bot,
    ) {}

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

        // ── Основной диалог с AI ──────────────────────────────────────────
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
     /start — приветствие и информация.
     */
    private function cmdStart(int|string $chatId, string $username): string
    {
        // Создаём новую сессию для этого чата
        $this->resolveSession($chatId);

        $name = ucfirst(mb_strtolower($username));

        return "👋 Привет, {$name}!\n\n"
            . "Я — AI-финансовый помощник AV8 Capital.\n"
            . "Задавай любые вопросы о финансах, инвестициях, криптовалютах, "
            . "токенах Sui, нашем фонде и DeFi.\n\n"
            . "📌 *Доступные команды:*\n"
            . "/help — список команд\n"
            . "/new — начать новый диалог (сбросить историю)\n"
            . "/clear — очистить историю текущего диалога\n\n"
            . "Чем могу помочь?";
    }

    /**
     /help — список команд.
     */
    private function cmdHelp(int|string $chatId): string
    {
        return "📋 *Доступные команды:*\n\n"
            . "/start — приветствие и запуск\n"
            . "/help — этот список\n"
            . "/new — начать новый диалог (сбросить историю)\n"
            . "/clear — очистить историю текущего диалога\n\n"
            . "Просто напишите свой вопрос — и я отвечу как AI-финансовый помощник!";
    }

    /**
     /clear — очистить историю текущего диалога (сообщения остаются, но AI их не видит).
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
     /new — начать новый диалог.
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
     * Обработать текстовое сообщение через DeepSeek.
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

            // Сохраняем сообщение пользователя
            $this->saveUserMessage($session, $text);

            // Обновляем заголовок сессии
            $session->updateTitle($text);

            // Загружаем историю для AI
            $history = $session->getHistoryForAi(20);

            // Формируем system prompt с контекстом AV8 Capital
            $instructions = $this->buildSystemPrompt();

            // Отправляем запрос к DeepSeek
            $result = $this->deepseek->chat($instructions, $history);

            $answer = $result['answer'];

            // Сохраняем ответ AI
            $this->saveAssistantMessage($session, $answer, $result);

            return $answer;

        } catch (RuntimeException $e) {
            Log::error('Telegram AI dialog failed.', [
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
            ]);

            // Ошибка DeepSeek — вежливо сообщаем пользователю
            if (str_contains($e->getMessage(), 'DeepSeek')) {
                return '⚠️ Извините, AI-сервис временно недоступен. Попробуйте позже.';
            }

            return '⚠️ Произошла ошибка при обработке запроса. Попробуйте ещё раз.';
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
     *
     * Используем chat_id как часть session_token с префиксом 'tg_'.
     * Это позволяет переиспользовать существующую модель ChatSession.
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

        // Создаём новую сессию
        return ChatSession::create([
            'session_token' => $token,
            'language' => 'ru',
            'page' => 'telegram',
            'status' => 'active',
        ]);
    }

    // ── Сохранение сообщений ────────────────────────────────────────────────

    private function saveUserMessage(ChatSession $session, string $text): ChatMessage
    {
        return ChatMessage::create([
            'chat_session_id' => $session->id,
            'fid' => null,
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
            'fid' => null,
            'firma' => null,
            'role' => 'assistant',
            'content' => $answer,
            'metadata' => [
                'model' => $result['model'] ?? null,
                'usage' => $result['usage'] ?? null,
                'provider' => 'deepseek',
                'source' => 'telegram',
            ],
        ]);
    }

    // ── System prompt ──────────────────────────────────────────────────────

    /**
     * Сформировать system prompt для AI-финансового помощника.
     */
    private function buildSystemPrompt(): string
    {
        return <<<'PROMPT'
Ты — AI-финансовый помощник AV8 Capital. Отвечай на русском языке.

Твоя миссия: помогать пользователям разбираться в финансах, инвестициях, 
криптовалютах, технологии Sui, DeFi-продуктах AV8 Capital и управлении капиталом.

Темы, в которых ты помогаешь:
- Основы финансовой грамотности и инвестиций
- Технология Sui (Move, смарт-контракты, zkLogin)
- DeFi: AMM, пулы ликвидности, стекинг, фарминг
- AV8 Capital: структура фонда, токены, RWA, корзина активов, mint/redeem
- Криптовалюты: аналитика, тренды, безопасность
- Управление портфелем, стратегии, риск-менеджмент

Правила:
- Отвечай понятно, структурированно, с примерами где уместно
- Не давай персональных финансовых рекомендаций
- Не запрашивай seed-фразы, private keys или секреты кошельков
- Если не знаешь точного ответа — честно скажи об этом
- Если вопрос требует onchain-данных (баланс, позиции), объясни, как их проверить в интерфейсе
- Будь дружелюбным и helpful

Используй эмодзи для улучшения читаемости, но не перебарщивай.
PROMPT;
    }
}
