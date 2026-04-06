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

    // ── getLatest: новини для вибору в описах товару ──────────────────────────

    public static function getLatest($fid)
    {
        return self::where('firma', $fid)
            ->orderByDesc('id')
            ->get(['id', 'title']);
    }
}
