<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('subscription_plans') && ! Schema::hasColumn('subscription_plans', 'sort_order')) {
            Schema::table('subscription_plans', function (Blueprint $table): void {
                $table->unsignedInteger('sort_order')->default(100)->after('description')->index();
            });
        }

        if (Schema::hasTable('customer_subscriptions') && ! Schema::hasColumn('customer_subscriptions', 'accesses')) {
            Schema::table('customer_subscriptions', function (Blueprint $table): void {
                $table->json('accesses')->nullable()->after('auto_close_if_paid');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('customer_subscriptions') && Schema::hasColumn('customer_subscriptions', 'accesses')) {
            Schema::table('customer_subscriptions', function (Blueprint $table): void {
                $table->dropColumn('accesses');
            });
        }

        if (Schema::hasTable('subscription_plans') && Schema::hasColumn('subscription_plans', 'sort_order')) {
            Schema::table('subscription_plans', function (Blueprint $table): void {
                $table->dropIndex(['sort_order']);
                $table->dropColumn('sort_order');
            });
        }
    }
};
