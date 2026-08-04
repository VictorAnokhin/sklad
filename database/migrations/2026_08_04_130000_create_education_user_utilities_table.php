<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('education_user_utilities')) {
            return;
        }

        Schema::create('education_user_utilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('project_id')->constrained('project')->cascadeOnDelete();
            $table->string('utility_slug', 120);
            $table->timestamp('installed_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'project_id', 'utility_slug'], 'education_user_utilities_unique');
            $table->index(['project_id', 'utility_slug'], 'education_user_utilities_project_slug_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('education_user_utilities');
    }
};
