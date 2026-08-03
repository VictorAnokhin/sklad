<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('education_utilities')) {
            return;
        }

        Schema::create('education_utilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('project')->cascadeOnDelete();
            $table->string('slug', 120);
            $table->string('module_key', 120)->default('calculator_builder');
            $table->string('icon', 80)->default('calculator');
            $table->string('icon_path')->nullable();
            $table->json('schema_json')->nullable();
            $table->string('title')->nullable();
            $table->json('title_translations')->nullable();
            $table->text('description')->nullable();
            $table->json('description_translations')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->decimal('cost_av8', 18, 6)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['project_id', 'slug'], 'education_utilities_project_slug_unique');
            $table->index(['project_id', 'is_active', 'position'], 'education_utilities_project_active_position_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('education_utilities');
    }
};
