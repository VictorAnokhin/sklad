<?php

namespace App\Events;

use App\Models\AgentTask;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

class AgentTaskStatusChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    public AgentTask $task;

    public function __construct(AgentTask $task)
    {
        $this->task = $task;
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('agent.' . $this->task->fid),
            new Channel('agent.' . $this->task->fid . '.' . $this->task->target_agent),
        ];
    }

    public function broadcastAs(): string
    {
        return 'agent.task.status_changed';
    }

    public function broadcastWith(): array
    {
        return [
            'uuid' => $this->task->uuid,
            'status' => $this->task->status,
            'error_message' => $this->task->error_message,
            'started_at' => $this->task->started_at?->toISOString(),
            'completed_at' => $this->task->completed_at?->toISOString(),
        ];
    }
}
