<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

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

    // ── Search (Async) ────────────────────────────────────────────────────────

    public function search(Request $request)
    {
        $q = $request->input('q');
        if (!$q || mb_strlen($q) < 2)
            return response()->json([]);

        $fid = session('fid', '');
        $qBase = $q;

        $users = DB::table('users')
            ->where('firma', $fid)
            ->where(function ($query) use ($q, $qBase) {
            $query->where('orgname', 'LIKE', "%{$qBase}%")
                ->orWhere('name', 'LIKE', "%{$qBase}%")
                ->orWhere('secondname', 'LIKE', "%{$qBase}%")
                ->orWhere('phone', 'LIKE', "%{$q}%");
        })
            ->select('id', 'orgname', 'name', 'name2', 'secondname', 'phone', 'city')
            ->limit(20)
            ->get()
            ->map(function ($u) {
            return [
            'id' => $u->id,
            'orgname' => $u->orgname,
            'name' => $u->name,
            'name2' => $u->name2,
            'secondname' => $u->secondname,
            'phone' => $u->phone,
            'city' => $u->city,
            ];
        });

        return response()->json($users);
    }

    // ── Quick-create client (AJAX from document.show modal) ──────────────────

    public function storeQuick(Request $request)
    {
        $fid = session('fid', '');
        $name = trim((string) ($request->input('name') ?? ''));
        $secondname = trim((string) ($request->input('secondname') ?? ''));
        $rawPhone = trim((string) ($request->input('phone') ?? ''));
        $phoneDigits = preg_replace('/\D/', '', $rawPhone);
        $phone = $phoneDigits !== '' ? '+' . $phoneDigits : '';
        $city = trim((string) ($request->input('city') ?? ''));
        $idstatus = (int) $request->input('idstatus', 0);

        if ($name === '' && $secondname === '' && $phone === '') {
            return response()->json([
                'message' => 'Заповніть хоча б одне поле: імʼя, прізвище або телефон.',
            ], 422);
        }

        if ($idstatus <= 0) {
            return response()->json([
                'message' => 'Оберіть статус клієнта.',
            ], 422);
        }

        $data = [
            'name' => $name,
            'secondname' => $secondname,
            'phone' => $phone,
            'city' => $city,
            'firma' => $fid,
            'idstatus' => $idstatus,
            'ustype' => $idstatus,
            'top' => 1,
        ];

        if (\App\Models\User::hasUsersColumn('email')) {
            $emailBase = $phoneDigits !== '' ? $phoneDigits : ('quickclient' . now()->timestamp);
            $candidateEmail = $emailBase . '@local.client';
            $suffix = 1;

            while (DB::table('users')->where('email', $candidateEmail)->exists()) {
                $candidateEmail = $emailBase . '+' . $suffix . '@local.client';
                $suffix++;
            }

            $data['email'] = $candidateEmail;
        }

        $id = User::edit('0', $data);

        return response()->json([
            'id' => $id,
            'name' => $name,
            'secondname' => $secondname,
            'phone' => $phone,
            'city' => $city,
            'idstatus' => $idstatus,
        ]);
    }

    // ── Show / edit form ──────────────────────────────────────────────────────

    public function show(Request $request)
    {
        $id = $request->input('id', '0');
        $fid = session('fid', '');

        $result = User::showClient($id, $fid);
        $client = $result['client'];
        $statuses = DB::table('conf')
            ->where('type', 'tclient')
            ->where('firma', $fid)
            ->orderBy('name')
            ->get();

        session(['client1' => $id]);

        return view('client.show', compact('client', 'statuses', 'fid'));
    }

    // ── Save ──────────────────────────────────────────────────────────────────

    public function save(Request $request)
    {
        $fid = session('fid', '');
        $id = $request->input('id', '0');

        $request->validate([
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($id === '0' ? null : $id),
            ],
            'login' => array_filter([
                User::hasUsersColumn('login') ? 'nullable' : null,
                User::hasUsersColumn('login') ? 'string' : null,
                User::hasUsersColumn('login') ? 'max:255' : null,
                User::hasUsersColumn('login') ? Rule::unique('users', 'login')->ignore($id === '0' ? null : $id) : null,
            ]),
        ]);

        $data = [
            'login' => $request->input('login', ''),
            'name' => $request->input('name', ''),
            'secondname' => $request->input('secondname', ''),
            'fathername' => $request->input('fathername', ''),
            'orgname' => $request->input('orgname', ''),
            'name2' => $request->input('name2', ''),
            'kod1' => $request->input('kod1', ''),
            'phone' => preg_replace('/\D/', '', $request->input('phone', '')),
            'phone1' => preg_replace('/\D/', '', $request->input('phone1', '')),
            'email' => trim((string) $request->input('email', '')),
            'city' => $request->input('city', ''),
            'region' => $request->input('region', ''),
            'poshta' => $request->input('poshta', ''),
            'idstatus' => (int)$request->input('idstatus', 1),
            'ustype' => (int)$request->input('idstatus', 1),
            'top' => (int)$request->input('top', 1),
            'bonus' => (float)$request->input('bonus', 0),
            'hbd' => $request->input('hbd', ''),
            'firma' => $fid,
        ];

        $password = trim((string) $request->input('pass', ''));
        if ($password !== '') {
            $hash = Hash::make($password);
            $data['pass'] = $hash;
            $data['password'] = $hash;
        }

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

    public function orders($id)
    {
        $fid = session('fid', '');
        $year = session('year', date('Y'));

        $statusMap = DB::table('conf')
            ->where('type', 'status')
            ->where('firma', $fid)
            ->get(['id', 'name', 'color'])
            ->keyBy('id');

        $orders = DB::table('document')
            ->where('firma', $fid)
            ->where('client1', $id)
            ->where('type', 'ZOUT')
            ->orderByDesc('id')
            ->limit(50)
            ->get(['id', 'num', 'data', 'status', 'summa']);

        $payload = $orders->map(function ($order) use ($statusMap, $year) {
            $status = $statusMap->get($order->status);

            return [
                'id' => $order->id,
                'num' => (string) $order->num,
                'data' => (string) $order->data,
                'summa' => number_format((float) ($order->summa ?? 0), 2, '.', ''),
                'status_name' => $status->name ?? 'Новий',
                'status_color' => $status->color ?? '',
                'link_url' => route('document.show', [
                    'doc_id' => $order->id,
                    'num' => $order->num,
                    'year' => $year,
                    'doc' => 'ZOUT',
                ]),
            ];
        })->values();

        return response()->json($payload);
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
            'orgname' => $request->input('orgname', ''),
            'kod1' => $request->input('kod1', ''),
            'mfo' => $request->input('mfo', ''),
            'ras_schet' => $request->input('ras_schet', ''),
            'bank' => $request->input('bank', ''),
            'address' => $request->input('address', ''),
            'director' => $request->input('director', ''),
            'buh' => $request->input('buh', ''),
            'firma' => $fid,
        ];

        User::saveFirm($id, $data);

        return redirect()->back()->with('success', 'Збережено');
    }
}
