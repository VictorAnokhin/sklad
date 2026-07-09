<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuestTest extends Model
{
    protected $table = 'quests_tests';
    protected $guarded = [];

    protected $casts = [
        'quest_data' => 'array',
        'is_active' => 'boolean',
    ];

    public function material()
    {
        return $this->belongsTo(EducationalMaterial::class, 'material_id');
    }

    public function results()
    {
        return $this->hasMany(QuestTestResult::class, 'quest_test_id')->orderBy('sort_order')->orderBy('min_score');
    }
}
