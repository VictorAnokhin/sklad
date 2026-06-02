<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unmet_needs', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('fid')->index();
            $table->foreignId('widget_user_profile_id')->nullable()->constrained('widget_user_profiles')->nullOnDelete();
            $table->string('fingerprint_hash', 128)->nullable()->index();
            $table->string('visitor_uid', 100)->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('google_id', 191)->nullable()->index();
            $table->string('email', 191)->nullable()->index();
            $table->string('status', 40)->default('pending')->index();
            $table->string('search_query', 500);
            $table->string('normalized_query', 500)->nullable()->index();
            $table->json('context')->nullable();
            $table->json('product_snapshot')->nullable();
            $table->timestamp('detected_at')->nullable()->index();
            $table->timestamp('ready_at')->nullable()->index();
            $table->timestamp('resolved_at')->nullable()->index();
            $table->timestamps();

            $table->index(['fid', 'status', 'updated_at'], 'unmet_needs_fid_status_updated_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unmet_needs');
    }
};
