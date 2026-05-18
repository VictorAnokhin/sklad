<?php

namespace App\Jobs;

use App\Agents\BackendAgent;
use App\Agents\FrontendAgent;
use App\Agents\TelegramAgent;
use App\Models\AgentTask;
use App\Models\ChatMessage;
use App\Models\ChatSession;
use App\Services\AgentOrchestrator;
use App\Services\TelegramBotService;
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

            if (($result['status'] ?? null) === 'waiting_human') {
                $task = $orchestrator->updateTaskStatus($task->id, 'waiting_human', $result);
                $this->deliverResultToChatSession($task, $result);
                return;
            }

            // Обновляем задачу со статусом completed
            $task = $orchestrator->updateTaskStatus($task->id, 'completed', $result);

            // Отправляем результат source-агенту
            $orchestrator->sendTaskResult($task, $result);
            $this->deliverResultToSourceChannel($task, $result);
            $this->deliverResultToChatSession($task, $result);

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

    private function deliverResultToChatSession(AgentTask $task, array $result): void
    {
        if (! $task->session_token) {
            return;
        }

        $session = ChatSession::resolveByToken($task->session_token);
        if (! $session) {
            Log::warning("AgentTask #{$task->id}: chat session not found for result delivery.", [
                'session_token' => $task->session_token,
            ]);
            return;
        }

        $message = $result['message'] ?? $result['answer'] ?? null;
        if (! is_string($message) || trim($message) === '') {
            return;
        }

        ChatMessage::create([
            'chat_session_id' => $session->id,
            'fid' => $task->fid,
            'firma' => $session->firma,
            'role' => 'assistant',
            'content' => $message,
            'metadata' => [
                'source' => 'agent_task_result',
                'source_agent' => $task->target_agent,
                'target_agent' => $task->source_agent,
                'task_uuid' => $task->uuid,
                'task_type' => $task->task_type,
            ],
        ]);
    }

    private function deliverResultToSourceChannel(AgentTask $task, array $result): void
    {
        if ($task->source_agent !== 'telegram_expert') {
            return;
        }

        $chatId = $task->input_data['chat_id'] ?? null;
        if (! $chatId) {
            return;
        }

        $message = $this->resultMessage($result);
        if ($message === null) {
            return;
        }

        try {
            $bot = app(TelegramBotService::class);
            $bot->sendChatAction($chatId, 'typing');

            try {
                $bot->sendMarkdown($chatId, $message);
            } catch (\Throwable $markdownError) {
                Log::warning('AgentTask result markdown send failed, retrying as plain text.', [
                    'chat_id' => $chatId,
                    'task_uuid' => $task->uuid,
                    'error' => $markdownError->getMessage(),
                ]);

                $bot->sendMessage($chatId, $message);
            }
        } catch (\Throwable $e) {
            Log::warning("AgentTask #{$task->id}: failed to deliver result to Telegram chat.", [
                'chat_id' => $chatId,
                'task_uuid' => $task->uuid,
                'error' => $e->getMessage(),
            ]);
        }
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
