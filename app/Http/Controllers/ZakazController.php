<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Field;
use App\Models\User;
use App\Support\MediaUrl;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * ZakazController
 * Оформление заказа — аналог zakaz-d.php
 * Принимает корзину и данные клиента, создаёт документ ZOUT
 */
class ZakazController extends Controller
{
    private function resolveApiFid(Request $request, $default = '2')
    {
        return (string) $request->input('fid', $default !== '' ? $default : session('fid', ''));
    }

    // ── Оформить заказ ──────────────────────────────────────────────────────

    public function store(Request $request)
    {
        $fid = $this->resolveApiFid($request, '2');

        if ($request->boolean('quick_order')) {
            return $this->storeQuickOrder($request, $fid);
        }

        $validator = Validator::make($request->all(), [
            // Товары
            'items'              => 'required|array|min:1',
            'items.*.id'         => 'required|integer',
            'items.*.name'       => 'required|string|max:255',
            'items.*.price'      => 'required|numeric|min:0',
            'items.*.quantity'   => 'required|integer|min:1',

            // Контактные данные
            'firstname'          => 'required|string|max:50',
            'secondname'         => 'nullable|string|max:50',
            'mobile'             => ['required', 'regex:/^\+38\d{10}$/'],

            // Адрес
            'region'             => 'nullable|string|max:100',
            'city'               => 'required|string|max:100',
            'dostavka'           => 'required|in:office,poshta,address',
            'office'             => 'nullable|string|max:255',
            'poshta'             => 'nullable|string|max:255',
            'address'            => 'nullable|string|max:255',

            // Прочее
            'pay'                => 'required|in:card,order',
            'autonum'            => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors(),
            ], 422);
        }

        $data = $request->all();

        // ── Сумма заказа ────────────────────────────────────────────────
        $totalSum = 0;
        $itemsDescription = [];
        foreach ($data['items'] as $item) {
            $sum = (float) $item['price'] * (int) $item['quantity'];
            $totalSum += $sum;
            $itemsDescription[] = $item['name'] . ' — ' . (int) $item['quantity'] . 'шт × ' . (float) $item['price'] . '₴';
        }

        // ── Формируем описание доставки ─────────────────────────────────
        $deliveryDescription = '';
        switch ($data['dostavka']) {
            case 'office':
                $deliveryDescription = 'Самовывіз з точки видачі: ' . ($data['office'] ?? 'не обрано');
                break;
            case 'poshta':
                $deliveryDescription = 'Нова пошта: ' . ($data['poshta'] ?? 'не вказано');
                break;
            case 'address':
                $deliveryDescription = 'Адресна доставка: ' . ($data['address'] ?? 'не вказано');
                break;
        }

        $fullDescription = implode('; ', $itemsDescription) . '. ' . $deliveryDescription;
        if (!empty($data['autonum'])) {
            $fullDescription .= '. Додатково: ' . $data['autonum'];
        }

        $docId = 0;
        $docNum = 0;

        /** @var User|null $bearerUser Користувач з Bearer token (кабінет), щоб client1 збігався з акаунтом */
        $bearerUser = Auth::guard('sanctum')->user();

        DB::transaction(function () use ($data, $deliveryDescription, $fid, $fullDescription, $totalSum, $bearerUser, &$docId, &$docNum) {
            $year = now()->format('Y');
            $this->withOrderNumberLock($fid, $year, function () use ($data, $deliveryDescription, $fid, $fullDescription, $totalSum, $year, $bearerUser, &$docId, &$docNum) {
                $bearerFirma = $bearerUser
                    ? trim((string) ($bearerUser->firma ?? $bearerUser->fid ?? ''))
                    : '';
                $useBearerClient = $bearerUser
                    && $bearerFirma !== ''
                    && $bearerFirma === trim((string) $fid);

                if ($useBearerClient) {
                    $clientId = (int) $bearerUser->id;

                    $userRow = DB::table('users')->where('id', $clientId)->lockForUpdate()->first();
                    if ($userRow) {
                        DB::table('users')
                            ->where('id', $clientId)
                            ->update([
                                'region'      => $data['region'] ?? $userRow->region,
                                'city'        => $data['city'],
                                'poshta'      => $data['poshta'] ?? $userRow->poshta,
                                'address'     => $data['address'] ?? $userRow->address,
                                'name'        => $data['firstname'],
                                'secondname'  => $data['secondname'] ?? $userRow->secondname,
                                'description' => trim(($userRow->description ?? '') . ', ПІБ: ' . ($data['secondname'] ?? '') . ' ' . $data['firstname'], ', '),
                                'phone'       => $data['mobile'],
                            ]);
                    }
                } else {
                    $client = DB::table('users')
                        ->where('phone', $data['mobile'])
                        ->where('firma', $fid)
                        ->lockForUpdate()
                        ->first();

                    if (!$client) {
                        $clientId = DB::table('users')->insertGetId([
                            'region'      => $data['region'] ?? '',
                            'city'        => $data['city'],
                            'poshta'      => $data['poshta'] ?? '',
                            'address'     => $data['address'] ?? '',
                            'name'        => $data['firstname'],
                            'secondname'  => $data['secondname'] ?? '',
                            'phone'       => $data['mobile'],
                            'email'       => $this->buildGuestEmail($data['mobile']),
                            'password'    => Hash::make(Str::random(40)),
                            'firma'       => $fid,
                            'user'        => 'autoagent_api',
                            'description' => $deliveryDescription,
                            'domen'       => 'http://autoagent.in.ua',
                            'msg'         => '1',
                            'date'        => now()->format('Y-m-d'),
                            'time'        => now()->format('H:i:s'),
                        ]);
                    } else {
                        $clientId = $client->id;

                        DB::table('users')
                            ->where('id', $clientId)
                            ->update([
                                'region'      => $data['region'] ?? $client->region,
                                'city'        => $data['city'],
                                'poshta'      => $data['poshta'] ?? $client->poshta,
                                'address'     => $data['address'] ?? $client->address,
                                'name'        => $data['firstname'],
                                'secondname'  => $data['secondname'] ?? $client->secondname,
                                'description' => $client->description . ', ПІБ: ' . ($data['secondname'] ?? '') . ' ' . $data['firstname'],
                            ]);
                    }
                }

                $docNum = $this->nextOrderNumber($fid, $year);
                $dt = now()->format('Y-m-d');
                $time = now()->format('H:i:s');

                $docId = DB::table('document')->insertGetId([
                    'num'       => $docNum,
                    'client1'   => $clientId,
                    'client2'   => '',
                    'content'   => $fullDescription . ' autoagent',
                    'type'      => 'ZOUT',
                    'summa'     => $totalSum,
                    'schet'     => '',
                    'data'      => now()->format('d-m-Y'),
                    'time'      => $time,
                    'user'      => 'autoagent_api',
                    'firma'     => $fid,
                    'dt'        => strtotime($dt) ?: time(),
                    'numz'      => $docNum,
                    'typez'     => 'ZOUT',
                    'manager'   => '',
                    'dostup'    => '1',
                ]);

                foreach ($data['items'] as $item) {
                    DB::table('z_body')->insert([
                        'docnum' => $docNum,
                        'pid'    => 1,
                        'pnum'   => $item['id'],
                        'pcount' => (int) $item['quantity'],
                        'pprice' => (float) $item['price'],
                        'psumma' => (float) $item['price'] * (int) $item['quantity'],
                        'type'   => 'ZOUT',
                        'firma'  => $fid,
                        'docid'  => $docId,
                    ]);
                }
            });
        });

        // ── SMS notification (через SMSClub) ────────────────────────────
        $this->sendSmsNotification($data['mobile'], $docNum);

        return response()->json([
            'success' => true,
            'message' => 'Замовлення успішно оформлено',
            'order'   => [
                'id'   => $docId,
                'num'  => $docNum,
                'summa' => $totalSum,
            ],
        ]);
    }

    private function storeQuickOrder(Request $request, string $fid)
    {
        $validator = Validator::make($request->all(), [
            'firstname' => 'required|string|max:50',
            'email' => 'required|email|max:255',
            'mobile' => ['required', 'regex:/^\+38\d{10}$/'],
            'autonum' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        $docId = 0;
        $docNum = 0;

        DB::transaction(function () use ($data, $fid, &$docId, &$docNum) {
            $year = now()->format('Y');
            $this->withOrderNumberLock($fid, $year, function () use ($data, $fid, $year, &$docId, &$docNum) {
                $client = DB::table('users')
                    ->where('phone', $data['mobile'])
                    ->where('firma', $fid)
                    ->lockForUpdate()
                    ->first();

                if (!$client) {
                    $clientId = DB::table('users')->insertGetId([
                        'region' => '',
                        'city' => '',
                        'poshta' => '',
                        'address' => '',
                        'name' => $data['firstname'],
                        'secondname' => '',
                        'phone' => $data['mobile'],
                        'email' => $data['email'],
                        'password' => Hash::make(Str::random(40)),
                        'firma' => $fid,
                        'user' => 'autoagent_api',
                        'description' => 'Швидке замовлення номера з головної сторінки',
                        'domen' => 'http://autoagent.in.ua',
                        'msg' => '1',
                        'date' => now()->format('Y-m-d'),
                        'time' => now()->format('H:i:s'),
                    ]);
                } else {
                    $clientId = $client->id;

                    DB::table('users')
                        ->where('id', $clientId)
                        ->update([
                            'name' => $data['firstname'],
                            'email' => $data['email'],
                            'description' => 'Швидке замовлення номера з головної сторінки',
                        ]);
                }

                $docNum = $this->nextOrderNumber($fid, $year);
                $dt = now()->format('Y-m-d');
                $time = now()->format('H:i:s');
                $content = 'Швидке замовлення номера. Номер: ' . $data['autonum'] . '. Імʼя: ' . $data['firstname'] . '. Email: ' . $data['email'] . '. Телефон: ' . $data['mobile'];

                $docId = DB::table('document')->insertGetId([
                    'num' => $docNum,
                    'client1' => $clientId,
                    'client2' => '',
                    'content' => $content,
                    'type' => 'ZOUT',
                    'summa' => 0,
                    'schet' => '',
                    'data' => now()->format('d-m-Y'),
                    'time' => $time,
                    'user' => 'autoagent_api',
                    'firma' => $fid,
                    'dt' => strtotime($dt) ?: time(),
                    'numz' => $docNum,
                    'typez' => 'ZOUT',
                    'manager' => '',
                    'dostup' => '1',
                ]);
            });
        });

        $this->sendSmsNotification($data['mobile'], $docNum);

        return response()->json([
            'success' => true,
            'message' => 'Замовлення успішно оформлено',
            'order' => [
                'id' => $docId,
                'num' => $docNum,
                'summa' => 0,
            ],
        ]);
    }

    public function storeCarRequest(Request $request)
    {
        $fid = $this->resolveApiFid($request, '2');

        $validator = Validator::make($request->all(), [
            'request_type' => 'required|in:sale,purchase',
            'name' => 'required|string|max:50',
            'mobile' => ['required', 'regex:/^\+38\d{10}$/'],
            'description' => 'required|string|max:2000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $user = $request->user();
        $docId = 0;
        $docNum = 0;
        $requestLabel = $data['request_type'] === 'sale' ? 'продажу авто' : 'покупку авто';
        $userName = trim((string) $data['name']);
        $userEmail = trim((string) ($user->email ?? ''));
        $userPhone = trim((string) $data['mobile']);

        DB::transaction(function () use ($data, $fid, $requestLabel, $user, $userEmail, $userName, $userPhone, &$docId, &$docNum) {
            $year = now()->format('Y');
            $this->withOrderNumberLock($fid, $year, function () use ($data, $fid, $requestLabel, $user, $userEmail, $userName, $userPhone, $year, &$docId, &$docNum) {
                $docNum = $this->nextOrderNumber($fid, $year);
                $dt = now()->format('Y-m-d');
                $time = now()->format('H:i:s');
                $content = implode('. ', array_filter([
                    'Заявка на ' . $requestLabel,
                    'Google: ' . $userEmail,
                    'Імʼя: ' . $userName,
                    $userPhone !== '' ? 'Телефон: ' . $userPhone : null,
                    'Опис: ' . trim((string) $data['description']),
                ]));

                $docId = DB::table('document')->insertGetId([
                    'num' => $docNum,
                    'client1' => $user->id,
                    'client2' => '',
                    'content' => $content,
                    'type' => 'ZOUT',
                    'summa' => 0,
                    'schet' => '',
                    'data' => now()->format('d-m-Y'),
                    'time' => $time,
                    'user' => 'autoagent_api',
                    'firma' => $fid,
                    'dt' => strtotime($dt) ?: time(),
                    'numz' => $docNum,
                    'typez' => 'ZOUT',
                    'manager' => '',
                    'dostup' => '1',
                ]);
            });
        });

        return response()->json([
            'success' => true,
            'message' => 'Заявку успішно оформлено',
            'order' => [
                'id' => $docId,
                'num' => $docNum,
                'summa' => 0,
            ],
        ]);
    }

    // ── SMS уведомление ───────────────────────────────────────────────────

    private function sendSmsNotification(string $phone, int $docNum): void
    {
        $smsText = "Ваше замовлення (№ {$docNum}) прийнято. На протязі 10хв ми зателефонуємо. Довідки: (097)23-23-183";

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => 'https://im.smsclub.mobi/sms/send',
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS     => json_encode([
                'phone'     => [$phone],
                'message'   => $smsText,
                'src_addr'  => 'Avtoznak',
            ]),
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer FVER5d9Swn1tDN9',
                'Content-Type: application/json',
            ],
        ]);

        try {
            curl_exec($ch);
        } catch (\Exception $e) {
            \Log::warning('SMS send failed: ' . $e->getMessage());
        } finally {
            curl_close($ch);
        }
    }

    // ── Получить список заказов клиента ───────────────────────────────────

    public function index(Request $request)
    {
        $fid = $this->resolveApiFid($request, '2');
        $mobile = $request->input('mobile');

        if (!$mobile) {
            return response()->json([
                'success' => false,
                'message' => 'Необхідно вказати mobile',
            ], 400);
        }

        $client = DB::table('users')
            ->where('phone', $mobile)
            ->where('firma', $fid)
            ->first();

        if (!$client) {
            return response()->json([
                'success' => true,
                'data'    => [],
            ]);
        }

        $orders = DB::table('document')
            ->where('client1', $client->id)
            ->where('type', 'ZOUT')
            ->orderBy('dt', 'desc')
            ->limit(50)
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $orders,
        ]);
    }

    // ── Получить заказы аутентифицированного пользователя ──────────────────

    public function apiOrders(Request $request)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json([
                    'message' => 'Unauthenticated',
                ], 401);
            }

            $fid = trim((string) $request->input('fid', ''));
            if ($fid === '') {
                $fid = trim((string) ($user->firma ?? $user->fid ?? ''));
            }

            $clientUserIds = $this->resolveCabinetOrderClientIds($user);

            $orders = DB::table('document')
                ->whereIn('client1', $clientUserIds)
                ->where('type', 'ZOUT')
                ->when($fid !== '', fn ($q) => $q->where('firma', $fid))
                ->orderBy('dt', 'desc')
                ->get();

            // Parse description into items
            $formattedOrders = $orders->map(function ($order) {
                $items = [];
                
                // Simple parsing of content format: "Item1 — qty1шт × price1₴; Item2 — qty2шт × price2₴; ..."
                if (!empty($order->content)) {
                    // Split by ';' to separate delivery info and items
                    $parts = explode(';', $order->content);
                    foreach ($parts as $part) {
                        $part = trim($part);
                        // Match pattern: "Name — Qqty × Price"
                        if (preg_match('/^(.+?)\s*—\s*(\d+)шт\s*×\s*([0-9.]+)₴/', $part, $matches)) {
                            $items[] = [
                                'name' => trim($matches[1]),
                                'quantity' => (int) $matches[2],
                                'price' => (float) $matches[3],
                            ];
                        }
                    }
                }

                return [
                    'id' => $order->id,
                    'num' => (string) $order->num,
                    'dt' => $order->dt,
                    'description' => $order->content,
                    'items' => $items,
                    'sum' => (float) $order->summa,
                    'status' => $order->status ?? 'pending',
                ];
            });

            return response()->json($formattedOrders);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error fetching orders',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function apiGarage(Request $request)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json([
                    'message' => 'Unauthenticated',
                ], 401);
            }

            $fid = trim((string) $request->input('fid', ''));
            if ($fid === '') {
                $fid = trim((string) ($user->firma ?? $user->fid ?? ''));
            }

            $locale = Field::normalizeLocale((string) $request->input('lang', $request->input('locale', 'ru')));
            $clientUserIds = $this->resolveCabinetOrderClientIds($user);
            if ($clientUserIds === []) {
                return response()->json([
                    'items' => [],
                    'client_ids' => [],
                ]);
            }

            $vehicleSectionIds = $this->vehicleSectionIds($fid);

            $query = DB::table('document as d')
                ->join('z_body as zb', function ($join) {
                    $join->on('zb.docid', '=', 'd.id')
                        ->whereColumn('zb.firma', 'd.firma');
                })
                ->join('comp as c', function ($join) {
                    $join->on('c.id', '=', 'zb.pnum');
                })
                ->leftJoin('descript as ds', function ($join) {
                    $join->on('ds.pnum', '=', 'c.id')
                        ->whereColumn('ds.firma', 'c.firma');
                })
                ->whereIn('d.client1', $clientUserIds)
                ->where('d.type', 'ZOUT')
                ->when($fid !== '', fn ($q) => $q->where('d.firma', $fid))
                ->where(function ($q) use ($vehicleSectionIds) {
                    if ($vehicleSectionIds !== []) {
                        $q->whereIn('c.idcaption', $vehicleSectionIds)
                            ->orWhereIn('c.idglava', $vehicleSectionIds);
                    }

                    $q->orWhere('c.htmlkeyspop', 'like', '%transport%')
                        ->orWhere('c.htmlkeys', 'like', '%transport%')
                        ->orWhere('c.htmlkeyspop', 'like', '%auto%')
                        ->orWhere('c.htmlkeys', 'like', '%auto%')
                        ->orWhere('c.htmlkeyspop', 'like', '%vehicle%')
                        ->orWhere('c.htmlkeys', 'like', '%vehicle%');
                })
                ->select([
                    'd.id as order_id',
                    'd.num as order_num',
                    'd.dt as order_dt',
                    'd.data as order_date',
                    'd.content as order_description',
                    'd.summa as order_sum',
                    'zb.pcount as quantity',
                    'zb.pprice as purchase_price',
                    'zb.psumma as line_sum',
                    'c.id as product_id',
                    'c.nickname',
                    'c.name as comp_name',
                    'c.pay',
                    'c.pay1',
                    'c.count',
                    'c.idcaption',
                    'c.idglava',
                    'c.nfoto',
                    'c.nfoto1',
                    'c.param1',
                    'c.param2',
                    'c.param3',
                    'c.param4',
                    'c.param5',
                    'c.param6',
                    'c.htmlkeys',
                    'c.htmlkeyspop',
                    'ds.name as name_ru',
                    'ds.name_ua',
                    'ds.name_en',
                    'ds.description',
                    'ds.description_ua',
                    'ds.description_en',
                ])
                ->orderByDesc('d.dt')
                ->orderByDesc('d.id');

            $rows = $query->get();

            $items = $rows->map(function ($row) use ($locale) {
                $name = Field::localizedValue(
                    $locale,
                    $row->name_ru ?? $row->comp_name ?? $row->nickname ?? '',
                    $row->name_ua ?? '',
                    $row->name_en ?? ''
                );
                $description = Field::localizedValue(
                    $locale,
                    $row->description ?? '',
                    $row->description_ua ?? '',
                    $row->description_en ?? ''
                );

                return [
                    'id' => (int) $row->product_id,
                    'order_id' => (int) $row->order_id,
                    'order_num' => (string) $row->order_num,
                    'order_dt' => $row->order_dt,
                    'order_date' => $row->order_date,
                    'order_description' => $row->order_description,
                    'name' => $name !== '' ? $name : 'Автомобиль #' . $row->product_id,
                    'description' => $description,
                    'quantity' => (int) $row->quantity,
                    'purchase_price' => (float) $row->purchase_price,
                    'line_sum' => (float) $row->line_sum,
                    'market_price' => (float) ($row->pay ?? 0),
                    'old_price' => (float) ($row->pay1 ?? 0),
                    'count' => (int) ($row->count ?? 0),
                    'image' => MediaUrl::image($row->nfoto ?? '') ?: MediaUrl::image($row->nfoto1 ?? ''),
                    'image_thumb' => MediaUrl::image($row->nfoto1 ?? '') ?: MediaUrl::image($row->nfoto ?? ''),
                    'make' => trim((string) ($row->param1 ?? '')),
                    'model' => trim((string) ($row->param2 ?? '')),
                    'year' => $this->positiveIntOrNull($row->param3 ?? null),
                    'mileage' => $this->positiveIntOrNull($row->param4 ?? null),
                    'vin' => trim((string) ($row->param5 ?? '')),
                    'plate' => trim((string) ($row->param6 ?? '')),
                    'meta_keywords' => trim((string) ($row->htmlkeys ?? $row->htmlkeyspop ?? '')),
                ];
            })->values();

            return response()->json([
                'items' => $items,
                'client_ids' => $clientUserIds,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error fetching garage',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * У кабінеті client1 у документів часто вказує на «гостьовий» рядок users з тим самим телефоном.
     * Збираємо всі id контрагентів з поточним акаунтом за id, email та phone / phone1 у межах firma.
     *
     * @return list<int>
     */
    private function resolveCabinetOrderClientIds(User $user): array
    {
        $firma = trim((string) ($user->firma ?? $user->fid ?? ''));
        $ids = collect([(int) $user->id]);

        if ($firma === '') {
            return $ids->unique()->values()->all();
        }

        $email = mb_strtolower(trim((string) ($user->email ?? '')));
        if ($email !== '' && Schema::hasColumn('users', 'email')) {
            $ids = $ids->merge(
                DB::table('users')
                    ->where('firma', $firma)
                    ->whereRaw('LOWER(TRIM(email)) = ?', [$email])
                    ->pluck('id')
            );
        }

        $p = trim((string) ($user->phone ?? ''));
        $p1 = trim((string) ($user->phone1 ?? ''));

        if ($p !== '' || $p1 !== '') {
            $phoneColumns = collect(['phone', 'phone1'])
                ->filter(fn ($column) => Schema::hasColumn('users', $column))
                ->values()
                ->all();

            $ids = $ids->merge(
                DB::table('users')
                    ->where('firma', $firma)
                    ->where(function ($q) use ($p, $p1, $phoneColumns) {
                        foreach (array_filter([$p, $p1]) as $phone) {
                            $digits = preg_replace('/\D+/', '', $phone) ?? '';
                            $q->orWhere(function ($inner) use ($phone, $digits, $phoneColumns) {
                                foreach ($phoneColumns as $column) {
                                    $inner->orWhere($column, $phone);
                                    if ($digits !== '') {
                                        $normalizedPhoneSql = "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE({$column}, ' ', ''), '-', ''), '(', ''), ')', ''), '+', '')";
                                        $inner->orWhereRaw("{$normalizedPhoneSql} = ?", [$digits]);
                                    }
                                }
                            });
                        }
                    })
                    ->pluck('id')
            );
        }

        return $ids->map(fn ($id) => (int) $id)->unique()->values()->all();
    }

    private function vehicleSectionIds(string $fid): array
    {
        if (!Schema::hasTable('field')) {
            return [];
        }

        $query = DB::table('field')
            ->where('keyfield', 'catalog')
            ->when($fid !== '', fn ($q) => $q->where('firma', $fid))
            ->where(function ($q) {
                if (Schema::hasColumn('field', 'link')) {
                    $q->orWhere('link', 'like', '%transport%')
                        ->orWhere('link', 'like', '%auto%')
                        ->orWhere('link', 'like', '%car%');
                }

                foreach (['val', 'valua', 'valen'] as $column) {
                    if (Schema::hasColumn('field', $column)) {
                        $q->orWhere($column, 'like', '%Авто%')
                            ->orWhere($column, 'like', '%Транспорт%')
                            ->orWhere($column, 'like', '%Машин%')
                            ->orWhere($column, 'like', '%Vehicle%')
                            ->orWhere($column, 'like', '%Car%');
                    }
                }
            });

        return $query->pluck('id')->map(fn ($id) => (string) $id)->unique()->values()->all();
    }

    private function positiveIntOrNull($value): ?int
    {
        $value = preg_replace('/\D+/', '', (string) ($value ?? '')) ?? '';
        if ($value === '') {
            return null;
        }

        $number = (int) $value;

        return $number > 0 ? $number : null;
    }

    private function buildGuestEmail(string $mobile): string
    {
        $digits = preg_replace('/\D+/', '', $mobile) ?: Str::random(10);

        return "order-{$digits}@autoagent.local";
    }

    private function nextOrderNumber(int|string $fid, int|string $year): int
    {
        return Document::nextNum('ZOUT', (string) $fid, (string) $year);
    }

    private function withOrderNumberLock(int|string $fid, int|string $year, callable $callback): mixed
    {
        $lockName = sprintf('zout-order-number:%s:%s', $fid, $year);
        $lock = DB::selectOne('SELECT GET_LOCK(?, 10) AS lock_acquired', [$lockName]);

        if ((int) ($lock->lock_acquired ?? 0) !== 1) {
            abort(503, 'Не вдалося отримати блокування для номера замовлення');
        }

        try {
            return $callback();
        } finally {
            DB::select('SELECT RELEASE_LOCK(?)', [$lockName]);
        }
    }
}
