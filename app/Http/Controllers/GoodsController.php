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
    // ── List ──────────────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $fid = session('fid', '');
        $idcaption = $request->input('idcapt', session('idcaption', ''));
        $idglava = $request->input('igla', session('idglava', ''));
        $pos = (int)$request->input('pos', session('pos', 0));
        $pos2 = (int)$request->input('pos2', 15);
        $sort = $request->input('sort', session('sort', 'pay'));

        $filters = [
            'fName' => $request->input('fName', session('filter1', '')),
            'filterBrand' => $request->input('filterBrand', session('filter_brand', '')),
            'skladNone' => $request->input('skladNone', session('sklad_none', '')),
            'priceFrom' => $request->input('priceFrom', session('price00', '')),
            'priceTo' => $request->input('priceTo', session('price01', '')),
        ];

        session([
            'idcaption' => $idcaption,
            'idglava' => $idglava,
            'pos' => $pos,
            'sort' => $sort,
            'filter1' => $filters['fName'],
            'filter_brand' => $filters['filterBrand'],
            'sklad_none' => $filters['skladNone'],
            'price00' => $filters['priceFrom'],
            'price01' => $filters['priceTo'],
        ]);

        $result = Goods::init($fid, $idcaption, $idglava, $pos, $pos2, $sort, $filters);
        $comps = $result['comps'];
        $total = $result['total'];
        $pers = $result['pers'];
        $sections = $result['sections'];

        return view('goods.index', compact(
            'comps', 'total', 'pos', 'pos2', 'fid',
            'idcaption', 'idglava', 'pers', 'sections', 'filters', 'sort'
        ));
    }

    // ── Search (Async) ────────────────────────────────────────────────────────

    public function search(Request $request)
    {
        $q = $request->input('q');
        if (!$q || mb_strlen($q) < 2)
            return response()->json([]);

        $fid = session('fid', '');

        $goods = Goods::query()
            ->leftJoin('price', function ($join) use ($fid) {
            $join->on('price.pnum', '=', 'comp.id')
                ->where('price.firma', '=', $fid)
                ->where('price.tgroup', '=', '1'); // Default retail group
        })
            ->where('comp.firma', $fid)
            ->where(function ($query) use ($q) {
            $query->where('comp.id', 'LIKE', "%{$q}%")
                ->orWhere('comp.name', 'LIKE', "%{$q}%")
                ->orWhere('comp.name_ua', 'LIKE', "%{$q}%")
                ->orWhere('comp.name_en', 'LIKE', "%{$q}%")
                ->orWhere('comp.htmlkeyspop', 'LIKE', "%{$q}%");
        })
            ->select('comp.id', 'comp.name',
            DB::raw('COALESCE(price.pay, comp.pay, 0) as price'),
            DB::raw('COALESCE(price.count, 0) as count'))
            ->limit(20)
            ->get()
            ->map(function ($g) {
            return [
            'id' => $g->id,
            'pnum' => $g->id,
            'name' => $g->name,
            'price' => (float)$g->price,
            'count' => (float)$g->count,
            ];
        });

        return response()->json($goods);
    }

    // ── Get Hit Goods (API) ───────────────────────────────────────────────────

    public function getHits(Request $request)
    {
        $limit = (int)$request->input('limit', 10);
        $offset = (int)$request->input('offset', 0);

        $hits = Goods::query()
            ->leftJoin('price', function ($join) {
            $join->on('price.pnum', '=', 'comp.id')
                ->where('price.tgroup', '=', '1'); // Default retail group
        })

            ->where('comp.web', '1')
            ->select(
            'comp.id',
            'comp.name',
            'comp.name_ua',
            'comp.name_en',
            'comp.description',
            'comp.description_ua',
            'comp.description_en',
            'comp.nfoto',
            'comp.nfoto1',
            'comp.pay',
            DB::raw('COALESCE(price.pay, comp.pay, 0) as price'),
            DB::raw('COALESCE(price.count, 0) as count'),
            'comp.firma'
        )
            ->orderBy('comp.hit', 'desc')
            ->offset($offset)
            ->limit($limit)
            ->get()
            ->map(function ($item) {
            return [
            'id' => $item->id,
            'name' => $item->name,
            'name_ua' => $item->name_ua,
            'name_en' => $item->name_en,
            'description' => $item->description,
            'description_ua' => $item->description_ua,
            'description_en' => $item->description_en,
            'price' => (float)$item->price,
            'oldPrice' => (float)$item->pay,
            'count' => (int)$item->count,
            'image' => $item->nfoto,
            'image_thumb' => $item->nfoto1,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $hits,
            'limit' => $limit,
            'offset' => $offset,
        ]);

    }

    // ── Get Sections (API) ────────────────────────────────────────────────────

    public function getSections(Request $request)
    {
        $tree = Field::getCatalogTree();

        return response()->json([
            'success' => true,
            'data' => $tree,
        ]);
    }

    // ── Get Goods By Section (API) ────────────────────────────────────────────

    public function getBySection(Request $request, $id)
    {
        $limit = (int)$request->input('limit', 20);
        $offset = (int)$request->input('offset', 0);

        $result = Goods::getWebGoodsBySection($id, $limit, $offset);

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
            'comp', 'descript', 'priceGroups', 'prices',
            'tops', 'subs', 'news', 'filterTags', 'fid', 'pnum'
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
        foreach ((array)$request->input('tgroup', []) as $gid => $_) {
            $priceRows[$gid] = [
                'oldpay' => (float)($request->input('toldpay')[$gid] ?? 0),
                'pay' => (float)($request->input('tpay')[$gid] ?? 0),
                'pay1' => (float)($request->input('tpay1')[$gid] ?? 0),
                'count' => (int)($request->input('tcount')[$gid] ?? 0),
            ];
        }

        // ── Main comp data
        $compData = [
            'idcaption' => $idcaption,
            'idglava' => $request->input('idglava', ''),
            'idtype' => $request->input('idtype', 1),
            'hit' => (int)$request->input('hit', 0),
            'constanta' => (int)$request->input('constanta', 0),
            'top' => (int)$request->input('top', 0),
            'firma' => $request->input('firma', $fid),
            'nickname' => $request->input('nickname', ''),
            'namedoc' => $request->input('name_doc', ''),
            'pay1' => (float)$request->input('pay1', 0),
            'pay' => (float)$request->input('pay', 0),
            'profitpay' => (float)$request->input('profitpay', 0),
            'sklad' => (int)$request->input('sklad', 0),
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