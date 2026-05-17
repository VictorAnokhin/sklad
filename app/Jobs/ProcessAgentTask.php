<?php

namespace App\Jobs;

use App\Agents\BackendAgent;
use App\Agents\FrontendAgent;
use App\Agents\TelegramAgent;
use App\Models\AgentTask;
use App\Services\AgentOrchestrator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessAgentTask implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $taskId;
    public int $tries = 3;
    public int $backoff = 5;

    public function __construct(int $taskId)
    {
        $this->taskId = $taskId;
    }

    public function handle(AgentOrchestrator $orchestrator): void
    {
        $task = AgentTask::find($this->taskId);

        if (!$task) {
            Log::warning("AgentTask #{$this->taskId} not found.");
            return;
        }

        // Отмечаем как processing
        $orchestrator->updateTaskStatus($task->id, 'processing');

        try {
            // Определяем целевой агент
            $agent = $this->resolveAgent($task->target_agent);

            if (!$agent) {
                throw new \InvalidArgumentException("Unknown target agent: {$task->target_agent}");
            }

            // Выполняем задачу
            $result = $agent->executeTask($task);

            // Обновляем задачу со статусом completed
            $task = $orchestrator->updateTaskStatus($task->id, 'completed', $result);

            // Отправляем результат source-агенту
            $orchestrator->sendTaskResult($task, $result);

        } catch (\Throwable $e) {
            Log::error("AgentTask #{$this->taskId} failed", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $orchestrator->updateTaskStatus(
                $task->id,
                'failed',
                null,
                $e->getMessage()
            );

            // Создаём коммуникацию об ошибке
            $orchestrator->sendCommunication(
                sourceAgent: $task->target_agent,
                targetAgent: $task->source_agent,
                fid: $task->fid,
                content: "Ошибка выполнения задачи: {$e->getMessage()}",
                messageType: 'error',
                taskId: $task->id,
                metadata: ['task_uuid' => $task->uuid],
            );

            throw $e;
        }
    }

    private function resolveAgent(string $agentName): BackendAgent|TelegramAgent|FrontendAgent|null
    {
        return match ($agentName) {
            'backend' => app(BackendAgent::class),
            'telegram' => app(TelegramAgent::class),
            'frontend' => app(FrontendAgent::class),
            default => null,
        };
    }
}
