<?php

namespace App\Http\Controllers;

use App\Models\Kurs;
use Illuminate\Http\Request;

/**
 * KursController — currency rate module (migrated from kurs/)
 */
class KursController extends Controller
{
    public function index(Request $request)
    {
        $fid    = session('fid', '');
        $result = Kurs::init($fid);
        $kurs   = $result['kurs'];

        return view('money.kurs', compact('kurs', 'fid'));
    }

    public function save(Request $request)
    {
        $fid  = session('fid', '');
        $data = [
            'usd'  => (float)$request->input('usd', 0),
            'eur'  => (float)$request->input('eur', 0),
            'data' => date('Y-m-d'),
        ];

        Kurs::saveKurs($fid, $data);

        return redirect()->back()->with('success', 'Збережено');
    }
}
