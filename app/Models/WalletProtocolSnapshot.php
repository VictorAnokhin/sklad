<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WalletProtocolSnapshot extends Model
{
    protected $fillable = [
        'wallet_id',
        'chain_id',
        'payload',
        'synced_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'synced_at' => 'datetime',
    ];

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }
}
