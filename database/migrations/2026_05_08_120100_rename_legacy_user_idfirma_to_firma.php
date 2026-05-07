<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Таблиця `user` (legacy): у коді колонка тепер `firma` замість `idfirma`.
 * Оновлює вже розгорнуті БД зі старою схемою.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('user')) {
            return;
        }

        if (! Schema::hasColumn('user', 'idfirma')) {
            return;
        }

        if (Schema::hasColumn('user', 'firma')) {
            Schema::table('user', function (Blueprint $table) {
                $table->dropColumn('idfirma');
            });

            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement('ALTER TABLE `user` CHANGE COLUMN `idfirma` `firma` BIGINT UNSIGNED NOT NULL DEFAULT 0');

            return;
        }

        Schema::table('user', function (Blueprint $table) {
            $table->renameColumn('idfirma', 'firma');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('user') || ! Schema::hasColumn('user', 'firma') || Schema::hasColumn('user', 'idfirma')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement('ALTER TABLE `user` CHANGE COLUMN `firma` `idfirma` BIGINT UNSIGNED NOT NULL DEFAULT 0');

            return;
        }

        Schema::table('user', function (Blueprint $table) {
            $table->renameColumn('firma', 'idfirma');
        });
    }
};
