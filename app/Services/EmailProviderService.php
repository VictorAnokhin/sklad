<?php

namespace App\Services;

use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class EmailProviderService
{
    public function __construct(private readonly HttpFactory $http)
    {
    }

    public function configured(): bool
    {
        return $this->apiKey() !== '' && $this->fromEmail() !== '';
    }

    public function settings(): array
    {
        return [
            'provider' => $this->provider(),
            'api_key_hint' => $this->mask($this->apiKey()),
            'from_email' => $this->fromEmail(),
            'from_name' => $this->fromName(),
            'configured' => $this->configured(),
        ];
    }

    public function send(string $to, string $subject, string $html, ?string $text = null): array
    {
        $to = trim($to);
        $subject = trim($subject);
        $html = trim($html);

        if (! filter_var($to, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Некорректный email получателя.');
        }
        if ($subject === '' || $html === '') {
            throw new RuntimeException('Заполните тему и текст письма.');
        }

        return match ($this->provider()) {
            'brevo' => $this->sendBrevo($to, $subject, $html, $text),
            default => $this->sendResend($to, $subject, $html, $text),
        };
    }

    private function sendResend(string $to, string $subject, string $html, ?string $text): array
    {
        $apiKey = $this->apiKey();
        if ($apiKey === '') {
            throw new RuntimeException('API key Resend не настроен.');
        }

        $payload = [
            'from' => $this->fromAddress(),
            'to' => [$to],
            'subject' => $subject,
            'html' => $html,
        ];
        if ($text !== null && trim($text) !== '') {
            $payload['text'] = trim($text);
        }

        $response = $this->http
            ->timeout(15)
            ->withToken($apiKey)
            ->acceptJson()
            ->post('https://api.resend.com/emails', $payload);

        if (! $response->successful()) {
            throw new RuntimeException($this->responseError($response->json(), $response->body(), $response->status()));
        }

        return $response->json() ?: ['success' => true];
    }

    private function sendBrevo(string $to, string $subject, string $html, ?string $text): array
    {
        $apiKey = $this->apiKey();
        if ($apiKey === '') {
            throw new RuntimeException('API key Brevo не настроен.');
        }

        $payload = [
            'sender' => [
                'email' => $this->fromEmail(),
                'name' => $this->fromName() ?: $this->fromEmail(),
            ],
            'to' => [['email' => $to]],
            'subject' => $subject,
            'htmlContent' => $html,
        ];
        if ($text !== null && trim($text) !== '') {
            $payload['textContent'] = trim($text);
        }

        $response = $this->http
            ->timeout(15)
            ->withHeaders(['api-key' => $apiKey])
            ->acceptJson()
            ->post('https://api.brevo.com/v3/smtp/email', $payload);

        if (! $response->successful()) {
            throw new RuntimeException($this->responseError($response->json(), $response->body(), $response->status()));
        }

        return $response->json() ?: ['success' => true];
    }

    private function provider(): string
    {
        $provider = strtolower($this->setting('provider', (string) config('services.email_provider.provider', 'resend')));

        return in_array($provider, ['resend', 'brevo'], true) ? $provider : 'resend';
    }

    private function apiKey(): string
    {
        return $this->setting('api_key', (string) config('services.email_provider.api_key', ''));
    }

    private function fromEmail(): string
    {
        return $this->setting('from_email', (string) config('services.email_provider.from_email', ''));
    }

    private function fromName(): string
    {
        return $this->setting('from_name', (string) config('services.email_provider.from_name', config('app.name', 'AV8Capital')));
    }

    private function fromAddress(): string
    {
        $email = $this->fromEmail();
        $name = trim($this->fromName());

        return $name !== '' ? "{$name} <{$email}>" : $email;
    }

    private function setting(string $name, string $fallback = ''): string
    {
        if (! Schema::hasTable('conf') || ! Schema::hasColumn('conf', 'constanta')) {
            return trim($fallback);
        }

        $fid = (string) session('fid', '');
        $query = DB::table('conf')->where('type', 'email_provider')->where('name', $name);
        $row = (clone $query)->where('firma', $fid !== '' ? $fid : '0')->first()
            ?: (clone $query)->where('firma', 0)->first();

        return trim((string) ($row->constanta ?? $fallback));
    }

    private function mask(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        return str_repeat('*', max(4, min(12, strlen($value) - 4))) . substr($value, -4);
    }

    private function responseError(mixed $payload, string $body, int $status): string
    {
        if (is_array($payload)) {
            $message = $payload['message'] ?? $payload['error'] ?? $payload['code'] ?? '';
            if (is_string($message) && trim($message) !== '') {
                return trim($message);
            }
        }

        return 'Email provider error HTTP ' . $status . ': ' . mb_substr($body, 0, 300);
    }
}
