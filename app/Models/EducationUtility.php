<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EducationUtility extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'title_translations' => 'array',
        'description_translations' => 'array',
        'cost_av8' => 'decimal:6',
        'module_key' => 'string',
        'icon' => 'string',
        'icon_path' => 'string',
    ];
}
