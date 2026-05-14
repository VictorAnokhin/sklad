<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class FundPoolController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        if (! Schema::hasTable('fund_pools')) {
            return response()->json(['data' => []]);
        }

        $network = trim((string) $request->query('network', 'testnet'));
        $packageId = strtolower(trim((string) $request->query('package_id', '')));
        $coinType = trim((string) $request->query('coin_type', ''));
        $includeInactive = filter_var($request->query('include_inactive', true), FILTER_VALIDATE_BOOLEAN);

        $query = DB::table('fund_pools')
            ->when($network !== '', fn ($q) => $q->where('network', $network))
            ->when($packageId !== '', fn ($q) => $q->where('package_id', $packageId))
            ->when($coinType !== '', fn ($q) => $q->where('coin_type', $this->normalizeCoinType($coinType)))
            ->when(! $includeInactive, fn ($q) => $q->where('active', true))
            ->orderByDesc('active')
            ->orderByDesc('is_default_deposit')
            ->orderBy('risk_level')
            ->orderBy('name')
            ->orderBy('id');

        return response()->json([
            'data' => $query->get()->map(fn ($row) => $this->mapRow($row))->values(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        if (! Schema::hasTable('fund_pools')) {
            throw ValidationException::withMessages([
                'fund_pools' => 'Run migrations before saving fund pools.',
            ]);
        }

        $this->requireRwaAdminWallet($request);
        $validated = $this->validatePayload($request);
        $network = trim((string) ($validated['network'] ?? 'testnet')) ?: 'testnet';
        $poolObjectId = strtolower(trim((string) $validated['pool_object_id']));
        $now = now();

        DB::table('fund_pools')->updateOrInsert(
            [
                'network' => $network,
                'pool_object_id' => $poolObjectId,
            ],
            [
                'package_id' => strtolower(trim((string) ($validated['package_id'] ?? ''))),
                'pool_registry_id' => strtolower(trim((string) ($validated['pool_registry_id'] ?? ''))),
                'pool_admin_cap_id' => strtolower(trim((string) ($validated['pool_admin_cap_id'] ?? ''))),
                'pool_accounting_id' => $this->normalizeOptionalSuiAddress((string) ($validated['pool_accounting_id'] ?? '')),
                'basket_vault_id' => $this->normalizeOptionalSuiAddress((string) ($validated['basket_vault_id'] ?? '')),
                'liquidity_wallet_address' => $this->normalizeOptionalSuiAddress((string) ($validated['liquidity_wallet_address'] ?? '')),
                'coin_type' => $this->normalizeCoinType($validated['coin_type']),
                'symbol' => strtoupper(trim((string) ($validated['symbol'] ?? 'USDC'))),
                'name' => trim((string) $validated['name']),
                'description' => trim((string) ($validated['description'] ?? '')) ?: null,
                'risk_level' => (int) ($validated['risk_level'] ?? 1),
                'target_apy_bps' => (int) ($validated['target_apy_bps'] ?? 0),
                'realized_apy_bps' => (int) ($validated['realized_apy_bps'] ?? 0),
                'min_deposit_usdc' => trim((string) ($validated['min_deposit_usdc'] ?? '0')),
                'min_av8_balance' => trim((string) ($validated['min_av8_balance'] ?? '0')),
                'max_weight_bps' => (int) ($validated['max_weight_bps'] ?? 10000),
                'active' => (bool) ($validated['active'] ?? true),
                'is_default_deposit' => (bool) ($validated['is_default_deposit'] ?? false),
                'logo_url' => trim((string) ($validated['logo_url'] ?? '')),
                'notes' => trim((string) ($validated['notes'] ?? '')) ?: null,
                'created_by' => Auth::id(),
                'updated_at' => $now,
                'created_at' => $now,
            ]
        );
        $this->clearOtherDefaultDepositPools($network, $poolObjectId, (bool) ($validated['is_default_deposit'] ?? false));

        $row = DB::table('fund_pools')
            ->where('network', $network)
            ->where('pool_object_id', $poolObjectId)
            ->first();

        return response()->json(['data' => $this->mapRow($row)], 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        if (! Schema::hasTable('fund_pools')) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $this->requireRwaAdminWallet($request);
        $validated = $this->validatePayload($request, $id);
        $network = trim((string) ($validated['network'] ?? 'testnet')) ?: 'testnet';

        $updated = DB::table('fund_pools')
            ->where('id', $id)
            ->update([
                'network' => $network,
                'package_id' => strtolower(trim((string) ($validated['package_id'] ?? ''))),
                'pool_registry_id' => strtolower(trim((string) ($validated['pool_registry_id'] ?? ''))),
                'pool_admin_cap_id' => strtolower(trim((string) ($validated['pool_admin_cap_id'] ?? ''))),
                'pool_object_id' => strtolower(trim((string) $validated['pool_object_id'])),
                'pool_accounting_id' => $this->normalizeOptionalSuiAddress((string) ($validated['pool_accounting_id'] ?? '')),
                'basket_vault_id' => $this->normalizeOptionalSuiAddress((string) ($validated['basket_vault_id'] ?? '')),
                'liquidity_wallet_address' => $this->normalizeOptionalSuiAddress((string) ($validated['liquidity_wallet_address'] ?? '')),
                'coin_type' => $this->normalizeCoinType($validated['coin_type']),
                'symbol' => strtoupper(trim((string) ($validated['symbol'] ?? 'USDC'))),
                'name' => trim((string) $validated['name']),
                'description' => trim((string) ($validated['description'] ?? '')) ?: null,
                'risk_level' => (int) ($validated['risk_level'] ?? 1),
                'target_apy_bps' => (int) ($validated['target_apy_bps'] ?? 0),
                'realized_apy_bps' => (int) ($validated['realized_apy_bps'] ?? 0),
                'min_deposit_usdc' => trim((string) ($validated['min_deposit_usdc'] ?? '0')),
                'min_av8_balance' => trim((string) ($validated['min_av8_balance'] ?? '0')),
                'max_weight_bps' => (int) ($validated['max_weight_bps'] ?? 10000),
                'active' => (bool) ($validated['active'] ?? true),
                'is_default_deposit' => (bool) ($validated['is_default_deposit'] ?? false),
                'logo_url' => trim((string) ($validated['logo_url'] ?? '')),
                'notes' => trim((string) ($validated['notes'] ?? '')) ?: null,
                'updated_at' => now(),
            ]);

        if ($updated === 0 && ! DB::table('fund_pools')->where('id', $id)->exists()) {
            return response()->json(['message' => 'Not found'], 404);
        }
        $this->clearOtherDefaultDepositPools($network, strtolower(trim((string) $validated['pool_object_id'])), (bool) ($validated['is_default_deposit'] ?? false));

        $row = DB::table('fund_pools')->where('id', $id)->first();

        return response()->json(['data' => $this->mapRow($row)]);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        if (! Schema::hasTable('fund_pools')) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $this->requireRwaAdminWallet($request);
        $deleted = DB::table('fund_pools')->where('id', $id)->delete();

        return response()->json(['deleted' => $deleted > 0]);
    }

    public function events(Request $request, string $id): JsonResponse
    {
        if (! Schema::hasTable('fund_pools') || ! Schema::hasTable('fund_pool_events')) {
            return response()->json(['data' => [], 'chart' => []]);
        }

        $pool = DB::table('fund_pools')->where('id', $id)->first();
        if (! $pool) {
            $poolObjectId = $this->normalizeSuiAddress($id);
            $pool = DB::table('fund_pools')
                ->whereRaw('LOWER(pool_object_id) = ?', [$poolObjectId])
                ->first();
        }

        if (! $pool) {
            return response()->json(['message' => 'Pool not found'], 404);
        }

        $limit = max(1, min(500, (int) $request->query('limit', 200)));
        $events = DB::table('fund_pool_events')
            ->whereRaw('LOWER(pool_object_id) = ?', [strtolower((string) $pool->pool_object_id)])
            ->orderBy('event_at')
            ->orderBy('id')
            ->limit($limit)
            ->get();

        return response()->json([
            'pool' => $this->mapRow($pool),
            'data' => $events->map(fn ($row) => $this->mapEventRow($row))->values(),
            'chart' => $this->chartRows($events),
        ]);
    }

    private function validatePayload(Request $request, ?string $id = null): array
    {
        $network = trim((string) $request->input('network', 'testnet')) ?: 'testnet';

        return $request->validate([
            'network' => ['nullable', 'string', 'max:40'],
            'package_id' => ['nullable', 'string', 'max:80', 'regex:/^(|0x[a-fA-F0-9]{64})$/'],
            'pool_registry_id' => ['nullable', 'string', 'max:80', 'regex:/^(|0x[a-fA-F0-9]{64})$/'],
            'pool_admin_cap_id' => ['nullable', 'string', 'max:80', 'regex:/^(|0x[a-fA-F0-9]{64})$/'],
            'pool_accounting_id' => ['nullable', 'string', 'max:80', 'regex:/^(|0x[a-fA-F0-9]{1,64})$/'],
            'basket_vault_id' => ['nullable', 'string', 'max:80', 'regex:/^(|0x[a-fA-F0-9]{1,64})$/'],
            'liquidity_wallet_address' => ['nullable', 'string', 'max:80', 'regex:/^(|0x[a-fA-F0-9]{1,64})$/'],
            'pool_object_id' => [
                'required',
                'string',
                'max:80',
                'regex:/^0x[a-fA-F0-9]{64}$/',
                Rule::unique('fund_pools', 'pool_object_id')->where(fn ($query) => $query->where('network', $network))->ignore($id),
            ],
            'coin_type' => [
                'required',
                'string',
                'max:500',
                'regex:/^0x[a-fA-F0-9]+::[A-Za-z_][A-Za-z0-9_]*::[A-Za-z_][A-Za-z0-9_]*(<.*>)?$/',
            ],
            'symbol' => ['nullable', 'string', 'max:32'],
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:5000'],
            'risk_level' => ['nullable', 'integer', 'min:1', 'max:5'],
            'target_apy_bps' => ['nullable', 'integer', 'min:0', 'max:10000'],
            'realized_apy_bps' => ['nullable', 'integer', 'min:0', 'max:10000'],
            'min_deposit_usdc' => ['nullable', 'string', 'max:80', 'regex:/^\d+$/'],
            'min_av8_balance' => ['nullable', 'string', 'max:80', 'regex:/^\d+$/'],
            'max_weight_bps' => ['nullable', 'integer', 'min:0', 'max:10000'],
            'active' => ['nullable', 'boolean'],
            'is_default_deposit' => ['nullable', 'boolean'],
            'logo_url' => ['nullable', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);
    }

    private function requireRwaAdminWallet(Request $request): void
    {
        if (! Schema::hasTable('rwa_admin_caps')) {
            throw ValidationException::withMessages([
                'admin' => 'RWA admin caps table is missing.',
            ]);
        }

        $address = $this->normalizeSuiAddress((string) $request->header('X-Sui-Admin-Address', ''));
        if (! preg_match('/^0x[a-f0-9]{64}$/', $address)) {
            throw ValidationException::withMessages([
                'admin' => 'Connect a registered Sui admin wallet before changing fund pools.',
            ]);
        }

        $exists = DB::table('rwa_admin_caps')
            ->whereRaw('LOWER(owner_address) = ?', [$address])
            ->exists();

        if (! $exists) {
            throw ValidationException::withMessages([
                'admin' => 'This wallet is not registered as an RWA AdminCap owner.',
            ]);
        }
    }

    private function normalizeCoinType(string $coinType): string
    {
        $parts = explode('::', trim($coinType), 3);
        if (count($parts) !== 3) {
            return trim($coinType);
        }

        $parts[0] = strtolower($parts[0]);

        return implode('::', $parts);
    }

    private function normalizeSuiAddress(string $value): string
    {
        $value = strtolower(trim($value));

        if (preg_match('/^0x([a-f0-9]{1,64})$/', $value, $matches)) {
            return '0x'.str_pad($matches[1], 64, '0', STR_PAD_LEFT);
        }

        return $value;
    }

    private function normalizeOptionalSuiAddress(string $value): string
    {
        $value = trim($value);

        return $value === '' ? '' : $this->normalizeSuiAddress($value);
    }

    private function clearOtherDefaultDepositPools(string $network, string $poolObjectId, bool $isDefaultDeposit): void
    {
        if (! $isDefaultDeposit || ! Schema::hasColumn('fund_pools', 'is_default_deposit')) {
            return;
        }

        DB::table('fund_pools')
            ->where('network', $network)
            ->whereRaw('LOWER(pool_object_id) <> ?', [strtolower($poolObjectId)])
            ->update(['is_default_deposit' => false]);
    }

    private function mapRow(object $row): array
    {
        return [
            'id' => (int) $row->id,
            'network' => (string) $row->network,
            'package_id' => (string) ($row->package_id ?? ''),
            'pool_registry_id' => (string) ($row->pool_registry_id ?? ''),
            'pool_admin_cap_id' => (string) ($row->pool_admin_cap_id ?? ''),
            'pool_object_id' => (string) ($row->pool_object_id ?? ''),
            'pool_accounting_id' => (string) ($row->pool_accounting_id ?? ''),
            'basket_vault_id' => (string) ($row->basket_vault_id ?? ''),
            'liquidity_wallet_address' => (string) ($row->liquidity_wallet_address ?? ''),
            'coin_type' => (string) $row->coin_type,
            'symbol' => (string) ($row->symbol ?? 'USDC'),
            'name' => (string) $row->name,
            'description' => (string) ($row->description ?? ''),
            'risk_level' => (int) $row->risk_level,
            'target_apy_bps' => (int) $row->target_apy_bps,
            'realized_apy_bps' => (int) $row->realized_apy_bps,
            'min_deposit_usdc' => (string) ($row->min_deposit_usdc ?? '0'),
            'min_av8_balance' => (string) ($row->min_av8_balance ?? '0'),
            'max_weight_bps' => (int) $row->max_weight_bps,
            'active' => (bool) $row->active,
            'is_default_deposit' => (bool) ($row->is_default_deposit ?? false),
            'logo_url' => (string) ($row->logo_url ?? ''),
            'notes' => (string) ($row->notes ?? ''),
            'created_at' => $row->created_at ? (string) $row->created_at : null,
            'updated_at' => $row->updated_at ? (string) $row->updated_at : null,
        ];
    }

    private function mapEventRow(object $row): array
    {
        return [
            'id' => (int) $row->id,
            'network' => (string) $row->network,
            'package_id' => (string) $row->package_id,
            'event_type' => (string) $row->event_type,
            'move_event_type' => (string) $row->move_event_type,
            'tx_digest' => (string) $row->tx_digest,
            'event_seq' => (int) $row->event_seq,
            'checkpoint' => $row->checkpoint !== null ? (int) $row->checkpoint : null,
            'pool_object_id' => (string) $row->pool_object_id,
            'owner_address' => (string) $row->owner_address,
            'amount_usdc' => (string) $row->amount_usdc,
            'pool_shares' => (string) $row->pool_shares,
            'burned_pool_shares' => (string) $row->burned_pool_shares,
            'balance_usdc' => (string) $row->balance_usdc,
            'active' => $row->active === null ? null : (bool) $row->active,
            'target_apy_bps' => $row->target_apy_bps === null ? null : (int) $row->target_apy_bps,
            'realized_apy_bps' => $row->realized_apy_bps === null ? null : (int) $row->realized_apy_bps,
            'min_deposit_usdc' => $row->min_deposit_usdc === null ? null : (string) $row->min_deposit_usdc,
            'max_weight_bps' => $row->max_weight_bps === null ? null : (int) $row->max_weight_bps,
            'event_at' => $row->event_at ? (string) $row->event_at : null,
        ];
    }

    private function chartRows($events): array
    {
        $lastBalance = '0';
        $lastTargetApy = null;
        $lastRealizedApy = null;

        return $events->map(function ($row) use (&$lastBalance, &$lastTargetApy, &$lastRealizedApy) {
            if ((string) $row->balance_usdc !== '0') {
                $lastBalance = (string) $row->balance_usdc;
            }
            if ($row->target_apy_bps !== null) {
                $lastTargetApy = (int) $row->target_apy_bps;
            }
            if ($row->realized_apy_bps !== null) {
                $lastRealizedApy = (int) $row->realized_apy_bps;
            }

            return [
                'label' => $row->event_at ? date('M d', strtotime((string) $row->event_at)) : '#'.$row->id,
                'event_at' => $row->event_at ? (string) $row->event_at : null,
                'event_type' => (string) $row->event_type,
                'tvl_usdc' => $lastBalance,
                'target_apy_bps' => $lastTargetApy,
                'realized_apy_bps' => $lastRealizedApy,
            ];
        })->values()->all();
    }
}
