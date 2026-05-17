<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_tasks', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('source_agent', 50);              // 'telegram', 'backend', 'frontend', 'backend_chat'
            $table->string('target_agent', 50);              // 'telegram', 'backend', 'frontend'
            $table->unsignedInteger('fid');
            $table->string('session_token', 100)->nullable();
            $table->string('task_type', 50);                  // 'find_client', 'create_client', 'find_order', 'create_order', 'study_website', 'complex_question', etc.
            $table->json('input_data');
            $table->json('output_data')->nullable();
            $table->string('status', 20)->default('pending'); // 'pending', 'processing', 'completed', 'failed', 'cancelled'
            $table->integer('priority')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->index(['source_agent', 'target_agent'], 'agent_tasks_agents_idx');
            $table->index('fid', 'agent_tasks_fid_idx');
            $table->index('status', 'agent_tasks_status_idx');
            $table->index('uuid', 'agent_tasks_uuid_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_tasks');
    }
};
