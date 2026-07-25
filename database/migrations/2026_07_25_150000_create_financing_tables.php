<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('financing_agreements')) {
            Schema::create('financing_agreements', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('fid')->index();
                $table->unsignedBigInteger('counterparty_id')->nullable()->index();
                $table->string('agreement_type', 40)->default('bank_loan')->index();
                $table->string('name');
                $table->string('counterparty_name')->nullable();
                $table->string('agreement_number')->nullable();
                $table->date('agreement_date')->nullable();
                $table->date('maturity_date')->nullable();
                $table->decimal('principal_amount', 15, 2)->default(0);
                $table->decimal('principal_balance', 15, 2)->default(0);
                $table->decimal('interest_rate', 8, 4)->default(0);
                $table->decimal('accrued_interest', 15, 2)->default(0);
                $table->decimal('equity_amount', 15, 2)->default(0);
                $table->decimal('equity_percent', 8, 4)->default(0);
                $table->decimal('dividends_payable', 15, 2)->default(0);
                $table->string('currency', 10)->default('UAH');
                $table->string('status', 30)->default('active')->index();
                $table->text('description')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('financing_operations')) {
            Schema::create('financing_operations', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('fid')->index();
                $table->foreignId('financing_agreement_id')->nullable()->constrained('financing_agreements')->nullOnDelete();
                $table->string('operation_type', 60)->index();
                $table->date('operation_date')->index();
                $table->decimal('amount', 15, 2)->default(0);
                $table->string('cash_account_id', 80)->nullable();
                $table->unsignedBigInteger('payment_type_id')->nullable()->index();
                $table->text('description')->nullable();
                $table->boolean('provodka')->default(false)->index();
                $table->unsignedBigInteger('ledger_transaction_id')->nullable()->index();
                $table->unsignedBigInteger('reversal_transaction_id')->nullable()->index();
                $table->timestamps();
            });
        }

        $this->seedFinancingAccounts();
    }

    public function down(): void
    {
        Schema::dropIfExists('financing_operations');
        Schema::dropIfExists('financing_agreements');
    }

    private function seedFinancingAccounts(): void
    {
        if (! Schema::hasTable('accounts')) {
            return;
        }

        $accounts = [
            ['code' => '40', 'name' => 'Зарегистрированный капитал', 'type' => 'equity', 'parent_code' => null],
            ['code' => '42', 'name' => 'Дополнительный капитал', 'type' => 'equity', 'parent_code' => null],
            ['code' => '44', 'name' => 'Нераспределенная прибыль', 'type' => 'equity', 'parent_code' => null],
            ['code' => '50', 'name' => 'Долгосрочные кредиты банков', 'type' => 'liability', 'parent_code' => null],
            ['code' => '501', 'name' => 'Долгосрочные кредиты банков', 'type' => 'liability', 'parent_code' => '50'],
            ['code' => '55', 'name' => 'Прочие долгосрочные обязательства', 'type' => 'liability', 'parent_code' => null],
            ['code' => '60', 'name' => 'Краткосрочные займы', 'type' => 'liability', 'parent_code' => null],
            ['code' => '601', 'name' => 'Краткосрочные кредиты банков', 'type' => 'liability', 'parent_code' => '60'],
            ['code' => '67', 'name' => 'Расчеты с участниками', 'type' => 'liability', 'parent_code' => null],
            ['code' => '671', 'name' => 'Расчеты по начисленным дивидендам', 'type' => 'liability', 'parent_code' => '67'],
            ['code' => '68', 'name' => 'Расчеты по прочим операциям', 'type' => 'liability', 'parent_code' => null],
            ['code' => '684', 'name' => 'Расчеты по начисленным процентам', 'type' => 'liability', 'parent_code' => '68'],
            ['code' => '95', 'name' => 'Финансовые расходы', 'type' => 'expense', 'parent_code' => null],
            ['code' => '951', 'name' => 'Проценты за кредит', 'type' => 'expense', 'parent_code' => '95'],
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
};
