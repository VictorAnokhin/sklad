<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Wallet extends Model
{
    protected $fillable = [
        'address',
    ];

    public function tokens(): HasMany
    {
        return $this->hasMany(WalletToken::class);
    }

    public function protocolSnapshots(): HasMany
    {
        return $this->hasMany(WalletProtocolSnapshot::class);
    }
}
