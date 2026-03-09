<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Conf extends Model
{
    protected $table = 'conf';
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
