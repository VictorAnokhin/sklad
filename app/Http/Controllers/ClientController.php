<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Document;
use App\Models\GarageVehicle;
use App\Models\Project;
use App\Services\AutoRiaVehicleCheckService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Throwable;

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
        $projects = Project::query()
            ->orderBy('name')
            ->orderBy('id')
            ->get(['id', 'name']);

        session(['client1' => $id]);

        $kycPhotos = $client ? $this->buildKycPhotoCards($client) : [];
        $garageVehicles = $client ? $this->clientGarageVehicles($client) : collect();

        return view('client.show', compact('client', 'statuses', 'clientTypes', 'projects', 'fid', 'kycPhotos', 'garageVehicles'));
    }

    public function groups(Request $request)
    {
        $fid = session('fid', '');
        $search = trim((string) $request->input('q', ''));

        $query = $this->clientGroupsQuery($fid);

        if ($search !== '') {
            $query->where('name', 'like', '%' . $search . '%');
        }

        $items = $query
            ->orderBy('name')
            ->get()
            ->map(fn ($group) => $this->mapClientGroup($group));

        return response()->json(['items' => $items]);
    }

    public function groupStore(Request $request)
    {
        $fid = session('fid', '');
        $payload = $this->validateClientGroup($request);

        $id = DB::table('conf')->insertGetId([
            'type' => 'tgroup',
            'name' => $payload['name'],
            'color' => '',
            'status' => $payload['status'],
            'firma' => $fid,
            'constanta' => '0',
            'vision' => '1',
            'hide' => '0',
        ]);

        $group = $this->clientGroupsQuery($fid)->where('id', $id)->first();

        return response()->json([
            'success' => true,
            'item' => $this->mapClientGroup($group),
        ], 201);
    }

    public function groupUpdate(Request $request, string $id)
    {
        $fid = session('fid', '');
        $group = $this->clientGroupsQuery($fid)->where('id', $id)->first();

        if (! $group) {
            return response()->json(['success' => false, 'message' => 'Группа не найдена.'], 404);
        }

        $payload = $this->validateClientGroup($request);

        DB::table('conf')
            ->where('id', $group->id)
            ->where('type', 'tgroup')
            ->where('firma', $fid)
            ->update([
                'name' => $payload['name'],
                'status' => $payload['status'],
            ]);

        $updated = $this->clientGroupsQuery($fid)->where('id', $id)->first();

        return response()->json([
            'success' => true,
            'item' => $this->mapClientGroup($updated),
        ]);
    }

    public function groupDestroy(string $id)
    {
        $fid = session('fid', '');
        $group = $this->clientGroupsQuery($fid)->where('id', $id)->first();

        if (! $group) {
            return response()->json(['success' => false, 'message' => 'Группа не найдена.'], 404);
        }

        $isUsed = DB::table('users')
            ->where('firma', $fid)
            ->where('tgroup', $group->id)
            ->exists();

        if ($isUsed) {
            return response()->json([
                'success' => false,
                'message' => 'Нельзя удалить группу, которая назначена клиентам.',
            ], 422);
        }

        DB::table('conf')
            ->where('id', $group->id)
            ->where('type', 'tgroup')
            ->where('firma', $fid)
            ->delete();

        return response()->json(['success' => true]);
    }

    private function clientGroupsQuery(string $fid)
    {
        return DB::table('conf')
            ->where('type', 'tgroup')
            ->where('firma', $fid);
    }

    private function validateClientGroup(Request $request): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(['0', '1', 0, 1])],
        ]);

        $name = trim((string) $validated['name']);

        if ($name === '') {
            throw ValidationException::withMessages([
                'name' => 'Введите название группы.',
            ]);
        }

        return [
            'name' => $name,
            'status' => (string) ($validated['status'] ?? '0'),
        ];
    }

    private function mapClientGroup(object $group): array
    {
        $status = (string) ($group->status ?? '0');

        return [
            'id' => (int) $group->id,
            'name' => (string) ($group->name ?? ''),
            'status' => $status,
            'status_label' => $status === '1' ? 'Розничная' : 'Доп. группа',
        ];
    }

    public function garageLookup(Request $request, AutoRiaVehicleCheckService $autoRia)
    {
        $validated = $request->validate([
            'client_id' => ['required'],
            'vehicle_info' => ['required', 'string', 'max:64'],
        ]);

        $fid = session('fid', '');
        $client = DB::table('users')
            ->where('id', $validated['client_id'])
            ->where('firma', $fid)
            ->first();

        if (! $client) {
            return response()->json(['success' => false, 'message' => 'Клиент не найден.'], 404);
        }

        $email = $this->clientEmail($client);
        if ($email === null) {
            return response()->json([
                'success' => false,
                'message' => 'Для сохранения авто нужен email клиента.',
            ], 422);
        }

        try {
            $langId = in_array((string) app()->getLocale(), ['ua', 'uk'], true) ? 4 : 2;
            $check = $autoRia->check((string) $validated['vehicle_info'], $langId);
            $payload = is_array($check['data']) ? $check['data'] : ['raw' => $check['data']];
            $snapshot = $autoRia->extractSnapshot($payload, $check['vehicle_info'], $check['input_type']);

            $vehicle = GarageVehicle::query()->updateOrCreate(
                [
                    'email' => $email,
                    'input_value' => $check['vehicle_info'],
                ],
                [
                    'user_id' => $client->id,
                    'fid' => is_numeric($fid) ? (int) $fid : null,
                    'vehicle_number' => $snapshot['vehicle_number'],
                    'vin' => $snapshot['vin'],
                    'input_type' => $check['input_type'],
                    'title' => $snapshot['title'],
                    'photo_url' => $snapshot['photo_url'],
                    'adv_link' => $snapshot['adv_link'],
                    'characteristics' => $snapshot['characteristics'],
                    'vehicle_price' => $snapshot['vehicle_price'],
                    'autoria_payload' => $payload,
                    'autoria_status' => $check['status'],
                    'checked_at' => now(),
                ]
            );

            return response()->json([
                'success' => $check['success'],
                'message' => $check['success'] ? 'Авто сохранено в гараже.' : 'Auto.RIA вернул ответ с ошибкой.',
                'item' => $this->formatGarageVehicle($vehicle->refresh()),
            ], $check['success'] ? 200 : 502);
        } catch (InvalidArgumentException $error) {
            return response()->json(['success' => false, 'message' => $error->getMessage()], 422);
        } catch (Throwable $error) {
            Log::warning('Client garage lookup failed', [
                'client_id' => $client->id,
                'vehicle_info' => $validated['vehicle_info'],
                'message' => $error->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Не удалось получить данные Auto.RIA.',
                'error' => $error->getMessage(),
            ], 502);
        }
    }

    public function garageUpdate(Request $request)
    {
        $validated = $request->validate([
            'client_id' => ['required'],
            'vehicle_id' => ['required'],
            'brand' => ['nullable', 'string', 'max:120'],
            'model' => ['nullable', 'string', 'max:120'],
            'color' => ['nullable', 'string', 'max:80'],
            'year' => ['nullable', 'integer', 'min:1900', 'max:2100'],
            'vin' => ['nullable', 'string', 'max:32'],
            'description' => ['nullable', 'string', 'max:5000'],
            'vehicle_price' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'garage_photos' => ['nullable', 'array', 'max:5'],
            'garage_photos.*' => ['nullable', 'string', 'max:500'],
            'garage_photo_files' => ['nullable', 'array', 'max:5'],
            'garage_photo_files.*' => ['nullable', 'image', 'max:5120'],
        ]);

        $fid = session('fid', '');
        $client = DB::table('users')
            ->where('id', $validated['client_id'])
            ->where('firma', $fid)
            ->first();

        if (! $client) {
            return response()->json(['success' => false, 'message' => 'Клиент не найден.'], 404);
        }

        $vehicle = GarageVehicle::query()
            ->where('id', $validated['vehicle_id'])
            ->where(function ($query) use ($client) {
                $query->where('user_id', $client->id);
                if ($email = $this->clientEmail($client)) {
                    $query->orWhereRaw('LOWER(TRIM(email)) = ?', [mb_strtolower($email)]);
                }
            })
            ->first();

        if (! $vehicle) {
            return response()->json(['success' => false, 'message' => 'Авто не найдено в гараже клиента.'], 404);
        }

        $photos = array_values($validated['garage_photos'] ?? []);
        $photos = array_pad(array_slice($photos, 0, 5), 5, null);

        foreach ($request->file('garage_photo_files', []) as $index => $file) {
            if ($index >= 0 && $index < 5 && $file && $file->isValid()) {
                $path = $file->store('files/garage/' . $client->id, 'public');
                $photos[$index] = '/storage/' . $path;
            }
        }

        $characteristics = is_array($vehicle->characteristics) ? $vehicle->characteristics : [];
        $characteristics['brand'] = $this->cleanNullableString($validated['brand'] ?? null);
        $characteristics['model'] = $this->cleanNullableString($validated['model'] ?? null);
        $characteristics['color'] = $this->cleanNullableString($validated['color'] ?? null);
        $characteristics['year'] = array_key_exists('year', $validated) && $validated['year'] !== null ? (int) $validated['year'] : null;
        $characteristics['description'] = $this->cleanNullableString($validated['description'] ?? null);

        $title = trim(implode(' ', array_filter([
            $characteristics['brand'] ?? '',
            $characteristics['model'] ?? '',
        ])));

        $vehicle->fill([
            'user_id' => $client->id,
            'fid' => is_numeric($fid) ? (int) $fid : null,
            'email' => $this->clientEmail($client) ?? $vehicle->email,
            'title' => $title !== '' ? $title : $vehicle->title,
            'vin' => $this->cleanNullableString($validated['vin'] ?? null),
            'characteristics' => $characteristics,
            'vehicle_price' => array_key_exists('vehicle_price', $validated) && $validated['vehicle_price'] !== null
                ? round((float) $validated['vehicle_price'], 2)
                : null,
            'garage_photo_1' => $this->cleanNullableString($photos[0]),
            'garage_photo_2' => $this->cleanNullableString($photos[1]),
            'garage_photo_3' => $this->cleanNullableString($photos[2]),
            'garage_photo_4' => $this->cleanNullableString($photos[3]),
            'garage_photo_5' => $this->cleanNullableString($photos[4]),
        ])->save();

        return response()->json([
            'success' => true,
            'message' => 'Данные авто обновлены.',
            'item' => $this->formatGarageVehicle($vehicle->refresh()),
        ]);
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

        $path = $this->resolveKycPhotoPath($client, $map[$type]['column']);
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

    public function deleteKycPhoto(Request $request)
    {
        $id = $request->input('id', '0');
        $type = $request->input('type', '');
        $fid = session('fid', '');

        $client = DB::table('users')
            ->where('id', $id)
            ->where('firma', $fid)
            ->first();

        if (! $client) {
            return response()->json(['success' => false, 'message' => 'Клієнта не знайдено'], 404);
        }

        $map = $this->kycPhotoMap();
        if (! isset($map[$type])) {
            return response()->json(['success' => false, 'message' => 'Невірний тип фото'], 404);
        }

        $meta = $map[$type];
        $pathColumn = $meta['column']; // kyc_passport_file_path, kyc_passport_back_file_path, etc.

        // Clear the main photo column and all related kyc_* metadata columns
        $updateData = [$pathColumn => ''];

        // String columns (file name, mime type) — set to empty string
        foreach (['name', 'mime'] as $key) {
            if (!empty($meta[$key])) {
                $updateData[$meta[$key]] = '';
            }
        }

        // uploaded_at is a nullable timestamp column — set to null, not empty string
        if (!empty($meta['uploaded_at'])) {
            $updateData[$meta['uploaded_at']] = null;
        }

        // file_size is an integer column — set to 0 instead of empty string
        if (!empty($meta['size'])) {
            $updateData[$meta['size']] = 0;
        }

        $updateData = User::filterUsersColumns($updateData);

        DB::table('users')
            ->where('id', $id)
            ->where('firma', $fid)
            ->update($updateData);

        return response()->json(['success' => true, 'message' => 'Фото видалено']);
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
                'project_id' => ['nullable', 'integer', Rule::exists('project', 'id')],
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
                'kyc_status' => $stringValue($request->input('kyc_status', 'not_started')),
                'firma' => $fid,
                'project_id' => $request->filled('project_id')
                    ? (int) $request->input('project_id')
                    : null,
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

    private function clientGarageVehicles(object $client)
    {
        return GarageVehicle::query()
            ->where(function ($query) use ($client) {
                $query->where('user_id', $client->id);
                if ($email = $this->clientEmail($client)) {
                    $query->orWhereRaw('LOWER(TRIM(email)) = ?', [mb_strtolower($email)]);
                }
            })
            ->orderByDesc('checked_at')
            ->orderByDesc('id')
            ->get();
    }

    private function formatGarageVehicle(GarageVehicle $vehicle): array
    {
        $characteristics = is_array($vehicle->characteristics) ? $vehicle->characteristics : [];

        return [
            'id' => $vehicle->id,
            'email' => $vehicle->email,
            'vehicle_number' => $vehicle->vehicle_number,
            'vin' => $vehicle->vin,
            'input_value' => $vehicle->input_value,
            'input_type' => $vehicle->input_type,
            'title' => $vehicle->title,
            'photo_url' => $vehicle->photo_url,
            'garage_photos' => [
                $vehicle->garage_photo_1,
                $vehicle->garage_photo_2,
                $vehicle->garage_photo_3,
                $vehicle->garage_photo_4,
                $vehicle->garage_photo_5,
            ],
            'vehicle_price' => $vehicle->vehicle_price !== null ? (float) $vehicle->vehicle_price : null,
            'adv_link' => $vehicle->adv_link,
            'characteristics' => $characteristics,
            'brand' => $characteristics['brand'] ?? null,
            'model' => $characteristics['model'] ?? null,
            'color' => $characteristics['color'] ?? null,
            'year' => $characteristics['year'] ?? null,
            'description' => $characteristics['description'] ?? null,
            'autoria_status' => $vehicle->autoria_status,
            'checked_at' => optional($vehicle->checked_at)->toDateTimeString(),
        ];
    }

    private function clientEmail(object $client): ?string
    {
        $email = trim((string) ($client->email ?? ''));

        if ($email === '' && filter_var($client->login ?? '', FILTER_VALIDATE_EMAIL)) {
            $email = trim((string) $client->login);
        }

        return $email !== '' ? mb_strtolower($email) : null;
    }

    private function cleanNullableString(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        return $value !== '' ? $value : null;
    }

    private function buildKycPhotoCards(object $client): array
    {
        $cards = [];
        foreach ($this->kycPhotoMap() as $type => $meta) {
            $path = $this->resolveKycPhotoPath($client, $meta['column']);
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
                'label' => 'Паспорт (спереди)',
                'column' => 'kyc_passport_file_path',
                'name' => 'kyc_passport_file_name',
                'mime' => 'kyc_passport_file_mime',
                'size' => 'kyc_passport_file_size',
                'uploaded_at' => 'kyc_passport_uploaded_at',
            ],
            'passport-back' => [
                'label' => 'Паспорт (сзади)',
                'column' => 'kyc_passport_back_file_path',
                'name' => 'kyc_passport_back_file_name',
                'mime' => 'kyc_passport_back_file_mime',
                'size' => 'kyc_passport_back_file_size',
                'uploaded_at' => 'kyc_passport_back_uploaded_at',
            ],
            'selfie' => [
                'label' => 'Лицо с паспортом',
                'column' => 'kyc_selfie_file_path',
                'name' => 'kyc_selfie_file_name',
                'mime' => 'kyc_selfie_file_mime',
                'size' => 'kyc_selfie_file_size',
                'uploaded_at' => 'kyc_selfie_uploaded_at',
            ],
            'liveness' => [
                'label' => 'Liveness-селфи',
                'column' => 'kyc_liveness_file_path',
                'name' => 'kyc_liveness_file_name',
                'mime' => 'kyc_liveness_file_mime',
                'size' => 'kyc_liveness_file_size',
                'uploaded_at' => 'kyc_liveness_uploaded_at',
            ],
        ];
    }

    private function resolveKycPhotoPath(object $client, string $column): string
    {
        $path = trim((string) $this->clientValue($client, $column, ''));
        if ($path === '') {
            return '';
        }

        // Absolute URLs (http/https) — return as-is
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        // Local storage path — check existence
        if (Storage::disk('local')->exists($path)) {
            return $path;
        }

        // Relative paths like ../files/... or files/... — resolve via MediaUrl
        $resolved = \App\Support\MediaUrl::image($path);
        if ($resolved !== null) {
            return $resolved;
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
