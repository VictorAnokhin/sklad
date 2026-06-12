<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (
            ! Schema::hasTable('accounts')
            || ! Schema::hasTable('conf')
            || ! Schema::hasColumn('conf', 'debit_account_id')
            || ! Schema::hasColumn('conf', 'credit_account_id')
        ) {
            return;
        }

        $this->ensureAccount('60', 'Краткосрочные займы', 'liability');
        $this->ensureAccount('601', 'Краткосрочные кредиты', 'liability', '60');
        $this->ensureAccount('64', 'Расчеты по налогам и платежам', 'liability');
        $this->ensureAccount('641', 'Расчеты по налогам', 'liability', '64');
        $this->ensureAccount('66', 'Расчеты по оплате труда', 'liability');
        $this->ensureAccount('661', 'Расчеты по заработной плате', 'liability', '66');
        $this->ensureAccount('71', 'Прочий операционный доход', 'income');
        $this->ensureAccount('719', 'Прочие доходы от операционной деятельности', 'income', '71');

        DB::table('conf')
            ->where('type', 'reestr')
            ->orderBy('id')
            ->get(['id', 'firma', 'name', 'doc', 'debit_account_id', 'credit_account_id'])
            ->each(function ($paymentType): void {
                $name = mb_strtolower(trim((string) $paymentType->name));
                $documents = array_filter(array_map(
                    static fn ($value) => strtoupper(trim($value)),
                    explode(',', (string) $paymentType->doc)
                ));

                $debitAccountId = null;
                $creditAccountId = null;

                if (in_array('ZP', $documents, true)) {
                    $debitAccountId = $this->detailAccount(
                        '661',
                        (int) $paymentType->firma,
                        (int) $paymentType->id,
                        (string) $paymentType->name,
                        'liability'
                    );
                } elseif (in_array('RO', $documents, true) && ! in_array('PO', $documents, true)) {
                    if ($this->containsAny($name, ['налог', 'подат', 'ндс', 'пдв'])) {
                        $debitAccountId = $this->detailAccount(
                            '641',
                            (int) $paymentType->firma,
                            (int) $paymentType->id,
                            (string) $paymentType->name,
                            'liability'
                        );
                    } elseif ($this->containsAny($name, ['кредит', 'loan', 'займ', 'позик'])) {
                        $debitAccountId = $this->detailAccount(
                            '601',
                            (int) $paymentType->firma,
                            (int) $paymentType->id,
                            (string) $paymentType->name,
                            'liability'
                        );
                    } else {
                        $debitAccountId = $this->detailAccount(
                            '949',
                            (int) $paymentType->firma,
                            (int) $paymentType->id,
                            (string) $paymentType->name,
                            'expense'
                        );
                    }
                } elseif (
                    in_array('PO', $documents, true)
                    && ! in_array('RO', $documents, true)
                    && $this->containsAny($name, ['процент', 'відсот', 'interest'])
                ) {
                    $creditAccountId = $this->detailAccount(
                        '719',
                        (int) $paymentType->firma,
                        (int) $paymentType->id,
                        (string) $paymentType->name,
                        'income'
                    );
                }

                if ($debitAccountId === null && $creditAccountId === null) {
                    return;
                }

                DB::table('conf')
                    ->where('id', $paymentType->id)
                    ->update([
                        'debit_account_id' => $paymentType->debit_account_id ?: $debitAccountId,
                        'credit_account_id' => $paymentType->credit_account_id ?: $creditAccountId,
                    ]);
            });
    }

    public function down(): void
    {
        if (! Schema::hasTable('conf')) {
            return;
        }

        DB::table('conf')
            ->where('type', 'reestr')
            ->where(function ($query) {
                $query->whereIn('debit_account_id', $this->generatedAccountIds())
                    ->orWhereIn('credit_account_id', $this->generatedAccountIds());
            })
            ->update([
                'debit_account_id' => null,
                'credit_account_id' => null,
            ]);
    }

    private function detailAccount(
        string $rootCode,
        int $companyId,
        int $paymentTypeId,
        string $name,
        string $type
    ): int {
        return $this->ensureAccount(
            "{$rootCode}.{$companyId}.{$paymentTypeId}",
            "{$rootCode} {$name}",
            $type,
            $rootCode
        );
    }

    private function ensureAccount(string $code, string $name, string $type, ?string $parentCode = null): int
    {
        $parentId = $parentCode
            ? DB::table('accounts')->where('code', $parentCode)->value('id')
            : null;
        $existing = DB::table('accounts')->where('code', $code)->first();

        if ($existing) {
            return (int) $existing->id;
        }

        $values = [
            'code' => $code,
            'name' => $name,
            'type' => $type,
            'parent_id' => $parentId,
            'created_at' => now(),
            'updated_at' => now(),
        ];
        if (Schema::hasColumn('accounts', 'currency')) {
            $values['currency'] = 'UAH';
        }

        return (int) DB::table('accounts')->insertGetId($values);
    }

    private function containsAny(string $value, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (str_contains($value, $needle)) {
                return true;
            }
        }

        return false;
    }

    private function generatedAccountIds(): array
    {
        if (! Schema::hasTable('accounts')) {
            return [];
        }

        return DB::table('accounts')
            ->where(function ($query) {
                foreach (['949.', '641.', '601.', '661.', '719.'] as $prefix) {
                    $query->orWhere('code', 'like', "{$prefix}%");
                }
            })
            ->pluck('id')
            ->all();
    }
};
