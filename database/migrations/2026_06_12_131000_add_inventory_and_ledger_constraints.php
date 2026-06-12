<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('price_sklad')) {
            Schema::table('price_sklad', function (Blueprint $table) {
                $table->unique(
                    ['pnum', 'firma', 'sklad'],
                    'price_sklad_product_company_warehouse_unique'
                );
            });
        }

        if (DB::getDriverName() === 'mysql') {
            DB::statement(
                'ALTER TABLE entries
                 ADD CONSTRAINT entries_non_negative_check CHECK (debit >= 0 AND credit >= 0),
                 ADD CONSTRAINT entries_one_side_check CHECK (
                    (debit > 0 AND credit = 0) OR (credit > 0 AND debit = 0)
                 )'
            );
            DB::statement(
                'ALTER TABLE inventory_cost_movements
                 ADD CONSTRAINT inventory_cost_movements_quantity_check CHECK (quantity > 0),
                 ADD CONSTRAINT inventory_cost_movements_direction_check CHECK (direction IN ("in", "out"))'
            );
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE entries DROP CHECK entries_non_negative_check');
            DB::statement('ALTER TABLE entries DROP CHECK entries_one_side_check');
            DB::statement('ALTER TABLE inventory_cost_movements DROP CHECK inventory_cost_movements_quantity_check');
            DB::statement('ALTER TABLE inventory_cost_movements DROP CHECK inventory_cost_movements_direction_check');
        }

        if (Schema::hasTable('price_sklad')) {
            Schema::table('price_sklad', function (Blueprint $table) {
                $table->dropUnique('price_sklad_product_company_warehouse_unique');
            });
        }
    }
};
