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
            if (! Schema::hasColumn('fund_pools', 'pool_accounting_id')) {
                $table->string('pool_accounting_id', 80)->default('')->after('pool_object_id')->index();
            }

            if (! Schema::hasColumn('fund_pools', 'basket_vault_id')) {
                $table->string('basket_vault_id', 80)->default('')->after('pool_accounting_id')->index();
            }

            if (! Schema::hasColumn('fund_pools', 'liquidity_wallet_address')) {
                $table->string('liquidity_wallet_address', 80)->default('')->after('basket_vault_id');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('fund_pools')) {
            return;
        }

        Schema::table('fund_pools', function (Blueprint $table) {
            if (Schema::hasColumn('fund_pools', 'liquidity_wallet_address')) {
                $table->dropColumn('liquidity_wallet_address');
            }

            if (Schema::hasColumn('fund_pools', 'basket_vault_id')) {
                $table->dropColumn('basket_vault_id');
            }

            if (Schema::hasColumn('fund_pools', 'pool_accounting_id')) {
                $table->dropColumn('pool_accounting_id');
            }
        });
    }
};
