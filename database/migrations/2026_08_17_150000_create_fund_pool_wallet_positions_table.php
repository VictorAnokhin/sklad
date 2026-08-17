<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('fund_pool_wallet_positions')) {
            return;
        }

        Schema::create('fund_pool_wallet_positions', function (Blueprint $table) {
            $table->id();
            $table->string('network', 40)->default('testnet');
            $table->string('pool_object_id', 80)->index();
            $table->unsignedBigInteger('pool_id')->nullable()->index();
            $table->string('wallet_address', 80)->index();
            $table->string('balance_av8', 80)->default('0');
            $table->string('balance_usdc', 80)->default('0');
            $table->string('deposited_av8', 80)->default('0');
            $table->string('withdrawn_av8', 80)->default('0');
            $table->string('deposited_usdc', 80)->default('0');
            $table->string('withdrawn_usdc', 80)->default('0');
            $table->unsignedInteger('stake_operations_count')->default(0);
            $table->unsignedInteger('unstake_operations_count')->default(0);
            $table->string('last_tx_digest', 120)->nullable();
            $table->timestamp('last_event_at')->nullable();
            $table->timestamps();

            $table->unique(['network', 'pool_object_id', 'wallet_address'], 'pool_wallet_pos_unique');
            $table->index(['pool_object_id', 'balance_av8'], 'pool_wallet_pos_av8_idx');
            $table->index(['pool_object_id', 'balance_usdc'], 'pool_wallet_pos_usdc_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fund_pool_wallet_positions');
    }
};
