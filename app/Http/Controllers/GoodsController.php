<?php

namespace App\Http\Controllers;

use App\Models\Goods;
use App\Models\EducationTopic;
use App\Models\Field;
use App\Models\Filter;
use App\Models\Price;
use App\Models\Project;
use App\Models\Rating;
use App\Support\HoldingScope;
use App\Support\MediaUrl;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * GoodsController
 * Migrated from: comp/index.php, comp/show.php (product edit form),
 *                run-comp.php, delete-comp.php, toggle-sklad.php
 */
class GoodsController extends Controller
{
    private function resolveApiFid(Request $request, $default = '')
    {
        return (string) $request->input('fid', $default !== '' ? $default : session('fid', ''));
    }

    // ── List ──────────────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $fid = session('fid', '');
        $locale = $this->resolveBackendLocale($request);
        $hasFilterRequest = $request->hasAny(['fName', 'filterBrand', 'skladNone', 'showAllGoods', 'hitOnly', 'igla', 'idcapt']);
        $idglava = $hasFilterRequest ? $request->input('igla', '') : session('idglava', '');
        $idcaption = $hasFilterRequest ? $request->input('idcapt', '') : session('idcaption', '');
        $pos = (int) $request->input('pos', session('goods_pos', 0));
        $pos2 = (int) $request->input('pos2', 20);
        $sort = $request->input('sort', session('sort', 'pay'));

        $filters = [
            'fName' => $request->input('fName', session('filter1', '')),
            'filterBrand' => $request->input('filterBrand', session('filter_brand', '')),
            'skladNone' => $hasFilterRequest ? $request->input('skladNone', '') : session('sklad_none', ''),
            'showAllGoods' => $hasFilterRequest ? $request->input('showAllGoods', '') : session('show_all_goods', ''),
            'hitOnly' => $hasFilterRequest ? $request->input('hitOnly', '') : session('hit_only', ''),
        ];

        session([
            'idcaption' => $idcaption,
            'idglava' => $idglava,
            'goods_pos' => $pos,
            'sort' => $sort,
            'filter1' => $filters['fName'],
            'filter_brand' => $filters['filterBrand'],
            'sklad_none' => $filters['skladNone'],
            'show_all_goods' => $filters['showAllGoods'],
            'hit_only' => $filters['hitOnly'],
        ]);

        $result = Goods::init($fid, $idcaption, $idglava, $pos, $pos2, $sort, $filters, $locale);
        $comps = $result['comps'];
        $total = $result['total'];
        $pers = $result['pers'];
        $sections = $result['sections'];
        $tops = $result['tops'];
        $subs = $result['subs'];

        return view('goods.index', compact(
            'comps',
            'total',
            'pos',
            'pos2',
            'fid',
            'idcaption',
            'idglava',
            'pers',
            'sections',
            'tops',
            'subs',
            'filters',
            'sort'
        ));
    }

    /**
     * @return array<int, array{0: int, 1: int}>
     */
    private function parseHtmlkeyspopFilterPairs(?string $raw): array
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

    /**
     * @param array<int, array{0: int, 1: int}> $pairs
     * @return array<int, array<int, int>>
     */
    private function groupHtmlkeyspopFilterPairs(array $pairs): array
    {
        $grouped = [];
        foreach ($pairs as [$groupId, $valueId]) {
            $grouped[$groupId][] = $valueId;
        }

        return array_map(static fn (array $values) => array_values(array_unique($values)), $grouped);
    }

    // ── Search (Web API — for Accessories page) ──────────────────────────────

    public function searchWeb(Request $request)
    {
        $q = trim((string) $request->input('q', ''));
        $htmlkeyspopRaw = (string) $request->input('htmlkeyspop', '');
        $filterPairs = $this->parseHtmlkeyspopFilterPairs($htmlkeyspopRaw);
        $filterGroups = $this->groupHtmlkeyspopFilterPairs($filterPairs);

        $qOk = mb_strlen($q) >= 2;
        $filtersOk = $filterPairs !== [];

        if (! $qOk && ! $filtersOk) {
            return response()->json([]);
        }

        $fid = $this->resolveApiFid($request, '2');
        $locale = $this->resolveApiLocale($request);

        // Get User's tgroup from Sanctum auth
        $user = Auth::guard('sanctum')->user();
        $tgroupId = $user ? ($user->idstatus ?: $user->ustype) : null;

        $goods = DB::table('comp')
            ->leftJoin('descript as d', function ($join) {
                $join->on('d.pnum', '=', 'comp.id')
                    ->whereColumn('d.firma', '=', 'comp.firma');
            })
            ->where(function ($nested) use ($fid) {
                $nested->where('comp.firma', $fid)
                    ->orWhere('comp.constanta', 1);
            })
            ->where('comp.web', '1')
            ->where(function ($outer) use ($qOk, $q, $filterGroups) {
                if ($qOk) {
                    $outer->where(function ($query) use ($q) {
                        $query->where('d.name', 'LIKE', "%{$q}%")
                            ->orWhere('d.name_ua', 'LIKE', "%{$q}%")
                            ->orWhere('d.name_en', 'LIKE', "%{$q}%")
                            ->orWhere('d.description', 'LIKE', "%{$q}%")
                            ->orWhere('d.description_ua', 'LIKE', "%{$q}%")
                            ->orWhere('d.description_en', 'LIKE', "%{$q}%")
                            ->orWhere('comp.nickname', 'LIKE', "%{$q}%")
                            ->orWhere('comp.namedoc', 'LIKE', "%{$q}%")
                            ->orWhere('comp.name', 'LIKE', "%{$q}%")
                            ->orWhere('comp.htmlkeyspop', 'LIKE', "%{$q}%");
                    });
                }
                foreach ($filterGroups as $groupId => $valueIds) {
                    $outer->where(function ($groupQuery) use ($groupId, $valueIds) {
                        foreach ($valueIds as $valueId) {
                            $needle = $groupId . ':' . $valueId;
                            $groupQuery->orWhere('comp.htmlkeyspop', 'LIKE', '%' . $needle . '%');
                        }
                    });
                }
            })
            ->select(
                'comp.id',
                'comp.nickname',
                DB::raw("COALESCE(NULLIF(d.name, ''), NULLIF(d.name_ua, ''), NULLIF(d.name_en, ''), NULLIF(comp.nickname, ''), NULLIF(comp.namedoc, ''), NULLIF(comp.name, ''), CONCAT('Товар #', comp.id)) as name"),
                DB::raw('COALESCE(d.name, "") as name_ru'),
                DB::raw('COALESCE(d.name_ua, "") as name_ua'),
                DB::raw('COALESCE(d.name_en, "") as name_en'),
                'comp.nfoto as image',
                'comp.nfoto1 as image_thumb',
                'comp.pay',
                'comp.pay1',
                'comp.firma',
                'comp.sklad',
                DB::raw('COALESCE(d.description, "") as description'),
                DB::raw('COALESCE(d.description_ua, "") as description_ua'),
                DB::raw('COALESCE(d.description_en, "") as description_en')
            )
            ->limit(30)
            ->get();

        $goods = Goods::attachRatings(
            Goods::attachPreferredPricesByItemFirma($goods, $tgroupId),
            $user?->id
        )
            ->map(function ($g) use ($locale) {
                $nameView = Field::localizedValue($locale, $g->name_ru ?? '', $g->name_ua ?? '', $g->name_en ?? '');
                $desc = Field::localizedValue($locale, $g->description ?? '', $g->description_ua ?? '', $g->description_en ?? '');
                // Strip HTML tags from description for search results
                $desc = strip_tags($desc);

                return [
                    'id' => (int) $g->id,
                    'code' => trim((string) ($g->nickname ?? '')),
                    'nickname' => trim((string) ($g->nickname ?? '')),
                    'name' => $nameView ?: '',
                    'name_ru' => $g->name_ru ?? '',
                    'name_ua' => $g->name_ua ?? '',
                    'name_en' => $g->name_en ?? '', 
                    'name_view' => $nameView ?: '',
                    'price' => (float) ($g->price_pay ?? 0),
                    'pay' => (float) ($g->price_pay ?? 0),
                    'pay1' => (float) ($g->price_pay1 ?? 0),
                    'oldPrice' => (float) ($g->price_oldpay ?? 0),
                    'count' => (int) ($g->price_count ?? 0),
                    'sklad' => (int) ((int) ($g->sklad ?? 0) === 1),
                    'image' => MediaUrl::image($g->image ?? ''),
                    'image_thumb' => MediaUrl::image($g->image_thumb ?? ''),
                    'description' => mb_substr($desc, 0, 200),
                    'description_view' => mb_substr($desc, 0, 200),
                    'rating_avg' => (float) ($g->rating_avg ?? 0),
                    'rating_count' => (int) ($g->rating_count ?? 0),
                    'user_rating' => $g->user_rating !== null ? (int) $g->user_rating : null,
                ];
            });

        return response()->json($goods);
    }

    // ── Search (Async) ────────────────────────────────────────────────────────

    public function search(Request $request)
    {
        $q = $request->input('q');
        if (!$q || mb_strlen($q) < 2)
            return response()->json([]);

        $fid = $this->resolveApiFid($request);
        $locale = $this->resolveApiLocale($request);

        if ($this->isEducationProject($fid)) {
            return response()->json($this->searchEducationCourses((string) $q, $fid, $locale));
        }

        $doc = strtoupper((string) $request->input('doc', ''));
        $counterpartyUserId = (int) $request->input('counterparty_user_id', 0);
        $targetProjectId = $counterpartyUserId > 0
            ? $this->counterpartyProjectId($counterpartyUserId, $fid)
            : null;
        
        $user = Auth::user();
        $tgroupId = $user ? ($user->idstatus ?: $user->ustype) : null;

        $goods = Goods::query()
            ->leftJoin('descript as d', function ($join) use ($fid) {
                $join->on('d.pnum', '=', 'comp.id')
                    ->where('d.firma', '=', $fid);
            })
            ->where('comp.firma', $fid)
            ->where(function ($query) use ($q) {
                $query->where('comp.id', 'LIKE', "%{$q}%")
                    ->orWhere('d.name', 'LIKE', "%{$q}%")
                    ->orWhere('d.name_ua', 'LIKE', "%{$q}%")
                    ->orWhere('d.name_en', 'LIKE', "%{$q}%")
                    ->orWhere('d.description', 'LIKE', "%{$q}%")
                    ->orWhere('d.description_ua', 'LIKE', "%{$q}%")
                    ->orWhere('d.description_en', 'LIKE', "%{$q}%")
                    ->orWhere('comp.htmlkeyspop', 'LIKE', "%{$q}%");
            })
            ->select(
                'comp.id',
                DB::raw("COALESCE(NULLIF(d.name, ''), NULLIF(d.name_ua, ''), NULLIF(d.name_en, ''), NULLIF(comp.nickname, ''), NULLIF(comp.namedoc, ''), NULLIF(comp.name, ''), CONCAT('Товар #', comp.id)) as name"),
                DB::raw('COALESCE(d.name, "") as name_ru'),
                'comp.pay',
                'comp.pay1',
                'comp.firma',
                'comp.nfoto',
                'comp.nfoto1',
                DB::raw('COALESCE(d.name_ua, "") as name_ua'),
                DB::raw('COALESCE(d.name_en, "") as name_en')
            )
            ->limit(20)
            ->get();

        $goods = Goods::attachRatings(
            Goods::attachPreferredPricesByItemFirma($goods, $tgroupId),
            $user?->id
        );
        $mappedProducts = $this->productMappingsForCounterparty(
            $fid,
            $counterpartyUserId,
            $targetProjectId,
            $goods->pluck('id')->map(fn ($id) => (string) $id)->all()
        );

        $goods = $goods
            ->map(function ($g) use ($doc, $locale, $mappedProducts) {
                if (in_array($doc, ['ZIN', 'PN'], true)) {
                    $price = (float) ($g->pay1 ?? 0);
                } elseif (in_array($doc, ['ZOUT', 'RN'], true)) {
                    $price = (float) ($g->price_pay ?? $g->pay ?? 0);
                } else {
                    $price = (float) ($g->price_pay ?? $g->pay1 ?? 0);
                }

                return [
                    'id' => $g->id,
                    'pnum' => $g->id,
                    'name' => Field::localizedValue($locale, $g->name_ru ?? '', $g->name_ua ?? '', $g->name_en ?? ''),
                    'name_ru' => $g->name_ru ?? '',
                    'name_ua' => $g->name_ua ?? '',
                    'name_en' => $g->name_en ?? '',
                    'image' => MediaUrl::image($g->nfoto ?? ''),
                    'image_thumb' => MediaUrl::image($g->nfoto1 ?? '') ?: MediaUrl::image($g->nfoto ?? ''),
                    'price' => (float) $price,
                    'pay' => (float) ($g->price_pay ?? 0),
                    'pay1' => (float) ($g->price_pay1 ?? 0),
                    'priceCompPay' => (float) ($g->pay ?? 0),
                    'priceCompPay1' => (float) ($g->pay1 ?? 0),
                    'priceBase' => (float) ($g->price_pay ?? 0),
                    'priceWholesale' => (float) ($g->price_pay1 ?? 0),
                    'wholesaleFrom' => (int) ($g->price_count ?? 0),
                    'count' => (int) ($g->price_count ?? 0),
                    'sklad' => (float) ($g->sklad ?? 0),
                    'mappedProductId' => $mappedProducts[(string) $g->id] ?? '',
                    'rating_avg' => (float) ($g->rating_avg ?? 0),
                    'rating_count' => (int) ($g->rating_count ?? 0),
                    'user_rating' => $g->user_rating !== null ? (int) $g->user_rating : null,
                ];
            });

        return response()->json($goods);
    }

    private function isEducationProject(string $fid): bool
    {
        if ($fid === '' || !Schema::hasTable('project') || !Schema::hasColumn('project', 'project_type')) {
            return false;
        }

        return strtolower(trim((string) Project::query()->whereKey((int) $fid)->value('project_type'))) === 'education';
    }

    private function searchEducationCourses(string $search, string $fid, string $locale)
    {
        if (!Schema::hasTable('education_topics')) {
            return collect();
        }

        $search = trim($search);
        $hasTranslations = Schema::hasColumn('education_topics', 'title_translations');
        $hasCost = Schema::hasColumn('education_topics', 'cost_av8');

        return EducationTopic::query()
            ->where('project_id', (int) $fid)
            ->where('is_active', true)
            ->where(function ($query) use ($search, $hasTranslations) {
                $query->where('id', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%");
                if ($hasTranslations) {
                    $query->orWhere('title_translations', 'like', "%{$search}%");
                }
            })
            ->orderBy('position')
            ->orderBy('id')
            ->limit(20)
            ->get()
            ->map(function (EducationTopic $course) use ($locale, $hasCost) {
                $translations = is_array($course->title_translations ?? null) ? $course->title_translations : [];
                $name = trim((string) ($translations[$locale] ?? ''));
                if ($name === '') {
                    $name = trim((string) $course->title);
                }
                $price = $hasCost ? (float) ($course->cost_av8 ?? 0) : 0.0;

                return [
                    'id' => (int) $course->id,
                    'pnum' => (int) $course->id,
                    'name' => $name,
                    'image' => '',
                    'image_thumb' => '',
                    'price' => $price,
                    'pay' => $price,
                    'pay1' => 0,
                    'priceCompPay' => $price,
                    'priceCompPay1' => 0,
                    'priceBase' => $price,
                    'priceWholesale' => 0,
                    'wholesaleFrom' => 0,
                    'count' => 0,
                    'sklad' => 1,
                    'mappedProductId' => '',
                    'itemType' => 'course',
                ];
            });
    }

    private function counterpartyProjectId(int $counterpartyUserId, string $sourceCompanyId): ?int
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'project_id')) {
            return null;
        }

        $projectId = DB::table('users')
            ->where('id', $counterpartyUserId)
            ->whereIn('firma', HoldingScope::projectIdsFor($sourceCompanyId))
            ->orderByRaw('CASE WHEN firma = ? THEN 0 ELSE 1 END', [$sourceCompanyId])
            ->value('project_id');

        if ($projectId === null || (string) $projectId === (string) $sourceCompanyId) {
            return null;
        }

        return DB::table('project')->where('id', $projectId)->exists()
            ? (int) $projectId
            : null;
    }

    private function productMappingsForCounterparty(
        string $sourceCompanyId,
        int $counterpartyUserId,
        ?int $targetProjectId,
        array $sourceProductIds
    ): array {
        if (
            $targetProjectId === null
            || $sourceProductIds === []
            || ! Schema::hasTable('product_project_mappings')
        ) {
            return [];
        }

        return DB::table('product_project_mappings')
            ->where('source_company_id', (int) $sourceCompanyId)
            ->where('target_company_id', $targetProjectId)
            ->whereIn('source_product_id', array_values(array_unique($sourceProductIds)))
            ->whereIn('counterparty_user_id', [$counterpartyUserId, 0])
            ->orderByRaw('CASE WHEN counterparty_user_id = ? THEN 0 ELSE 1 END', [$counterpartyUserId])
            ->get(['source_product_id', 'target_product_id'])
            ->groupBy(fn ($row) => (string) $row->source_product_id)
            ->map(fn ($rows) => (string) $rows->first()->target_product_id)
            ->all();
    }

    // ── Get Hit Goods (API) ───────────────────────────────────────────────────

    public function getHits(Request $request)
    {
        $limit  = (int) $request->input('limit', 10);
        $offset = (int) $request->input('offset', 0);

        $fid = $this->resolveApiFid($request, '2');
        $locale = $this->resolveApiLocale($request);

        $user = Auth::guard('sanctum')->user();
        $tgroupId = $user ? ($user->idstatus ?: $user->ustype) : null;
        $hits = Goods::getHits($fid, $limit, $offset, $locale, $tgroupId, $user?->id);

        return response()->json([
            'success' => true,
            'data'    => $hits,
            'limit'   => $limit,
            'offset'  => $offset,
            'locale'  => $locale,
        ]);
    }

    // ── Get Sections (API) ────────────────────────────────────────────────────

    public function getSections(Request $request)
    {
        $fid = $this->resolveApiFid($request, '2');
        $locale = $this->resolveApiLocale($request);
        $tree = Field::getCatalogTree($fid, $locale);
        return response()->json([
            'success' => true,
            'data' => $tree,
            'locale' => $locale,
        ]);
    }

    public function getRegions(Request $request)
    {
        $fid = $this->resolveApiFid($request, '2');
        $locale = $this->resolveApiLocale($request);
        $regions = Field::getRegionsList($fid, $locale);
        $regionIds = $regions->pluck('id')->map(fn ($id) => (int) $id)->all();
        $citiesByRegion = collect();

        if ($regionIds !== [] && Schema::hasTable('filter')) {
            $citiesByRegion = Filter::query()
                ->where('keyfield', 'city')
                ->whereIn('idkeyfield', $regionIds)
                ->orderBy('num')
                ->orderBy('val')
                ->orderBy('id')
                ->get(['id', 'idkeyfield', 'val', 'valru', 'valen', 'num'])
                ->map(function ($city) use ($locale) {
                    $nameUa = trim((string) ($city->val ?? ''));
                    $nameRu = trim((string) ($city->valru ?? ''));
                    $nameEn = trim((string) ($city->valen ?? ''));
                    $slugSource = $nameRu !== '' ? $nameRu : ($nameEn !== '' ? $nameEn : $nameUa);

                    return [
                        'id' => (int) $city->id,
                        'region_id' => (int) $city->idkeyfield,
                        'slug' => Str::slug($slugSource) ?: 'city-' . $city->id,
                        'name' => Field::localizedValue($locale, $nameRu, $nameUa, $nameEn),
                        'val' => $nameUa,
                        'valru' => $nameRu,
                        'valen' => $nameEn,
                        'num' => (int) ($city->num ?? 0),
                    ];
                })
                ->groupBy('region_id');
        }

        $regions = $regions->map(function (array $region) use ($citiesByRegion) {
            $region['cities'] = $citiesByRegion->get($region['id'], collect())->values();

            return $region;
        })->values();

        return response()->json([
            'success' => true,
            'data' => $regions,
            'locale' => $locale,
        ]);
    }

    public function getCities(Request $request)
    {
        $fid = $this->resolveApiFid($request, '2');
        $locale = $this->resolveApiLocale($request);
        $regionIds = Field::getRegionsList($fid, $locale)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($regionIds === [] || !Schema::hasTable('filter')) {
            return response()->json([
                'success' => true,
                'data' => [],
                'locale' => $locale,
            ]);
        }

        $cities = Filter::query()
            ->where('keyfield', 'city')
            ->whereIn('idkeyfield', $regionIds)
            ->orderBy('num')
            ->orderBy('val')
            ->orderBy('id')
            ->get(['id', 'idkeyfield', 'val', 'valru', 'valen', 'num'])
            ->map(function ($city) use ($locale) {
                $nameUa = trim((string) ($city->val ?? ''));
                $nameRu = trim((string) ($city->valru ?? ''));
                $nameEn = trim((string) ($city->valen ?? ''));
                $slugSource = $nameRu !== '' ? $nameRu : ($nameEn !== '' ? $nameEn : $nameUa);

                return [
                    'id' => (int) $city->id,
                    'region_id' => (int) $city->idkeyfield,
                    'slug' => Str::slug($slugSource) ?: 'city-' . $city->id,
                    'name' => Field::localizedValue($locale, $nameRu, $nameUa, $nameEn),
                    'val' => $nameUa,
                    'valru' => $nameRu,
                    'valen' => $nameEn,
                    'num' => (int) ($city->num ?? 0),
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'data' => $cities,
            'locale' => $locale,
        ]);
    }

    // ── Get Goods By Section (API) ────────────────────────────────────────────

    public function getBySection(Request $request, $id)
    {
        $limit = max(1, min((int) $request->input('limit', 200), 200));
        $offset = (int) $request->input('offset', 0);
        $hitOnly = $request->boolean('hit');
        $htmlkeyspopRaw = trim((string) $request->input('htmlkeyspop', ''));

        $fid = $this->resolveApiFid($request, '2');
        $locale = $this->resolveApiLocale($request);

        $user = Auth::guard('sanctum')->user();
        $tgroupId = $user ? ($user->idstatus ?: $user->ustype) : null;
        $result = Goods::getWebGoodsBySection(
            $fid,
            $id,
            $limit,
            $offset,
            $locale,
            $tgroupId,
            $hitOnly,
            $user?->id,
            $htmlkeyspopRaw !== '' ? $htmlkeyspopRaw : null
        );

        return response()->json([
            'success' => true,
            'data' => $result['goods'],
            'total' => $result['total'],
            'limit' => $limit,
            'offset' => $offset,
            'hit' => $hitOnly,
            'locale' => $locale,
        ]);
    }

    public function getOne(Request $request, $id)
    {
        $fid = $this->resolveApiFid($request, '2');
        $locale = $this->resolveApiLocale($request);

        $user = Auth::guard('sanctum')->user();
        $tgroupId = $user ? ($user->idstatus ?: $user->ustype) : null;
        $item = Goods::getWebGood($fid, $id, $locale, $tgroupId, $user?->id);

        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => 'Товар не найден',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'item' => $item,
            'locale' => $locale,
        ]);
    }

    public function managerAiSearch(Request $request)
    {
        if ($denied = $this->denyInvalidManagerAiSecret($request)) {
            return $denied;
        }

        $payload = $request->validate([
            'fid' => ['required', 'integer', 'min:1'],
            'q' => ['nullable', 'string', 'max:200'],
            'query' => ['nullable', 'string', 'max:200'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:30'],
            'locale' => ['nullable', 'string', 'in:ru,ua,en'],
        ]);

        $fid = (int) $payload['fid'];
        $query = trim((string) ($payload['q'] ?? $payload['query'] ?? ''));
        $limit = min((int) ($payload['limit'] ?? 10), 30);
        $locale = (string) ($payload['locale'] ?? $this->resolveApiLocale($request));

        if (mb_strlen($query) < 2) {
            return response()->json([
                'success' => false,
                'message' => 'Parameter "q" must contain at least 2 characters.',
                'data' => [],
            ], 422);
        }

        $items = $this->managerAiGoodsBaseQuery($fid)
            ->where(function ($search) use ($query) {
                $search->where('comp.id', 'LIKE', "%{$query}%")
                    ->orWhere('comp.nickname', 'LIKE', "%{$query}%")
                    ->orWhere('comp.namedoc', 'LIKE', "%{$query}%")
                    ->orWhere('comp.name', 'LIKE', "%{$query}%")
                    ->orWhere('comp.htmlkeys', 'LIKE', "%{$query}%")
                    ->orWhere('comp.htmlkeyspop', 'LIKE', "%{$query}%")
                    ->orWhere('d.name', 'LIKE', "%{$query}%")
                    ->orWhere('d.name_ua', 'LIKE', "%{$query}%")
                    ->orWhere('d.name_en', 'LIKE', "%{$query}%")
                    ->orWhere('d.description', 'LIKE', "%{$query}%")
                    ->orWhere('d.description_ua', 'LIKE', "%{$query}%")
                    ->orWhere('d.description_en', 'LIKE', "%{$query}%");
            })
            ->orderByDesc('comp.top')
            ->orderBy('name')
            ->limit($limit)
            ->get()
            ->map(fn ($item) => $this->serializeManagerAiGood($item, $locale, $request))
            ->values();

        return response()->json([
            'success' => true,
            'fid' => $fid,
            'query' => $query,
            'count' => $items->count(),
            'data' => $items,
        ]);
    }

    public function managerAiShow(Request $request, $identifier)
    {
        if ($denied = $this->denyInvalidManagerAiSecret($request)) {
            return $denied;
        }

        $payload = $request->validate([
            'fid' => ['required', 'integer', 'min:1'],
            'locale' => ['nullable', 'string', 'in:ru,ua,en'],
        ]);

        $fid = (int) $payload['fid'];
        $locale = (string) ($payload['locale'] ?? $this->resolveApiLocale($request));
        $identifier = trim((string) $identifier);

        $query = $this->managerAiGoodsBaseQuery($fid);
        if (ctype_digit($identifier)) {
            $query->where('comp.id', (int) $identifier);
        } else {
            $query->where('comp.nickname', $identifier);
        }

        $item = $query->first();
        if (! $item) {
            return response()->json([
                'success' => false,
                'message' => 'Товар не найден',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'fid' => $fid,
            'data' => $this->serializeManagerAiGood($item, $locale, $request),
        ]);
    }

    public function managerAiItemsIndex(Request $request)
    {
        if ($denied = $this->denyInvalidManagerAiSecret($request)) {
            return $denied;
        }

        $payload = $request->validate([
            'fid' => ['required', 'integer', 'min:1'],
            'email' => ['nullable', 'email', 'max:255'],
            'external_id' => ['nullable', 'string', 'max:255'],
            'source_url' => ['nullable', 'string', 'max:2000'],
            'q' => ['nullable', 'string', 'max:200'],
            'idglava' => ['nullable', 'integer', 'min:1'],
            'idcaption' => ['nullable', 'integer', 'min:1'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'offset' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'locale' => ['nullable', 'string', 'in:ru,ua,en'],
        ]);

        $project = $this->managerAiResolveProject((int) $payload['fid'], $payload['email'] ?? null);
        if ($project instanceof \Illuminate\Http\JsonResponse) {
            return $project;
        }

        $fid = (int) $payload['fid'];
        $locale = (string) ($payload['locale'] ?? $this->resolveApiLocale($request));
        $limit = min((int) ($payload['limit'] ?? 30), 100);
        $offset = (int) ($payload['offset'] ?? 0);

        $query = $this->managerAiGoodsBaseQuery($fid);

        $externalId = trim((string) ($payload['external_id'] ?? ''));
        if ($externalId !== '' && $this->managerAiCompHasColumn('manager_ai_external_id')) {
            $query->where('comp.manager_ai_external_id', $externalId);
        }

        $sourceHash = $this->managerAiSourceHash($payload['source_url'] ?? null);
        if ($sourceHash !== null && $this->managerAiCompHasColumn('manager_ai_source_hash')) {
            $query->where('comp.manager_ai_source_hash', $sourceHash);
        }

        $idglava = (int) ($payload['idglava'] ?? 0);
        $idcaption = (int) ($payload['idcaption'] ?? 0);
        if ($idglava > 0) {
            $query->where('comp.idglava', $idglava);
        }
        if ($idcaption > 0) {
            $query->where('comp.idcaption', $idcaption);
        }

        $search = trim((string) ($payload['q'] ?? ''));
        if ($search !== '') {
            $query->where(function ($nested) use ($search) {
                $nested->where('comp.id', 'LIKE', "%{$search}%")
                    ->orWhere('comp.nickname', 'LIKE', "%{$search}%")
                    ->orWhere('d.name', 'LIKE', "%{$search}%")
                    ->orWhere('d.name_ua', 'LIKE', "%{$search}%")
                    ->orWhere('d.name_en', 'LIKE', "%{$search}%");

                if ($this->managerAiCompHasColumn('manager_ai_external_id')) {
                    $nested->orWhere('comp.manager_ai_external_id', 'LIKE', "%{$search}%");
                }
                if ($this->managerAiCompHasColumn('manager_ai_source_url')) {
                    $nested->orWhere('comp.manager_ai_source_url', 'LIKE', "%{$search}%");
                }
            });
        }

        $total = (clone $query)->count();
        $items = $query
            ->orderByDesc('comp.id')
            ->offset($offset)
            ->limit($limit)
            ->get()
            ->map(fn ($item) => $this->serializeManagerAiGood($item, $locale, $request))
            ->values();

        return response()->json([
            'success' => true,
            'fid' => $fid,
            'total' => $total,
            'limit' => $limit,
            'offset' => $offset,
            'filters' => [
                'idglava' => $idglava ?: null,
                'idcaption' => $idcaption ?: null,
            ],
            'data' => $items,
        ]);
    }

    public function managerAiItemsByCategory(Request $request)
    {
        if ($denied = $this->denyInvalidManagerAiSecret($request)) {
            return $denied;
        }

        $request->validate([
            'fid' => ['required', 'integer', 'min:1'],
            'idglava' => ['nullable', 'integer', 'min:1', 'required_without:idcaption'],
            'idcaption' => ['nullable', 'integer', 'min:1', 'required_without:idglava'],
        ]);

        return $this->managerAiItemsIndex($request);
    }

    public function managerAiItemsByPnum(Request $request)
    {
        if ($denied = $this->denyInvalidManagerAiSecret($request)) {
            return $denied;
        }

        $payload = $request->validate([
            'fid' => ['required', 'integer', 'min:1'],
            'pnum' => ['required', 'integer', 'min:1'],
            'email' => ['nullable', 'email', 'max:255'],
            'locale' => ['nullable', 'string', 'in:ru,ua,en'],
        ]);

        $project = $this->managerAiResolveProject((int) $payload['fid'], $payload['email'] ?? null);
        if ($project instanceof \Illuminate\Http\JsonResponse) {
            return $project;
        }

        $fid = (int) $payload['fid'];
        $pnum = (int) $payload['pnum'];
        $locale = (string) ($payload['locale'] ?? $this->resolveApiLocale($request));
        $item = $this->managerAiGoodsBaseQuery($fid)
            ->where('comp.id', $pnum)
            ->first();

        if (! $item) {
            return response()->json([
                'success' => false,
                'message' => 'Товар не найден',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'fid' => $fid,
            'pnum' => $pnum,
            'data' => $this->serializeManagerAiGood($item, $locale, $request),
        ]);
    }

    public function managerAiItemsStore(Request $request)
    {
        if ($denied = $this->denyInvalidManagerAiSecret($request)) {
            return $denied;
        }

        $payload = $this->validateManagerAiItemPayload($request, true);
        $project = $this->managerAiResolveProject((int) $payload['fid'], $payload['email'] ?? null);
        if ($project instanceof \Illuminate\Http\JsonResponse) {
            return $project;
        }

        $result = DB::transaction(fn () => $this->managerAiPersistItem(null, $payload));

        return response()->json([
            'success' => true,
            'created' => true,
            'price_changed' => false,
            'id' => (int) $result['id'],
            'item' => $this->managerAiLoadSerializedGood((int) $payload['fid'], (int) $result['id'], $request),
        ], 201);
    }

    public function managerAiItemsUpsert(Request $request)
    {
        if ($denied = $this->denyInvalidManagerAiSecret($request)) {
            return $denied;
        }

        $payload = $this->validateManagerAiItemPayload($request, false, true);
        $project = $this->managerAiResolveProject((int) $payload['fid'], $payload['email'] ?? null);
        if ($project instanceof \Illuminate\Http\JsonResponse) {
            return $project;
        }

        $existing = $this->managerAiFindExistingItem((int) $payload['fid'], $payload);
        if (! $existing && trim((string) ($payload['name'] ?? $payload['name_ru'] ?? '')) === '') {
            throw ValidationException::withMessages(['name' => 'Передайте название товара при создании новой карточки.']);
        }
        $oldPrice = $existing ? (float) ($existing->pay ?? 0) : null;

        $result = DB::transaction(fn () => $this->managerAiPersistItem($existing ? (int) $existing->id : null, $payload));
        $newPrice = (float) ($payload['price'] ?? $payload['pay'] ?? 0);

        return response()->json([
            'success' => true,
            'created' => ! $existing,
            'price_changed' => $oldPrice !== null && abs($oldPrice - $newPrice) > 0.00001,
            'old_price' => $oldPrice,
            'new_price' => $newPrice,
            'id' => (int) $result['id'],
            'item' => $this->managerAiLoadSerializedGood((int) $payload['fid'], (int) $result['id'], $request),
        ], $existing ? 200 : 201);
    }

    public function managerAiItemsUpdate(Request $request, $id)
    {
        if ($denied = $this->denyInvalidManagerAiSecret($request)) {
            return $denied;
        }

        $payload = $this->validateManagerAiItemPayload($request, false, false);
        $project = $this->managerAiResolveProject((int) $payload['fid'], $payload['email'] ?? null);
        if ($project instanceof \Illuminate\Http\JsonResponse) {
            return $project;
        }

        $item = DB::table('comp')->where('id', (int) $id)->where('firma', (string) $payload['fid'])->first();
        if (! $item) {
            return response()->json(['success' => false, 'message' => 'Товар не найден'], 404);
        }

        $oldPrice = (float) ($item->pay ?? 0);
        DB::transaction(fn () => $this->managerAiPersistItem((int) $id, $payload));
        $newPrice = (float) ($payload['price'] ?? $payload['pay'] ?? $oldPrice);

        return response()->json([
            'success' => true,
            'price_changed' => abs($oldPrice - $newPrice) > 0.00001,
            'old_price' => $oldPrice,
            'new_price' => $newPrice,
            'id' => (int) $id,
            'item' => $this->managerAiLoadSerializedGood((int) $payload['fid'], (int) $id, $request),
        ]);
    }

    public function managerAiItemsDestroy(Request $request, $id)
    {
        if ($denied = $this->denyInvalidManagerAiSecret($request)) {
            return $denied;
        }

        $payload = $request->validate([
            'fid' => ['required', 'integer', 'min:1'],
            'email' => ['nullable', 'email', 'max:255'],
        ]);

        $project = $this->managerAiResolveProject((int) $payload['fid'], $payload['email'] ?? null);
        if ($project instanceof \Illuminate\Http\JsonResponse) {
            return $project;
        }

        $item = DB::table('comp')->where('id', (int) $id)->where('firma', (string) $payload['fid'])->first();
        if (! $item) {
            return response()->json(['success' => false, 'message' => 'Товар не найден'], 404);
        }

        if (Schema::hasTable('z_body') && DB::table('z_body')->where('pnum', (int) $id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Товар используется в документах и не может быть удален.',
            ], 409);
        }

        DB::transaction(function () use ($id, $payload) {
            DB::table('price')->where('pnum', (string) $id)->where('firma', (string) $payload['fid'])->delete();
            DB::table('descript')->where('pnum', (int) $id)->where('firma', (int) $payload['fid'])->delete();
            DB::table('comp')->where('id', (int) $id)->where('firma', (string) $payload['fid'])->delete();
        });

        return response()->json(['success' => true, 'id' => (int) $id]);
    }

    public function apiStoreBySecret(Request $request): JsonResponse
    {
        $this->requireGoodsPublishToken($request);
        $validated = $this->validateAgentPayload($request);
        $fid = trim((string) ($validated['fid'] ?? ''));

        if ($fid === '') {
            throw ValidationException::withMessages([
                'fid' => 'Parameter "fid" is required.',
            ]);
        }

        $code = trim((string) ($validated['code'] ?? ''));
        if ($code !== '') {
            $existing = DB::table('comp')
                ->where('nickname', $code)
                ->where('firma', (int) $fid)
                ->exists();
            if ($existing) {
                return response()->json([
                    'message' => 'Товар з таким кодом вже існує',
                    'code' => $code,
                ], 409);
            }
        }

        $idcaption = (int) ($validated['category'] ?? 0);
        $compData = [
            'idcaption' => $idcaption,
            'idglava' => 0,
            'idtype' => 1,
            'firma' => (int) $fid,
            'nickname' => $code,
            'namedoc' => '',
            'pay' => (float) ($validated['price'] ?? 0),
            'pay1' => (float) ($validated['price_wholesale'] ?? 0),
            'sklad' => (int) ($validated['in_stock'] ?? true),
            'htmldescr' => '',
            'htmlkeys' => (string) ($validated['htmlkeys'] ?? ''),
            'htmlkeyspop' => '',
            'nvideo1' => '',
            'nvideo2' => '',
            'top' => 0,
            'hit' => 0,
            'constanta' => 0,
            'profitpay' => 0,
            'garant' => '',
        ];

        $descData = [
            'name' => (string) ($validated['name'] ?? ''),
            'name_ua' => (string) ($validated['name_ua'] ?? ''),
            'name_en' => (string) ($validated['name_en'] ?? ''),
            'description' => (string) ($validated['description'] ?? ''),
            'description_ua' => (string) ($validated['description_ua'] ?? ''),
            'description_en' => (string) ($validated['description_en'] ?? ''),
            'web' => 1,
            'descript' => 0,
            'descript2' => 0,
            'descript3' => 0,
            'descript4' => 0,
            'descript5' => 0,
        ];

        $pnum = Goods::saveGoods('0', $fid, $compData, [], $descData, []);

        $locale = $this->resolveApiLocale($request);
        $item = Goods::getWebGood($fid, $pnum, $locale, null, null);

        return response()->json([
            'message' => 'Товар створено',
            'id' => (int) $pnum,
            'code' => $code,
            'item' => $item,
        ], 201);
    }

    private function requireGoodsPublishToken(Request $request): void
    {
        $expectedToken = trim((string) config('services.goods_publish.token', ''));
        if ($expectedToken === '') {
            abort(503, 'Goods publish token is not configured.');
        }

        $providedToken = trim((string) $request->header('X-Goods-Publish-Token', ''));
        if ($providedToken === '' || ! hash_equals($expectedToken, $providedToken)) {
            abort(403, 'Invalid goods publish token.');
        }
    }

    private function validateAgentPayload(Request $request): array
    {
        return $request->validate([
            'fid' => ['required', 'integer', 'min:1'],
            'code' => ['nullable', 'string', 'max:100'],
            'name' => ['nullable', 'string', 'max:1000', 'required_without_all:name_ua,name_en'],
            'name_ua' => ['nullable', 'string', 'max:1000'],
            'name_en' => ['nullable', 'string', 'max:1000'],
            'description' => ['nullable', 'string'],
            'description_ua' => ['nullable', 'string'],
            'description_en' => ['nullable', 'string'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'price_wholesale' => ['nullable', 'numeric', 'min:0'],
            'category' => ['nullable', 'integer', 'min:0'],
            'in_stock' => ['nullable', 'boolean'],
            'tags' => ['nullable', 'string', 'max:10000'],
            'htmlkeys' => ['nullable', 'string', 'max:10000'],
        ]);
    }

    private function denyInvalidManagerAiSecret(Request $request)
    {
        $expectedSecret = trim((string) config('services.manager_ai.bridge_secret', ''));
        $providedSecret = trim((string) (
            $request->header('X-ManagerAI-Bridge-Secret')
            ?: $request->header('X-Manager-AI-Bridge-Secret')
            ?: ''
        ));

        if ($expectedSecret === '' || $providedSecret === '' || ! hash_equals($expectedSecret, $providedSecret)) {
            if ($this->hasValidManagerAiGoodsToken($request, $expectedSecret)) {
                return null;
            }

            return response()->json(['message' => 'Invalid ManagerAI bridge secret.'], 403);
        }

        return null;
    }

    private function hasValidManagerAiGoodsToken(Request $request, string $secret): bool
    {
        if ($secret === '') {
            return false;
        }

        $fid = (int) $request->query('fid', 0);
        $expires = (int) $request->query('manager_ai_expires', 0);
        $providedToken = trim((string) $request->query('manager_ai_token', ''));

        if ($fid <= 0 || $expires <= 0 || $providedToken === '' || $expires < now()->timestamp) {
            return false;
        }

        $payload = implode('|', ['manager-ai-goods', $fid, $expires]);
        $expectedToken = hash_hmac('sha256', $payload, $secret);

        return hash_equals($expectedToken, $providedToken);
    }

    private function managerAiGoodsBaseQuery(int $fid)
    {
        $selects = [
            'comp.id',
            'comp.nickname',
            'comp.firma',
            'comp.pay',
            'comp.pay1',
            'comp.sklad',
            'comp.count',
            'comp.idcaption',
            'comp.idglava',
            'comp.nfoto as image',
            'comp.nfoto1 as image_thumb',
            DB::raw("COALESCE(NULLIF(d.name, ''), NULLIF(d.name_ua, ''), NULLIF(d.name_en, ''), NULLIF(comp.nickname, ''), NULLIF(comp.namedoc, ''), NULLIF(comp.name, ''), CONCAT('Товар #', comp.id)) as name"),
            DB::raw('COALESCE(d.name, "") as name_ru'),
            DB::raw('COALESCE(d.name_ua, "") as name_ua'),
            DB::raw('COALESCE(d.name_en, "") as name_en'),
            DB::raw('COALESCE(d.description, "") as description'),
            DB::raw('COALESCE(d.description_ua, "") as description_ua'),
            DB::raw('COALESCE(d.description_en, "") as description_en'),
        ];

        foreach ([
            'manager_ai_external_id',
            'manager_ai_source_url',
            'manager_ai_source_hash',
            'manager_ai_last_seen_at',
        ] as $column) {
            $selects[] = $this->managerAiCompHasColumn($column)
                ? "comp.{$column}"
                : DB::raw("NULL as {$column}");
        }

        return DB::table('comp')
            ->leftJoin('descript as d', function ($join) {
                $join->on('d.pnum', '=', 'comp.id')
                    ->whereColumn('d.firma', '=', 'comp.firma');
            })
            ->where('comp.firma', $fid)
            ->select($selects);
    }

    private function serializeManagerAiGood(object $item, string $locale, Request $request): array
    {
        $name = Field::localizedValue($locale, $item->name_ru ?? '', $item->name_ua ?? '', $item->name_en ?? '')
            ?: (string) ($item->name ?? '');
        $description = Field::localizedValue($locale, $item->description ?? '', $item->description_ua ?? '', $item->description_en ?? '');
        $description = (string) $description;
        $identifier = trim((string) ($item->nickname ?? '')) !== '' ? trim((string) $item->nickname) : (string) $item->id;
        $path = '/goods/' . rawurlencode($identifier);
        $frontendBaseUrl = rtrim((string) $request->query('frontend_base_url', ''), '/');

        return [
            'id' => (int) $item->id,
            'fid' => (int) $item->firma,
            'code' => trim((string) ($item->nickname ?? '')),
            'name' => $name,
            'name_ru' => (string) ($item->name_ru ?? ''),
            'name_ua' => (string) ($item->name_ua ?? ''),
            'name_en' => (string) ($item->name_en ?? ''),
            'description' => $description,
            'description_ru' => trim(preg_replace('/\s+/u', ' ', strip_tags((string) ($item->description ?? ''))) ?? ''),
            'description_ua' => (string) ($item->description_ua ?? ''),
            'description_en' => (string) ($item->description_en ?? ''),
            'price' => (float) ($item->pay ?? 0),
            'wholesale_price' => (float) ($item->pay1 ?? 0),
            'count' => (float) ($item->count ?? 0),
            'in_stock' => (int) ($item->sklad ?? 0) === 1,
            'idcaption' => (string) ($item->idcaption ?? ''),
            'idglava' => (string) ($item->idglava ?? ''),
            'external_id' => (string) ($item->manager_ai_external_id ?? ''),
            'source_url' => (string) ($item->manager_ai_source_url ?? ''),
            'source_hash' => (string) ($item->manager_ai_source_hash ?? ''),
            'last_seen_at' => $item->manager_ai_last_seen_at ?? null,
            'image' => MediaUrl::image($item->image ?? ''),
            'image_thumb' => MediaUrl::image($item->image_thumb ?? ''),
            'link' => $path,
            'url' => $frontendBaseUrl !== '' ? $frontendBaseUrl . $path : $path,
            'links' => [
                'laravel_react' => $path,
                'gm_react' => '/product/' . rawurlencode($identifier),
            ],
        ];
    }

    private function validateManagerAiItemPayload(Request $request, bool $creating, bool $requiresSourceIdentity = true): array
    {
        $rules = [
            'fid' => ['required', 'integer', 'min:1'],
            'email' => ['nullable', 'email', 'max:255'],
            'external_id' => ['nullable', 'string', 'max:255'],
            'source_url' => ['nullable', 'string', 'max:2000'],
            'source_domain' => ['nullable', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50'],
            'sku' => ['nullable', 'string', 'max:50'],
            'name' => [$creating ? 'required' : 'nullable', 'string', 'max:150'],
            'name_ru' => ['nullable', 'string', 'max:150'],
            'name_ua' => ['nullable', 'string', 'max:150'],
            'name_en' => ['nullable', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:65535'],
            'description_ru' => ['nullable', 'string', 'max:65535'],
            'description_ua' => ['nullable', 'string', 'max:65535'],
            'description_en' => ['nullable', 'string', 'max:65535'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'pay' => ['nullable', 'numeric', 'min:0'],
            'old_price' => ['nullable', 'numeric', 'min:0'],
            'wholesale_price' => ['nullable', 'numeric', 'min:0'],
            'pay1' => ['nullable', 'numeric', 'min:0'],
            'count' => ['nullable', 'numeric', 'min:0'],
            'in_stock' => ['nullable', 'boolean'],
            'idcaption' => ['nullable', 'integer', 'min:0'],
            'idglava' => ['nullable', 'integer', 'min:0'],
            'idtype' => ['nullable', 'string', 'max:20'],
            'hit' => ['nullable', 'boolean'],
            'top' => ['nullable', 'integer', 'min:0'],
            'web' => ['nullable', 'boolean'],
            'htmlkeys' => ['nullable', 'string', 'max:65535'],
            'htmlkeyspop' => ['nullable', 'string', 'max:65535'],
            'images' => ['nullable', 'array', 'max:10'],
            'images.*' => ['nullable', 'string', 'max:2000'],
            'image' => ['nullable', 'string', 'max:2000'],
            'image_thumb' => ['nullable', 'string', 'max:2000'],
        ];

        $payload = $request->validate($rules);
        $name = trim((string) ($payload['name'] ?? $payload['name_ru'] ?? ''));
        $externalId = trim((string) ($payload['external_id'] ?? ''));
        $sourceUrl = trim((string) ($payload['source_url'] ?? ''));

        if ($creating && $name === '') {
            throw ValidationException::withMessages(['name' => 'Передайте название товара.']);
        }

        if ($requiresSourceIdentity && $externalId === '' && $sourceUrl === '') {
            throw ValidationException::withMessages([
                'external_id' => 'Для повторного парсинга передайте external_id или source_url.',
            ]);
        }

        return $payload;
    }

    private function managerAiResolveProject(int $fid, ?string $email = null)
    {
        if (! Schema::hasTable('project')) {
            return response()->json(['success' => false, 'message' => 'Таблицу project не найдено'], 404);
        }

        $project = Project::query()->find($fid);
        if (! $project) {
            return response()->json(['success' => false, 'message' => 'Project для указанного fid не найден.'], 404);
        }

        $email = mb_strtolower(trim((string) $email));
        if ($email !== '' && Schema::hasColumn('project', 'email')) {
            $projectEmail = mb_strtolower(trim((string) ($project->email ?? '')));
            if ($projectEmail !== '' && $projectEmail !== $email) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email manager-ai не совпадает с project.email.',
                ], 403);
            }
        }

        return $project;
    }

    private function managerAiSourceHash(?string $sourceUrl): ?string
    {
        $sourceUrl = trim((string) $sourceUrl);
        if ($sourceUrl === '') {
            return null;
        }

        return hash('sha256', mb_strtolower($sourceUrl));
    }

    private function managerAiFindExistingItem(int $fid, array $payload): ?object
    {
        $externalId = trim((string) ($payload['external_id'] ?? ''));
        $sourceHash = $this->managerAiSourceHash($payload['source_url'] ?? null);
        $code = trim((string) ($payload['code'] ?? $payload['sku'] ?? $externalId));
        $generatedCode = $this->managerAiBuildCompCode($payload);
        $canMatchExternalId = $externalId !== '' && $this->managerAiCompHasColumn('manager_ai_external_id');
        $canMatchSourceHash = $sourceHash !== null && $this->managerAiCompHasColumn('manager_ai_source_hash');
        $canMatchNickname = $code !== '' && $this->managerAiCompHasColumn('nickname');
        $canMatchGeneratedCode = $generatedCode !== '' && $this->managerAiCompHasColumn('cod');

        if (! $canMatchExternalId && ! $canMatchSourceHash && ! $canMatchNickname && ! $canMatchGeneratedCode) {
            return null;
        }

        return DB::table('comp')
            ->where('firma', (string) $fid)
            ->where(function ($query) use (
                $externalId,
                $sourceHash,
                $code,
                $generatedCode,
                $canMatchExternalId,
                $canMatchSourceHash,
                $canMatchNickname,
                $canMatchGeneratedCode
            ) {
                if ($canMatchExternalId) {
                    $query->orWhere('manager_ai_external_id', $externalId);
                }
                if ($canMatchSourceHash) {
                    $query->orWhere('manager_ai_source_hash', $sourceHash);
                }
                if ($canMatchNickname) {
                    $query->orWhere('nickname', $code);
                }
                if ($canMatchGeneratedCode) {
                    $query->orWhere('cod', $generatedCode);
                }
            })
            ->orderBy('id')
            ->first();
    }

    private function managerAiPersistItem(?int $id, array $payload): array
    {
        $fid = (string) (int) $payload['fid'];
        $existing = $id ? DB::table('comp')->where('id', $id)->where('firma', $fid)->first() : null;
        $existingDesc = $id ? DB::table('descript')->where('pnum', $id)->where('firma', (int) $fid)->first() : null;
        $price = (float) ($payload['price'] ?? $payload['pay'] ?? ($existing->pay ?? 0));
        $wholesalePrice = (float) ($payload['wholesale_price'] ?? $payload['pay1'] ?? ($existing->pay1 ?? 0));
        $count = (float) ($payload['count'] ?? ($existing->count ?? 0));
        $images = $this->managerAiNormalizeImages($payload, $existing);
        $sourceUrl = trim((string) ($payload['source_url'] ?? ($existing->manager_ai_source_url ?? '')));
        $externalId = trim((string) ($payload['external_id'] ?? ($existing->manager_ai_external_id ?? '')));
        $sourceDomain = trim((string) ($payload['source_domain'] ?? ''));

        if ($sourceDomain === '' && $sourceUrl !== '') {
            $sourceDomain = (string) parse_url($sourceUrl, PHP_URL_HOST);
        }

        $compData = [
            'firma' => $fid,
            'nickname' => $this->managerAiFirstString([
                $payload['code'] ?? null,
                $payload['sku'] ?? null,
                $externalId,
                $existing->nickname ?? null,
            ]),
            'namedoc' => trim((string) ($payload['name'] ?? $payload['name_ru'] ?? ($existing->namedoc ?? ''))),
            'name' => trim((string) ($payload['name'] ?? $payload['name_ru'] ?? ($existing->name ?? ''))),
            'name_ua' => trim((string) ($payload['name_ua'] ?? ($existing->name_ua ?? ''))),
            'name_en' => trim((string) ($payload['name_en'] ?? ($existing->name_en ?? ''))),
            'idcaption' => (string) (int) ($payload['idcaption'] ?? ($existing->idcaption ?? 0)),
            'idglava' => (string) (int) ($payload['idglava'] ?? ($existing->idglava ?? 0)),
            'idtype' => trim((string) ($payload['idtype'] ?? ($existing->idtype ?? ''))),
            'pay' => $price,
            'pay1' => $wholesalePrice,
            'count' => $count,
            'sklad' => array_key_exists('in_stock', $payload)
                ? ($payload['in_stock'] ? 1 : 0)
                : ((float) $count > 0 ? 1 : (int) ($existing->sklad ?? 0)),
            'hit' => array_key_exists('hit', $payload) ? (int) (bool) $payload['hit'] : (int) ($existing->hit ?? 0),
            'top' => (int) ($payload['top'] ?? ($existing->top ?? 0)),
            'web' => array_key_exists('web', $payload) ? (string) (int) (bool) $payload['web'] : (string) ($existing->web ?? '1'),
            'htmlkeys' => trim((string) ($payload['htmlkeys'] ?? ($existing->htmlkeys ?? ''))),
            'htmlkeyspop' => trim((string) ($payload['htmlkeyspop'] ?? ($existing->htmlkeyspop ?? ''))),
            'nfoto' => $images[0] ?? '',
            'nfoto1' => $images[1] ?? '',
            'nfoto2' => $images[2] ?? '',
            'nfoto3' => $images[3] ?? '',
            'nfoto4' => $images[4] ?? '',
            'nfoto5' => $images[5] ?? '',
            'nfoto6' => $images[6] ?? '',
            'nfoto7' => $images[7] ?? '',
            'nfoto8' => $images[8] ?? '',
            'nfoto9' => $images[9] ?? '',
            'manager_ai_external_id' => $externalId !== '' ? $externalId : null,
            'manager_ai_source_url' => $sourceUrl !== '' ? $sourceUrl : null,
            'manager_ai_source_hash' => $this->managerAiSourceHash($sourceUrl),
            'manager_ai_last_seen_at' => now(),
        ];

        if ($sourceDomain !== '' && trim((string) ($compData['htmlkeys'] ?? '')) === '') {
            $compData['htmlkeys'] = $sourceDomain;
        }

        $compData = $this->filterTableColumns('comp', $compData);

        if ($existing) {
            DB::table('comp')->where('id', $id)->where('firma', $fid)->update($compData);
            $productId = (int) $id;
        } else {
            $compData['cod'] = $this->managerAiBuildCompCode($payload);
            if (Schema::hasColumn('comp', 'dt')) {
                $compData['dt'] = date('d-m-Y');
            }
            $productId = (int) DB::table('comp')->insertGetId($compData);
        }

        $descData = [
            'name' => trim((string) ($payload['name_ru'] ?? $payload['name'] ?? ($existingDesc->name ?? ''))),
            'name_ua' => trim((string) ($payload['name_ua'] ?? ($existingDesc->name_ua ?? ''))),
            'name_en' => trim((string) ($payload['name_en'] ?? ($existingDesc->name_en ?? ''))),
            'description' => (string) ($payload['description_ru'] ?? $payload['description'] ?? ($existingDesc->description ?? '')),
            'description_ua' => (string) ($payload['description_ua'] ?? ($existingDesc->description_ua ?? '')),
            'description_en' => (string) ($payload['description_en'] ?? ($existingDesc->description_en ?? '')),
            'web' => array_key_exists('web', $payload) ? (string) (int) (bool) $payload['web'] : '1',
        ];

        $descData = $this->filterTableColumns('descript', $descData);
        $hasDesc = DB::table('descript')->where('pnum', $productId)->where('firma', (int) $fid)->exists();
        if ($hasDesc) {
            DB::table('descript')->where('pnum', $productId)->where('firma', (int) $fid)->update($descData);
        } else {
            DB::table('descript')->insert(array_merge($descData, ['pnum' => $productId, 'firma' => (int) $fid]));
        }

        $this->managerAiSyncRetailPrice($fid, $productId, $price, $wholesalePrice, (float) ($payload['old_price'] ?? 0), $count);

        return ['id' => $productId];
    }

    private function managerAiNormalizeImages(array $payload, ?object $existing): array
    {
        $images = [];
        if (! empty($payload['image'])) {
            $images[] = trim((string) $payload['image']);
        }
        if (! empty($payload['image_thumb'])) {
            $images[] = trim((string) $payload['image_thumb']);
        }
        foreach ((array) ($payload['images'] ?? []) as $image) {
            $image = trim((string) $image);
            if ($image !== '') {
                $images[] = $image;
            }
        }

        if ($images === [] && $existing) {
            foreach (['nfoto', 'nfoto1', 'nfoto2', 'nfoto3', 'nfoto4', 'nfoto5', 'nfoto6', 'nfoto7', 'nfoto8', 'nfoto9'] as $column) {
                $value = trim((string) ($existing->{$column} ?? ''));
                if ($value !== '') {
                    $images[] = $value;
                }
            }
        }

        return array_values(array_slice(array_unique($images), 0, 10));
    }

    private function managerAiBuildCompCode(array $payload): string
    {
        $source = trim((string) ($payload['external_id'] ?? $payload['source_url'] ?? uniqid('mai_', true)));
        return substr('mai' . preg_replace('/[^A-Za-z0-9]/', '', hash('crc32b', $source)), 0, 30);
    }

    private function managerAiFirstString(array $values): string
    {
        foreach ($values as $value) {
            $value = trim((string) $value);
            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }

    private function managerAiSyncRetailPrice(string $fid, int $productId, float $price, float $pay1, float $oldPrice, float $count): void
    {
        if (! Schema::hasTable('price')) {
            return;
        }

        $tgroup = DB::table('conf')
            ->where('type', 'tgroup')
            ->where('firma', 0)
            ->where('status', '1')
            ->orderBy('id')
            ->value('id');

        if (! $tgroup) {
            $tgroup = DB::table('price')
                ->where('firma', $fid)
                ->where('pnum', (string) $productId)
                ->orderBy('id')
                ->value('tgroup');
        }

        if (! $tgroup) {
            return;
        }

        $row = [
            'pay' => $price,
            'pay1' => $pay1,
            'oldpay' => $oldPrice,
            'count' => $count,
            'sklad' => $count > 0 ? 1 : 0,
        ];

        $query = DB::table('price')
            ->where('firma', $fid)
            ->where('pnum', (string) $productId)
            ->where('tgroup', (string) $tgroup);

        if ($query->exists()) {
            $query->update($this->filterTableColumns('price', $row));
            return;
        }

        DB::table('price')->insert($this->filterTableColumns('price', array_merge($row, [
            'firma' => $fid,
            'pnum' => (string) $productId,
            'tgroup' => (string) $tgroup,
        ])));
    }

    private function managerAiLoadSerializedGood(int $fid, int $id, Request $request): ?array
    {
        $item = $this->managerAiGoodsBaseQuery($fid)->where('comp.id', $id)->first();
        if (! $item) {
            return null;
        }

        return $this->serializeManagerAiGood($item, $this->resolveApiLocale($request), $request);
    }

    private function filterTableColumns(string $table, array $payload): array
    {
        $columns = Schema::hasTable($table) ? Schema::getColumnListing($table) : [];
        if ($columns === []) {
            return [];
        }

        return array_intersect_key($payload, array_flip($columns));
    }

    private function managerAiCompHasColumn(string $column): bool
    {
        static $columns = null;

        if ($columns === null) {
            $columns = Schema::hasTable('comp')
                ? array_flip(Schema::getColumnListing('comp'))
                : [];
        }

        return isset($columns[$column]);
    }

    public function saveRating(Request $request, $id)
    {
        $user = Auth::guard('sanctum')->user();
        $fid = $this->resolveApiFid($request, '2');

        if (!Schema::hasTable('rating')) {
            return response()->json([
                'success' => false,
                'message' => 'Rating table is not migrated yet',
            ], 503);
        }

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
        ]);

        $comp = Goods::query()
            ->where('id', $id)
            ->where('firma', $fid)
            ->first();

        if (!$comp) {
            return response()->json([
                'success' => false,
                'message' => 'Товар не найден',
            ], 404);
        }

        Rating::updateOrCreate(
            [
                'user_id' => $user->id,
                'comp_id' => (int) $comp->id,
            ],
            [
                'rating' => (int) $validated['rating'],
            ]
        );

        $stats = DB::table('rating')
            ->select(
                DB::raw('AVG(rating) as rating_avg'),
                DB::raw('COUNT(*) as rating_count')
            )
            ->where('comp_id', (int) $comp->id)
            ->first();

        return response()->json([
            'success' => true,
            'rating_avg' => round((float) ($stats->rating_avg ?? 0), 2),
            'rating_count' => (int) ($stats->rating_count ?? 0),
            'user_rating' => (int) $validated['rating'],
        ]);
    }

    // ── Show / edit ───────────────────────────────────────────────────────────

    public function show(Request $request)
    {
        $pnum = $request->input('pnum', '0');
        $fid = session('fid', '');
        $locale = $this->resolveBackendLocale($request);

        $result = Goods::showGoods($pnum, $fid, $locale);
        $comp = $result['comp'];
        $descript = $result['descript'];
        $priceGroups = $result['priceGroups'];
        $prices = $result['prices'];
        $tops = $result['tops'];
        $subs = $result['subs'];
        $catalogSelectedTop = $result['catalogSelectedTop'];
        $catalogSelectedSub = $result['catalogSelectedSub'];
        $catalogAvailableSubs = $result['catalogAvailableSubs'];
        $news = $result['news'];
        $filterTags = $result['filterTags'];

        return view('goods.show', compact(
            'comp',
            'descript',
            'priceGroups',
            'prices',
            'tops',
            'subs',
            'catalogSelectedTop',
            'catalogSelectedSub',
            'catalogAvailableSubs',
            'news',
            'filterTags',
            'fid',
            'pnum',
            'locale'
        ));
    }

    /**
     * Групи та значення filter.* для категорій товару (idcaption, idglava) — для форми comp.htmlkeyspop (формат id_групи:id_значення,).
     */
    public function catalogFilterGroups(Request $request)
    {
        $fid = (string) session('fid', '');
        $idcaption = (int) $request->query('idcaption', 0);
        $idglava = (int) $request->query('idglava', 0);

        return response()->json([
            'groups' => $this->buildCatalogFilterGroupsPayload($fid, $idcaption, $idglava),
        ]);
    }

    /**
     * Public API: ті самі групи фільтрів, що в налаштуваннях «Фільтр» (по fid з query — як інші /api/goods/*).
     */
    public function catalogFilterGroupsApi(Request $request)
    {
        $fid = $this->resolveApiFid($request, '2');
        $idcaption = (int) $request->query('idcaption', 0);
        $idglava = (int) $request->query('idglava', 0);

        return response()->json([
            'groups' => $this->buildCatalogFilterGroupsPayload($fid, $idcaption, $idglava),
        ]);
    }

    /**
     * @return list<array{catalog_id: int, group: array, values: \Illuminate\Support\Collection<int, array>}>
     */
    private function buildCatalogFilterGroupsPayload(string $fid, int $idcaption, int $idglava): array
    {
        if (! Schema::hasTable('filter')) {
            return [];
        }

        $catalogIds = array_values(array_unique(array_filter([$idcaption, $idglava], fn ($x) => $x > 0)));

        if ($catalogIds === []) {
            return [];
        }

        $payload = [];
        $seenGroupIds = [];

        foreach ($catalogIds as $cid) {
            if (! $this->goodsCatalogFieldExists($fid, $cid)) {
                continue;
            }
            $groups = Filter::query()
                ->where('keyfield', 'filter')
                ->where('idkeyfield', $cid)
                ->where('idfilter', 0)
                ->orderBy('num')
                ->orderBy('id')
                ->get();

            foreach ($groups as $g) {
                $gid = (int) $g->id;
                if (isset($seenGroupIds[$gid])) {
                    continue;
                }
                $seenGroupIds[$gid] = true;

                $values = Filter::query()
                    ->where('keyfield', 'filter')
                    ->where('idkeyfield', $cid)
                    ->where('idfilter', $gid)
                    ->orderBy('num')
                    ->orderBy('id')
                    ->get();

                $payload[] = [
                    'catalog_id' => $cid,
                    'group' => $this->goodsSerializeFilterRow($g),
                    'values' => $values->map(fn ($v) => $this->goodsSerializeFilterRow($v))->values(),
                ];
            }
        }

        return $payload;
    }

    private function goodsCatalogFieldQuery($fid)
    {
        $firma = ($fid === null || $fid === '') ? 0 : (int) $fid;

        return DB::table('field')
            ->where('keyfield', 'catalog')
            ->where(function ($nested) use ($firma) {
                $nested->where('firma', $firma);
                if ($firma !== 0) {
                    $nested->orWhere('firma', 0);
                }
                if (Schema::hasTable('project') && Schema::hasColumn('project', 'constanta')) {
                    $marketplaceFirmas = Project::query()
                        ->where('constanta', 1)
                        ->pluck('id')
                        ->map(fn ($id) => (int) $id)
                        ->filter(fn ($id) => $id > 0 && $id !== $firma)
                        ->all();

                    if ($marketplaceFirmas !== []) {
                        $nested->orWhereIn('firma', array_values(array_unique($marketplaceFirmas)));
                    }
                }
            });
    }

    private function goodsCatalogFieldExists($fid, int $catalogId): bool
    {
        if (! Schema::hasTable('field') || $catalogId <= 0) {
            return false;
        }

        return $this->goodsCatalogFieldQuery($fid)->where('id', $catalogId)->exists();
    }

    private function goodsSerializeFilterRow(object $row): array
    {
        return [
            'id' => (int) $row->id,
            'idkeyfield' => (int) ($row->idkeyfield ?? 0),
            'idfilter' => (int) ($row->idfilter ?? 0),
            'val' => (string) ($row->val ?? ''),
            'valru' => (string) ($row->valru ?? ''),
            'valen' => (string) ($row->valen ?? ''),
        ];
    }

    // ── Save ──────────────────────────────────────────────────────────────────

    public function save(Request $request)
    {
        $fid = session('fid', '');
        $pnum = $request->input('id1', '');
        $idcaption = $request->input('idcaption', '');

        if ($idcaption === '') {
            return back()->withErrors(['idcaption' => 'Оберіть розділ каталогу']);
        }

        // ── File uploads ──────────────────────────────────────────────────────
        $fotoMap = [];
        foreach (range(1, 10) as $i) {
            $field = 'foto' . $i;
            $col = $i === 1 ? 'nfoto' : 'nfoto' . ($i - 1);
            if ($request->hasFile($field) && $request->file($field)->isValid()) {
                $path = $request->file($field)->store('files', 'public');
                $fotoMap[$col] = '/storage/' . $path;
            }
        }
        if ($request->hasFile('file1') && $request->file('file1')->isValid()) {
            $fotoMap['nfile'] = '/storage/' . $request->file('file1')->store('files', 'public');
        }

        // ── Price groups data
        $priceRows = [];
        foreach ((array) $request->input('tgroup', []) as $gid => $_) {
            $priceRows[$gid] = [
                'oldpay' => (float) ($request->input('toldpay')[$gid] ?? 0),
                'pay' => (float) ($request->input('tpay')[$gid] ?? 0),
                'pay1' => (float) ($request->input('tpay1')[$gid] ?? 0),
                'count' => (int) ($request->input('tcount')[$gid] ?? 0),
            ];
        }

        // ── Main comp data
        $compData = [
            'idcaption' => $idcaption,
            'idglava' => $request->input('idglava', ''),
            'idtype' => $request->input('idtype', 1),
            'hit' => (int) $request->input('hit', 0),
            'constanta' => (int) $request->input('constanta', 0),
            'top' => (int) $request->input('top', 0),
            'firma' => $request->input('firma', $fid),
            'nickname' => $this->safeGoodsText($request->input('nickname', ''), 'Код', 60),
            'namedoc' => $this->safeGoodsText($request->input('name_doc', ''), 'Назва документа', 120),
            'pay1' => (float) $request->input('pay1', 0),
            'pay' => (float) $request->input('pay', 0),
            'profitpay' => (float) $request->input('profitpay', 0),
            'sklad' => (int) $request->input('sklad', 0),
            'garant' => $this->safeGoodsText($request->input('garant', ''), 'Гарантія', 60),
            'htmldescr' => $request->input('htmldescr', ''),
            'htmlkeys' => $request->input('htmlkeys', ''),
            'htmlkeyspop' => $request->input('htmlkeyspop', ''),
            'nvideo1' => $request->input('video1', ''),
            'nvideo2' => $request->input('video2', ''),
        ];

        // ── Descript data
        $descData = [
            'name' => $this->safeGoodsText($request->input('name_client_ru', ''), 'Назва RU', 120),
            'name_ua' => $this->safeGoodsText($request->input('name_client_ua', ''), 'Назва UA', 120),
            'name_en' => $this->safeGoodsText($request->input('name_client_en', ''), 'Назва EN', 120),
            'description' => $this->safeGoodsText($request->input('description_ru', ''), 'Опис RU', 1000),
            'description_ua' => $this->safeGoodsText($request->input('description_ua', ''), 'Опис UA', 1000),
            'description_en' => $this->safeGoodsText($request->input('description_en', ''), 'Опис EN', 1000),
            'web' => $request->input('web', '0'),
            'descript' => $request->input('descript', 0),
            'descript2' => $request->input('descript2', 0),
            'descript3' => $request->input('descript3', 0),
            'descript4' => $request->input('descript4', 0),
            'descript5' => $request->input('descript5', 0),
        ];

        $pnum = Goods::saveGoods($pnum, $fid, $compData, $priceRows, $descData, $fotoMap);

        return redirect()->route('goods.show', ['pnum' => $pnum])->with('success', 'Збережено');
    }

    private function safeGoodsText(mixed $value, string $label, int $maxLength): string
    {
        $raw = trim((string) ($value ?? ''));

        if ($raw !== '' && preg_match('/[\x00-\x1F\x7F<>]/u', $raw)) {
            throw ValidationException::withMessages([
                $label => $label . ': недопустимі символи.',
            ]);
        }

        $text = preg_replace('/\s+/u', ' ', trim(strip_tags($raw)));

        return mb_substr((string) $text, 0, $maxLength);
    }

    // ── Delete ────────────────────────────────────────────────────────────────

    public function destroy(Request $request)
    {
        $id = $request->input('id', '');
        $cod = $request->input('cod', '');
        $fid = session('fid', '');

        $deleted = Goods::deleteGoods($id, $cod, $fid);

        if (!$deleted) {
            return back()->withErrors(['delete' => 'Товар використовується в документах']);
        }

        return redirect()->route('goods.index')->with('success', 'Видалено');
    }

    public function bulkFlags(Request $request)
    {
        $validated = $request->validate([
            'action' => ['required', 'in:in_stock,out_of_stock,hit,not_hit'],
            'goods_ids' => ['required', 'array', 'min:1'],
            'goods_ids.*' => ['integer'],
        ]);

        $ids = collect($validated['goods_ids'])
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return back()->withErrors(['goods_ids' => 'Выберите товары для изменения']);
        }

        $updates = match ($validated['action']) {
            'in_stock' => ['sklad' => 1],
            'out_of_stock' => ['sklad' => 0],
            'hit' => ['hit' => 1],
            'not_hit' => ['hit' => 0],
        };

        Goods::whereIn('id', $ids)->update($updates);

        return back()->with('success', 'Товары обновлены');
    }

    // ── Toggle sklad (AJAX) ───────────────────────────────────────────────────

    public function toggleSklad(Request $request)
    {
        $cod = $request->input('cod', '');
        $idagent = $request->input('idagent', session('fid', ''));

        if ($cod === '')
            abort(422);

        $comp = Goods::where('cod', $cod)->firstOrFail();
        $new = $comp->toggleSklad($idagent);

        return response()->json(['sklad' => $new]);
    }
}
