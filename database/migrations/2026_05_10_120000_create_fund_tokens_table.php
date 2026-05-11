<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('fund_tokens')) {
            return;
        }

        Schema::create('fund_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('network', 40)->default('testnet');
            $table->string('package_id', 80)->default('');
            $table->string('coin_type', 500);
            $table->string('symbol', 32);
            $table->string('name', 120);
            $table->unsignedTinyInteger('decimals')->default(9);
            $table->unsignedInteger('target_weight_bps')->default(0);
            $table->unsignedInteger('min_weight_bps')->default(0);
            $table->unsignedInteger('max_weight_bps')->default(0);
            $table->string('price_feed_id', 180)->default('');
            $table->string('logo_url', 500)->default('');
            $table->boolean('enabled')->default(true);
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->unique(['network', 'coin_type']);
            $table->index(['network', 'package_id']);
            $table->index(['enabled', 'symbol']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fund_tokens');
    }
};
