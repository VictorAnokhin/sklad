<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('traffic_test_questions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('fid')->default(2)->index();
            $table->unsignedBigInteger('source_external_id')->nullable();
            $table->unsignedBigInteger('topic_external_id')->nullable();
            $table->text('question');
            $table->json('answers');
            $table->unsignedSmallInteger('correct_answer')->nullable();
            $table->longText('explanation')->nullable();
            $table->string('image_url', 500)->nullable();
            $table->string('source_url', 500)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_published')->default(true)->index();
            $table->timestamps();

            $table->unique(['fid', 'source_external_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('traffic_test_questions');
    }
};
