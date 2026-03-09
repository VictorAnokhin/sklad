<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * MoneyController — migrated from money/ module
 * Handles cash register (kassa) balances and register (reestr) entries.
 */
class MoneyController extends Controller
{
    public function index(Request $request)
    {
        $fid = session('fid', '');
        $pos = (int)$request->input('pos', 0);

        $kassas  = DB::table('kassa')->where('firma', $fid)->get();
        $reestr  = DB::table('reestr')->where('firma', $fid)->orderByDesc('id')->get();

        return view('money.index', compact('kassas', 'reestr', 'pos', 'fid'));
    }

    public function save(Request $request)
    {
        $fid = session('fid', '');
        $id  = $request->input('id', '');

        $data = [
            'name'    => convert_to_base($request->input('name', '')),
            'balance' => (float)$request->input('balance', 0),
            'firma'   => $fid,
        ];

        if ($id === '') {
            DB::table('kassa')->insert($data);
        } else {
            DB::table('kassa')->where('id', $id)->update($data);
        }

        return redirect()->back()->with('success', 'Збережено');
    }
}
