<?php

namespace App\Http\Controllers;

use App\Models\Firma;
use App\Models\Project;
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

        $projects = $this->createdProjects($user);

        return view('pages.pay', [
            'projects' => $projects,
            'plans' => $this->plans(),
            'invoices' => $this->userInvoices($projects->pluck('id')->map(fn ($id): int => (int) $id)->all()),
            'paymentMethods' => $this->paymentMethods(),
            'companies' => $this->userCompanies($user),
        ]);
    }

    public function store(Request $request, SubscriptionBillingService $billing)
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 403);

        $planIds = $this->plans()->pluck('id')->map(fn ($id): int => (int) $id)->all();
        $projectIds = $this->createdProjects($user)->pluck('id')->map(fn ($id): int => (int) $id)->all();

        $validated = $request->validate([
            'project_id' => ['required', 'integer', Rule::in($projectIds)],
            'plan_id' => ['required', 'integer', Rule::in($planIds)],
            'payment_method' => ['required', Rule::in(array_keys($this->paymentMethods()))],
        ]);

        $plan = $this->plans()->firstWhere('id', (int) $validated['plan_id']);
        abort_unless($plan, 404);
        $project = $this->createdProjects($user)->firstWhere('id', (int) $validated['project_id']);
        abort_unless($project, 404);

        $subscriptionId = $this->ensureSubscription($user, $project, $plan);
        $paymentMethod = (string) $validated['payment_method'];

        DB::table('customer_subscriptions')->where('id', $subscriptionId)->update([
            'payment_method' => $paymentMethod,
            'updated_at' => now(),
        ]);

        $created = $billing->billSubscription($subscriptionId);
        $billing->enforceBlocks((int) $project->id);

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

    private function identityUserIds(User $user): \Illuminate\Support\Collection
    {
        $ids = collect([(int) $user->id]);

        $email = mb_strtolower(trim((string) ($user->email ?? '')));
        if ($email !== '' && Schema::hasTable('users') && Schema::hasColumn('users', 'email')) {
            $ids = $ids->merge(
                DB::table('users')
                    ->whereRaw('LOWER(TRIM(email)) = ?', [$email])
                    ->pluck('id')
                    ->map(fn ($id): int => (int) $id)
            );
        }

        return $ids
            ->filter()
            ->values()
            ->unique();
    }

    private function createdProjects(User $user)
    {
        if (! Schema::hasTable('project') || ! Schema::hasColumn('project', 'userid')) {
            return collect();
        }

        return Project::query()
            ->whereIn('userid', $this->identityUserIds($user)->all())
            ->orderBy('id')
            ->get(['id', 'name', 'project_type', 'userid']);
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

    private function ensureSubscription(User $user, object $project, object $plan): int
    {
        $clientId = (int) ($project->userid ?: $user->id);

        $existing = DB::table('customer_subscriptions')
            ->where('project_id', (int) $project->id)
            ->where('client_id', $clientId)
            ->where('plan_id', (int) $plan->id)
            ->whereIn('status', ['active', 'paused', 'blocked'])
            ->orderByDesc('id')
            ->first();

        if ($existing) {
            return (int) $existing->id;
        }

        $today = now()->toDateString();

        return (int) DB::table('customer_subscriptions')->insertGetId([
            'project_id' => (int) $project->id,
            'client_id' => $clientId,
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

    private function userInvoices(array $projectIds)
    {
        if ($projectIds === [] || ! Schema::hasTable('subscription_invoices')) {
            return collect();
        }

        return DB::table('subscription_invoices as si')
            ->join('customer_subscriptions as cs', 'cs.id', '=', 'si.subscription_id')
            ->join('subscription_plans as sp', 'sp.id', '=', 'cs.plan_id')
            ->leftJoin('project as p', 'p.id', '=', 'cs.project_id')
            ->leftJoin('document as d', 'd.id', '=', 'si.document_id')
            ->whereIn('cs.project_id', $projectIds)
            ->select(
                'si.*',
                'sp.name as plan_name',
                'cs.project_id as subscription_project_id',
                'p.name as project_name',
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
