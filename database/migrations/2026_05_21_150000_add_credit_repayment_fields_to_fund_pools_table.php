<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fund_pools', function (Blueprint $table) {
            if (! Schema::hasColumn('fund_pools', 'requested_loan_term_months')) {
                $table->unsignedSmallInteger('requested_loan_term_months')->default(12)->after('requested_loan_rate_bps');
            }
            if (! Schema::hasColumn('fund_pools', 'repayment_total_usdc')) {
                $table->string('repayment_total_usdc', 80)->default('0')->after('requested_loan_claim_tx_digest');
            }
            if (! Schema::hasColumn('fund_pools', 'repayment_monthly_usdc')) {
                $table->string('repayment_monthly_usdc', 80)->default('0')->after('repayment_total_usdc');
            }
            if (! Schema::hasColumn('fund_pools', 'repayment_paid_usdc')) {
                $table->string('repayment_paid_usdc', 80)->default('0')->after('repayment_monthly_usdc');
            }
            if (! Schema::hasColumn('fund_pools', 'repayment_last_paid_at')) {
                $table->timestamp('repayment_last_paid_at')->nullable()->after('repayment_paid_usdc');
            }
        });
    }

    public function down(): void
    {
        Schema::table('fund_pools', function (Blueprint $table) {
            foreach ([
                'repayment_last_paid_at',
                'repayment_paid_usdc',
                'repayment_monthly_usdc',
                'repayment_total_usdc',
                'requested_loan_term_months',
            ] as $column) {
                if (Schema::hasColumn('fund_pools', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
