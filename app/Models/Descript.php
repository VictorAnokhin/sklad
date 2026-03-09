<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Descript extends Model
{
    protected $table = 'descript';
    public $timestamps = false;
    protected $guarded = [];


    public function firmaObj()
    {
        return $this->belongsTo(Firma::class, 'firma');
    }

}
