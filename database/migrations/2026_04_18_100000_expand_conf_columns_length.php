<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('conf')) {
            DB::statement('ALTER TABLE `conf` MODIFY `color` VARCHAR(255) DEFAULT ""');
            DB::statement('ALTER TABLE `conf` MODIFY `constanta` VARCHAR(255) DEFAULT "0"');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('conf')) {
            DB::statement('ALTER TABLE `conf` MODIFY `color` VARCHAR(20) DEFAULT ""');
            DB::statement('ALTER TABLE `conf` MODIFY `constanta` VARCHAR(2) DEFAULT "0"');
        }
    }
};
