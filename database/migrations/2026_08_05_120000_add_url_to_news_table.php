<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('news') || Schema::hasColumn('news', 'url')) {
            return;
        }

        Schema::table('news', function (Blueprint $table) {
            $table->string('url', 255)->default('')->after('title_en');
            $table->index(['firma', 'url']);
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('news') || ! Schema::hasColumn('news', 'url')) {
            return;
        }

        Schema::table('news', function (Blueprint $table) {
            $table->dropIndex(['firma', 'url']);
            $table->dropColumn('url');
        });
    }
};
