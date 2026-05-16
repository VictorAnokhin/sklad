<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Удаляет колонку firma из таблицы ai_knowledge_base.
     * Все запросы переключены на использование fid.
     */
    public function up(): void
    {
        Schema::table('ai_knowledge_base', function (Blueprint $table) {
            if (Schema::hasColumn('ai_knowledge_base', 'firma')) {
                $table->dropColumn('firma');
            }
        });
    }

    /**
     * Восстанавливает колонку firma.
     */
    public function down(): void
    {
        Schema::table('ai_knowledge_base', function (Blueprint $table) {
            if (!Schema::hasColumn('ai_knowledge_base', 'firma')) {
                $table->integer('firma')->nullable()->after('fid');
            }
        });
    }
};
