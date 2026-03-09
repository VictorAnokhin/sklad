<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reestr extends Model
{
    protected $table = 'reestr';
    public $timestamps = false;
    protected $guarded = [];


    public function firmaObj()
    {
        return $this->belongsTo(Firma::class, 'firma');
    }

}
