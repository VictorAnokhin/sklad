<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_knowledge_base', function (Blueprint $table) {
            $table->id();
            $table->integer('fid')->index();
            $table->string('title', 255)->default('');
            $table->text('content');
            $table->string('category', 80)->default('general')->index();
            $table->string('source', 80)->default('manual')->comment('manual, chat_export, api');
            $table->boolean('active')->default(true)->index();
            $table->timestamps();

            $table->index(['fid', 'category']);
            $table->index(['fid', 'active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_knowledge_base');
    }
};
