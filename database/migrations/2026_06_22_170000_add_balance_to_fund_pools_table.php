<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('fund_pools') || Schema::hasColumn('fund_pools', 'balance')) {
            return;
        }

        Schema::table('fund_pools', function (Blueprint $table): void {
            $table->decimal('balance', 24, 8)->default(0)->after('symbol');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('fund_pools') || ! Schema::hasColumn('fund_pools', 'balance')) {
            return;
        }

        Schema::table('fund_pools', function (Blueprint $table): void {
            $table->dropColumn('balance');
        });
    }
};
