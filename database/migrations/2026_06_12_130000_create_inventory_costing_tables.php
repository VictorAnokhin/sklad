<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('inventory_cost_balances')) {
            Schema::create('inventory_cost_balances', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id');
                $table->unsignedBigInteger('warehouse_id');
                $table->string('product_id', 80);
                $table->decimal('quantity', 18, 3)->default(0);
                $table->decimal('total_value', 18, 4)->default(0);
                $table->decimal('average_cost', 18, 6)->default(0);
                $table->date('last_movement_date')->nullable();
                $table->timestamps();

                $table->unique(
                    ['company_id', 'warehouse_id', 'product_id'],
                    'inventory_cost_balances_key_unique'
                );
            });
        }

        if (! Schema::hasTable('inventory_cost_movements')) {
            Schema::create('inventory_cost_movements', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id');
                $table->unsignedBigInteger('warehouse_id');
                $table->string('product_id', 80);
                $table->string('source_type', 40);
                $table->string('source_id', 80);
                $table->unsignedBigInteger('line_id')->nullable();
                $table->date('movement_date');
                $table->string('direction', 10);
                $table->decimal('quantity', 18, 3);
                $table->decimal('unit_cost', 18, 6);
                $table->decimal('total_cost', 18, 4);
                $table->decimal('quantity_before', 18, 3);
                $table->decimal('value_before', 18, 4);
                $table->decimal('average_cost_before', 18, 6);
                $table->decimal('quantity_after', 18, 3);
                $table->decimal('value_after', 18, 4);
                $table->decimal('average_cost_after', 18, 6);
                $table->unsignedBigInteger('ledger_transaction_id')->nullable();
                $table->timestamp('reversed_at')->nullable();
                $table->timestamps();

                $table->index(
                    ['company_id', 'warehouse_id', 'product_id', 'movement_date'],
                    'inventory_cost_movements_product_date_index'
                );
                $table->index(
                    ['source_type', 'source_id', 'reversed_at'],
                    'inventory_cost_movements_source_index'
                );
            });
        }

        $this->seedOpeningBalances();
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_cost_movements');
        Schema::dropIfExists('inventory_cost_balances');
    }

    private function seedOpeningBalances(): void
    {
        if (! Schema::hasTable('price_sklad') || ! Schema::hasTable('price')) {
            return;
        }

        $costColumn = Schema::hasColumn('price', 'pay0') ? 'pay0' : 'pay';

        $priceSubquery = DB::table('price')
            ->selectRaw("firma, pnum, MAX({$costColumn}) as unit_cost")
            ->groupBy('firma', 'pnum');

        DB::table('price_sklad as ps')
            ->leftJoinSub($priceSubquery, 'p', function ($join) {
                $join->on('p.firma', '=', 'ps.firma')
                    ->on('p.pnum', '=', 'ps.pnum');
            })
            ->selectRaw(
                'ps.firma as company_id, ps.sklad as warehouse_id, ps.pnum as product_id,
                 SUM(ps.count) as quantity, COALESCE(MAX(p.unit_cost), 0) as average_cost'
            )
            ->groupBy('ps.firma', 'ps.sklad', 'ps.pnum')
            ->orderBy('ps.firma')
            ->orderBy('ps.sklad')
            ->orderBy('ps.pnum')
            ->chunk(500, function ($rows): void {
                $now = now();
                $payload = [];

                foreach ($rows as $row) {
                    $quantity = round((float) $row->quantity, 3);
                    $averageCost = round((float) $row->average_cost, 6);
                    $payload[] = [
                        'company_id' => (int) $row->company_id,
                        'warehouse_id' => (int) $row->warehouse_id,
                        'product_id' => (string) $row->product_id,
                        'quantity' => $quantity,
                        'total_value' => round($quantity * $averageCost, 4),
                        'average_cost' => $averageCost,
                        'last_movement_date' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                if ($payload !== []) {
                    DB::table('inventory_cost_balances')->insertOrIgnore($payload);
                }
            });
    }
};
