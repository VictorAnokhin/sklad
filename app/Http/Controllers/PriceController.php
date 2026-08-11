<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PriceController extends Controller
{
    private const ORDER_FID = '2';
    private const PLAN_TYPE = 'price_plan';

    public function index()
    {
        return view('pages.price', [
            'plans' => self::plans(),
        ]);
    }

    public function order(Request $request)
    {
        $validated = $request->validate([
            'plan' => ['required', 'string', 'max:100'],
        ]);

        $plans = self::plans()->values();
        $selectedIndex = $plans->search(fn ($plan) => $plan['name'] === trim((string) $validated['plan']));
        if ($selectedIndex === false) {
            throw ValidationException::withMessages(['plan' => 'Пакет не найден.']);
        }
        if ((int) $selectedIndex === 0) {
            throw ValidationException::withMessages(['plan' => 'Этот пакет уже в работе.']);
        }

        $user = Auth::user();
        if (! $user) {
            return redirect()->route('login');
        }

        $plan = $plans[(int) $selectedIndex];
        $order = DB::transaction(function () use ($user, $plan) {
            $client = $this->ensurePriceClient($user, $plan);
            $now = now();
            $year = $now->format('Y');
            $orderNum = Document::nextNum('ZOUT', self::ORDER_FID, $year);
            $content = implode('; ', array_filter([
                'Price: заявка на пакет',
                'package: ' . $plan['name'],
                'price: ' . $plan['price'],
                'client_email: ' . (string) ($user->email ?? ''),
                'client_id: ' . (string) ($client->id ?? ''),
            ]));

            $payload = [
                'num' => (string) $orderNum,
                'type' => 'ZOUT',
                'firma' => self::ORDER_FID,
                'client1' => (string) $client->id,
                'client2' => '0',
                'summa' => 0,
                'data' => $now->format('d-m-Y'),
                'data2' => $now->format('d-m-Y'),
                'time' => $now->format('H:i:s'),
                'dt' => $now->timestamp,
                'manager' => 'price_page',
                'user' => 'price_page',
                'content' => $content,
                'numz' => (string) $orderNum,
                'typez' => 'ZOUT',
                'docum' => 'price',
                'provodka' => 0,
                'dostup' => 1,
                'money' => '',
                'numdoc' => 'price',
                'close' => 0,
                'typeproduct' => 'price',
            ];
            $payload = array_intersect_key($payload, array_flip(Schema::getColumnListing('document')));
            $orderId = DB::table('document')->insertGetId($payload);

            return DB::table('document')->where('id', $orderId)->first();
        });

        return redirect()
            ->route('price')
            ->with('success', 'Заявка на пакет ' . $plan['name'] . ' отправлена. Номер заявки: ' . ($order->num ?? $order->id));
    }

    public static function plans(): \Illuminate\Support\Collection
    {
        self::seedDefaultPlansIfEmpty();

        return DB::table('conf')
            ->where('type', self::PLAN_TYPE)
            ->where('firma', '0')
            ->orderBy('id')
            ->get()
            ->map(fn ($row) => self::rowToPlan($row))
            ->values();
    }

    public static function replacePlans(array $plans): void
    {
        DB::transaction(function () use ($plans) {
            DB::table('conf')
                ->where('type', self::PLAN_TYPE)
                ->where('firma', '0')
                ->delete();

            foreach ($plans as $index => $plan) {
                $normalized = self::normalizePlan($plan, $index === 1);
                if ($normalized['name'] === '') {
                    continue;
                }

                DB::table('conf')->insert(self::planToRow($normalized));
            }
        });
    }

    public static function defaultPlans(): array
    {
        return [
            [
                'name' => 'Start',
                'subtitle' => 'Для одного проекта и первых продаж.',
                'price' => '$19',
                'description' => "Заказы, клиенты и товары\nКасса и базовые платежи\nДокументы покупки и продажи\nСтартовые отчеты",
                'featured' => false,
            ],
            [
                'name' => 'Business',
                'subtitle' => 'Для команды, склада и регулярного учета.',
                'price' => '$49',
                'description' => "Все возможности Start\nСклад, активы и финансирование\nP&L, Cash Flow и баланс\nКоманда, роли и несколько компаний",
                'featured' => true,
            ],
            [
                'name' => 'Platform',
                'subtitle' => 'Для холдинга, маркетплейса и инвестиционных процессов.',
                'price' => '$129',
                'description' => "Все возможности Business\nХолдинги и связанные проекты\nКаталог, сайт и база знаний\nБанк, активы, депозиты и Web3",
                'featured' => false,
            ],
        ];
    }

    private static function seedDefaultPlansIfEmpty(): void
    {
        if (! Schema::hasTable('conf')) {
            return;
        }

        $exists = DB::table('conf')
            ->where('type', self::PLAN_TYPE)
            ->where('firma', '0')
            ->exists();

        if (! $exists) {
            foreach (self::defaultPlans() as $plan) {
                DB::table('conf')->insert(self::planToRow($plan));
            }
        }
    }

    private static function rowToPlan(object $row): array
    {
        return [
            'id' => (int) $row->id,
            'name' => (string) ($row->name ?? ''),
            'subtitle' => (string) ($row->descript ?? ''),
            'price' => (string) ($row->descript2 ?? ''),
            'description' => (string) ($row->constanta ?? ''),
            'featured' => (string) ($row->color ?? '') === 'featured',
        ];
    }

    private static function planToRow(array $plan): array
    {
        $columns = Schema::getColumnListing('conf');
        $payload = [
            'type' => self::PLAN_TYPE,
            'name' => mb_substr((string) ($plan['name'] ?? ''), 0, 100),
            'descript' => mb_substr((string) ($plan['subtitle'] ?? ''), 0, 255),
            'descript2' => mb_substr((string) ($plan['price'] ?? ''), 0, 80),
            'constanta' => mb_substr((string) ($plan['description'] ?? ''), 0, 2000),
            'color' => ! empty($plan['featured']) ? 'featured' : '',
            'status' => '1',
            'firma' => '0',
            'vision' => '1',
        ];

        return array_intersect_key($payload, array_flip($columns));
    }

    private static function normalizePlan(array $plan, bool $featured): array
    {
        return [
            'name' => self::cleanText($plan['name'] ?? '', 100),
            'subtitle' => self::cleanText($plan['subtitle'] ?? '', 255),
            'price' => self::cleanText($plan['price'] ?? '', 80),
            'description' => self::cleanMultilineText($plan['description'] ?? '', 2000),
            'featured' => $featured,
        ];
    }

    private static function cleanText(mixed $value, int $maxLength): string
    {
        $value = preg_replace('/[\x00-\x1F\x7F<>[\]{}\\\\=;:*|~^$#!?%+=]/u', '', (string) $value);
        $value = preg_replace('/\s{2,}/u', ' ', trim((string) $value));

        return mb_substr($value, 0, $maxLength);
    }

    private static function cleanMultilineText(mixed $value, int $maxLength): string
    {
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F<>[\]{}\\\\|~^]/u', '', (string) $value);
        $value = preg_replace("/[ \t]{2,}/u", ' ', trim((string) $value));

        return mb_substr($value, 0, $maxLength);
    }

    private function ensurePriceClient(User $user, array $plan): object
    {
        $email = trim((string) ($user->email ?? ''));
        if ($email === '') {
            throw ValidationException::withMessages(['email' => 'У пользователя не указан email.']);
        }

        $client = DB::table('users')
            ->where('email', $email)
            ->where('firma', self::ORDER_FID)
            ->first();

        $description = $this->priceClientDescription((string) ($client->description ?? $user->description ?? ''), $plan);
        if ($client) {
            DB::table('users')->where('id', $client->id)->update([
                'description' => $description,
                'updated_at' => now(),
            ]);

            return DB::table('users')->where('id', $client->id)->first();
        }

        $payload = [
            'name' => $user->name,
            'secondname' => $user->secondname,
            'fathername' => $user->fathername,
            'orgname' => $user->orgname,
            'email' => $email,
            'phone' => $user->phone,
            'phone1' => $user->phone1,
            'city' => $user->city,
            'region' => $user->region,
            'country' => $user->country,
            'idstatus' => $user->idstatus ?: 1,
            'ustype' => $user->ustype ?: $user->idstatus,
            'fid' => self::ORDER_FID,
            'firma' => self::ORDER_FID,
            'project_id' => $this->projectIdForOrderFirma(),
            'status' => $user->status ?? 1,
            'password' => $user->password ?: Hash::make(Str::random(32)),
            'description' => $description,
            'created_at' => now(),
            'updated_at' => now(),
        ];
        $payload = array_intersect_key($payload, array_flip(Schema::getColumnListing('users')));
        $clientId = DB::table('users')->insertGetId($payload);

        return DB::table('users')->where('id', $clientId)->first();
    }

    private function projectIdForOrderFirma(): ?int
    {
        if (Schema::hasTable('project') && DB::table('project')->where('id', (int) self::ORDER_FID)->exists()) {
            return (int) self::ORDER_FID;
        }

        return null;
    }

    private function priceClientDescription(string $currentDescription, array $plan): string
    {
        $line = 'Выбранный пакет: ' . $plan['name'] . ' (' . $plan['price'] . ')';
        $description = trim(preg_replace('/Выбранный пакет: [^;\n]+[;\n]?/u', '', $currentDescription));
        $description = trim($description, " \t\n\r\0\x0B;");

        return mb_substr($description !== '' ? $description . '; ' . $line : $line, 0, 2000);
    }
}
