<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class AtomaClient
{
    /**
     * @param  array<int, array{role: string, content: string}>  $messages
     * @return array{answer: string, model: string, usage: array<string, mixed>}
     */
    public function chat(array $messages, array $options = []): array
    {
        $apiKey = trim((string) config('services.atoma.api_key', ''));
        if ($apiKey === '') {
            throw new RuntimeException('Atoma API key is not configured (ATOMA_API_KEY).');
        }

        $model = trim((string) ($options['model'] ?? config('services.atoma.model', '')));
        if ($model === '') {
            throw new RuntimeException('Atoma model is not configured (ATOMA_MODEL).');
        }

        $body = [
            'model' => $model,
            'messages' => $messages,
            'temperature' => (float) ($options['temperature'] ?? 0.35),
            'max_tokens' => (int) ($options['max_tokens'] ?? 700),
            'stream' => false,
        ];

        try {
            $response = Http::acceptJson()
                ->asJson()
                ->withToken($apiKey)
                ->timeout((int) config('services.atoma.timeout', 60))
                ->connectTimeout(15)
                ->post($this->apiBase().'/v1/chat/completions', $body);
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

    private function apiBase(): string
    {
        return rtrim((string) config('services.atoma.api_base', 'https://api.atoma.network'), '/');
    }
}
