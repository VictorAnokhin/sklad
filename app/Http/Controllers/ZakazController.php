<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
    // ── Оформить заказ ──────────────────────────────────────────────────────

    public function store(Request $request)
    {
        if ($request->boolean('quick_order')) {
            return $this->storeQuickOrder($request);
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

        DB::transaction(function () use ($data, $deliveryDescription, $fullDescription, $totalSum, &$docId, &$docNum) {
            $year = now()->format('Y');
            $this->withOrderNumberLock(2, $year, function () use ($data, $deliveryDescription, $fullDescription, $totalSum, $year, &$docId, &$docNum) {
                $client = DB::table('users')
                    ->where('phone', $data['mobile'])
                    ->where('firma', 2)
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
                        'firma'       => 2,
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

                $docNum = $this->nextOrderNumber(2, $year);
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
                    'firma'     => 2,
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
                        'firma'  => 2,
                        'docid'  => $docId,
                    ]);
                }
            });
        });

        // ── Telegram notification ───────────────────────────────────────
        $this->sendTelegramNotification($docNum, $data['mobile'], $totalSum);

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

    private function storeQuickOrder(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'firstname' => 'required|string|max:50',
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

        DB::transaction(function () use ($data, &$docId, &$docNum) {
            $year = now()->format('Y');
            $this->withOrderNumberLock(2, $year, function () use ($data, $year, &$docId, &$docNum) {
                $client = DB::table('users')
                    ->where('phone', $data['mobile'])
                    ->where('firma', 2)
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
                        'email' => $this->buildGuestEmail($data['mobile']),
                        'password' => Hash::make(Str::random(40)),
                        'firma' => 2,
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
                            'description' => 'Швидке замовлення номера з головної сторінки',
                        ]);
                }

                $docNum = $this->nextOrderNumber(2, $year);
                $dt = now()->format('Y-m-d');
                $time = now()->format('H:i:s');
                $content = 'Швидке замовлення номера. Номер: ' . $data['autonum'] . '. Імʼя: ' . $data['firstname'] . '. Телефон: ' . $data['mobile'];

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
                    'firma' => 2,
                    'dt' => strtotime($dt) ?: time(),
                    'numz' => $docNum,
                    'typez' => 'ZOUT',
                    'manager' => '',
                    'dostup' => '1',
                ]);
            });
        });

        $this->sendTelegramNotification($docNum, $data['mobile'], 0);
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

    // ── Telegram уведомление ───────────────────────────────────────────────

    private function sendTelegramNotification(int $docNum, string $phone, float $summa): void
    {
        $tgToken = env('TELEGRAM_BOT_TOKEN', '597739151:AAF6D3COpe7ietPHyeXPziVdmvRiw7Ah1Lo');
        $tgChat  = env('TELEGRAM_CHAT_ID', 405053730);

        $text = "🛒 Нове замовлення №{$docNum}\n"
              . "📱 Телефон: {$phone}\n"
              . "💰 Сума: {$summa}₴";

        $url = "https://api.telegram.org/bot{$tgToken}/sendMessage"
             . "?chat_id={$tgChat}"
             . "&parse_mode=html"
             . "&text=" . urlencode($text);

        @file_get_contents($url);
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
        $mobile = $request->input('mobile');

        if (!$mobile) {
            return response()->json([
                'success' => false,
                'message' => 'Необхідно вказати mobile',
            ], 400);
        }

        $client = DB::table('users')
            ->where('phone', $mobile)
            ->where('firma', 2)
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
        $user = $request->user();
        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated',
            ], 401);
        }

        $orders = DB::table('document')
            ->where('client1', $user->id)
            ->where('type', 'ZOUT')
            ->orderBy('dt', 'desc')
            ->get();

        // Parse description into items
        $formattedOrders = $orders->map(function ($order) {
            $items = [];
            
            // Simple parsing of description format: "Item1 — qty1шт × price1₴; Item2 — qty2шт × price2₴; ..."
            if (!empty($order->description)) {
                // Split by ';' to separate delivery info and items
                $parts = explode(';', $order->description);
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
                'num' => $order->num,
                'dt' => $order->dt,
                'description' => $order->description,
                'items' => $items,
                'sum' => $order->sum,
                'status' => $order->status ?? 'pending',
            ];
        });

        return response()->json([
            'orders' => $formattedOrders,
        ]);
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
