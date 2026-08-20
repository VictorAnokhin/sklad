<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\User;
use App\Services\SubscriptionBillingService;
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
        $plans = self::plans();

        return view('pages.price', [
            'plans' => $plans,
            'purchasedPlans' => $this->purchasedPlanNames(Auth::user(), $plans),
        ]);
    }

    public function order(Request $request)
    {
        $user = Auth::user();
        $rules = [
            'plan_id' => ['required', 'integer', 'min:1'],
            'plan' => ['nullable', 'string', 'max:100'],
            'customer_name' => [$user ? 'nullable' : 'required', 'string', 'max:120'],
            'customer_email' => [$user ? 'nullable' : 'required', 'email', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:50'],
            'customer_comment' => ['nullable', 'string', 'max:1000'],
        ];
        $validated = $request->validate($rules);

        if (! Schema::hasTable('subscription_plans') || ! Schema::hasTable('customer_subscriptions') || ! Schema::hasTable('subscription_invoices')) {
            throw ValidationException::withMessages(['plan' => 'Подписки еще не настроены. Выполните миграции.']);
        }

        $plan = DB::table('subscription_plans')
            ->where('id', (int) $validated['plan_id'])
            ->where('project_id', (int) self::ORDER_FID)
            ->where('active', true)
            ->first();
        if (! $plan) {
            throw ValidationException::withMessages(['plan' => 'Пакет не найден.']);
        }

        $planArray = self::rowToPlan($plan);
        $contact = [
            'name' => self::cleanText($validated['customer_name'] ?? $this->userDisplayName($user), 120),
            'email' => mb_substr(trim((string) ($validated['customer_email'] ?? ($user->email ?? ''))), 0, 255),
            'phone' => self::cleanText($validated['customer_phone'] ?? ($user->phone ?? ''), 50),
            'comment' => self::cleanPlanHtml($validated['customer_comment'] ?? '', 1000),
        ];

        $subscriptionId = DB::transaction(function () use ($user, $plan, $planArray, $contact) {
            $client = $this->ensurePriceClient($user, $planArray, $contact);
            $paidInvoiceExists = $this->clientHasPaidPlan((int) $client->id, (int) $plan->id);
            if ($paidInvoiceExists) {
                throw ValidationException::withMessages(['plan' => 'Этот тариф уже используется.']);
            }

            $existing = DB::table('customer_subscriptions')
                ->where('project_id', (int) self::ORDER_FID)
                ->where('client_id', (int) $client->id)
                ->where('plan_id', (int) $plan->id)
                ->whereIn('status', ['active', 'blocked'])
                ->orderByDesc('id')
                ->first();

            if ($existing) {
                return (int) $existing->id;
            }

            $today = now()->toDateString();

            return (int) DB::table('customer_subscriptions')->insertGetId([
                'project_id' => (int) self::ORDER_FID,
                'client_id' => (int) $client->id,
                'plan_id' => (int) $plan->id,
                'status' => 'active',
                'payment_status' => 'paid',
                'starts_at' => $today,
                'next_billing_at' => $today,
                'payment_method' => '',
                'auto_create_invoice' => true,
                'auto_close_if_paid' => false,
                'notes' => $contact['comment'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        app(SubscriptionBillingService::class)->billSubscription($subscriptionId);

        return redirect()
            ->route('price')
            ->with('success', 'Подписка на тариф ' . $planArray['name'] . ' создана. Начисление сформировано.');
    }

    public static function plans(): \Illuminate\Support\Collection
    {
        if (Schema::hasTable('subscription_plans')) {
            $query = DB::table('subscription_plans')
                ->where('project_id', (int) self::ORDER_FID)
                ->where('active', true);

            if (Schema::hasColumn('subscription_plans', 'sort_order')) {
                $query->orderBy('sort_order');
            }

            return $query
                ->orderBy('id')
                ->get()
                ->map(fn ($row) => self::rowToPlan($row))
                ->values();
        }

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
        if (property_exists($row, 'billing_period')) {
            $periodLabels = ['week' => 'в неделю', 'month' => 'в месяц', 'quarter' => 'в квартал', 'year' => 'в год'];

            return [
                'id' => (int) $row->id,
                'name' => (string) ($row->name ?? ''),
                'subtitle' => (string) ($row->subtitle ?? ''),
                'price' => number_format((float) ($row->price ?? 0), 2, '.', ' ') . ' ' . (string) ($row->currency ?? 'UAH'),
                'period' => $periodLabels[(string) ($row->billing_period ?? 'month')] ?? 'за период',
                'description' => self::cleanPlanHtml($row->description ?? '', 2000),
                'featured' => false,
            ];
        }

        return [
            'id' => (int) $row->id,
            'name' => (string) ($row->name ?? ''),
            'subtitle' => (string) ($row->descript ?? ''),
            'price' => (string) ($row->descript2 ?? ''),
            'description' => self::cleanPlanHtml($row->constanta ?? '', 2000),
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
            'description' => self::cleanPlanHtml($plan['description'] ?? '', 2000),
            'featured' => $featured,
        ];
    }

    private static function cleanText(mixed $value, int $maxLength): string
    {
        $value = preg_replace('/[\x00-\x1F\x7F<>[\]{}\\\\=;:*|~^$#!?%+=]/u', '', (string) $value);
        $value = preg_replace('/\s{2,}/u', ' ', trim((string) $value));

        return mb_substr($value, 0, $maxLength);
    }

    private static function cleanPlanHtml(mixed $value, int $maxLength): string
    {
        $value = preg_replace('/<\s*(script|style|iframe|object|embed|form)[^>]*>.*?<\s*\/\s*\1\s*>/isu', '', (string) $value);
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', (string) $value);
        $value = strip_tags($value, '<p><br><ul><ol><li><strong><b><em><i><u><span><small><div><h3><h4>');
        $value = preg_replace('/<([a-z][a-z0-9]*)\b[^>]*>/iu', '<$1>', (string) $value);
        $value = preg_replace("/[ \t]{2,}/u", ' ', trim((string) $value));

        return mb_substr($value, 0, $maxLength);
    }

    private function purchasedPlanNames(?User $user, \Illuminate\Support\Collection $plans): array
    {
        if (! $user || ! Schema::hasTable('subscription_invoices')) {
            return [];
        }

        $email = trim((string) ($user->email ?? ''));
        if ($email === '') {
            return [];
        }

        $planIds = $plans->pluck('id')->map(fn ($id): int => (int) $id)->filter()->values();
        if ($planIds->isEmpty()) {
            return [];
        }

        $clientIds = DB::table('users')
            ->where('email', $email)
            ->where('firma', self::ORDER_FID)
            ->pluck('id')
            ->values()
            ->all();

        if ($clientIds === []) {
            return [];
        }

        return DB::table('subscription_invoices as si')
            ->join('customer_subscriptions as cs', 'cs.id', '=', 'si.subscription_id')
            ->where('cs.project_id', (int) self::ORDER_FID)
            ->whereIn('cs.client_id', $clientIds)
            ->whereIn('cs.plan_id', $planIds)
            ->where('si.status', 'paid')
            ->pluck('cs.plan_id')
            ->map(fn ($id): string => (string) $id)
            ->unique()
            ->values()
            ->all();
    }

    private function clientHasPaidPlan(int $clientId, int $planId): bool
    {
        return Schema::hasTable('subscription_invoices')
            && DB::table('subscription_invoices as si')
                ->join('customer_subscriptions as cs', 'cs.id', '=', 'si.subscription_id')
                ->where('cs.project_id', (int) self::ORDER_FID)
                ->where('cs.client_id', $clientId)
                ->where('cs.plan_id', $planId)
                ->where('si.status', 'paid')
                ->exists();
    }

    private function ensurePriceClient(?User $user, array $plan, array $contact): object
    {
        $email = trim((string) ($contact['email'] ?? $user->email ?? ''));
        if ($email === '') {
            throw ValidationException::withMessages(['customer_email' => 'Укажите email для заявки.']);
        }

        $client = DB::table('users')
            ->where('email', $email)
            ->where('firma', self::ORDER_FID)
            ->first();

        $description = $this->priceClientDescription((string) ($client->description ?? $user->description ?? ''), $plan);
        if ($client) {
            DB::table('users')->where('id', $client->id)->update([
                'name' => self::cleanText($contact['name'] ?: ($client->name ?? ''), 120),
                'phone' => self::cleanText($contact['phone'] ?: ($client->phone ?? ''), 50),
                'description' => $description,
                'updated_at' => now(),
            ]);

            return DB::table('users')->where('id', $client->id)->first();
        }

        $payload = [
            'name' => $contact['name'] ?: ($user->name ?? ''),
            'secondname' => $user->secondname ?? '',
            'fathername' => $user->fathername ?? '',
            'orgname' => $user->orgname ?? '',
            'email' => $email,
            'phone' => $contact['phone'] ?: ($user->phone ?? ''),
            'phone1' => $user->phone1 ?? '',
            'city' => $user->city ?? '',
            'region' => $user->region ?? '',
            'country' => $user->country ?? '',
            'idstatus' => ($user->idstatus ?? 0) ?: 1,
            'ustype' => ($user->ustype ?? 0) ?: ($user->idstatus ?? 1),
            'fid' => self::ORDER_FID,
            'firma' => self::ORDER_FID,
            'project_id' => $this->projectIdForOrderFirma(),
            'status' => $user->status ?? 1,
            'password' => $user && $user->password ? $user->password : Hash::make(Str::random(32)),
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

    private function userDisplayName(?User $user): string
    {
        if (! $user) {
            return '';
        }

        return trim(implode(' ', array_filter([
            $user->secondname ?? '',
            $user->name ?? '',
            $user->fathername ?? '',
        ]))) ?: trim((string) ($user->orgname ?? ''));
    }
}
