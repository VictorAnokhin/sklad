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
            if (! Schema::hasColumn('bank_stock_analyses', 'dividend_frequency')) {
                $table->string('dividend_frequency', 20)->default('never')->after('dividend_ttm');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('bank_stock_analyses')) {
            return;
        }

        Schema::table('bank_stock_analyses', function (Blueprint $table) {
            if (Schema::hasColumn('bank_stock_analyses', 'dividend_frequency')) {
                $table->dropColumn('dividend_frequency');
            }
        });
    }
};
