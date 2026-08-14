<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('subscription_plans') && ! Schema::hasColumn('subscription_plans', 'subtitle')) {
            Schema::table('subscription_plans', function (Blueprint $table): void {
                $table->string('subtitle', 255)->nullable()->after('name');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('subscription_plans') && Schema::hasColumn('subscription_plans', 'subtitle')) {
            Schema::table('subscription_plans', function (Blueprint $table): void {
                $table->dropColumn('subtitle');
            });
        }
    }
};
