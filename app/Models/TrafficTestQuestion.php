<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrafficTestQuestion extends Model
{
    protected $guarded = [];

    protected $casts = [
        'answers' => 'array',
        'correct_answer' => 'integer',
        'sort_order' => 'integer',
        'is_published' => 'boolean',
    ];
}
