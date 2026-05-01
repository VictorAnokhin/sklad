<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallet_protocol_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained()->cascadeOnDelete();
            $table->string('chain_id', 20);
            $table->json('payload');
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->unique(['wallet_id', 'chain_id']);
            $table->index(['wallet_id', 'synced_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_protocol_snapshots');
    }
};
