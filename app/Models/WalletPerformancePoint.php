<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WalletPerformancePoint extends Model
{
    protected $fillable = [
        'wallet_id',
        'chain_id',
        'timeframe',
        'point_at',
        'label',
        'total_usd',
        'source',
        'meta',
    ];

    protected $casts = [
        'point_at' => 'datetime',
        'total_usd' => 'decimal:8',
        'meta' => 'array',
    ];

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }
}
