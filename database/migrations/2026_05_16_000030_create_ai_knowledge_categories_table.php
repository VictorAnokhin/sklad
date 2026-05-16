<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ai_knowledge_categories', function (Blueprint $table) {
            $table->id();
            $table->string('key', 80)->unique()->comment('Уникальный ключ категории (slug)');
            $table->string('name', 255)->comment('Название категории');
            $table->integer('sort_order')->default(0)->comment('Порядок сортировки');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        // Insert default categories
        $now = now();
        $defaults = [
            ['key' => 'general', 'name' => 'Общее', 'sort_order' => 0, 'active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'invest', 'name' => 'Инвестиции', 'sort_order' => 1, 'active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'wallet', 'name' => 'Кошелёк', 'sort_order' => 2, 'active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'token', 'name' => 'Токены', 'sort_order' => 3, 'active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'fund', 'name' => 'Фонд', 'sort_order' => 4, 'active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'admin', 'name' => 'Администрирование', 'sort_order' => 5, 'active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'faq', 'name' => 'FAQ', 'sort_order' => 6, 'active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'chat_export', 'name' => 'Из чата', 'sort_order' => 7, 'active' => true, 'created_at' => $now, 'updated_at' => $now],
        ];
        DB::table('ai_knowledge_categories')->insert($defaults);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_knowledge_categories');
    }
};
