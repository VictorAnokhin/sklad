<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        foreach (['foto1', 'foto2', 'foto3', 'foto4', 'foto5'] as $column) {
            if (Schema::hasColumn('users', $column)) {
                DB::statement("ALTER TABLE `users` MODIFY `{$column}` VARCHAR(255) NOT NULL DEFAULT ''");
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        foreach (['foto1', 'foto2', 'foto3', 'foto4', 'foto5'] as $column) {
            if (Schema::hasColumn('users', $column)) {
                DB::statement("ALTER TABLE `users` MODIFY `{$column}` VARCHAR(60) NOT NULL DEFAULT ''");
            }
        }
    }
};
