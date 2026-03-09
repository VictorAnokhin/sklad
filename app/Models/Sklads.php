<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sklads extends Model
{
    protected $table = 'sklads';
    public $timestamps = false;
    protected $guarded = [];


    public function firmaObj()
    {
        return $this->belongsTo(Firma::class, 'firma');
    }

    public function legacyUser()
    {
        return $this->belongsTo(LegacyUser::class, 'userid');
    }

}
