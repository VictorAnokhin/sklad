<?php

namespace App\Agents;

use App\Models\AgentTask;
use App\Services\AgentOrchestrator;
use App\Services\AiKnowledgeService;
use App\Services\ChatService;
use App\Services\DeepSeekClient;

class FrontendAgent
{
    public function __construct(
        private ChatService $chatService,
        private AiKnowledgeService $knowledgeService,
        private DeepSeekClient $deepseek,
        private AgentOrchestrator $orchestrator,
    ) {}

    /**
     * Обработать сообщение из веб-чата.
     * Используется AiChatController'ом вместо прямого вызова ChatService.
     */
    public function ask(array $payload): array
    {
        $fid = (int) ($payload['fid'] ?? 0);
        $message = $payload['message'] ?? '';
        $language = $payload['language'] ?? 'ru';
        $sessionToken = $payload['session_token'] ?? '';

        // Определяем, можем ответить сами или нужно делегировать
        if ($this->shouldDelegateToBackend($message)) {
            return $this->delegateAndWait($fid, $message, $language, $sessionToken);
        }

        // Отвечаем через ChatService (как сейчас)
        return $this->chatService->sendMessage($payload);
    }

    /**
     * Выполнить задачу от другого агента.
     */
    public function executeTask(AgentTask $task): array
    {
        return match ($task->task_type) {
            'simple_answer' => $this->simpleAnswer($task),
            default => throw new \InvalidArgumentException("FrontendAgent: unknown task_type '{$task->task_type}'"),
        };
    }

    // ════════════════════════════════════════════════════════════════
    //  PRIVATE
    // ════════════════════════════════════════════════════════════════

    /**
     * Определить, нужно ли делегировать BackendAgent.
     */
    private function shouldDelegateToBackend(string $message): bool
    {
        $delegateKeywords = [
            'заказ', 'клиент', 'баланс', 'долг', 'найти',
            'статистика', 'массовый', 'изучи сайт',
        ];

        foreach ($delegateKeywords as $keyword) {
            if (mb_stripos($message, $keyword) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Делегировать задачу BackendAgent и дождаться результата.
     */
    private function delegateAndWait(int $fid, string $message, string $language, string $sessionToken): array
    {
        // Создаём задачу для BackendAgent
        $task = $this->orchestrator->createTask(
            sourceAgent: 'frontend',
            targetAgent: 'backend',
            fid: $fid,
            taskType: $this->detectTaskType($message),
            inputData: [
                'query' => $message,
                'question' => $message,
                'language' => $language,
            ],
            sessionToken: $sessionToken,
            dispatchJob: true,
        );

        return [
            'response' => "⏳ Я передал ваш запрос аналитику. Ожидайте результат...",
            'task_uuid' => $task->uuid,
            'delegated' => true,
        ];
    }

    /**
     * Определить тип задачи.
     */
    private function detectTaskType(string $message): string
    {
        return match (true) {
            preg_match('/клиент/i', $message) && preg_match('/найди|поиск|найти/i', $message) => 'find_client',
            preg_match('/заказ/i') && preg_match('/создай|новый|оформить/i', $message) => 'create_order',
            preg_match('/заказ/i') && preg_match('/найди|номер/i', $message) => 'find_order',
            preg_match('/баланс|долг/i', $message) => 'get_client_balance',
            preg_match('/статистик/i', $message) => 'mass_analysis',
            default => 'complex_question',
        };
    }

    /**
     * Простой ответ (задача от другого агента).
     */
    private function simpleAnswer(AgentTask $task): array
    {
        $fid = $task->fid;
        $question = $task->input_data['question'] ?? '';

        $knowledge = $this->knowledgeService->getContext($fid);

        $result = $this->deepseek->chat(
            instructions: "Ты — FrontendAgent, помощник на сайте. Отвечай кратко и по делу. Контекст: {$knowledge}",
            messages: [['role' => 'user', 'content' => $question]],
        );

        return [
            'answer' => $result['response'] ?? 'Не удалось получить ответ.',
        ];
    }
}
