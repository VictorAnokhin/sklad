<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webchat_ui_configs', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('fid')->index();
            $table->string('variant_key', 120)->default('default');
            $table->string('site_domain', 120)->nullable()->index();
            $table->string('status', 20)->default('active')->index();
            $table->json('config');
            $table->json('recommendation')->nullable();
            $table->string('source', 80)->default('manual')->index();
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamps();

            $table->index(['fid', 'status'], 'webchat_ui_configs_fid_status_idx');
            $table->index(['fid', 'variant_key'], 'webchat_ui_configs_fid_variant_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webchat_ui_configs');
    }
};
