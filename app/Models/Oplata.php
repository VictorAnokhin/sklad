<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Oplata extends Model
{
    protected $table = 'oplata';
    public $timestamps = false;
    protected $guarded = [];


    public function firmaObj()
    {
        return $this->belongsTo(Firma::class, 'firma');
    }

}
