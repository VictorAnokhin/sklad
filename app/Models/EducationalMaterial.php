<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EducationalMaterial extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'title_translations' => 'array',
        'body_translations' => 'array',
        'rating' => 'integer',
    ];

    public function topic()
    {
        return $this->belongsTo(EducationTopic::class, 'topic_id');
    }

    public function tests()
    {
        return $this->hasMany(QuestTest::class, 'material_id');
    }
}
