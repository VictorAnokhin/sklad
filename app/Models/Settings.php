<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Settings extends Model
{
    protected $table = 'settings';
    public $timestamps = false;
    protected $guarded = [];

    // ── init: дані для index ──────────────────────────────────────────────────

    public static function init($fid)
    {
        $conf = DB::table('conf')->where('firma', $fid)->orderBy('type')->orderBy('name')->get();
        return compact('conf');
    }

    // ── saveConf: збереження/оновлення conf ───────────────────────────────────

    public static function saveConf($id, $data)
    {
        if ($id === '') {
            DB::table('conf')->insert($data);
        } else {
            DB::table('conf')->where('id', $id)->update($data);
        }
    }

    // ── deleteConf: видалення з conf ──────────────────────────────────────────

    public static function deleteConf($id, $fid)
    {
        DB::table('conf')->where('id', $id)->where('firma', $fid)->delete();
    }
}
