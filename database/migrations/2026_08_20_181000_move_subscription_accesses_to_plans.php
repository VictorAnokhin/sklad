<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('subscription_plans') && ! Schema::hasColumn('subscription_plans', 'accesses')) {
            Schema::table('subscription_plans', function (Blueprint $table): void {
                $table->json('accesses')->nullable()->after('blocked_features');
            });
        }

        if (
            Schema::hasTable('subscription_plans')
            && Schema::hasColumn('subscription_plans', 'accesses')
            && Schema::hasTable('customer_subscriptions')
            && Schema::hasColumn('customer_subscriptions', 'accesses')
        ) {
            DB::table('subscription_plans as sp')
                ->join('customer_subscriptions as cs', 'cs.plan_id', '=', 'sp.id')
                ->whereNotNull('cs.accesses')
                ->whereNull('sp.accesses')
                ->update(['sp.accesses' => DB::raw('cs.accesses')]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('subscription_plans') && Schema::hasColumn('subscription_plans', 'accesses')) {
            Schema::table('subscription_plans', function (Blueprint $table): void {
                $table->dropColumn('accesses');
            });
        }
    }
};
