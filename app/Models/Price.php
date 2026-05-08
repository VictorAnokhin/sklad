<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Price extends Model
{
    protected $table = 'price';
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

    // ── getForGoods: ціни по товару, згруповані по tgroup ─────────────────────

    public static function getForGoods($pnum, $fid)
    {
        if (! $pnum || $pnum === '0') {
            return collect();
        }

        $pnumKey = (string) $pnum;
        $validTgroups = Conf::getPriceGroups($fid)->pluck('id')->all();

        if ($validTgroups === []) {
            return collect();
        }

        $query = self::query()
            ->where('pnum', $pnumKey)
            ->where('firma', $fid)
            ->whereIn('tgroup', $validTgroups);

        return $query->get()->keyBy('tgroup');
    }
}
