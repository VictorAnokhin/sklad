<?php

namespace App\Jobs;

use App\Services\TelegramBotService;
use App\Services\TelegramChatService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class HandleTelegramMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Данные сообщения от Telegram */
    public array $message;

    /** ID проекта (fid), переданный из вебхука */
    public ?int $fid;

    public int $tries = 1;

    public int $timeout = 300;

    public function __construct(array $message, ?int $fid = null)
    {
        $this->message = $message;
        $this->fid = $fid;

        // Если fid передан, добавляем его в сообщение для TelegramChatService
        if ($fid !== null && $fid > 0) {
            $this->message['fid'] = $fid;
        }
    }

    /**
     * Обработать входящее сообщение из Telegram.
     * Выполняется в фоновой очереди, чтобы Telegram не ждал ответа.
     *
     * sendChatAction (печатает...) НЕ вызываем здесь —
     * он уже отправляется внутри TelegramChatService::handleAiDialog()
     * при старте обработки AI-запроса, чтобы избежать дублирования.
     */
    public function handle(
        TelegramChatService $chatService,
        TelegramBotService $bot,
    ): void {
        $chatId = $this->message['chat']['id'] ?? 0;
        $text = trim($this->message['text'] ?? '');

        if (empty($text)) {
            return;
        }

        Log::info('HandleTelegramMessage: processing message', [
            'chat_id' => $chatId,
            'fid' => $this->fid,
            'text_preview' => mb_substr($text, 0, 100),
        ]);

        try {
            $answer = $chatService->handleMessage($this->message);

            if ($answer !== '') {
                $bot->sendMessage($chatId, $answer);
            }
        } catch (Throwable $e) {
            Log::error('HandleTelegramMessage: processing failed', [
                'chat_id' => $chatId,
                'fid' => $this->fid,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            try {
                $bot->sendMessage(
                    $chatId,
                    '⚠️ Произошла внутренняя ошибка. Попробуйте позже или используйте /help.'
                );
            } catch (Throwable) {
                // Игнорируем
            }
        }
    }
}
