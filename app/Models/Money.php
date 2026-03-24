<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Money — cash documents from z_document
 * PO = Отримання грошей (приход)
 * RO = Видача грошей (витрата)
 */
class Money extends Model
{
    protected $table = 'z_document';
    public $timestamps = false;
    protected $guarded = [];

    // ── init: дані для index ──────────────────────────────────────────────────

    public static function init($fid, $pos = 0)
    {
        $sumPO = DB::table('z_document')
            ->where('firma', $fid)
            ->where('type', 'PO')
            ->sum('summa');

        $sumRO = DB::table('z_document')
            ->where('firma', $fid)
            ->where('type', 'RO')
            ->sum('summa');

        $total = DB::table('z_document')
            ->where('firma', $fid)
            ->whereIn('type', ['PO', 'RO'])
            ->count();

        $documents = DB::table('z_document as d')
            ->leftJoin('users as u', 'u.id', '=', 'd.client1')
            ->where('d.firma', $fid)
            ->whereIn('d.type', ['PO', 'RO'])
            ->orderByDesc('d.id')
            ->offset($pos)
            ->limit(30)
            ->get([
                'd.id', 'd.num', 'd.type', 'd.data', 'd.time',
                'd.summa', 'd.content', 'd.money', 'd.provodka',
                'u.name', 'u.name2', 'u.secondname', 'u.orgname', 'u.phone',
            ]);

        $kassasMap = DB::table('conf')
            ->where('type', 'oplata')
            ->pluck('name', 'name');

        return compact('documents', 'sumPO', 'sumRO', 'total', 'kassasMap');
    }

    // ── find: один документ + дані клієнта ───────────────────────────────────

    public static function find($id, $fid)
    {
        return DB::table('z_document as d')
            ->leftJoin('users as u', 'u.id', '=', 'd.client1')
            ->where('d.id', $id)
            ->where('d.firma', $fid)
            ->whereIn('d.type', ['PO', 'RO'])
            ->first([
                'd.id', 'd.num', 'd.type', 'd.data', 'd.time',
                'd.summa', 'd.content', 'd.money', 'd.provodka', 'd.client1',
                'u.name', 'u.name2', 'u.secondname', 'u.orgname', 'u.phone', 'u.city',
            ]);
    }

    // ── emptyDocument: порожній об'єкт для нового документа ──────────────────

    public static function emptyDocument($type = 'PO')
    {
        return (object)[
            'id'         => 0,
            'num'        => '',
            'type'       => in_array($type, ['PO', 'RO']) ? $type : 'PO',
            'data'       => date('d-m-Y'),
            'time'       => '',
            'summa'      => 0,
            'content'    => '',
            'money'      => '',
            'provodka'   => 0,
            'client1'    => '',
            'name'       => '',
            'name2'      => '',
            'secondname' => '',
            'orgname'    => '',
            'phone'      => '',
            'city'       => '',
        ];
    }

    // ── kassas: список кас з conf ─────────────────────────────────────────────

    public static function kassas()
    {
        return DB::table('conf')
            ->where('type', 'oplata')
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    // ── save: insert або update ───────────────────────────────────────────────

    public static function saveDocument($id, $fid, $data)
    {
        $data['firma'] = $fid;

        if ($id === 0) {
            $maxNum = DB::table('z_document')
                ->where('firma', $fid)
                ->where('type', $data['type'])
                ->max(DB::raw('CAST(num AS UNSIGNED)'));

            $data['num']  = $maxNum ? (int)$maxNum + 1 : 1;
            $data['time'] = date('H:i:s');

            DB::table('z_document')->insert($data);
        } else {
            DB::table('z_document')
                ->where('id', $id)
                ->where('firma', $fid)
                ->update($data);
        }
    }

    // ── delete ────────────────────────────────────────────────────────────────

    public static function deleteDocument($id, $fid)
    {
        DB::table('z_document')
            ->where('id', $id)
            ->where('firma', $fid)
            ->whereIn('type', ['PO', 'RO'])
            ->delete();
    }
}
