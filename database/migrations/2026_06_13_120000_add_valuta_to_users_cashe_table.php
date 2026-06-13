<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users_cashe')) {
            return;
        }

        Schema::table('users_cashe', function (Blueprint $table) {
            if (! Schema::hasColumn('users_cashe', 'valuta')) {
                $table->string('valuta', 10)->default('UAH')->after('balance');
            }
        });

        if (! $this->indexExists('users_cashe_userid_valuta_index')) {
            Schema::table('users_cashe', function (Blueprint $table) {
                $table->index(['userid', 'valuta'], 'users_cashe_userid_valuta_index');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('users_cashe')) {
            return;
        }

        Schema::table('users_cashe', function (Blueprint $table) {
            if ($this->indexExists('users_cashe_userid_valuta_index')) {
                $table->dropIndex('users_cashe_userid_valuta_index');
            }

            if (Schema::hasColumn('users_cashe', 'valuta')) {
                $table->dropColumn('valuta');
            }
        });
    }

    private function indexExists(string $indexName): bool
    {
        if (DB::getDriverName() !== 'mysql') {
            return false;
        }

        return DB::table('information_schema.statistics')
            ->where('table_schema', DB::getDatabaseName())
            ->where('table_name', 'users_cashe')
            ->where('index_name', $indexName)
            ->exists();
    }
};
