<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class BlockchainMonitorController extends Controller
{
    public function page(): View
    {
        return view('pages.blockchain_monitor');
    }

    public function summary(): JsonResponse
    {
        $hasEvents = Schema::hasTable('fund_pool_events');
        $hasPools = Schema::hasTable('fund_pools');
        $latestEvent = $hasEvents
            ? DB::table('fund_pool_events')->orderByDesc('event_at')->orderByDesc('id')->first()
            : null;

        $latestAt = $latestEvent?->event_at ? Carbon::parse($latestEvent->event_at) : null;
        $lagSeconds = $latestAt ? max(0, now()->diffInSeconds($latestAt)) : null;
        $status = $this->listenerStatus($lagSeconds, $hasEvents);

        $eventCounts = $hasEvents
            ? DB::table('fund_pool_events')
                ->select('event_type', DB::raw('COUNT(*) as count'))
                ->groupBy('event_type')
                ->orderBy('event_type')
                ->get()
                ->mapWithKeys(fn ($row) => [(string) $row->event_type => (int) $row->count])
            : collect();

        $networks = $hasEvents
            ? DB::table('fund_pool_events')->distinct()->orderBy('network')->pluck('network')->filter()->values()
            : collect(['testnet']);

        return response()->json([
            'source' => [
                'kind' => 'laravel_polling',
                'label' => 'Laravel polling scanner',
                'poll_interval_seconds' => 300,
                'bridge_ready' => false,
            ],
            'listener' => [
                'status' => $status,
                'network' => (string) ($latestEvent->network ?? ($networks->first() ?: 'testnet')),
                'last_event_at' => $latestAt?->toIso8601String(),
                'lag_seconds' => $lagSeconds,
                'last_checkpoint' => isset($latestEvent->checkpoint) ? (int) $latestEvent->checkpoint : null,
                'last_tx_digest' => (string) ($latestEvent->tx_digest ?? ''),
            ],
            'metrics' => [
                'events_total' => $hasEvents ? (int) DB::table('fund_pool_events')->count() : 0,
                'pools_total' => $hasPools ? (int) DB::table('fund_pools')->count() : 0,
                'packages_total' => $hasEvents ? (int) DB::table('fund_pool_events')->distinct('package_id')->count('package_id') : 0,
                'events_by_type' => $eventCounts,
            ],
            'filters' => [
                'networks' => $networks,
                'event_types' => $eventCounts->keys()->values(),
                'pools' => $this->poolOptions(),
            ],
            'actions' => [
                'sync_now' => [
                    'enabled' => $hasEvents,
                    'method' => 'POST',
                    'url' => route('blockchain-monitor.sync'),
                ],
            ],
        ]);
    }

    public function events(Request $request): JsonResponse
    {
        if (! Schema::hasTable('fund_pool_events')) {
            return response()->json(['data' => [], 'meta' => ['total' => 0]]);
        }

        $limit = max(10, min(250, (int) $request->query('limit', 100)));
        $query = DB::table('fund_pool_events')
            ->when($request->filled('network'), fn ($q) => $q->where('network', (string) $request->query('network')))
            ->when($request->filled('event_type'), fn ($q) => $q->where('event_type', (string) $request->query('event_type')))
            ->when($request->filled('pool_object_id'), fn ($q) => $q->whereRaw('LOWER(pool_object_id) = ?', [strtolower((string) $request->query('pool_object_id'))]))
            ->when($request->filled('package_id'), fn ($q) => $q->whereRaw('LOWER(package_id) = ?', [strtolower((string) $request->query('package_id'))]))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.strtolower(trim((string) $request->query('q'))).'%';
                $q->where(function ($inner) use ($term) {
                    $inner->whereRaw('LOWER(tx_digest) LIKE ?', [$term])
                        ->orWhereRaw('LOWER(owner_address) LIKE ?', [$term])
                        ->orWhereRaw('LOWER(pool_object_id) LIKE ?', [$term])
                        ->orWhereRaw('LOWER(package_id) LIKE ?', [$term]);
                });
            });

        $rows = $query
            ->orderByDesc('event_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'network' => (string) $row->network,
                'event_type' => (string) $row->event_type,
                'move_event_type' => (string) $row->move_event_type,
                'tx_digest' => (string) $row->tx_digest,
                'event_seq' => (int) $row->event_seq,
                'checkpoint' => isset($row->checkpoint) ? (int) $row->checkpoint : null,
                'pool_object_id' => (string) $row->pool_object_id,
                'owner_address' => (string) $row->owner_address,
                'amount_usdc' => (string) $row->amount_usdc,
                'pool_shares' => (string) $row->pool_shares,
                'balance_usdc' => (string) $row->balance_usdc,
                'event_at' => $row->event_at ? Carbon::parse($row->event_at)->toIso8601String() : null,
            ]);

        return response()->json([
            'data' => $rows,
            'meta' => [
                'limit' => $limit,
                'source_kind' => 'laravel_polling',
            ],
        ]);
    }

    public function sync(Request $request): JsonResponse
    {
        if (! Schema::hasTable('fund_pool_events')) {
            return response()->json(['message' => 'fund_pool_events table is missing.'], 422);
        }

        $args = [];
        foreach (['network', 'package', 'rpc'] as $option) {
            $value = trim((string) $request->input($option, ''));
            if ($value !== '') {
                $args['--'.$option] = $value;
            }
        }

        $code = Artisan::call('fund:pools:sync-events', $args);

        return response()->json([
            'ok' => $code === 0,
            'exit_code' => $code,
            'output' => trim(Artisan::output()),
        ], $code === 0 ? 200 : 500);
    }

    private function listenerStatus(?int $lagSeconds, bool $hasEvents): string
    {
        if (! $hasEvents) {
            return 'not_configured';
        }

        if ($lagSeconds === null) {
            return 'waiting_for_events';
        }

        return $lagSeconds <= 900 ? 'healthy' : 'stale';
    }

    private function poolOptions()
    {
        if (! Schema::hasTable('fund_pools')) {
            return collect();
        }

        return DB::table('fund_pools')
            ->select('id', 'name', 'network', 'pool_object_id')
            ->orderByDesc('active')
            ->orderBy('name')
            ->limit(200)
            ->get()
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'name' => (string) $row->name,
                'network' => (string) $row->network,
                'pool_object_id' => (string) $row->pool_object_id,
            ]);
    }
}
