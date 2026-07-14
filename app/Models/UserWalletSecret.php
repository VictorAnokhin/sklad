<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserWalletSecret extends Model
{
    protected $fillable = [
        'user_id',
        'provider',
        'kind',
        'network',
        'encrypted_payload',
        'expires_at',
        'last_used_at',
    ];

    protected $hidden = [
        'encrypted_payload',
    ];

    protected $casts = [
        'encrypted_payload' => 'encrypted:array',
        'expires_at' => 'datetime',
        'last_used_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
