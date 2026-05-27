<?php

namespace App\Console\Commands;

use App\Services\FundPoolObjectService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MigrateFundPoolObjects extends Command
{
    protected $signature = 'fund:pools:migrate-objects
        {--file=database/data/fund_pool_object_migration.testnet.json : JSON migration plan}
        {--rpc= : Sui JSON-RPC URL}
        {--apply : Persist database updates (default is dry-run)}
        {--remap-events : Rewrite fund_pool_events.pool_object_id from legacy ids to new ids}';

    protected $description = 'Update fund_pools pool_object_id / pool_accounting_id after on-chain pool recreation.';

    public function handle(FundPoolObjectService $service): int
    {
        if (! Schema::hasTable('fund_pools')) {
            $this->error('fund_pools table is missing. Run migrations first.');

            return self::FAILURE;
        }

        $path = (string) $this->option('file');
        if (! is_file($path)) {
            $this->error("Migration file not found: {$path}");

            return self::FAILURE;
        }

        $payload = json_decode((string) file_get_contents($path), true);
        if (! is_array($payload)) {
            $this->error('Migration file must contain valid JSON.');

            return self::FAILURE;
        }

        $network = trim((string) ($payload['network'] ?? 'testnet')) ?: 'testnet';
        $expectedPackageId = $service->resolveExpectedPackageId((string) ($payload['expected_package_id'] ?? ''));
        if ($expectedPackageId === '') {
            $this->error('expected_package_id is missing in migration file and AV8_CAPITAL_PACKAGE_ID is not set.');

            return self::FAILURE;
        }

        $entries = is_array($payload['pools'] ?? null) ? $payload['pools'] : [];
        if ($entries === []) {
            $this->warn('No pools listed in migration file.');

            return self::SUCCESS;
        }

        $rpcUrl = $service->resolveRpcUrl((string) $this->option('rpc'));
        $apply = (bool) $this->option('apply');
        $remapEvents = (bool) $this->option('remap-events');

        $this->line('Mode: '.($apply ? 'APPLY' : 'DRY-RUN'));
        $this->line("Network: {$network}");
        $this->line('Expected package: '.$service->shortObjectId($expectedPackageId));
        $this->line("RPC: {$rpcUrl}");
        $this->newLine();

        $plannedUpdates = [];
        $hasErrors = false;

        foreach ($entries as $index => $entry) {
            if (! is_array($entry)) {
                $this->error('Entry #'.($index + 1).' is not an object.');
                $hasErrors = true;
                continue;
            }

            $id = (int) ($entry['id'] ?? 0);
            if ($id <= 0) {
                $this->error('Entry #'.($index + 1).' is missing fund_pools.id.');
                $hasErrors = true;
                continue;
            }

            $row = DB::table('fund_pools')->where('id', $id)->where('network', $network)->first();
            if (! $row) {
                $this->error("fund_pools id={$id} not found for network={$network}.");
                $hasErrors = true;
                continue;
            }

            $validationIssues = $service->validateMigrationEntry($rpcUrl, $entry, $expectedPackageId);
            if ($validationIssues !== []) {
                $this->error("Pool id={$id} ({$row->name}) failed on-chain validation:");
                foreach ($validationIssues as $issue) {
                    $this->line("  - {$issue}");
                }
                $hasErrors = true;
                continue;
            }

            $newPoolObjectId = $service->normalizeObjectId((string) $entry['pool_object_id']);
            $newAccountingId = $service->normalizeObjectId((string) $entry['pool_accounting_id']);
            $legacyPoolObjectId = $service->normalizeObjectId((string) ($entry['legacy_pool_object_id'] ?? $row->pool_object_id));
            $legacyAccountingId = $service->normalizeObjectId((string) ($entry['legacy_pool_accounting_id'] ?? $row->pool_accounting_id));

            $conflict = DB::table('fund_pools')
                ->where('network', $network)
                ->where('pool_object_id', $newPoolObjectId)
                ->where('id', '!=', $id)
                ->exists();
            if ($conflict) {
                $this->error("Pool object {$newPoolObjectId} is already used by another fund_pools row.");
                $hasErrors = true;
                continue;
            }

            $plannedUpdates[] = [
                'id' => $id,
                'name' => (string) $row->name,
                'legacy_pool_object_id' => $legacyPoolObjectId,
                'legacy_pool_accounting_id' => $legacyAccountingId,
                'pool_object_id' => $newPoolObjectId,
                'pool_accounting_id' => $newAccountingId,
                'package_id' => $expectedPackageId,
                'pool_registry_id' => $service->normalizeObjectId((string) ($entry['pool_registry_id'] ?? $row->pool_registry_id ?? config('services.av8_capital.pool_registry_id', ''))),
                'pool_admin_cap_id' => $service->normalizeObjectId((string) ($entry['pool_admin_cap_id'] ?? $row->pool_admin_cap_id ?? config('services.av8_capital.pool_admin_cap_id', ''))),
                'notes_append' => trim((string) ($entry['notes_append'] ?? sprintf(
                    'Migrated pool objects on %s from pool %s / accounting %s.',
                    now()->toDateString(),
                    $service->shortObjectId($legacyPoolObjectId),
                    $service->shortObjectId($legacyAccountingId)
                ))),
            ];
        }

        if ($hasErrors) {
            return self::FAILURE;
        }

        $this->table(
            ['id', 'name', 'old pool', 'new pool', 'old accounting', 'new accounting'],
            array_map(static fn (array $update) => [
                $update['id'],
                $update['name'],
                $service->shortObjectId($update['legacy_pool_object_id']),
                $service->shortObjectId($update['pool_object_id']),
                $service->shortObjectId($update['legacy_pool_accounting_id']),
                $service->shortObjectId($update['pool_accounting_id']),
            ], $plannedUpdates)
        );

        if (! $apply) {
            $this->newLine();
            $this->info('Dry-run complete. Re-run with --apply to persist changes.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($plannedUpdates, $remapEvents, $network, $service): void {
            foreach ($plannedUpdates as $update) {
                $row = DB::table('fund_pools')->where('id', $update['id'])->first();
                $notes = trim((string) ($row->notes ?? ''));
                if ($update['notes_append'] !== '') {
                    $notes = $notes === '' ? $update['notes_append'] : $notes."\n".$update['notes_append'];
                }

                DB::table('fund_pools')
                    ->where('id', $update['id'])
                    ->update([
                        'package_id' => $update['package_id'],
                        'pool_registry_id' => $update['pool_registry_id'] !== '' ? $update['pool_registry_id'] : (string) ($row->pool_registry_id ?? ''),
                        'pool_admin_cap_id' => $update['pool_admin_cap_id'] !== '' ? $update['pool_admin_cap_id'] : (string) ($row->pool_admin_cap_id ?? ''),
                        'pool_object_id' => $update['pool_object_id'],
                        'pool_accounting_id' => $update['pool_accounting_id'],
                        'notes' => $notes !== '' ? $notes : null,
                        'updated_at' => now(),
                    ]);

                if ($remapEvents && Schema::hasTable('fund_pool_events')) {
                    DB::table('fund_pool_events')
                        ->where('network', $network)
                        ->where('pool_object_id', $update['legacy_pool_object_id'])
                        ->update([
                            'pool_object_id' => $update['pool_object_id'],
                            'package_id' => $update['package_id'],
                            'updated_at' => now(),
                        ]);
                }
            }
        });

        $this->info('fund_pools object ids updated.');
        if ($remapEvents) {
            $this->info('fund_pool_events pool_object_id values remapped where applicable.');
        }
        $this->line('Next: php artisan fund:pools:audit-packages');
        $this->line('Next: php artisan fund:pools:sync-events --package='.$expectedPackageId);

        return self::SUCCESS;
    }
}
