<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Field extends Model
{
    protected $table = 'field';
    public $timestamps = false;
    protected $guarded = [];


    public function firmaObj()
    {
        return $this->belongsTo(Firma::class, 'firma');
    }

    public static function getCatalogTree()
    {
        $sections = self::where('keyfield', 'catalog')
            ->orderBy('num')
            ->get();

        $tops = $sections->where('idkeyfield', '')->values();
        $subs = $sections->where('idkeyfield', '!=', '')->groupBy('idkeyfield');

        return $tops->map(function ($top) use ($subs) {
            $top->subs = $subs->get($top->id, []);
            return $top;
        });
    }

}
