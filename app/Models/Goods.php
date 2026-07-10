<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Support\MediaUrl;

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

    public function ratings()
    {
        return $this->hasMany(Rating::class, 'comp_id');
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

    private static function localizedValue(?string $locale, $ruValue, $uaValue = '', $enValue = '')
    {
        return Field::localizedValue($locale, $ruValue, $uaValue, $enValue);
    }

    private static function applyMarketplaceGoodsScope($query, $fid)
    {
        return $query->where(function ($nested) use ($fid) {
            $nested->where('comp.firma', $fid)
                ->orWhere('comp.constanta', 1);
        });
    }

    // ── getListQuery: filtered comp+price query builder ───────────────────────

    public static function getListQuery($fid, $idcaption, $idglava, $filters)
    {
        $fName = $filters['fName'] ?? '';
        $filterBrand = $filters['filterBrand'] ?? '';
        $inStockOnly = ($filters['skladNone'] ?? '') === '1';
        $showAllGoods = ($filters['showAllGoods'] ?? '') === '1';
        $hitOnly = ($filters['hitOnly'] ?? '') === '1';

        $query = self::query()
            ->leftJoin('descript as d', function ($join) {
                $join->on('d.pnum', '=', 'comp.id')
                    ->whereColumn('d.firma', 'comp.firma');
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

        if (! $showAllGoods) {
            $query->where('comp.firma', $fid);
        }

        if ($idglava && $idcaption) {
            $query->where('comp.idglava', $idglava)
                ->where('comp.idcaption', $idcaption);
        } elseif ($idglava) {
            $query->where('comp.idglava', $idglava);
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
        if ($inStockOnly)
            $query->where('comp.sklad', '1');
        if ($hitOnly)
            $query->where('comp.hit', '1');
        return $query;
    }

    // ── init: orchestrates list page data ────────────────────────────────────

    public static function init($fid, $idcaption, $idglava, $pos, $pos2, $sort, $filters, ?string $locale = 'ru')
    {
        $query = self::getListQuery($fid, $idcaption, $idglava, $filters);
        // Всегда сортируем по полю `top` в приоритетном порядке
        $query->orderByDesc('comp.top');

        $total = (clone $query)->count();
        $hasCategorySelection = !empty($idcaption) || !empty($idglava);

        if ($hasCategorySelection) {
            if ($sort === 'top') {
                $comps = $query->orderByDesc('top')
                    ->offset($pos)
                    ->limit($pos2)
                    ->get();
            } else {
                $orderCol = match ($sort) {
                    'description' => 'name',
                    default => 'comp.' . $sort,
                };

                $comps = $query->orderBy($orderCol)->offset($pos)->limit($pos2)->get();
            }
        } else {
            $comps = $query
                ->orderByDesc('comp.id')
                ->orderBy('name')
                ->offset($pos)
                ->limit($pos2)
                ->get();
        }

        $comps = $comps->map(function ($comp) use ($locale) {
            $comp->name = self::localizedValue($locale, $comp->name ?? '', $comp->name_ua ?? '', $comp->name_en ?? '');
            $comp->description = self::localizedValue($locale, $comp->description ?? '', $comp->description_ua ?? '', $comp->description_en ?? '');

            return $comp;
        });

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
        $sections = Field::applyLocaleToCatalogItems(Field::getSectionsList($fid), $locale);
        $tops = Field::applyLocaleToCatalogItems(Field::getCatalogTops($fid), $locale);
        $subs = Field::getCatalogSubs($fid)
            ->map(fn ($group) => Field::applyLocaleToCatalogItems($group, $locale));

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

        $firmaIds = $comps
            ->map(fn ($comp) => (string) (($comp->firma ?? '') !== '' ? $comp->firma : $fid))
            ->unique()
            ->values();

        $productIds = $comps->pluck('id')->filter()->values();

        $priceGroups = DB::table('conf')
            ->where('type', 'tgroup')
            ->whereIn('firma', $firmaIds)
            ->orderByDesc('status')
            ->get()
            ->groupBy(fn ($row) => (string) ($row->firma ?? ''));

        $retailGroups = $priceGroups->map(function ($rows) {
            $retail = $rows->first(fn ($row) => (string) ($row->status ?? '0') === '1');

            return $retail ? (string) $retail->id : null;
        });

        $priceRows = DB::table('price')
            ->whereIn('firma', $firmaIds)
            ->whereIn('pnum', $productIds)
            ->orderBy('firma')
            ->orderBy('pnum')
            ->orderBy('id')
            ->get()
            ->groupBy(fn ($row) => $row->firma . ':' . $row->pnum);

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

        $stockRows = Schema::hasTable('price_sklad')
            ? DB::table('price_sklad')
                ->whereIn('firma', $firmaIds)
                ->whereIn('pnum', $productIds)
                ->get()
                ->groupBy(fn ($row) => $row->firma . ':' . $row->pnum . ':' . $row->sklad)
            : collect();

        return $comps->map(function ($comp) use ($priceRows, $skladNames, $stockRows, $retailGroups, $fid) {
            $itemFirma = (string) (($comp->firma ?? '') !== '' ? $comp->firma : $fid);
            $key = $itemFirma . ':' . ($comp->id ?? '');
            $rows = $priceRows->get($key, collect());
            $retailGroupId = $retailGroups->get($itemFirma);

            $price = ($retailGroupId !== null
                ? $rows->first(fn ($row) => (string) $row->tgroup === (string) $retailGroupId)
                : null)
                ?: $rows->first();

            $comp->price_pay = $price->pay ?? $comp->pay ?? 0;
            $comp->price_pay1 = $price->pay1 ?? $comp->pay1 ?? 0;
            $comp->price_oldpay = $price->oldpay ?? 0;
            $comp->price_count = $price->count ?? 0;
            $comp->price_sklad = $price->sklad ?? $comp->sklad ?? 0;
            $comp->price_sklad_name = $skladNames->get($comp->price_sklad, '—');
            $comp->price_sklad_count = $stockRows
                ->get($itemFirma . ':' . ($comp->id ?? '') . ':' . ($comp->price_sklad ?? 0), collect())
                ->sum(fn ($row) => (float) ($row->count ?? 0));
            $comp->price_tgroup = $price->tgroup ?? null;

            return $comp;
        });
    }

    public static function attachPreferredPricesByItemFirma($comps, $targetTgroupId = null)
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
                $comp->price_sklad_count = 0;
                $comp->price_tgroup = null;
                return $comp;
            });
        }

        $firmaIds = $pairs->pluck('firma')->unique()->values();
        $productIds = $pairs->pluck('id')->unique()->values();

        // Fetch all price groups for these firms to identify the retail group
        $priceGroups = DB::table('conf')
            ->where('type', 'tgroup')
            ->whereIn('firma', $firmaIds)
            ->orderByDesc('status') // Usually status='1' is retail, put it first
            ->get()
            ->groupBy('firma');

        $retailGroups = $priceGroups->map(function ($rows) {
            $retail = $rows->first(fn($row) => (string) ($row->status ?? '0') === '1');
            return $retail ? (string) $retail->id : null;
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

        $stockRows = Schema::hasTable('price_sklad')
            ? DB::table('price_sklad')
                ->whereIn('firma', $firmaIds)
                ->whereIn('pnum', $productIds)
                ->get()
                ->groupBy(fn ($row) => $row->firma . ':' . $row->pnum . ':' . $row->sklad)
            : collect();

        return $comps->map(function ($comp) use ($priceRows, $retailGroups, $skladNames, $stockRows, $targetTgroupId) {
            $key = ($comp->firma ?? '') . ':' . ($comp->id ?? '');
            $rows = $priceRows->get($key, collect());
            
            $retailGroupId = $retailGroups->get((string) ($comp->firma ?? ''));

            // 1. Try to find row for the user's specific tgroup
            // 2. Fallback to retail group row
            // 3. Fallback to first available row
            $price = $rows->first(fn($row) => $targetTgroupId !== null && (string) $row->tgroup === (string) $targetTgroupId)
                  ?: $rows->first(fn($row) => $retailGroupId !== null && (string) $row->tgroup === (string) $retailGroupId)
                  ?: $rows->first();

            $comp->price_pay = $price->pay ?? $comp->pay ?? 0;        // Retail price
            $comp->price_pay1 = $price->pay1 ?? $comp->pay1 ?? 0;     // Discount price
            $comp->price_count = $price->count ?? 0;                  // Threshold quantity
            $comp->price_oldpay = $price->oldpay ?? 0;
            $comp->price_sklad = $price->sklad ?? $comp->sklad ?? 0;
            $comp->price_sklad_name = $skladNames->get($comp->price_sklad, '—');
            $comp->price_sklad_count = $stockRows
                ->get(($comp->firma ?? '') . ':' . ($comp->id ?? '') . ':' . ($comp->price_sklad ?? 0), collect())
                ->sum(fn ($row) => (float) ($row->count ?? 0));
            $comp->price_tgroup = $price->tgroup ?? null;
            
            return $comp;
        });
    }

    public static function attachRatings($comps, ?int $userId = null)
    {
        if ($comps->isEmpty()) {
            return $comps;
        }

        if (!Schema::hasTable('rating')) {
            return $comps->map(fn ($comp) => self::applyRatingFields($comp, null, null));
        }

        $compIds = $comps
            ->pluck('id')
            ->filter(fn ($value) => (string) $value !== '')
            ->map(fn ($value) => (int) $value)
            ->unique()
            ->values();

        if ($compIds->isEmpty()) {
            return $comps->map(fn ($comp) => self::applyRatingFields($comp, null, null));
        }

        $stats = DB::table('rating')
            ->select(
                'comp_id',
                DB::raw('AVG(rating) as rating_avg'),
                DB::raw('COUNT(*) as rating_count')
            )
            ->whereIn('comp_id', $compIds)
            ->groupBy('comp_id')
            ->get()
            ->keyBy(fn ($row) => (int) $row->comp_id);

        $userRatings = collect();

        if ($userId !== null) {
            $userRatings = DB::table('rating')
                ->where('user_id', $userId)
                ->whereIn('comp_id', $compIds)
                ->pluck('rating', 'comp_id');
        }

        return $comps->map(function ($comp) use ($stats, $userRatings) {
            $compId = (int) ($comp->id ?? 0);
            $stat = $stats->get($compId);
            $userRating = $userRatings->get($compId);

            return self::applyRatingFields($comp, $stat, $userRating);
        });
    }

    private static function applyRatingFields($comp, $stat, $userRating)
    {
        $comp->rating_avg = $stat ? round((float) ($stat->rating_avg ?? 0), 2) : 0.0;
        $comp->rating_count = $stat ? (int) ($stat->rating_count ?? 0) : 0;
        $comp->user_rating = $userRating !== null ? (int) $userRating : null;

        return $comp;
    }

    /**
     * Стан селектів «Категорія / Підкатегорія» на goods/show з урахуванням idglava та idcaption:
     * — підкатегорія: idglava = батько (верх), idcaption = дочірній field;
     * — лише верхній рівень: idglava = 0, idcaption = id верхнього розділу;
     * — відновлення батька, якщо idcaption є, а idglava порожній (legacy / імпорт).
     *
     * @param  \Illuminate\Support\Collection<int, object>  $tops
     * @param  \Illuminate\Support\Collection  $subsGrouped  groupBy(idkeyfield) з Field::getCatalogSubs
     * @return array{selectedTop: string, selectedSub: string, availableSubs: \Illuminate\Support\Collection<int, object>}
     */
    public static function resolveCatalogCategoryFormState(?object $comp, string|int $firmaId, Collection $tops, Collection $subsGrouped): array
    {
        $isEmpty = static function ($v): bool {
            $s = trim((string) ($v ?? ''));

            return $s === '' || $s === '0';
        };

        $topIdSet = $tops->pluck('id')->mapWithKeys(static fn ($id) => [(string) (int) $id => true]);

        $findParentIdForSub = static function (string $subId) use ($subsGrouped): ?string {
            foreach ($subsGrouped->keys() as $parentKey) {
                $children = $subsGrouped->get($parentKey);
                if (! $children) {
                    continue;
                }
                foreach ($children as $child) {
                    if ((string) (int) ($child->id ?? 0) === $subId) {
                        return (string) (int) $parentKey;
                    }
                }
            }

            return null;
        };

        $pickSubs = static function (string $parentId) use ($subsGrouped): Collection {
            if ($parentId === '' || $parentId === '0') {
                return collect();
            }
            foreach ($subsGrouped->keys() as $key) {
                if ((string) (int) $key === (string) (int) $parentId) {
                    return collect($subsGrouped->get($key) ?? []);
                }
            }

            return collect();
        };

        $idglava = $comp ? trim((string) ($comp->idglava ?? '')) : '';
        $idcaption = $comp ? trim((string) ($comp->idcaption ?? '')) : '';

        $selectedTop = '';
        $selectedSub = '';

        if (! $isEmpty($idglava)) {
            $selectedTop = (string) (int) $idglava;
            $selectedSub = $isEmpty($idcaption) ? '' : (string) (int) $idcaption;

            if ($selectedSub !== '') {
                $subsForTop = $pickSubs($selectedTop);
                $belongs = $subsForTop->contains(
                    static fn ($item) => (string) (int) ($item->id ?? 0) === $selectedSub
                );
                if (! $belongs) {
                    $realParent = $findParentIdForSub($selectedSub);
                    if ($realParent !== null) {
                        $selectedTop = $realParent;
                    }
                }
            }
        } elseif (! $isEmpty($idcaption)) {
            $cap = (string) (int) $idcaption;
            if ($topIdSet->has($cap)) {
                $selectedTop = $cap;
                $selectedSub = '';
            } else {
                $parent = $findParentIdForSub($cap);
                if ($parent === null) {
                    $row = Field::query()
                        ->where('keyfield', 'catalog')
                        ->whereIn('firma', Field::catalogFirmaScope($firmaId))
                        ->where('id', (int) $cap)
                        ->first();
                    if ($row) {
                        $pk = trim((string) ($row->idkeyfield ?? ''));
                        if ($pk !== '' && $pk !== '0') {
                            $parent = (string) (int) $pk;
                        }
                    }
                }
                if ($parent !== null) {
                    $selectedTop = $parent;
                    $selectedSub = $cap;
                } else {
                    $selectedTop = '';
                    $selectedSub = $cap;
                }
            }
        }

        $availableSubs = $pickSubs($selectedTop);

        if (! $isEmpty($selectedSub)) {
            $hasSub = $availableSubs->contains(
                static fn ($item) => (string) (int) ($item->id ?? 0) === (string) (int) $selectedSub
            );
            if (! $hasSub) {
                $row = Field::query()
                    ->where('keyfield', 'catalog')
                    ->whereIn('firma', Field::catalogFirmaScope($firmaId))
                    ->where('id', (int) $selectedSub)
                    ->first();
                if ($row) {
                    $availableSubs = $availableSubs->push($row)->sortBy('num')->values();
                }
            }
        }

        return [
            'selectedTop' => $selectedTop,
            'selectedSub' => $selectedSub,
            'availableSubs' => $availableSubs,
        ];
    }

    // ── show: load single product + related data ──────────────────────────────

    public static function showGoods($pnum, $fid, ?string $locale = 'ru')
    {
        $comp = $pnum !== '0' ? self::find($pnum) : null;
        $descript = Descript::getForGoods($pnum, $fid);

        $priceGroups = Conf::getPriceGroups($fid);
        $prices = Price::getForGoods($pnum, $fid);
        $tops = Field::applyLocaleToCatalogItems(Field::getCatalogTops($fid), $locale);
        $subs = Field::getCatalogSubs($fid)
            ->map(fn ($group) => Field::applyLocaleToCatalogItems($group, $locale));
        $catForm = self::resolveCatalogCategoryFormState($comp, $fid, collect($tops), collect($subs));
        $news = News::getLatest($fid, 5, $locale);
        $filterTags = Conf::getFilterTags($fid);

        return [
            'comp' => $comp,
            'descript' => $descript,
            'priceGroups' => $priceGroups,
            'prices' => $prices,
            'tops' => $tops,
            'subs' => $subs,
            'catalogSelectedTop' => $catForm['selectedTop'],
            'catalogSelectedSub' => $catForm['selectedSub'],
            'catalogAvailableSubs' => $catForm['availableSubs'],
            'news' => $news,
            'filterTags' => $filterTags,
        ];
    }

    // ── Web / API methods ─────────────────────────────────────────────────────

    /**
     * @return array<int, array{0: int, 1: int}>
     */
    private static function parseHtmlkeyspopFilterPairs(?string $raw): array
    {
        if ($raw === null || $raw === '') {
            return [];
        }

        $out = [];
        foreach (preg_split('/\s*,\s*/', trim($raw)) as $part) {
            if ($part === '' || ! preg_match('/^(\d+)\s*:\s*(\d+)/', $part, $m)) {
                continue;
            }
            $out[] = [(int) $m[1], (int) $m[2]];
        }

        return $out;
    }

    public static function getWebGoodsBySection($fid, $id, $limit, $offset, ?string $locale = 'ru', $targetTgroupId = null, bool $hitOnly = false, ?int $userId = null, ?string $htmlkeyspop = null)
    {
        $query = self::query()
            ->leftJoin('descript as d', function ($join) {
                $join->on('d.pnum', '=', 'comp.id')
                    ->whereColumn('d.firma', '=', 'comp.firma');
            })
            ->where('comp.web', '1')
            ->where(function ($q) use ($id) {
                $q->where('comp.idcaption', $id)
                    ->orWhere('comp.idglava', $id);
            });
        self::applyMarketplaceGoodsScope($query, $fid);

        if ($hitOnly) {
            $query->where('comp.hit', 1);
        }

        $filterPairs = self::parseHtmlkeyspopFilterPairs($htmlkeyspop);
        $filterGroups = [];
        foreach ($filterPairs as [$groupId, $valueId]) {
            $filterGroups[$groupId][] = $valueId;
        }

        foreach ($filterGroups as $groupId => $valueIds) {
            $valueIds = array_values(array_unique($valueIds));
            $query->where(function ($groupQuery) use ($groupId, $valueIds) {
                foreach ($valueIds as $valueId) {
                    $needle = $groupId . ':' . $valueId;
                    $groupQuery->orWhere('comp.htmlkeyspop', 'LIKE', '%' . $needle . '%');
                }
            });
        }

        $totalCount = $query->count();

        $goods = $query->select(
            'comp.id',
            'comp.nickname',
            DB::raw(self::displayNameSql() . ' as name'),
            DB::raw('COALESCE(d.name, "") as name_ru'),
            DB::raw('COALESCE(d.name_ua, "") as name_ua'),
            DB::raw('COALESCE(d.name_en, "") as name_en'),
            DB::raw('COALESCE(d.description, "") as description'),
            DB::raw('COALESCE(d.description_ua, "") as description_ua'),
            DB::raw('COALESCE(d.description_en, "") as description_en'),
            'comp.nfoto',
            'comp.nfoto1',
            'comp.pay',
            'comp.top',
            'comp.hit',
            'comp.sklad',
            'comp.firma'
        )
            ->orderByDesc('comp.top')
            ->orderByDesc('comp.hit')
            ->orderBy('name')
            ->offset($offset)
            ->limit($limit)
            ->get();

        $goods = self::attachRatings(
            self::attachPreferredPricesByItemFirma($goods, $targetTgroupId),
            $userId
        )
            ->map(function ($item) use ($locale) {
                $nameView = self::localizedValue($locale, $item->name_ru ?? '', $item->name_ua ?? '', $item->name_en ?? '');
                $descriptionView = self::localizedValue($locale, $item->description ?? '', $item->description_ua ?? '', $item->description_en ?? '');

                return [
                    'id' => $item->id,
                    'code' => trim((string) ($item->nickname ?? '')),
                    'nickname' => trim((string) ($item->nickname ?? '')),
                    'name' => $nameView,
                    'name_ua' => $item->name_ua,
                    'name_en' => $item->name_en,
                    'name_ru' => $item->name_ru,
                    'name_view' => $nameView,
                    'description' => $descriptionView,
                    'description_ua' => $item->description_ua,
                    'description_en' => $item->description_en,
                    'description_ru' => $item->description,
                    'description_view' => $descriptionView,
                    'price' => (float) ($item->price_pay ?? 0),
                    'oldPrice' => (float) ($item->price_oldpay ?? 0),
                    'pay' => (float) ($item->price_pay ?? 0),
                    'pay1' => (float) ($item->price_pay1 ?? 0),
                    'count' => (int) ($item->price_count ?? 0),
                    'sklad' => (int) ((int) ($item->sklad ?? 0) === 1),
                    'image' => MediaUrl::image($item->nfoto),
                    'image_thumb' => MediaUrl::image($item->nfoto1),
                    'rating_avg' => (float) ($item->rating_avg ?? 0),
                    'rating_count' => (int) ($item->rating_count ?? 0),
                    'user_rating' => $item->user_rating !== null ? (int) $item->user_rating : null,
                ];
            });

        return [
            'goods' => $goods,
            'total' => $totalCount,
        ];
    }

    public static function getWebGood($fid, $identifier, ?string $locale = 'ru', $targetTgroupId = null, ?int $userId = null)
    {
        $identifier = trim((string) $identifier);

        if ($identifier === '') {
            return null;
        }

        $baseQuery = self::query()
            ->leftJoin('descript as d', function ($join) {
                $join->on('d.pnum', '=', 'comp.id')
                    ->whereColumn('d.firma', '=', 'comp.firma');
            })
            ->where('comp.web', '1')
            ->select(
                'comp.id',
                'comp.nickname',
                'comp.idcaption',
                'comp.idglava',
                'comp.firma',
                'comp.pay',
                'comp.nfoto',
                'comp.nfoto1',
                'comp.nfoto2',
                'comp.nfoto3',
                'comp.nfoto4',
                'comp.nfoto5',
                'comp.nfoto6',
                'comp.nfoto7',
                'comp.nfoto8',
                'comp.nfoto9',
                'comp.sklad',
                'comp.htmlkeys',
                'comp.htmlkeyspop',
                'comp.htmldescr',
                DB::raw(self::displayNameSql() . ' as name'),
                DB::raw('COALESCE(d.name, "") as name_ru'),
                DB::raw('COALESCE(d.name_ua, "") as name_ua'),
                DB::raw('COALESCE(d.name_en, "") as name_en'),
                DB::raw('COALESCE(d.description, "") as description'),
                DB::raw('COALESCE(d.description_ua, "") as description_ua'),
                DB::raw('COALESCE(d.description_en, "") as description_en')
            );
        self::applyMarketplaceGoodsScope($baseQuery, $fid);

        if (ctype_digit($identifier)) {
            $item = (clone $baseQuery)
                ->where('comp.id', (int) $identifier)
                ->first();
        } else {
            $item = (clone $baseQuery)
                ->whereRaw('TRIM(comp.nickname) = ?', [$identifier])
                ->orderByRaw('CASE WHEN comp.firma = ? THEN 0 ELSE 1 END', [$fid])
                ->first();
        }

        if (!$item) {
            return null;
        }

        $item = self::attachRatings(
            self::attachPreferredPricesByItemFirma(collect([$item]), $targetTgroupId),
            $userId
        )->first();

        $nameView = self::localizedValue($locale, $item->name_ru ?? '', $item->name_ua ?? '', $item->name_en ?? '');
        $descriptionView = self::localizedValue($locale, $item->description ?? '', $item->description_ua ?? '', $item->description_en ?? '');

        $images = collect([
            $item->nfoto ?? '',
            $item->nfoto1 ?? '',
            $item->nfoto2 ?? '',
            $item->nfoto3 ?? '',
            $item->nfoto4 ?? '',
            $item->nfoto5 ?? '',
            $item->nfoto6 ?? '',
            $item->nfoto7 ?? '',
            $item->nfoto8 ?? '',
            $item->nfoto9 ?? '',
        ])
            ->map(fn ($image) => trim((string) $image))
            ->filter()
            ->map(fn ($image) => MediaUrl::image($image))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $sectionIds = collect([$item->idglava ?? null, $item->idcaption ?? null])
            ->filter(fn ($value) => !in_array((string) $value, ['', '0'], true))
            ->map(fn ($value) => (int) $value)
            ->unique()
            ->values();

        $sections = $sectionIds->isEmpty()
            ? collect()
            : Field::query()
                ->where('keyfield', 'catalog')
                ->where('firma', $fid)
                ->whereIn('id', $sectionIds)
                ->get()
                ->keyBy('id');

        $topSection = $sections->get((int) ($item->idglava ?? 0));
        $section = $sections->get((int) ($item->idcaption ?? 0)) ?? $topSection;

        $mapSection = function ($fieldItem) use ($locale) {
            if (!$fieldItem) {
                return null;
            }

            return [
                'id' => (int) $fieldItem->id,
                'name' => Field::localizedValue($locale, $fieldItem->val ?? '', $fieldItem->valua ?? '', $fieldItem->valen ?? ''),
                'name_ru' => $fieldItem->val ?? '',
                'name_ua' => $fieldItem->valua ?? '',
                'name_en' => $fieldItem->valen ?? '',
                'link' => trim((string) ($fieldItem->link ?? '')),
            ];
        };

        return [
            'id' => (int) $item->id,
            'code' => trim((string) ($item->nickname ?? '')),
            'nickname' => trim((string) ($item->nickname ?? '')),
            'name' => $nameView,
            'name_ru' => $item->name_ru ?? '',
            'name_ua' => $item->name_ua ?? '',
            'name_en' => $item->name_en ?? '',
            'name_view' => $nameView,
            'description' => $descriptionView,
            'description_ru' => $item->description ?? '',
            'description_ua' => $item->description_ua ?? '',
            'description_en' => $item->description_en ?? '',
            'description_view' => $descriptionView,
            'price' => (float) ($item->price_pay ?? 0),
            'oldPrice' => (float) ($item->price_oldpay ?? 0),
            'pay' => (float) ($item->price_pay ?? 0),
            'pay1' => (float) ($item->price_pay1 ?? 0),
            'wholesalePrice' => (float) ($item->price_pay1 ?? 0),
            'wholesaleOldPrice' => (float) ($item->price_oldpay ?? 0),
            'wholesaleFrom' => (int) ($item->price_count ?? 0),
            'count' => (int) ($item->price_count ?? 0),
            'sklad' => (int) ((int) ($item->sklad ?? 0) === 1),
            'image' => MediaUrl::image($item->nfoto ?? ''),
            'image_thumb' => MediaUrl::image($item->nfoto1 ?? ''),
            'images' => $images,
            'section' => $mapSection($section),
            'parent_section' => $mapSection($topSection),
            'meta_description' => trim((string) ($item->htmldescr ?? '')),
            'meta_keywords' => trim((string) ($item->htmlkeys ?? $item->htmlkeyspop ?? '')),
            'rating_avg' => (float) ($item->rating_avg ?? 0),
            'rating_count' => (int) ($item->rating_count ?? 0),
            'user_rating' => $item->user_rating !== null ? (int) $item->user_rating : null,
        ];
    }

    // ── getHits: paginated hit goods for API ─────────────────────────────────

    public static function getHits($fid, $limit = 10, $offset = 0, ?string $locale = 'ru', $targetTgroupId = null, ?int $userId = null)
    {
        $hits = self::query()
            ->leftJoin('descript as d', function ($join) {
                $join->on('d.pnum', '=', 'comp.id')
                    ->whereColumn('d.firma', '=', 'comp.firma');
            })
            ->where('comp.web', '1')
            ->select(
                'comp.id',
                'comp.nickname',
                DB::raw(self::displayNameSql() . ' as name'),
                DB::raw('COALESCE(d.name, "") as name_ru'),
                DB::raw('COALESCE(d.name_ua, "") as name_ua'),
                DB::raw('COALESCE(d.name_en, "") as name_en'),
                DB::raw('COALESCE(d.description, "") as description'),
                DB::raw('COALESCE(d.description_ua, "") as description_ua'),
                DB::raw('COALESCE(d.description_en, "") as description_en'),
                'comp.nfoto',
                'comp.nfoto1',
                'comp.pay',
                'comp.firma',
                'comp.top',
                'comp.hit',
                'comp.sklad'
            );
        self::applyMarketplaceGoodsScope($hits, $fid);

        $hits = $hits
            ->orderByDesc('comp.top')
            ->orderByDesc('comp.id')
            ->offset($offset)
            ->limit($limit)
            ->get();

        return self::attachRatings(
            self::attachPreferredPricesByItemFirma($hits, $targetTgroupId),
            $userId
        )
            ->map(function ($item) use ($locale) {
                $nameView = self::localizedValue($locale, $item->name_ru ?? '', $item->name_ua ?? '', $item->name_en ?? '');
                $descriptionView = self::localizedValue($locale, $item->description ?? '', $item->description_ua ?? '', $item->description_en ?? '');

                return [
                    'id' => $item->id,
                    'code' => trim((string) ($item->nickname ?? '')),
                    'nickname' => trim((string) ($item->nickname ?? '')),
                    'name' => $nameView,
                    'name_ua' => $item->name_ua,
                    'name_en' => $item->name_en,
                    'name_ru' => $item->name_ru,
                    'name_view' => $nameView,
                    'description' => $descriptionView,
                    'description_ua' => $item->description_ua,
                    'description_en' => $item->description_en,
                    'description_ru' => $item->description,
                    'description_view' => $descriptionView,
                    'price' => (float) ($item->price_pay ?? 0),
                    'oldPrice' => (float) ($item->price_oldpay ?? 0),
                    'pay' => (float) ($item->price_pay ?? 0),
                    'pay1' => (float) ($item->price_pay1 ?? 0),
                    'count' => (int) ($item->price_count ?? 0),
                    'sklad' => (int) ((int) ($item->sklad ?? 0) === 1),
                    'image' => MediaUrl::image($item->nfoto),
                    'image_thumb' => MediaUrl::image($item->nfoto1),
                    'rating_avg' => (float) ($item->rating_avg ?? 0),
                    'rating_count' => (int) ($item->rating_count ?? 0),
                    'user_rating' => $item->user_rating !== null ? (int) $item->user_rating : null,
                ];
            });
    }

    // ── save: insert or update comp + prices + descript ────────────────────────

    public static function saveGoods($pnum, $fid, $compData, $priceRows, $descData, $fotoMap)
    {
        return DB::transaction(function () use ($pnum, $fid, $compData, $priceRows, $descData, $fotoMap) {
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

            // ── Main comp data
            $compData = array_merge($compData, $fotoMap);

            if ($pnum === '' || $pnum === '0') {
                $compData['cod'] = date('dmHis') . rand(10, 99);
                $compData['dt'] = date('d-m-Y');
                $pnum = (string) DB::table('comp')->insertGetId($compData);
            } else {
                DB::table('comp')->where('id', $pnum)->update($compData);
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
        });
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
