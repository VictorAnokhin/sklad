<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuestTestAttempt extends Model
{
    protected $guarded = [];

    protected $casts = [
        'answers' => 'array',
        'result_data' => 'array',
        'passed' => 'boolean',
    ];
}
