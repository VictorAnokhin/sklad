<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class TelegramBotService
{
    private string $token;

    private string $apiBase = 'https://api.telegram.org';

    public function __construct()
    {
        $this->token = trim((string) config('services.telegram.bot_token', ''));
    }

    // ── Проверка конфигурации ────────────────────────────────────────────

    /**
     * Проверить, что токен бота настроен.
     */
    public function isConfigured(): bool
    {
        return $this->token !== '';
    }

    /**
     * Выбросить исключение, если токен не настроен.
     */
    private function ensureConfigured(): void
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('Telegram bot token is not configured (TELEGRAM_BOT_TOKEN).');
        }
    }

    // ── Отправка сообщений ────────────────────────────────────────────────

    /**
     * Отправить текстовое сообщение в чат.
     *
     * @param  array<string, mixed>  $extra  Дополнительные параметры (parse_mode, reply_markup и т.д.)
     * @return array<string, mixed> Ответ Telegram API
     */
    public function sendMessage(int|string $chatId, string $text, array $extra = []): array
    {
        $this->ensureConfigured();

        return $this->post('sendMessage', array_merge([
            'chat_id' => $chatId,
            'text' => $text,
        ], $extra));
    }

    /**
     * Отправить Markdown-сообщение (без экранирования — вся разметка в $text).
     */
    public function sendMarkdown(int|string $chatId, string $text, array $extra = []): array
    {
        return $this->sendMessage($chatId, $text, array_merge([
            'parse_mode' => 'MarkdownV2',
        ], $extra));
    }

    /**
     * Отправить HTML-сообщение.
     */
    public function sendHtml(int|string $chatId, string $html, array $extra = []): array
    {
        return $this->sendMessage($chatId, $html, array_merge([
            'parse_mode' => 'HTML',
        ], $extra));
    }

    /**
     * Отправить действие "печатает...".
     *
     * @param  string  $action  typing | upload_photo | record_video | find_location | ...
     */
    public function sendChatAction(int|string $chatId, string $action = 'typing'): array
    {
        $this->ensureConfigured();

        return $this->post('sendChatAction', [
            'chat_id' => $chatId,
            'action' => $action,
        ]);
    }

    // ── Управление вебхуком ──────────────────────────────────────────────

    /**
     * Установить вебхук.
     *
     * @param  string  $url  Полный URL вебхука (с секретом)
     * @param  array<string, mixed>  $extra  Доп. параметры (secret_token, allowed_updates и т.д.)
     * @return array<string, mixed>
     */
    public function setWebhook(string $url, array $extra = []): array
    {
        $this->ensureConfigured();

        return $this->post('setWebhook', array_merge([
            'url' => $url,
        ], $extra));
    }

    /**
     * Удалить вебхук.
     *
     * @param  bool  $dropPendingUpdates  Удалить ожидающие обновления
     * @return array<string, mixed>
     */
    public function deleteWebhook(bool $dropPendingUpdates = false): array
    {
        $this->ensureConfigured();

        return $this->post('deleteWebhook', [
            'drop_pending_updates' => $dropPendingUpdates,
        ]);
    }

    /**
     * Получить информацию о текущем вебхуке.
     *
     * @return array<string, mixed>
     */
    public function getWebhookInfo(): array
    {
        $this->ensureConfigured();

        return $this->post('getWebhookInfo', []);
    }

    // ── Прочее ───────────────────────────────────────────────────────────

    /**
     * Установить меню команд бота (BotFather style).
     *
     * @param  array<int, array{command: string, description: string}>  $commands
     * @return array<string, mixed>
     */
    public function setMyCommands(array $commands): array
    {
        $this->ensureConfigured();

        return $this->post('setMyCommands', [
            'commands' => $commands,
        ]);
    }

    // ── HTTP ──────────────────────────────────────────────────────────────

    /**
     * Выполнить POST-запрос к Telegram Bot API.
     *
     * @param  string  $method  Название метода Telegram API (sendMessage, setWebhook, ...)
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function post(string $method, array $data): array
    {
        $url = "{$this->apiBase}/bot{$this->token}/{$method}";

        try {
            $response = Http::acceptJson()
                ->asJson()
                ->timeout(15)
                ->connectTimeout(10)
                ->post($url, $data);
        } catch (ConnectionException|Throwable $e) {
            Log::error('Telegram API request failed.', [
                'method' => $method,
                'error' => $e->getMessage(),
            ]);
            throw new RuntimeException("Telegram API request failed: {$e->getMessage()}", 0, $e);
        }

        $payload = $response->json();

        if (! $response->successful() || ! is_array($payload)) {
            $error = is_array($payload)
                ? ($payload['description'] ?? $response->body())
                : $response->body();
            Log::error('Telegram API error.', [
                'method' => $method,
                'status' => $response->status(),
                'error' => $error,
            ]);
            throw new RuntimeException("Telegram API error (HTTP {$response->status()}): {$error}");
        }

        if (! ($payload['ok'] ?? false)) {
            $desc = $payload['description'] ?? 'unknown error';
            Log::warning('Telegram API returned not ok.', [
                'method' => $method,
                'description' => $desc,
            ]);
            throw new RuntimeException("Telegram API error: {$desc}");
        }

        return $payload['result'] ?? $payload;
    }
}
