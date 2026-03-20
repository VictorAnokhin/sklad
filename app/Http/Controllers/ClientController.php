<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * ClientController
 * Migrated from: client/index.php, client/run.php, client/run-firm.php,
 *                client/edit-firm.php, client/saldo_cl.php, client/show.php
 */
class ClientController extends Controller
{
    // ── List ──────────────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $fid = session('fid', '');
        $pos = (int)$request->input('pos', session('pos', 0));
        $pos2 = 20;

        $filters = [
            'search' => $request->input('search', session('cl_search', '')),
            'city' => $request->input('city', session('cl_city', '')),
            'idstatus' => $request->input('idstatus', session('cl_idstatus', '')),
            'phone' => $request->input('phone', session('cl_phone', '')),
        ];

        session([
            'pos' => $pos,
            'cl_search' => $filters['search'],
            'cl_city' => $filters['city'],
            'cl_idstatus' => $filters['idstatus'],
            'cl_phone' => $filters['phone'],
        ]);

        $result = User::userslist($fid, $filters, $pos, $pos2);
        $clients = $result['clients'];
        $total = $result['total'];
        $statuses = $result['statuses'];

        return view('client.index', compact('clients', 'total', 'pos', 'pos2', 'filters', 'statuses', 'fid'));
    }

    // ── Show / edit form ──────────────────────────────────────────────────────

    public function show(Request $request)
    {
        $id = $request->input('id', '0');
        $fid = session('fid', '');

        $result = User::showClient($id, $fid);
        $client = $result['client'];
        $statuses = $result['statuses'];

        session(['client1' => $id]);

        return view('client.show', compact('client', 'statuses', 'fid'));
    }

    // ── Save ──────────────────────────────────────────────────────────────────

    public function save(Request $request)
    {
        $fid = session('fid', '');
        $id = $request->input('id', '0');

        $data = [
            'name' => convert_to_base($request->input('name', '')),
            'secondname' => convert_to_base($request->input('secondname', '')),
            'fathername' => convert_to_base($request->input('fathername', '')),
            'orgname' => convert_to_base($request->input('orgname', '')),
            'name2' => convert_to_base($request->input('name2', '')),
            'kod1' => $request->input('kod1', ''),
            'phone' => preg_replace('/\D/', '', $request->input('phone', '')),
            'phone1' => preg_replace('/\D/', '', $request->input('phone1', '')),
            'city' => $request->input('city', ''),
            'region' => $request->input('region', ''),
            'poshta' => $request->input('poshta', ''),
            'idstatus' => (int)$request->input('idstatus', 1),
            'top' => (int)$request->input('top', 1),
            'bonus' => (float)$request->input('bonus', 0),
            'hbd' => convert_to_base($request->input('hbd', '')),
            'firma' => $fid,
        ];

        $id = User::edit($id, $data);

        session(['client1' => $id]);
        return redirect()->route('client.show', ['id' => $id])->with('success', 'Збережено');
    }

    // ── Delete ────────────────────────────────────────────────────────────────

    public function destroy(Request $request)
    {
        $id = $request->input('id', '');
        $fid = session('fid', '');

        // Guard: has documents
        if (!User::deleteClient($id, $fid)) {
            return back()->withErrors(['delete' => 'Клієнт має документи, видалення неможливе']);
        }
        return redirect()->route('client.index');
    }

    // ── Saldo (balance) ───────────────────────────────────────────────────────

    public function saldo(Request $request)
    {
        $id = $request->input('id', session('client1', '0'));
        $fid = session('fid', '');

        return response()->json(\App\Models\Document::saldo($id, $fid));
    }

    // ── Save firm ─────────────────────────────────────────────────────────────

    public function saveFirm(Request $request)
    {
        $fid = session('fid', '');
        $id = $request->input('id', '0');

        $data = [
            'orgname' => convert_to_base($request->input('orgname', '')),
            'kod1' => $request->input('kod1', ''),
            'mfo' => $request->input('mfo', ''),
            'ras_schet' => $request->input('ras_schet', ''),
            'bank' => convert_to_base($request->input('bank', '')),
            'address' => convert_to_base($request->input('address', '')),
            'director' => convert_to_base($request->input('director', '')),
            'buh' => convert_to_base($request->input('buh', '')),
            'firma' => $fid,
        ];

        User::saveFirm($id, $data);

        return redirect()->back()->with('success', 'Збережено');
    }
}