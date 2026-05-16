<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chat_session_id')->constrained()->cascadeOnDelete();
            $table->integer('fid')->nullable()->index()->comment('ID проекта');
            $table->string('role', 20)->index()->comment('user, assistant, system');
            $table->text('content');
            $table->json('metadata')->nullable()->comment('model, usage, provider и пр.');
            $table->timestamps();

            $table->index(['chat_session_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
    }
};
