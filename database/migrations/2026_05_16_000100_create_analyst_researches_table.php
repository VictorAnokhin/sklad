<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analyst_researches', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('fid')->default(0)->index();
            $table->string('topic', 500);
            $table->text('summary')->nullable();
            $table->string('status', 30)->default('in_progress')->index();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analyst_researches');
    }
};
