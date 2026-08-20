<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SyncLegacySessionFromAuth
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check()) {
            $user = Auth::user();
            if ($user instanceof User) {
                self::applyWorkspaceSession($user);
            }
        }

        return $next($request);
    }

    /**
     * Оновлює legacy-ключі сесії (userid, fid, …) відповідно до поточного User та активного проєкту.
     */
    public static function applyWorkspaceSession(User $user): void
    {
        $defaultFirma = $user->firma ?: $user->fid ?: 0;
        $firma = session()->has('fid') ? session('fid') : $defaultFirma;
        $idstatus = $user->idstatus ?: $user->ustype ?: 1;

        session([
            'id' => $user->id,
            'fid' => $firma,
            'userid' => $user->id,
            'idstatus' => $idstatus,
            'status' => $idstatus,
            'doc' => (int) $idstatus === 2 ? 'WO1' : 'ZOUT',
            'idkassa' => $user->idkassa,
            'idsklad' => $user->idsklad,
            'idreestr' => $user->idreestr,
            'domen' => $user->domen,
            'bonus' => $user->bonus,
            'balans' => $user->balans,
            'name1' => $user->name,
            'fname' => $user->fathername,
            'login' => method_exists($user, 'legacyLoginValue') ? $user->legacyLoginValue() : ($user->login ?? $user->email ?? $user->phone ?? ''),
        ]);

        self::ensureDefaultSubscription($user, (int) $firma);
    }

    private static function ensureDefaultSubscription(User $user, int $projectId): void
    {
        if ($projectId <= 0 || (int) ($user->id ?? 0) <= 0) {
            return;
        }

        if (
            ! Schema::hasTable('subscription_plans')
            || ! Schema::hasTable('customer_subscriptions')
        ) {
            return;
        }

        $hasSubscription = DB::table('customer_subscriptions')
            ->where('project_id', $projectId)
            ->where('client_id', (int) $user->id)
            ->exists();

        if ($hasSubscription) {
            return;
        }

        $planQuery = DB::table('subscription_plans')
            ->where('project_id', $projectId)
            ->where('active', true);

        if (Schema::hasColumn('subscription_plans', 'sort_order')) {
            $planQuery->orderBy('sort_order');
        }

        $plan = $planQuery->orderBy('id')->first();
        if (! $plan) {
            return;
        }

        $today = now()->toDateString();
        $payload = [
            'project_id' => $projectId,
            'client_id' => (int) $user->id,
            'plan_id' => (int) $plan->id,
            'status' => 'active',
            'payment_status' => 'paid',
            'starts_at' => $today,
            'next_billing_at' => $today,
            'payment_method' => '',
            'auto_create_invoice' => true,
            'auto_close_if_paid' => false,
            'notes' => 'Назначен автоматически при авторизации.',
            'created_at' => now(),
            'updated_at' => now(),
        ];

        DB::table('customer_subscriptions')->insert($payload);
    }
}
