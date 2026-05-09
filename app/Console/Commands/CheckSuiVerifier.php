<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class CheckSuiVerifier extends Command
{
    protected $signature = 'sui:check-verifier';

    protected $description = 'Check Node.js dependencies required for Sui wallet signature verification.';

    public function handle(): int
    {
        $node = trim((string) config('services.sui.verify_node_binary', 'node'));
        $script = base_path('scripts/verify-sui-personal-message.mjs');

        $this->line('Node binary: ' . $node);
        $this->line('Verifier script: ' . $script);

        if (! is_file($script)) {
            $this->error('Verifier script is missing.');

            return self::FAILURE;
        }

        if (! $this->runCheck([$node, '-v'], 'Node.js')) {
            $this->warn('Install Node.js on the API server, or set SUI_VERIFY_NODE_BINARY to the absolute node path.');

            return self::FAILURE;
        }

        if (! $this->runCheck([$node, '-e', "import('@mysten/sui/verify').then(() => console.log('ok'))"], '@mysten/sui verifier import')) {
            $this->warn('Run `npm install` or `npm ci --omit=dev` in the laravel-api directory on the API server.');

            return self::FAILURE;
        }

        $this->info('Sui verifier dependencies are available.');

        return self::SUCCESS;
    }

    /**
     * @param array<int, string> $command
     */
    private function runCheck(array $command, string $label): bool
    {
        $process = new Process($command);
        $process->setWorkingDirectory(base_path());
        $process->setTimeout(20);
        $process->run();

        if ($process->isSuccessful()) {
            $output = trim($process->getOutput());
            $this->info($label . ': OK' . ($output !== '' ? ' (' . $output . ')' : ''));

            return true;
        }

        $this->error($label . ': FAILED');
        $stderr = trim($process->getErrorOutput());
        $stdout = trim($process->getOutput());
        if ($stderr !== '') {
            $this->line($stderr);
        }
        if ($stdout !== '') {
            $this->line($stdout);
        }

        return false;
    }
}
