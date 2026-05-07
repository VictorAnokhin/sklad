<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LegacyUser extends Model
{
    protected $table = 'user';
    public $timestamps = false;
    protected $guarded = [];


    public function firmaObj()
    {
        return $this->belongsTo(Firma::class, 'firma');
    }

    public function skladObj()
    {
        return $this->belongsTo(Sklad::class, 'sklad');
    }

    public function legacyUser()
    {
        return $this->belongsTo(LegacyUser::class, 'iduser');
    }

}
