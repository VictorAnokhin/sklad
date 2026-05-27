<?php

namespace App\Console\Commands;

use App\Services\FundPoolObjectService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AuditFundPoolPackages extends Command
{
    protected $signature = 'fund:pools:audit-packages
        {--network=testnet : Filter fund_pools.network}
        {--package= : Expected AV8 Capital package id; defaults to AV8_CAPITAL_PACKAGE_ID}
        {--rpc= : Sui JSON-RPC URL}
        {--id= : Audit only one fund_pools.id}';

    protected $description = 'Compare fund_pools object ids with on-chain Move package ids.';

    public function handle(FundPoolObjectService $service): int
    {
        if (! Schema::hasTable('fund_pools')) {
            $this->error('fund_pools table is missing. Run migrations first.');

            return self::FAILURE;
        }

        $expectedPackageId = $service->resolveExpectedPackageId((string) $this->option('package'));
        if ($expectedPackageId === '') {
            $this->error('Expected package id is missing. Pass --package=0x... or set AV8_CAPITAL_PACKAGE_ID.');

            return self::FAILURE;
        }

        $network = trim((string) $this->option('network')) ?: 'testnet';
        $rpcUrl = $service->resolveRpcUrl((string) $this->option('rpc'));
        $poolId = trim((string) $this->option('id'));

        $query = DB::table('fund_pools')
            ->where('network', $network)
            ->orderBy('id');

        if ($poolId !== '') {
            $query->where('id', (int) $poolId);
        }

        $rows = $query->get();
        if ($rows->isEmpty()) {
            $this->warn("No fund_pools rows found for network={$network}.");

            return self::SUCCESS;
        }

        $this->line("RPC: {$rpcUrl}");
        $this->line('Expected package: '.$service->shortObjectId($expectedPackageId));
        $this->newLine();

        $rowsForTable = [];
        $mismatchCount = 0;

        foreach ($rows as $row) {
            $audit = $service->auditPoolRow($rpcUrl, $row, $expectedPackageId);
            if ($audit['status'] !== 'ok') {
                $mismatchCount++;
            }

            $rowsForTable[] = [
                $audit['id'],
                $audit['name'],
                $service->shortObjectId($audit['pool_object_id']),
                $audit['pool_package_id'] ? $service->shortObjectId($audit['pool_package_id']) : '—',
                $audit['accounting_package_id'] ? $service->shortObjectId($audit['accounting_package_id']) : '—',
                $audit['status'],
                implode('; ', $audit['issues']),
            ];
        }

        $this->table(
            ['id', 'name', 'pool', 'pool pkg', 'acct pkg', 'status', 'issues'],
            $rowsForTable
        );

        if ($mismatchCount > 0) {
            $this->newLine();
            $this->warn("{$mismatchCount} pool(s) need migration.");
            $this->line('1. Create replacement Pool + PoolAccounting in Pool Admin on the expected package.');
            $this->line('2. Fill database/data/fund_pool_object_migration.testnet.json.example with new object ids.');
            $this->line('3. Run: php artisan fund:pools:migrate-objects --file=... --apply');

            return self::FAILURE;
        }

        $this->info('All audited pools match the expected package.');

        return self::SUCCESS;
    }
}
