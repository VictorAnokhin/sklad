<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class Field extends Model
{
    protected $table = 'field';
    public $timestamps = false;
    protected $guarded = [];


    public function firmaObj()
    {
        return $this->belongsTo(Firma::class, 'firma');
    }

    public static function getCatalogTree($firmaId = 2)
    {
        $columns = Schema::getColumnListing('field');
        $hasColumn = fn(string $column) => in_array($column, $columns, true);

        $query = self::query()->where('keyfield', 'catalog');

        if ($hasColumn('num')) {
            $query->orderBy('num');
        }

        $query->orderBy('id');

        if ($firmaId !== null && $firmaId !== '') {
            $query->where('firma', $firmaId);
        }

        if ($hasColumn('visible')) {
            $query->where('visible', '1');
        }

        $select = ['id', 'idkeyfield', 'val'];
        foreach (['valua', 'valen', 'description', 'descriptionua', 'descriptionen', 'num', 'visible', 'firstpage'] as $column) {
            if ($hasColumn($column)) {
                $select[] = $column;
            }
        }

        $items = $query->select($select)->get();

        $childrenByParent = $items
            ->filter(function ($item) {
                return !in_array((string) $item->idkeyfield, ['', '0'], true) && $item->idkeyfield !== null;
            })
            ->groupBy(function ($item) {
                return (string) $item->idkeyfield;
            });

        $mapNode = function ($item) use (&$mapNode, $childrenByParent) {
            $visibleValue = $item->visible ?? '0';
            $firstPageValue = $item->firstpage ?? '0';

            $children = $childrenByParent
                ->get((string) $item->id, collect())
                ->map(fn($child) => $mapNode($child))
                ->values()
                ->all();

            return [
                'id' => (int) $item->id,
                'name' => $item->val ?: '',
                'id_field' => (int) $item->id,
                'val_field' => $item->val ?: '',
                'name_ua' => $item->valua ?? '',
                'name_en' => $item->valen ?? '',
                'description' => $item->description ?? '',
                'description_ua' => $item->descriptionua ?? '',
                'description_en' => $item->descriptionen ?? '',
                'num' => (int) ($item->num ?? 0),
                'visible' => is_scalar($visibleValue) ? (string) $visibleValue === '1' : false,
                'firstpage' => is_scalar($firstPageValue) ? (string) $firstPageValue === '1' : false,
                'children' => $children,
            ];
        };

        return $items
            ->filter(function ($item) {
                return in_array((string) $item->idkeyfield, ['', '0'], true) || $item->idkeyfield === null;
            })
            ->map(fn($item) => $mapNode($item))
            ->values();
    }

    // ── getPers: markup % for a given section id ──────────────────────────────

    public static function getPers($id)
    {
        if (!$id)
            return 0;
        return self::where('id', $id)->value('pers') ?? 0;
    }

    // ── getSectionsList: full catalog list for navigation ─────────────────────

    public static function getSectionsList($firmaId)
    {
        return self::where('keyfield', 'catalog')
            ->where('firma', $firmaId)
            ->orderBy('num')
            ->get();
    }
    // ── getCatalogTops: верхній рівень каталогу ───────────────────────────────

    public static function getCatalogTops($firmaId)
    {
        return self::where(function ($query) {
                $query->where('idkeyfield', '0')
                    ->orWhere('idkeyfield', 0)
                    ->orWhereNull('idkeyfield')
                    ->orWhere('idkeyfield', '');
            })
            ->where('keyfield', 'catalog')
            ->where('firma', $firmaId)
            ->orderBy('num')
            ->get();
    }

    // ── getCatalogSubs: підрозділи каталогу, згруповані по idkeyfield ─────────

    public static function getCatalogSubs($firmaId)
    {
        return self::where(function ($query) {
                $query->whereNotNull('idkeyfield')
                    ->where('idkeyfield', '<>', '')
                    ->where('idkeyfield', '<>', '0');
            })
            ->where('keyfield', 'catalog')
            ->where('firma', $firmaId)
            ->orderBy('num')
            ->get()
            ->groupBy('idkeyfield');
    }
}
