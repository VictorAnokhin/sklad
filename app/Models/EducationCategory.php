<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EducationCategory extends Model
{
    public const CONTEXT_KNOW_YOURSELF = 'know_yourself';
    public const CONTEXT_COURSE = 'course';

    protected $guarded = [];

    protected $casts = [
        'title_translations' => 'array',
        'is_active' => 'boolean',
    ];

    public function courses()
    {
        return $this->hasMany(EducationTopic::class, 'category_id');
    }

    public function tests()
    {
        return $this->hasMany(QuestTest::class, 'category_id');
    }
}
