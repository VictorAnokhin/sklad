<?php

namespace App\Events;

use App\Models\AgentCommunication;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

class AgentCommunicationSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    public AgentCommunication $communication;

    public function __construct(AgentCommunication $communication)
    {
        $this->communication = $communication;
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('agent.' . $this->communication->fid),
            new Channel('agent.' . $this->communication->fid . '.' . $this->communication->target_agent),
        ];
    }

    public function broadcastAs(): string
    {
        return 'agent.communication.sent';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->communication->id,
            'source_agent' => $this->communication->source_agent,
            'target_agent' => $this->communication->target_agent,
            'fid' => $this->communication->fid,
            'message_type' => $this->communication->message_type,
            'content' => $this->communication->content,
            'content_preview' => mb_substr($this->communication->content, 0, 200),
            'metadata' => $this->communication->metadata,
            'task_uuid' => $this->communication->task?->uuid,
            'created_at' => $this->communication->created_at->toISOString(),
        ];
    }
}
