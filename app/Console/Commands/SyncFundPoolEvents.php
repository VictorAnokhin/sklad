<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

class SyncFundPoolEvents extends Command
{
    protected $signature = 'fund:pools:sync-events
        {--network=testnet : Logical network label saved in the database}
        {--package= : AV8 Capital package id; defaults to distinct fund_pools package_id values}
        {--rpc= : Sui JSON-RPC URL; defaults to SUI_RPC_URL or public testnet fullnode}
        {--limit=50 : Sui page size per event type}
        {--pages=20 : Max pages per event type}';

    protected $description = 'Sync AV8 pool_manager Move events from Sui into fund_pool_events.';

    private const EVENT_TYPES = [
        'deposit' => 'PoolDepositEvent',
        'withdraw' => 'PoolWithdrawEvent',
        'update' => 'PoolUpdatedEvent',
    ];

    public function handle(): int
    {
        if (! Schema::hasTable('fund_pool_events')) {
            $this->error('fund_pool_events table is missing. Run migrations first.');
            return self::FAILURE;
        }

        $packages = $this->packages();
        if ($packages === []) {
            $this->warn('No package id found. Pass --package=0x... or create fund_pools records first.');
            return self::SUCCESS;
        }

        $network = trim((string) $this->option('network')) ?: 'testnet';
        $rpc = $this->rpcUrl();
        $limit = max(1, min(100, (int) $this->option('limit')));
        $maxPages = max(1, (int) $this->option('pages'));
        $total = 0;

        foreach ($packages as $packageId) {
            foreach (self::EVENT_TYPES as $eventType => $eventName) {
                $moveEventType = "{$packageId}::pool_manager::{$eventName}";
                $saved = $this->syncMoveEventType($rpc, $network, $packageId, $eventType, $moveEventType, $limit, $maxPages);
                $total += $saved;
                $this->line("{$moveEventType}: {$saved} saved/updated");
            }
        }

        $this->info("Pool events sync complete. {$total} rows saved/updated.");
        return self::SUCCESS;
    }

    /**
     * @return array<int, string>
     */
    private function packages(): array
    {
        $option = strtolower(trim((string) $this->option('package')));
        if ($option !== '') {
            return [$option];
        }

        if (! Schema::hasTable('fund_pools')) {
            return [];
        }

        return DB::table('fund_pools')
            ->where('package_id', '!=', '')
            ->distinct()
            ->pluck('package_id')
            ->map(fn ($value) => strtolower(trim((string) $value)))
            ->filter()
            ->values()
            ->all();
    }

    private function rpcUrl(): string
    {
        $fromOption = trim((string) $this->option('rpc'));
        if ($fromOption !== '') {
            return $fromOption;
        }

        $configured = trim((string) config('services.sui.rpc_url', ''));
        return $configured !== '' ? $configured : 'https://fullnode.testnet.sui.io:443';
    }

    private function syncMoveEventType(
        string $rpc,
        string $network,
        string $packageId,
        string $eventType,
        string $moveEventType,
        int $limit,
        int $maxPages,
    ): int {
        $cursor = null;
        $saved = 0;

        for ($page = 0; $page < $maxPages; $page++) {
            $result = $this->rpc($rpc, 'suix_queryEvents', [
                ['MoveEventType' => $moveEventType],
                $cursor,
                $limit,
                false,
            ]);

            $events = is_array($result['data'] ?? null) ? $result['data'] : [];
            if ($events === []) {
                break;
            }

            foreach ($events as $event) {
                if (! is_array($event)) {
                    continue;
                }

                $this->upsertEvent($network, $packageId, $eventType, $moveEventType, $event);
                $saved++;
            }

            $hasNextPage = (bool) ($result['hasNextPage'] ?? false);
            $cursor = $result['nextCursor'] ?? null;
            if (! $hasNextPage || $cursor === null) {
                break;
            }
        }

        return $saved;
    }

    /**
     * @param  array<int, mixed>  $params
     * @return array<string, mixed>
     */
    private function rpc(string $rpc, string $method, array $params): array
    {
        $response = Http::acceptJson()
            ->timeout(90)
            ->connectTimeout(15)
            ->post($rpc, [
                'jsonrpc' => '2.0',
                'method' => $method,
                'params' => $params,
                'id' => 1,
            ]);

        $payload = $response->json();
        if (! is_array($payload)) {
            throw new \RuntimeException('Sui fullnode returned non-JSON (HTTP '.$response->status().').');
        }

        if (isset($payload['error']) && is_array($payload['error'])) {
            throw new \RuntimeException((string) ($payload['error']['message'] ?? 'Sui JSON-RPC error'));
        }

        $result = $payload['result'] ?? [];
        return is_array($result) ? $result : [];
    }

    /**
     * @param array<string, mixed> $event
     */
    private function upsertEvent(string $network, string $packageId, string $eventType, string $moveEventType, array $event): void
    {
        $id = is_array($event['id'] ?? null) ? $event['id'] : [];
        $parsed = is_array($event['parsedJson'] ?? null) ? $event['parsedJson'] : [];
        $txDigest = (string) ($id['txDigest'] ?? $event['id']['txDigest'] ?? '');
        $eventSeq = (int) ($id['eventSeq'] ?? 0);
        $poolObjectId = $this->normalizeObjectId((string) ($parsed['pool_id'] ?? ''));
        $timestampMs = (string) ($event['timestampMs'] ?? '');

        DB::table('fund_pool_events')->updateOrInsert(
            [
                'tx_digest' => $txDigest,
                'event_seq' => $eventSeq,
            ],
            [
                'network' => $network,
                'package_id' => $packageId,
                'event_type' => $eventType,
                'move_event_type' => $moveEventType,
                'checkpoint' => isset($event['checkpoint']) ? (int) $event['checkpoint'] : null,
                'pool_object_id' => $poolObjectId,
                'owner_address' => $this->normalizeObjectId((string) ($parsed['owner'] ?? '')),
                'amount_usdc' => (string) ($parsed['amount_usdc'] ?? '0'),
                'pool_shares' => (string) ($parsed['pool_shares'] ?? '0'),
                'burned_pool_shares' => (string) ($parsed['burned_pool_shares'] ?? '0'),
                'balance_usdc' => (string) ($parsed['balance_usdc'] ?? '0'),
                'active' => array_key_exists('active', $parsed) ? (bool) $parsed['active'] : null,
                'target_apy_bps' => isset($parsed['target_apy_bps']) ? (int) $parsed['target_apy_bps'] : null,
                'realized_apy_bps' => isset($parsed['realized_apy_bps']) ? (int) $parsed['realized_apy_bps'] : null,
                'min_deposit_usdc' => isset($parsed['min_deposit_usdc']) ? (string) $parsed['min_deposit_usdc'] : null,
                'max_weight_bps' => isset($parsed['max_weight_bps']) ? (int) $parsed['max_weight_bps'] : null,
                'raw_event' => json_encode($event, JSON_UNESCAPED_SLASHES),
                'event_at' => ctype_digit($timestampMs) ? Carbon::createFromTimestampMs((int) $timestampMs) : null,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }

    private function normalizeObjectId(string $value): string
    {
        $value = strtolower(trim($value));
        if (preg_match('/^0x([a-f0-9]{1,64})$/', $value, $matches)) {
            return '0x'.str_pad($matches[1], 64, '0', STR_PAD_LEFT);
        }

        return $value;
    }
}
