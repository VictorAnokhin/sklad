<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ZBody extends Model
{
    protected $table = 'z_body';
    public $timestamps = false;
    protected $guarded = [];


    public function firmaObj()
    {
        return $this->belongsTo(Firma::class, 'firma');
    }

    public function doc()
    {
        return $this->belongsTo(ZDocument::class, 'docid');
    }

}
