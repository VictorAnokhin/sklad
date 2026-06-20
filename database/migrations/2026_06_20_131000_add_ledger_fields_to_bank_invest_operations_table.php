<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('bank_invest_operations')) {
            return;
        }

        Schema::table('bank_invest_operations', function (Blueprint $table): void {
            if (! Schema::hasColumn('bank_invest_operations', 'ledger_transaction_id')) {
                $table->unsignedBigInteger('ledger_transaction_id')->nullable()->after('value_usd')->index();
            }
            if (! Schema::hasColumn('bank_invest_operations', 'status')) {
                $table->string('status', 32)->default('pending')->after('ledger_transaction_id')->index();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('bank_invest_operations')) {
            return;
        }

        Schema::table('bank_invest_operations', function (Blueprint $table): void {
            if (Schema::hasColumn('bank_invest_operations', 'status')) {
                $table->dropColumn('status');
            }
            if (Schema::hasColumn('bank_invest_operations', 'ledger_transaction_id')) {
                $table->dropColumn('ledger_transaction_id');
            }
        });
    }
};
