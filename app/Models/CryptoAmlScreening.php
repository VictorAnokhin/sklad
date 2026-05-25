<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CryptoAmlScreening extends Model
{
    protected $fillable = [
        'user_id',
        'wallet_address',
        'asset',
        'network',
        'amount',
        'direction',
        'risk_level',
        'allowed',
        'reason',
        'provider',
        'transfer_reference',
        'raw_response',
    ];

    protected $casts = [
        'allowed' => 'boolean',
        'amount' => 'decimal:8',
        'raw_response' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
