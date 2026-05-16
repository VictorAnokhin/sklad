<?php

namespace App\Console\Commands;

use App\Services\TelegramBotService;
use Illuminate\Console\Command;
use Throwable;

class TelegramSetWebhook extends Command
{
    protected $signature = 'telegram:set-webhook
        {url? : Публичный URL сервера (https://example.com)}
        {--secret= : Секретный ключ для вебхука (если не указан, берётся из .env)}
        {--drop : Сбросить ожидающие обновления}
        {--delete : Удалить текущий вебхук (вместо установки)}
        {--info : Показать информацию о текущем вебхуке}
    ';

    protected $description = 'Установить/удалить/проверить вебхук Telegram бота';

    /**
     * Выполнить команду.
     */
    public function handle(TelegramBotService $bot): int
    {
        // ── Проверка токена ──────────────────────────────────────────────
        if (! $bot->isConfigured()) {
            $this->error('TELEGRAM_BOT_TOKEN не настроен в .env');
            return self::FAILURE;
        }

        // ── Информация о текущем вебхуке ─────────────────────────────────
        if ($this->option('info')) {
            return $this->showWebhookInfo($bot);
        }

        // ── Удаление вебхука ─────────────────────────────────────────────
        if ($this->option('delete')) {
            return $this->deleteWebhook($bot);
        }

        // ── Установка вебхука ─────────────────────────────────────────────
        return $this->setupWebhook($bot);
    }

    /**
     * Показать информацию о текущем вебхуке.
     */
    private function showWebhookInfo(TelegramBotService $bot): int
    {
        $this->components->task('Получение информации о вебхуке');

        try {
            $info = $bot->getWebhookInfo();
        } catch (Throwable $e) {
            $this->error("Ошибка: {$e->getMessage()}");
            return self::FAILURE;
        }

        $this->newLine();
        $this->line('📡 Текущий вебхук:');
        $this->line("  URL:        " . ($info['url'] ?: '(не установлен)'));
        $this->line("  Активен:    " . ($info['has_custom_certificate'] ? 'Да (сертификат)' : 'Нет'));
        $this->line("  Ожидающих:  " . ($info['pending_update_count'] ?? 0));
        $this->line("  Последняя ошибка: " . (!empty($info['last_error_date']) ? date('Y-m-d H:i:s', $info['last_error_date']) : '—'));
        $this->line("  Сообщение ошибки: " . ($info['last_error_message'] ?? '—'));
        $this->newLine();

        return self::SUCCESS;
    }

    /**
     * Удалить вебхук.
     */
    private function deleteWebhook(TelegramBotService $bot): int
    {
        $drop = $this->option('drop');

        $this->components->task('Удаление вебхука');

        try {
            $result = $bot->deleteWebhook($drop);
            $this->info('✓ Вебхук успешно удалён' . ($drop ? ' (ожидающие обновления сброшены)' : ''));
            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error("Ошибка при удалении вебхука: {$e->getMessage()}");
            return self::FAILURE;
        }
    }

    /**
     * Установить вебхук.
     */
    private function setupWebhook(TelegramBotService $bot): int
    {
        // ── Определяем URL сервера ───────────────────────────────────────
        $serverUrl = $this->argument('url') ?: config('services.telegram.server_url', '');

        if ($serverUrl === '') {
            $this->error('Не указан URL сервера.');
            $this->line('Укажите URL как аргумент: php artisan telegram:set-webhook https://your-server.com');
            $this->line('Или установите SERVER_URL в .env');
            return self::FAILURE;
        }

        // ── Определяем секретный ключ ────────────────────────────────────
        $secret = $this->option('secret') ?: config('services.telegram.webhook_secret', '');

        if ($secret === '') {
            $this->error('Не указан секретный ключ.');
            $this->line('Укажите --secret=... или установите WEBHOOK_SECRET в .env');
            return self::FAILURE;
        }

        // ── Формируем URL вебхука ────────────────────────────────────────
        $serverUrl = rtrim($serverUrl, '/');
        $webhookUrl = "{$serverUrl}/api/telegram/webhook/{$secret}";

        $this->components->twoColumnDetail('Адрес вебхука', $webhookUrl);

        // ── Устанавливаем вебхук ─────────────────────────────────────────
        $this->components->task('Установка вебхука');

        try {
            $drop = $this->option('drop');
            $result = $bot->setWebhook($webhookUrl, [
                'drop_pending_updates' => $drop,
                'allowed_updates' => ['message'],
            ]);
        } catch (Throwable $e) {
            $this->error("Ошибка при установке вебхука: {$e->getMessage()}");
            return self::FAILURE;
        }

        $this->info('✓ Вебхук успешно установлен!');
        $this->line("  URL: {$webhookUrl}");

        if ($drop) {
            $this->line('  Ожидающие обновления сброшены.');
        }

        // ── Устанавливаем команды бота ───────────────────────────────────
        $this->components->task('Установка команд бота');

        try {
            $bot->setMyCommands([
                ['command' => 'start', 'description' => '🚀 Запустить бота / приветствие'],
                ['command' => 'help', 'description' => '📋 Список команд'],
                ['command' => 'new', 'description' => '🔄 Начать новый диалог'],
                ['command' => 'clear', 'description' => '🧹 Очистить историю диалога'],
            ]);
            $this->info('✓ Команды бота установлены');
        } catch (Throwable $e) {
            $this->warn("Не удалось установить команды бота: {$e->getMessage()}");
        }

        return self::SUCCESS;
    }
}
