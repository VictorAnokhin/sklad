<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportLegacyUsers extends Command
{
    protected $signature = 'db:import-legacy-users
        {--path= : Path to import_users.sql or maha_v3.sql}
        {--dry-run : Parse and report without writing to DB}';

    protected $description = 'Imports legacy users into the current Laravel users table';

    public function handle(): int
    {
        $path = $this->resolveSqlPath();
        if ($path === null) {
            $this->error('Users SQL file not found.');
            return self::FAILURE;
        }

        $this->info("Reading legacy users from: {$path}");

        $sql = file_get_contents($path);
        if ($sql === false) {
            $this->error('Unable to read SQL file.');
            return self::FAILURE;
        }

        [$columns, $rows] = $this->extractRows($sql);
        if ($columns === [] || $rows === []) {
            $this->warn('No legacy users INSERT statement found.');
            return self::SUCCESS;
        }

        $existingEmails = DB::table('users')
            ->select('id', 'email')
            ->whereNotNull('email')
            ->get()
            ->mapWithKeys(fn($row) => [mb_strtolower(trim((string) $row->email)) => (int) $row->id])
            ->all();

        $prepared = [];
        foreach ($rows as $row) {
            $prepared[] = $this->mapLegacyRow($columns, $row, $existingEmails);
        }

        $dryRun = (bool) $this->option('dry-run');

        $this->table(
            ['Metric', 'Value'],
            [
                ['legacy rows parsed', (string) count($rows)],
                ['rows prepared', (string) count($prepared)],
                ['mode', $dryRun ? 'dry-run' : 'import'],
            ]
        );

        if ($dryRun) {
            $this->info('Dry run complete.');
            return self::SUCCESS;
        }

        $chunks = array_chunk($prepared, 200);
        $updateColumns = array_values(array_diff(array_keys($prepared[0]), ['id']));

        foreach ($chunks as $index => $chunk) {
            DB::table('users')->upsert($chunk, ['id'], $updateColumns);
            $this->line('Imported user chunk: ' . ($index + 1) . '/' . count($chunks));
        }

        $this->info('Legacy users imported successfully.');
        return self::SUCCESS;
    }

    private function resolveSqlPath(): ?string
    {
        $paths = array_filter([
            $this->option('path'),
            base_path('import_users.sql'),
            base_path('maha_v3.sql'),
            '/var/www/html/import_users.sql',
            '/var/www/html/maha_v3.sql',
        ]);

        foreach ($paths as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        return null;
    }

    private function extractRows(string $sql): array
    {
        if (!preg_match_all('/INSERT INTO `users` \((.*?)\) VALUES\s*(.*?);/is', $sql, $matches, PREG_SET_ORDER)) {
            return [[], []];
        }

        $columns = [];
        $rows = [];

        foreach ($matches as $match) {
            $statementColumns = array_map(
                static fn(string $column) => trim($column, " `\t\n\r\0\x0B"),
                explode(',', $match[1])
            );

            if ($columns === []) {
                $columns = $statementColumns;
            }

            $tuples = $this->splitTuples($match[2]);
            foreach ($tuples as $tuple) {
                $rows[] = str_getcsv($tuple, ',', "'", '\\');
            }
        }

        return [$columns, $rows];
    }

    private function splitTuples(string $valuesSql): array
    {
        $tuples = [];
        $buffer = '';
        $depth = 0;
        $inString = false;
        $escaped = false;

        $length = strlen($valuesSql);
        for ($i = 0; $i < $length; $i++) {
            $char = $valuesSql[$i];

            if ($inString) {
                $buffer .= $char;
                if ($escaped) {
                    $escaped = false;
                    continue;
                }
                if ($char === '\\') {
                    $escaped = true;
                    continue;
                }
                if ($char === "'") {
                    $inString = false;
                }
                continue;
            }

            if ($char === "'") {
                $inString = true;
                $buffer .= $char;
                continue;
            }

            if ($char === '(') {
                if ($depth === 0) {
                    $buffer = '';
                } else {
                    $buffer .= $char;
                }
                $depth++;
                continue;
            }

            if ($char === ')') {
                $depth--;
                if ($depth === 0) {
                    $tuples[] = $buffer;
                    $buffer = '';
                } else {
                    $buffer .= $char;
                }
                continue;
            }

            if ($depth > 0) {
                $buffer .= $char;
            }
        }

        return $tuples;
    }

    private function mapLegacyRow(array $columns, array $row, array &$usedEmails): array
    {
        $legacy = [];
        foreach ($columns as $index => $column) {
            $legacy[$column] = $this->normalizeValue($row[$index] ?? null);
        }

        $id = (int) ($legacy['id'] ?? 0);
        $rawEmail = mb_strtolower(trim((string) ($legacy['email'] ?? '')));
        $email = $this->uniqueEmail($rawEmail, $id, $usedEmails);

        [$password, $pass] = $this->resolvePasswords((string) ($legacy['pass'] ?? ''));

        $idstatus = $this->normalizeStatus($legacy['ustype'] ?? null);
        $timestamp = $this->legacyTimestamp($legacy['date'] ?? null, $legacy['time'] ?? null);

        return [
            'id' => $id,
            'fid' => (string) ($legacy['firma'] ?? $legacy['idfirma'] ?? ''),
            'firmuser' => (string) ($legacy['firmuser'] ?? ''),
            'firmuserall' => (string) ($legacy['firmuserall'] ?? ''),
            'login' => trim((string) ($legacy['login'] ?? '')),
            'pass' => $pass,
            'password' => $password,
            'ustype' => (string) ($legacy['ustype'] ?? ''),
            'tgroup' => (int) ($legacy['tgroup'] ?? 0),
            'region' => (string) ($legacy['region'] ?? ''),
            'city' => (string) ($legacy['city'] ?? ''),
            'poshta' => (string) ($legacy['poshta'] ?? ''),
            'direktor' => (string) ($legacy['direktor'] ?? ''),
            'orgname' => (string) ($legacy['orgname'] ?? ''),
            'kod1' => (string) ($legacy['kod1'] ?? ''),
            'kod2' => (string) ($legacy['kod2'] ?? ''),
            'pp' => (string) ($legacy['pp'] ?? ''),
            'bank' => (string) ($legacy['bank'] ?? ''),
            'mfo' => (string) ($legacy['mfo'] ?? ''),
            'name' => (string) ($legacy['name'] ?? ''),
            'secondname' => (string) ($legacy['secondname'] ?? ''),
            'fathername' => (string) ($legacy['fathername'] ?? ''),
            'name2' => (string) ($legacy['name2'] ?? ''),
            'address' => (string) ($legacy['address'] ?? ''),
            'phone2' => (string) ($legacy['phone2'] ?? ''),
            'phone1' => (string) ($legacy['phone1'] ?? ''),
            'phone' => (string) ($legacy['phone'] ?? ''),
            'email' => $email,
            'hbd' => (string) ($legacy['hbd'] ?? ''),
            'firma' => (string) ($legacy['firma'] ?? $legacy['idfirma'] ?? 0),
            'status' => (int) ($legacy['status'] ?? 0),
            'idstatus' => $idstatus,
            'idkassa' => (string) ($legacy['kassa'] ?? 0),
            'idsklad' => (string) ($legacy['sklad'] ?? 0),
            'idreestr' => (string) ($legacy['reestr'] ?? 0),
            'kassa' => (int) ($legacy['kassa'] ?? 0),
            'sklad' => (int) ($legacy['sklad'] ?? 0),
            'reestr' => (int) ($legacy['reestr'] ?? 0),
            'bonus' => (float) ($legacy['bonus'] ?? 0),
            'summa' => (float) ($legacy['summa'] ?? 0),
            'website' => (string) ($legacy['website'] ?? ''),
            'user' => (string) ($legacy['user'] ?? ''),
            'description' => (string) ($legacy['description'] ?? ''),
            'foto1' => (string) ($legacy['foto1'] ?? ''),
            'foto2' => (string) ($legacy['foto2'] ?? ''),
            'foto3' => (string) ($legacy['foto3'] ?? ''),
            'foto4' => (string) ($legacy['foto4'] ?? ''),
            'foto5' => (string) ($legacy['foto5'] ?? ''),
            'domen' => (string) ($legacy['domen'] ?? ''),
            'firm' => (int) ($legacy['firm'] ?? 0),
            'msg' => (int) ($legacy['msg'] ?? 0),
            'date' => $this->normalizeLegacyDate($legacy['date'] ?? null),
            'time' => $this->normalizeLegacyTime($legacy['time'] ?? null),
            'top' => (int) ($legacy['top'] ?? 0),
            'docs' => (int) ($legacy['docs'] ?? 0),
            'balans' => (float) ($legacy['balans'] ?? 0),
            'userid' => (int) ($legacy['userid'] ?? 0),
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ];
    }

    private function normalizeValue(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);
        if (strcasecmp($value, 'NULL') === 0) {
            return null;
        }

        return stripslashes($value);
    }

    private function uniqueEmail(string $email, int $id, array &$usedEmails): string
    {
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->reservePlaceholderEmail($id, $usedEmails);
        }

        if (isset($usedEmails[$email]) && $usedEmails[$email] !== $id) {
            return $this->reservePlaceholderEmail($id, $usedEmails);
        }

        $usedEmails[$email] = $id;
        return $email;
    }

    private function reservePlaceholderEmail(int $id, array &$usedEmails): string
    {
        $base = "legacy-user-{$id}@legacy.local";
        $email = $base;
        $suffix = 1;

        while (isset($usedEmails[$email]) && $usedEmails[$email] !== $id) {
            $email = "legacy-user-{$id}-{$suffix}@legacy.local";
            $suffix++;
        }

        $usedEmails[$email] = $id;
        return $email;
    }

    private function resolvePasswords(string $legacyPass): array
    {
        $legacyPass = trim($legacyPass);

        if ($legacyPass === '') {
            return ['', ''];
        }

        if ($this->isLaravelHash($legacyPass) || $this->looksLikeMd5($legacyPass)) {
            return [$legacyPass, $legacyPass];
        }

        return [$legacyPass, $legacyPass];
    }

    private function isLaravelHash(string $value): bool
    {
        $info = password_get_info($value);
        return ($info['algo'] ?? null) !== null;
    }

    private function looksLikeMd5(string $value): bool
    {
        return preg_match('/^[a-f0-9]{32}$/i', $value) === 1;
    }

    private function normalizeStatus(mixed $legacyStatus): int
    {
        $value = trim((string) $legacyStatus);
        return ctype_digit($value) ? (int) $value : 1;
    }

    private function legacyTimestamp(mixed $date, mixed $time): Carbon
    {
        $normalizedDate = $this->normalizeLegacyDate($date);
        $normalizedTime = $this->normalizeLegacyTime($time) ?? '00:00:00';

        if ($normalizedDate !== null) {
            try {
                return Carbon::createFromFormat('Y-m-d H:i:s', "{$normalizedDate} {$normalizedTime}");
            } catch (\Throwable) {
                // Fallback below.
            }
        }

        return now();
    }

    private function normalizeLegacyDate(mixed $date): ?string
    {
        $value = trim((string) $date);
        if ($value === '' || $value === '0000-00-00') {
            return null;
        }

        try {
            if (preg_match('/^\d{2}-\d{2}-\d{4}$/', $value) === 1) {
                return Carbon::createFromFormat('d-m-Y', $value)->format('Y-m-d');
            }

            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    private function normalizeLegacyTime(mixed $time): ?string
    {
        $value = trim((string) $time);
        if ($value === '' || $value === '00:00:00') {
            return '00:00:00';
        }

        return preg_match('/^\d{2}:\d{2}:\d{2}$/', $value) === 1 ? $value : '00:00:00';
    }
}
