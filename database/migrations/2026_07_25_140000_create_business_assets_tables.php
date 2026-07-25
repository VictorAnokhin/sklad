<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('business_assets')) {
            Schema::create('business_assets', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('fid')->index();
                $table->unsignedBigInteger('asset_type_id')->nullable()->index();
                $table->string('type', 40)->default('equipment')->index();
                $table->string('name');
                $table->string('currency', 10)->default('UAH');
                $table->decimal('initial_cost', 15, 2)->default(0);
                $table->decimal('current_value', 15, 2)->default(0);
                $table->decimal('accumulated_depreciation', 15, 2)->default(0);
                $table->date('acquired_at')->nullable();
                $table->date('disposed_at')->nullable();
                $table->string('status', 30)->default('draft')->index();
                $table->text('description')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('asset_operations')) {
            Schema::create('asset_operations', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('fid')->index();
                $table->foreignId('business_asset_id')->nullable()->constrained('business_assets')->nullOnDelete();
                $table->string('operation_type', 40)->index();
                $table->date('operation_date')->index();
                $table->decimal('amount', 15, 2)->default(0);
                $table->decimal('carrying_amount', 15, 2)->default(0);
                $table->string('cash_account_id', 80)->nullable();
                $table->unsignedBigInteger('payment_type_id')->nullable()->index();
                $table->unsignedBigInteger('counterparty_id')->nullable()->index();
                $table->text('description')->nullable();
                $table->boolean('provodka')->default(false)->index();
                $table->unsignedBigInteger('ledger_transaction_id')->nullable()->index();
                $table->unsignedBigInteger('reversal_transaction_id')->nullable()->index();
                $table->timestamps();
            });
        }

        $this->seedAssetAccountingAccounts();
        $this->seedDefaultAssetTypes();
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_operations');
        Schema::dropIfExists('business_assets');
    }

    private function seedAssetAccountingAccounts(): void
    {
        if (! Schema::hasTable('accounts')) {
            return;
        }

        $accounts = [
            ['code' => '10', 'name' => 'Основные средства', 'type' => 'asset', 'parent_code' => null],
            ['code' => '103', 'name' => 'Здания и сооружения', 'type' => 'asset', 'parent_code' => '10'],
            ['code' => '104', 'name' => 'Машины и оборудование', 'type' => 'asset', 'parent_code' => '10'],
            ['code' => '12', 'name' => 'Нематериальные активы', 'type' => 'asset', 'parent_code' => null],
            ['code' => '125', 'name' => 'Разработка ПО и R&D', 'type' => 'asset', 'parent_code' => '12'],
            ['code' => '13', 'name' => 'Износ необоротных активов', 'type' => 'liability', 'parent_code' => null],
            ['code' => '131', 'name' => 'Амортизация основных средств', 'type' => 'liability', 'parent_code' => '13'],
            ['code' => '133', 'name' => 'Амортизация нематериальных активов', 'type' => 'liability', 'parent_code' => '13'],
            ['code' => '14', 'name' => 'Финансовые инвестиции', 'type' => 'asset', 'parent_code' => null],
            ['code' => '143', 'name' => 'Ценные бумаги', 'type' => 'asset', 'parent_code' => '14'],
            ['code' => '146', 'name' => 'Криптоактивы', 'type' => 'asset', 'parent_code' => '14'],
            ['code' => '70', 'name' => 'Доходы от реализации', 'type' => 'income', 'parent_code' => null],
            ['code' => '742', 'name' => 'Доход от реализации необоротных активов', 'type' => 'income', 'parent_code' => '70'],
            ['code' => '746', 'name' => 'Доход от переоценки активов', 'type' => 'income', 'parent_code' => '70'],
            ['code' => '90', 'name' => 'Себестоимость реализации', 'type' => 'expense', 'parent_code' => null],
            ['code' => '92', 'name' => 'Административные расходы', 'type' => 'expense', 'parent_code' => null],
            ['code' => '972', 'name' => 'Себестоимость реализованных необоротных активов', 'type' => 'expense', 'parent_code' => '90'],
            ['code' => '94', 'name' => 'Прочие операционные расходы', 'type' => 'expense', 'parent_code' => null],
            ['code' => '949', 'name' => 'Прочие расходы операционной деятельности', 'type' => 'expense', 'parent_code' => '94'],
            ['code' => '975', 'name' => 'Уценка и обесценение активов', 'type' => 'expense', 'parent_code' => '94'],
        ];

        foreach ($accounts as $account) {
            $parentId = $account['parent_code']
                ? DB::table('accounts')->where('code', $account['parent_code'])->value('id')
                : null;

            DB::table('accounts')->updateOrInsert(
                ['code' => $account['code']],
                [
                    'name' => $account['name'],
                    'type' => $account['type'],
                    'parent_id' => $parentId,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }

    private function seedDefaultAssetTypes(): void
    {
        if (! Schema::hasTable('conf')) {
            return;
        }

        foreach (['Оборудование', 'Недвижимость', 'Ценные бумаги', 'Криптоактивы', 'Разработка ПО / R&D'] as $name) {
            DB::table('conf')->updateOrInsert(
                ['type' => 'asset_type', 'firma' => '0', 'name' => $name],
                [
                    'status' => '1',
                    'vision' => '1',
                    'hide' => '0',
                ]
            );
        }
    }
};
