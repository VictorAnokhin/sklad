<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WalletToken extends Model
{
    protected $fillable = [
        'wallet_id',
        'chain',
        'token_address',
        'symbol',
        'name',
        'decimals',
        'balance',
        'price_usd',
        'value_usd',
        'logo',
        'is_spam',
        'is_selected',
        'commission',
        'synced_at',
    ];

    protected $casts = [
        'decimals' => 'integer',
        'balance' => 'decimal:18',
        'price_usd' => 'decimal:8',
        'value_usd' => 'decimal:2',
        'is_spam' => 'boolean',
        'is_selected' => 'boolean',
        'commission' => 'decimal:4',
        'synced_at' => 'datetime',
    ];

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }
}
