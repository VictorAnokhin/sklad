<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_communications', function (Blueprint $table) {
            $table->id();
            $table->string('source_agent', 50);               // 'backend', 'frontend'
            $table->string('target_agent', 50);               // 'backend', 'frontend'
            $table->unsignedInteger('fid');
            $table->unsignedBigInteger('task_id')->nullable();
            $table->string('message_type', 50)->default('text'); // 'text', 'task_request', 'task_result', 'notification'
            $table->text('content');
            $table->json('metadata')->nullable();
            $table->string('status', 20)->default('sent');     // 'sent', 'delivered', 'read', 'error'
            $table->timestamp('created_at')->useCurrent();

            $table->index(['source_agent', 'target_agent', 'fid'], 'agent_comm_agents_fid_idx');
            $table->index('task_id', 'agent_comm_task_idx');

            $table->foreign('task_id')
                  ->references('id')
                  ->on('agent_tasks')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_communications');
    }
};
