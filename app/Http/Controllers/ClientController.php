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
        $fid      = session('fid', '');
        $idstatus = (int)session('idstatus', 0);
        $pos      = (int)$request->input('pos', session('pos', 0));
        $pos2     = 20;
        $search   = $request->input('search', session('search', ''));

        session(['pos' => $pos, 'search' => $search]);

        $query = DB::table('users')
            ->where('firma', $fid)
            ->where('top', '>', 0);

        if ($search !== '') {
            $like = '%' . $search . '%';
            $query->where(function ($q) use ($like) {
                $q->where('orgname',    'like', $like)
                  ->orWhere('name',     'like', $like)
                  ->orWhere('secondname','like', $like)
                  ->orWhere('phone',    'like', $like)
                  ->orWhere('city',     'like', $like)
                  ->orWhere('name2',    'like', $like);
            });
        }

        $total   = $query->count();
        $clients = $query->orderByDesc('top')->orderBy('id')->offset($pos)->limit($pos2)->get();

        return view('client.index', compact('clients', 'total', 'pos', 'pos2', 'search', 'fid'));
    }

    // ── Show / edit form ──────────────────────────────────────────────────────

    public function show(Request $request)
    {
        $id  = $request->input('id', '0');
        $fid = session('fid', '');

        $client = $id !== '0' ? DB::table('users')->where('id', $id)->first() : null;

        // Selects needed for form
        $cities   = DB::table('town')->orderBy('name')->get(['id', 'name']);
        $statuses = DB::table('conf')
            ->where('type', 'idstatus')->where('firma', $fid)->orderBy('name')->get();

        session(['client1' => $id]);

        return view('client.show', compact('client', 'cities', 'statuses', 'fid'));
    }

    // ── Save ──────────────────────────────────────────────────────────────────

    public function save(Request $request)
    {
        $fid = session('fid', '');
        $id  = $request->input('id', '0');

        $data = [
            'name'        => convert_to_base($request->input('name', '')),
            'secondname'  => convert_to_base($request->input('secondname', '')),
            'fathername'  => convert_to_base($request->input('fathername', '')),
            'orgname'     => convert_to_base($request->input('orgname', '')),
            'name2'       => convert_to_base($request->input('name2', '')),
            'kod1'        => $request->input('kod1', ''),
            'phone'       => preg_replace('/\D/', '', $request->input('phone', '')),
            'phone1'      => preg_replace('/\D/', '', $request->input('phone1', '')),
            'city'        => $request->input('city', ''),
            'region'      => $request->input('region', ''),
            'poshta'      => $request->input('poshta', ''),
            'idstatus'    => (int)$request->input('idstatus', 1),
            'top'         => (int)$request->input('top', 1),
            'bonus'       => (float)$request->input('bonus', 0),
            'hbd'         => convert_to_base($request->input('hbd', '')),
            'firma'       => $fid,
        ];

        if ($id === '0' || $id === '') {
            // New client: generate login/pass
            $phone = $data['phone'];
            $data['login'] = $phone ?: uniqid('cl_');
            $data['pass']  = Hash::make($phone ?: str_pad((string)rand(1000, 9999), 4));
            $id = (string)DB::table('users')->insertGetId($data);
        } else {
            DB::table('users')->where('id', $id)->update($data);
        }

        session(['client1' => $id]);
        return redirect()->route('client.show', ['id' => $id])->with('success', 'Збережено');
    }

    // ── Delete ────────────────────────────────────────────────────────────────

    public function destroy(Request $request)
    {
        $id  = $request->input('id', '');
        $fid = session('fid', '');

        // Guard: has documents
        $hasDoc = DB::table('document')->where('client1', $id)->exists()
               || DB::table('z_document')->where('client1', $id)->exists();

        if ($hasDoc) {
            return back()->withErrors(['delete' => 'Клієнт має документи, видалення неможливе']);
        }

        DB::table('users')->where('id', $id)->where('firma', $fid)->delete();
        return redirect()->route('client.index');
    }

    // ── Saldo (balance) ───────────────────────────────────────────────────────

    public function saldo(Request $request)
    {
        $id  = $request->input('id', session('client1', '0'));
        $fid = session('fid', '');

        $zout = DB::table('document')
            ->where('client1', $id)->where('firma', $fid)->where('type', 'ZOUT')
            ->sum('summa');
        $paid = DB::table('z_document')
            ->where('client1', $id)->where('firma', $fid)->where('type', 'PO')
            ->where('provodka', 1)->sum('summa');

        return response()->json([
            'debt'    => (float)$zout,
            'paid'    => (float)$paid,
            'balance' => (float)$paid - (float)$zout,
        ]);
    }

    // ── Save firm ─────────────────────────────────────────────────────────────

    public function saveFirm(Request $request)
    {
        $fid = session('fid', '');
        $id  = $request->input('id', '0');

        $data = [
            'orgname'  => convert_to_base($request->input('orgname', '')),
            'kod1'     => $request->input('kod1', ''),
            'mfo'      => $request->input('mfo', ''),
            'ras_schet'=> $request->input('ras_schet', ''),
            'bank'     => convert_to_base($request->input('bank', '')),
            'address'  => convert_to_base($request->input('address', '')),
            'director' => convert_to_base($request->input('director', '')),
            'buh'      => convert_to_base($request->input('buh', '')),
            'firma'    => $fid,
        ];

        $exists = DB::table('firm')->where('id', $id)->exists();
        if ($exists) {
            DB::table('firm')->where('id', $id)->update($data);
        } else {
            DB::table('firm')->insert($data);
        }

        return redirect()->back()->with('success', 'Збережено');
    }
}
