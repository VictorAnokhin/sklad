<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('users')) {
            return;
        }

        if ($this->indexExists('users_email_unique')) {
            DB::statement('ALTER TABLE `users` DROP INDEX `users_email_unique`');
        }

        if (Schema::hasColumn('users', 'email')
            && Schema::hasColumn('users', 'firma')
            && !$this->indexExists('users_email_firma_index')) {
            DB::statement('ALTER TABLE `users` ADD INDEX `users_email_firma_index` (`email`, `firma`)');
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('users')) {
            return;
        }

        if ($this->indexExists('users_email_firma_index')) {
            DB::statement('ALTER TABLE `users` DROP INDEX `users_email_firma_index`');
        }

        if (Schema::hasColumn('users', 'email') && !$this->indexExists('users_email_unique')) {
            DB::statement('ALTER TABLE `users` ADD UNIQUE INDEX `users_email_unique` (`email`)');
        }
    }

    private function indexExists(string $indexName): bool
    {
        $database = DB::getDatabaseName();

        return DB::table('information_schema.statistics')
            ->where('table_schema', $database)
            ->where('table_name', 'users')
            ->where('index_name', $indexName)
            ->exists();
    }
};
