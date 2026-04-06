<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Descript extends Model
{
    protected $table = 'descript';
    public $timestamps = false;
    protected $guarded = [];


    public function firmaObj()
    {
        return $this->belongsTo(Firma::class, 'firma');
    }

    // ── getForGoods: опис товару по pnum і firma ──────────────────────────────

    public static function getForGoods($pnum, $fid)
    {
        if (!$pnum || $pnum === '0') return null;

        return self::where('pnum', $pnum)
            ->where('firma', $fid)
            ->first();
    }
}
