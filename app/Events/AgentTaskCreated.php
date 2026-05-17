<?php

namespace App\Events;

use App\Models\AgentTask;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

class AgentTaskCreated implements ShouldBroadcast
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
        return 'agent.task.created';
    }

    public function broadcastWith(): array
    {
        return [
            'uuid' => $this->task->uuid,
            'source_agent' => $this->task->source_agent,
            'target_agent' => $this->task->target_agent,
            'fid' => $this->task->fid,
            'task_type' => $this->task->task_type,
            'status' => $this->task->status,
            'priority' => $this->task->priority,
        ];
    }
}
