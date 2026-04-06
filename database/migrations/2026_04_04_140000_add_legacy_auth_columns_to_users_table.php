<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'firma')) {
                $table->string('firma', 20)->default('0')->after('fid');
            }

            if (!Schema::hasColumn('users', 'idfirma')) {
                $table->string('idfirma', 20)->default('0')->after('firma');
            }

            if (!Schema::hasColumn('users', 'status')) {
                $table->tinyInteger('status')->default(1)->after('idfirma');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'status')) {
                $table->dropColumn('status');
            }

            if (Schema::hasColumn('users', 'idfirma')) {
                $table->dropColumn('idfirma');
            }

            if (Schema::hasColumn('users', 'firma')) {
                $table->dropColumn('firma');
            }
        });
    }
};
