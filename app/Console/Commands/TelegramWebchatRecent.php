<?php

namespace App\Console\Commands;

use App\Models\TelegramWebchatMessage;
use Illuminate\Console\Command;

class TelegramWebchatRecent extends Command
{
    protected $signature = 'telegram:webchat-recent {--limit=20 : Number of rows to show}';

    protected $description = 'Show recent Telegram webchat message bindings.';

    public function handle(): int
    {
        $limit = max(1, min(100, (int) $this->option('limit')));

        $rows = TelegramWebchatMessage::query()
            ->with(['session', 'message'])
            ->latest('id')
            ->limit($limit)
            ->get()
            ->map(function (TelegramWebchatMessage $row): array {
                return [
                    'id' => $row->id,
                    'direction' => $row->direction,
                    'site' => $row->site_key,
                    'domain' => $row->site_domain,
                    'session' => $row->session?->session_token,
                    'chat_message_id' => $row->chat_message_id,
                    'role' => $row->message?->role,
                    'content' => mb_substr((string) $row->message?->content, 0, 80),
                    'telegram_chat_id' => $row->telegram_chat_id,
                    'telegram_thread_id' => $row->telegram_thread_id,
                    'telegram_message_id' => $row->telegram_message_id,
                    'reply_to' => $row->telegram_reply_to_message_id,
                    'created_at' => $row->created_at?->toDateTimeString(),
                ];
            })
            ->values()
            ->all();

        $this->line(json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        return self::SUCCESS;
    }
}
