<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('bank_stock_analyses')) {
            return;
        }

        Schema::create('bank_stock_analyses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_id')->default(0);
            $table->string('ticker', 20);
            $table->string('company', 255);
            $table->string('sector', 160)->nullable();
            $table->string('industry', 190)->nullable();
            $table->string('country', 120)->nullable();
            $table->string('market', 80)->nullable();
            $table->string('pe', 80)->nullable();
            $table->string('price', 80)->nullable();
            $table->string('change_percent', 80)->nullable();
            $table->string('volume', 80)->nullable();
            $table->string('market_cap', 80)->nullable();
            $table->string('enterprise_value', 80)->nullable();
            $table->string('income', 80)->nullable();
            $table->string('sales', 80)->nullable();
            $table->string('book_per_share', 80)->nullable();
            $table->string('cash_per_share', 80)->nullable();
            $table->string('dividend_est', 120)->nullable();
            $table->string('dividend_ttm', 120)->nullable();
            $table->string('dividend_ex_date', 120)->nullable();
            $table->string('dividend_growth_3_5y', 120)->nullable();
            $table->string('payout', 80)->nullable();
            $table->string('employees', 80)->nullable();
            $table->string('ipo', 120)->nullable();
            $table->string('forward_pe', 80)->nullable();
            $table->string('peg', 80)->nullable();
            $table->string('ps', 80)->nullable();
            $table->string('pb', 80)->nullable();
            $table->string('pc', 80)->nullable();
            $table->string('pfcf', 80)->nullable();
            $table->string('ev_ebitda', 80)->nullable();
            $table->string('ev_sales', 80)->nullable();
            $table->string('quick_ratio', 80)->nullable();
            $table->string('current_ratio', 80)->nullable();
            $table->string('debt_eq', 80)->nullable();
            $table->string('lt_debt_eq', 80)->nullable();
            $table->string('option_short', 80)->nullable();
            $table->string('eps_ttm', 80)->nullable();
            $table->string('eps_next_y_value', 80)->nullable();
            $table->string('eps_next_q', 80)->nullable();
            $table->string('eps_this_y_growth', 80)->nullable();
            $table->string('eps_next_y_growth', 80)->nullable();
            $table->string('eps_next_5y_growth', 80)->nullable();
            $table->string('eps_past_3_5y', 120)->nullable();
            $table->string('sales_past_3_5y', 120)->nullable();
            $table->string('eps_yy_ttm', 80)->nullable();
            $table->string('sales_yy_ttm', 80)->nullable();
            $table->string('eps_qq', 80)->nullable();
            $table->string('sales_qq', 80)->nullable();
            $table->string('earnings', 120)->nullable();
            $table->timestamps();

            $table->unique(['project_id', 'ticker'], 'bank_stock_analyses_project_ticker_unique');
            $table->index(['project_id', 'sector'], 'bank_stock_analyses_project_sector_idx');
        });

        DB::table('bank_stock_analyses')->insert([
            'project_id' => 0,
            'ticker' => 'KO',
            'company' => 'Coca-Cola Co',
            'sector' => 'Consumer Defensive',
            'industry' => 'Beverages - Non-Alcoholic',
            'country' => 'USA',
            'market' => '372.39B',
            'pe' => '26.08',
            'price' => '86.55',
            'change_percent' => '-0.35%',
            'volume' => '75,988',
            'market_cap' => '374.54B',
            'enterprise_value' => '403.87B',
            'income' => '14.32B',
            'sales' => '50.57B',
            'book_per_share' => '8.40',
            'cash_per_share' => '3.80',
            'dividend_est' => '2.19 (2.52%)',
            'dividend_ttm' => '2.08 (2.39%)',
            'dividend_ex_date' => 'Sep 15, 2026',
            'dividend_growth_3_5y' => '5.04% 4.46%',
            'payout' => '67.13%',
            'employees' => '65900',
            'ipo' => 'Jan 26, 1950',
            'forward_pe' => '24.65',
            'peg' => '3.00',
            'ps' => '7.41',
            'pb' => '10.36',
            'pc' => '22.88',
            'pfcf' => '26.20',
            'ev_ebitda' => '23.51',
            'ev_sales' => '7.99',
            'quick_ratio' => '1.12',
            'current_ratio' => '1.30',
            'debt_eq' => '1.20',
            'lt_debt_eq' => '1.02',
            'option_short' => 'Yes / Yes',
            'eps_ttm' => '3.32',
            'eps_next_y_value' => '3.53',
            'eps_next_q' => '0.88',
            'eps_this_y_growth' => '10.17%',
            'eps_next_y_growth' => '6.86%',
            'eps_next_5y_growth' => '8.21%',
            'eps_past_3_5y' => '11.48% 11.14%',
            'sales_past_3_5y' => '4.14% 7.94%',
            'eps_yy_ttm' => '17.58%',
            'sales_yy_ttm' => '7.42%',
            'eps_qq' => '16.19%',
            'sales_qq' => '5.93%',
            'earnings' => 'Jul 28 BMO',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_stock_analyses');
    }
};
