<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('user_course_progress')) {
            Schema::create('user_course_progress', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('education_topic_id')->constrained('education_topics')->cascadeOnDelete();
                $table->unsignedInteger('local_rating')->default(0);
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();

                $table->unique(['user_id', 'education_topic_id'], 'user_course_progress_user_topic_unique');
                $table->index(['education_topic_id', 'local_rating'], 'user_course_progress_topic_rating_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('user_course_progress');
    }
};
