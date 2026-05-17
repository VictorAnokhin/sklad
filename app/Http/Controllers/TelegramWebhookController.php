<?php

namespace App\Http\Controllers;

use App\Jobs\HandleTelegramMessage;
use App\Services\TelegramBotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TelegramWebhookController extends Controller
{
    public function __construct(
        private readonly TelegramBotService $bot,
    ) {}

    /**
     * Главный обработчик вебхука от Telegram.
     *
     * Telegram отправляет POST-запросы с JSON-телом.
     * Мы проверяем секретный ключ в URL, ВСЕГДА возвращаем 200 OK
     * и диспатчим обработку сообщения в фоновую очередь (Job).
     *
     * Это решает проблему "Read timeout expired", которая возникала,
     * когда AI-обработка занимала больше времени, чем таймаут Telegram (~30 сек).
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

        Log::info('Telegram webhook: incoming message, dispatching to queue.', [
            'chat_id' => $chatId,
            'text_preview' => mb_substr($text, 0, 100),
        ]);

        // ── 5. Диспатчим обработку в очередь и сразу возвращаем 200 OK ────
        // Это критически важно: Telegram ожидает ответ в течение ~30 секунд.
        // AI-обработка (DeepSeek с function calling) может занимать >60 секунд,
        // поэтому всю логику переносим в фоновый Job.
        HandleTelegramMessage::dispatch($message);

        return response()->json(['ok' => true]);
    }
}
