<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallet_performance_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained()->cascadeOnDelete();
            $table->string('chain_id', 20)->default('all');
            $table->string('timeframe', 8);
            $table->timestamp('point_at');
            $table->string('label', 32);
            $table->decimal('total_usd', 24, 8)->default(0);
            $table->string('source', 40)->default('on_demand');
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['wallet_id', 'chain_id', 'timeframe', 'point_at'], 'wallet_performance_unique_point');
            $table->index(['wallet_id', 'chain_id', 'timeframe', 'point_at'], 'wallet_performance_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_performance_points');
    }
};
