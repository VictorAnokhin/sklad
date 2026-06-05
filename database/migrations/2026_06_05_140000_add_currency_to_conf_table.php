<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('conf') || Schema::hasColumn('conf', 'currency')) {
            return;
        }

        Schema::table('conf', function (Blueprint $table) {
            $table->string('currency', 10)->default('UAH')->after('value2');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('conf') || !Schema::hasColumn('conf', 'currency')) {
            return;
        }

        Schema::table('conf', function (Blueprint $table) {
            $table->dropColumn('currency');
        });
    }
};
