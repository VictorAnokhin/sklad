<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('z_document')) {
            return;
        }

        Schema::table('z_document', function (Blueprint $table) {
            if (!Schema::hasColumn('z_document', 'currency_from')) {
                $table->string('currency_from', 10)->default('UAH')->after('summa2');
            }

            if (!Schema::hasColumn('z_document', 'currency_to')) {
                $table->string('currency_to', 10)->default('UAH')->after('currency_from');
            }

            if (!Schema::hasColumn('z_document', 'exchange_rate')) {
                $table->decimal('exchange_rate', 18, 8)->default(1)->after('currency_to');
            }

            if (!Schema::hasColumn('z_document', 'commission_amount')) {
                $table->decimal('commission_amount', 15, 2)->default(0)->after('exchange_rate');
            }

            if (!Schema::hasColumn('z_document', 'commission_currency')) {
                $table->string('commission_currency', 10)->default('')->after('commission_amount');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('z_document')) {
            return;
        }

        Schema::table('z_document', function (Blueprint $table) {
            foreach (['commission_currency', 'commission_amount', 'exchange_rate', 'currency_to', 'currency_from'] as $column) {
                if (Schema::hasColumn('z_document', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
