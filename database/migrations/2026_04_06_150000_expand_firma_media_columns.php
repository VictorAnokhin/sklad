<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE `firma` MODIFY `pidpys` VARCHAR(255) NOT NULL DEFAULT ''");
        DB::statement("ALTER TABLE `firma` MODIFY `pechat` VARCHAR(255) NOT NULL DEFAULT ''");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE `firma` MODIFY `pidpys` VARCHAR(30) NOT NULL DEFAULT ''");
        DB::statement("ALTER TABLE `firma` MODIFY `pechat` VARCHAR(30) NOT NULL DEFAULT ''");
    }
};
