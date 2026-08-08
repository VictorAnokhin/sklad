<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('bank_stock_analysis_multipliers')) {
            return;
        }

        if (! Schema::hasColumn('bank_stock_analysis_multipliers', 'block')) {
            Schema::table('bank_stock_analysis_multipliers', function (Blueprint $table) {
                $table->string('block', 40)->default('cheapness')->after('description')->index();
            });
        }

        DB::table('bank_stock_analysis_multipliers')
            ->whereBetween('sort_order', [50, 69])
            ->update(['block' => 'debt']);
        DB::table('bank_stock_analysis_multipliers')
            ->whereBetween('sort_order', [70, 89])
            ->update(['block' => 'efficiency']);
        DB::table('bank_stock_analysis_multipliers')
            ->whereBetween('sort_order', [90, 109])
            ->update(['block' => 'growth']);
        DB::table('bank_stock_analysis_multipliers')
            ->whereNotBetween('sort_order', [50, 109])
            ->update(['block' => 'cheapness']);
    }

    public function down(): void
    {
        if (Schema::hasTable('bank_stock_analysis_multipliers') && Schema::hasColumn('bank_stock_analysis_multipliers', 'block')) {
            Schema::table('bank_stock_analysis_multipliers', function (Blueprint $table) {
                $table->dropColumn('block');
            });
        }
    }
};
