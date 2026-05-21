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
            if (! Schema::hasColumn('fund_pools', 'requested_loan_usdc')) {
                $table->string('requested_loan_usdc', 80)->default('0')->after('credit_request_status');
            }
            if (! Schema::hasColumn('fund_pools', 'requested_loan_rate_bps')) {
                $table->unsignedSmallInteger('requested_loan_rate_bps')->default(500)->after('requested_loan_usdc');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('fund_pools')) {
            return;
        }

        Schema::table('fund_pools', function (Blueprint $table) {
            if (Schema::hasColumn('fund_pools', 'requested_loan_rate_bps')) {
                $table->dropColumn('requested_loan_rate_bps');
            }
            if (Schema::hasColumn('fund_pools', 'requested_loan_usdc')) {
                $table->dropColumn('requested_loan_usdc');
            }
        });
    }
};
