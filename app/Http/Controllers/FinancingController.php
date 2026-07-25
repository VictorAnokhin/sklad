<?php

namespace App\Http\Controllers;

use App\Models\Conf;
use App\Models\FinancingAgreement;
use App\Models\FinancingOperation;
use App\Services\AccountingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class FinancingController extends Controller
{
    public function index()
    {
        $fid = (string) session('fid', '');

        $agreements = FinancingAgreement::query()
            ->where('fid', (int) $fid)
            ->orderByDesc('id')
            ->get();

        $operations = FinancingOperation::query()
            ->leftJoin('financing_agreements as agreement', 'agreement.id', '=', 'financing_operations.financing_agreement_id')
            ->leftJoin('conf as cash', 'cash.id', '=', 'financing_operations.cash_account_id')
            ->leftJoin('conf as payment_type', 'payment_type.id', '=', 'financing_operations.payment_type_id')
            ->where('financing_operations.fid', (int) $fid)
            ->orderByDesc('financing_operations.operation_date')
            ->orderByDesc('financing_operations.id')
            ->limit(300)
            ->get([
                'financing_operations.*',
                DB::raw("COALESCE(NULLIF(agreement.name, ''), '') as agreement_name"),
                DB::raw("COALESCE(NULLIF(cash.name, ''), '') as cash_account_name"),
                DB::raw("COALESCE(NULLIF(payment_type.name, ''), '') as payment_type_name"),
            ]);

        $cashAccounts = DB::table('conf')
            ->where('type', 'oplata')
            ->where('firma', $fid)
            ->orderBy('name')
            ->get();

        $paymentTypes = Conf::query()
            ->where('type', 'reestr')
            ->where('vision', 'financing')
            ->orderBy('name')
            ->get()
            ->map(fn ($item) => Conf::decoratePaymentType($item));

        $clientStatuses = DB::table('conf')->where('type', 'tclient')->where('firma', $fid)->orderBy('name')->get();
        $clientGroups = DB::table('conf')->where('type', 'usergroup')->where('firma', $fid)->orderBy('name')->get();

        $summary = [
            'principal_balance' => (float) $agreements->sum('principal_balance'),
            'accrued_interest' => (float) $agreements->sum('accrued_interest'),
            'equity_amount' => (float) $agreements->sum('equity_amount'),
            'dividends_payable' => (float) $agreements->sum('dividends_payable'),
        ];

        return view('document.financing', compact(
            'fid',
            'agreements',
            'operations',
            'cashAccounts',
            'paymentTypes',
            'clientStatuses',
            'clientGroups',
            'summary'
        ));
    }

    public function storeAgreement(Request $request)
    {
        $fid = (string) session('fid', '');
        $validated = $request->validate([
            'agreement_type' => ['required', 'string', 'in:bank_loan,loan,convertible_loan,equity'],
            'name' => ['required', 'string', 'max:255'],
            'counterparty_id' => ['nullable', 'integer'],
            'counterparty_name' => ['nullable', 'string', 'max:255'],
            'agreement_number' => ['nullable', 'string', 'max:120'],
            'agreement_date' => ['nullable', 'date'],
            'maturity_date' => ['nullable', 'date'],
            'principal_amount' => ['nullable', 'numeric', 'min:0'],
            'interest_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'equity_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        FinancingAgreement::create([
            'fid' => (int) $fid,
            'counterparty_id' => (int) ($validated['counterparty_id'] ?? 0) ?: null,
            'agreement_type' => $validated['agreement_type'],
            'name' => trim((string) $validated['name']),
            'counterparty_name' => trim((string) ($validated['counterparty_name'] ?? '')),
            'agreement_number' => trim((string) ($validated['agreement_number'] ?? '')),
            'agreement_date' => $validated['agreement_date'] ?? null,
            'maturity_date' => $validated['maturity_date'] ?? null,
            'principal_amount' => round((float) ($validated['principal_amount'] ?? 0), 2),
            'principal_balance' => 0,
            'interest_rate' => round((float) ($validated['interest_rate'] ?? 0), 4),
            'equity_percent' => round((float) ($validated['equity_percent'] ?? 0), 4),
            'currency' => 'UAH',
            'status' => 'active',
            'description' => $validated['description'] ?? null,
        ]);

        return redirect()->route('document.financing.index')->with('success', 'Договор финансирования создан.');
    }

    public function storeOperation(Request $request, AccountingService $accounting)
    {
        $fid = (string) session('fid', '');
        $validated = $request->validate([
            'financing_agreement_id' => ['required', 'integer'],
            'operation_type' => ['required', 'string', 'in:loan_received,loan_principal_repaid,loan_interest_accrued,loan_interest_paid,equity_investment_received,dividend_accrued,dividend_paid'],
            'operation_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'cash_account_id' => ['nullable', 'string', 'max:80'],
            'payment_type_id' => ['nullable', 'integer'],
            'description' => ['nullable', 'string', 'max:2000'],
            'post_after_save' => ['nullable', 'boolean'],
        ]);

        $agreement = FinancingAgreement::query()
            ->where('fid', (int) $fid)
            ->where('id', (int) $validated['financing_agreement_id'])
            ->firstOrFail();

        $cashRequired = in_array($validated['operation_type'], [
            'loan_received',
            'loan_principal_repaid',
            'loan_interest_paid',
            'equity_investment_received',
            'dividend_paid',
        ], true);

        if ($cashRequired && trim((string) ($validated['cash_account_id'] ?? '')) === '') {
            return back()->withErrors(['cash_account_id' => 'Выберите денежный счет.'])->withInput();
        }

        $operation = FinancingOperation::create([
            'fid' => (int) $fid,
            'financing_agreement_id' => $agreement->id,
            'operation_type' => $validated['operation_type'],
            'operation_date' => $validated['operation_date'],
            'amount' => round((float) $validated['amount'], 2),
            'cash_account_id' => $validated['cash_account_id'] ?? null,
            'payment_type_id' => $validated['payment_type_id'] ?? null,
            'description' => $validated['description'] ?? null,
            'provodka' => false,
        ]);

        if ($request->boolean('post_after_save')) {
            $this->postOperationInternal($operation, $accounting);
        }

        return redirect()->route('document.financing.index')->with('success', 'Операция финансирования сохранена.');
    }

    public function post(FinancingOperation $operation, AccountingService $accounting)
    {
        $this->authorizeProjectOperation($operation);
        $this->postOperationInternal($operation, $accounting);

        return redirect()->route('document.financing.index')->with('success', 'Операция проведена.');
    }

    public function reverse(FinancingOperation $operation, AccountingService $accounting)
    {
        $this->authorizeProjectOperation($operation);
        $this->reverseOperationInternal($operation, $accounting);

        return redirect()->route('document.financing.index')->with('success', 'Проводка снята.');
    }

    public function destroy(FinancingOperation $operation)
    {
        $this->authorizeProjectOperation($operation);
        if ($operation->provodka) {
            return redirect()->route('document.financing.index')->with('error', 'Сначала снимите проводку операции.');
        }

        $operation->delete();

        return redirect()->route('document.financing.index')->with('success', 'Операция удалена.');
    }

    private function postOperationInternal(FinancingOperation $operation, AccountingService $accounting): void
    {
        $operation->refresh();
        if ($operation->provodka) {
            return;
        }

        DB::transaction(function () use ($operation, $accounting) {
            $agreement = $operation->agreement()->lockForUpdate()->first();
            $transaction = $accounting->createFinancingOperationTransaction($operation, $agreement, (string) $operation->fid);
            if (!$transaction || $transaction->entries->count() < 2) {
                throw new RuntimeException('Не удалось создать двойную запись по операции финансирования.');
            }

            $this->applyAgreementEffect($operation, 1, $agreement);
            $this->shiftCashAccountValue($operation, $this->cashDelta($operation, 1));

            $operation->ledger_transaction_id = $transaction->id;
            $operation->provodka = true;
            $operation->save();
        });
    }

    private function reverseOperationInternal(FinancingOperation $operation, AccountingService $accounting): void
    {
        $operation->refresh();
        if (!$operation->provodka) {
            return;
        }

        DB::transaction(function () use ($operation, $accounting) {
            $agreement = $operation->agreement()->lockForUpdate()->first();
            $transaction = $accounting->createFinancingOperationTransaction($operation, $agreement, (string) $operation->fid, true);
            if (!$transaction || $transaction->entries->count() < 2) {
                throw new RuntimeException('Не удалось создать сторно операции финансирования.');
            }

            $this->applyAgreementEffect($operation, -1, $agreement);
            $this->shiftCashAccountValue($operation, $this->cashDelta($operation, -1));

            $operation->reversal_transaction_id = $transaction->id;
            $operation->provodka = false;
            $operation->save();
        });
    }

    private function applyAgreementEffect(FinancingOperation $operation, int $direction, ?FinancingAgreement $agreement): void
    {
        if (!$agreement) {
            return;
        }

        $amount = round((float) $operation->amount, 2);
        if ($operation->operation_type === 'loan_received') {
            $agreement->principal_balance = max(0, (float) $agreement->principal_balance + ($amount * $direction));
        } elseif ($operation->operation_type === 'loan_principal_repaid') {
            $agreement->principal_balance = max(0, (float) $agreement->principal_balance - ($amount * $direction));
        } elseif ($operation->operation_type === 'loan_interest_accrued') {
            $agreement->accrued_interest = max(0, (float) $agreement->accrued_interest + ($amount * $direction));
        } elseif ($operation->operation_type === 'loan_interest_paid') {
            $agreement->accrued_interest = max(0, (float) $agreement->accrued_interest - ($amount * $direction));
        } elseif ($operation->operation_type === 'equity_investment_received') {
            $agreement->equity_amount = max(0, (float) $agreement->equity_amount + ($amount * $direction));
        } elseif ($operation->operation_type === 'dividend_accrued') {
            $agreement->dividends_payable = max(0, (float) $agreement->dividends_payable + ($amount * $direction));
        } elseif ($operation->operation_type === 'dividend_paid') {
            $agreement->dividends_payable = max(0, (float) $agreement->dividends_payable - ($amount * $direction));
        }

        if (
            $direction > 0
            && in_array($operation->operation_type, ['loan_principal_repaid', 'loan_interest_paid'], true)
            && in_array($agreement->agreement_type, ['bank_loan', 'loan', 'convertible_loan'], true)
            && (float) $agreement->principal_balance <= 0
            && (float) $agreement->accrued_interest <= 0
        ) {
            $agreement->status = 'closed';
        } else {
            $agreement->status = 'active';
        }
        $agreement->save();
    }

    private function cashDelta(FinancingOperation $operation, int $direction): float
    {
        $amount = round((float) $operation->amount, 2);
        $base = match ($operation->operation_type) {
            'loan_received', 'equity_investment_received' => $amount,
            'loan_principal_repaid', 'loan_interest_paid', 'dividend_paid' => -1 * $amount,
            default => 0.0,
        };

        return $base * $direction;
    }

    private function shiftCashAccountValue(FinancingOperation $operation, float $delta): void
    {
        $cashId = trim((string) $operation->cash_account_id);
        if (
            abs($delta) <= 0.0001
            || $cashId === ''
            || !Schema::hasTable('conf')
            || !Schema::hasColumn('conf', 'value')
        ) {
            return;
        }

        DB::table('conf')
            ->where('type', 'oplata')
            ->where('firma', (string) $operation->fid)
            ->where('id', $cashId)
            ->update(['value' => DB::raw('COALESCE(value, 0) + ' . (float) $delta)]);
    }

    private function authorizeProjectOperation(FinancingOperation $operation): void
    {
        abort_unless((string) $operation->fid === (string) session('fid', ''), 403);
    }
}
