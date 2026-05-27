<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crypto_aml_screenings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('wallet_address', 128);
            $table->string('asset', 16);
            $table->string('network', 32);
            $table->decimal('amount', 24, 8)->nullable();
            $table->string('direction', 16)->default('incoming');
            $table->string('risk_level', 16);
            $table->boolean('allowed')->default(false);
            $table->string('reason', 64)->nullable();
            $table->string('provider', 32)->default('chainalysis');
            $table->string('transfer_reference', 64)->nullable();
            $table->json('raw_response')->nullable();
            $table->timestamps();

            $table->index(['wallet_address', 'asset', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crypto_aml_screenings');
    }
};
