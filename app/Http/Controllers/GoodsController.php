<?php

namespace App\Http\Controllers;

use App\Models\Goods;
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
        $idagent = session('fid', '');

        session(['idcaption' => $idcaption, 'idglava' => $idglava, 'pos' => $pos, 'sort' => $sort]);

        $filters = [
            'fName'       => session('filter1', ''),
            'filterFirma' => session('filter_firma', ''),
            'filterBrand' => session('filter_brand', ''),
            'skladNone'   => session('sklad_none', ''),
            'priceFrom'   => session('price00', ''),
            'priceTo'     => session('price01', ''),
        ];

        $result   = Goods::init($fid, $idagent, $idcaption, $idglava, $pos, $pos2, $sort, $filters);
        $comps    = $result['comps'];
        $total    = $result['total'];
        $pers     = $result['pers'];
        $sections = $result['sections'];

        return view('goods.index', compact(
            'comps', 'total', 'pos', 'pos2', 'fid',
            'idcaption', 'idglava', 'pers', 'sections'
        ));
    }

    // ── Show / edit ───────────────────────────────────────────────────────────

    public function show(Request $request)
    {
        $pnum = $request->input('pnum', '0');
        $fid = session('fid', '');

        $result      = Goods::showGoods($pnum, $fid);
        $comp        = $result['comp'];
        $descript    = $result['descript'];
        $priceGroups = $result['priceGroups'];
        $prices      = $result['prices'];
        $tops        = $result['tops'];
        $subs        = $result['subs'];
        $news        = $result['news'];
        $filterTags  = $result['filterTags'];

        return view('goods.show', compact(
            'comp', 'descript', 'priceGroups', 'prices',
            'tops', 'subs', 'news', 'filterTags', 'fid'
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
                'pay'    => (float)($request->input('tpay')[$gid] ?? 0),
                'pay1'   => (float)($request->input('tpay1')[$gid] ?? 0),
                'count'  => (int)($request->input('tcount')[$gid] ?? 0),
            ];
        }

        // ── Main comp data
        $compData = [
            'idcaption'   => $idcaption,
            'idglava'     => $request->input('idglava', ''),
            'idtype'      => $request->input('idtype', 1),
            'hit'         => (int)$request->input('hit', 0),
            'constanta'   => (int)$request->input('constanta', 0),
            'top'         => (int)$request->input('top', 0),
            'firma'       => $request->input('firma', $fid),
            'nickname'    => convert_to_base($request->input('nickname', '')),
            'namedoc'     => convert_to_base($request->input('name_doc', '')),
            'pay1'        => (float)$request->input('pay1', 0),
            'pay'         => (float)$request->input('pay', 0),
            'profitpay'   => (float)$request->input('profitpay', 0),
            'sklad'       => (int)$request->input('sklad', 0),
            'garant'      => $request->input('garant', ''),
            'htmldescr'   => convert_to_base($request->input('htmldescr', '')),
            'htmlkeys'    => convert_to_base($request->input('htmlkeys', '')),
            'htmlkeyspop' => convert_to_base($request->input('htmlkeyspop', '')),
            'nvideo1'     => $request->input('video1', ''),
            'nvideo2'     => $request->input('video2', ''),
        ];

        // ── Descript data
        $descData = [
            'name'           => convert_to_base($request->input('name_client_ru', '')),
            'name_ua'        => convert_to_base($request->input('name_client_ua', '')),
            'name_en'        => convert_to_base($request->input('name_client_en', '')),
            'description'    => convert_to_base($request->input('description_ru', '')),
            'description_ua' => convert_to_base($request->input('description_ua', '')),
            'description_en' => convert_to_base($request->input('description_en', '')),
            'web'            => convert_to_base($request->input('web', '0')),
            'descript'       => $request->input('descript', 0),
            'descript2'      => $request->input('descript2', 0),
            'descript3'      => $request->input('descript3', 0),
            'descript4'      => $request->input('descript4', 0),
            'descript5'      => $request->input('descript5', 0),
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