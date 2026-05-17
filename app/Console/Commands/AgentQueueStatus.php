<?php

namespace App\Console\Commands;

use App\Models\AgentTask;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AgentQueueStatus extends Command
{
    protected $signature = 'agent:queue-status {--limit=10 : Number of recent rows to show}';

    protected $description = 'Show Telegram agent queue diagnostics without using Tinker.';

    public function handle(): int
    {
        $limit = max(1, min(50, (int) $this->option('limit')));

        $this->info('Queue');
        $this->line('connection: ' . config('queue.default'));

        $this->table(
            ['table', 'exists', 'count'],
            [
                $this->tableStatus('jobs'),
                $this->tableStatus('failed_jobs'),
                $this->tableStatus('agent_tasks'),
                $this->tableStatus('agent_communications'),
            ],
        );

        if (Schema::hasTable('jobs')) {
            $jobs = DB::table('jobs')
                ->selectRaw('queue, count(*) as count')
                ->groupBy('queue')
                ->orderBy('queue')
                ->get()
                ->map(fn ($row) => [(string) $row->queue, (int) $row->count])
                ->all();

            $this->info('Pending jobs by queue');
            $jobs === []
                ? $this->line('none')
                : $this->table(['queue', 'count'], $jobs);
        }

        if (Schema::hasTable('agent_tasks')) {
            $this->info('Agent tasks by status');
            $statuses = AgentTask::query()
                ->selectRaw('status, count(*) as count')
                ->groupBy('status')
                ->orderBy('status')
                ->get()
                ->map(fn (AgentTask $task) => [(string) $task->status, (int) $task->count])
                ->all();

            $statuses === []
                ? $this->line('none')
                : $this->table(['status', 'count'], $statuses);

            $this->info("Latest {$limit} agent tasks");
            $tasks = AgentTask::query()
                ->latest('id')
                ->limit($limit)
                ->get()
                ->map(fn (AgentTask $task) => [
                    $task->id,
                    $task->uuid,
                    $task->source_agent,
                    $task->target_agent,
                    $task->task_type,
                    $task->status,
                    optional($task->created_at)->toDateTimeString(),
                    optional($task->started_at)->toDateTimeString(),
                    optional($task->completed_at)->toDateTimeString(),
                    $task->error_message ? mb_substr($task->error_message, 0, 120) : '',
                ])
                ->all();

            $tasks === []
                ? $this->line('none')
                : $this->table(
                    ['id', 'uuid', 'source', 'target', 'type', 'status', 'created', 'started', 'completed', 'error'],
                    $tasks,
                );
        }

        if (Schema::hasTable('agent_communications')) {
            $this->info("Latest {$limit} agent communications");
            $communications = DB::table('agent_communications')
                ->latest('id')
                ->limit($limit)
                ->get()
                ->map(fn ($communication) => [
                    $communication->id,
                    $communication->task_id ?? '',
                    $communication->source_agent,
                    $communication->target_agent,
                    $communication->message_type,
                    $communication->status,
                    $communication->created_at ?? '',
                    mb_substr((string) $communication->content, 0, 180),
                ])
                ->all();

            $communications === []
                ? $this->line('none')
                : $this->table(
                    ['id', 'task_id', 'source', 'target', 'type', 'status', 'created', 'content'],
                    $communications,
                );
        }

        if (Schema::hasTable('failed_jobs')) {
            $this->info("Latest {$limit} failed jobs");
            $failed = DB::table('failed_jobs')
                ->latest('id')
                ->limit($limit)
                ->get()
                ->map(fn ($job) => [
                    $job->id,
                    $job->queue ?? '',
                    $job->failed_at ?? '',
                    mb_substr((string) ($job->exception ?? ''), 0, 180),
                ])
                ->all();

            $failed === []
                ? $this->line('none')
                : $this->table(['id', 'queue', 'failed_at', 'exception'], $failed);
        }

        return self::SUCCESS;
    }

    private function tableStatus(string $table): array
    {
        if (! Schema::hasTable($table)) {
            return [$table, 'no', '-'];
        }

        return [$table, 'yes', DB::table($table)->count()];
    }
}
