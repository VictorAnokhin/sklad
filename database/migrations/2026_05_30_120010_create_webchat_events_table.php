<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webchat_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('fid')->index();
            $table->foreignId('webchat_visitor_id')->nullable()->constrained('webchat_visitors')->nullOnDelete();
            $table->string('visitor_uid', 100)->index();
            $table->string('session_token', 64)->nullable()->index();
            $table->string('event_type', 80)->index();
            $table->string('funnel_step', 120)->nullable()->index();
            $table->string('ui_variant_key', 120)->nullable()->index();
            $table->string('site_domain', 120)->nullable()->index();
            $table->string('page_url', 500)->nullable();
            $table->string('page_path', 255)->nullable()->index();
            $table->string('page_title', 255)->nullable();
            $table->string('referrer', 500)->nullable();
            $table->string('language', 10)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('occurred_at')->useCurrent()->index();
            $table->timestamps();

            $table->index(['fid', 'event_type', 'occurred_at'], 'webchat_events_fid_type_time_idx');
            $table->index(['fid', 'visitor_uid', 'occurred_at'], 'webchat_events_fid_visitor_time_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webchat_events');
    }
};
