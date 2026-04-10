<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $fillable = [
        'date',
        'description',
        'company_id',
        'user_id',
        'reference_type',
        'reference_id',
        'currency',
        'amount',
        'amount_base',
    ];

    public function entries()
    {
        return $this->hasMany(Entry::class);
    }
}
