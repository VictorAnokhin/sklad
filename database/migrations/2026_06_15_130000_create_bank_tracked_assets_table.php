<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('bank_tracked_assets')) {
            return;
        }

        Schema::create('bank_tracked_assets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_id')->default(0);
            $table->string('asset_type', 20)->default('token');
            $table->string('name', 160)->default('');
            $table->string('symbol', 40)->default('');
            $table->string('blockchain', 60);
            $table->string('asset_address', 190);
            $table->string('owner_address', 190)->default('');
            $table->string('protocol', 120)->default('');
            $table->string('token_id', 120)->default('');
            $table->unsignedSmallInteger('decimals')->nullable();
            $table->decimal('last_balance', 36, 18)->nullable();
            $table->decimal('last_price_usd', 20, 8)->nullable();
            $table->decimal('last_value_usd', 20, 2)->nullable();
            $table->json('last_payload')->nullable();
            $table->string('sync_status', 40)->default('manual');
            $table->text('sync_error')->nullable();
            $table->boolean('hidden')->default(false);
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->unique(['project_id', 'asset_type', 'blockchain', 'asset_address', 'owner_address', 'token_id'], 'bank_tracked_assets_unique');
            $table->index(['project_id', 'asset_type', 'hidden'], 'bank_tracked_assets_project_type_hidden_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_tracked_assets');
    }
};
