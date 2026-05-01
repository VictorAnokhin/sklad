<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->string('address')->index();
            $table->timestamps();
        });

        Schema::create('wallet_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained()->cascadeOnDelete();
            $table->string('chain');
            $table->string('token_address')->nullable();
            $table->string('symbol')->nullable();
            $table->string('name')->nullable();
            $table->decimal('balance', 36, 18)->default(0);
            $table->decimal('price_usd', 20, 8)->nullable();
            $table->decimal('value_usd', 20, 2)->nullable();
            $table->string('logo')->nullable();
            $table->boolean('is_spam')->default(false);
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->index(['wallet_id', 'chain']);
            $table->index(['wallet_id', 'token_address']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_tokens');
        Schema::dropIfExists('wallets');
    }
};
