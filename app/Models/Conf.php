<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Conf extends Model
{
    protected $table = 'conf';
    public $timestamps = false;
    protected $guarded = [];


    public function firmaObj()
    {
        return $this->belongsTo(Firma::class, 'firma');
    }

    public function legacyUser()
    {
        return $this->belongsTo(LegacyUser::class, 'userid');
    }

    // ── getPriceGroups: всі цінові групи для форми товару ─────────────────────

    public static function getPriceGroups($fid)
    {
        return self::where('type', 'tgroup')
            ->where(fn($q) => $q->where('firma', $fid)->orWhere('constanta', '1'))
            ->orderBy('name')
            ->get();
    }

    // ── getFilterTags: теги-фільтри для форми товару ──────────────────────────

    public static function getFilterTags($fid)
    {
        return self::where('type', 'filter')
            ->where(fn($q) => $q->where('firma', $fid)->orWhere('constanta', '1'))
            ->orderBy('name')
            ->get();
    }
}
