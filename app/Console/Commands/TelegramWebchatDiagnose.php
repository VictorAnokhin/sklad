<?php

namespace App\Console\Commands;

use App\Services\TelegramWebchatService;
use Illuminate\Console\Command;

class TelegramWebchatDiagnose extends Command
{
    protected $signature = 'telegram:webchat-diagnose
        {domain : Website domain sent by the webchat, for example av8.fund}
        {--fid=0 : Project fid for the check}';

    protected $description = 'Diagnose why Telegram webchat transport is enabled or disabled for a website domain.';

    public function handle(TelegramWebchatService $telegramWebchat): int
    {
        $domain = trim((string) $this->argument('domain'));
        $fid = (int) $this->option('fid');

        $diagnostics = $telegramWebchat->diagnostics([
            'site_domain' => $domain,
        ], $fid);

        $this->line(json_encode($diagnostics, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        return (bool) ($diagnostics['enabled'] ?? false) ? self::SUCCESS : self::FAILURE;
    }
}
