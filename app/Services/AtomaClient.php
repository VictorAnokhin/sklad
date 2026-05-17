<?php

namespace App\Services;

use App\Contracts\AiClientInterface;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class AtomaClient implements AiClientInterface
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
            'messages' => array_merge(
                [['role' => 'system', 'content' => $instructions]],
                array_map(static fn (array $message): array => [
                    'role' => $message['role'],
                    'content' => $message['content'],
                ], $messages),
            ),
            'temperature' => (float) ($options['temperature'] ?? 0.35),
            'max_tokens' => (int) ($options['max_tokens'] ?? 700),
            'stream' => false,
        ];

        try {
            $response = Http::acceptJson()
                ->asJson()
                ->withToken($apiKey)
                ->timeout((int) ($config['timeout'] ?? 60))
                ->connectTimeout(15)
                ->post($config['api_base'].'/v1/chat/completions', $body);
        } catch (Throwable $e) {
            throw new RuntimeException('Atoma request failed: '.$e->getMessage(), 0, $e);
        }

        if (! $response->successful()) {
            $message = (string) data_get($response->json(), 'error.message', $response->body());
            throw new RuntimeException('Atoma returned HTTP '.$response->status().': '.mb_substr($message, 0, 500));
        }

        $payload = $response->json();
        if (! is_array($payload)) {
            throw new RuntimeException('Atoma returned non-JSON response.');
        }

        $answer = trim((string) data_get($payload, 'choices.0.message.content', ''));
        if ($answer === '') {
            throw new RuntimeException('Atoma response did not contain an assistant message.');
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
     * Atoma использует OpenAI-совместимый API (/v1/chat/completions).
     * Tool calling поддерживается, если модель умеет.
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

        // Формируем тело запроса с tools
        $body = [
            'model' => $model,
            'messages' => array_merge(
                [['role' => 'system', 'content' => $instructions]],
                array_map(static fn (array $message): array => [
                    'role' => $message['role'],
                    'content' => $message['content'],
                ], $messages),
            ),
            'tools' => $tools,
            'tool_choice' => 'auto',
            'temperature' => (float) ($options['temperature'] ?? 0.35),
            'max_tokens' => (int) ($options['max_tokens'] ?? 2000),
            'stream' => false,
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
                    ->post($config['api_base'].'/v1/chat/completions', $body);
            } catch (Throwable $e) {
                throw new RuntimeException('Atoma request failed: '.$e->getMessage(), 0, $e);
            }

            if (! $response->successful()) {
                // Если tool calling не поддерживается — fallback на обычный чат
                if ($response->status() === 400 || $response->status() === 404) {
                    return $this->chat($instructions, $messages, $options);
                }

                $message = (string) data_get($response->json(), 'error.message', $response->body());
                throw new RuntimeException('Atoma returned HTTP '.$response->status().': '.mb_substr($message, 0, 500));
            }

            $payload = $response->json();
            if (! is_array($payload)) {
                throw new RuntimeException('Atoma returned non-JSON response.');
            }

            $choice = $payload['choices'][0] ?? [];
            $message = $choice['message'] ?? [];

            $content = trim((string) ($message['content'] ?? ''));
            $toolCalls = $message['tool_calls'] ?? [];

            if (empty($toolCalls)) {
                if ($content === '' && $iteration === 1) {
                    throw new RuntimeException('Atoma response did not contain an assistant message.');
                }

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

            // Обработка tool_calls
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

            if ($iteration >= $maxIterations) {
                $finalBody = $body;
                unset($finalBody['tools'], $finalBody['tool_choice']);

                try {
                    $response = Http::acceptJson()
                        ->asJson()
                        ->withToken($apiKey)
                        ->timeout((int) ($config['timeout'] ?? 60))
                        ->connectTimeout(15)
                        ->post($config['api_base'].'/v1/chat/completions', $finalBody);

                    $payload = $response->json();
                    $finalContent = trim((string) data_get($payload, 'choices.0.message.content', ''));

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

        throw new RuntimeException('Atoma tool calling exceeded maximum iterations.');
    }

    /**
     * {@inheritdoc}
     */
    public function getProviderName(): string
    {
        return 'atoma';
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
     * Получить конфигурацию провайдера.
     *
     * @return array{api_base: string, api_key: string, model: string, timeout: int}
     */
    private function getConfig(): array
    {
        $config = config('ai.providers.atoma');
        if ($config === null || empty($config['api_key'])) {
            $config = config('services.atoma');
        }

        return [
            'api_base' => rtrim((string) ($config['api_base'] ?? 'https://api.atoma.network'), '/'),
            'api_key'  => (string) ($config['api_key'] ?? ''),
            'model'    => (string) ($config['model'] ?? 'openai/gpt-4o-mini'),
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
