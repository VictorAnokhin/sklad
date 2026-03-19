<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Money extends Model
{
    protected $table = 'money';
    public $timestamps = false;
    protected $guarded = [];


    public function firmaObj()
    {
        return $this->belongsTo(Firma::class, 'firma');
    }

    // ── init: load kassas + reestr for index ──────────────────────────────────

    public static function init($fid)
    {
        $kassas = DB::table('kassa')->where('firma', $fid)->get();
        $reestr = DB::table('reestr')->where('firma', $fid)->orderByDesc('id')->get();

        return [
            'kassas' => $kassas,
            'reestr' => $reestr,
        ];
    }

    // ── save: insert or update kassa ─────────────────────────────────────────

    public static function saveMoney($id, $fid, $data)
    {
        if ($id === '') {
            DB::table('kassa')->insert(array_merge($data, ['firma' => $fid]));
        } else {
            DB::table('kassa')->where('id', $id)->update($data);
        }
    }
}
