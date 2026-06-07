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

    public static function normalizeLocale(?string $locale): string
    {
        $locale = strtolower(trim((string) $locale));

        return match (true) {
            str_starts_with($locale, 'ua'),
            str_starts_with($locale, 'uk') => 'ua',
            str_starts_with($locale, 'en') => 'en',
            default => 'ru',
        };
    }

    public static function localizedValue(?string $locale, $ruValue, $uaValue = '', $enValue = '')
    {
        $locale = self::normalizeLocale($locale);
        $ru = trim((string) ($ruValue ?? ''));
        $ua = trim((string) ($uaValue ?? ''));
        $en = trim((string) ($enValue ?? ''));

        return match ($locale) {
            'ua' => $ua !== '' ? $ua : ($ru !== '' ? $ru : $en),
            'en' => $en !== '' ? $en : ($ru !== '' ? $ru : $ua),
            default => $ru !== '' ? $ru : ($ua !== '' ? $ua : $en),
        };
    }

    public static function getCatalogTree($firmaId = 2, ?string $locale = 'ru')
    {
        $columns = Schema::getColumnListing('field');
        $hasColumn = fn(string $column) => in_array($column, $columns, true);
        $locale = self::normalizeLocale($locale);

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
        foreach (['valua', 'valen', 'description', 'descriptionua', 'descriptionen', 'link', 'news_catalog_id', 'nw', 'num', 'visible', 'firstpage'] as $column) {
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

        $mapNode = function ($item) use (&$mapNode, $childrenByParent, $locale) {
            $visibleValue = $item->visible ?? '0';
            $firstPageValue = $item->firstpage ?? '0';
            $nameRu = $item->val ?? '';
            $nameUa = $item->valua ?? '';
            $nameEn = $item->valen ?? '';
            $descriptionRu = $item->description ?? '';
            $descriptionUa = $item->descriptionua ?? '';
            $descriptionEn = $item->descriptionen ?? '';

            $children = $childrenByParent
                ->get((string) $item->id, collect())
                ->map(fn($child) => $mapNode($child))
                ->values()
                ->all();

            return [
                'id' => (int) $item->id,
                'name' => self::localizedValue($locale, $nameRu, $nameUa, $nameEn),
                'id_field' => (int) $item->id,
                'val_field' => $nameRu,
                'name_ru' => $nameRu,
                'name_ua' => $nameUa,
                'name_en' => $nameEn,
                'link' => trim((string) ($item->link ?? '')),
                'description' => self::localizedValue($locale, $descriptionRu, $descriptionUa, $descriptionEn),
                'description_ru' => $descriptionRu,
                'description_ua' => $descriptionUa,
                'description_en' => $descriptionEn,
                'num' => (int) ($item->num ?? 0),
                'news_catalog_id' => isset($item->news_catalog_id) && $item->news_catalog_id !== null
                    ? (int) $item->news_catalog_id
                    : ((int) ($item->nw ?? 0) > 0 ? (int) $item->nw : null),
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

    public static function getRegionsList($firmaId = 2, ?string $locale = 'ru')
    {
        $columns = Schema::getColumnListing('field');
        $hasColumn = fn(string $column) => in_array($column, $columns, true);
        $locale = self::normalizeLocale($locale);

        $query = self::query()
            ->where('keyfield', 'city');

        if ($firmaId !== null && $firmaId !== '') {
            $query->where('firma', $firmaId);
        }

        if ($hasColumn('num')) {
            $query->orderBy('num');
        }

        $query->orderBy('id');

        $select = ['id', 'val'];
        foreach (['valua', 'valen', 'num'] as $column) {
            if ($hasColumn($column)) {
                $select[] = $column;
            }
        }

        return $query->select($select)->get()->map(function ($item) use ($locale) {
            $nameRu = $item->val ?? '';
            $nameUa = $item->valua ?? '';
            $nameEn = $item->valen ?? '';

            return [
                'id' => (int) $item->id,
                'name' => self::localizedValue($locale, $nameRu, $nameUa, $nameEn),
                'name_ru' => $nameRu,
                'name_ua' => $nameUa,
                'name_en' => $nameEn,
                'num' => (int) ($item->num ?? 0),
            ];
        })->values();
    }

    public static function applyLocaleToCatalogItems($items, ?string $locale = 'ru')
    {
        return collect($items)->map(function ($item) use ($locale) {
            $name = self::localizedValue($locale, $item->val ?? '', $item->valua ?? '', $item->valen ?? '');
            $item->val = $name;
            $item->name = $name;
            $item->description_view = self::localizedValue($locale, $item->description ?? '', $item->descriptionua ?? '', $item->descriptionen ?? '');

            return $item;
        });
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
        $firmas = self::catalogFirmaScope($firmaId);

        return self::where(function ($query) {
                $query->where('idkeyfield', '0')
                    ->orWhere('idkeyfield', 0)
                    ->orWhereNull('idkeyfield')
                    ->orWhere('idkeyfield', '');
            })
            ->where('keyfield', 'catalog')
            ->whereIn('firma', $firmas)
            ->orderBy('num')
            ->orderBy('firma')
            ->get();
    }

    // ── getCatalogSubs: підрозділи каталогу, згруповані по idkeyfield ─────────

    public static function getCatalogSubs($firmaId)
    {
        $firmas = self::catalogFirmaScope($firmaId);

        return self::where(function ($query) {
                $query->whereNotNull('idkeyfield')
                    ->where('idkeyfield', '<>', '')
                    ->where('idkeyfield', '<>', '0');
            })
            ->where('keyfield', 'catalog')
            ->whereIn('firma', $firmas)
            ->orderBy('num')
            ->orderBy('firma')
            ->get()
            ->groupBy('idkeyfield');
    }

    public static function catalogFirmaScope($firmaId): array
    {
        $firmas = [0];
        $current = (int) $firmaId;

        if ($current > 0) {
            $firmas[] = $current;
        }

        if (Schema::hasTable('project') && Schema::hasColumn('project', 'constanta')) {
            $marketplaceFirmas = Project::query()
                ->where('constanta', 1)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->filter(fn ($id) => $id > 0)
                ->all();

            $firmas = array_merge($firmas, $marketplaceFirmas);
        }

        return array_values(array_unique($firmas));
    }
}
