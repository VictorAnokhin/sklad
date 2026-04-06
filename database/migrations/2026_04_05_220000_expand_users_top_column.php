<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'top')) {
            DB::statement("ALTER TABLE `users` MODIFY `top` int NOT NULL DEFAULT 0");
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'top')) {
            DB::statement("ALTER TABLE `users` MODIFY `top` tinyint NOT NULL DEFAULT 0");
        }
    }
};
