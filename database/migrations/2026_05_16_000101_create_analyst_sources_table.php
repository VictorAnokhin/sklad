<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analyst_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('research_id')->nullable()->constrained('analyst_researches')->nullOnDelete();
            $table->unsignedSmallInteger('fid')->default(0)->index();
            $table->string('url', 2048)->nullable();
            $table->string('title', 500)->nullable();
            $table->longText('content')->nullable();
            $table->string('content_type', 50)->default('website');
            $table->text('summary')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analyst_sources');
    }
};
