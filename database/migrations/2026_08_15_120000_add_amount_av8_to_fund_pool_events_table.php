<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('fund_pool_events') || Schema::hasColumn('fund_pool_events', 'amount_av8')) {
            return;
        }

        Schema::table('fund_pool_events', function (Blueprint $table) {
            $table->string('amount_av8', 80)->default('0')->after('amount_usdc');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('fund_pool_events') || ! Schema::hasColumn('fund_pool_events', 'amount_av8')) {
            return;
        }

        Schema::table('fund_pool_events', function (Blueprint $table) {
            $table->dropColumn('amount_av8');
        });
    }
};
