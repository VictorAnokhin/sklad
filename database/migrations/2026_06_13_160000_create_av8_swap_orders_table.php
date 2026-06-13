<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('av8_swap_orders')) {
            return;
        }

        Schema::create('av8_swap_orders', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('fid')->default(0)->index();
            $table->string('mode', 20)->default('crypto');
            $table->string('pay_currency', 20)->default('USDC');
            $table->decimal('pay_amount', 24, 8)->default(0);
            $table->decimal('rate_usdc', 24, 8)->default(0);
            $table->decimal('fee_percent', 10, 4)->default(0);
            $table->decimal('fee_amount', 24, 8)->default(0);
            $table->decimal('expected_av8', 24, 8)->default(0);
            $table->string('payment_method', 80)->default('');
            $table->string('wallet_address', 120)->default('');
            $table->string('client_email', 191)->nullable();
            $table->string('client_phone', 80)->nullable();
            $table->string('status', 40)->default('new')->index();
            $table->string('source', 80)->default('av8fund-react');
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['fid', 'created_at']);
            $table->index(['wallet_address']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('av8_swap_orders');
    }
};
