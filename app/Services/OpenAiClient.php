<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class OpenAiClient
{
    /**
     * @param  array<int, array{role: string, content: string}>  $messages
     * @return array{answer: string, model: string, usage: array<string, mixed>}
     */
    public function chat(string $instructions, array $messages, array $options = []): array
    {
        $apiKey = trim((string) config('services.openai.api_key', ''));
        if ($apiKey === '') {
            throw new RuntimeException('OpenAI API key is not configured (OPENAI_API_KEY).');
        }

        $model = trim((string) ($options['model'] ?? config('services.openai.model', '')));
        if ($model === '') {
            throw new RuntimeException('OpenAI model is not configured (OPENAI_MODEL).');
        }

        $body = [
            'model' => $model,
            'instructions' => $instructions,
            'input' => array_map(static fn (array $message): array => [
                'role' => $message['role'],
                'content' => [
                    [
                        'type' => 'input_text',
                        'text' => $message['content'],
                    ],
                ],
            ], $messages),
            'max_output_tokens' => (int) ($options['max_output_tokens'] ?? 900),
        ];

        try {
            $response = Http::acceptJson()
                ->asJson()
                ->withToken($apiKey)
                ->timeout((int) config('services.openai.timeout', 60))
                ->connectTimeout(15)
                ->post($this->apiBase().'/v1/responses', $body);
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

    private function apiBase(): string
    {
        return rtrim((string) config('services.openai.api_base', 'https://api.openai.com'), '/');
    }
}
