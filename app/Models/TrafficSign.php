<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrafficSign extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_published' => 'boolean',
        'sort_order' => 'integer',
    ];
}
