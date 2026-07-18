<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('education_categories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_id')->index();
            $table->string('context', 32)->index();
            $table->string('title');
            $table->json('title_translations')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['project_id', 'context', 'is_active', 'position'], 'education_categories_scope_idx');
        });

        Schema::table('education_topics', function (Blueprint $table) {
            $table->foreignId('category_id')
                ->nullable()
                ->after('project_id')
                ->constrained('education_categories')
                ->nullOnDelete();
        });

        Schema::table('quests_tests', function (Blueprint $table) {
            $table->foreignId('category_id')
                ->nullable()
                ->after('project_id')
                ->constrained('education_categories')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('quests_tests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('category_id');
        });
        Schema::table('education_topics', function (Blueprint $table) {
            $table->dropConstrainedForeignId('category_id');
        });
        Schema::dropIfExists('education_categories');
    }
};
