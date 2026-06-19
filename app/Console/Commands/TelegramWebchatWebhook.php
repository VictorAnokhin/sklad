<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class TelegramWebchatWebhook extends Command
{
    protected $signature = 'telegram:webchat-webhook
        {action=info : set, info, or delete}
        {--url= : Override public webhook URL}';

    protected $description = 'Manage Telegram webhook for the website webchat operator bridge.';

    public function handle(): int
    {
        $token = trim((string) config('services.telegram_webchat.bot_token'));
        if ($token === '') {
            $this->error('Telegram webchat bot token is empty. Set TELEGRAM_WEBCHAT_BOT_TOKEN or TELEGRAM_BOT_TOKEN.');

            return self::FAILURE;
        }

        $action = strtolower((string) $this->argument('action'));

        return match ($action) {
            'set' => $this->setWebhook($token),
            'info' => $this->webhookInfo($token),
            'delete' => $this->deleteWebhook($token),
            default => $this->invalidAction($action),
        };
    }

    private function setWebhook(string $token): int
    {
        $url = trim((string) ($this->option('url') ?: $this->defaultWebhookUrl()));
        if ($url === '') {
            $this->error('Webhook URL is empty. Set SERVER_URL or APP_URL, or pass --url=...');

            return self::FAILURE;
        }

        $response = Http::timeout((int) config('services.telegram_webchat.timeout', 10))
            ->asForm()
            ->post($this->apiUrl($token, 'setWebhook'), [
                'url' => $url,
                'allowed_updates' => json_encode(['message'], JSON_THROW_ON_ERROR),
            ]);

        $this->line($response->body());

        return $response->successful() && (bool) ($response->json('ok') ?? false)
            ? self::SUCCESS
            : self::FAILURE;
    }

    private function webhookInfo(string $token): int
    {
        $response = Http::timeout((int) config('services.telegram_webchat.timeout', 10))
            ->get($this->apiUrl($token, 'getWebhookInfo'));

        $this->line($response->body());

        return $response->successful() ? self::SUCCESS : self::FAILURE;
    }

    private function deleteWebhook(string $token): int
    {
        $response = Http::timeout((int) config('services.telegram_webchat.timeout', 10))
            ->asForm()
            ->post($this->apiUrl($token, 'deleteWebhook'));

        $this->line($response->body());

        return $response->successful() && (bool) ($response->json('ok') ?? false)
            ? self::SUCCESS
            : self::FAILURE;
    }

    private function invalidAction(string $action): int
    {
        $this->error("Unknown action: {$action}. Use set, info, or delete.");

        return self::FAILURE;
    }

    private function defaultWebhookUrl(): string
    {
        $secret = trim((string) config('services.telegram_webchat.webhook_secret'));
        if ($secret === '') {
            return '';
        }

        $baseUrl = rtrim((string) env('SERVER_URL', config('app.url', '')), '/');
        if ($baseUrl === '') {
            return '';
        }

        return $baseUrl.'/api/telegram/webchat/webhook/'.$secret;
    }

    private function apiUrl(string $token, string $method): string
    {
        return 'https://api.telegram.org/bot'.$token.'/'.$method;
    }
}
