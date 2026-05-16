<?php

namespace App\Http\Controllers;

use App\Services\TelegramBotService;
use App\Services\TelegramChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class TelegramWebhookController extends Controller
{
    public function __construct(
        private readonly TelegramBotService $bot,
        private readonly TelegramChatService $chatService,
    ) {}

    /**
     * Главный обработчик вебхука от Telegram.
     *
     * Telegram отправляет POST-запросы с JSON-телом.
     * Мы проверяем секретный ключ в URL (query param ?secret=...),
     * обрабатываем сообщение и возвращаем 200 OK.
     *
     * @param  Request  $request
     * @param  string|null  $secret  Секретный ключ из URL
     * @return JsonResponse
     */
    public function __invoke(Request $request, ?string $secret = null): JsonResponse
    {
        // ── 1. Проверка секретного ключа ──────────────────────────────────
        $expected = config('services.telegram.webhook_secret', '');

        if ($expected === '') {
            Log::warning('Telegram webhook: WEBHOOK_SECRET is not configured.');
            return response()->json(['error' => 'Webhook not configured'], 503);
        }

        if ($secret !== $expected) {
            Log::warning('Telegram webhook: invalid secret.', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // ── 2. Проверяем, что бот настроен ────────────────────────────────
        if (! $this->bot->isConfigured()) {
            Log::error('Telegram webhook: TELEGRAM_BOT_TOKEN is not configured.');
            return response()->json(['error' => 'Bot not configured'], 503);
        }

        // ── 3. Получаем данные от Telegram ─────────────────────────────────
        $content = $request->getContent();
        $update = json_decode($content, true);

        if (! is_array($update)) {
            Log::warning('Telegram webhook: invalid JSON payload.');
            return response()->json(['error' => 'Invalid payload'], 400);
        }

        // ── 4. Извлекаем сообщение ────────────────────────────────────────
        $message = $update['message'] ?? [];

        if (empty($message) || empty($message['text'])) {
            // Telegram может присылать другие типы обновлений (edited_message,
            // callback_query, inline_query и т.д.) — игнорируем их.
            return response()->json(['ok' => true]);
        }

        $chatId = $message['chat']['id'];
        $text = trim($message['text']);

        Log::info('Telegram webhook: incoming message.', [
            'chat_id' => $chatId,
            'text_preview' => mb_substr($text, 0, 100),
        ]);

        // ── 5. Показываем "печатает..." (в фоне) ─────────────────────────
        try {
            $this->bot->sendChatAction($chatId, 'typing');
        } catch (Throwable $e) {
            // Не критично, если не удалось отправить действие
            Log::debug('Telegram webhook: sendChatAction failed.', [
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
            ]);
        }

        // ── 6. Обрабатываем сообщение ──────────────────────────────────────
        try {
            $answer = $this->chatService->handleMessage($message);

            if ($answer !== '') {
                $this->bot->sendMessage($chatId, $answer);
            }
        } catch (Throwable $e) {
            Log::error('Telegram webhook: message handling failed.', [
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Пытаемся отправить пользователю сообщение об ошибке
            try {
                $this->bot->sendMessage(
                    $chatId,
                    '⚠️ Произошла внутренняя ошибка. Попробуйте позже или используйте /help.'
                );
            } catch (Throwable) {
                // Игнорируем — если и это не удалось, уже ничего не сделаешь
            }
        }

        // ── 7. Всегда возвращаем 200 OK — Telegram ждёт подтверждения ─────
        return response()->json(['ok' => true]);
    }
}
