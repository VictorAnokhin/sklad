<?php

namespace App\Http\Controllers;

use App\Services\SubscriptionBillingService;
use App\Support\HoldingScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class SubscriptionController extends Controller
{
    public function index()
    {
        $fid = $this->activeFid();
        $this->ensureTables();
        app(SubscriptionBillingService::class)->enforceBlocks((int) $fid);

        return view('subscriptions.index', [
            'plans' => $this->plans($fid),
            'subscriptions' => $this->subscriptions($fid),
            'invoices' => $this->invoices($fid),
            'clients' => $this->clients($fid),
            'products' => $this->products($fid),
            'fid' => $fid,
        ]);
    }

    public function storePlan(Request $request)
    {
        $fid = $this->activeFid();
        $validated = $this->validatePlan($request);

        DB::table('subscription_plans')->insert(array_merge($validated, [
            'project_id' => (int) $fid,
            'blocked_features' => json_encode($request->input('blocked_features', []), JSON_UNESCAPED_UNICODE),
            'block_on_overdue' => $request->boolean('block_on_overdue'),
            'active' => $request->boolean('active', true),
            'created_at' => now(),
            'updated_at' => now(),
        ]));

        return redirect()->route('subscriptions.index')->with('success', 'Тариф подписки создан');
    }

    public function updatePlan(Request $request, int $plan)
    {
        $fid = $this->activeFid();
        abort_unless($this->planExists($fid, $plan), 404);
        $validated = $this->validatePlan($request);

        DB::table('subscription_plans')->where('id', $plan)->update(array_merge($validated, [
            'blocked_features' => json_encode($request->input('blocked_features', []), JSON_UNESCAPED_UNICODE),
            'block_on_overdue' => $request->boolean('block_on_overdue'),
            'active' => $request->boolean('active'),
            'updated_at' => now(),
        ]));

        return redirect()->route('subscriptions.index')->with('success', 'Тариф обновлен');
    }

    public function destroyPlan(int $plan)
    {
        $fid = $this->activeFid();
        abort_unless($this->planExists($fid, $plan), 404);

        if (DB::table('customer_subscriptions')->where('plan_id', $plan)->exists()) {
            return redirect()->route('subscriptions.index')->with('error', 'Тариф нельзя удалить, пока он связан с подписками клиентов.');
        }

        DB::transaction(function () use ($plan): void {
            DB::table('subscription_plan_items')->where('plan_id', $plan)->delete();
            DB::table('subscription_plans')->where('id', $plan)->delete();
        });

        return redirect()->route('subscriptions.index')->with('success', 'Тариф удален');
    }

    public function storePlanItem(Request $request, int $plan)
    {
        $fid = $this->activeFid();
        abort_unless($this->planExists($fid, $plan), 404);
        $validated = $request->validate([
            'product_id' => ['required', 'integer', 'min:1'],
            'item_type' => ['required', Rule::in(['goods', 'service'])],
            'quantity' => ['required', 'numeric', 'min:0.001', 'max:999999'],
            'price' => ['required', 'numeric', 'min:0', 'max:999999999'],
        ]);

        DB::table('subscription_plan_items')->insert([
            'plan_id' => $plan,
            'product_id' => (int) $validated['product_id'],
            'item_type' => $validated['item_type'],
            'quantity' => $validated['quantity'],
            'price' => $validated['price'],
            'sort' => 100,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('subscriptions.index')->with('success', 'Позиция добавлена в тариф');
    }

    public function destroyPlanItem(int $item)
    {
        $fid = $this->activeFid();
        $exists = DB::table('subscription_plan_items as spi')
            ->join('subscription_plans as sp', 'sp.id', '=', 'spi.plan_id')
            ->where('spi.id', $item)
            ->where('sp.project_id', (int) $fid)
            ->exists();
        abort_unless($exists, 404);

        DB::table('subscription_plan_items')->where('id', $item)->delete();

        return redirect()->route('subscriptions.index')->with('success', 'Позиция удалена');
    }

    public function storeSubscription(Request $request)
    {
        $fid = $this->activeFid();
        $validated = $this->validateSubscription($request, $fid);
        $startsAt = $validated['starts_at'] ?: now()->toDateString();

        DB::table('customer_subscriptions')->insert([
            'project_id' => (int) $fid,
            'client_id' => (int) $validated['client_id'],
            'plan_id' => (int) $validated['plan_id'],
            'status' => $validated['status'],
            'payment_status' => 'paid',
            'starts_at' => $startsAt,
            'next_billing_at' => $validated['next_billing_at'] ?: $startsAt,
            'ends_at' => $validated['ends_at'] ?: null,
            'payment_method' => (string) ($validated['payment_method'] ?? ''),
            'auto_create_invoice' => $request->boolean('auto_create_invoice', true),
            'auto_close_if_paid' => $request->boolean('auto_close_if_paid'),
            'notes' => $validated['notes'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('subscriptions.index', ['tab' => 'customers'])->with('success', 'Подписка клиента создана');
    }

    public function updateSubscription(Request $request, int $subscription)
    {
        $fid = $this->activeFid();
        abort_unless($this->subscriptionExists($fid, $subscription), 404);
        $validated = $this->validateSubscription($request, $fid);

        DB::table('customer_subscriptions')->where('id', $subscription)->update([
            'client_id' => (int) $validated['client_id'],
            'plan_id' => (int) $validated['plan_id'],
            'status' => $validated['status'],
            'starts_at' => $validated['starts_at'] ?: null,
            'next_billing_at' => $validated['next_billing_at'] ?: null,
            'ends_at' => $validated['ends_at'] ?: null,
            'payment_method' => (string) ($validated['payment_method'] ?? ''),
            'auto_create_invoice' => $request->boolean('auto_create_invoice'),
            'auto_close_if_paid' => $request->boolean('auto_close_if_paid'),
            'notes' => $validated['notes'] ?? null,
            'updated_at' => now(),
        ]);

        return redirect()->route('subscriptions.index', ['tab' => 'customers'])->with('success', 'Подписка обновлена');
    }

    public function destroySubscription(int $subscription)
    {
        $fid = $this->activeFid();
        abort_unless($this->subscriptionExists($fid, $subscription), 404);

        DB::transaction(function () use ($subscription): void {
            DB::table('subscription_invoices')->where('subscription_id', $subscription)->delete();
            DB::table('customer_subscriptions')->where('id', $subscription)->delete();
        });

        return redirect()->route('subscriptions.index', ['tab' => 'customers'])->with('success', 'Подписка удалена');
    }

    public function bill(int $subscription, SubscriptionBillingService $billing)
    {
        $fid = $this->activeFid();
        abort_unless($this->subscriptionExists($fid, $subscription), 404);
        $created = $billing->billSubscription($subscription);
        $billing->enforceBlocks((int) $fid);

        return redirect()->route('subscriptions.index', ['tab' => 'customers'])->with($created ? 'success' : 'error', $created ? 'Начисление создано' : 'Начисление уже существует или подписка неактивна');
    }

    public function markInvoicePaid(int $invoice, SubscriptionBillingService $billing)
    {
        $fid = $this->activeFid();
        abort_unless($this->invoiceExists($fid, $invoice), 404);
        $billing->markInvoicePaid($invoice);
        $billing->enforceBlocks((int) $fid);

        return redirect()->route('subscriptions.index', ['tab' => 'invoices'])->with('success', 'Начисление отмечено оплаченным');
    }

    private function validatePlan(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'billing_period' => ['required', Rule::in(['week', 'month', 'quarter', 'year'])],
            'interval_count' => ['required', 'integer', 'min:1', 'max:60'],
            'price' => ['required', 'numeric', 'min:0', 'max:999999999'],
            'currency' => ['required', 'string', 'max:10'],
            'payment_due_days' => ['required', 'integer', 'min:0', 'max:365'],
            'grace_days' => ['required', 'integer', 'min:0', 'max:365'],
            'blocked_features' => ['nullable', 'array'],
            'blocked_features.*' => ['string', 'max:80'],
        ]);
    }

    private function validateSubscription(Request $request, string $fid): array
    {
        return $request->validate([
            'client_id' => ['required', 'integer', Rule::exists('users', 'id')->whereIn('firma', HoldingScope::projectIdsFor($fid))],
            'plan_id' => ['required', 'integer', Rule::exists('subscription_plans', 'id')->where('project_id', (int) $fid)],
            'status' => ['required', Rule::in(['active', 'paused', 'cancelled', 'expired', 'blocked'])],
            'starts_at' => ['nullable', 'date'],
            'next_billing_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date'],
            'payment_method' => ['nullable', 'string', 'max:60'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
    }

    private function plans(string $fid)
    {
        $plans = DB::table('subscription_plans')->where('project_id', (int) $fid)->orderByDesc('active')->orderBy('name')->get();
        $items = DB::table('subscription_plan_items as spi')
            ->leftJoin('comp as c', 'c.id', '=', 'spi.product_id')
            ->whereIn('spi.plan_id', $plans->pluck('id'))
            ->select('spi.*', DB::raw("COALESCE(NULLIF(c.name, ''), NULLIF(c.nickname, ''), CONCAT('Товар #', spi.product_id)) as product_name"))
            ->orderBy('spi.sort')
            ->orderBy('spi.id')
            ->get()
            ->groupBy('plan_id');

        return $plans->map(function (object $plan) use ($items): object {
            $plan->items = $items->get($plan->id, collect());
            return $plan;
        });
    }

    private function subscriptions(string $fid)
    {
        $userColumns = Schema::hasTable('users') ? Schema::getColumnListing('users') : [];
        $userColumn = static fn (string $column, string $alias) => in_array($column, $userColumns, true)
            ? "u.{$column} as {$alias}"
            : DB::raw("'' as {$alias}");

        return DB::table('customer_subscriptions as cs')
            ->join('subscription_plans as sp', 'sp.id', '=', 'cs.plan_id')
            ->join('users as u', 'u.id', '=', 'cs.client_id')
            ->where('cs.project_id', (int) $fid)
            ->select(
                'cs.*',
                'sp.name as plan_name',
                $userColumn('orgname', 'client_orgname'),
                $userColumn('secondname', 'client_secondname'),
                $userColumn('name', 'client_firstname'),
                $userColumn('email', 'client_email'),
                $userColumn('phone', 'client_phone'),
                $userColumn('region', 'client_region'),
                $userColumn('city', 'client_city'),
                $userColumn('poshta', 'client_poshta'),
                DB::raw("COALESCE(NULLIF(u.orgname, ''), CONCAT_WS(' ', u.secondname, u.name), u.email, CONCAT('Клиент #', u.id)) as client_name")
            )
            ->orderByDesc('cs.updated_at')
            ->orderByDesc('cs.id')
            ->get();
    }

    private function invoices(string $fid)
    {
        return DB::table('subscription_invoices as si')
            ->join('customer_subscriptions as cs', 'cs.id', '=', 'si.subscription_id')
            ->join('subscription_plans as sp', 'sp.id', '=', 'cs.plan_id')
            ->join('users as u', 'u.id', '=', 'cs.client_id')
            ->leftJoin('document as d', 'd.id', '=', 'si.document_id')
            ->where('cs.project_id', (int) $fid)
            ->select('si.*', 'sp.name as plan_name', 'd.num as document_num', DB::raw("COALESCE(NULLIF(u.orgname, ''), CONCAT_WS(' ', u.secondname, u.name), u.email, CONCAT('Клиент #', u.id)) as client_name"))
            ->orderByDesc('si.created_at')
            ->orderByDesc('si.id')
            ->limit(200)
            ->get();
    }

    private function clients(string $fid)
    {
        return Schema::hasTable('users')
            ? DB::table('users')->whereIn('firma', HoldingScope::projectIdsFor($fid))->orderBy('orgname')->orderBy('secondname')->limit(500)->get(['id', 'orgname', 'secondname', 'name', 'email'])
            : collect();
    }

    private function products(string $fid)
    {
        return Schema::hasTable('comp')
            ? DB::table('comp')->where(function ($query) use ($fid): void {
                $query->where('firma', $fid)->orWhere('constanta', 1);
            })->orderBy('name')->limit(500)->get(['id', 'name', 'nickname', 'pay'])
            : collect();
    }

    private function planExists(string $fid, int $plan): bool
    {
        return DB::table('subscription_plans')->where('id', $plan)->where('project_id', (int) $fid)->exists();
    }

    private function subscriptionExists(string $fid, int $subscription): bool
    {
        return DB::table('customer_subscriptions')->where('id', $subscription)->where('project_id', (int) $fid)->exists();
    }

    private function invoiceExists(string $fid, int $invoice): bool
    {
        return DB::table('subscription_invoices as si')
            ->join('customer_subscriptions as cs', 'cs.id', '=', 'si.subscription_id')
            ->where('si.id', $invoice)
            ->where('cs.project_id', (int) $fid)
            ->exists();
    }

    private function activeFid(): string
    {
        return trim((string) session('fid', '')) ?: (string) (Auth::user()->firma ?? '');
    }

    private function ensureTables(): void
    {
        abort_unless(Schema::hasTable('subscription_plans'), 503, 'Сначала выполните миграции подписок.');
    }
}
