<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ZDocument extends Model
{
    protected $table = 'z_document';
    public $timestamps = false;
    protected $guarded = [];


    public function firmaObj()
    {
        return $this->belongsTo(Firma::class, 'firma');
    }

}
