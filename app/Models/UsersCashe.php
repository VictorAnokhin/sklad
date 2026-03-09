<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UsersCashe extends Model
{
    protected $table = 'users_cashe';
    public $timestamps = false;
    protected $guarded = [];


    public function firmaObj()
    {
        return $this->belongsTo(Firma::class, 'firma');
    }

}
