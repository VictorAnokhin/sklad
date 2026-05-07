<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'idfirma')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('idfirma');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('users') || Schema::hasColumn('users', 'idfirma')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->string('idfirma', 20)->default('0')->after('firma');
        });
    }
};
