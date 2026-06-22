<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FundPoolOperation extends Model
{
    protected $fillable = [
        'company_id',
        'pool_id',
        'user_id',
        'type',
        'amount',
        'currency',
        'shares_delta',
        'nav_price',
        'source',
        'ledger_transaction_id',
        'status',
        'reference_type',
        'reference_id',
        'blockchain_tx_digest',
        'external_event_id',
        'operation_at',
        'posted_at',
        'reversed_at',
        'reversed_by_operation_id',
        'meta',
    ];

    protected $casts = [
        'company_id' => 'integer',
        'pool_id' => 'integer',
        'user_id' => 'integer',
        'amount' => 'decimal:8',
        'shares_delta' => 'decimal:18',
        'nav_price' => 'decimal:12',
        'ledger_transaction_id' => 'integer',
        'operation_at' => 'datetime',
        'posted_at' => 'datetime',
        'reversed_at' => 'datetime',
        'meta' => 'array',
    ];

    public static function recordDepositDocument(
        int $companyId,
        object $document,
        ?int $ledgerTransactionId,
        bool $reverse = false
    ): void {
        if (!Schema::hasTable('fund_pool_operations')) {
            return;
        }

        $poolId = self::poolIdFromAssetKey((string) ($document->money ?? ''));
        $userId = (int) ($document->client2 ?? 0);
        if ($poolId <= 0 || $userId <= 0) {
            return;
        }

        $referenceType = 'z_document:deposit_operation';
        $referenceId = (string) ($document->id ?? '');
        if ($reverse) {
            self::reverseDepositDocument($companyId, $referenceType, $referenceId, $ledgerTransactionId);

            return;
        }

        $type = match ((string) ($document->docum ?? 'topup')) {
            'withdraw' => 'withdraw',
            default => 'deposit',
        };
        $amount = round(abs((float) ($document->summa ?? 0)), 8);
        if ($amount <= 0) {
            return;
        }

        $navPrice = self::currentNavPrice($poolId);
        $sharesDelta = $navPrice > 0 ? round($amount / $navPrice, 18) : $amount;
        if ($type === 'withdraw') {
            $sharesDelta *= -1;
        }

        self::create([
            'company_id' => $companyId,
            'pool_id' => $poolId,
            'user_id' => $userId,
            'type' => $type,
            'amount' => $amount,
            'currency' => self::poolCurrency($poolId, (string) ($document->currency_from ?? 'USDC')),
            'shares_delta' => $sharesDelta,
            'nav_price' => $navPrice,
            'source' => 'internal',
            'ledger_transaction_id' => $ledgerTransactionId,
            'status' => 'posted',
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'operation_at' => self::operationDate($document),
            'posted_at' => now(),
            'meta' => [
                'document_type' => (string) ($document->type ?? 'PP'),
                'document_mode' => (string) ($document->docum ?? ''),
                'document_number' => (string) ($document->num ?? ''),
            ],
        ]);
    }

    private static function reverseDepositDocument(
        int $companyId,
        string $referenceType,
        string $referenceId,
        ?int $ledgerTransactionId
    ): void {
        $operation = self::query()
            ->where('company_id', $companyId)
            ->where('reference_type', $referenceType)
            ->where('reference_id', $referenceId)
            ->where('status', 'posted')
            ->latest('id')
            ->first();

        if (!$operation) {
            return;
        }

        $meta = is_array($operation->meta) ? $operation->meta : [];
        if ($ledgerTransactionId) {
            $meta['reversal_ledger_transaction_id'] = $ledgerTransactionId;
        }

        $operation->update([
            'status' => 'reversed',
            'reversed_at' => now(),
            'meta' => $meta,
        ]);
    }

    private static function poolIdFromAssetKey(string $assetKey): int
    {
        if (!str_starts_with($assetKey, 'pool:')) {
            return 0;
        }

        return (int) substr($assetKey, strlen('pool:'));
    }

    private static function currentNavPrice(int $poolId): float
    {
        if (!Schema::hasTable('fund_pools') || !Schema::hasTable('fund_pool_events')) {
            return 1.0;
        }

        $poolObjectId = (string) DB::table('fund_pools')->where('id', $poolId)->value('pool_object_id');
        if ($poolObjectId === '') {
            return 1.0;
        }

        $event = DB::table('fund_pool_events')
            ->where('pool_object_id', $poolObjectId)
            ->orderByDesc('event_at')
            ->orderByDesc('id')
            ->first(['balance_usdc', 'pool_shares']);
        if (!$event) {
            return 1.0;
        }

        $balance = self::atomicToFloat((string) ($event->balance_usdc ?? '0'), 6);
        $shares = self::atomicToFloat((string) ($event->pool_shares ?? '0'), 6);

        return $balance > 0 && $shares > 0 ? round($balance / $shares, 12) : 1.0;
    }

    private static function poolCurrency(int $poolId, string $fallback): string
    {
        if (!Schema::hasTable('fund_pools')) {
            return self::normalizeCurrency($fallback ?: 'USDC');
        }

        $pool = DB::table('fund_pools')->where('id', $poolId)->first(['symbol', 'coin_type']);
        if (!$pool) {
            return self::normalizeCurrency($fallback ?: 'USDC');
        }

        $symbol = (string) ($pool->symbol ?? '');
        if ($symbol === '') {
            $coinTypeParts = explode('::', (string) ($pool->coin_type ?? ''));
            $symbol = (string) end($coinTypeParts);
        }

        return self::normalizeCurrency($symbol ?: $fallback ?: 'USDC');
    }

    private static function operationDate(object $document): Carbon
    {
        $rawDate = (string) ($document->data ?? '');
        try {
            $date = Carbon::createFromFormat('d-m-Y', $rawDate);
        } catch (\Throwable) {
            $date = null;
        }

        return $date ?: now();
    }

    private static function atomicToFloat(string $value, int $decimals): float
    {
        $value = trim($value);
        if ($value === '') {
            return 0.0;
        }
        if (str_contains($value, '.')) {
            return (float) $value;
        }

        return (float) $value / (10 ** $decimals);
    }

    private static function normalizeCurrency(string $value): string
    {
        $currency = strtoupper(preg_replace('/[^A-Z0-9]/', '', $value) ?? '');

        return $currency !== '' ? substr($currency, 0, 20) : 'USDC';
    }
}
