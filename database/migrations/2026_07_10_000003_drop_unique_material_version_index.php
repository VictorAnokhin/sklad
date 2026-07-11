<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('educational_materials', function (Blueprint $table) {
            $table->dropUnique('educational_material_topic_level_version_unique');
            $table->index(['topic_id', 'level', 'version'], 'educational_material_topic_level_version_idx');
        });
    }

    public function down(): void
    {
        Schema::table('educational_materials', function (Blueprint $table) {
            $table->dropIndex('educational_material_topic_level_version_idx');
            $table->unique(['topic_id', 'level', 'version'], 'educational_material_topic_level_version_unique');
        });
    }
};
