<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webchat_visitors', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('fid')->index();
            $table->string('visitor_uid', 100);
            $table->string('site_domain', 120)->nullable()->index();
            $table->string('last_session_token', 64)->nullable()->index();
            $table->unsignedBigInteger('identified_user_id')->nullable()->index();
            $table->string('language', 10)->nullable();
            $table->string('timezone', 80)->nullable();
            $table->string('last_seen_url', 500)->nullable();
            $table->string('last_seen_path', 255)->nullable();
            $table->string('last_referrer', 500)->nullable();
            $table->string('ip_hash', 64)->nullable()->index();
            $table->string('user_agent_hash', 64)->nullable()->index();
            $table->json('interests')->nullable();
            $table->json('traits')->nullable();
            $table->json('counters')->nullable();
            $table->boolean('consent_analytics')->default(false)->index();
            $table->decimal('identification_confidence', 5, 2)->default(0);
            $table->timestamp('first_seen_at')->nullable()->index();
            $table->timestamp('last_seen_at')->nullable()->index();
            $table->timestamps();

            $table->unique(['fid', 'visitor_uid'], 'webchat_visitors_fid_uid_unique');
            $table->index(['fid', 'last_seen_at'], 'webchat_visitors_fid_last_seen_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webchat_visitors');
    }
};
