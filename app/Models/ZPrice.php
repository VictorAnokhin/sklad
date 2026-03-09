<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ZPrice extends Model
{
    protected $table = 'z_price';
    public $timestamps = false;
    protected $guarded = [];


    public function skladObj()
    {
        return $this->belongsTo(Sklad::class, 'sklad');
    }

}
