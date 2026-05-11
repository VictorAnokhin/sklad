<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fund_share_settings', function (Blueprint $table) {
            $table->id();
            $table->string('network', 40)->default('testnet');
            $table->string('package_id', 80)->default('');
            $table->string('share_config_id', 80)->default('');
            $table->string('share_admin_cap_id', 80)->default('');
            $table->string('share_treasury_cap_id', 80)->default('');
            $table->string('pricing_model', 40)->default('nav_per_share');
            $table->unsignedSmallInteger('mint_fee_bps')->default(0);
            $table->unsignedSmallInteger('redeem_fee_bps')->default(0);
            $table->unsignedSmallInteger('redeem_burn_bps')->default(10000);
            $table->unsignedSmallInteger('price_impact_bps')->default(0);
            $table->string('min_price_sui', 80)->default('0');
            $table->string('base_price_sui', 80)->default('0');
            $table->string('max_supply', 80)->default('0');
            $table->string('max_daily_mint', 80)->default('0');
            $table->boolean('mint_paused')->default(false);
            $table->boolean('redeem_paused')->default(false);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['network', 'package_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fund_share_settings');
    }
};
