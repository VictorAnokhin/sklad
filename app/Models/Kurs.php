<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Kurs extends Model
{
    protected $table = 'kurs';
    public $timestamps = false;
    protected $guarded = [];

    // ── init: load currency rates for index ──────────────────────────────────

    public static function init($fid)
    {
        $kurs = DB::table('kurs')->where('firma', $fid)->orderByDesc('data')->limit(30)->get();

        return [
            'kurs' => $kurs,
        ];
    }

    // ── save: insert new currency rate ───────────────────────────────────────

    public static function saveKurs($fid, $data)
    {
        DB::table('kurs')->insert(array_merge($data, ['firma' => $fid]));
    }
}
