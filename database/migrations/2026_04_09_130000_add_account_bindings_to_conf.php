<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('conf')) {
            return;
        }

        Schema::table('conf', function (Blueprint $table) {
            if (!Schema::hasColumn('conf', 'debit_account_id')) {
                $table->unsignedBigInteger('debit_account_id')->nullable()->after('doc');
            }

            if (!Schema::hasColumn('conf', 'credit_account_id')) {
                $table->unsignedBigInteger('credit_account_id')->nullable()->after('debit_account_id');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('conf')) {
            return;
        }

        Schema::table('conf', function (Blueprint $table) {
            if (Schema::hasColumn('conf', 'credit_account_id')) {
                $table->dropColumn('credit_account_id');
            }

            if (Schema::hasColumn('conf', 'debit_account_id')) {
                $table->dropColumn('debit_account_id');
            }
        });
    }
};
