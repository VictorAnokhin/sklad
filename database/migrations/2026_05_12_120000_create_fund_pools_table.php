<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('fund_pools')) {
            return;
        }

        Schema::create('fund_pools', function (Blueprint $table) {
            $table->id();
            $table->string('network', 40)->default('testnet');
            $table->string('package_id', 80)->default('');
            $table->string('pool_registry_id', 80)->default('');
            $table->string('pool_admin_cap_id', 80)->default('');
            $table->string('pool_object_id', 80)->default('');
            $table->string('coin_type', 500);
            $table->string('symbol', 32)->default('USDC');
            $table->string('name', 120);
            $table->text('description')->nullable();
            $table->unsignedTinyInteger('risk_level')->default(1);
            $table->unsignedSmallInteger('target_apy_bps')->default(0);
            $table->unsignedSmallInteger('realized_apy_bps')->default(0);
            $table->string('min_deposit_usdc', 80)->default('0');
            $table->unsignedSmallInteger('max_weight_bps')->default(10000);
            $table->boolean('active')->default(true);
            $table->string('logo_url', 500)->default('');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->unique(['network', 'pool_object_id']);
            $table->index(['network', 'package_id']);
            $table->index(['active', 'risk_level']);
            $table->index('coin_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fund_pools');
    }
};
