<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fund_share_settings', function (Blueprint $table) {
            $table->string('current_price_usdc', 80)->default('0')->after('base_price_sui');
            $table->string('total_emission_av8', 80)->default('0')->after('current_price_usdc');
            $table->string('virtual_usdc_reserves', 80)->default('0')->after('total_emission_av8');
            $table->string('virtual_av8_reserves', 80)->default('0')->after('virtual_usdc_reserves');
            $table->unsignedSmallInteger('quote_ttl_seconds')->default(30)->after('virtual_av8_reserves');
            $table->string('min_buy_usdc', 80)->default('0')->after('quote_ttl_seconds');
            $table->string('max_buy_usdc', 80)->default('0')->after('min_buy_usdc');
            $table->string('min_sell_av8', 80)->default('0')->after('max_buy_usdc');
            $table->string('max_sell_av8', 80)->default('0')->after('min_sell_av8');
            $table->unsignedSmallInteger('redeem_delay_days')->default(3)->after('max_sell_av8');
        });
    }

    public function down(): void
    {
        Schema::table('fund_share_settings', function (Blueprint $table) {
            $table->dropColumn([
                'current_price_usdc',
                'total_emission_av8',
                'virtual_usdc_reserves',
                'virtual_av8_reserves',
                'quote_ttl_seconds',
                'min_buy_usdc',
                'max_buy_usdc',
                'min_sell_av8',
                'max_sell_av8',
                'redeem_delay_days',
            ]);
        });
    }
};
