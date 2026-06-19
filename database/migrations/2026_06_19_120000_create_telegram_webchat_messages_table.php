<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telegram_webchat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chat_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('chat_message_id')->nullable()->constrained('chat_messages')->nullOnDelete();
            $table->integer('fid')->nullable()->index();
            $table->integer('firma')->nullable()->index();
            $table->string('site_key', 80)->nullable()->index();
            $table->string('site_domain', 120)->nullable()->index();
            $table->string('telegram_chat_id', 80)->index();
            $table->string('telegram_thread_id', 80)->nullable()->index();
            $table->unsignedBigInteger('telegram_message_id')->index();
            $table->unsignedBigInteger('telegram_reply_to_message_id')->nullable()->index();
            $table->string('direction', 24)->index();
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->unique(['telegram_chat_id', 'telegram_message_id'], 'telegram_webchat_chat_message_unique');
            $table->index(['chat_session_id', 'created_at'], 'telegram_webchat_session_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_webchat_messages');
    }
};
