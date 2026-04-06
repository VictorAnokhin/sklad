<?php

namespace App\Http\Controllers;

use App\Models\Goods;
use App\Models\Field;
use App\Models\Price;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
        $idglava = $request->input('igla', session('idglava', ''));
        $idcaption = $request->input('idcapt', session('idcaption', ''));
        $pos = (int) $request->input('pos', session('pos', 0));
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
            'pos' => $pos,
            'sort' => $sort,
            'filter1' => $filters['fName'],
            'filter_brand' => $filters['filterBrand'],
            'sklad_none' => $filters['skladNone'],
        ]);

        $result = Goods::init($fid, $idcaption, $idglava, $pos, $pos2, $sort, $filters);
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
                DB::raw('COALESCE(d.name, comp.nickname, "") as name'),
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

        $goods = Goods::attachPreferredPricesByItemFirma($goods)
            ->map(function ($g) {
                $desc = $g->description_ua ?: $g->description_en ?: $g->description ?: '';
                // Strip HTML tags from description for search results
                $desc = strip_tags($desc);

                return [
                    'id' => (int) $g->id,
                    'name' => $g->name ?: '',
                    'price' => (float) ($g->price_pay ?? 0),
                    'oldPrice' => (float) ($g->price_oldpay ?? 0),
                    'wholesalePrice' => $g->wholesale_price !== null ? (float) $g->wholesale_price : null,
                    'wholesaleOldPrice' => $g->wholesale_oldpay !== null ? (float) $g->wholesale_oldpay : null,
                    'wholesaleFrom' => $g->wholesale_from !== null ? (int) $g->wholesale_from : null,
                    'count' => (int) ($g->price_count ?? 0),
                    'image' => $g->image ?? '',
                    'image_thumb' => $g->image_thumb ?? '',
                    'description' => mb_substr($desc, 0, 200),
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
                DB::raw('COALESCE(d.name, comp.nickname, "") as name'),
                'comp.pay',
                'comp.firma'
            )
            ->limit(20)
            ->get();

        $goods = Goods::attachPreferredPricesByItemFirma($goods)
            ->map(function ($g) {
                return [
                    'id' => $g->id,
                    'pnum' => $g->id,
                    'name' => $g->name,
                    'price' => (float) ($g->price_pay ?? 0),
                    'wholesalePrice' => $g->wholesale_price !== null ? (float) $g->wholesale_price : null,
                    'wholesaleFrom' => $g->wholesale_from !== null ? (int) $g->wholesale_from : null,
                    'count' => (float) ($g->price_count ?? 0),
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
        $hits = Goods::getHits($fid, $limit, $offset);

        return response()->json([
            'success' => true,
            'data'    => $hits,
            'limit'   => $limit,
            'offset'  => $offset,
        ]);
    }

    // ── Get Sections (API) ────────────────────────────────────────────────────

    public function getSections(Request $request)
    {
        $fid = $this->resolveApiFid($request, '2');
        $tree = Field::getCatalogTree($fid);
        return response()->json([
            'success' => true,
            'data' => $tree,
        ]);
    }

    // ── Get Goods By Section (API) ────────────────────────────────────────────

    public function getBySection(Request $request, $id)
    {
        $limit = (int) $request->input('limit', 20);
        $offset = (int) $request->input('offset', 0);

        $fid = $this->resolveApiFid($request, '2');
        $result = Goods::getWebGoodsBySection($fid, $id, $limit, $offset);

        return response()->json([
            'success' => true,
            'data' => $result['goods'],
            'total' => $result['total'],
            'limit' => $limit,
            'offset' => $offset,
        ]);
    }

    // ── Show / edit ───────────────────────────────────────────────────────────

    public function show(Request $request)
    {
        $pnum = $request->input('pnum', '0');
        $fid = session('fid', '');

        $result = Goods::showGoods($pnum, $fid);
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
