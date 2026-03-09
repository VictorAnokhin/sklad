<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Comp extends Model
{
    protected $table = 'comp';
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

}
