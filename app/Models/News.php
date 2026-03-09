<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class News extends Model
{
    protected $table = 'news';
    public $timestamps = false;
    protected $guarded = [];


    public function firmaObj()
    {
        return $this->belongsTo(Firma::class, 'firma');
    }

}
