<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Goods extends Model
{
    protected $table = 'comp';
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

    private static function displayNameSql(): string
    {
        return "COALESCE(
            NULLIF(d.name, ''),
            NULLIF(d.name_ua, ''),
            NULLIF(d.name_en, ''),
            NULLIF(comp.nickname, ''),
            NULLIF(comp.namedoc, ''),
            NULLIF(comp.name, ''),
            CONCAT('Товар #', comp.id)
        )";
    }

    // ── getListQuery: filtered comp+price query builder ───────────────────────

    public static function getListQuery($fid, $idcaption, $idglava, $filters)
    {
        $fName = $filters['fName'] ?? '';
        $filterBrand = $filters['filterBrand'] ?? '';
        $skladNone = $filters['skladNone'] ?? '';

        $query = self::query()
            ->leftJoin('descript as d', function ($join) {
                $join->on('d.pnum', '=', 'comp.id')
                    ->whereColumn('d.firma', 'comp.firma');
            })
            ->where(function ($q) use ($fid) {
                $q->where('comp.firma', $fid)->orWhere('comp.constanta', '1');
            })
            ->select(
                'comp.*',
                DB::raw(self::displayNameSql() . ' as name'),
                DB::raw('COALESCE(d.name_ua, "") as name_ua'),
                DB::raw('COALESCE(d.name_en, "") as name_en'),
                DB::raw('COALESCE(d.description, "") as description'),
                DB::raw('COALESCE(d.description_ua, "") as description_ua'),
                DB::raw('COALESCE(d.description_en, "") as description_en')
            );

        if ($idglava && $idcaption) {
            $query->where('comp.idglava', $idglava)
                ->where('comp.idcaption', $idcaption);
        } elseif ($idglava) {
            $query->where(function ($q) use ($idglava) {
                $q->where('comp.idglava', $idglava)
                    ->orWhere('comp.idcaption', $idglava);
            });
        } elseif ($idcaption) {
            $query->where('comp.idcaption', $idcaption);
        }
        if ($fName) {
            $query->where(function ($search) use ($fName) {
                $search->where('d.name', 'like', "%{$fName}%")
                    ->orWhere('d.name_ua', 'like', "%{$fName}%")
                    ->orWhere('d.name_en', 'like', "%{$fName}%")
                    ->orWhere('d.description', 'like', "%{$fName}%")
                    ->orWhere('d.description_ua', 'like', "%{$fName}%")
                    ->orWhere('d.description_en', 'like', "%{$fName}%")
                    ->orWhere('comp.htmlkeyspop', 'like', "%{$fName}%");
            });
        }
        if ($filterBrand)
            $query->where('comp.idtype', $filterBrand);
        if ($skladNone !== '1')
            $query->where('comp.sklad', '1');

        return $query;
    }

    // ── init: orchestrates list page data ────────────────────────────────────

    public static function init($fid, $idcaption, $idglava, $pos, $pos2, $sort, $filters)
    {
        $query = self::getListQuery($fid, $idcaption, $idglava, $filters);

        $total = (clone $query)->count();
        $hasCategorySelection = !empty($idcaption) || !empty($idglava);

        if ($hasCategorySelection) {
            $orderCol = match ($sort) {
                'description' => 'name',
                default => 'comp.' . $sort,
            };

            $comps = $query->orderBy($orderCol)->offset($pos)->limit($pos2)->get();
        } else {
            $comps = $query
                ->orderByDesc('comp.hit')
                ->orderBy('name')
                ->offset($pos)
                ->limit($pos2)
                ->get();
        }

        $comps = self::attachListPrices($comps, $fid);

        if ($hasCategorySelection && in_array($sort, ['pay', 'pay1', 'oldpay', 'count'], true)) {
            $sortField = match ($sort) {
                'pay' => 'price_pay',
                'pay1' => 'price_pay1',
                'oldpay' => 'price_oldpay',
                'count' => 'price_count',
            };

            $comps = $comps->sortBy($sortField)->values();
        }

        $pers = Field::getPers($idcaption ?: $idglava);
        $sections = Field::getSectionsList($fid);
        $tops = Field::getCatalogTops($fid);
        $subs = Field::getCatalogSubs($fid);

        return [
            'comps' => $comps,
            'total' => $total,
            'pers' => $pers,
            'sections' => $sections,
            'tops' => $tops,
            'subs' => $subs,
        ];
    }

    private static function attachListPrices($comps, $fid)
    {
        if ($comps->isEmpty()) {
            return $comps;
        }

        $retailGroupId = DB::table('conf')
            ->where('type', 'tgroup')
            ->where('status', '1')
            ->where(function ($query) use ($fid) {
                $query->where('firma', $fid)
                    ->orWhere('constanta', '1');
            })
            ->orderByDesc('constanta')
            ->orderBy('id')
            ->value('id');

        $ids = $comps->pluck('id')->filter()->values();

        $priceQuery = DB::table('price')
            ->whereIn('pnum', $ids)
            ->where('firma', $fid)
            ->orderBy('pnum');

        if ($retailGroupId !== null) {
            $priceQuery->orderByRaw("CASE WHEN tgroup = ? THEN 0 ELSE 1 END", [$retailGroupId]);
        }

        $priceRows = $priceQuery
            ->orderBy('id')
            ->get()
            ->groupBy('pnum');

        $skladIds = $priceRows
            ->flatten(1)
            ->pluck('sklad')
            ->filter(fn ($value) => (string) $value !== '' && (string) $value !== '0')
            ->unique()
            ->values();

        $skladNames = $skladIds->isEmpty()
            ? collect()
            : DB::table('conf')
                ->where('type', 'sklads')
                ->whereIn('id', $skladIds)
                ->pluck('name', 'id');

        return $comps->map(function ($comp) use ($priceRows, $skladNames) {
            $price = $priceRows->get($comp->id)?->first();
            $comp->price_pay = $price->pay ?? $comp->pay ?? 0;
            $comp->price_pay1 = $price->pay1 ?? $comp->pay1 ?? 0;
            $comp->price_oldpay = $price->oldpay ?? 0;
            $comp->price_count = $price->count ?? 0;
            $comp->price_sklad = $price->sklad ?? $comp->sklad ?? 0;
            $comp->price_sklad_name = $skladNames->get($comp->price_sklad, '—');
            $comp->price_tgroup = $price->tgroup ?? null;
            return $comp;
        });
    }

    public static function attachPreferredPricesByItemFirma($comps)
    {
        if ($comps->isEmpty()) {
            return $comps;
        }

        $pairs = $comps
            ->map(fn($comp) => [
                'id' => (string) ($comp->id ?? ''),
                'firma' => (string) ($comp->firma ?? ''),
            ])
            ->filter(fn($pair) => $pair['id'] !== '' && $pair['firma'] !== '')
            ->values();

        if ($pairs->isEmpty()) {
            return $comps->map(function ($comp) {
                $comp->price_pay = $comp->pay ?? 0;
                $comp->price_pay1 = $comp->pay1 ?? 0;
                $comp->price_oldpay = $comp->oldpay ?? 0;
                $comp->price_count = 0;
                $comp->price_sklad = $comp->sklad ?? 0;
                $comp->price_sklad_name = '—';
                $comp->price_tgroup = null;
                return $comp;
            });
        }

        $firmaIds = $pairs->pluck('firma')->unique()->values();
        $productIds = $pairs->pluck('id')->unique()->values();

        $priceGroups = DB::table('conf')
            ->where('type', 'tgroup')
            ->whereIn('firma', $firmaIds)
            ->orderBy('id')
            ->get()
            ->groupBy('firma');

        $retailGroups = $priceGroups->map(function ($rows) {
            $retail = $rows->first(fn($row) => (string) ($row->status ?? '0') === '1');
            return $retail ? (string) $retail->id : null;
        });

        $wholesaleGroups = $priceGroups->map(function ($rows) {
            $wholesale = $rows->first(fn($row) => (string) ($row->status ?? '0') !== '1');
            return $wholesale ? (string) $wholesale->id : null;
        });

        $priceRows = DB::table('price')
            ->whereIn('firma', $firmaIds)
            ->whereIn('pnum', $productIds)
            ->orderBy('firma')
            ->orderBy('pnum')
            ->orderBy('id')
            ->get()
            ->groupBy(fn($row) => $row->firma . ':' . $row->pnum);

        $skladIds = $priceRows
            ->flatten(1)
            ->pluck('sklad')
            ->filter(fn ($value) => (string) $value !== '' && (string) $value !== '0')
            ->unique()
            ->values();

        $skladNames = $skladIds->isEmpty()
            ? collect()
            : DB::table('conf')
                ->where('type', 'sklads')
                ->whereIn('id', $skladIds)
                ->pluck('name', 'id');

        return $comps->map(function ($comp) use ($priceRows, $retailGroups, $wholesaleGroups, $skladNames) {
            $key = ($comp->firma ?? '') . ':' . ($comp->id ?? '');
            $rows = $priceRows->get($key, collect());
            $retailGroupId = $retailGroups->get((string) ($comp->firma ?? ''));
            $wholesaleGroupId = $wholesaleGroups->get((string) ($comp->firma ?? ''));

            $price = $rows->first(function ($row) use ($retailGroupId) {
                return $retailGroupId !== null && (string) $row->tgroup === (string) $retailGroupId;
            }) ?: $rows->first();

            $wholesalePrice = $rows->first(function ($row) use ($wholesaleGroupId) {
                return $wholesaleGroupId !== null && (string) $row->tgroup === (string) $wholesaleGroupId;
            });

            $comp->price_pay = $price->pay ?? $comp->pay ?? 0;
            $comp->price_pay1 = $price->pay1 ?? $comp->pay1 ?? 0;
            $comp->price_oldpay = $price->oldpay ?? $comp->oldpay ?? 0;
            $comp->price_count = $price->count ?? 0;
            $comp->price_sklad = $price->sklad ?? $comp->sklad ?? 0;
            $comp->price_sklad_name = $skladNames->get($comp->price_sklad, '—');
            $comp->price_tgroup = $price->tgroup ?? null;
            $comp->wholesale_price = $wholesalePrice->pay ?? null;
            $comp->wholesale_oldpay = $wholesalePrice->oldpay ?? null;
            $comp->wholesale_from = $wholesalePrice->count ?? null;
            $comp->wholesale_tgroup = $wholesalePrice->tgroup ?? null;
            return $comp;
        });
    }

    // ── show: load single product + related data ──────────────────────────────

    public static function showGoods($pnum, $fid)
    {
        $comp = $pnum !== '0' ? self::find($pnum) : null;
        $descript = Descript::getForGoods($pnum, $fid);

        $priceGroups = Conf::getPriceGroups($fid);
        $prices = Price::getForGoods($pnum, $fid);
        $tops = Field::getCatalogTops($fid);
        $subs = Field::getCatalogSubs($fid);
        $news = News::getLatest($fid);
        $filterTags = Conf::getFilterTags($fid);

        return [
            'comp' => $comp,
            'descript' => $descript,
            'priceGroups' => $priceGroups,
            'prices' => $prices,
            'tops' => $tops,
            'subs' => $subs,
            'news' => $news,
            'filterTags' => $filterTags,
        ];
    }

    // ── Web / API methods ─────────────────────────────────────────────────────

    public static function getWebGoodsBySection($fid, $id, $limit, $offset)
    {
        $query = self::query()
            ->leftJoin('descript as d', function ($join) {
                $join->on('d.pnum', '=', 'comp.id')
                    ->whereColumn('d.firma', '=', 'comp.firma');
            })
            ->where('comp.web', '1')
            ->where('comp.firma', $fid)
            ->where(function ($q) use ($id) {
                $q->where('comp.idcaption', $id)
                    ->orWhere('comp.idglava', $id);
            });

        $totalCount = $query->count();

        $goods = $query->select(
            'comp.id',
            DB::raw(self::displayNameSql() . ' as name'),
            DB::raw('COALESCE(d.name_ua, "") as name_ua'),
            DB::raw('COALESCE(d.name_en, "") as name_en'),
            DB::raw('COALESCE(d.description, "") as description'),
            DB::raw('COALESCE(d.description_ua, "") as description_ua'),
            DB::raw('COALESCE(d.description_en, "") as description_en'),
            'comp.nfoto',
            'comp.nfoto1',
            'comp.pay',
            'comp.firma'
        )
            ->offset($offset)
            ->limit($limit)
            ->get();

        $goods = self::attachPreferredPricesByItemFirma($goods)
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'name_ua' => $item->name_ua,
                    'name_en' => $item->name_en,
                    'description' => $item->description,
                    'description_ua' => $item->description_ua,
                    'description_en' => $item->description_en,
                    'price' => (float) ($item->price_pay ?? 0),
                    'oldPrice' => (float) ($item->price_oldpay ?? 0),
                    'wholesalePrice' => $item->wholesale_price !== null ? (float) $item->wholesale_price : null,
                    'wholesaleOldPrice' => $item->wholesale_oldpay !== null ? (float) $item->wholesale_oldpay : null,
                    'wholesaleFrom' => $item->wholesale_from !== null ? (int) $item->wholesale_from : null,
                    'count' => (int) ($item->price_count ?? 0),
                    'image' => $item->nfoto,
                    'image_thumb' => $item->nfoto1,
                ];
            });

        return [
            'goods' => $goods,
            'total' => $totalCount,
        ];
    }

    // ── getHits: paginated hit goods for API ─────────────────────────────────

    public static function getHits($fid, $limit = 10, $offset = 0)
    {
        $hits = self::query()
            ->leftJoin('descript as d', function ($join) {
                $join->on('d.pnum', '=', 'comp.id')
                    ->whereColumn('d.firma', '=', 'comp.firma');
            })
            ->where('comp.web', '1')
            ->where('comp.firma', $fid)
            ->select(
                'comp.id',
                DB::raw(self::displayNameSql() . ' as name'),
                DB::raw('COALESCE(d.name_ua, "") as name_ua'),
                DB::raw('COALESCE(d.name_en, "") as name_en'),
                DB::raw('COALESCE(d.description, "") as description'),
                DB::raw('COALESCE(d.description_ua, "") as description_ua'),
                DB::raw('COALESCE(d.description_en, "") as description_en'),
                'comp.nfoto',
                'comp.nfoto1',
                'comp.pay',
                'comp.firma'
            )
            ->orderBy('comp.hit', 'desc')
            ->offset($offset)
            ->limit($limit)
            ->get();

        return self::attachPreferredPricesByItemFirma($hits)
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'name_ua' => $item->name_ua,
                    'name_en' => $item->name_en,
                    'description' => $item->description,
                    'description_ua' => $item->description_ua,
                    'description_en' => $item->description_en,
                    'price' => (float) ($item->price_pay ?? 0),
                    'oldPrice' => (float) ($item->price_oldpay ?? 0),
                    'wholesalePrice' => $item->wholesale_price !== null ? (float) $item->wholesale_price : null,
                    'wholesaleOldPrice' => $item->wholesale_oldpay !== null ? (float) $item->wholesale_oldpay : null,
                    'wholesaleFrom' => $item->wholesale_from !== null ? (int) $item->wholesale_from : null,
                    'count' => (int) ($item->price_count ?? 0),
                    'image' => $item->nfoto,
                    'image_thumb' => $item->nfoto1,
                ];
            });
    }

    // ── save: insert or update comp + prices + descript ────────────────────────

    public static function saveGoods($pnum, $fid, $compData, $priceRows, $descData, $fotoMap)
    {
        foreach ([
            'idcaption', 'idglava', 'idtype', 'firma', 'nickname', 'namedoc',
            'garant', 'htmldescr', 'htmlkeys', 'htmlkeyspop', 'nvideo1', 'nvideo2'
        ] as $stringField) {
            if (!array_key_exists($stringField, $compData) || $compData[$stringField] === null) {
                $compData[$stringField] = '';
            } else {
                $compData[$stringField] = (string) $compData[$stringField];
            }
        }

        // ── Price groups upsert
        foreach ($priceRows as $gid => $row) {
            $existing = DB::table('price')
                ->where('tgroup', $gid)->where('pnum', $pnum)->where('firma', $fid)
                ->exists();
            if ($existing) {
                DB::table('price')
                    ->where('tgroup', $gid)->where('pnum', $pnum)->where('firma', $fid)
                    ->update($row);
            } else {
                DB::table('price')->insert(array_merge($row, [
                    'tgroup' => $gid,
                    'pnum' => $pnum,
                    'firma' => $fid,
                ]));
            }
        }

        // ── Main comp data
        $compData = array_merge($compData, $fotoMap);

        if ($pnum === '' || $pnum === '0') {
            $compData['cod'] = date('dmHis') . rand(10, 99);
            $compData['dt'] = now();
            $pnum = (string) DB::table('comp')->insertGetId($compData);
        } else {
            DB::table('comp')->where('id', $pnum)->update($compData);
        }

        // ── Descript upsert
        foreach (['name', 'name_ua', 'name_en', 'description', 'description_ua', 'description_en'] as $stringField) {
            if (!array_key_exists($stringField, $descData) || $descData[$stringField] === null) {
                $descData[$stringField] = '';
            } else {
                $descData[$stringField] = (string) $descData[$stringField];
            }
        }

        $hasDesc = DB::table('descript')->where('pnum', $pnum)->where('firma', $fid)->exists();
        if ($hasDesc) {
            DB::table('descript')->where('pnum', $pnum)->where('firma', $fid)->update($descData);
        } else {
            DB::table('descript')->insert(array_merge($descData, ['pnum' => $pnum, 'firma' => $fid]));
        }

        return $pnum;
    }

    // ── delete: remove product ────────────────────────────────────────────────

    public static function deleteGoods($id, $cod, $fid)
    {
        if (DB::table('z_body')->where('pnum', $id)->exists()) {
            return false; // used in documents
        }

        DB::table('price')->where('pnum', $id)->where('firma', $fid)->delete();
        DB::table('descript')->where('pnum', $id)->where('firma', $fid)->delete();
        DB::table('comp')->where('id', $id)->where('firma', $fid)->delete();

        return true;
    }
}
