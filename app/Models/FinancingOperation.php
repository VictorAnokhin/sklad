<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinancingOperation extends Model
{
    protected $fillable = [
        'fid',
        'financing_agreement_id',
        'operation_type',
        'operation_date',
        'amount',
        'cash_account_id',
        'payment_type_id',
        'description',
        'provodka',
        'ledger_transaction_id',
        'reversal_transaction_id',
    ];

    protected $casts = [
        'fid' => 'integer',
        'financing_agreement_id' => 'integer',
        'operation_date' => 'date',
        'amount' => 'decimal:2',
        'provodka' => 'boolean',
        'ledger_transaction_id' => 'integer',
        'reversal_transaction_id' => 'integer',
    ];

    public function agreement()
    {
        return $this->belongsTo(FinancingAgreement::class, 'financing_agreement_id');
    }

    public static function operationOptions(): array
    {
        return [
            'loan_received' => 'Получение кредита/займа',
            'loan_principal_repaid' => 'Возврат тела кредита',
            'loan_interest_accrued' => 'Начисление процентов',
            'loan_interest_paid' => 'Оплата процентов',
            'equity_investment_received' => 'Инвестиция в капитал',
            'dividend_accrued' => 'Начисление дивидендов',
            'dividend_paid' => 'Выплата дивидендов',
        ];
    }

    public static function operationLabel(?string $type): string
    {
        return self::operationOptions()[$type ?: ''] ?? 'Операция финансирования';
    }
}
