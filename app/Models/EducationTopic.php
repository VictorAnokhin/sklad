<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EducationTopic extends Model
{
    protected $guarded = [];

    protected $casts = ['is_active' => 'boolean'];

    public function materials()
    {
        return $this->hasMany(EducationalMaterial::class, 'topic_id');
    }
}
