<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Conf;
use App\Models\Entry;
use App\Models\Transaction as LedgerTransaction;
use App\Models\ZBody;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class AccountingService
{
    private const DEFAULT_CURRENCY = 'UAH';

    public function createTransaction(array $entries, string $description = null, array $attributes = []): ?LedgerTransaction
    {
        if (!$this->isAvailable()) {
            return null;
        }

        $normalizedEntries = collect($entries)
            ->map(fn (array $entry) => $this->normalizeEntry($entry, $attributes))
            ->filter(fn (?array $entry) => $entry !== null)
            ->values();

        if ($normalizedEntries->isEmpty()) {
            return null;
        }

        $totalDebit = round((float) $normalizedEntries->sum('debit'), 2);
        $totalCredit = round((float) $normalizedEntries->sum('credit'), 2);

        if (abs($totalDebit - $totalCredit) > 0.009) {
            throw new RuntimeException('Debit and Credit must be equal');
        }

        return DB::transaction(function () use ($normalizedEntries, $description, $attributes, $totalDebit): LedgerTransaction {
            $transaction = LedgerTransaction::create([
                'date' => $attributes['date'] ?? now()->toDateString(),
                'description' => $description,
                'company_id' => (int) ($attributes['company_id'] ?? 0),
                'user_id' => $attributes['user_id'] ?? $this->resolveActorUserId(),
                'reference_type' => $attributes['reference_type'] ?? null,
                'reference_id' => isset($attributes['reference_id']) ? (string) $attributes['reference_id'] : null,
                'currency' => $attributes['currency'] ?? self::DEFAULT_CURRENCY,
                'amount' => $attributes['amount'] ?? $totalDebit,
                'amount_base' => $attributes['amount_base'] ?? $totalDebit,
            ]);

            foreach ($normalizedEntries as $entry) {
                Entry::create([
                    'transaction_id' => $transaction->id,
                    'account_id' => $entry['account_id'],
                    'debit' => $entry['debit'],
                    'credit' => $entry['credit'],
                    'company_id' => (int) ($attributes['company_id'] ?? 0),
                    'user_id' => $attributes['user_id'] ?? $this->resolveActorUserId(),
                    'reference_type' => $attributes['reference_type'] ?? null,
                    'reference_id' => isset($attributes['reference_id']) ? (string) $attributes['reference_id'] : null,
                    'currency' => $attributes['currency'] ?? self::DEFAULT_CURRENCY,
                    'amount' => max($entry['debit'], $entry['credit']),
                    'amount_base' => max($entry['debit'], $entry['credit']),
                ]);
            }

            return $transaction->load('entries');
        });
    }

    public function createDocumentTransaction(
        string $referenceType,
        string|int $referenceId,
        string $docType,
        object $document,
        iterable $lineItems,
        string $fid,
        bool $reverse = false
    ): ?LedgerTransaction {
        if (!$this->isAvailable()) {
            return null;
        }

        if ($reverse) {
            $original = $this->activeOriginalTransaction($referenceType, (string) $referenceId, (int) $fid);
            if ($original) {
                return $this->reverseStoredTransaction($original, $document, "{$referenceType}:reversal");
            }
        }

        $entries = $this->entriesForDocument($docType, $document, $lineItems, $fid);
        if ($entries === []) {
            return null;
        }

        if ($reverse) {
            $entries = $this->reverseEntries($entries);
        }

        $description = $this->makeDescription($docType, $document, $reverse);
        $amount = round((float) collect($entries)->sum('debit'), 2);

        return $this->createTransaction($entries, $description, [
            'date' => $this->normalizeDate((string) ($document->data ?? now()->toDateString())),
            'company_id' => (int) $fid,
            'reference_type' => $referenceType,
            'reference_id' => (string) $referenceId,
            'amount' => $amount,
            'amount_base' => $amount,
        ]);
    }

    private function activeOriginalTransaction(
        string $referenceType,
        string $referenceId,
        int $companyId
    ): ?LedgerTransaction {
        $reversalReferenceType = "{$referenceType}:reversal";

        return LedgerTransaction::query()
            ->where('company_id', $companyId)
            ->where('reference_type', $referenceType)
            ->where('reference_id', $referenceId)
            ->whereNotExists(function ($query) use ($reversalReferenceType) {
                $query->selectRaw('1')
                    ->from('transactions as reversal')
                    ->whereColumn('reversal.company_id', 'transactions.company_id')
                    ->whereColumn('reversal.reference_id', 'transactions.reference_id')
                    ->where('reversal.reference_type', $reversalReferenceType)
                    ->whereColumn('reversal.id', '>', 'transactions.id');
            })
            ->latest('id')
            ->first();
    }

    private function reverseStoredTransaction(
        LedgerTransaction $original,
        object $document,
        string $reversalReferenceType
    ): LedgerTransaction {
        $entries = $original->entries()
            ->get(['account_id', 'debit', 'credit'])
            ->map(fn (Entry $entry): array => [
                'account_id' => $entry->account_id,
                'debit' => (float) $entry->credit,
                'credit' => (float) $entry->debit,
            ])
            ->all();

        return $this->createTransaction($entries, "Сторно {$original->description}", [
            'date' => $this->normalizeDate((string) ($document->data ?? now()->toDateString())),
            'company_id' => (int) $original->company_id,
            'reference_type' => $reversalReferenceType,
            'reference_id' => (string) $original->reference_id,
            'amount' => (float) $original->amount,
            'amount_base' => (float) $original->amount_base,
        ]);
    }

    public function createProjectMirrorTransaction(
        string $referenceType,
        string|int $referenceId,
        string $docType,
        object $document,
        iterable $lineItems,
        string $fid,
        bool $reverse = false
    ): ?LedgerTransaction {
        if (! $this->isAvailable() || ! in_array($docType, ['PN', 'RN', 'PO', 'RO'], true)) {
            return null;
        }

        $mirrorReferenceType = "{$referenceType}:project-mirror";
        $reversalReferenceType = "{$mirrorReferenceType}:reversal";

        if ($reverse) {
            $original = LedgerTransaction::query()
                ->where('reference_type', $mirrorReferenceType)
                ->where('reference_id', (string) $referenceId)
                ->latest('id')
                ->first();

            if (! $original) {
                return null;
            }

            return $this->reverseStoredTransaction($original, $document, $reversalReferenceType);
        }

        $projectId = $this->counterpartyProjectId($document, $fid);
        if ($projectId === null) {
            return null;
        }

        $entries = $this->entriesForProjectMirror($docType, $document, $lineItems, $projectId, $fid);
        if ($entries === []) {
            return null;
        }

        $amount = round((float) collect($entries)->sum('debit'), 2);

        return $this->createTransaction(
            $entries,
            "Зеркало проекта: ".$this->makeDescription($docType, $document, false),
            [
                'date' => $this->normalizeDate((string) ($document->data ?? now()->toDateString())),
                'company_id' => $projectId,
                'reference_type' => $mirrorReferenceType,
                'reference_id' => (string) $referenceId,
                'amount' => $amount,
                'amount_base' => $amount,
            ]
        );
    }

    public function reverseEntries(array $entries): array
    {
        return array_map(static function (array $entry): array {
            return [
                'account_id' => $entry['account_id'],
                'debit' => (float) ($entry['credit'] ?? 0),
                'credit' => (float) ($entry['debit'] ?? 0),
            ];
        }, $entries);
    }

    public function entriesForDocument(string $docType, object $document, iterable $lineItems, string $fid): array
    {
        if (!$this->isAvailable()) {
            return [];
        }

        return match ($docType) {
            'PO', 'CPO' => $this->entriesForMoneyReceipt($document, $fid),
            'PPO' => $this->entriesForMoneyReceipt($document, $fid),
            'RO', 'CRO' => $this->entriesForMoneyIssue($document, $fid),
            'PRO' => $this->entriesForMoneyIssue($document, $fid),
            'ZP' => $this->entriesForMoneyIssue($document, $fid),
            'PP' => $this->entriesForDepositOperation($document, $fid),
            'PPP' => $this->entriesForBalanceExchange($document, $fid),
            'PN' => $this->entriesForPurchaseInvoice($document, $lineItems, $fid),
            'RN' => $this->entriesForSalesInvoice($document, $lineItems, $fid),
            default => [],
        };
    }

    private function entriesForMoneyReceipt(object $document, string $fid): array
    {
        $summa = round((float) ($document->summa ?? 0), 2);
        if ($summa <= 0) {
            return [];
        }

        $cashAccount = $this->cashAccount($fid, (string) ($document->oplata ?? $document->money ?? ''));
        $loan = $this->loanParentDocument($document, $fid);
        if ($loan !== null) {
            ['principal' => $principal, 'interest' => $interest] = $this->loanRepaymentSplit($summa, $loan);
            $entries = [
                [
                    'account_id' => $cashAccount->id,
                    'debit' => $summa,
                    'credit' => 0,
                ],
            ];

            if ($principal > 0) {
                $entries[] = [
                    'account_id' => $this->lendingReceivableAccount(
                        $fid,
                        (string) ($document->client1 ?? $loan->client1 ?? '')
                    )->id,
                    'debit' => 0,
                    'credit' => $principal,
                ];
            }
            if ($interest > 0) {
                $entries[] = [
                    'account_id' => $this->loanInterestIncomeAccount($fid)->id,
                    'debit' => 0,
                    'credit' => $interest,
                ];
            }

            return $entries;
        }

        $paymentTypeBinding = Conf::paymentTypeAccountBinding((string) ($document->reestr ?? ''));
        $receivableAccount = $this->receivableAccount($fid, (string) ($document->client1 ?? ''));

        return [
            [
                'account_id' => $paymentTypeBinding['debit_account_id'] ?: $cashAccount->id,
                'debit' => $summa,
                'credit' => 0,
            ],
            [
                'account_id' => $paymentTypeBinding['credit_account_id'] ?: $receivableAccount->id,
                'debit' => 0,
                'credit' => $summa,
            ],
        ];
    }

    private function entriesForMoneyIssue(object $document, string $fid): array
    {
        $summa = round((float) ($document->summa ?? 0), 2);
        if ($summa <= 0) {
            return [];
        }

        $paymentTypeBinding = Conf::paymentTypeAccountBinding((string) ($document->reestr ?? ''));
        $cashAccount = $this->cashAccount($fid, (string) ($document->oplata ?? $document->money ?? ''));
        $counterpartyId = trim((string) ($document->client1 ?? ''));
        $isLoanIssue = $this->isLoanIssue($document, $fid);
        $debitAccount = $isLoanIssue
            ? $this->lendingReceivableAccount($fid, $counterpartyId)
            : ($counterpartyId !== '' && $counterpartyId !== '0'
                ? $this->payableAccount($fid, $counterpartyId)
                : $this->operatingExpenseAccount($fid));

        return [
            [
                'account_id' => $isLoanIssue
                    ? $debitAccount->id
                    : ($paymentTypeBinding['debit_account_id'] ?: $debitAccount->id),
                'debit' => $summa,
                'credit' => 0,
            ],
            [
                'account_id' => $paymentTypeBinding['credit_account_id'] ?: $cashAccount->id,
                'debit' => 0,
                'credit' => $summa,
            ],
        ];
    }

    private function isLoanIssue(object $document, string $fid): bool
    {
        return $this->loanParentDocument($document, $fid) !== null;
    }

    private function loanParentDocument(object $document, string $fid): ?object
    {
        $parentDocumentId = trim((string) ($document->docid ?? ''));
        if ($parentDocumentId === '' || $parentDocumentId === '0' || ! Schema::hasTable('document')) {
            return null;
        }

        return DB::table('document')
            ->where('id', $parentDocumentId)
            ->where('firma', $fid)
            ->where('type', 'CRDT')
            ->where(function ($query) {
                $query->where('typeproduct', 'credit_request')
                    ->orWhere('numorder', 'AV8-LOAN')
                    ->orWhere('content', 'like', '%[AV8_LOAN_REQUEST]%');
            })
            ->first();
    }

    private function loanRepaymentSplit(float $payment, object $loan): array
    {
        $principalTotal = max(0, round((float) ($loan->summa ?? 0), 2));
        $content = (string) ($loan->content ?? '');
        $annualRate = $this->loanContentNumber($content, 'Процентная ставка заемщика');
        $termMonths = $this->loanTermMonths($content);
        $totalDue = round($principalTotal * (1 + ($annualRate / 100) * ($termMonths / 12)), 2);

        if ($principalTotal <= 0 || $totalDue <= 0) {
            return ['principal' => $payment, 'interest' => 0.0];
        }

        $principal = round($payment * ($principalTotal / $totalDue), 2);

        return [
            'principal' => $principal,
            'interest' => round($payment - $principal, 2),
        ];
    }

    private function loanContentNumber(string $content, string $label): float
    {
        if (preg_match('/^'.preg_quote($label, '/').':\s*(.+)$/mu', $content, $matches) !== 1) {
            return 0.0;
        }

        return (float) preg_replace('/[^\d.]/', '', str_replace(',', '.', (string) $matches[1]));
    }

    private function loanTermMonths(string $content): int
    {
        if (preg_match('/^Срок кредита:\s*(.+)$/mu', $content, $matches) !== 1) {
            return 12;
        }

        $term = mb_strtolower(trim((string) $matches[1]));
        preg_match('/\d+/', $term, $number);
        $isYears = str_contains($term, 'год')
            || str_contains($term, 'лет')
            || str_contains($term, 'рок')
            || str_contains($term, 'рік');
        $value = max(1, (int) ($number[0] ?? ($isYears ? 1 : 12)));

        return $isYears ? $value * 12 : $value;
    }

    private function entriesForDepositOperation(object $document, string $fid): array
    {
        $summa = round((float) ($document->summa ?? 0), 2);
        if ($summa <= 0) {
            return [];
        }

        $mode = (string) ($document->docum ?? 'topup');
        $deposit = $this->depositAccount($fid, (string) ($document->money ?? ''));

        return match ($mode) {
            'topup' => [
                ['account_id' => $deposit->id, 'debit' => $summa, 'credit' => 0],
                [
                    'account_id' => $this->cashAccount(
                        $fid,
                        $this->depositOperationCashAccountId($document, 'topup')
                    )->id,
                    'debit' => 0,
                    'credit' => $summa,
                ],
            ],
            'withdraw' => [
                [
                    'account_id' => $this->cashAccount(
                        $fid,
                        $this->depositOperationCashAccountId($document, 'withdraw')
                    )->id,
                    'debit' => $summa,
                    'credit' => 0,
                ],
                ['account_id' => $deposit->id, 'debit' => 0, 'credit' => $summa],
            ],
            'exchange' => $this->entriesForCashExchange($document, $fid),
            default => [],
        };
    }

    private function depositOperationCashAccountId(object $document, string $mode): string
    {
        $primary = $mode === 'withdraw'
            ? trim((string) ($document->oplata2 ?? ''))
            : trim((string) ($document->oplata ?? ''));
        $fallback = $mode === 'withdraw'
            ? trim((string) ($document->oplata ?? ''))
            : trim((string) ($document->oplata2 ?? ''));

        return $primary !== '' && $primary !== '0' ? $primary : $fallback;
    }

    private function entriesForCashExchange(object $document, string $fid): array
    {
        $amountTo = round((float) ($document->summa2 ?? 0), 2);
        if ($amountTo <= 0) {
            $amountTo = round((float) ($document->summa ?? 0), 2);
        }

        if ($amountTo <= 0) {
            return [];
        }

        $commission = round((float) ($document->commission_amount ?? 0), 2);
        $cashFrom = $this->cashAccount($fid, (string) ($document->oplata ?? ''));
        $cashTo = $this->cashAccount($fid, (string) ($document->oplata2 ?? ''));
        $entries = [
            ['account_id' => $cashTo->id, 'debit' => $amountTo, 'credit' => 0],
            ['account_id' => $cashFrom->id, 'debit' => 0, 'credit' => $amountTo + $commission],
        ];

        if ($commission > 0) {
            $entries[] = ['account_id' => $this->operatingExpenseAccount($fid)->id, 'debit' => $commission, 'credit' => 0];
        }

        return $entries;
    }

    private function entriesForBalanceExchange(object $document, string $fid): array
    {
        $amountFrom = round((float) ($document->summa ?? 0), 2);
        $amountTo = round((float) (($document->summa2 ?? 0) > 0 ? $document->summa2 : $document->summa), 2);

        if ($amountFrom <= 0 || $amountTo <= 0) {
            return [];
        }

        $currencyFrom = $this->normalizeCurrencyCode($document->currency_from ?? 'UAH');
        $currencyTo = $this->normalizeCurrencyCode($document->currency_to ?? $currencyFrom);
        $userId = (string) ($document->client2 ?? '');
        $baseAmount = $this->exchangeBaseAmount($amountFrom, $amountTo, $currencyFrom, $currencyTo);

        return [
            [
                'account_id' => $this->userBalanceAccount($fid, $userId, $currencyTo)->id,
                'debit' => $baseAmount,
                'credit' => 0,
                'currency' => $currencyTo,
            ],
            [
                'account_id' => $this->userBalanceAccount($fid, $userId, $currencyFrom)->id,
                'debit' => 0,
                'credit' => $baseAmount,
                'currency' => $currencyFrom,
            ],
        ];
    }

    private function entriesForPurchaseInvoice(object $document, iterable $lineItems, string $fid): array
    {
        $summa = round(collect($lineItems)->sum(function ($item): float {
            $quantity = (float) ($item->pcount ?? 0);
            $lineTotal = (float) ($item->psumma ?? 0);

            return $lineTotal > 0
                ? $lineTotal
                : $quantity * (float) ($item->pprice ?? 0);
        }), 2);
        if ($summa <= 0) {
            return [];
        }

        $inventoryAccount = $this->inventoryAccount($fid);
        $payableAccount = $this->payableAccount($fid, (string) ($document->client1 ?? ''));

        return [
            ['account_id' => $inventoryAccount->id, 'debit' => $summa, 'credit' => 0],
            ['account_id' => $payableAccount->id, 'debit' => 0, 'credit' => $summa],
        ];
    }

    private function entriesForSalesInvoice(object $document, iterable $lineItems, string $fid): array
    {
        $summa = round((float) ($document->summa ?? 0), 2);
        $cost = round($this->resolveCostOfSales($lineItems, $fid), 2);
        $entries = [];

        if ($summa > 0) {
            $entries[] = [
                'account_id' => $this->receivableAccount($fid, (string) ($document->client1 ?? ''))->id,
                'debit' => $summa,
                'credit' => 0,
            ];
            $entries[] = [
                'account_id' => $this->revenueAccount($fid)->id,
                'debit' => 0,
                'credit' => $summa,
            ];
        }

        if ($cost > 0) {
            $entries[] = [
                'account_id' => $this->costOfSalesAccount($fid)->id,
                'debit' => $cost,
                'credit' => 0,
            ];
            $entries[] = [
                'account_id' => $this->inventoryAccount($fid)->id,
                'debit' => 0,
                'credit' => $cost,
            ];
        }

        return $entries;
    }

    private function entriesForProjectMirror(
        string $docType,
        object $document,
        iterable $lineItems,
        int $projectId,
        string $sourceCompanyId
    ): array {
        $amount = $docType === 'PN'
            ? round(collect($lineItems)->sum(function ($item): float {
                $lineTotal = (float) ($item->psumma ?? 0);

                return $lineTotal > 0
                    ? $lineTotal
                    : (float) ($item->pcount ?? 0) * (float) ($item->pprice ?? 0);
            }), 2)
            : round((float) ($document->summa ?? 0), 2);

        if ($amount <= 0) {
            return [];
        }

        $projectFid = (string) $projectId;
        $sourceCounterparty = "company-{$sourceCompanyId}";
        $intercompanyCash = $this->projectMirrorCashbox($projectFid, $sourceCompanyId);

        return match ($docType) {
            'PN' => [
                [
                    'account_id' => $this->receivableAccount($projectFid, $sourceCounterparty)->id,
                    'debit' => $amount,
                    'credit' => 0,
                ],
                [
                    'account_id' => $this->revenueAccount($projectFid)->id,
                    'debit' => 0,
                    'credit' => $amount,
                ],
            ],
            'RN' => [
                [
                    'account_id' => $this->inventoryAccount($projectFid)->id,
                    'debit' => $amount,
                    'credit' => 0,
                ],
                [
                    'account_id' => $this->payableAccount($projectFid, $sourceCounterparty)->id,
                    'debit' => 0,
                    'credit' => $amount,
                ],
            ],
            'PO' => [
                [
                    'account_id' => $this->payableAccount($projectFid, $sourceCounterparty)->id,
                    'debit' => $amount,
                    'credit' => 0,
                ],
                [
                    'account_id' => $this->cashAccount($projectFid, $intercompanyCash)->id,
                    'debit' => 0,
                    'credit' => $amount,
                ],
            ],
            'RO' => [
                [
                    'account_id' => $this->cashAccount($projectFid, $intercompanyCash)->id,
                    'debit' => $amount,
                    'credit' => 0,
                ],
                [
                    'account_id' => $this->receivableAccount($projectFid, $sourceCounterparty)->id,
                    'debit' => 0,
                    'credit' => $amount,
                ],
            ],
            default => [],
        };
    }

    private function projectMirrorCashbox(string $projectFid, string $sourceCompanyId): string
    {
        if (Schema::hasColumn('conf', 'is_default')) {
            $cashboxId = DB::table('conf')
                ->where('type', 'oplata')
                ->where('firma', $projectFid)
                ->where('is_default', 1)
                ->orderBy('id')
                ->value('id');

            if ($cashboxId !== null && (int) $cashboxId > 0) {
                return (string) $cashboxId;
            }
        }

        return "intercompany-{$sourceCompanyId}";
    }

    private function counterpartyProjectId(object $document, string $fid): ?int
    {
        $counterpartyId = trim((string) ($document->client1 ?? ''));
        if ($counterpartyId === '' || $counterpartyId === '0' || ! Schema::hasColumn('users', 'project_id')) {
            return null;
        }

        $projectId = DB::table('users')
            ->where('id', $counterpartyId)
            ->where('firma', $fid)
            ->value('project_id');

        if ($projectId === null || (string) $projectId === (string) $fid) {
            return null;
        }

        return DB::table('project')->where('id', $projectId)->exists()
            ? (int) $projectId
            : null;
    }

    private function normalizeEntry(array $entry, array $attributes): ?array
    {
        $accountId = (int) ($entry['account_id'] ?? 0);
        $debit = round((float) ($entry['debit'] ?? 0), 2);
        $credit = round((float) ($entry['credit'] ?? 0), 2);

        if ($accountId <= 0 || ($debit <= 0 && $credit <= 0)) {
            return null;
        }

        if ($debit > 0 && $credit > 0) {
            throw new RuntimeException('Entry must contain either debit or credit');
        }

        return [
            'account_id' => $accountId,
            'debit' => $debit,
            'credit' => $credit,
            'currency' => $entry['currency'] ?? ($attributes['currency'] ?? self::DEFAULT_CURRENCY),
        ];
    }

    private function resolveCostOfSales(iterable $lineItems, string $fid): float
    {
        return collect($lineItems)->reduce(function (float $carry, $item) use ($fid) {
            $unitCostRaw = (string) ($item->zvalue ?? '');
            $unitCost = $unitCostRaw !== ''
                ? (float) $unitCostRaw
                : (float) ZBody::resolveUnitCost((string) ($item->pnum ?? ''), $fid);

            return $carry + ($unitCost * (float) ($item->pcount ?? 0));
        }, 0.0);
    }

    private function cashAccount(string $fid, string $cashId): Account
    {
        $cashId = trim($cashId) !== '' ? trim($cashId) : 'default';
        $name = $this->confName('oplata', $cashId) ?: 'Касса';

        return $this->ensureAccount(
            "301.{$fid}.{$cashId}",
            "Касса {$name}",
            'asset',
            '301'
        );
    }

    private function depositAccount(string $fid, string $depositId): Account
    {
        $depositId = trim($depositId) !== '' ? trim($depositId) : 'default';
        $name = $this->confName('deposit', $depositId) ?: 'Депозит';

        return $this->ensureAccount(
            "311.{$fid}.{$depositId}",
            "Депозит {$name}",
            'asset',
            '311'
        );
    }

    private function receivableAccount(string $fid, string $clientId): Account
    {
        $clientId = trim($clientId) !== '' && trim($clientId) !== '0' ? trim($clientId) : 'generic';
        $name = $clientId === 'generic' ? 'Покупатели' : ($this->userName($clientId) ?: "Контрагент {$clientId}");

        return $this->ensureAccount(
            "361.{$fid}.{$clientId}",
            "Расчеты с покупателем {$name}",
            'asset',
            '361'
        );
    }

    private function payableAccount(string $fid, string $clientId): Account
    {
        $clientId = trim($clientId) !== '' && trim($clientId) !== '0' ? trim($clientId) : 'generic';
        $name = $clientId === 'generic' ? 'Поставщики' : ($this->userName($clientId) ?: "Контрагент {$clientId}");

        return $this->ensureAccount(
            "631.{$fid}.{$clientId}",
            "Расчеты с поставщиком {$name}",
            'liability',
            '631'
        );
    }

    private function lendingReceivableAccount(string $fid, string $clientId): Account
    {
        $clientId = trim($clientId) !== '' && trim($clientId) !== '0' ? trim($clientId) : 'generic';
        $name = $clientId === 'generic' ? 'Заемщики' : ($this->userName($clientId) ?: "Заемщик {$clientId}");

        return $this->ensureAccount(
            "377.{$fid}.{$clientId}",
            "Выданный кредит: {$name}",
            'asset',
            '377'
        );
    }

    private function loanInterestIncomeAccount(string $fid): Account
    {
        return $this->ensureAccount(
            "732.{$fid}",
            "Процентный доход по кредитованию {$fid}",
            'income',
            '732'
        );
    }

    private function inventoryAccount(string $fid): Account
    {
        return $this->ensureAccount("281.{$fid}", "Товары компании {$fid}", 'asset', '281');
    }

    private function revenueAccount(string $fid): Account
    {
        return $this->ensureAccount("701.{$fid}", "Доход от реализации {$fid}", 'income', '701');
    }

    private function costOfSalesAccount(string $fid): Account
    {
        return $this->ensureAccount("902.{$fid}", "Себестоимость реализации {$fid}", 'expense', '902');
    }

    private function operatingExpenseAccount(string $fid): Account
    {
        return $this->ensureAccount("949.{$fid}", "Прочие операционные расходы {$fid}", 'expense', '949');
    }

    private function userBalanceAccount(string $fid, string $userId, string $currency): Account
    {
        $userId = trim($userId) !== '' && trim($userId) !== '0' ? trim($userId) : 'generic';
        $currency = $this->normalizeCurrencyCode($currency);
        $name = $userId === 'generic' ? 'Пользователи' : ($this->userName($userId) ?: "Пользователь {$userId}");

        return $this->ensureAccount(
            "333.{$fid}.{$userId}.{$currency}",
            "Баланс пользователя {$name} {$currency}",
            'asset',
            '31'
        );
    }

    private function exchangeBaseAmount(float $amountFrom, float $amountTo, string $currencyFrom, string $currencyTo): float
    {
        if ($currencyFrom === self::DEFAULT_CURRENCY) {
            return $amountFrom;
        }

        if ($currencyTo === self::DEFAULT_CURRENCY) {
            return $amountTo;
        }

        return $amountTo;
    }

    private function normalizeCurrencyCode(mixed $value): string
    {
        $currency = strtoupper(preg_replace('/[^A-Z0-9]/', '', (string) $value) ?? '');

        return $currency !== '' ? substr($currency, 0, 10) : self::DEFAULT_CURRENCY;
    }

    private function ensureAccount(string $code, string $name, string $type, ?string $parentCode = null): Account
    {
        $parentId = null;
        if ($parentCode !== null) {
            $parent = Account::query()->where('code', $parentCode)->first();
            $parentId = $parent?->id;
        }

        $account = Account::query()->firstOrCreate(
            ['code' => $code],
            ['name' => $name, 'type' => $type, 'parent_id' => $parentId]
        );

        $dirty = false;
        if ($account->name !== $name) {
            $account->name = $name;
            $dirty = true;
        }
        if ($account->type !== $type) {
            $account->type = $type;
            $dirty = true;
        }
        if ($account->parent_id !== $parentId) {
            $account->parent_id = $parentId;
            $dirty = true;
        }
        if ($dirty) {
            $account->save();
        }

        return $account;
    }

    private function confName(string $type, string $id): string
    {
        if (!Schema::hasTable('conf')) {
            return '';
        }

        return (string) (DB::table('conf')
            ->where('type', $type)
            ->where('id', $id)
            ->value('name') ?? '');
    }

    private function userName(string $id): string
    {
        if (!Schema::hasTable('users')) {
            return '';
        }

        $user = DB::table('users')
            ->where('id', $id)
            ->first(['orgname', 'secondname', 'name', 'name2']);

        if (!$user) {
            return '';
        }

        $parts = array_filter([
            (string) ($user->orgname ?? ''),
            trim(implode(' ', array_filter([
                (string) ($user->secondname ?? ''),
                (string) ($user->name ?? ''),
                (string) ($user->name2 ?? ''),
            ]))),
        ]);

        return trim(implode(' ', $parts));
    }

    private function makeDescription(string $docType, object $document, bool $reverse): string
    {
        $prefix = $reverse ? 'Сторно ' : '';
        $num = (string) ($document->num ?? $document->id ?? '');

        return trim("{$prefix}{$docType} №{$num}");
    }

    private function normalizeDate(string $date): string
    {
        if (preg_match('/^\d{2}-\d{2}-\d{4}$/', $date) === 1) {
            $parts = explode('-', $date);
            return "{$parts[2]}-{$parts[1]}-{$parts[0]}";
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) === 1) {
            return $date;
        }

        return now()->toDateString();
    }

    private function resolveActorUserId(): ?int
    {
        return Auth::id() ?: (session()->has('userid') ? (int) session('userid') : null);
    }

    private function isAvailable(): bool
    {
        return Schema::hasTable('accounts')
            && Schema::hasTable('transactions')
            && Schema::hasTable('entries');
    }
}
