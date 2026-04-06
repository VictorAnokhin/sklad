<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

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

        // ── Ищем или создаём клиента ────────────────────────────────────
        $client = DB::table('users')
            ->where('phone', $data['mobile'])
            ->where('firma', 2)
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
                'email'       => '',
                'firma'       => 2,
                'user'        => 'autoagent_api',
                'description' => $deliveryDescription,
                'domen'       => 'http://autoagent.in.ua',
                'msg'         => '1',
                'date'        => now()->format('d-m-Y'),
                'time'        => now()->format('H:i:s'),
            ]);
        } else {
            $clientId = $client->id;
            // Обновим данные клиента
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

        // ── Номер документа ─────────────────────────────────────────────
        $year = now()->format('Y');
        $maxNum = DB::table('document')
            ->where('client1', '<>', '0')
            ->where('type', 'ZOUT')
            ->where('firma', 2)
            ->where('data', 'like', "%{$year}%")
            ->max('num');

        $docNum = ((int) $maxNum) + 1;
        $dt = now()->format('Y-m-d');
        $time = now()->format('H:i:s');

        // ── Создаём документ (ZOUT) ─────────────────────────────────────
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

        // ── Позиции заказа ──────────────────────────────────────────────
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
}
