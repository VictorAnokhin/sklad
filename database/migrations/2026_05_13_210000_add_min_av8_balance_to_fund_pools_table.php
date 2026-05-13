<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('fund_pools')) {
            return;
        }

        Schema::table('fund_pools', function (Blueprint $table) {
            if (! Schema::hasColumn('fund_pools', 'min_av8_balance')) {
                $table->string('min_av8_balance', 80)->default('0')->after('min_deposit_usdc');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('fund_pools')) {
            return;
        }

        Schema::table('fund_pools', function (Blueprint $table) {
            if (Schema::hasColumn('fund_pools', 'min_av8_balance')) {
                $table->dropColumn('min_av8_balance');
            }
        });
    }
};
