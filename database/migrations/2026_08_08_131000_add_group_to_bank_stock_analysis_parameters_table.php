<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('bank_stock_analysis_parameters')) {
            return;
        }

        if (! Schema::hasColumn('bank_stock_analysis_parameters', 'group_name')) {
            Schema::table('bank_stock_analysis_parameters', function (Blueprint $table) {
                $table->string('group_name', 160)->default('Основные')->index()->after('label');
            });
        }

        DB::table('bank_stock_analysis_parameters')
            ->whereNull('group_name')
            ->orWhere('group_name', '')
            ->update(['group_name' => 'Основные']);

        foreach ($this->defaultGroups() as $groupName => $fieldKeys) {
            DB::table('bank_stock_analysis_parameters')
                ->whereIn('field_key', $fieldKeys)
                ->update(['group_name' => $groupName]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('bank_stock_analysis_parameters') && Schema::hasColumn('bank_stock_analysis_parameters', 'group_name')) {
            Schema::table('bank_stock_analysis_parameters', function (Blueprint $table) {
                $table->dropColumn('group_name');
            });
        }
    }

    private function defaultGroups(): array
    {
        return [
            'Оценка и баланс' => ['market_cap', 'enterprise_value', 'income', 'sales', 'book_per_share', 'cash_per_share'],
            'Дивиденды' => ['dividend_est', 'dividend_ttm', 'dividend_ex_date', 'dividend_growth_3_5y', 'payout'],
            'Мультипликаторы' => ['pe', 'forward_pe', 'peg', 'ps', 'pb', 'pc', 'pfcf', 'ev_ebitda', 'ev_sales'],
            'Ликвидность и долг' => ['quick_ratio', 'current_ratio', 'debt_eq', 'lt_debt_eq', 'option_short'],
            'EPS и продажи' => ['eps_ttm', 'eps_next_y_value', 'eps_next_q', 'eps_this_y_growth', 'eps_next_y_growth', 'eps_next_5y_growth', 'eps_past_3_5y', 'sales_past_3_5y', 'eps_yy_ttm', 'sales_yy_ttm', 'eps_qq', 'sales_qq', 'earnings'],
        ];
    }
};
