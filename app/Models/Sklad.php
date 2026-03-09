<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sklad extends Model
{
    protected $table = 'sklad';
    public $timestamps = false;
    protected $guarded = [];


    public function firmaObj()
    {
        return $this->belongsTo(Firma::class, 'firma');
    }

}
