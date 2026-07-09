<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EducationProgress extends Model
{
    protected $table = 'education_progress';
    protected $guarded = [];

    protected $casts = ['completed_at' => 'datetime'];
}
