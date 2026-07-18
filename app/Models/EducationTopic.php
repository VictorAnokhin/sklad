<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EducationTopic extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'title_translations' => 'array',
        'description_translations' => 'array',
        'cost_av8' => 'decimal:6',
    ];

    public function materials()
    {
        return $this->hasMany(EducationalMaterial::class, 'topic_id');
    }

    public function category()
    {
        return $this->belongsTo(EducationCategory::class, 'category_id');
    }
}
