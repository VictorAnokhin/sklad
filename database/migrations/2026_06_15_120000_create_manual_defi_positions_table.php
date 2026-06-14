<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('manual_defi_positions')) {
            return;
        }

        Schema::create('manual_defi_positions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('fid')->default(0);
            $table->string('wallet_address', 120);
            $table->string('chain_id', 40)->default('');
            $table->string('protocol_key', 80);
            $table->string('protocol_name', 120);
            $table->string('position_address', 180);
            $table->timestamps();

            $table->unique(['fid', 'wallet_address', 'chain_id', 'protocol_key', 'position_address'], 'manual_defi_positions_unique');
            $table->index(['wallet_address', 'chain_id'], 'manual_defi_positions_wallet_chain_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manual_defi_positions');
    }
};
