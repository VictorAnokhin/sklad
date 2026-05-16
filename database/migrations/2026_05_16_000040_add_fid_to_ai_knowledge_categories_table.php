<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Добавляет поле fid (ID проекта) в таблицу ai_knowledge_categories,
     * чтобы категории были привязаны к конкретному проекту.
     */
    public function up(): void
    {
        if (!Schema::hasTable('ai_knowledge_categories')) {
            return;
        }

        if (Schema::hasColumn('ai_knowledge_categories', 'fid')) {
            return;
        }

        Schema::table('ai_knowledge_categories', function (Blueprint $table) {
            $table->integer('fid')->nullable()->index()->after('id')
                ->comment('ID проекта, к которому относится категория. NULL = глобальные категории');
        });
    }

    public function down(): void
    {
        Schema::table('ai_knowledge_categories', function (Blueprint $table) {
            $table->dropColumn('fid');
        });
    }
};
