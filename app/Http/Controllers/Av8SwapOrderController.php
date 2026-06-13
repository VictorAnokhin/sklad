<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class Av8SwapOrderController extends Controller
{
    private const DEFAULT_RATE_USDC = 1.00;
    private const DEFAULT_FEE_PERCENT = 0.35;

    public function index(Request $request): JsonResponse
    {
        if (! Schema::hasTable('av8_swap_orders')) {
            return response()->json(['data' => []]);
        }

        $limit = max(5, min(100, (int) $request->query('limit', 30)));
        $fid = (int) $request->query('fid', 0);

        $rows = DB::table('av8_swap_orders')
            ->when($fid > 0, fn ($query) => $query->where('fid', $fid))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => $this->mapOrder($row));

        return response()->json(['data' => $rows]);
    }

    public function store(Request $request): JsonResponse
    {
        if (! Schema::hasTable('av8_swap_orders')) {
            throw ValidationException::withMessages([
                'av8_swap_orders' => 'Run migrations before creating AV8 swap orders.',
            ]);
        }

        $payload = $request->validate([
            'fid' => ['nullable', 'integer', 'min:0'],
            'mode' => ['required', Rule::in(['fiat', 'crypto'])],
            'pay_currency' => ['required', 'string', 'max:20'],
            'pay_amount' => ['required', 'numeric', 'gt:0', 'max:999999999'],
            'rate_usdc' => ['nullable', 'numeric', 'gt:0', 'max:999999999'],
            'fee_percent' => ['nullable', 'numeric', 'min:0', 'max:20'],
            'payment_method' => ['nullable', 'string', 'max:80'],
            'wallet_address' => ['required', 'string', 'max:120'],
            'client_email' => ['nullable', 'email', 'max:191'],
            'client_phone' => ['nullable', 'string', 'max:80'],
            'source' => ['nullable', 'string', 'max:80'],
            'meta' => ['nullable', 'array'],
        ]);

        $payAmount = round((float) $payload['pay_amount'], 8);
        $rateUsdc = round((float) ($payload['rate_usdc'] ?? $this->currentAv8RateUsdc()), 8);
        $feePercent = round((float) ($payload['fee_percent'] ?? $this->currentFeePercent()), 4);
        $feeAmount = round($payAmount * $feePercent / 100, 8);
        $netAmount = max(0, $payAmount - $feeAmount);
        $expectedAv8 = $rateUsdc > 0 ? round($netAmount / $rateUsdc, 8) : 0;
        $now = now();

        $id = DB::table('av8_swap_orders')->insertGetId([
            'fid' => (int) ($payload['fid'] ?? 0),
            'mode' => $payload['mode'],
            'pay_currency' => mb_strtoupper(trim((string) $payload['pay_currency'])),
            'pay_amount' => $payAmount,
            'rate_usdc' => $rateUsdc,
            'fee_percent' => $feePercent,
            'fee_amount' => $feeAmount,
            'expected_av8' => $expectedAv8,
            'payment_method' => trim((string) ($payload['payment_method'] ?? '')),
            'wallet_address' => trim((string) $payload['wallet_address']),
            'client_email' => trim((string) ($payload['client_email'] ?? '')) ?: null,
            'client_phone' => trim((string) ($payload['client_phone'] ?? '')) ?: null,
            'status' => 'new',
            'source' => trim((string) ($payload['source'] ?? 'av8fund-react')) ?: 'av8fund-react',
            'meta' => isset($payload['meta']) ? json_encode($payload['meta'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return response()->json([
            'data' => $this->mapOrder(DB::table('av8_swap_orders')->where('id', $id)->first()),
        ], 201);
    }

    private function currentAv8RateUsdc(): float
    {
        if (! Schema::hasTable('fund_share_settings') || ! Schema::hasColumn('fund_share_settings', 'current_price_usdc')) {
            return self::DEFAULT_RATE_USDC;
        }

        $value = DB::table('fund_share_settings')
            ->where('current_price_usdc', '!=', '0')
            ->orderByDesc('updated_at')
            ->value('current_price_usdc');

        $rate = $this->storedUsdcToDecimal($value);

        return $rate > 0 ? $rate : self::DEFAULT_RATE_USDC;
    }

    private function currentFeePercent(): float
    {
        if (! Schema::hasTable('fund_share_settings') || ! Schema::hasColumn('fund_share_settings', 'mint_fee_bps')) {
            return self::DEFAULT_FEE_PERCENT;
        }

        $bps = DB::table('fund_share_settings')
            ->orderByDesc('updated_at')
            ->value('mint_fee_bps');

        return $bps !== null ? round(((float) $bps) / 100, 4) : self::DEFAULT_FEE_PERCENT;
    }

    private function storedUsdcToDecimal(mixed $value): float
    {
        $value = trim((string) $value);
        if ($value === '') {
            return 0.0;
        }

        if (str_contains($value, '.')) {
            return (float) $value;
        }

        return is_numeric($value) ? ((float) $value / 1000000) : 0.0;
    }

    private function mapOrder(?object $row): array
    {
        if (! $row) {
            return [];
        }

        return [
            'id' => (int) $row->id,
            'fid' => (int) $row->fid,
            'mode' => (string) $row->mode,
            'pay_currency' => (string) $row->pay_currency,
            'pay_amount' => (float) $row->pay_amount,
            'rate_usdc' => (float) $row->rate_usdc,
            'fee_percent' => (float) $row->fee_percent,
            'fee_amount' => (float) $row->fee_amount,
            'expected_av8' => (float) $row->expected_av8,
            'payment_method' => (string) $row->payment_method,
            'wallet_address' => (string) $row->wallet_address,
            'client_email' => (string) ($row->client_email ?? ''),
            'client_phone' => (string) ($row->client_phone ?? ''),
            'status' => (string) $row->status,
            'source' => (string) $row->source,
            'created_at' => (string) $row->created_at,
        ];
    }
}
