<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('conf')) {
            DB::statement('ALTER TABLE `conf` MODIFY `vision` VARCHAR(255) DEFAULT "1"');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('conf')) {
            DB::statement('ALTER TABLE `conf` MODIFY `vision` VARCHAR(2) DEFAULT "1"');
        }
    }
};
