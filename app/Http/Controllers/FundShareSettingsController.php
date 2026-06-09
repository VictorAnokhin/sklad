<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class FundShareSettingsController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $network = trim((string) $request->query('network', 'testnet')) ?: 'testnet';
        $packageId = strtolower(trim((string) $request->query('package_id', '')));

        if (! Schema::hasTable('fund_share_settings')) {
            return response()->json(['data' => $this->defaults($network, $packageId)]);
        }

        $row = DB::table('fund_share_settings')
            ->where('network', $network)
            ->where('package_id', $packageId)
            ->first();

        return response()->json(['data' => $row ? $this->mapRow($row) : $this->defaults($network, $packageId)]);
    }

    public function update(Request $request): JsonResponse
    {
        if (! Schema::hasTable('fund_share_settings')) {
            throw ValidationException::withMessages([
                'fund_share_settings' => 'Run migrations before saving fund share settings.',
            ]);
        }

        $this->requireRwaAdminWallet($request);
        $validated = $this->validatePayload($request);
        $network = trim((string) ($validated['network'] ?? 'testnet')) ?: 'testnet';
        $packageId = strtolower(trim((string) ($validated['package_id'] ?? '')));
        $now = now();

        DB::table('fund_share_settings')->updateOrInsert(
            [
                'network' => $network,
                'package_id' => $packageId,
            ],
            [
                'share_config_id' => strtolower(trim((string) ($validated['share_config_id'] ?? ''))),
                'share_fee_config_id' => strtolower(trim((string) ($validated['share_fee_config_id'] ?? ''))),
                'share_admin_cap_id' => strtolower(trim((string) ($validated['share_admin_cap_id'] ?? ''))),
                'share_treasury_cap_id' => strtolower(trim((string) ($validated['share_treasury_cap_id'] ?? ''))),
                'pricing_model' => trim((string) ($validated['pricing_model'] ?? 'nav_per_share')),
                'mint_fee_bps' => (int) ($validated['mint_fee_bps'] ?? 0),
                'redeem_fee_bps' => (int) ($validated['redeem_fee_bps'] ?? 0),
                'redeem_burn_bps' => (int) ($validated['redeem_burn_bps'] ?? 10000),
                'price_impact_bps' => (int) ($validated['price_impact_bps'] ?? 0),
                'min_price_sui' => trim((string) ($validated['min_price_sui'] ?? '0')),
                'base_price_sui' => trim((string) ($validated['base_price_sui'] ?? '0')),
                'current_price_usdc' => trim((string) ($validated['current_price_usdc'] ?? '0')),
                'total_emission_av8' => trim((string) ($validated['total_emission_av8'] ?? '0')),
                'virtual_usdc_reserves' => trim((string) ($validated['virtual_usdc_reserves'] ?? '0')),
                'virtual_av8_reserves' => trim((string) ($validated['virtual_av8_reserves'] ?? '0')),
                'quote_ttl_seconds' => (int) ($validated['quote_ttl_seconds'] ?? 30),
                'min_buy_usdc' => trim((string) ($validated['min_buy_usdc'] ?? '0')),
                'max_buy_usdc' => trim((string) ($validated['max_buy_usdc'] ?? '0')),
                'min_sell_av8' => trim((string) ($validated['min_sell_av8'] ?? '0')),
                'max_sell_av8' => trim((string) ($validated['max_sell_av8'] ?? '0')),
                'redeem_delay_days' => (int) ($validated['redeem_delay_days'] ?? 3),
                'max_supply' => trim((string) ($validated['max_supply'] ?? '0')),
                'max_daily_mint' => trim((string) ($validated['max_daily_mint'] ?? '0')),
                'mint_paused' => (bool) ($validated['mint_paused'] ?? false),
                'redeem_paused' => (bool) ($validated['redeem_paused'] ?? false),
                'notes' => trim((string) ($validated['notes'] ?? '')) ?: null,
                'created_by' => Auth::id(),
                'updated_at' => $now,
                'created_at' => $now,
            ]
        );

        $row = DB::table('fund_share_settings')
            ->where('network', $network)
            ->where('package_id', $packageId)
            ->first();

        return response()->json(['data' => $this->mapRow($row)]);
    }

    private function validatePayload(Request $request): array
    {
        return $request->validate([
            'network' => ['nullable', 'string', 'max:40'],
            'package_id' => ['nullable', 'string', 'max:80', 'regex:/^(|0x[a-fA-F0-9]{64})$/'],
            'share_config_id' => ['nullable', 'string', 'max:80', 'regex:/^(|0x[a-fA-F0-9]{64})$/'],
            'share_admin_cap_id' => ['nullable', 'string', 'max:80', 'regex:/^(|0x[a-fA-F0-9]{64})$/'],
            'share_treasury_cap_id' => ['nullable', 'string', 'max:80', 'regex:/^(|0x[a-fA-F0-9]{64})$/'],
            'share_fee_config_id' => ['nullable', 'string', 'max:80', 'regex:/^(|0x[a-fA-F0-9]{64})$/'],
            'pricing_model' => ['nullable', Rule::in(['nav_per_share', 'manual_floor', 'bonding_curve'])],
            'mint_fee_bps' => ['nullable', 'integer', 'min:0', 'max:10000'],
            'redeem_fee_bps' => ['nullable', 'integer', 'min:0', 'max:10000'],
            'redeem_burn_bps' => ['nullable', 'integer', 'min:0', 'max:10000'],
            'price_impact_bps' => ['nullable', 'integer', 'min:0', 'max:10000'],
            'min_price_sui' => ['nullable', 'string', 'max:80', 'regex:/^\d+$/'],
            'base_price_sui' => ['nullable', 'string', 'max:80', 'regex:/^\d+$/'],
            'current_price_usdc' => ['nullable', 'string', 'max:80', 'regex:/^\d+$/'],
            'total_emission_av8' => ['nullable', 'string', 'max:80', 'regex:/^\d+$/'],
            'virtual_usdc_reserves' => ['nullable', 'string', 'max:80', 'regex:/^\d+$/'],
            'virtual_av8_reserves' => ['nullable', 'string', 'max:80', 'regex:/^\d+$/'],
            'quote_ttl_seconds' => ['nullable', 'integer', 'min:5', 'max:3600'],
            'min_buy_usdc' => ['nullable', 'string', 'max:80', 'regex:/^\d+$/'],
            'max_buy_usdc' => ['nullable', 'string', 'max:80', 'regex:/^\d+$/'],
            'min_sell_av8' => ['nullable', 'string', 'max:80', 'regex:/^\d+$/'],
            'max_sell_av8' => ['nullable', 'string', 'max:80', 'regex:/^\d+$/'],
            'redeem_delay_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'max_supply' => ['nullable', 'string', 'max:80', 'regex:/^\d+$/'],
            'max_daily_mint' => ['nullable', 'string', 'max:80', 'regex:/^\d+$/'],
            'mint_paused' => ['nullable', 'boolean'],
            'redeem_paused' => ['nullable', 'boolean'],
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
                'admin' => 'Connect a registered Sui admin wallet before changing fund share settings.',
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

    private function defaults(string $network, string $packageId): array
    {
        return [
            'id' => null,
            'network' => $network,
            'package_id' => $packageId,
            'share_config_id' => '',
            'share_fee_config_id' => '',
            'share_admin_cap_id' => '',
            'share_treasury_cap_id' => '',
            'pricing_model' => 'nav_per_share',
            'mint_fee_bps' => 0,
            'redeem_fee_bps' => 0,
            'redeem_burn_bps' => 10000,
            'price_impact_bps' => 0,
            'min_price_sui' => '0',
            'base_price_sui' => '0',
            'current_price_usdc' => '0',
            'total_emission_av8' => '0',
            'virtual_usdc_reserves' => '0',
            'virtual_av8_reserves' => '0',
            'quote_ttl_seconds' => 30,
            'min_buy_usdc' => '0',
            'max_buy_usdc' => '0',
            'min_sell_av8' => '0',
            'max_sell_av8' => '0',
            'redeem_delay_days' => 3,
            'max_supply' => '0',
            'max_daily_mint' => '0',
            'mint_paused' => false,
            'redeem_paused' => false,
            'notes' => '',
            'created_at' => null,
            'updated_at' => null,
        ];
    }

    private function mapRow(object $row): array
    {
        return [
            'id' => (int) $row->id,
            'network' => (string) $row->network,
            'package_id' => (string) ($row->package_id ?? ''),
            'share_config_id' => (string) ($row->share_config_id ?? ''),
            'share_fee_config_id' => (string) ($row->share_fee_config_id ?? ''),
            'share_admin_cap_id' => (string) ($row->share_admin_cap_id ?? ''),
            'share_treasury_cap_id' => (string) ($row->share_treasury_cap_id ?? ''),
            'pricing_model' => (string) ($row->pricing_model ?? 'nav_per_share'),
            'mint_fee_bps' => (int) $row->mint_fee_bps,
            'redeem_fee_bps' => (int) $row->redeem_fee_bps,
            'redeem_burn_bps' => (int) $row->redeem_burn_bps,
            'price_impact_bps' => (int) $row->price_impact_bps,
            'min_price_sui' => (string) ($row->min_price_sui ?? '0'),
            'base_price_sui' => (string) ($row->base_price_sui ?? '0'),
            'current_price_usdc' => (string) ($row->current_price_usdc ?? '0'),
            'total_emission_av8' => (string) ($row->total_emission_av8 ?? '0'),
            'virtual_usdc_reserves' => (string) ($row->virtual_usdc_reserves ?? '0'),
            'virtual_av8_reserves' => (string) ($row->virtual_av8_reserves ?? '0'),
            'quote_ttl_seconds' => (int) ($row->quote_ttl_seconds ?? 30),
            'min_buy_usdc' => (string) ($row->min_buy_usdc ?? '0'),
            'max_buy_usdc' => (string) ($row->max_buy_usdc ?? '0'),
            'min_sell_av8' => (string) ($row->min_sell_av8 ?? '0'),
            'max_sell_av8' => (string) ($row->max_sell_av8 ?? '0'),
            'redeem_delay_days' => (int) ($row->redeem_delay_days ?? 3),
            'max_supply' => (string) ($row->max_supply ?? '0'),
            'max_daily_mint' => (string) ($row->max_daily_mint ?? '0'),
            'mint_paused' => (bool) $row->mint_paused,
            'redeem_paused' => (bool) $row->redeem_paused,
            'notes' => (string) ($row->notes ?? ''),
            'created_at' => $row->created_at ? (string) $row->created_at : null,
            'updated_at' => $row->updated_at ? (string) $row->updated_at : null,
        ];
    }
}
