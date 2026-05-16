<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('session_token', 64)->unique();
            $table->integer('fid')->nullable()->index();
            $table->string('wallet', 100)->nullable()->index();
            $table->string('language', 10)->default('ru');
            $table->string('page', 80)->nullable();
            $table->string('title', 255)->nullable()->comment('Первый вопрос пользователя — для отображения в списке сессий');
            $table->string('status', 20)->default('active')->index()->comment('active, archived');
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['session_token', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_sessions');
    }
};
