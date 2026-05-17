<?php

namespace App\Services;

use App\Contracts\AiClientInterface;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class OpenAiClient implements AiClientInterface
{
    private ?string $modelOverride = null;

    /**
     * {@inheritdoc}
     */
    public function chat(string $instructions, array $messages, array $options = []): array
    {
        $config = $this->getConfig();
        $apiKey = $config['api_key'];
        $model = $this->resolveModel($options);

        $body = [
            'model' => $model,
            'instructions' => $instructions,
            'input' => array_map(static fn (array $message): array => [
                'role' => $message['role'],
                'content' => $message['content'],
            ], $messages),
            'max_output_tokens' => (int) ($options['max_tokens'] ?? 900),
            'temperature' => (float) ($options['temperature'] ?? 0.35),
        ];

        try {
            $response = Http::acceptJson()
                ->asJson()
                ->withToken($apiKey)
                ->timeout((int) ($config['timeout'] ?? 60))
                ->connectTimeout(15)
                ->post($config['api_base'].'/v1/responses', $body);
        } catch (Throwable $e) {
            throw new RuntimeException('OpenAI request failed: '.$e->getMessage(), 0, $e);
        }

        if (! $response->successful()) {
            $message = (string) data_get($response->json(), 'error.message', $response->body());
            throw new RuntimeException('OpenAI returned HTTP '.$response->status().': '.mb_substr($message, 0, 500));
        }

        $payload = $response->json();
        if (! is_array($payload)) {
            throw new RuntimeException('OpenAI returned non-JSON response.');
        }

        $answer = $this->extractOutputText($payload);
        if ($answer === '') {
            throw new RuntimeException('OpenAI response did not contain output text.');
        }

        return [
            'answer' => $answer,
            'model' => (string) data_get($payload, 'model', $model),
            'usage' => is_array(data_get($payload, 'usage')) ? data_get($payload, 'usage') : [],
        ];
    }

    /**
     * {@inheritdoc}
     *
     * OpenAI Responses API поддерживает tools через /v1/responses.
     * Если провайдер не поддерживает tools, делаем обычный запрос.
     */
    public function chatWithTools(
        string $instructions,
        array $messages,
        array $tools,
        callable $toolExecutor,
        array $options = [],
    ): array {
        $config = $this->getConfig();
        $apiKey = $config['api_key'];
        $model = $this->resolveModel($options);

        // Пробуем использовать Responses API с tools
        $body = [
            'model' => $model,
            'instructions' => $instructions,
            'input' => array_map(static fn (array $message): array => [
                'role' => $message['role'],
                'content' => $message['content'],
            ], $messages),
            'tools' => $this->convertTools($tools),
            'tool_choice' => 'auto',
            'max_output_tokens' => (int) ($options['max_tokens'] ?? 2000),
            'temperature' => (float) ($options['temperature'] ?? 0.35),
        ];

        $maxIterations = (int) ($options['max_tool_iterations'] ?? config('ai.tools.max_iterations', 10));
        $iteration = 0;

        do {
            $iteration++;

            try {
                $response = Http::acceptJson()
                    ->asJson()
                    ->withToken($apiKey)
                    ->timeout((int) ($config['timeout'] ?? 60))
                    ->connectTimeout(15)
                    ->post($config['api_base'].'/v1/responses', $body);
            } catch (Throwable $e) {
                throw new RuntimeException('OpenAI request failed: '.$e->getMessage(), 0, $e);
            }

            if (! $response->successful()) {
                // Если Responses API не поддерживается — пробуем chat completions
                if ($response->status() === 404 || $response->status() === 400) {
                    return $this->chat($instructions, $messages, $options);
                }

                $message = (string) data_get($response->json(), 'error.message', $response->body());
                throw new RuntimeException('OpenAI returned HTTP '.$response->status().': '.mb_substr($message, 0, 500));
            }

            $payload = $response->json();
            if (! is_array($payload)) {
                throw new RuntimeException('OpenAI returned non-JSON response.');
            }

            // Проверяем, есть ли tool_calls в output
            $output = data_get($payload, 'output', []);
            $toolCalls = $this->extractToolCalls($output);

            if (empty($toolCalls)) {
                $answer = $this->extractOutputText($payload);

                return [
                    'answer' => $answer ?: 'Запрос обработан.',
                    'model' => (string) data_get($payload, 'model', $model),
                    'usage' => is_array(data_get($payload, 'usage')) ? data_get($payload, 'usage') : [],
                ];
            }

            // Добавляем результаты tool calls в input
            foreach ($toolCalls as $tc) {
                $functionName = $tc['function']['name'];
                $functionArgs = $tc['function']['arguments'];
                $callId = $tc['call_id'] ?? $tc['id'] ?? uniqid('call_', true);

                $resultJson = $toolExecutor($functionName, $functionArgs);

                $body['input'][] = [
                    'role' => 'assistant',
                    'content' => null,
                    'tool_calls' => [[
                        'id' => $callId,
                        'type' => 'function',
                        'function' => [
                            'name' => $functionName,
                            'arguments' => json_encode($functionArgs),
                        ],
                    ]],
                ];

                $body['input'][] = [
                    'role' => 'tool',
                    'tool_call_id' => $callId,
                    'content' => $resultJson,
                ];
            }

            if ($iteration >= $maxIterations) {
                // Финальный запрос без tools
                $finalBody = $body;
                unset($finalBody['tools'], $finalBody['tool_choice']);

                try {
                    $response = Http::acceptJson()
                        ->asJson()
                        ->withToken($apiKey)
                        ->timeout((int) ($config['timeout'] ?? 60))
                        ->connectTimeout(15)
                        ->post($config['api_base'].'/v1/responses', $finalBody);

                    $payload = $response->json();
                    $finalContent = $this->extractOutputText(is_array($payload) ? $payload : []);

                    return [
                        'answer' => $finalContent ?: 'Запрос обработан.',
                        'model' => (string) data_get($payload, 'model', $model),
                        'usage' => is_array(data_get($payload, 'usage')) ? data_get($payload, 'usage') : [],
                    ];
                } catch (Throwable) {
                    return [
                        'answer' => 'Запрос обработан. Чем ещё могу помочь?',
                        'model' => $model,
                        'usage' => [],
                    ];
                }
            }
        } while ($iteration < $maxIterations);

        throw new RuntimeException('OpenAI tool calling exceeded maximum iterations.');
    }

    /**
     * {@inheritdoc}
     */
    public function getProviderName(): string
    {
        return 'openai';
    }

    /**
     * {@inheritdoc}
     */
    public function setModel(?string $model): static
    {
        $this->modelOverride = $model;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getModel(): string
    {
        return $this->resolveModel([]);
    }

    // ── Приватные методы ─────────────────────────────────────────────────

    /**
     * @param  array<string, mixed>  $payload
     */
    private function extractOutputText(array $payload): string
    {
        $outputText = trim((string) data_get($payload, 'output_text', ''));
        if ($outputText !== '') {
            return $outputText;
        }

        $parts = [];
        foreach ((array) data_get($payload, 'output', []) as $item) {
            foreach ((array) data_get($item, 'content', []) as $content) {
                $text = trim((string) data_get($content, 'text', ''));
                if ($text !== '') {
                    $parts[] = $text;
                }
            }
        }

        return trim(implode("\n", $parts));
    }

    /**
     * Извлечь tool_calls из output Responses API.
     *
     * @param  array<int, mixed>  $output
     * @return array<int, array{id: string, type: string, function: array{name: string, arguments: array}}>
     */
    private function extractToolCalls(array $output): array
    {
        $calls = [];

        foreach ($output as $item) {
            $type = $item['type'] ?? '';
            if ($type === 'function_call') {
                $name = $item['name'] ?? '';
                $args = $item['arguments'] ?? [];
                $callId = $item['id'] ?? $item['call_id'] ?? uniqid('call_', true);

                if ($name !== '') {
                    $calls[] = [
                        'id' => $callId,
                        'type' => 'function',
                        'function' => [
                            'name' => $name,
                            'arguments' => is_array($args) ? $args : (json_decode((string) $args, true) ?? []),
                        ],
                    ];
                }
            }
        }

        return $calls;
    }

    /**
     * Конвертировать tools из формата OpenAI в формат Responses API.
     *
     * @param  array<int, array<string, mixed>>  $tools
     * @return array<int, array<string, mixed>>
     */
    private function convertTools(array $tools): array
    {
        $converted = [];

        foreach ($tools as $tool) {
            if (($tool['type'] ?? '') === 'function') {
                $func = $tool['function'] ?? [];

                $converted[] = [
                    'type' => 'function',
                    'name' => $func['name'] ?? '',
                    'description' => $func['description'] ?? '',
                    'parameters' => $func['parameters'] ?? ((object) []),
                    'strict' => false,
                ];
            }
        }

        return $converted;
    }

    /**
     * Получить конфигурацию провайдера.
     *
     * @return array{api_base: string, api_key: string, model: string, timeout: int}
     */
    private function getConfig(): array
    {
        $config = config('ai.providers.openai');
        if ($config === null || empty($config['api_key'])) {
            $config = config('services.openai');
        }

        return [
            'api_base' => rtrim((string) ($config['api_base'] ?? 'https://api.openai.com'), '/'),
            'api_key'  => (string) ($config['api_key'] ?? ''),
            'model'    => (string) ($config['model'] ?? 'gpt-4o-mini'),
            'timeout'  => (int) ($config['timeout'] ?? 60),
        ];
    }

    /**
     * Разрешить модель: приоритет — options > modelOverride > config.
     */
    private function resolveModel(array $options): string
    {
        $model = trim((string) ($options['model'] ?? ''));
        if ($model !== '') {
            return $model;
        }

        if ($this->modelOverride !== null) {
            return $this->modelOverride;
        }

        $config = $this->getConfig();

        return $config['model'];
    }
}
