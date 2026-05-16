<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Добавляет поле firma (ID компании) в таблицу ai_knowledge_base,
     * чтобы знания можно было привязывать как к проекту (fid), так и к компании (firma).
     */
    public function up(): void
    {
        Schema::table('ai_knowledge_base', function (Blueprint $table) {
            $table->integer('firma')->nullable()->index()->after('fid')
                ->comment('ID компании (фирмы), к которой относится знание');
        });
    }

    public function down(): void
    {
        Schema::table('ai_knowledge_base', function (Blueprint $table) {
            $table->dropColumn('firma');
        });
    }
};
