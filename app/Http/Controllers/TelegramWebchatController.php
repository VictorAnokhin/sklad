<?php

namespace App\Http\Controllers;

use App\Services\TelegramWebchatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class TelegramWebchatController extends Controller
{
    public function __construct(
        private readonly TelegramWebchatService $telegramWebchat,
    ) {}

    public function webhook(Request $request, string $secret): JsonResponse
    {
        $expectedSecret = trim((string) config('services.telegram_webchat.webhook_secret'));
        if ($expectedSecret === '' || ! hash_equals($expectedSecret, $secret)) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        try {
            $result = $this->telegramWebchat->handleWebhookUpdate($request->all());
        } catch (Throwable $e) {
            Log::warning('Telegram webchat webhook failed.', [
                'message' => $e->getMessage(),
            ]);

            return response()->json(['message' => 'Webhook processing failed.'], 500);
        }

        return response()->json($result);
    }
}
