<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    protected $table = 'project';
    public $timestamps = false;
    protected $guarded = [];


    public function legacyUser()
    {
        return $this->belongsTo(LegacyUser::class, 'userid');
    }

}
