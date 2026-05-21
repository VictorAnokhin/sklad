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
            if (! Schema::hasColumn('fund_pools', 'requested_loan_claimed_at')) {
                $table->timestamp('requested_loan_claimed_at')->nullable()->after('requested_loan_rate_bps');
            }
            if (! Schema::hasColumn('fund_pools', 'requested_loan_claim_tx_digest')) {
                $table->string('requested_loan_claim_tx_digest', 120)->default('')->after('requested_loan_claimed_at');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('fund_pools')) {
            return;
        }

        Schema::table('fund_pools', function (Blueprint $table) {
            if (Schema::hasColumn('fund_pools', 'requested_loan_claim_tx_digest')) {
                $table->dropColumn('requested_loan_claim_tx_digest');
            }
            if (Schema::hasColumn('fund_pools', 'requested_loan_claimed_at')) {
                $table->dropColumn('requested_loan_claimed_at');
            }
        });
    }
};
