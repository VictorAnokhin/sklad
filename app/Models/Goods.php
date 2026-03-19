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

    // ── init: list query for index ────────────────────────────────────────────

    public static function init($fid, $idagent, $idcaption, $idglava, $pos, $pos2, $sort, $filters)
    {
        $fName       = $filters['fName'] ?? '';
        $filterFirma = $filters['filterFirma'] ?? '';
        $filterBrand = $filters['filterBrand'] ?? '';
        $skladNone   = $filters['skladNone'] ?? '';
        $priceFrom   = $filters['priceFrom'] ?? '';
        $priceTo     = $filters['priceTo'] ?? '';

        $query = DB::table('comp')
            ->join('price', 'price.pnum', '=', 'comp.id')
            ->where('price.idagent', $idagent)
            ->where(function ($q) use ($fid) {
                $q->where('comp.firma', $fid)->orWhere('comp.constanta', '1');
            })
            ->select('comp.*', 'price.pay', 'price.pay1', 'price.oldpay',
                'price.count', 'price.sklad as price_sklad', 'price.tgroup');

        if ($idcaption)  $query->where('comp.idcaption', $idcaption);
        if ($idglava)    $query->where('comp.idglava', $idglava);
        if ($fName)      $query->where('comp.htmlkeyspop', 'like', "%{$fName}%");
        if ($filterFirma) $query->where('comp.firma', $filterFirma);
        if ($filterBrand) $query->where('price.tgroup', $filterBrand);
        if ($skladNone !== '1') $query->where('comp.sklad', '1');
        if ($priceFrom)  $query->where('price.pay', '>=', $priceFrom);
        if ($priceTo)    $query->where('price.pay', '<=', $priceTo);

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

        return [
            'comps'    => $comps,
            'total'    => $total,
            'pers'     => $pers,
            'sections' => $sections,
        ];
    }

    // ── show: load single product + related data ──────────────────────────────

    public static function showGoods($pnum, $fid)
    {
        $comp = $pnum !== '0' ? self::find($pnum) : null;
        $descript = $pnum !== '0' ? DB::table('descript')
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
        $tops = DB::table('field')
            ->where('idkeyfield', '')->where('keyfield', 'catalog')
            ->where(fn($q) => $q->where('firma', $fid)->orWhere('constanta', '1'))
            ->orderBy('num')->get();
        $subs = DB::table('field')
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

        return [
            'comp'        => $comp,
            'descript'    => $descript,
            'priceGroups' => $priceGroups,
            'prices'      => $prices,
            'tops'        => $tops,
            'subs'        => $subs,
            'news'        => $news,
            'filterTags'  => $filterTags,
        ];
    }

    // ── save: insert or update comp + prices + descript ────────────────────────

    public static function saveGoods($pnum, $fid, $compData, $priceRows, $descData, $fotoMap)
    {
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
                    'tgroup' => $gid, 'pnum' => $pnum, 'firma' => $fid,
                ]));
            }
        }

        // ── Main comp data
        $compData = array_merge($compData, $fotoMap);

        if ($pnum === '' || $pnum === '0') {
            $compData['cod'] = date('dmHis') . rand(10, 99);
            $compData['dt'] = now();
            $pnum = (string)DB::table('comp')->insertGetId($compData);
        } else {
            DB::table('comp')->where('id', $pnum)->update($compData);
        }

        // ── Descript upsert
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

        DB::table('price')->where('cod', $cod)->update(['cod' => '']);
        DB::table('comp')->where('id', $id)->where('firma', $fid)->delete();

        return true;
    }
}
