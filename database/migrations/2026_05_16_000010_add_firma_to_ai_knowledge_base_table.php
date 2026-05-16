<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Миграция больше не добавляет поле firma — оно удалено в 2026_05_16_000050.
     * Все запросы используют fid вместо firma.
     */
    public function up(): void
    {
        // No-op: колонка firma удалена из таблицы ai_knowledge_base
    }

    public function down(): void
    {
        // No-op
    }
};
