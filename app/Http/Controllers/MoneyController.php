<?php

namespace App\Http\Controllers;

use App\Models\Money;
use Illuminate\Http\Request;

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

        $result = Money::init($fid);
        $kassas = $result['kassas'];
        $reestr = $result['reestr'];

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

        Money::saveMoney($id, $fid, $data);

        return redirect()->back()->with('success', 'Збережено');
    }
}
