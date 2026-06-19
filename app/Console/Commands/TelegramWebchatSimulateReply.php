<?php

namespace App\Console\Commands;

use App\Models\TelegramWebchatMessage;
use App\Services\TelegramWebchatService;
use Illuminate\Console\Command;

class TelegramWebchatSimulateReply extends Command
{
    protected $signature = 'telegram:webchat-simulate-reply
        {--text=Тестовый ответ оператора : Reply text to save}
        {--id= : Source telegram_webchat_messages id}';

    protected $description = 'Simulate an operator Reply for the latest outgoing Telegram webchat message.';

    public function handle(TelegramWebchatService $telegramWebchat): int
    {
        $sourceId = $this->option('id');
        $query = TelegramWebchatMessage::query()
            ->where('direction', 'web_to_telegram')
            ->latest('id');

        if ($sourceId !== null && $sourceId !== '') {
            $query->where('id', (int) $sourceId);
        }

        $source = $query->first();
        if ($source === null) {
            $this->error('No outgoing web_to_telegram row found.');

            return self::FAILURE;
        }

        $replyMessageId = (int) $source->telegram_message_id + 1000000;
        $update = [
            'update_id' => time(),
            'message' => [
                'message_id' => $replyMessageId,
                'message_thread_id' => $source->telegram_thread_id !== null ? (int) $source->telegram_thread_id : null,
                'from' => [
                    'id' => 100001,
                    'is_bot' => false,
                    'first_name' => 'Test',
                    'last_name' => 'Operator',
                ],
                'chat' => [
                    'id' => (int) $source->telegram_chat_id,
                    'type' => 'supergroup',
                ],
                'date' => time(),
                'reply_to_message' => [
                    'message_id' => (int) $source->telegram_message_id,
                    'chat' => [
                        'id' => (int) $source->telegram_chat_id,
                        'type' => 'supergroup',
                    ],
                ],
                'text' => (string) $this->option('text'),
            ],
        ];

        $result = $telegramWebchat->handleWebhookUpdate($update);
        $this->line(json_encode([
            'source_id' => $source->id,
            'source_session' => $source->session?->session_token,
            'result' => $result,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        return ! empty($result['chat_message_id']) ? self::SUCCESS : self::FAILURE;
    }
}
