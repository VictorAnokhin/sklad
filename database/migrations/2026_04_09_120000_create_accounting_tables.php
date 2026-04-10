<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('accounts')) {
            Schema::create('accounts', function (Blueprint $table) {
                $table->id();
                $table->string('code')->unique();
                $table->string('name');
                $table->enum('type', ['asset', 'liability', 'equity', 'income', 'expense']);
                $table->foreignId('parent_id')->nullable()->constrained('accounts')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('transactions')) {
            Schema::create('transactions', function (Blueprint $table) {
                $table->id();
                $table->date('date');
                $table->string('description')->nullable();
                $table->unsignedBigInteger('company_id')->default(0);
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('reference_type')->nullable();
                $table->string('reference_id')->nullable();
                $table->string('currency', 10)->default('UAH');
                $table->decimal('amount', 15, 2)->default(0);
                $table->decimal('amount_base', 15, 2)->default(0);
                $table->timestamps();

                $table->index(['company_id', 'date']);
                $table->index(['reference_type', 'reference_id']);
            });
        }

        if (!Schema::hasTable('entries')) {
            Schema::create('entries', function (Blueprint $table) {
                $table->id();
                $table->foreignId('transaction_id')->constrained('transactions')->cascadeOnDelete();
                $table->foreignId('account_id')->constrained('accounts');
                $table->decimal('debit', 15, 2)->default(0);
                $table->decimal('credit', 15, 2)->default(0);
                $table->unsignedBigInteger('company_id')->default(0);
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('reference_type')->nullable();
                $table->string('reference_id')->nullable();
                $table->string('currency', 10)->default('UAH');
                $table->decimal('amount', 15, 2)->default(0);
                $table->decimal('amount_base', 15, 2)->default(0);
                $table->timestamps();

                $table->index(['account_id', 'company_id']);
                $table->index(['reference_type', 'reference_id']);
            });
        }

        $this->seedDefaultAccounts();
    }

    public function down(): void
    {
        Schema::dropIfExists('entries');
        Schema::dropIfExists('transactions');
        Schema::dropIfExists('accounts');
    }

    private function seedDefaultAccounts(): void
    {
        if (!Schema::hasTable('accounts')) {
            return;
        }

        $roots = [
            ['code' => '28', 'name' => 'Товары', 'type' => 'asset', 'parent_code' => null],
            ['code' => '30', 'name' => 'Касса', 'type' => 'asset', 'parent_code' => null],
            ['code' => '31', 'name' => 'Счета в банках', 'type' => 'asset', 'parent_code' => null],
            ['code' => '36', 'name' => 'Расчеты с покупателями', 'type' => 'asset', 'parent_code' => null],
            ['code' => '63', 'name' => 'Расчеты с поставщиками', 'type' => 'liability', 'parent_code' => null],
            ['code' => '70', 'name' => 'Доходы от реализации', 'type' => 'income', 'parent_code' => null],
            ['code' => '90', 'name' => 'Себестоимость реализации', 'type' => 'expense', 'parent_code' => null],
            ['code' => '94', 'name' => 'Прочие операционные расходы', 'type' => 'expense', 'parent_code' => null],
        ];

        foreach ($roots as $root) {
            DB::table('accounts')->updateOrInsert(
                ['code' => $root['code']],
                [
                    'name' => $root['name'],
                    'type' => $root['type'],
                    'parent_id' => null,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        $parentIds = DB::table('accounts')->pluck('id', 'code');
        $children = [
            ['code' => '281', 'name' => 'Товары на складе', 'type' => 'asset', 'parent_code' => '28'],
            ['code' => '301', 'name' => 'Касса в национальной валюте', 'type' => 'asset', 'parent_code' => '30'],
            ['code' => '311', 'name' => 'Текущие счета в национальной валюте', 'type' => 'asset', 'parent_code' => '31'],
            ['code' => '361', 'name' => 'Расчеты с отечественными покупателями', 'type' => 'asset', 'parent_code' => '36'],
            ['code' => '631', 'name' => 'Расчеты с отечественными поставщиками', 'type' => 'liability', 'parent_code' => '63'],
            ['code' => '701', 'name' => 'Доход от реализации готовой продукции', 'type' => 'income', 'parent_code' => '70'],
            ['code' => '902', 'name' => 'Себестоимость реализованных товаров', 'type' => 'expense', 'parent_code' => '90'],
            ['code' => '949', 'name' => 'Прочие операционные расходы', 'type' => 'expense', 'parent_code' => '94'],
        ];

        foreach ($children as $child) {
            DB::table('accounts')->updateOrInsert(
                ['code' => $child['code']],
                [
                    'name' => $child['name'],
                    'type' => $child['type'],
                    'parent_id' => $parentIds[$child['parent_code']] ?? null,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }
};
