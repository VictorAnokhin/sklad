<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Создаёт таблицу ai_tools для хранения определений инструментов (function calling),
     * которые AI-модель может использовать для взаимодействия с программой.
     */
    public function up(): void
    {
        Schema::create('ai_tools', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('fid')->nullable()->comment('ID проекта (null = глобальный инструмент)');
            $table->string('key', 80)->comment('Уникальный идентификатор инструмента (slug)');
            $table->string('name', 255)->comment('Человекочитаемое название инструмента');
            $table->text('description')->nullable()->comment('Описание — для чего этот инструмент');
            $table->json('schema')->comment('JSON-схема функции для AI (function calling)');
            $table->boolean('active')->default(true)->comment('Активен ли инструмент');
            $table->timestamps();

            $table->unique(['fid', 'key']);
            $table->index('fid');
            $table->index('active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_tools');
    }
};
