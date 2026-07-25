<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    protected $fillable = ['code', 'name', 'type', 'currency', 'parent_id', 'project_id'];
    protected $casts = [
        'project_id' => 'integer',
    ];

    public function entries()
    {
        return $this->hasMany(Entry::class);
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function getBalance(): float
    {
        $debit = (float) $this->entries()->sum('debit');
        $credit = (float) $this->entries()->sum('credit');

        return match ($this->type) {
            'asset', 'expense' => $debit - $credit,
            'liability', 'equity', 'income' => $credit - $debit,
            default => 0.0,
        };
    }
}
