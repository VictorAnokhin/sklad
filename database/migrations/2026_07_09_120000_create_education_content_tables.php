<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('education_topics', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_id')->index();
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['project_id', 'is_active', 'position'], 'education_topics_project_active_position_idx');
        });

        Schema::create('educational_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('topic_id')->constrained('education_topics')->cascadeOnDelete();
            $table->enum('level', ['beginner', 'intermediate', 'advanced']);
            $table->enum('content_type', ['markdown', 'video_link', 'interactive_scenario']);
            $table->longText('body');
            $table->string('version', 32)->default('1.0');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['topic_id', 'level', 'version'], 'educational_material_topic_level_version_unique');
            $table->index(['topic_id', 'level', 'is_active'], 'educational_material_topic_level_active_idx');
        });

        Schema::create('quests_tests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_id')->nullable()->index();
            $table->foreignId('material_id')->nullable()->constrained('educational_materials')->nullOnDelete();
            $table->string('test_type', 40)->default('knowledge_check')->index();
            $table->string('title');
            $table->json('quest_data');
            $table->unsignedTinyInteger('passing_score')->default(80);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['material_id', 'is_active']);
        });

        Schema::create('quest_test_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quest_test_id')->constrained('quests_tests')->cascadeOnDelete();
            $table->unsignedInteger('min_score');
            $table->unsignedInteger('max_score');
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->text('description')->nullable();
            $table->text('recommendation')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index(['quest_test_id', 'min_score', 'max_score'], 'quest_test_results_test_score_idx');
        });

        Schema::create('education_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('topic_id')->constrained('education_topics')->cascadeOnDelete();
            $table->enum('current_level', ['beginner', 'intermediate', 'advanced'])->default('beginner');
            $table->foreignId('current_material_id')->nullable()->constrained('educational_materials')->nullOnDelete();
            $table->unsignedInteger('failed_attempts')->default(0);
            $table->unsignedInteger('passed_attempts')->default(0);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'topic_id']);
        });

        Schema::create('quest_test_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('quest_test_id')->constrained('quests_tests')->cascadeOnDelete();
            $table->foreignId('material_id')->nullable()->constrained('educational_materials')->nullOnDelete();
            $table->unsignedTinyInteger('score');
            $table->unsignedInteger('total_score')->nullable();
            $table->unsignedInteger('max_score')->nullable();
            $table->boolean('passed');
            $table->json('answers')->nullable();
            $table->json('result_data')->nullable();
            $table->foreignId('next_material_id')->nullable()->constrained('educational_materials')->nullOnDelete();
            $table->timestamps();
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quest_test_attempts');
        Schema::dropIfExists('education_progress');
        Schema::dropIfExists('quest_test_results');
        Schema::dropIfExists('quests_tests');
        Schema::dropIfExists('educational_materials');
        Schema::dropIfExists('education_topics');
    }
};
