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
            if (! Schema::hasColumn('fund_pools', 'is_default_deposit')) {
                $table->boolean('is_default_deposit')->default(false)->after('active')->index();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('fund_pools')) {
            return;
        }

        Schema::table('fund_pools', function (Blueprint $table) {
            if (Schema::hasColumn('fund_pools', 'is_default_deposit')) {
                $table->dropColumn('is_default_deposit');
            }
        });
    }
};
