<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinancingAgreement extends Model
{
    protected $fillable = [
        'fid',
        'counterparty_id',
        'agreement_type',
        'name',
        'counterparty_name',
        'agreement_number',
        'agreement_date',
        'maturity_date',
        'principal_amount',
        'principal_balance',
        'interest_rate',
        'accrued_interest',
        'equity_amount',
        'equity_percent',
        'dividends_payable',
        'currency',
        'status',
        'description',
    ];

    protected $casts = [
        'fid' => 'integer',
        'counterparty_id' => 'integer',
        'agreement_date' => 'date',
        'maturity_date' => 'date',
        'principal_amount' => 'decimal:2',
        'principal_balance' => 'decimal:2',
        'interest_rate' => 'decimal:4',
        'accrued_interest' => 'decimal:2',
        'equity_amount' => 'decimal:2',
        'equity_percent' => 'decimal:4',
        'dividends_payable' => 'decimal:2',
    ];

    public function operations()
    {
        return $this->hasMany(FinancingOperation::class);
    }

    public static function typeOptions(): array
    {
        return [
            'bank_loan' => 'Банковский кредит',
            'loan' => 'Заем',
            'convertible_loan' => 'Конвертируемый заем',
            'equity' => 'Инвестор в капитал',
        ];
    }

    public static function typeLabel(?string $type): string
    {
        return self::typeOptions()[$type ?: ''] ?? 'Финансирование';
    }
}
