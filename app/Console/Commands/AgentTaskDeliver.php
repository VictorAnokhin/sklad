<?php

namespace App\Console\Commands;

use App\Models\AgentTask;
use App\Models\ChatMessage;
use App\Models\ChatSession;
use Illuminate\Console\Command;

class AgentTaskDeliver extends Command
{
    protected $signature = 'agent:task-deliver {uuid : Agent task UUID}';

    protected $description = 'Deliver an already completed agent task result to its source channel.';

    public function handle(): int
    {
        $uuid = (string) $this->argument('uuid');
        $task = AgentTask::where('uuid', $uuid)->first();

        if (! $task) {
            $this->error("Task not found: {$uuid}");
            return self::FAILURE;
        }

        $message = $this->resultMessage($task->output_data ?? []);
        if ($message === null) {
            $this->error('Task has no deliverable output_data.message or output_data.answer.');
            return self::FAILURE;
        }

        $delivered = false;

        if ($task->session_token) {
            $session = ChatSession::resolveByToken($task->session_token);
            if ($session) {
                ChatMessage::create([
                    'chat_session_id' => $session->id,
                    'fid' => $task->fid,
                    'firma' => $session->firma,
                    'role' => 'assistant',
                    'content' => $message,
                    'metadata' => [
                        'source' => 'agent_task_manual_delivery',
                        'source_agent' => $task->target_agent,
                        'target_agent' => $task->source_agent,
                        'task_uuid' => $task->uuid,
                        'task_type' => $task->task_type,
                    ],
                ]);
                $this->info("Saved to chat session {$task->session_token}.");
                $delivered = true;
            } else {
                $this->warn("Chat session not found: {$task->session_token}");
            }
        }

        if (! $delivered) {
            $this->error('No source channel was available for delivery.');
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function resultMessage(array $result): ?string
    {
        $message = $result['message'] ?? $result['answer'] ?? null;

        if (! is_string($message)) {
            return null;
        }

        $message = trim($message);

        return $message !== '' ? $message : null;
    }
}
