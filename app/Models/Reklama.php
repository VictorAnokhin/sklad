<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reklama extends Model
{
    protected $table = 'reklama';
    public $timestamps = false;
    protected $guarded = [];


    public function firmaObj()
    {
        return $this->belongsTo(Firma::class, 'firma');
    }

}
