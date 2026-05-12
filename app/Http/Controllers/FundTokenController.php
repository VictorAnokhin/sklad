<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class FundTokenController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        if (! Schema::hasTable('fund_tokens')) {
            return response()->json(['data' => []]);
        }

        $network = trim((string) $request->query('network', 'testnet'));
        $packageId = strtolower(trim((string) $request->query('package_id', '')));
        $includeDisabled = filter_var($request->query('include_disabled', true), FILTER_VALIDATE_BOOLEAN);

        $query = DB::table('fund_tokens')
            ->when($network !== '', fn ($q) => $q->where('network', $network))
            ->when($packageId !== '', fn ($q) => $q->where('package_id', $packageId))
            ->when(! $includeDisabled, fn ($q) => $q->where('enabled', true))
            ->orderByDesc('enabled')
            ->orderBy('symbol')
            ->orderBy('id');

        return response()->json([
            'data' => $query->get()->map(fn ($row) => $this->mapRow($row))->values(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        if (! Schema::hasTable('fund_tokens')) {
            throw ValidationException::withMessages([
                'fund_tokens' => 'Run migrations before saving fund tokens.',
            ]);
        }

        $this->requireRwaAdminWallet($request);
        $validated = $this->validatePayload($request);
        $now = now();
        $coinType = $this->normalizeCoinType($validated['coin_type']);
        $network = trim((string) ($validated['network'] ?? 'testnet')) ?: 'testnet';

        DB::table('fund_tokens')->updateOrInsert(
            [
                'network' => $network,
                'coin_type' => $coinType,
            ],
            [
                'package_id' => strtolower(trim((string) ($validated['package_id'] ?? ''))),
                'symbol' => strtoupper(trim((string) $validated['symbol'])),
                'name' => trim((string) $validated['name']),
                'decimals' => (int) $validated['decimals'],
                'target_weight_bps' => (int) ($validated['target_weight_bps'] ?? 0),
                'min_weight_bps' => (int) ($validated['min_weight_bps'] ?? 0),
                'max_weight_bps' => (int) ($validated['max_weight_bps'] ?? 0),
                'price_feed_id' => trim((string) ($validated['price_feed_id'] ?? '')),
                'logo_url' => trim((string) ($validated['logo_url'] ?? '')),
                'enabled' => (bool) ($validated['enabled'] ?? true),
                'notes' => trim((string) ($validated['notes'] ?? '')) ?: null,
                'created_by' => Auth::id(),
                'updated_at' => $now,
                'created_at' => $now,
            ]
        );

        $row = DB::table('fund_tokens')
            ->where('network', $network)
            ->where('coin_type', $coinType)
            ->first();

        return response()->json(['data' => $this->mapRow($row)], 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        if (! Schema::hasTable('fund_tokens')) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $this->requireRwaAdminWallet($request);
        $validated = $this->validatePayload($request, $id);
        $coinType = $this->normalizeCoinType($validated['coin_type']);
        $network = trim((string) ($validated['network'] ?? 'testnet')) ?: 'testnet';

        $updated = DB::table('fund_tokens')
            ->where('id', $id)
            ->update([
                'network' => $network,
                'package_id' => strtolower(trim((string) ($validated['package_id'] ?? ''))),
                'coin_type' => $coinType,
                'symbol' => strtoupper(trim((string) $validated['symbol'])),
                'name' => trim((string) $validated['name']),
                'decimals' => (int) $validated['decimals'],
                'target_weight_bps' => (int) ($validated['target_weight_bps'] ?? 0),
                'min_weight_bps' => (int) ($validated['min_weight_bps'] ?? 0),
                'max_weight_bps' => (int) ($validated['max_weight_bps'] ?? 0),
                'price_feed_id' => trim((string) ($validated['price_feed_id'] ?? '')),
                'logo_url' => trim((string) ($validated['logo_url'] ?? '')),
                'enabled' => (bool) ($validated['enabled'] ?? true),
                'notes' => trim((string) ($validated['notes'] ?? '')) ?: null,
                'updated_at' => now(),
            ]);

        if ($updated === 0 && ! DB::table('fund_tokens')->where('id', $id)->exists()) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $row = DB::table('fund_tokens')->where('id', $id)->first();

        return response()->json(['data' => $this->mapRow($row)]);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        if (! Schema::hasTable('fund_tokens')) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $this->requireRwaAdminWallet($request);
        $deleted = DB::table('fund_tokens')->where('id', $id)->delete();

        return response()->json(['deleted' => $deleted > 0]);
    }

    private function validatePayload(Request $request, ?string $id = null): array
    {
        $network = trim((string) $request->input('network', 'testnet')) ?: 'testnet';

        return $request->validate([
            'network' => ['nullable', 'string', 'max:40'],
            'package_id' => ['nullable', 'string', 'max:80', 'regex:/^(|0x[a-fA-F0-9]{64})$/'],
            'coin_type' => [
                'required',
                'string',
                'max:500',
                'regex:/^0x[a-fA-F0-9]+::[A-Za-z_][A-Za-z0-9_]*::[A-Za-z_][A-Za-z0-9_]*(<.*>)?$/',
                Rule::unique('fund_tokens', 'coin_type')->where(fn ($query) => $query->where('network', $network))->ignore($id),
            ],
            'symbol' => ['required', 'string', 'max:32'],
            'name' => ['required', 'string', 'max:120'],
            'decimals' => ['required', 'integer', 'min:0', 'max:18'],
            'target_weight_bps' => ['nullable', 'integer', 'min:0', 'max:10000'],
            'min_weight_bps' => ['nullable', 'integer', 'min:0', 'max:10000'],
            'max_weight_bps' => ['nullable', 'integer', 'min:0', 'max:10000', 'gte:min_weight_bps'],
            'price_feed_id' => ['nullable', 'string', 'max:180'],
            'logo_url' => ['nullable', 'string', 'max:500'],
            'enabled' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);
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
                'admin' => 'Connect a registered Sui admin wallet before changing fund tokens.',
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

    private function normalizeSuiAddress(string $value): string
    {
        $value = strtolower(trim($value));

        if (preg_match('/^0x([a-f0-9]{1,64})$/', $value, $matches)) {
            return '0x'.str_pad($matches[1], 64, '0', STR_PAD_LEFT);
        }

        return $value;
    }

    private function mapRow(object $row): array
    {
        return [
            'id' => (int) $row->id,
            'network' => (string) $row->network,
            'package_id' => (string) ($row->package_id ?? ''),
            'coin_type' => (string) $row->coin_type,
            'symbol' => (string) $row->symbol,
            'name' => (string) $row->name,
            'decimals' => (int) $row->decimals,
            'target_weight_bps' => (int) $row->target_weight_bps,
            'min_weight_bps' => (int) $row->min_weight_bps,
            'max_weight_bps' => (int) $row->max_weight_bps,
            'price_feed_id' => (string) ($row->price_feed_id ?? ''),
            'logo_url' => (string) ($row->logo_url ?? ''),
            'enabled' => (bool) $row->enabled,
            'notes' => (string) ($row->notes ?? ''),
            'created_at' => $row->created_at ? (string) $row->created_at : null,
            'updated_at' => $row->updated_at ? (string) $row->updated_at : null,
        ];
    }
}
