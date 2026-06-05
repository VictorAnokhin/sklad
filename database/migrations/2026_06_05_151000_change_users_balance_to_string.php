<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        if (! Schema::hasColumn('users', 'balance')) {
            Schema::table('users', function (Blueprint $table) {
                $table->text('balance')->nullable();
            });

            return;
        }

        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE users MODIFY balance TEXT NULL');
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE users ALTER COLUMN balance TYPE TEXT USING balance::text');
            DB::statement('ALTER TABLE users ALTER COLUMN balance DROP DEFAULT');
        }
    }

    public function down(): void
    {
        // Intentionally left as a no-op because balances can contain multiple
        // currency segments like "100:USD;2500:UAH;" and cannot be safely cast
        // back to a single decimal amount.
    }
};
