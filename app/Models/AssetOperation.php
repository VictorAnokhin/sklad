<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssetOperation extends Model
{
    protected $fillable = [
        'fid',
        'business_asset_id',
        'operation_type',
        'operation_date',
        'amount',
        'carrying_amount',
        'cash_account_id',
        'payment_type_id',
        'counterparty_id',
        'description',
        'provodka',
        'ledger_transaction_id',
        'reversal_transaction_id',
    ];

    protected $casts = [
        'fid' => 'integer',
        'business_asset_id' => 'integer',
        'amount' => 'decimal:2',
        'carrying_amount' => 'decimal:2',
        'operation_date' => 'date',
        'provodka' => 'boolean',
        'ledger_transaction_id' => 'integer',
        'reversal_transaction_id' => 'integer',
    ];

    public function asset()
    {
        return $this->belongsTo(BusinessAsset::class, 'business_asset_id');
    }

    public static function operationOptions(): array
    {
        return [
            'purchase' => 'Покупка актива',
            'sell' => 'Продажа актива',
            'depreciation' => 'Амортизация',
            'impairment' => 'Обесценение',
            'revalue' => 'Переоценка',
            'rd_capitalize' => 'Капитализация R&D',
            'rd_expense' => 'R&D в расходы',
        ];
    }

    public static function operationLabel(?string $type): string
    {
        return self::operationOptions()[$type ?: ''] ?? 'Операция';
    }
}
