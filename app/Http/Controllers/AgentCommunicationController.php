<?php

namespace App\Http\Controllers;

use App\Models\AgentCommunication;
use App\Services\AgentOrchestrator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class AgentCommunicationController extends Controller
{
    public function __construct(
        private readonly AgentOrchestrator $orchestrator,
    ) {}

    /**
     * Список сообщений для агента.
     * GET /api/agent/communications?agent=backend&fid=1&limit=20
     */
    public function index(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'agent' => ['required', 'string', 'max:50'],
            'fid'   => ['required', 'integer', 'min:1'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $communications = $this->orchestrator->getCommunications(
            agentName: $payload['agent'],
            fid:       (int) $payload['fid'],
            limit:     (int) ($payload['limit'] ?? 50),
        );

        return response()->json([
            'communications' => $communications->map(fn (AgentCommunication $c) => [
                'id'           => $c->id,
                'source_agent' => $c->source_agent,
                'target_agent' => $c->target_agent,
                'message_type' => $c->message_type,
                'content'      => $c->content,
                'task_id'      => $c->task_id,
                'metadata'     => $c->metadata,
                'status'       => $c->status,
                'created_at'   => $c->created_at?->toIso8601String(),
            ]),
        ]);
    }

    /**
     * Отправить сообщение агенту.
     * POST /api/agent/communications
     */
    public function store(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'source_agent' => ['required', 'string', 'max:50'],
            'target_agent' => ['required', 'string', 'max:50'],
            'fid'          => ['required', 'integer', 'min:1'],
            'content'      => ['required', 'string', 'max:10000'],
            'message_type' => ['nullable', 'string', 'max:50'],
            'task_id'      => ['nullable', 'integer', 'exists:agent_tasks,id'],
            'metadata'     => ['nullable', 'array'],
        ]);

        try {
            $communication = $this->orchestrator->sendCommunication(
                sourceAgent: $payload['source_agent'],
                targetAgent: $payload['target_agent'],
                fid:         (int) $payload['fid'],
                content:     $payload['content'],
                messageType: $payload['message_type'] ?? 'text',
                taskId:      isset($payload['task_id']) ? (int) $payload['task_id'] : null,
                metadata:    $payload['metadata'] ?? [],
            );

            return response()->json([
                'communication' => [
                    'id'           => $communication->id,
                    'source_agent' => $communication->source_agent,
                    'target_agent' => $communication->target_agent,
                    'message_type' => $communication->message_type,
                    'content'      => $communication->content,
                    'task_id'      => $communication->task_id,
                    'status'       => $communication->status,
                    'created_at'   => $communication->created_at?->toIso8601String(),
                ],
            ], 201);
        } catch (Throwable $e) {
            return response()->json([
                'message' => 'Failed to send communication: ' . $e->getMessage(),
            ], 500);
        }
    }
}
