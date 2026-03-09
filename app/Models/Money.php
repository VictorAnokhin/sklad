<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Money extends Model
{
    protected $table = 'money';
    public $timestamps = false;
    protected $guarded = [];


    public function firmaObj()
    {
        return $this->belongsTo(Firma::class, 'firma');
    }

}
