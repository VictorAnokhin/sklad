<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('fund_pools')) {
            return;
        }

        Schema::table('fund_pools', function (Blueprint $table) {
            foreach (['ua', 'en', 'es', 'fr'] as $locale) {
                $nameColumn = "name_{$locale}";
                $descriptionColumn = "description_{$locale}";

                if (! Schema::hasColumn('fund_pools', $nameColumn)) {
                    $table->string($nameColumn, 120)->default('')->after('name');
                }

                if (! Schema::hasColumn('fund_pools', $descriptionColumn)) {
                    $table->text($descriptionColumn)->nullable()->after('description');
                }
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('fund_pools')) {
            return;
        }

        Schema::table('fund_pools', function (Blueprint $table) {
            foreach (['ua', 'en', 'es', 'fr'] as $locale) {
                $nameColumn = "name_{$locale}";
                $descriptionColumn = "description_{$locale}";

                if (Schema::hasColumn('fund_pools', $nameColumn)) {
                    $table->dropColumn($nameColumn);
                }

                if (Schema::hasColumn('fund_pools', $descriptionColumn)) {
                    $table->dropColumn($descriptionColumn);
                }
            }
        });
    }
};
