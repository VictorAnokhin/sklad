<?php

namespace App\Services;

use App\Events\AgentCommunicationSent;
use App\Events\AgentTaskCreated;
use App\Models\AgentCommunication;
use App\Models\AgentTask;
use App\Jobs\ProcessAgentTask;
use Illuminate\Support\Collection;

class AgentOrchestrator
{
    /**
     * Создать задачу для целевого агента.
     */
    public function createTask(
        string $sourceAgent,
        string $targetAgent,
        int $fid,
        string $taskType,
        array $inputData,
        ?string $sessionToken = null,
        int $priority = 0,
        bool $dispatchJob = true,
    ): AgentTask {
        $task = AgentTask::create([
            'source_agent' => $sourceAgent,
            'target_agent' => $targetAgent,
            'fid' => $fid,
            'session_token' => $sessionToken,
            'task_type' => $taskType,
            'input_data' => $inputData,
            'priority' => $priority,
            'status' => 'pending',
        ]);

        // Создаём запись в коммуникациях
        $this->sendCommunication(
            sourceAgent: $sourceAgent,
            targetAgent: $targetAgent,
            fid: $fid,
            content: json_encode([
                'task_type' => $taskType,
                'summary' => $this->summarizeInput($inputData),
            ]),
            messageType: 'task_request',
            taskId: $task->id,
            metadata: ['task_uuid' => $task->uuid],
        );

        // WebSocket-уведомление
        event(new AgentTaskCreated($task));

        // Диспатчим задачу в очередь (асинхронно)
        if ($dispatchJob) {
            ProcessAgentTask::dispatch($task->id);
        }

        return $task;
    }

    /**
     * Получить статус задачи по UUID.
     */
    public function getTaskStatus(string $uuid): ?AgentTask
    {
        return AgentTask::where('uuid', $uuid)->first();
    }

    /**
     * Получить ожидающие задачи для агента.
     */
    public function getPendingTasks(string $agentName, ?string $status = 'pending'): Collection
    {
        $query = AgentTask::forAgent($agentName);

        if ($status) {
            $query->where('status', $status);
        }

        return $query->orderByDesc('priority')
            ->orderBy('created_at')
            ->get();
    }

    /**
     * Отправить сообщение между агентами.
     */
    public function sendCommunication(
        string $sourceAgent,
        string $targetAgent,
        int $fid,
        string $content,
        string $messageType = 'text',
        ?int $taskId = null,
        array $metadata = [],
    ): AgentCommunication {
        $communication = AgentCommunication::create([
            'source_agent' => $sourceAgent,
            'target_agent' => $targetAgent,
            'fid' => $fid,
            'task_id' => $taskId,
            'message_type' => $messageType,
            'content' => $content,
            'metadata' => $metadata,
            'status' => 'sent',
        ]);

        event(new AgentCommunicationSent($communication));

        return $communication;
    }

    /**
     * Получить сообщения для агента.
     */
    public function getCommunications(string $agentName, int $fid, int $limit = 50): Collection
    {
        return AgentCommunication::forAgent($agentName)
            ->forFid($fid)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Обновить статус задачи.
     */
    public function updateTaskStatus(int $taskId, string $status, ?array $outputData = null, ?string $errorMessage = null): AgentTask
    {
        $task = AgentTask::findOrFail($taskId);

        $updateData = ['status' => $status];

        if ($status === 'processing') {
            $updateData['started_at'] = now();
        }

        if ($status === 'completed' || $status === 'failed') {
            $updateData['completed_at'] = now();
        }

        if ($outputData !== null) {
            $updateData['output_data'] = $outputData;
        }

        if ($errorMessage !== null) {
            $updateData['error_message'] = $errorMessage;
        }

        $task->update($updateData);

        // Триггерим событие изменения статуса
        if (in_array($status, ['completed', 'failed'], true)) {
            event(new \App\Events\AgentTaskCompleted($task));
        } else {
            event(new \App\Events\AgentTaskStatusChanged($task));
        }

        return $task;
    }

    /**
     * Создать коммуникацию с результатом задачи.
     */
    public function sendTaskResult(AgentTask $task, array $outputData): AgentCommunication
    {
        $task->refresh();

        return $this->sendCommunication(
            sourceAgent: $task->target_agent,
            targetAgent: $task->source_agent,
            fid: $task->fid,
            content: $outputData['message'] ?? $outputData['answer'] ?? json_encode($outputData),
            messageType: 'task_result',
            taskId: $task->id,
            metadata: [
                'task_uuid' => $task->uuid,
                'task_type' => $task->task_type,
                'status' => $task->status,
            ],
        );
    }

    private function summarizeInput(array $inputData): string
    {
        return $inputData['query'] ?? $inputData['question'] ?? $inputData['message'] ?? json_encode($inputData);
    }
}
