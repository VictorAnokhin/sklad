<?php

namespace App\Http\Controllers;

use App\Models\Comp;
use App\Models\Price;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * CompController
 * Migrated from: comp/index.php, comp/show.php (product edit form),
 *                run-comp.php, delete-comp.php, toggle-sklad.php
 */
class CompController extends Controller
{
    // ── List ──────────────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $fid       = session('fid', '');
        $idstatus  = session('idstatus', '0');
        $idcaption = $request->input('idcapt', session('idcaption', ''));
        $idglava   = $request->input('igla',   session('idglava',   ''));
        $pos       = (int)$request->input('pos', session('pos', 0));
        $pos2      = (int)$request->input('pos2', 15);
        $sort      = $request->input('sort', session('sort', 'pay'));

        session(['idcaption' => $idcaption, 'idglava' => $idglava, 'pos' => $pos, 'sort' => $sort]);

        // Filter values
        $fName       = session('filter1', '');
        $filterFirma = session('filter_firma', '');
        $filterBrand = session('filter_brand', '');
        $skladNone   = session('sklad_none', '');
        $priceFrom   = session('price00', '');
        $priceTo     = session('price01', '');
        $idagent     = session('fid', '');

        $query = DB::table('comp')
            ->join('price', 'price.pnum', '=', 'comp.id')
            ->where('price.idagent', $idagent)
            ->where(function ($q) use ($fid) {
                $q->where('comp.firma', $fid)->orWhere('comp.constanta', '1');
            })
            ->select('comp.*', 'price.pay', 'price.pay1', 'price.oldpay',
                     'price.count', 'price.sklad as price_sklad', 'price.tgroup');

        if ($idcaption)   $query->where('comp.idcaption', $idcaption);
        if ($idglava)     $query->where('comp.idglava',   $idglava);
        if ($fName)       $query->where('comp.htmlkeyspop', 'like', "%{$fName}%");
        if ($filterFirma) $query->where('comp.firma', $filterFirma);
        if ($filterBrand) $query->where('price.tgroup', $filterBrand);
        if ($skladNone !== '1') $query->where('comp.sklad', '1');
        if ($priceFrom)   $query->where('price.pay', '>=', $priceFrom);
        if ($priceTo)     $query->where('price.pay', '<=', $priceTo);

        $total = (clone $query)->count();
        $comps = $query->orderBy($sort === 'description' ? 'comp.name' : $sort)
                       ->offset($pos)->limit($pos2)->get();

        // Markup %
        $pers = DB::table('field')
            ->where('id', $idcaption ?: $idglava)->value('pers') ?? 0;

        // Catalog tree for navigation
        $sections = DB::table('field')
            ->where('keyfield', 'catalog')
            ->where(fn($q) => $q->where('firma', $fid)->orWhere('constanta', '1'))
            ->orderBy('num')->get();

        return view('comp.index', compact(
            'comps', 'total', 'pos', 'pos2', 'fid',
            'idcaption', 'idglava', 'pers', 'sections'
        ));
    }

    // ── Show / edit ───────────────────────────────────────────────────────────

    public function show(Request $request)
    {
        $pnum = $request->input('pnum', '0');
        $fid  = session('fid', '');

        $comp       = $pnum !== '0' ? Comp::find($pnum) : null;
        $descript   = $pnum !== '0' ? DB::table('descript')
            ->where('pnum', $pnum)->where('firma', $fid)->first() : null;

        // All price groups
        $priceGroups = DB::table('conf')
            ->where('type', 'tgroup')
            ->where(fn($q) => $q->where('firma', $fid)->orWhere('constanta', '1'))
            ->orderBy('name')->get();

        // Prices per group for this product
        $prices = $pnum !== '0'
            ? DB::table('price')->where('pnum', $pnum)->where('firma', $fid)
                ->get()->keyBy('tgroup')
            : collect();

        // Catalog sections (two-level)
        $tops    = DB::table('field')
            ->where('idkeyfield', '')->where('keyfield', 'catalog')
            ->where(fn($q) => $q->where('firma', $fid)->orWhere('constanta', '1'))
            ->orderBy('num')->get();
        $subs    = DB::table('field')
            ->where('idkeyfield', '<>', '')->where('keyfield', 'catalog')
            ->where(fn($q) => $q->where('firma', $fid)->orWhere('constanta', '1'))
            ->orderBy('num')->get()->groupBy('idkeyfield');

        // News for descript 1-5
        $news = DB::table('news')->where('firma', $fid)
            ->orderByDesc('id')->get(['id', 'title']);

        // Tags (filter params)
        $filterTags = DB::table('conf')
            ->where('type', 'filter')
            ->where(fn($q) => $q->where('firma', $fid)->orWhere('constanta', '1'))
            ->orderBy('name')->get();

        return view('comp.show', compact(
            'comp', 'descript', 'priceGroups', 'prices',
            'tops', 'subs', 'news', 'filterTags', 'fid'
        ));
    }

    // ── Save ──────────────────────────────────────────────────────────────────

    public function save(Request $request)
    {
        $fid       = session('fid', '');
        $pnum      = $request->input('id1', '');
        $idcaption = $request->input('idcaption', '');

        if ($idcaption === '') {
            return back()->withErrors(['idcaption' => 'Оберіть розділ каталогу']);
        }

        // ── File uploads ──────────────────────────────────────────────────────
        $fotoMap = [];
        foreach (range(1, 10) as $i) {
            $field = 'foto' . $i;
            $col   = $i === 1 ? 'nfoto' : 'nfoto' . ($i - 1);
            if ($request->hasFile($field) && $request->file($field)->isValid()) {
                $path = $request->file($field)->store('files', 'public');
                $fotoMap[$col] = '/storage/' . $path;
            }
        }
        if ($request->hasFile('file1') && $request->file('file1')->isValid()) {
            $fotoMap['nfile'] = '/storage/' . $request->file('file1')->store('files', 'public');
        }

        // ── Price groups upsert ───────────────────────────────────────────────
        foreach ((array)$request->input('tgroup', []) as $gid => $_) {
            $row = [
                'oldpay' => (float)($request->input('toldpay')[$gid] ?? 0),
                'pay'    => (float)($request->input('tpay')[$gid]    ?? 0),
                'pay1'   => (float)($request->input('tpay1')[$gid]   ?? 0),
                'count'  => (int)  ($request->input('tcount')[$gid]  ?? 0),
            ];
            $existing = DB::table('price')
                ->where('tgroup', $gid)->where('pnum', $pnum)->where('firma', $fid)
                ->exists();
            if ($existing) {
                DB::table('price')
                    ->where('tgroup', $gid)->where('pnum', $pnum)->where('firma', $fid)
                    ->update($row);
            } else {
                DB::table('price')->insert(array_merge($row, [
                    'tgroup' => $gid, 'pnum' => $pnum, 'firma' => $fid,
                ]));
            }
        }

        // ── Main comp data ────────────────────────────────────────────────────
        $compData = array_merge([
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
        ], $fotoMap);

        if ($pnum === '' || $pnum === '0') {
            $compData['cod'] = date('dmHis') . rand(10, 99);
            $compData['dt']  = now();
            $pnum = (string)DB::table('comp')->insertGetId($compData);
        } else {
            DB::table('comp')->where('id', $pnum)->update($compData);
        }

        // ── Descript upsert ───────────────────────────────────────────────────
        $desc = [
            'name'           => convert_to_base($request->input('name_client_ru', '')),
            'name_ua'        => convert_to_base($request->input('name_client_ua', '')),
            'name_en'        => convert_to_base($request->input('name_client_en', '')),
            'description'    => convert_to_base($request->input('description_ru', '')),
            'description_ua' => convert_to_base($request->input('description_ua', '')),
            'description_en' => convert_to_base($request->input('description_en', '')),
            'web'            => convert_to_base($request->input('web', '0')),
            'descript'  => $request->input('descript',  0),
            'descript2' => $request->input('descript2', 0),
            'descript3' => $request->input('descript3', 0),
            'descript4' => $request->input('descript4', 0),
            'descript5' => $request->input('descript5', 0),
        ];
        $hasDesc = DB::table('descript')->where('pnum', $pnum)->where('firma', $fid)->exists();
        if ($hasDesc) {
            DB::table('descript')->where('pnum', $pnum)->where('firma', $fid)->update($desc);
        } else {
            DB::table('descript')->insert(array_merge($desc, ['pnum' => $pnum, 'firma' => $fid]));
        }

        return redirect()->route('comp.show', ['pnum' => $pnum])->with('success', 'Збережено');
    }

    // ── Delete ────────────────────────────────────────────────────────────────

    public function destroy(Request $request)
    {
        $id  = $request->input('id', '');
        $cod = $request->input('cod', '');
        $fid = session('fid', '');

        if (DB::table('z_body')->where('pnum', $id)->exists()) {
            return back()->withErrors(['delete' => 'Товар використовується в документах']);
        }

        DB::table('price')->where('cod', $cod)->update(['cod' => '']);
        DB::table('comp')->where('id', $id)->where('firma', $fid)->delete();

        return redirect()->route('comp.index')->with('success', 'Видалено');
    }

    // ── Toggle sklad (AJAX) ───────────────────────────────────────────────────

    public function toggleSklad(Request $request)
    {
        $cod     = $request->input('cod', '');
        $idagent = $request->input('idagent', session('fid', ''));

        if ($cod === '') abort(422);

        $comp = Comp::where('cod', $cod)->firstOrFail();
        $new  = $comp->toggleSklad($idagent);

        return response()->json(['sklad' => $new]);
    }
}
