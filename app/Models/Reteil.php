<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reteil extends Model
{
    protected $table = 'reteil';
    public $timestamps = false;
    protected $guarded = [];


    public function firmaObj()
    {
        return $this->belongsTo(Firma::class, 'firma');
    }

}
