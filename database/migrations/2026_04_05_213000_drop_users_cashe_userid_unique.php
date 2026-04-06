<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE `users_cashe` DROP INDEX `users_cashe_userid_unique`');
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE `users_cashe` ADD UNIQUE KEY `users_cashe_userid_unique` (`userid`)');
        }
    }
};
