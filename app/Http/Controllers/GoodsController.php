<?php

namespace App\Http\Controllers;

use App\Models\Goods;
use App\Models\Field;
use App\Models\Price;
use App\Models\Rating;
use App\Support\MediaUrl;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

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
        $idglava = $request->input('igla', session('idglava', ''));
        $idcaption = $request->input('idcapt', session('idcaption', ''));
        $pos = (int) $request->input('pos', session('goods_pos', 0));
        $pos2 = (int) $request->input('pos2', 20);
        $sort = $request->input('sort', session('sort', 'pay'));

        $filters = [
            'fName' => $request->input('fName', session('filter1', '')),
            'filterBrand' => $request->input('filterBrand', session('filter_brand', '')),
            'skladNone' => $request->input('skladNone', session('sklad_none', '')),
        ];

        session([
            'idcaption' => $idcaption,
            'idglava' => $idglava,
            'goods_pos' => $pos,
            'sort' => $sort,
            'filter1' => $filters['fName'],
            'filter_brand' => $filters['filterBrand'],
            'sklad_none' => $filters['skladNone'],
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

    // ── Search (Web API — for Accessories page) ──────────────────────────────

    public function searchWeb(Request $request)
    {
        $q = $request->input('q');
        if (!$q || mb_strlen($q) < 2) {
            return response()->json([]);
        }

        $fid = $this->resolveApiFid($request, '2');
        $locale = $this->resolveApiLocale($request);

        // Get User's tgroup from Sanctum auth
        $user = Auth::guard('sanctum')->user();
        $tgroupId = $user ? ($user->idstatus ?: $user->ustype) : null;

        $goods = DB::table('comp')
            ->leftJoin('descript as d', function ($join) use ($fid) {
                $join->on('d.pnum', '=', 'comp.id')
                    ->where('d.firma', '=', $fid);
            })
            ->where('comp.firma', $fid)
            ->where(function ($query) use ($q) {
                $query->where('d.name', 'LIKE', "%{$q}%")
                    ->orWhere('d.name_ua', 'LIKE', "%{$q}%")
                    ->orWhere('d.name_en', 'LIKE', "%{$q}%")
                    ->orWhere('d.description', 'LIKE', "%{$q}%")
                    ->orWhere('d.description_ua', 'LIKE', "%{$q}%")
                    ->orWhere('d.description_en', 'LIKE', "%{$q}%")
                    ->orWhere('comp.htmlkeyspop', 'LIKE', "%{$q}%");
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
        $doc = strtoupper((string) $request->input('doc', ''));
        
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
                DB::raw('COALESCE(d.name_ua, "") as name_ua'),
                DB::raw('COALESCE(d.name_en, "") as name_en')
            )
            ->limit(20)
            ->get();

        $goods = Goods::attachRatings(
            Goods::attachPreferredPricesByItemFirma($goods, $tgroupId),
            $user?->id
        )
            ->map(function ($g) use ($doc, $locale) {
                // For ZIN documents (purchase/procurement), use purchase price (comp.pay)
                // For ZOUT documents (orders), use price.pay by default,
                // but if quantity >= price.count and price.pay1 > 0, use price.pay1
                // For other documents, use sales price (comp.pay1 or preferred price)
                if ($doc === 'ZIN') {
                    $price = (float) ($g->pay1 ?? 0);
                } elseif ($doc === 'ZOUT') {
                    $basePrice = (float) ($g->price_pay ?? $g->pay ?? 0);
                    $wholesalePrice = (float) ($g->price_pay1 ?? 0);
                    $wholesaleThreshold = (int) ($g->price_count ?? 0);
                    
                    // For ZOUT: use wholesale price only if threshold is set and wholesale price > 0
                    // The actual quantity check will be done on frontend
                    if ($wholesaleThreshold > 0 && $wholesalePrice > 0) {
                        $price = $wholesalePrice;
                    } else {
                        $price = $basePrice;
                    }
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
                    'rating_avg' => (float) ($g->rating_avg ?? 0),
                    'rating_count' => (int) ($g->rating_count ?? 0),
                    'user_rating' => $g->user_rating !== null ? (int) $g->user_rating : null,
                ];
            });

        return response()->json($goods);
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

        return response()->json([
            'success' => true,
            'data' => $regions,
            'locale' => $locale,
        ]);
    }

    // ── Get Goods By Section (API) ────────────────────────────────────────────

    public function getBySection(Request $request, $id)
    {
        $limit = (int) $request->input('limit', 20);
        $offset = (int) $request->input('offset', 0);
        $hitOnly = $request->boolean('hit');

        $fid = $this->resolveApiFid($request, '2');
        $locale = $this->resolveApiLocale($request);

        $user = Auth::guard('sanctum')->user();
        $tgroupId = $user ? ($user->idstatus ?: $user->ustype) : null;
        $result = Goods::getWebGoodsBySection($fid, $id, $limit, $offset, $locale, $tgroupId, $hitOnly, $user?->id);

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
        $news = $result['news'];
        $filterTags = $result['filterTags'];

        return view('goods.show', compact(
            'comp',
            'descript',
            'priceGroups',
            'prices',
            'tops',
            'subs',
            'news',
            'filterTags',
            'fid',
            'pnum'
        ));
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
            'nickname' => $request->input('nickname', ''),
            'namedoc' => $request->input('name_doc', ''),
            'pay1' => (float) $request->input('pay1', 0),
            'pay' => (float) $request->input('pay', 0),
            'profitpay' => (float) $request->input('profitpay', 0),
            'sklad' => (int) $request->input('sklad', 0),
            'garant' => $request->input('garant', ''),
            'htmldescr' => $request->input('htmldescr', ''),
            'htmlkeys' => $request->input('htmlkeys', ''),
            'htmlkeyspop' => $request->input('htmlkeyspop', ''),
            'nvideo1' => $request->input('video1', ''),
            'nvideo2' => $request->input('video2', ''),
        ];

        // ── Descript data
        $descData = [
            'name' => $request->input('name_client_ru', ''),
            'name_ua' => $request->input('name_client_ua', ''),
            'name_en' => $request->input('name_client_en', ''),
            'description' => $request->input('description_ru', ''),
            'description_ua' => $request->input('description_ua', ''),
            'description_en' => $request->input('description_en', ''),
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
