<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'balance')) {
            return;
        }

        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            DB::statement("UPDATE users SET balance = CONCAT(balance, ':UAH;') WHERE balance IS NOT NULL AND TRIM(balance) <> '' AND balance NOT LIKE '%:%' AND TRIM(balance) REGEXP '^-?[0-9]+(\\\\.[0-9]+)?$'");
        } elseif ($driver === 'pgsql') {
            DB::statement("UPDATE users SET balance = balance || ':UAH;' WHERE balance IS NOT NULL AND BTRIM(balance) <> '' AND balance NOT LIKE '%:%' AND BTRIM(balance) ~ '^-?[0-9]+(\\.[0-9]+)?$'");
        }
    }

    public function down(): void
    {
        // No-op: converting multicurrency strings back to one numeric balance is lossy.
    }
};
