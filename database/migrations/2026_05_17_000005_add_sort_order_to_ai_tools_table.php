<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Добавляет колонку sort_order для ручной сортировки инструментов.
     */
    public function up(): void
    {
        Schema::table('ai_tools', function (Blueprint $table) {
            $table->unsignedSmallInteger('sort_order')->default(0)->after('active')->comment('Порядок сортировки');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ai_tools', function (Blueprint $table) {
            $table->dropColumn('sort_order');
        });
    }
};
