<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class DeepSeekClient
{
    /**
     * @param  string  $instructions  System prompt
     * @param  array<int, array{role: string, content: string}>  $messages
     * @return array{answer: string, model: string, usage: array<string, mixed>}
     */
    public function chat(string $instructions, array $messages, array $options = []): array
    {
        $result = $this->sendRequest($instructions, $messages, $options);

        return [
            'answer' => $result['answer'],
            'model' => $result['model'],
            'usage' => $result['usage'],
        ];
    }

    /**
     * Отправить запрос с поддержкой function calling (tools).
     *
     * Позволяет AI вызывать функции для получения данных из БД.
     *
     * @param  string  $instructions  System prompt
     * @param  array<int, array{role: string, content: string}>  $messages
     * @param  array<int, array<string, mixed>>  $tools  Список инструментов (functions)
     * @param  callable(string $name, array $arguments): string  $toolExecutor  Функция выполнения инструментов
     * @return array{answer: string, model: string, usage: array<string, mixed>}
     */
    public function chatWithTools(
        string $instructions,
        array $messages,
        array $tools,
        callable $toolExecutor,
        array $options = [],
    ): array {
        $apiKey = $this->getApiKey();
        $model = $this->getModel($options);

        // Формируем тело запроса с tools
        $body = $this->buildRequestBody($instructions, $messages, $model, $options);
        $body['tools'] = $tools;
        $body['tool_choice'] = 'auto';

        // Максимальное количество итераций tool calling (предотвращает бесконечный цикл)
        $maxIterations = (int) ($options['max_tool_iterations'] ?? 5);
        $iteration = 0;

        do {
            $iteration++;
            $payload = $this->sendHttpRequest($body, $apiKey);
            $choice = $payload['choices'][0] ?? [];
            $message = $choice['message'] ?? [];

            $content = trim((string) ($message['content'] ?? ''));
            $toolCalls = $message['tool_calls'] ?? [];

            // Если нет tool_calls — возвращаем ответ
            if (empty($toolCalls)) {
                if ($content === '' && $iteration === 1) {
                    throw new RuntimeException('DeepSeek response did not contain an assistant message.');
                }

                // Добавляем сообщение ассистента в историю
                $body['messages'][] = [
                    'role' => 'assistant',
                    'content' => $content ?: '...',
                ];

                return [
                    'answer' => $content,
                    'model' => (string) data_get($payload, 'model', $model),
                    'usage' => is_array(data_get($payload, 'usage')) ? data_get($payload, 'usage') : [],
                ];
            }

            // ── Обработка tool_calls ──────────────────────────────────
            // Добавляем сообщение ассистента с tool_calls в историю
            $assistantMessage = [
                'role' => 'assistant',
                'content' => $content ?: null,
                'tool_calls' => [],
            ];

            foreach ($toolCalls as $tc) {
                $assistantMessage['tool_calls'][] = [
                    'id' => $tc['id'],
                    'type' => 'function',
                    'function' => [
                        'name' => $tc['function']['name'],
                        'arguments' => $tc['function']['arguments'],
                    ],
                ];
            }

            $body['messages'][] = $assistantMessage;

            // Выполняем каждый tool call
            foreach ($toolCalls as $tc) {
                $toolCallId = $tc['id'];
                $functionName = $tc['function']['name'];
                $functionArgs = json_decode($tc['function']['arguments'], true) ?? [];

                $resultJson = $toolExecutor($functionName, $functionArgs);

                $body['messages'][] = [
                    'role' => 'tool',
                    'tool_call_id' => $toolCallId,
                    'content' => $resultJson,
                ];
            }

            // Если превысили лимит итераций — завершаем
            if ($iteration >= $maxIterations) {
                // Запрашиваем финальный ответ без tools
                $finalBody = $body;
                unset($finalBody['tools'], $finalBody['tool_choice']);

                $payload = $this->sendHttpRequest($finalBody, $apiKey);
                $finalContent = trim((string) data_get($payload, 'choices.0.message.content', ''));

                return [
                    'answer' => $finalContent ?: 'Запрос обработан. Чем ещё могу помочь?',
                    'model' => (string) data_get($payload, 'model', $model),
                    'usage' => is_array(data_get($payload, 'usage')) ? data_get($payload, 'usage') : [],
                ];
            }

            // Продолжаем цикл — отправляем следующий запрос с обновлённой историей
        } while ($iteration < $maxIterations);

        throw new RuntimeException('DeepSeek tool calling exceeded maximum iterations.');
    }

    /**
     * Отправить единичный HTTP-запрос к DeepSeek API.
     *
     * @param  array<string, mixed>  $body
     * @return array<string, mixed>
     */
    private function sendHttpRequest(array $body, string $apiKey): array
    {
        try {
            $response = Http::acceptJson()
                ->asJson()
                ->withToken($apiKey)
                ->timeout((int) config('services.deepseek.timeout', 60))
                ->connectTimeout(15)
                ->post($this->apiBase().'/v1/chat/completions', $body);
        } catch (Throwable $e) {
            throw new RuntimeException('DeepSeek request failed: '.$e->getMessage(), 0, $e);
        }

        if (! $response->successful()) {
            $message = (string) data_get($response->json(), 'error.message', $response->body());
            throw new RuntimeException('DeepSeek returned HTTP '.$response->status().': '.mb_substr($message, 0, 500));
        }

        $payload = $response->json();
        if (! is_array($payload)) {
            throw new RuntimeException('DeepSeek returned non-JSON response.');
        }

        return $payload;
    }

    /**
     * Простой запрос (без tools).
     */
    private function sendRequest(string $instructions, array $messages, array $options = []): array
    {
        $apiKey = $this->getApiKey();
        $model = $this->getModel($options);
        $body = $this->buildRequestBody($instructions, $messages, $model, $options);

        $payload = $this->sendHttpRequest($body, $apiKey);

        $answer = trim((string) data_get($payload, 'choices.0.message.content', ''));
        if ($answer === '') {
            throw new RuntimeException('DeepSeek response did not contain an assistant message.');
        }

        return [
            'answer' => $answer,
            'model' => (string) data_get($payload, 'model', $model),
            'usage' => is_array(data_get($payload, 'usage')) ? data_get($payload, 'usage') : [],
        ];
    }

    /**
     * Сформировать тело запроса.
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    private function buildRequestBody(string $instructions, array $messages, string $model, array $options = []): array
    {
        return [
            'model' => $model,
            'messages' => array_merge(
                [['role' => 'system', 'content' => $instructions]],
                array_map(static fn (array $message): array => [
                    'role' => $message['role'],
                    'content' => $message['content'],
                ], $messages),
            ),
            'temperature' => (float) ($options['temperature'] ?? 0.35),
            'max_tokens' => (int) ($options['max_tokens'] ?? 1500),
            'stream' => false,
        ];
    }

    private function getApiKey(): string
    {
        $apiKey = trim((string) config('services.deepseek.api_key', ''));
        if ($apiKey === '') {
            throw new RuntimeException('DeepSeek API key is not configured (DEEPSEEK_API_KEY).');
        }
        return $apiKey;
    }

    private function getModel(array $options = []): string
    {
        $model = trim((string) ($options['model'] ?? config('services.deepseek.model', '')));
        if ($model === '') {
            throw new RuntimeException('DeepSeek model is not configured (DEEPSEEK_MODEL).');
        }
        return $model;
    }

    private function apiBase(): string
    {
        return rtrim((string) config('services.deepseek.api_base', 'https://api.deepseek.com'), '/');
    }
}
