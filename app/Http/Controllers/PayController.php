<?php

namespace App\Http\Controllers;

use App\Models\Firma;
use App\Models\User;
use App\Services\SubscriptionBillingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PayController extends Controller
{
    private const ORDER_FID = '2';

    public function index()
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 403);

        $clientIds = $this->priceClientIds($user);

        return view('pages.pay', [
            'plans' => $this->plans(),
            'invoices' => $this->userInvoices($clientIds),
            'paymentMethods' => $this->paymentMethods(),
            'companies' => $this->userCompanies($user),
        ]);
    }

    public function store(Request $request, SubscriptionBillingService $billing)
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 403);

        $planIds = $this->plans()->pluck('id')->map(fn ($id): int => (int) $id)->all();

        $validated = $request->validate([
            'plan_id' => ['required', 'integer', Rule::in($planIds)],
            'payment_method' => ['required', Rule::in(array_keys($this->paymentMethods()))],
        ]);

        $plan = $this->plans()->firstWhere('id', (int) $validated['plan_id']);
        abort_unless($plan, 404);

        $subscriptionId = $this->ensureSubscription($user, $plan);
        $paymentMethod = (string) $validated['payment_method'];

        DB::table('customer_subscriptions')->where('id', $subscriptionId)->update([
            'payment_method' => $paymentMethod,
            'updated_at' => now(),
        ]);

        $created = $billing->billSubscription($subscriptionId);
        $billing->enforceBlocks((int) self::ORDER_FID);

        if ($created) {
            DB::table('customer_subscriptions')->where('id', $subscriptionId)->update([
                'payment_method' => $paymentMethod,
                'updated_at' => now(),
            ]);
        }

        return redirect()
            ->route('pay')
            ->with($created ? 'success' : 'error', $created ? 'Начисление сформировано.' : 'Начисление уже существует или подписка неактивна.');
    }

    private function priceClientIds(User $user): array
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'email') || ! Schema::hasColumn('users', 'firma')) {
            return [];
        }

        $email = mb_strtolower(trim((string) ($user->email ?? '')));
        if ($email === '') {
            return [];
        }

        return DB::table('users')
            ->whereRaw('LOWER(TRIM(email)) = ?', [$email])
            ->where('firma', self::ORDER_FID)
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->filter()
            ->values()
            ->all();
    }

    private function plans()
    {
        if (! Schema::hasTable('subscription_plans')) {
            return collect();
        }

        $query = DB::table('subscription_plans')
            ->where('project_id', (int) self::ORDER_FID)
            ->where('active', true);

        if (Schema::hasColumn('subscription_plans', 'sort_order')) {
            $query->orderBy('sort_order');
        }

        return $query->orderBy('name')->orderBy('id')->get();
    }

    private function ensureSubscription(User $user, object $plan): int
    {
        $client = $this->ensurePriceClient($user);

        $existing = DB::table('customer_subscriptions')
            ->where('project_id', (int) self::ORDER_FID)
            ->where('client_id', (int) $client->id)
            ->where('plan_id', (int) $plan->id)
            ->whereIn('status', ['active', 'paused', 'blocked'])
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
            'notes' => '',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function ensurePriceClient(User $user): object
    {
        $email = trim((string) ($user->email ?? ''));
        abort_if($email === '', 422, 'У пользователя не указан email.');

        $client = DB::table('users')
            ->where('email', $email)
            ->where('firma', self::ORDER_FID)
            ->first();

        if ($client) {
            return $client;
        }

        $payload = [
            'name' => $user->name ?? '',
            'secondname' => $user->secondname ?? '',
            'fathername' => $user->fathername ?? '',
            'orgname' => $user->orgname ?? '',
            'email' => $email,
            'login' => $user->login ?? $email,
            'phone' => $user->phone ?? '',
            'phone1' => $user->phone1 ?? '',
            'city' => $user->city ?? '',
            'region' => $user->region ?? '',
            'country' => $user->country ?? '',
            'idstatus' => ($user->idstatus ?? 0) ?: 1,
            'ustype' => ($user->ustype ?? 0) ?: ($user->idstatus ?? 1),
            'fid' => self::ORDER_FID,
            'firma' => self::ORDER_FID,
            'project_id' => (int) self::ORDER_FID,
            'status' => $user->status ?? 1,
            'password' => ($user->password ?? '') ?: Hash::make(Str::random(32)),
            'pass' => ($user->pass ?? '') ?: Hash::make(Str::random(32)),
            'created_at' => now(),
            'updated_at' => now(),
        ];
        $payload = array_intersect_key($payload, array_flip(Schema::getColumnListing('users')));

        $clientId = DB::table('users')->insertGetId($payload);

        return DB::table('users')->where('id', $clientId)->first();
    }

    private function userSubscriptions(array $clientIds)
    {
        if ($clientIds === [] || ! Schema::hasTable('customer_subscriptions') || ! Schema::hasTable('subscription_plans')) {
            return collect();
        }

        $query = DB::table('customer_subscriptions as cs')
            ->join('subscription_plans as sp', 'sp.id', '=', 'cs.plan_id')
            ->where('cs.project_id', (int) self::ORDER_FID)
            ->whereIn('cs.client_id', $clientIds)
            ->whereIn('cs.status', ['active', 'paused', 'blocked'])
            ->select(
                'cs.*',
                'sp.name as plan_name',
                'sp.price as plan_price',
                'sp.currency as plan_currency',
                'sp.billing_period as plan_billing_period',
                'sp.interval_count as plan_interval_count'
            );

        if (Schema::hasColumn('subscription_plans', 'sort_order')) {
            $query->orderBy('sp.sort_order');
        }

        return $query->orderBy('sp.name')->orderByDesc('cs.id')->get();
    }

    private function userInvoices(array $clientIds)
    {
        if ($clientIds === [] || ! Schema::hasTable('subscription_invoices')) {
            return collect();
        }

        return DB::table('subscription_invoices as si')
            ->join('customer_subscriptions as cs', 'cs.id', '=', 'si.subscription_id')
            ->join('subscription_plans as sp', 'sp.id', '=', 'cs.plan_id')
            ->leftJoin('document as d', 'd.id', '=', 'si.document_id')
            ->where('cs.project_id', (int) self::ORDER_FID)
            ->whereIn('cs.client_id', $clientIds)
            ->select(
                'si.*',
                'sp.name as plan_name',
                'cs.payment_method',
                'd.num as document_num'
            )
            ->orderByDesc('si.created_at')
            ->orderByDesc('si.id')
            ->limit(200)
            ->get();
    }

    private function userCompanies(User $user)
    {
        if (! Schema::hasTable('firma')) {
            return collect();
        }

        return Firma::query()
            ->where(function ($query) use ($user): void {
                $query->where('userid', (int) $user->id);

                if (! empty($user->firma ?? null)) {
                    $query->orWhere('firma', $user->firma);
                }
            })
            ->orderBy('id')
            ->get();
    }

    private function paymentMethods(): array
    {
        return [
            'av8' => 'Оплата AV8',
            'bank_requisites' => 'Оплата на счет по реквизитам',
        ];
    }
}
