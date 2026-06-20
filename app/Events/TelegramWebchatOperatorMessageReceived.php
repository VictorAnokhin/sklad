<?php

namespace App\Events;

use App\Models\ChatMessage;
use App\Models\ChatSession;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

class TelegramWebchatOperatorMessageReceived implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(
        public ChatSession $session,
        public ChatMessage $message,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('webchat.session.'.$this->session->session_token),
        ];
    }

    public function broadcastAs(): string
    {
        return 'webchat.operator.message';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'session_token' => $this->session->session_token,
            'message' => [
                'id' => $this->message->id,
                'role' => $this->message->role,
                'content' => $this->message->content,
                'fid' => $this->message->fid,
                'firma' => $this->message->firma,
                'metadata' => $this->message->metadata,
                'created_at' => $this->message->created_at?->toISOString(),
            ],
        ];
    }
}
