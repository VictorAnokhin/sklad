<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('educational_materials')) {
            Schema::table('educational_materials', function (Blueprint $table) {
                if (!Schema::hasColumn('educational_materials', 'rating')) {
                    $table->unsignedInteger('rating')->default(0)->after('level');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('educational_materials')) {
            Schema::table('educational_materials', function (Blueprint $table) {
                if (Schema::hasColumn('educational_materials', 'rating')) {
                    $table->dropColumn('rating');
                }
            });
        }
    }
};
