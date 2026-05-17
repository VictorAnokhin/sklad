<?php

namespace App\Console\Commands;

use App\Models\AgentCommunication;
use App\Models\AgentTask;
use Illuminate\Console\Command;

class AgentTaskShow extends Command
{
    protected $signature = 'agent:task-show {uuid : Agent task UUID}';

    protected $description = 'Show one agent task with output data and related communications.';

    public function handle(): int
    {
        $uuid = (string) $this->argument('uuid');
        $task = AgentTask::where('uuid', $uuid)->first();

        if (! $task) {
            $this->error("Task not found: {$uuid}");
            return self::FAILURE;
        }

        $this->info('Task');
        $this->table(
            ['field', 'value'],
            [
                ['id', $task->id],
                ['uuid', $task->uuid],
                ['source_agent', $task->source_agent],
                ['target_agent', $task->target_agent],
                ['fid', $task->fid],
                ['session_token', $task->session_token ?? ''],
                ['task_type', $task->task_type],
                ['status', $task->status],
                ['priority', $task->priority],
                ['created_at', optional($task->created_at)->toDateTimeString()],
                ['started_at', optional($task->started_at)->toDateTimeString()],
                ['completed_at', optional($task->completed_at)->toDateTimeString()],
                ['error_message', $task->error_message ?? ''],
            ],
        );

        $this->info('Input data');
        $this->line(json_encode($task->input_data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) ?: '{}');

        $this->info('Output data');
        $this->line(json_encode($task->output_data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) ?: '{}');

        $communications = AgentCommunication::where('task_id', $task->id)
            ->orderBy('id')
            ->get()
            ->map(fn (AgentCommunication $communication) => [
                $communication->id,
                $communication->source_agent,
                $communication->target_agent,
                $communication->message_type,
                $communication->status,
                optional($communication->created_at)->toDateTimeString(),
                $communication->content,
                json_encode($communication->metadata, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) ?: '{}',
            ])
            ->all();

        $this->info('Communications');
        $communications === []
            ? $this->line('none')
            : $this->table(
                ['id', 'source', 'target', 'type', 'status', 'created_at', 'content', 'metadata'],
                $communications,
            );

        return self::SUCCESS;
    }
}
