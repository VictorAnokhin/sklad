<?php

namespace App\Http\Controllers;

use App\Models\Firma;
use App\Models\User;
use App\Services\SubscriptionBillingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class PayController extends Controller
{
    private const ORDER_FID = '2';

    public function index()
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 403);

        $clientIds = $this->priceClientIds($user);
        $subscriptions = $this->userSubscriptions($clientIds);

        return view('pages.pay', [
            'subscriptions' => $subscriptions,
            'invoices' => $this->userInvoices($clientIds),
            'paymentMethods' => $this->paymentMethods(),
            'companies' => $this->userCompanies($user),
        ]);
    }

    public function store(Request $request, SubscriptionBillingService $billing)
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 403);

        $clientIds = $this->priceClientIds($user);
        $subscriptionIds = $this->userSubscriptions($clientIds)->pluck('id')->map(fn ($id): int => (int) $id)->all();

        $validated = $request->validate([
            'subscription_id' => ['required', 'integer', Rule::in($subscriptionIds)],
            'payment_method' => ['required', Rule::in(array_keys($this->paymentMethods()))],
        ]);

        $subscriptionId = (int) $validated['subscription_id'];
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
