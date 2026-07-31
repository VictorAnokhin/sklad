<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('conf') || Schema::hasColumn('conf', 'city_id')) {
            return;
        }

        Schema::table('conf', function (Blueprint $table) {
            $table->unsignedBigInteger('city_id')->nullable()->after('address');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('conf') || !Schema::hasColumn('conf', 'city_id')) {
            return;
        }

        Schema::table('conf', function (Blueprint $table) {
            $table->dropColumn('city_id');
        });
    }
};
