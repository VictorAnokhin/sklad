<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use SplFileObject;
use Throwable;

class ImportMahaTables extends Command
{
    protected $signature = 'db:import-maha
        {--path= : Path to the sql file}
        {--with-users : Import legacy users rows too}
        {--schema : Execute CREATE/ALTER statements from the dump}
        {--truncate : Truncate target tables before importing rows}
        {--dry-run : Parse and report without executing SQL}';

    protected $description = 'Imports maha_v3.sql into the current Laravel database using the existing schema';

    private array $rowCountCache = [];

    public function handle(): int
    {
        @ini_set('memory_limit', '512M');

        $sqlPath = $this->resolveSqlPath();
        if ($sqlPath === null) {
            $this->error('SQL file not found.');
            return self::FAILURE;
        }

        $this->info("Reading SQL from: {$sqlPath}");

        $dryRun = (bool) $this->option('dry-run');
        $withUsers = (bool) $this->option('with-users');
        $withSchema = (bool) $this->option('schema');
        $truncate = (bool) $this->option('truncate');

        $stats = [
            'source_statements' => 0,
            'selected_statements' => 0,
            'skipped_users' => 0,
            'skipped_non_empty' => 0,
            'skipped_missing_tables' => 0,
            'skipped_unsupported' => 0,
            'tables' => [],
            'truncate_tables' => [],
        ];

        $current = null;

        try {
            if (!$dryRun) {
                DB::beginTransaction();
                DB::statement('SET FOREIGN_KEY_CHECKS=0');
            }

            foreach ($this->iterateStatements($sqlPath) as $statement) {
                $stats['source_statements']++;
                $current = $this->prepareStatement($statement, $withUsers, $withSchema, $truncate, $stats);

                if ($current === null) {
                    continue;
                }

                $stats['selected_statements']++;

                if ($dryRun) {
                    continue;
                }

                if ($current['truncate_before'] !== null) {
                    DB::table($current['truncate_before'])->truncate();
                    $this->line("Truncated: {$current['truncate_before']}");
                }

                DB::unprepared($current['sql']);

                if ($stats['selected_statements'] % 25 === 0) {
                    $this->line('Executed statements: ' . $stats['selected_statements']);
                }
            }

            if (!$dryRun) {
                DB::statement('SET FOREIGN_KEY_CHECKS=1');
                DB::commit();
            }
        } catch (Throwable $e) {
            if (!$dryRun) {
                DB::rollBack();
                try {
                    DB::statement('SET FOREIGN_KEY_CHECKS=1');
                } catch (Throwable) {
                    // Ignore cleanup errors.
                }
            }

            $failed = $current['label'] ?? 'unknown statement';
            $this->error("Import failed on {$failed}: {$e->getMessage()}");
            return self::FAILURE;
        }

        $this->table(
            ['Metric', 'Value'],
            [
                ['source statements', (string) $stats['source_statements']],
                ['selected statements', (string) $stats['selected_statements']],
                ['selected tables', implode(', ', array_keys($stats['tables'])) ?: 'none'],
                ['skipped users statements', (string) $stats['skipped_users']],
                ['skipped non-empty inserts', (string) $stats['skipped_non_empty']],
                ['skipped missing tables', (string) $stats['skipped_missing_tables']],
                ['skipped unsupported', (string) $stats['skipped_unsupported']],
                ['truncate tables', implode(', ', $stats['truncate_tables']) ?: 'none'],
            ]
        );

        $this->info($dryRun ? 'Dry run complete.' : 'Import completed successfully.');
        return self::SUCCESS;
    }

    private function resolveSqlPath(): ?string
    {
        $paths = array_filter([
            $this->option('path'),
            base_path('maha_v3.sql'),
            base_path('filtered_maha.sql'),
            base_path('filtered_maha_2.sql'),
            base_path('filtered_maha_no_data.sql'),
            '/var/www/html/maha_v3.sql',
            '/tmp/maha_v3.sql',
        ]);

        foreach ($paths as $path) {
            if (File::exists($path)) {
                return $path;
            }
        }

        return null;
    }

    private function iterateStatements(string $path): \Generator
    {
        $buffer = '';
        $file = new SplFileObject($path, 'r');

        while (!$file->eof()) {
            $line = str_replace("\r\n", "\n", (string) $file->fgets());
            $trimmed = trim($line);

            if ($trimmed === '' || str_starts_with($trimmed, '--')) {
                continue;
            }

            $buffer .= $line . "\n";

            if (str_ends_with($trimmed, ';')) {
                $statement = trim($buffer);
                if ($statement !== '') {
                    yield $statement;
                }
                $buffer = '';
            }
        }

        if (trim($buffer) !== '') {
            yield trim($buffer);
        }
    }

    private function prepareStatement(string $statement, bool $withUsers, bool $withSchema, bool $truncate, array &$stats): ?array
    {
        $parsed = $this->classifyStatement($statement);
        if ($parsed === null) {
            if ($this->isSessionStatement($statement)) {
                return ['sql' => $statement, 'label' => 'session statement', 'truncate_before' => null];
            }

            $stats['skipped_unsupported']++;
            return null;
        }

        [$kind, $table] = $parsed;

        if ($table === 'users' && !$withUsers) {
            $stats['skipped_users']++;
            return null;
        }

        if (!$this->shouldProcessTable($table)) {
            $stats['skipped_unsupported']++;
            return null;
        }

        if (!$withSchema && in_array($kind, ['create', 'alter', 'drop'], true)) {
            return null;
        }

        if (!Schema::hasTable($table)) {
            $stats['skipped_missing_tables']++;
            return null;
        }

        $truncateBefore = null;
        if ($kind === 'insert') {
            if (!$truncate && $this->tableRowCount($table) > 0) {
                $stats['skipped_non_empty']++;
                return null;
            }

            if ($truncate && !in_array($table, $stats['truncate_tables'], true)) {
                $stats['truncate_tables'][] = $table;
                $truncateBefore = $table;
                $this->rowCountCache[$table] = 0;
            }
        }

        $stats['tables'][$table] = true;

        return [
            'sql' => $statement,
            'label' => "{$kind}:{$table}",
            'truncate_before' => $truncateBefore,
        ];
    }

    private function classifyStatement(string $statement): ?array
    {
        $patterns = [
            'insert' => '/^INSERT(?: IGNORE)? INTO `([^`]+)`/i',
            'create' => '/^CREATE TABLE(?: IF NOT EXISTS)? `([^`]+)`/i',
            'alter' => '/^ALTER TABLE `([^`]+)`/i',
            'drop' => '/^DROP TABLE IF EXISTS `([^`]+)`/i',
        ];

        foreach ($patterns as $kind => $pattern) {
            if (preg_match($pattern, $statement, $matches) === 1) {
                return [$kind, $matches[1]];
            }
        }

        return null;
    }

    private function isSessionStatement(string $statement): bool
    {
        return preg_match('/^(SET\s|\/\*!\d{5}\sSET\s)/i', $statement) === 1;
    }

    private function shouldProcessTable(string $table): bool
    {
        $blocked = [
            'migrations',
            'failed_jobs',
            'password_reset_tokens',
            'personal_access_tokens',
            'jobs',
            'job_batches',
            'cache',
            'cache_locks',
            'sessions',
        ];

        return !in_array($table, $blocked, true);
    }

    private function tableRowCount(string $table): int
    {
        if (!array_key_exists($table, $this->rowCountCache)) {
            $this->rowCountCache[$table] = (int) DB::table($table)->count();
        }

        return $this->rowCountCache[$table];
    }
}
