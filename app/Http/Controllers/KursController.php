<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * KursController — currency rate module (migrated from kurs/)
 */
class KursController extends Controller
{
    public function index(Request $request)
    {
        $fid  = session('fid', '');
        $kurs = DB::table('kurs')->where('firma', $fid)->orderByDesc('data')->limit(30)->get();
        return view('money.kurs', compact('kurs', 'fid'));
    }

    public function save(Request $request)
    {
        $fid  = session('fid', '');
        $data = [
            'usd'   => (float)$request->input('usd', 0),
            'eur'   => (float)$request->input('eur', 0),
            'data'  => date('Y-m-d'),
            'firma' => $fid,
        ];
        DB::table('kurs')->insert($data);
        return redirect()->back()->with('success', 'Збережено');
    }
}
