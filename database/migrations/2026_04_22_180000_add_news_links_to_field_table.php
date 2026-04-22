<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('field')) {
            return;
        }

        Schema::table('field', function (Blueprint $table) {
            if (!Schema::hasColumn('field', 'news_catalog_id')) {
                $table->unsignedBigInteger('news_catalog_id')->nullable()->after('link');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('field')) {
            return;
        }

        Schema::table('field', function (Blueprint $table) {
            if (Schema::hasColumn('field', 'news_catalog_id')) {
                $table->dropColumn('news_catalog_id');
            }
        });
    }
};
