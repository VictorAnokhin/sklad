<?php

namespace App\Http\Controllers;

use App\Agents\BackendAgent;
use App\Models\AgentTask;
use App\Services\AgentOrchestrator;
use App\Services\ChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class BackendAgentChatController extends Controller
{
    public function __construct(
        private readonly BackendAgent       $backendAgent,
        private readonly AgentOrchestrator  $orchestrator,
        private readonly ChatService        $chatService,
    ) {}

    /**
     * Чат с BackendAgent — определяет намерение и выполняет или делегирует.
     *
     * POST /api/agent/backend/chat
     */
    public function chat(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'message'       => ['required', 'string', 'min:2', 'max:2000'],
            'fid'           => ['required', 'integer', 'min:1'],
            'session_token' => ['nullable', 'string', 'max:100'],
            'language'      => ['nullable', 'string', 'in:ru,ua,en'],
        ]);

        $fid    = (int) $payload['fid'];
        $text   = $payload['message'];
        $lang   = $payload['language'] ?? 'ru';

        try {
            // Определяем тип задачи по тексту
            $taskType = $this->detectTaskType($text);

            // Если простая команда — создаём AgentTask и выполняем синхронно
            if ($taskType !== 'complex_question') {
                $inputData = $this->buildInputData($taskType, $text);

                $task = $this->orchestrator->createTask(
                    sourceAgent:  'backend',
                    targetAgent:  'backend',
                    fid:          $fid,
                    taskType:     $taskType,
                    inputData:    $inputData,
                    sessionToken: $payload['session_token'] ?? null,
                    dispatchJob:  false, // выполняем синхронно
                );

                // Выполняем задачу напрямую
                try {
                    $output = $this->backendAgent->executeTask($task);
                    $this->orchestrator->updateTaskStatus($task->id, 'completed', $output);
                } catch (Throwable $e) {
                    $output = ['message' => 'Ошибка выполнения: ' . $e->getMessage()];
                    $this->orchestrator->updateTaskStatus($task->id, 'failed', $output, $e->getMessage());
                }

                $reply = $output['message'] ?? 'Задача выполнена.';

                return response()->json([
                    'message'   => $reply,
                    'task_type' => $taskType,
                    'task_uuid' => $task->uuid,
                    'output'    => $output,
                ]);
            }

            // Сложный вопрос — используем DeepSeek с инструментами БД
            $result = $this->chatService->sendMessage([
                'message'       => $text,
                'fid'           => $fid,
                'language'      => $lang,
                'session_token' => $payload['session_token'] ?? null,
                'use_db_tools'  => true,
            ]);

            return response()->json([
                'message'   => $result['answer'] ?? $result['message'] ?? 'Ответ получен.',
                'task_type' => 'complex_question',
                'session'   => $result['session'] ?? null,
            ]);
        } catch (Throwable $e) {
            Log::error('BackendAgentChatController:chat failed', [
                'message' => $text,
                'fid'     => $fid,
                'error'   => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Ошибка: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Получить историю задач BackendAgent.
     * GET /api/agent/backend/tasks?fid=1&limit=10
     */
    public function tasks(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'fid'   => ['required', 'integer', 'min:1'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $tasks = AgentTask::where('target_agent', 'backend')
            ->where('fid', (int) $payload['fid'])
            ->orderByDesc('created_at')
            ->limit((int) ($payload['limit'] ?? 20))
            ->get();

        return response()->json([
            'tasks' => $tasks->map(fn (AgentTask $t) => [
                'uuid'          => $t->uuid,
                'task_type'     => $t->task_type,
                'source_agent'  => $t->source_agent,
                'input_data'    => $t->input_data,
                'output_data'   => $t->output_data,
                'status'        => $t->status,
                'error_message' => $t->error_message,
                'created_at'    => $t->created_at?->toIso8601String(),
                'completed_at'  => $t->completed_at?->toIso8601String(),
            ]),
        ]);
    }

    /**
     * Классификатор намерений — определяет тип задачи по тексту.
     */
    private function detectTaskType(string $message): string
    {
        return match (true) {
            preg_match('/найди клиента|поиск клиента|клиент.*телефон|найти клиента/i', $message) === 1 => 'find_client',
            preg_match('/создай клиента|новый клиент|добавь клиента|регистрация клиента/i', $message) === 1 => 'create_client',
            preg_match('/баланс|долг|задолженность|сальдо/i', $message) === 1                       => 'get_client_balance',
            preg_match('/заказ.*номер|найди заказ|поиск заказа|номер заказа/i', $message) === 1      => 'find_order',
            preg_match('/заказы клиента|заказы пользовател|все заказы/i', $message) === 1             => 'find_client_orders',
            preg_match('/создай заказ|новый заказ|оформить|сделать заказ/i', $message) === 1          => 'create_order',
            preg_match('/статус заказа|где заказ/i', $message) === 1                                 => 'get_order_status',
            preg_match('/изучи сайт|проанализируй сайт|проверь сайт/i', $message) === 1              => 'study_website',
            preg_match('/сохрани в базу|запомни|добавь информацию/i', $message) === 1                => 'save_to_knowledge',
            preg_match('/массовый анализ|проанализируй все/i', $message) === 1                       => 'mass_analysis',
            default                                                                                  => 'complex_question',
        };
    }

    /**
     * Сформировать input_data для задачи по типу.
     */
    private function buildInputData(string $taskType, string $text): array
    {
        $data = ['query' => $text, 'message' => $text];

        // Извлекаем ID клиента/заказа, если есть
        if (preg_match('/(?:id|номер|№)\s*[:\s]*(\d+)/i', $text, $m)) {
            $data['client_id'] = (int) $m[1];
            $data['order_id']  = (int) $m[1];
        }

        // Извлекаем URL для study_website
        if ($taskType === 'study_website' && preg_match('/https?:\/\/[^\s]+/', $text, $m)) {
            $data['url'] = $m[0];
        }

        // Извлекаем телефон для find_client
        if (preg_match('/(?:тел|phone|моб|\+?380)\s*[:\s]*(\+?\d[\d\s\-\(\)]{7,})/i', $text, $m)) {
            $data['query'] = trim($m[1]);
        }

        return $data;
    }
}
