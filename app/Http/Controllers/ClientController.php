<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
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
        $previousFilters = [
            'search' => session('cl_search', ''),
            'city' => session('cl_city', ''),
            'idstatus' => session('cl_idstatus', ''),
            'phone' => session('cl_phone', ''),
            'email' => session('cl_email', ''),
        ];
        $pos = (int)$request->input('pos', session('client_pos', 0));
        $pos2 = 20;

        $filters = [
            'search' => $request->input('search', $previousFilters['search']),
            'city' => $request->input('city', $previousFilters['city']),
            'idstatus' => $request->input('idstatus', $previousFilters['idstatus']),
            'phone' => $request->input('phone', $previousFilters['phone']),
            'email' => $request->input('email', $previousFilters['email']),
        ];

        $hasFilterInput = $request->hasAny(['search', 'city', 'idstatus', 'phone', 'email']);
        if ($hasFilterInput && $filters !== $previousFilters) {
            $pos = 0;
        }

        session([
            'client_pos' => $pos,
            'cl_search' => $filters['search'],
            'cl_city' => $filters['city'],
            'cl_idstatus' => $filters['idstatus'],
            'cl_phone' => $filters['phone'],
            'cl_email' => $filters['email'],
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
        $teamOnly = $request->boolean('team_only');

        $users = DB::table('users')
            ->where('firma', $fid)
            ->when($teamOnly, fn ($query) => $query->where('firmuser', '1'))
            ->where(function ($query) use ($q, $qBase) {
            $query->where('orgname', 'LIKE', "%{$qBase}%")
                ->orWhere('name', 'LIKE', "%{$qBase}%")
                ->orWhere('secondname', 'LIKE', "%{$qBase}%")
                ->orWhere('phone', 'LIKE', "%{$q}%");
            if (User::hasUsersColumn('email')) {
                $query->orWhereRaw('LOWER(email) LIKE ?', ['%' . mb_strtolower($qBase) . '%']);
            }
        })
            ->select('id', 'orgname', 'name', 'name2', 'secondname', 'phone', 'city', 'region', 'poshta', 'idstatus', 'balance')
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
            'region' => $u->region,
            'poshta' => $u->poshta,
            'idstatus' => $u->idstatus,
            'client_balance' => (float) ($u->balance ?? 0),
            ];
        });

        return response()->json($users);
    }

    // ── Check email uniqueness (Async) ────────────────────────────────────────

    public function checkEmail(Request $request)
    {
        $email = trim((string) $request->input('email'));
        $clientId = $request->input('client_id', '0');

        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return response()->json([
                'valid' => false,
                'message' => 'Некоректний формат email',
            ]);
        }

        $fid = session('fid', '');

        $query = DB::table('users')
            ->where('email', $email)
            ->where('firma', $fid);

        // Exclude current client when editing
        if ($clientId !== '0' && $clientId !== '') {
            $query->where('id', '!=', $clientId);
        }

        $exists = $query->exists();

        if ($exists) {
            return response()->json([
                'valid' => false,
                'message' => 'Клієнт з таким email вже існує',
            ]);
        }

        return response()->json([
            'valid' => true,
            'message' => 'Email доступний',
        ]);
    }

    // ── Quick-create client (AJAX from document.show modal) ──────────────────

    public function storeQuick(Request $request)
    {
        $fid = session('fid', '');
        $orgname = trim((string) ($request->input('orgname') ?? ''));
        $name = trim((string) ($request->input('name') ?? ''));
        $secondname = trim((string) ($request->input('secondname') ?? ''));
        $rawPhone = trim((string) ($request->input('phone') ?? ''));
        $phoneDigits = preg_replace('/\D/', '', $rawPhone);
        $phone = $phoneDigits !== '' ? '+' . $phoneDigits : '';
        $city = trim((string) ($request->input('city') ?? ''));
        $region = trim((string) ($request->input('region') ?? ''));
        $poshta = trim((string) ($request->input('poshta') ?? ''));
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

        $idParam = $request->input('id', '0');

        // Check for unique phone if provided
        if ($phone !== '') {
            $query = DB::table('users')
                ->where('firma', $fid)
                ->where(function ($query) use ($phone) {
                    $query->where('phone', $phone)
                          ->orWhere('phone1', $phone);
                });
            
            if ($idParam !== '0') {
                $query->where('id', '!=', $idParam);
            }

            $phoneExists = $query->exists();

            if ($phoneExists) {
                return response()->json([
                    'message' => 'Клієнт з таким телефоном вже існує',
                ], 422);
            }
        }

        $data = [
            'orgname' => $orgname,
            'name' => $name,
            'secondname' => $secondname,
            'phone' => $phone,
            'city' => $city,
            'region' => $region,
            'poshta' => $poshta,
            'firma' => $fid,
            'idstatus' => $idstatus,
            'ustype' => $idstatus,
            'top' => 1,
        ];

        if (\App\Models\User::hasUsersColumn('email') && $idParam === '0') {
            $emailBase = $phoneDigits !== '' ? $phoneDigits : ('quickclient' . now()->timestamp);
            $candidateEmail = $emailBase . '@local.client';
            $suffix = 1;

            while (DB::table('users')->where('email', $candidateEmail)->exists()) {
                $candidateEmail = $emailBase . '+' . $suffix . '@local.client';
                $suffix++;
            }

            $data['email'] = $candidateEmail;
        }

        $id = User::edit($idParam, $data, false);

        $user = DB::table('users')->where('id', $id)->first();

        return response()->json($user);
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
        $clientTypes = DB::table('conf')
            ->where('type', 'tgroup')
            ->where('firma', $fid)
            ->orderBy('name')
            ->get();

        session(['client1' => $id]);

        $kycPhotos = $client ? $this->buildKycPhotoCards($client) : [];

        return view('client.show', compact('client', 'statuses', 'clientTypes', 'fid', 'kycPhotos'));
    }

    public function kycPhoto(string $id, string $type)
    {
        $fid = session('fid', '');
        $client = DB::table('users')
            ->where('id', $id)
            ->where('firma', $fid)
            ->first();

        if (! $client) {
            abort(404);
        }

        $map = $this->kycPhotoMap();
        if (! isset($map[$type])) {
            abort(404);
        }

        $path = $this->resolveKycPhotoPath($client, $map[$type]['column'], $map[$type]['fallback']);
        if ($path === '') {
            abort(404);
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return redirect()->away($path);
        }

        $disk = Storage::disk('local');
        if (! $disk->exists($path)) {
            abort(404);
        }

        return response()->file($disk->path($path), [
            'Content-Type' => $this->clientValue($client, $map[$type]['mime']) ?: ($disk->mimeType($path) ?: 'image/jpeg'),
        ]);
    }

    // ── Save ──────────────────────────────────────────────────────────────────

    public function save(Request $request)
    {
        $fid = session('fid', '');
        $id = $request->input('id', '0');
        $stringValue = static fn ($value): string => trim((string) ($value ?? ''));

        try {
            $phoneDigits = preg_replace('/\D/', '', $request->input('phone', ''));
            $phone1Digits = preg_replace('/\D/', '', $request->input('phone1', ''));
            
            $request->validate([
                'email' => [
                    'required',
                    'email',
                    'max:255',
                    Rule::unique('users', 'email')
                        ->where('firma', $fid)
                        ->ignore($id === '0' ? null : $id),
                ],
                'phone' => [
                    'nullable',
                    'string',
                    'max:50',
                    Rule::unique('users', 'phone')
                        ->where('firma', $fid)
                        ->ignore($id === '0' ? null : $id),
                ],
                'phone1' => [
                    'nullable',
                    'string',
                    'max:50',
                    Rule::unique('users', 'phone1')
                        ->where('firma', $fid)
                        ->ignore($id === '0' ? null : $id),
                ],
                'login' => array_filter([
                    User::hasUsersColumn('login') ? 'nullable' : null,
                    User::hasUsersColumn('login') ? 'string' : null,
                    User::hasUsersColumn('login') ? 'max:255' : null,
                    User::hasUsersColumn('login') ? Rule::unique('users', 'login')->ignore($id === '0' ? null : $id) : null,
                ]),
            ], [
                'phone.unique' => 'Клієнт з таким телефоном вже існує',
                'phone1.unique' => 'Клієнт з таким додатковим телефоном вже існує',
            ]);

            $data = [
                'login' => $stringValue($request->input('login', '')),
                'name' => $stringValue($request->input('name', '')),
                'secondname' => $stringValue($request->input('secondname', '')),
                'fathername' => $stringValue($request->input('fathername', '')),
                'orgname' => $stringValue($request->input('orgname', '')),
                'name2' => $stringValue($request->input('name2', '')),
                'kod1' => $stringValue($request->input('kod1', '')),
                'phone' => preg_replace('/\D/', '', $request->input('phone', '')),
                'phone1' => preg_replace('/\D/', '', $request->input('phone1', '')),
                'email' => $stringValue($request->input('email', '')),
                'city' => $stringValue($request->input('city', '')),
                'region' => $stringValue($request->input('region', '')),
                'poshta' => $stringValue($request->input('poshta', '')),
                'idstatus' => (int)$request->input('idstatus', 1),
                'ustype' => (int)$request->input('idstatus', 1),
                'tgroup' => (int)$request->input('tgroup', 0),
                'top' => (int)$request->input('top', 1),
                'bonus' => (float)$request->input('bonus', 0),
                'hbd' => $stringValue($request->input('hbd', '')),
                'firma' => $fid,
            ];

            $password = trim((string) $request->input('pass', ''));
            if ($password !== '') {
                $hash = Hash::make($password);
                $data['pass'] = $hash;
                $data['password'] = $hash;
            }

            $id = User::edit($id, $data, false);

            session(['client1' => $id]);
            return redirect()->route('client.show', ['id' => $id])->with('success', 'Збережено');
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            return back()->withErrors(['save' => 'Помилка збереження: ' . $e->getMessage()])->withInput();
        }
    }

    private function buildKycPhotoCards(object $client): array
    {
        $cards = [];
        foreach ($this->kycPhotoMap() as $type => $meta) {
            $path = $this->resolveKycPhotoPath($client, $meta['column'], $meta['fallback']);
            $cards[] = [
                'type' => $type,
                'label' => $meta['label'],
                'column' => $meta['column'],
                'path' => $path,
                'file_name' => $this->clientValue($client, $meta['name']) ?: ($path !== '' ? basename($path) : ''),
                'file_size' => (int) $this->clientValue($client, $meta['size'], 0),
                'uploaded_at' => $this->clientValue($client, $meta['uploaded_at']),
                'url' => $path !== '' ? route('client.kycPhoto', ['id' => $client->id, 'type' => $type]) : null,
            ];
        }

        return $cards;
    }

    private function kycPhotoMap(): array
    {
        return [
            'passport' => [
                'label' => 'Фото паспорта',
                'column' => 'foto1',
                'fallback' => 'kyc_passport_file_path',
                'name' => 'kyc_passport_file_name',
                'mime' => 'kyc_passport_file_mime',
                'size' => 'kyc_passport_file_size',
                'uploaded_at' => 'kyc_passport_uploaded_at',
            ],
            'selfie' => [
                'label' => 'Лицо с паспортом',
                'column' => 'foto2',
                'fallback' => 'kyc_selfie_file_path',
                'name' => 'kyc_selfie_file_name',
                'mime' => 'kyc_selfie_file_mime',
                'size' => 'kyc_selfie_file_size',
                'uploaded_at' => 'kyc_selfie_uploaded_at',
            ],
            'liveness' => [
                'label' => 'Liveness-селфи',
                'column' => 'foto3',
                'fallback' => 'kyc_liveness_file_path',
                'name' => 'kyc_liveness_file_name',
                'mime' => 'kyc_liveness_file_mime',
                'size' => 'kyc_liveness_file_size',
                'uploaded_at' => 'kyc_liveness_uploaded_at',
            ],
        ];
    }

    private function resolveKycPhotoPath(object $client, string $photoColumn, string $fallbackColumn): string
    {
        $photoPath = trim((string) $this->clientValue($client, $photoColumn, ''));
        if ($photoPath !== '') {
            if (str_starts_with($photoPath, 'http://') || str_starts_with($photoPath, 'https://') || Storage::disk('local')->exists($photoPath)) {
                return $photoPath;
            }
        }

        $fallbackPath = trim((string) $this->clientValue($client, $fallbackColumn, ''));
        if ($fallbackPath !== '') {
            if (str_starts_with($fallbackPath, 'http://') || str_starts_with($fallbackPath, 'https://') || Storage::disk('local')->exists($fallbackPath)) {
                return $fallbackPath;
            }
        }

        return '';
    }

    private function clientValue(object $client, string $column, mixed $default = null): mixed
    {
        return property_exists($client, $column) ? $client->{$column} : $default;
    }

    // ── Delete ────────────────────────────────────────────────────────────────

    public function destroy(Request $request)
    {
        $id = $request->input('id', '');
        $fid = session('fid', '');

        // Detailed check: what's blocking deletion
        $documentCount1 = DB::table('document')->where('client1', $id)->count();
        $documentCount2 = DB::table('document')->where('client2', $id)->count();
        $zDocumentCount1 = DB::table('z_document')->where('client1', $id)->count();
        $zDocumentCount2 = DB::table('z_document')->where('client2', $id)->count();
        
        $totalDocuments = $documentCount1 + $documentCount2;
        $totalZDocuments = $zDocumentCount1 + $zDocumentCount2;
        
        if ($totalDocuments > 0 || $totalZDocuments > 0) {
            $message = 'Клієнт має документи, видалення неможливе. ';
            $details = [];
            if ($totalDocuments > 0) {
                $parts = [];
                if ($documentCount1 > 0) $parts[] = "client1: {$documentCount1}";
                if ($documentCount2 > 0) $parts[] = "client2: {$documentCount2}";
                $details[] = "document (" . implode(', ', $parts) . ")";
            }
            if ($totalZDocuments > 0) {
                $parts = [];
                if ($zDocumentCount1 > 0) $parts[] = "client1: {$zDocumentCount1}";
                if ($zDocumentCount2 > 0) $parts[] = "client2: {$zDocumentCount2}";
                $details[] = "z_document (" . implode(', ', $parts) . ")";
            }
            $message .= '(' . implode('; ', $details) . ')';
            return back()->withErrors(['delete' => $message]);
        }

        // Check if client exists and belongs to current firma
        $client = DB::table('users')->where('id', $id)->where('firma', $fid)->first();
        if (!$client) {
            return back()->withErrors(['delete' => 'Клієнта не знайдено або він не належить поточній компанії']);
        }

        User::deleteClient($id, $fid);
        return redirect()->route('client.index')->with('success', 'Клієнта видалено');
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
