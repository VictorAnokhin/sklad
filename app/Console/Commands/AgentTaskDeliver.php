<?php

namespace App\Console\Commands;

use App\Models\AgentTask;
use App\Models\ChatMessage;
use App\Models\ChatSession;
use App\Services\TelegramBotService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class AgentTaskDeliver extends Command
{
    protected $signature = 'agent:task-deliver {uuid : Agent task UUID}';

    protected $description = 'Deliver an already completed agent task result to its source channel.';

    public function handle(TelegramBotService $bot): int
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

        if ($task->source_agent === 'telegram_expert') {
            $chatId = $task->input_data['chat_id'] ?? null;
            if (! $chatId) {
                $this->warn('Task source is telegram_expert, but input_data.chat_id is empty.');
            } else {
                $this->deliverToTelegram($bot, $chatId, $message, $task->uuid);
                $this->info("Delivered to Telegram chat {$chatId}.");
                $delivered = true;
            }
        }

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

    private function deliverToTelegram(TelegramBotService $bot, int|string $chatId, string $message, string $taskUuid): void
    {
        $bot->sendChatAction($chatId, 'typing');

        try {
            $bot->sendMarkdown($chatId, $message);
        } catch (\Throwable $markdownError) {
            Log::warning('AgentTask manual delivery markdown failed, retrying as plain text.', [
                'chat_id' => $chatId,
                'task_uuid' => $taskUuid,
                'error' => $markdownError->getMessage(),
            ]);

            $bot->sendMessage($chatId, $message);
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
