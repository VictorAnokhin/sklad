<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('fund_pool_events')) {
            return;
        }

        Schema::create('fund_pool_events', function (Blueprint $table) {
            $table->id();
            $table->string('network', 40)->default('testnet');
            $table->string('package_id', 80)->default('');
            $table->string('event_type', 80);
            $table->string('move_event_type', 500);
            $table->string('tx_digest', 120);
            $table->unsignedBigInteger('event_seq')->default(0);
            $table->unsignedBigInteger('checkpoint')->nullable();
            $table->string('pool_object_id', 80)->default('');
            $table->string('owner_address', 80)->default('');
            $table->string('amount_usdc', 80)->default('0');
            $table->string('pool_shares', 80)->default('0');
            $table->string('burned_pool_shares', 80)->default('0');
            $table->string('balance_usdc', 80)->default('0');
            $table->boolean('active')->nullable();
            $table->unsignedSmallInteger('target_apy_bps')->nullable();
            $table->unsignedSmallInteger('realized_apy_bps')->nullable();
            $table->string('min_deposit_usdc', 80)->nullable();
            $table->unsignedSmallInteger('max_weight_bps')->nullable();
            $table->json('raw_event')->nullable();
            $table->timestamp('event_at')->nullable();
            $table->timestamps();

            $table->unique(['tx_digest', 'event_seq']);
            $table->index(['network', 'package_id', 'event_type']);
            $table->index(['pool_object_id', 'event_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fund_pool_events');
    }
};
