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
        $apiKey = trim((string) config('services.deepseek.api_key', ''));
        if ($apiKey === '') {
            throw new RuntimeException('DeepSeek API key is not configured (DEEPSEEK_API_KEY).');
        }

        $model = trim((string) ($options['model'] ?? config('services.deepseek.model', '')));
        if ($model === '') {
            throw new RuntimeException('DeepSeek model is not configured (DEEPSEEK_MODEL).');
        }

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
            'max_tokens' => (int) ($options['max_tokens'] ?? 900),
            'stream' => false,
        ];

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

    private function apiBase(): string
    {
        return rtrim((string) config('services.deepseek.api_base', 'https://api.deepseek.com'), '/');
    }
}
