<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('bank_stock_analysis_multipliers')) {
            return;
        }

        if (! Schema::hasColumn('bank_stock_analysis_multipliers', 'table_visible')) {
            Schema::table('bank_stock_analysis_multipliers', function (Blueprint $table) {
                $table->boolean('table_visible')->default(false)->after('block')->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('bank_stock_analysis_multipliers') && Schema::hasColumn('bank_stock_analysis_multipliers', 'table_visible')) {
            Schema::table('bank_stock_analysis_multipliers', function (Blueprint $table) {
                $table->dropColumn('table_visible');
            });
        }
    }
};
