<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('bank_stock_analyses')) {
            return;
        }

        Schema::table('bank_stock_analyses', function (Blueprint $table) {
            if (! Schema::hasColumn('bank_stock_analyses', 'net_debt_ebitda')) {
                $table->string('net_debt_ebitda', 80)->nullable()->after('current_ratio');
            }
            if (! Schema::hasColumn('bank_stock_analyses', 'roe')) {
                $table->string('roe', 80)->nullable()->after('net_debt_ebitda');
            }
            if (! Schema::hasColumn('bank_stock_analyses', 'roic')) {
                $table->string('roic', 80)->nullable()->after('roe');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('bank_stock_analyses')) {
            return;
        }

        Schema::table('bank_stock_analyses', function (Blueprint $table) {
            foreach (['roic', 'roe', 'net_debt_ebitda'] as $column) {
                if (Schema::hasColumn('bank_stock_analyses', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
