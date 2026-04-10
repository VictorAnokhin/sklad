<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Entry extends Model
{
    protected $fillable = [
        'transaction_id',
        'account_id',
        'debit',
        'credit',
        'company_id',
        'user_id',
        'reference_type',
        'reference_id',
        'currency',
        'amount',
        'amount_base',
    ];

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }
}
