<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('widget_user_profiles', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('fid')->index();
            $table->string('fingerprint_hash', 128)->index();
            $table->string('visitor_uid', 100)->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('google_id', 191)->nullable()->index();
            $table->string('email', 191)->nullable()->index();
            $table->string('last_session_token', 64)->nullable()->index();
            $table->string('site_domain', 120)->nullable()->index();
            $table->json('traits')->nullable();
            $table->timestamp('first_seen_at')->nullable()->index();
            $table->timestamp('last_seen_at')->nullable()->index();
            $table->timestamps();

            $table->unique(['fid', 'fingerprint_hash'], 'widget_profiles_fid_fp_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('widget_user_profiles');
    }
};
