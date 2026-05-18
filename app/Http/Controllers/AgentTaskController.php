<?php

namespace App\Http\Controllers;

use App\Models\AgentTask;
use App\Services\AgentOrchestrator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class AgentTaskController extends Controller
{
    public function __construct(
        private readonly AgentOrchestrator $orchestrator,
    ) {}

    /**
     * Создать новую задачу для агента.
     * POST /api/agent/tasks
     */
    public function store(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'source_agent'  => ['required', 'string', 'max:50', 'in:backend,telegram,frontend,system,telegram_expert'],
            'target_agent'  => ['required', 'string', 'max:50', 'in:backend,telegram,frontend'],
            'fid'           => ['required', 'integer', 'min:1'],
            'task_type'     => ['required', 'string', 'max:50'],
            'input_data'    => ['required', 'array'],
            'session_token' => ['nullable', 'string', 'max:100'],
            'priority'      => ['nullable', 'integer', 'min:0', 'max:9'],
        ]);

        try {
            $task = $this->orchestrator->createTask(
                sourceAgent:  $payload['source_agent'],
                targetAgent:  $payload['target_agent'],
                fid:          (int) $payload['fid'],
                taskType:     $payload['task_type'],
                inputData:    $payload['input_data'],
                sessionToken: $payload['session_token'] ?? null,
                priority:     (int) ($payload['priority'] ?? 0),
            );

            return response()->json([
                'task' => [
                    'uuid'         => $task->uuid,
                    'source_agent' => $task->source_agent,
                    'target_agent' => $task->target_agent,
                    'task_type'    => $task->task_type,
                    'status'       => $task->status,
                    'priority'     => $task->priority,
                    'fid'          => $task->fid,
                    'created_at'   => $task->created_at?->toIso8601String(),
                ],
            ], 201);
        } catch (Throwable $e) {
            Log::error('AgentTaskController:store failed', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Failed to create agent task: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Получить статус задачи по UUID.
     * GET /api/agent/tasks/{uuid}
     */
    public function show(string $uuid): JsonResponse
    {
        $task = $this->orchestrator->getTaskStatus($uuid);

        if ($task === null) {
            return response()->json(['message' => 'Task not found.'], 404);
        }

        return response()->json([
            'task' => [
                'uuid'          => $task->uuid,
                'source_agent'  => $task->source_agent,
                'target_agent'  => $task->target_agent,
                'fid'           => $task->fid,
                'session_token' => $task->session_token,
                'task_type'     => $task->task_type,
                'input_data'    => $task->input_data,
                'output_data'   => $task->output_data,
                'status'        => $task->status,
                'priority'      => $task->priority,
                'error_message' => $task->error_message,
                'created_at'    => $task->created_at?->toIso8601String(),
                'started_at'    => $task->started_at?->toIso8601String(),
                'completed_at'  => $task->completed_at?->toIso8601String(),
            ],
        ]);
    }

    /**
     * Список задач для агента.
     * GET /api/agent/tasks?agent=backend&status=pending&fid=1
     */
    public function index(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'agent'  => ['required', 'string', 'max:50'],
            'status' => ['nullable', 'string', 'max:20'],
            'fid'    => ['nullable', 'integer', 'min:1'],
        ]);

        $query = AgentTask::query();

        if ($agentName = $payload['agent']) {
            $query->where('target_agent', $agentName);
        }

        if ($status = $payload['status'] ?? null) {
            $query->where('status', $status);
        }

        if ($fid = $payload['fid'] ?? null) {
            $query->where('fid', (int) $fid);
        }

        $tasks = $query->orderByDesc('priority')
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        return response()->json([
            'tasks' => $tasks->map(fn (AgentTask $t) => [
                'uuid'          => $t->uuid,
                'source_agent'  => $t->source_agent,
                'target_agent'  => $t->target_agent,
                'task_type'     => $t->task_type,
                'input_data'    => $t->input_data,
                'output_data'   => $t->output_data,
                'status'        => $t->status,
                'priority'      => $t->priority,
                'error_message' => $t->error_message,
                'created_at'    => $t->created_at?->toIso8601String(),
                'completed_at'  => $t->completed_at?->toIso8601String(),
            ]),
        ]);
    }

    /**
     * Обновить статус задачи (используется внешними сервисами).
     * PATCH /api/agent/tasks/{uuid}/status
     */
    public function updateStatus(Request $request, string $uuid): JsonResponse
    {
        $payload = $request->validate([
            'status'        => ['required', 'string', 'in:pending,processing,waiting_human,completed,failed'],
            'output_data'   => ['nullable', 'array'],
            'error_message' => ['nullable', 'string', 'max:2000'],
        ]);

        $task = AgentTask::where('uuid', $uuid)->first();

        if ($task === null) {
            return response()->json(['message' => 'Task not found.'], 404);
        }

        try {
            $this->orchestrator->updateTaskStatus(
                taskId:       $task->id,
                status:       $payload['status'],
                outputData:   $payload['output_data'] ?? null,
                errorMessage: $payload['error_message'] ?? null,
            );

            return response()->json([
                'message' => 'Task status updated.',
                'task' => [
                    'uuid'   => $task->uuid,
                    'status' => $payload['status'],
                ],
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'message' => 'Failed to update task: ' . $e->getMessage(),
            ], 500);
        }
    }
}
