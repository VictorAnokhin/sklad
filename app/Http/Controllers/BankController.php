<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\AccountingService;
use App\Services\BlockchainAssetAdapterService;
use App\Support\HoldingScope;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class BankController extends Controller
{
    private const DEPOSIT_TRANSFER_ACCOUNT_FID = '12';

    private const EXCHANGE_ORDER_STATUSES = [
        'new' => 'Новая',
        'awaiting_payment' => 'Ожидает оплату',
        'paid' => 'Оплачена',
        'processing' => 'В обработке',
        'completed' => 'Выполнена',
        'cancelled' => 'Отменена',
        'failed' => 'Ошибка',
    ];

    public function __construct(private readonly BlockchainAssetAdapterService $assetAdapterService)
    {
    }

    public function cashAccounts(): View
    {
        $project = $this->bankProject();
        $cashAccounts = collect();
        $clientAccounts = collect();
        $projectAccounts = collect();

        if (Schema::hasTable('conf')) {
            $cashAccounts = DB::table('conf')
                ->where('type', 'oplata')
                ->where('firma', (string) $project->id)
                ->orderBy('name')
                ->get()
                ->map(fn ($account) => $this->normalizeCashAccount($account));
        }

        if (Schema::hasTable('users') && Schema::hasTable('users_cashe')) {
            $clientAccounts = $this->clientAccounts((string) $project->id, $cashAccounts);
        }
        if (Schema::hasTable('project')) {
            $projectAccounts = $this->projectAccounts($project);
        }

        $totalByCurrency = $clientAccounts
            ->groupBy('currency')
            ->map(fn ($items) => (float) $items->sum('balance'));
        $ownerTypeTotals = $clientAccounts
            ->groupBy('owner_type')
            ->map(fn ($items) => [
                'count' => $items->count(),
                'balance' => (float) $items->sum('balance'),
            ]);

        return view('bank.cash_accounts', [
            'project' => $project,
            'cashAccounts' => $cashAccounts,
            'clientAccounts' => $clientAccounts,
            'projectAccounts' => $projectAccounts,
            'personOwners' => $this->personOwners((string) $project->id, $clientAccounts),
            'totalByCurrency' => $totalByCurrency,
            'ownerTypeTotals' => $ownerTypeTotals,
        ]);
    }

    public function operationalAccounts(): View
    {
        $project = $this->bankProject();
        $cashAccounts = $this->bankOperationalAccountsByAccountType((string) $project->id, 'bank');
        $operationalTotalByCurrency = $cashAccounts
            ->groupBy('currency')
            ->map(fn ($items) => (float) $items->sum('balance'));

        return view('bank.operational_accounts', [
            'project' => $project,
            'cashAccounts' => $cashAccounts,
            'operationalTotalByCurrency' => $operationalTotalByCurrency,
        ]);
    }

    public function storeProjectAccount(Request $request, int $project): RedirectResponse
    {
        $bankProject = $this->bankProject();
        $this->assertProjectInBankScope($project, $bankProject);
        abort_unless(Schema::hasTable('conf'), 404);

        $payload = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'currency' => ['required', 'string', 'max:20'],
        ]);
        $currency = $this->normalizeCurrencyCode($payload['currency']);

        DB::table('conf')->insert([
            'type' => 'oplata',
            'name' => trim($payload['name']),
            'firma' => $project,
            'currency' => $currency,
            'value' => 0,
            'status' => 1,
            'vision' => '1',
        ]);

        return redirect()->route('bank.cash-accounts')->with('success', 'Счёт проекта добавлен.');
    }

    public function storeOperationalAccount(Request $request): RedirectResponse
    {
        $project = $this->bankProject();
        abort_unless(Schema::hasTable('conf'), 404);

        $payload = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'account_type' => ['nullable', Rule::in(['bank', 'personal'])],
            'currency' => ['required', 'string', 'max:20'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'google_auth' => ['nullable', 'string', 'max:255'],
        ]);

        $columns = Schema::getColumnListing('conf');
        $values = [
            'type' => 'oplata',
            'name' => trim((string) $payload['name']),
            'firma' => (string) $project->id,
            'currency' => $this->normalizeCurrencyCode($payload['currency']),
            'value' => (float) ($payload['amount'] ?? 0),
            'status' => 1,
            'vision' => '1',
        ];

        if (in_array('doc', $columns, true)) {
            $values['doc'] = (string) ($payload['account_type'] ?? 'bank');
        }
        if (in_array('google_map', $columns, true)) {
            $values['google_map'] = trim((string) ($payload['google_auth'] ?? ''));
        }

        DB::table('conf')->insert($values);

        return redirect()
            ->route($this->operationalAccountReturnRoute($request))
            ->with('success', 'Операционный счёт создан.');
    }

    public function updateOperationalAccount(Request $request, int $account): RedirectResponse
    {
        $project = $this->bankProject();
        abort_unless(Schema::hasTable('conf'), 404);

        $payload = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'account_type' => ['required', Rule::in(['bank', 'personal'])],
            'currency' => ['required', 'string', 'max:20'],
            'amount' => ['required', 'numeric', 'min:0'],
            'google_auth' => ['nullable', 'string', 'max:255'],
        ]);

        $columns = Schema::getColumnListing('conf');
        $values = [
            'name' => trim((string) $payload['name']),
            'currency' => $this->normalizeCurrencyCode($payload['currency']),
            'value' => (float) $payload['amount'],
        ];

        if (in_array('doc', $columns, true)) {
            $values['doc'] = (string) $payload['account_type'];
        }
        if (in_array('google_map', $columns, true)) {
            $values['google_map'] = trim((string) ($payload['google_auth'] ?? ''));
        }

        $query = DB::table('conf')
            ->where('id', $account)
            ->where('type', 'oplata')
            ->where('firma', (string) $project->id);

        if (! $query->exists()) {
            return redirect()
                ->route($this->operationalAccountReturnRoute($request))
                ->with('error', 'Операционный счёт не найден.');
        }

        $query->update($values);

        return redirect()
            ->route($this->operationalAccountReturnRoute($request))
            ->with('success', 'Операционный счёт сохранён.');
    }

    public function destroyOperationalAccount(Request $request, int $account): RedirectResponse
    {
        $project = $this->bankProject();
        abort_unless(Schema::hasTable('conf'), 404);

        $deleted = DB::table('conf')
            ->where('id', $account)
            ->where('type', 'oplata')
            ->where('firma', (string) $project->id)
            ->delete();

        if ($deleted === 0) {
            return redirect()
                ->route($this->operationalAccountReturnRoute($request))
                ->with('error', 'Операционный счёт не найден.');
        }

        return redirect()
            ->route($this->operationalAccountReturnRoute($request))
            ->with('success', 'Операционный счёт удалён.');
    }

    public function destroyProjectAccount(int $project, int $account): RedirectResponse
    {
        $bankProject = $this->bankProject();
        $this->assertProjectInBankScope($project, $bankProject);
        abort_unless(Schema::hasTable('conf'), 404);

        $accountRow = DB::table('conf')
            ->where('id', $account)
            ->where('firma', $project)
            ->where('type', 'oplata')
            ->first();
        abort_unless($accountRow, 404);

        if (abs((float) ($accountRow->value ?? 0)) > 0.000001) {
            return redirect()->route('bank.cash-accounts')->with('error', 'Нельзя удалить счёт с ненулевым балансом.');
        }

        if ($this->projectAccountHasDocuments((string) $account)) {
            return redirect()->route('bank.cash-accounts')->with('error', 'Счёт используется в документах и не может быть удалён.');
        }

        DB::table('conf')->where('id', $account)->delete();

        return redirect()->route('bank.cash-accounts')->with('success', 'Счёт проекта удалён.');
    }

    public function storePersonAccount(Request $request, int $person): RedirectResponse
    {
        $bankProject = $this->bankProject();
        abort_unless(Schema::hasTable('users') && Schema::hasTable('users_cashe'), 404);
        $userQuery = DB::table('users')->where('id', $person);
        if (Schema::hasColumn('users', 'firma')) {
            $userQuery->whereIn(
                'firma',
                array_map('intval', HoldingScope::projectIdsFor((string) $bankProject->id))
            );
        }
        abort_unless($userQuery->exists(), 404);

        $payload = $request->validate([
            'currency' => ['required', 'string', 'max:20'],
        ]);
        $currency = $this->normalizeCurrencyCode($payload['currency']);
        $columns = Schema::getColumnListing('users_cashe');
        $scope = HoldingScope::projectIdsFor((string) $bankProject->id);

        $existing = DB::table('users_cashe')
            ->where(function ($query) use ($person, $columns): void {
                $query->where('userid', (string) $person);
                if (in_array('user_id', $columns, true)) {
                    $query->orWhere('user_id', $person);
                }
            })
            ->when(
                in_array('firma', $columns, true) && $scope !== [],
                fn ($query) => $query->whereIn('firma', array_map('intval', $scope))
            )
            ->when(
                in_array('valuta', $columns, true),
                fn ($query) => $query->where('valuta', $currency)
            )
            ->exists();

        if ($existing) {
            return redirect()->route('bank.cash-accounts')->with('error', "Счёт физлица в валюте {$currency} уже существует.");
        }

        $values = [
            'userid' => (string) $person,
            'balance' => 0,
        ];
        if (in_array('user_id', $columns, true)) {
            $values['user_id'] = $person;
        }
        if (in_array('firma', $columns, true)) {
            $values['firma'] = (int) $bankProject->id;
        }
        if (in_array('valuta', $columns, true)) {
            $values['valuta'] = $currency;
        }

        DB::table('users_cashe')->insert($values);

        return redirect()->route('bank.cash-accounts')->with('success', 'Счёт физлица добавлен.');
    }

    public function destroyPersonAccount(int $person, int $account): RedirectResponse
    {
        $bankProject = $this->bankProject();
        abort_unless(Schema::hasTable('users_cashe'), 404);

        $columns = Schema::getColumnListing('users_cashe');
        $scope = HoldingScope::projectIdsFor((string) $bankProject->id);
        $query = DB::table('users_cashe')
            ->where('id', $account)
            ->where(function ($nested) use ($person, $columns): void {
                $nested->where('userid', (string) $person);
                if (in_array('user_id', $columns, true)) {
                    $nested->orWhere('user_id', $person);
                }
            })
            ->when(
                in_array('firma', $columns, true) && $scope !== [],
                fn ($nested) => $nested->whereIn('firma', array_map('intval', $scope))
            );

        $accountRow = $query->first();
        abort_unless($accountRow, 404);

        if (abs((float) ($accountRow->balance ?? 0)) > 0.000001) {
            return redirect()->route('bank.cash-accounts')->with('error', 'Нельзя удалить счёт с ненулевым остатком.');
        }

        $query->delete();

        return redirect()->route('bank.cash-accounts')->with('success', 'Счёт физлица удалён.');
    }

    public function deposit(): View
    {
        $project = $this->bankProject();
        $projectIds = HoldingScope::projectIdsFor((string) $project->id);
        $deposits = $this->bankDeposits($projectIds);
        $operations = $this->bankDepositOperations($projectIds);
        $depositTransfers = $this->bankDepositTransfers($projectIds);
        $operationalAccounts = $this->bankOperationalAccounts(self::DEPOSIT_TRANSFER_ACCOUNT_FID);
        $depositPools = $this->bankDepositPoolRows($deposits, (int) $project->id);

        return view('bank.deposit', [
            'project' => $project,
            'deposits' => $deposits,
            'operations' => $operations,
            'depositTransfers' => $depositTransfers,
            'operationalAccounts' => $operationalAccounts,
            'depositPools' => $depositPools,
            'totalByCurrency' => $deposits
                ->groupBy('currency')
                ->map(fn ($rows) => (float) $rows->sum('balance')),
            'limitByCurrency' => $deposits
                ->groupBy('currency')
                ->map(fn ($rows) => (float) $rows->sum('limit')),
            'summary' => [
                'active' => $deposits->where('is_active', true)->count(),
                'topups' => (float) $operations->where('mode', 'topup')->sum('amount'),
                'withdrawals' => (float) $operations->where('mode', 'withdraw')->sum('amount'),
                'pending' => $operations->where('status', 'pending')->count(),
            ],
        ]);
    }

    public function pools(): View
    {
        $project = $this->bankProject();
        $pools = $this->investmentPools()
            ->map(function ($pool) {
                $pool->update_action = route('bank.pools.update', ['pool' => (int) $pool->id]);

                return $pool;
            });

        return view('bank.pools', [
            'project' => $project,
            'pools' => $pools,
            'summary' => [
                'pools' => $pools->count(),
                'active' => $pools->where('active', true)->count(),
                'onchain_balance' => (float) $pools->sum('balance_usdc'),
                'avg_apy_bps' => $pools->count() > 0 ? (int) round($pools->avg('apy_bps')) : 0,
            ],
        ]);
    }

    public function storePool(Request $request): RedirectResponse
    {
        abort_unless(Schema::hasTable('fund_pools'), 404);

        DB::table('fund_pools')->insert($this->bankPoolPayload($request) + [
            'created_by' => Auth::id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('bank.pools')->with('success', 'Пул создан.');
    }

    public function updatePool(Request $request, int $pool): RedirectResponse
    {
        abort_unless(Schema::hasTable('fund_pools'), 404);

        $updated = DB::table('fund_pools')
            ->where('id', $pool)
            ->update($this->bankPoolPayload($request, $pool) + [
                'updated_at' => now(),
            ]);

        if ($updated === 0) {
            return redirect()->route('bank.pools')->with('error', 'Пул не найден.');
        }

        return redirect()->route('bank.pools')->with('success', 'Пул сохранен.');
    }

    private function bankPoolPayload(Request $request, ?int $poolId = null): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'network' => ['nullable', 'string', 'max:40'],
            'package_id' => ['nullable', 'string', 'max:80'],
            'pool_object_id' => [
                'nullable',
                'string',
                'max:80',
                Rule::unique('fund_pools', 'pool_object_id')
                    ->where(fn ($query) => $query->where('network', trim((string) $request->input('network', 'testnet')) ?: 'testnet'))
                    ->ignore($poolId),
            ],
            'coin_type' => ['nullable', 'string', 'max:500'],
            'symbol' => ['required', 'string', 'max:32'],
            'description' => ['nullable', 'string', 'max:5000'],
            'risk_level' => ['nullable', 'integer', 'min:1', 'max:10'],
            'target_apy_bps' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'realized_apy_bps' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'min_deposit_usdc' => ['nullable', 'string', 'max:80'],
            'min_av8_balance' => ['nullable', 'string', 'max:80'],
            'max_weight_bps' => ['nullable', 'integer', 'min:0', 'max:10000'],
            'logo_url' => ['nullable', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $network = trim((string) ($validated['network'] ?? 'testnet')) ?: 'testnet';
        $symbol = strtoupper(preg_replace('/[^A-Z0-9]/', '', (string) $validated['symbol']) ?: 'USDC');
        $poolObjectId = strtolower(trim((string) ($validated['pool_object_id'] ?? '')));
        if ($poolObjectId === '') {
            $poolObjectId = $poolId
                ? (string) DB::table('fund_pools')->where('id', $poolId)->value('pool_object_id')
                : 'internal-' . bin2hex(random_bytes(8));
        }

        $payload = [
            'network' => $network,
            'package_id' => strtolower(trim((string) ($validated['package_id'] ?? ''))),
            'pool_object_id' => $poolObjectId,
            'coin_type' => trim((string) ($validated['coin_type'] ?? '')) ?: "internal::pool::{$symbol}",
            'symbol' => $symbol,
            'name' => trim((string) $validated['name']),
            'description' => trim((string) ($validated['description'] ?? '')) ?: null,
            'risk_level' => (int) ($validated['risk_level'] ?? 1),
            'target_apy_bps' => (int) ($validated['target_apy_bps'] ?? 0),
            'realized_apy_bps' => (int) ($validated['realized_apy_bps'] ?? 0),
            'min_deposit_usdc' => trim((string) ($validated['min_deposit_usdc'] ?? '0')) ?: '0',
            'min_av8_balance' => trim((string) ($validated['min_av8_balance'] ?? '0')) ?: '0',
            'max_weight_bps' => (int) ($validated['max_weight_bps'] ?? 10000),
            'active' => $request->boolean('active'),
            'logo_url' => trim((string) ($validated['logo_url'] ?? '')),
            'notes' => trim((string) ($validated['notes'] ?? '')) ?: null,
        ];

        if ($poolId === null) {
            $payload['pool_registry_id'] = '';
            $payload['pool_admin_cap_id'] = '';
        }

        foreach ([
            'pool_accounting_id',
            'basket_vault_id',
            'liquidity_wallet_address',
        ] as $column) {
            if (Schema::hasColumn('fund_pools', $column)) {
                $payload[$column] = '';
            }
        }
        if (Schema::hasColumn('fund_pools', 'is_default_deposit')) {
            $payload['is_default_deposit'] = $request->boolean('is_default_deposit');
        }

        return $payload;
    }

    public function storeDeposit(Request $request): RedirectResponse
    {
        $project = $this->bankProject();
        $payload = $this->validateDepositSettings($request);

        DB::table('conf')->insert([
            'type' => 'deposit',
            'name' => $payload['name'],
            'status' => 1,
            'firma' => (string) $project->id,
            'vision' => '1',
            'hide' => 0,
            'constanta' => '0',
            'value' => 0,
            'value1' => 0,
            'currency' => $payload['currency'],
            'doc' => $payload['deposit_type'],
        ]);

        return redirect()->route('bank.deposit')->with('success', 'Депозит создан.');
    }

    public function updateDeposit(Request $request, int $deposit): RedirectResponse
    {
        $project = $this->bankProject();
        $projectIds = HoldingScope::projectIdsFor((string) $project->id);
        $payload = $this->validateDepositSettings($request);

        $updated = DB::table('conf')
            ->where('id', $deposit)
            ->where('type', 'deposit')
            ->whereIn('firma', array_map('intval', $projectIds))
            ->update([
                'name' => $payload['name'],
                'doc' => $payload['deposit_type'],
                'currency' => $payload['currency'],
            ]);

        if ($updated === 0) {
            return redirect()->route('bank.deposit')->with('error', 'Депозит не найден.');
        }

        return redirect()->route('bank.deposit')->with('success', 'Настройки депозита сохранены.');
    }

    public function storeDepositTransfer(Request $request): RedirectResponse
    {
        $project = $this->bankProject();
        $projectIds = HoldingScope::projectIdsFor((string) $project->id);
        abort_unless(Schema::hasTable('conf') && Schema::hasTable('z_document'), 404);

        $payload = $this->validateDepositTransferPayload($request);

        $amount = round((float) $payload['amount'], 2);
        $depositId = (int) $payload['deposit_id'];
        $accountId = (int) $payload['operational_account_id'];
        $direction = (string) $payload['direction'];

        try {
            DB::transaction(function () use ($project, $projectIds, $depositId, $accountId, $amount, $direction, $payload): void {
                [$deposit, $account, $accountCurrency] = $this->applyDepositTransferBalances($project, $projectIds, $depositId, $accountId, $amount, $direction);
                $documentProjectId = (int) ($deposit->firma ?? $project->id);
                $postLedger = (bool) ($payload['post_ledger'] ?? false);
                $documentId = $this->createDepositTransferDocument(
                    (string) $documentProjectId,
                    $depositId,
                    $accountId,
                    $amount,
                    $accountCurrency,
                    trim((string) ($account->name ?? '')),
                    trim((string) ($deposit->name ?? '')),
                    $direction,
                    $postLedger
                );

                if ($postLedger) {
                    $this->postDepositTransferLedger($documentProjectId, $documentId);
                }
            });
        } catch (\RuntimeException $exception) {
            return redirect()->route('bank.deposit', ['tab' => 'transfer'])->with('error', $exception->getMessage());
        }

        return redirect()->route('bank.deposit', ['tab' => 'transfer'])->with('success', 'Трансфер выполнен.');
    }

    public function updateDepositTransfer(Request $request, int $transfer): RedirectResponse
    {
        $project = $this->bankProject();
        $projectIds = HoldingScope::projectIdsFor((string) $project->id);
        abort_unless(Schema::hasTable('conf') && Schema::hasTable('z_document'), 404);

        $payload = $this->validateDepositTransferPayload($request);

        try {
            DB::transaction(function () use ($project, $projectIds, $transfer, $payload): void {
                $document = $this->depositTransferDocument($transfer, $projectIds, true);
                if (! $document) {
                    throw new \RuntimeException('Трансфер не найден.');
                }

                $documentProjectId = (int) $document->firma;
                $oldDirection = (string) $document->docum === 'withdraw' ? 'deposit_to_account' : 'account_to_deposit';
                $oldAccountId = (int) $this->depositTransferAccountId($document, $oldDirection);
                $oldDepositId = (int) $document->money;
                $oldAmount = round((float) $document->summa, 2);

                $this->reverseDepositTransferBalances($project, $projectIds, $oldDepositId, $oldAccountId, $oldAmount, $oldDirection);

                $depositId = (int) $payload['deposit_id'];
                $accountId = (int) $payload['operational_account_id'];
                $amount = round((float) $payload['amount'], 2);
                $direction = (string) $payload['direction'];
                [$deposit, $account, $currency] = $this->applyDepositTransferBalances($project, $projectIds, $depositId, $accountId, $amount, $direction);

                $this->reverseDepositTransferLedger($documentProjectId, (int) $document->id);
                $postLedger = (bool) ($payload['post_ledger'] ?? false);
                $this->updateDepositTransferDocument(
                    (int) $document->id,
                    (string) $documentProjectId,
                    $depositId,
                    $accountId,
                    $amount,
                    $currency,
                    trim((string) ($account->name ?? '')),
                    trim((string) ($deposit->name ?? '')),
                    $direction,
                    $postLedger
                );
                if ($postLedger) {
                    $this->postDepositTransferLedger($documentProjectId, (int) $document->id);
                }
            });
        } catch (\RuntimeException $exception) {
            return redirect()->route('bank.deposit', ['tab' => 'transfer'])->with('error', $exception->getMessage());
        }

        return redirect()->route('bank.deposit', ['tab' => 'transfer'])->with('success', 'Трансфер обновлен.');
    }

    public function destroyDepositTransfer(int $transfer): RedirectResponse
    {
        $project = $this->bankProject();
        $projectIds = HoldingScope::projectIdsFor((string) $project->id);
        abort_unless(Schema::hasTable('conf') && Schema::hasTable('z_document'), 404);

        try {
            DB::transaction(function () use ($project, $projectIds, $transfer): void {
                $document = $this->depositTransferDocument($transfer, $projectIds, true);
                if (! $document) {
                    throw new \RuntimeException('Трансфер не найден.');
                }

                $direction = (string) $document->docum === 'withdraw' ? 'deposit_to_account' : 'account_to_deposit';
                $accountId = (int) $this->depositTransferAccountId($document, $direction);
                $depositId = (int) $document->money;
                $amount = round((float) $document->summa, 2);

                $this->reverseDepositTransferBalances($project, $projectIds, $depositId, $accountId, $amount, $direction);
                $this->reverseDepositTransferLedger((int) $document->firma, (int) $document->id);

                DB::table('z_document')->where('id', (int) $document->id)->update([
                    'status' => '-1',
                    'close' => 1,
                    'provodka' => 0,
                ]);
            });
        } catch (\RuntimeException $exception) {
            return redirect()->route('bank.deposit', ['tab' => 'transfer'])->with('error', $exception->getMessage());
        }

        return redirect()->route('bank.deposit', ['tab' => 'transfer'])->with('success', 'Трансфер удален.');
    }

    public function reverseDepositTransfer(int $transfer): RedirectResponse
    {
        $project = $this->bankProject();
        $projectIds = HoldingScope::projectIdsFor((string) $project->id);
        abort_unless(Schema::hasTable('z_document'), 404);

        try {
            DB::transaction(function () use ($project, $projectIds, $transfer): void {
                $document = $this->depositTransferDocument($transfer, $projectIds, true);
                if (! $document) {
                    throw new \RuntimeException('Трансфер не найден.');
                }
                if ((int) ($document->provodka ?? 0) !== 1) {
                    throw new \RuntimeException('У трансфера нет активной проводки для отмены.');
                }

                $this->reverseDepositTransferLedger((int) $document->firma, (int) $document->id);

                DB::table('z_document')->where('id', (int) $document->id)->update([
                    'provodka' => 0,
                ]);
            });
        } catch (\RuntimeException $exception) {
            return redirect()->route('bank.deposit', ['tab' => 'transfer'])->with('error', $exception->getMessage());
        }

        return redirect()->route('bank.deposit', ['tab' => 'transfer'])->with('success', 'Проводка трансфера отменена.');
    }

    public function exchange(): View
    {
        $project = $this->bankProject();
        $operationalAccounts = $this->bankOperationalAccounts((string) $project->id);

        return view('bank.exchange', [
            'project' => $project,
            'exchangeSettings' => $this->exchangeSettings(),
            'swapOrders' => $this->swapOrders((string) $project->id),
            'exchangeOrderStatuses' => self::EXCHANGE_ORDER_STATUSES,
            'blockchainExchangeEvents' => $this->blockchainExchangeEvents(),
            'operationalAccounts' => $operationalAccounts,
        ]);
    }

    public function invest(): View
    {
        $project = $this->bankProject();
        $projectIds = HoldingScope::projectIdsFor((string) $project->id);
        $operationalAccounts = $this->bankOperationalAccounts((string) $project->id);
        $deposits = $this->bankDeposits($projectIds);
        $pools = $this->investmentPools();
        $poolEvents = $this->investmentPoolEvents();
        $walletPortfolio = $this->googleAccountWalletPortfolio();
        $trackedAssets = $this->trackedAssetRows((int) $project->id);
        $tokenRows = $this->tokenManifestRows($walletPortfolio['tokens']);
        $hiddenTokenRows = $this->tokenManifestRows($walletPortfolio['tokens'], true)
            ->filter(fn ($token) => (bool) ($token->manifest_hidden ?? false))
            ->values();
        $assetManifestSettings = $this->assetManifestSettings((int) $project->id);
        $portfolioRows = $this->investmentPortfolioRows($deposits, $pools, $assetManifestSettings);
        $fixedAssetRows = $this->manualInvestmentAssetRows((int) $project->id);
        $investOperations = $this->bankInvestOperations((int) $project->id, $operationalAccounts, $fixedAssetRows);
        $investOperationRows = $this->investOperationRows($investOperations);
        $accountAssetAllocations = $this->accountAssetAllocations($operationalAccounts, $investOperations);
        $assetManifestRows = $this->assetManifestRows($portfolioRows);
        $assetManifestHiddenRows = $this->assetManifestRows($portfolioRows, true)
            ->filter(fn ($row) => (bool) ($row->manifest_hidden ?? false))
            ->values();
        $portfolioTotal = (float) $portfolioRows->sum('value_usd');
        $liquidTotal = (float) $portfolioRows->where('group', 'liquid')->sum('value_usd');
        $defiTotal = (float) $portfolioRows->where('group', 'defi')->sum('value_usd');
        $walletTokensTotal = (float) $walletPortfolio['tokens']->sum('value_usd');
        $visibleWalletTokensTotal = (float) $tokenRows->sum('value_usd');
        $walletDefiTotal = (float) $walletPortfolio['defiPositions']->sum('value_usd');
        $walletNftTotal = (float) $walletPortfolio['nfts']->sum('value_usd');

        return view('bank.invest', [
            'project' => $project,
            'operationalAccounts' => $operationalAccounts,
            'accountAssetAllocations' => $accountAssetAllocations,
            'investOperations' => $investOperations,
            'investOperationRows' => $investOperationRows,
            'fixedAssetRows' => $fixedAssetRows,
            'portfolioRows' => $portfolioRows,
            'assetManifestRows' => $assetManifestRows,
            'assetManifestHiddenRows' => $assetManifestHiddenRows,
            'tokenRows' => $tokenRows,
            'hiddenTokenRows' => $hiddenTokenRows,
            'pools' => $pools,
            'poolEvents' => $poolEvents,
            'walletPortfolio' => $walletPortfolio,
            'trackedAssets' => $trackedAssets,
            'summary' => [
                'nav' => $portfolioTotal,
                'liquid' => $liquidTotal,
                'defi' => $defiTotal,
                'wallet_tokens' => $walletTokensTotal,
                'wallet_tokens_visible' => $visibleWalletTokensTotal,
                'wallet_defi' => $walletDefiTotal,
                'wallet_nfts' => $walletNftTotal,
                'wallet_total' => $walletTokensTotal + $walletDefiTotal + $walletNftTotal,
                'health' => $portfolioTotal > 0 ? min(100, max(0, round($liquidTotal / $portfolioTotal * 100))) : 0,
                'pools' => $pools->count(),
                'active_pools' => $pools->where('active', true)->count(),
                'events' => $poolEvents->count(),
                'avg_apy_bps' => $pools->count() > 0 ? (int) round($pools->avg('apy_bps')) : 0,
            ],
        ]);
    }

    public function assets(): View
    {
        $project = $this->bankProject();
        $fixedAssetRows = $this->manualInvestmentAssetRows((int) $project->id);

        return view('bank.assets', [
            'project' => $project,
            'fixedAssetRows' => $fixedAssetRows,
            'summary' => [
                'assets' => $fixedAssetRows->count(),
                'tokens' => $fixedAssetRows->where('asset_type', 'token')->count(),
                'pools' => $fixedAssetRows->where('asset_type', 'pool')->count(),
                'value_usd' => (float) $fixedAssetRows->sum('value_usd'),
            ],
        ]);
    }

    public function poolMovements(): View
    {
        $project = $this->bankProject();
        $operationalAccounts = $this->bankOperationalAccounts((string) $project->id);
        $poolAssetRows = $this->investmentPoolAssetRows();
        $poolAssetKeys = $poolAssetRows->pluck('asset_key')->all();
        $investOperations = $poolAssetKeys === []
            ? collect()
            : $this->bankInvestOperations((int) $project->id, $operationalAccounts, $poolAssetRows, [
                'asset_type' => 'pool',
                'asset_keys' => $poolAssetKeys,
            ]);

        return view('bank.pool_movements', [
            'project' => $project,
            'operationalAccounts' => $operationalAccounts,
            'fixedAssetRows' => $poolAssetRows,
            'investOperations' => $investOperations,
            'investOperationRows' => $this->investOperationRows($investOperations),
            'summary' => [
                'operations' => $investOperations->count(),
                'posted' => $investOperations->where('status', 'posted')->count(),
                'pending' => $investOperations->where('status', 'pending')->count(),
                'value_usd' => (float) $investOperations->sum('value_usd'),
            ],
        ]);
    }

    public function storeInvestOperation(Request $request): RedirectResponse
    {
        $project = $this->bankProject();
        abort_unless(Schema::hasTable('bank_invest_operations'), 404);

        [$payload, $account, $asset, $amount, $priceUsd, $valueUsd, $currency, $operatedAt] = $this->investOperationPayload($request, (int) $project->id);
        $operationId = DB::transaction(function () use ($project, $payload, $asset, $account, $amount, $priceUsd, $valueUsd, $currency, $operatedAt): int {
            $now = now();
            $values = [
                'project_id' => (int) $project->id,
                'account_id' => (int) ($payload['account_id'] ?? 0),
                'direction' => (string) $payload['direction'],
                'asset_type' => (string) $asset->asset_type,
                'asset_key' => (string) $asset->asset_key,
                'asset_label' => (string) $asset->name,
                'currency' => $currency,
                'quantity' => (float) ($payload['quantity'] ?? 0),
                'amount' => $amount,
                'price_usd' => $priceUsd,
                'value_usd' => $valueUsd,
                'note' => trim((string) ($payload['note'] ?? '')),
                'operated_at' => $operatedAt,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if (Schema::hasColumn('bank_invest_operations', 'status')) {
                $values['status'] = 'pending';
            }

            $operationId = (int) DB::table('bank_invest_operations')->insertGetId($values);
            $ledger = null;
            if ((bool) ($payload['post_ledger'] ?? false)) {
                $ledger = $this->createInvestOperationLedger(
                    $operationId,
                    (int) $project->id,
                    $account,
                    $asset,
                    (string) $payload['direction'],
                    $valueUsd,
                    $currency,
                    (string) $operatedAt
                );
            }

            $updates = ['updated_at' => $now];
            if (Schema::hasColumn('bank_invest_operations', 'ledger_transaction_id')) {
                $updates['ledger_transaction_id'] = $ledger?->id;
            }
            if (Schema::hasColumn('bank_invest_operations', 'status')) {
                $updates['status'] = $ledger ? 'posted' : 'pending';
            }
            DB::table('bank_invest_operations')->where('id', $operationId)->update($updates);

            if ((bool) ($payload['post_ledger'] ?? false) && (bool) ($payload['update_account_balance'] ?? false)) {
                $this->applyInvestOperationAccountBalance($account, (string) $payload['direction'], $amount, $currency);
            }

            return $operationId;
        });

        $redirectRoute = $this->bankRedirectRoute((string) $request->input('redirect_to', 'bank.invest'));

        return redirect()
            ->route($redirectRoute, match ($redirectRoute) {
                'bank.invest' => ['tab' => 'operations'],
                'bank.deposit' => ['tab' => 'transfer'],
                default => [],
            })
            ->with('success', "Операция Счет ↔ Актив #{$operationId} выполнена.");
    }

    public function updateInvestOperation(Request $request, int $operation): RedirectResponse
    {
        $project = $this->bankProject();
        $redirectRoute = $this->bankRedirectRoute((string) $request->input('redirect_to', 'bank.invest'));
        abort_unless(Schema::hasTable('bank_invest_operations'), 404);

        $current = DB::table('bank_invest_operations')
            ->where('id', $operation)
            ->where('project_id', (int) $project->id)
            ->first();
        abort_unless($current, 404);

        if ($this->hasNewerInvestOperationForAsset((int) $project->id, (string) $current->asset_key, (string) $current->operated_at, (int) $current->id)) {
            return redirect()->route($redirectRoute, $this->bankRedirectRouteParams($redirectRoute))->with('error', 'Операция не редактируется: по этому активу уже есть более новый документ.');
        }

        $operationalAccounts = $this->bankOperationalAccounts((string) $project->id);
        $assetOptions = $this->investOperationAssetOptions((int) $project->id);
        $currentAccount = $operationalAccounts->firstWhere('id', (string) $current->account_id)
            ?? $operationalAccounts->firstWhere('id', (int) $current->account_id);
        $currentAsset = $assetOptions->firstWhere('asset_key', (string) $current->asset_key)
            ?? (object) [
                'asset_key' => (string) $current->asset_key,
                'asset_type' => (string) $current->asset_type,
                'name' => (string) $current->asset_label,
            ];
        $this->assertInvestOperationReversalDebitAvailable($currentAccount, $current);

        [$payload, $account, $asset, $amount, $priceUsd, $valueUsd, $currency, $operatedAt] = $this->investOperationPayload($request, (int) $project->id, $current);
        if ((string) $asset->asset_key !== (string) $current->asset_key
            && $this->hasNewerInvestOperationForAsset((int) $project->id, (string) $asset->asset_key, (string) $current->operated_at, (int) $current->id)) {
            return redirect()->route($redirectRoute, $this->bankRedirectRouteParams($redirectRoute))->with('error', 'Операция не редактируется: по выбранному активу уже есть более новый документ.');
        }

        DB::transaction(function () use ($operation, $project, $current, $currentAccount, $currentAsset, $payload, $account, $asset, $amount, $priceUsd, $valueUsd, $currency, $operatedAt): void {
            $now = now();
            if ((int) ($current->ledger_transaction_id ?? 0) > 0 || (string) ($current->status ?? 'pending') === 'posted') {
                $this->reverseInvestOperationLedgers((int) $project->id, $operation, (int) ($current->ledger_transaction_id ?? 0));
                if (in_array((string) $current->direction, ['account_to_asset', 'asset_to_account'], true)) {
                    $this->applyInvestOperationAccountBalance(
                        $currentAccount,
                        (string) $current->direction === 'account_to_asset' ? 'asset_to_account' : 'account_to_asset',
                        (float) $current->amount,
                        $this->normalizeCurrencyCode((string) $current->currency)
                    );
                }
            }

            DB::table('bank_invest_operations')->where('id', $operation)->update([
                'account_id' => (int) ($payload['account_id'] ?? 0),
                'direction' => (string) $payload['direction'],
                'asset_type' => (string) $asset->asset_type,
                'asset_key' => (string) $asset->asset_key,
                'asset_label' => (string) $asset->name,
                'currency' => $currency,
                'quantity' => (float) ($payload['quantity'] ?? 0),
                'amount' => $amount,
                'price_usd' => $priceUsd,
                'value_usd' => $valueUsd,
                'note' => trim((string) ($payload['note'] ?? '')),
                'operated_at' => $operatedAt,
                'updated_at' => $now,
            ]);

            $ledger = null;
            if ((bool) ($payload['post_ledger'] ?? false)) {
                $ledger = $this->createInvestOperationLedger(
                    $operation,
                    (int) $project->id,
                    $account,
                    $asset,
                    (string) $payload['direction'],
                    $valueUsd,
                    $currency,
                    (string) $operatedAt
                );
            }

            $updates = ['updated_at' => $now];
            if (Schema::hasColumn('bank_invest_operations', 'ledger_transaction_id')) {
                $updates['ledger_transaction_id'] = $ledger?->id;
            }
            if (Schema::hasColumn('bank_invest_operations', 'status')) {
                $updates['status'] = $ledger ? 'posted' : 'pending';
            }
            DB::table('bank_invest_operations')->where('id', $operation)->update($updates);

            if ((bool) ($payload['post_ledger'] ?? false) && (bool) ($payload['update_account_balance'] ?? false)) {
                $this->applyInvestOperationAccountBalance($account, (string) $payload['direction'], $amount, $currency);
            }
        });

        return redirect()->route($redirectRoute, $this->bankRedirectRouteParams($redirectRoute))->with('success', "Операция Счет ↔ Актив #{$operation} обновлена.");
    }

    public function destroyInvestOperation(Request $request, int $operation): RedirectResponse
    {
        $project = $this->bankProject();
        $redirectRoute = $this->bankRedirectRoute((string) $request->input('redirect_to', 'bank.invest'));
        abort_unless(Schema::hasTable('bank_invest_operations'), 404);

        $current = DB::table('bank_invest_operations')
            ->where('id', $operation)
            ->where('project_id', (int) $project->id)
            ->first();
        abort_unless($current, 404);

        if ($this->hasNewerInvestOperationForAsset((int) $project->id, (string) $current->asset_key, (string) $current->operated_at, (int) $current->id)) {
            return redirect()->route($redirectRoute, $this->bankRedirectRouteParams($redirectRoute))->with('error', 'Операция не удаляется: по этому активу уже есть более новый документ.');
        }

        $operationalAccounts = $this->bankOperationalAccounts((string) $project->id);
        $account = $operationalAccounts->firstWhere('id', (string) $current->account_id)
            ?? $operationalAccounts->firstWhere('id', (int) $current->account_id);
        $this->assertInvestOperationReversalDebitAvailable($account, $current);

        DB::transaction(function () use ($operation, $project, $current, $account): void {
            if ((int) ($current->ledger_transaction_id ?? 0) > 0 || (string) ($current->status ?? 'pending') === 'posted') {
                $this->reverseInvestOperationLedgers((int) $project->id, $operation, (int) ($current->ledger_transaction_id ?? 0));
                if (in_array((string) $current->direction, ['account_to_asset', 'asset_to_account'], true)) {
                    $this->applyInvestOperationAccountBalance(
                        $account,
                        (string) $current->direction === 'account_to_asset' ? 'asset_to_account' : 'account_to_asset',
                        (float) $current->amount,
                        $this->normalizeCurrencyCode((string) $current->currency)
                    );
                }
            }

            DB::table('bank_invest_operations')->where('id', $operation)->delete();
        });

        return redirect()->route($redirectRoute, $this->bankRedirectRouteParams($redirectRoute))->with('success', "Операция Счет ↔ Актив #{$operation} удалена.");
    }

    public function showReverseInvestOperation(int $operation): RedirectResponse
    {
        return redirect()
            ->route('bank.invest', ['tab' => 'operations'])
            ->with('error', "Отмена проводки операции #{$operation} выполняется из формы редактирования операции.");
    }

    public function reverseInvestOperation(Request $request, int $operation): RedirectResponse
    {
        $project = $this->bankProject();
        $redirectRoute = $this->bankRedirectRoute((string) $request->input('redirect_to', 'bank.invest'));
        abort_unless(Schema::hasTable('bank_invest_operations'), 404);

        $current = DB::table('bank_invest_operations')
            ->where('id', $operation)
            ->where('project_id', (int) $project->id)
            ->first();
        abort_unless($current, 404);

        if ($this->hasNewerInvestOperationForAsset((int) $project->id, (string) $current->asset_key, (string) $current->operated_at, (int) $current->id)) {
            return redirect()->route($redirectRoute, $this->bankRedirectRouteParams($redirectRoute))->with('error', 'Проводка не отменяется: по этому активу уже есть более новый документ.');
        }
        if ((int) ($current->ledger_transaction_id ?? 0) <= 0 && (string) ($current->status ?? 'pending') !== 'posted') {
            return redirect()->route($redirectRoute, $this->bankRedirectRouteParams($redirectRoute))->with('error', 'У операции нет активной проводки для отмены.');
        }

        $operationalAccounts = $this->bankOperationalAccounts((string) $project->id);
        $account = $operationalAccounts->firstWhere('id', (string) $current->account_id)
            ?? $operationalAccounts->firstWhere('id', (int) $current->account_id);
        $this->assertInvestOperationReversalDebitAvailable($account, $current);

        DB::transaction(function () use ($operation, $project, $current, $account): void {
            $this->reverseInvestOperationLedgers((int) $project->id, $operation, (int) ($current->ledger_transaction_id ?? 0));
            if (in_array((string) $current->direction, ['account_to_asset', 'asset_to_account'], true)) {
                $this->applyInvestOperationAccountBalance(
                    $account,
                    (string) $current->direction === 'account_to_asset' ? 'asset_to_account' : 'account_to_asset',
                    (float) $current->amount,
                    $this->normalizeCurrencyCode((string) $current->currency)
                );
            }

            $updates = ['updated_at' => now()];
            if (Schema::hasColumn('bank_invest_operations', 'ledger_transaction_id')) {
                $updates['ledger_transaction_id'] = null;
            }
            if (Schema::hasColumn('bank_invest_operations', 'status')) {
                $updates['status'] = 'pending';
            }
            DB::table('bank_invest_operations')->where('id', $operation)->update($updates);
        });

        return redirect()->route($redirectRoute, $this->bankRedirectRouteParams($redirectRoute))->with('success', "Проводка операции #{$operation} отменена.");
    }

    private function hasNewerInvestOperationForAsset(int $projectId, string $assetKey, string $operatedAt, int $operationId): bool
    {
        return DB::table('bank_invest_operations')
            ->where('project_id', $projectId)
            ->where('asset_key', $assetKey)
            ->where(function ($query) use ($operatedAt, $operationId): void {
                $query->where('operated_at', '>', $operatedAt)
                    ->orWhere(function ($sameDateQuery) use ($operatedAt, $operationId): void {
                        $sameDateQuery->where('operated_at', '=', $operatedAt)
                            ->where('id', '>', $operationId);
                    });
            })
            ->exists();
    }

    private function reverseInvestOperationLedgers(int $projectId, int $operationId, int $ledgerTransactionId = 0): array
    {
        if (! Schema::hasTable('transactions') || ! Schema::hasTable('entries')) {
            return [];
        }

        $reversalReferenceType = 'bank_invest_operation:reversal';
        $originalQuery = DB::table('transactions')
            ->where('company_id', $projectId)
            ->where('reference_type', 'bank_invest_operation')
            ->where('reference_id', (string) $operationId);
        if ($ledgerTransactionId > 0) {
            $originalQuery->where('id', $ledgerTransactionId);
        }

        $original = $originalQuery
            ->whereNotExists(function ($query) use ($reversalReferenceType): void {
                $query->selectRaw('1')
                    ->from('transactions as reversal')
                    ->whereColumn('reversal.company_id', 'transactions.company_id')
                    ->whereColumn('reversal.reference_id', 'transactions.reference_id')
                    ->where('reversal.reference_type', $reversalReferenceType)
                    ->whereColumn('reversal.id', '>', 'transactions.id');
            })
            ->latest('id')
            ->first();
        if (! $original && $ledgerTransactionId > 0) {
            $original = DB::table('transactions')
                ->where('id', $ledgerTransactionId)
                ->where('company_id', $projectId)
                ->first();
        }
        if (! $original) {
            return [];
        }

        $existingReversal = DB::table('transactions')
            ->where('company_id', $projectId)
            ->where('reference_type', $reversalReferenceType)
            ->where('reference_id', (string) $operationId)
            ->where('id', '>', (int) $original->id)
            ->first();
        if ($existingReversal) {
            return [];
        }

        $entries = DB::table('entries')
            ->where('transaction_id', (int) $original->id)
            ->get(['account_id', 'debit', 'credit'])
            ->map(fn ($entry): array => [
                'account_id' => (int) $entry->account_id,
                'debit' => (float) $entry->credit,
                'credit' => (float) $entry->debit,
            ])
            ->all();
        if ($entries === []) {
            return [];
        }

        $reversal = app(AccountingService::class)->createTransaction(
            $entries,
            'Сторно ' . trim((string) ($original->description ?? "Инвестиционная операция #{$operationId}")),
            [
                'date' => now()->toDateString(),
                'company_id' => $projectId,
                'reference_type' => $reversalReferenceType,
                'reference_id' => (string) $operationId,
                'currency' => (string) ($original->currency ?? 'UAH'),
                'amount' => (float) ($original->amount ?? 0),
                'amount_base' => (float) ($original->amount_base ?? 0),
            ]
        );

        return $reversal ? [$reversal] : [];
    }

    private function investOperationPayload(Request $request, int $projectId, ?object $currentOperation = null): array
    {
        $operationalAccounts = $this->bankOperationalAccounts((string) $projectId);
        $accountIds = $operationalAccounts->pluck('id')->map(fn ($id) => (string) $id)->all();
        $assetOptions = $this->investOperationAssetOptions($projectId);
        $assetKeys = $assetOptions->pluck('asset_key')->all();

        $payload = $request->validate([
            'account_id' => ['nullable', Rule::in($accountIds)],
            'direction' => ['required', Rule::in(['account_to_asset', 'asset_to_account', 'revaluation'])],
            'asset_key' => ['required', Rule::in($assetKeys)],
            'currency' => ['required', 'string', 'max:20'],
            'amount' => ['required', 'numeric'],
            'quantity' => ['nullable', 'numeric', 'min:0'],
            'price_usd' => ['nullable', 'numeric', 'min:0'],
            'operated_at' => ['nullable', 'date'],
            'note' => ['nullable', 'string', 'max:2000'],
            'update_account_balance' => ['nullable', 'boolean'],
            'post_ledger' => ['nullable', 'boolean'],
        ]);

        $asset = $assetOptions->firstWhere('asset_key', (string) $payload['asset_key']);
        abort_unless($asset, 422, 'Актив для операции не найден.');
        abort_unless(
            (string) $payload['direction'] === 'revaluation' || filled($payload['account_id'] ?? null),
            422,
            'Операционный счет обязателен для покупки или продажи актива.'
        );
        $accountId = $payload['account_id'] ?? null;
        $account = $accountId !== null
            ? ($operationalAccounts->firstWhere('id', (string) $accountId)
                ?? $operationalAccounts->firstWhere('id', (int) $accountId))
            : null;

        $amount = (float) $payload['amount'];
        if ((string) $payload['direction'] !== 'revaluation' && $amount <= 0) {
            abort(422, 'Сумма операции должна быть больше нуля.');
        }
        if ((string) $payload['direction'] === 'revaluation' && abs($amount) <= 0.00000001) {
            abort(422, 'Сумма переоценки не должна быть нулевой.');
        }
        $priceUsd = $request->filled('price_usd') ? (float) $payload['price_usd'] : null;
        $valueUsd = (string) $payload['direction'] === 'revaluation'
            ? $amount
            : ($priceUsd !== null && (float) ($payload['quantity'] ?? 0) > 0
            ? (float) $payload['quantity'] * $priceUsd
            : $amount);
        $currency = $this->normalizeCurrencyCode((string) $payload['currency']);
        if ((string) $payload['direction'] !== 'revaluation' && $account) {
            $currency = $this->normalizeCurrencyCode((string) ($account->currency ?? $currency));
        }
        if ((bool) ($payload['update_account_balance'] ?? false)) {
            abort_unless(
                in_array((string) $payload['direction'], ['account_to_asset', 'asset_to_account'], true),
                422,
                'Изменение остатка счета разрешено только для трансфера счет ↔ актив.'
            );
            $accountCurrency = $this->normalizeCurrencyCode((string) ($account->currency ?? ''));
            abort_unless($accountCurrency === $currency, 422, "Валюта счета {$accountCurrency} не совпадает с валютой операции {$currency}.");
            if ((string) $payload['direction'] === 'account_to_asset') {
                $this->assertInvestAccountDebitAvailable($account, $amount, $currency, $currentOperation);
            }
        }
        $operatedAt = $request->filled('operated_at')
            ? Carbon::parse((string) $payload['operated_at'])->toDateTimeString()
            : now()->toDateTimeString();

        return [$payload, $account, $asset, $amount, $priceUsd, $valueUsd, $currency, $operatedAt];
    }

    private function assertInvestAccountDebitAvailable(?object $account, float $amount, string $currency, ?object $currentOperation = null): void
    {
        if (! $account) {
            throw ValidationException::withMessages([
                'account_id' => 'Операционный счет для списания не найден.',
            ]);
        }

        $accountCurrency = $this->normalizeCurrencyCode((string) ($account->currency ?? ''));
        $currency = $this->normalizeCurrencyCode($currency);
        if ($accountCurrency !== $currency) {
            throw ValidationException::withMessages([
                'currency' => "Валюта счета {$accountCurrency} не совпадает с валютой операции {$currency}.",
            ]);
        }

        $available = $this->investAccountAvailableBalanceAfterReversal($account, $currentOperation);
        if ($available + 0.00000001 < $amount) {
            throw ValidationException::withMessages([
                'amount' => 'Недостаточно средств на операционном счете. Доступно: '
                    . number_format(max(0, $available), 2, '.', ' ')
                    . " {$accountCurrency}.",
            ]);
        }
    }

    private function assertInvestOperationReversalDebitAvailable(?object $account, ?object $operation): void
    {
        if (! $operation || ! $this->isPostedInvestOperation($operation) || (string) ($operation->direction ?? '') !== 'asset_to_account') {
            return;
        }

        $this->assertInvestAccountDebitAvailable(
            $account,
            (float) ($operation->amount ?? 0),
            $this->normalizeCurrencyCode((string) ($operation->currency ?? '')),
            null
        );
    }

    private function investAccountAvailableBalanceAfterReversal(?object $account, ?object $operation): float
    {
        $available = (float) ($account->balance ?? 0);
        if (! $account || ! $operation || ! $this->isPostedInvestOperation($operation)) {
            return $available;
        }

        if ((string) ($account->id ?? '') !== (string) ($operation->account_id ?? '')) {
            return $available;
        }

        $accountCurrency = $this->normalizeCurrencyCode((string) ($account->currency ?? ''));
        $operationCurrency = $this->normalizeCurrencyCode((string) ($operation->currency ?? ''));
        if ($accountCurrency !== $operationCurrency) {
            return $available;
        }

        return match ((string) ($operation->direction ?? '')) {
            'account_to_asset' => $available + (float) ($operation->amount ?? 0),
            'asset_to_account' => $available - (float) ($operation->amount ?? 0),
            default => $available,
        };
    }

    private function isPostedInvestOperation(object $operation): bool
    {
        return (int) ($operation->ledger_transaction_id ?? 0) > 0 || (string) ($operation->status ?? 'pending') === 'posted';
    }

    private function applyInvestOperationAccountBalance(?object $account, string $direction, float $amount, string $currency): void
    {
        if (! $account || ! Schema::hasTable('conf')) {
            return;
        }

        $accountCurrency = $this->normalizeCurrencyCode((string) ($account->currency ?? ''));
        if ($accountCurrency !== $currency) {
            return;
        }

        $operator = $direction === 'asset_to_account' ? '+' : '-';
        DB::table('conf')
            ->where('id', (int) $account->id)
            ->where('type', 'oplata')
            ->update(['value' => DB::raw('COALESCE(value, 0) ' . $operator . ' ' . abs($amount))]);
    }

    private function bankRedirectRoute(string $route): string
    {
        return in_array($route, ['bank.invest', 'bank.deposit', 'bank.assets', 'bank.pools', 'bank.pool-movements'], true) ? $route : 'bank.invest';
    }

    private function bankRedirectRouteParams(string $route): array
    {
        return match ($route) {
            'bank.invest' => ['tab' => 'operations'],
            'bank.deposit' => ['tab' => 'transfer'],
            default => [],
        };
    }

    public function storeInvestAsset(Request $request): RedirectResponse
    {
        $project = $this->bankProject();
        abort_unless(Schema::hasTable('bank_tracked_assets'), 404);

        [$key, $values] = $this->investAssetPayload($request, (int) $project->id);
        $existing = DB::table('bank_tracked_assets')->where($key)->exists();
        if ($existing) {
            DB::table('bank_tracked_assets')->where($key)->update($values);
        } else {
            DB::table('bank_tracked_assets')->insert($key + $values + ['created_at' => now()]);
        }

        return redirect()->route('bank.assets')->with('success', 'Инвестиционный актив добавлен.');
    }

    public function updateInvestAsset(Request $request, int $asset): RedirectResponse
    {
        $project = $this->bankProject();
        abort_unless(Schema::hasTable('bank_tracked_assets'), 404);

        $current = DB::table('bank_tracked_assets')
            ->where('id', $asset)
            ->where('project_id', (int) $project->id)
            ->whereIn('asset_type', ['token', 'pool'])
            ->when(
                Schema::hasColumn('bank_tracked_assets', 'adapter'),
                fn ($query) => $query->where(function ($subQuery) {
                    $subQuery->where('adapter', 'manual')
                        ->orWhere('blockchain', 'manual');
                }),
                fn ($query) => $query->where('blockchain', 'manual')
            )
            ->first();
        abort_unless($current, 404);

        [$key, $values] = $this->investAssetPayload($request, (int) $project->id);
        DB::table('bank_tracked_assets')->where('id', $asset)->update($key + $values);

        return redirect()->route('bank.assets')->with('success', 'Инвестиционный актив обновлен.');
    }

    private function investAssetPayload(Request $request, int $projectId): array
    {
        $payload = $request->validate([
            'asset_type' => ['required', Rule::in(['token', 'pool'])],
            'asset_address' => ['required', 'string', 'max:190'],
            'name' => ['required', 'string', 'max:160'],
            'quantity' => ['nullable', 'numeric', 'min:0'],
            'price_usd' => ['nullable', 'numeric', 'min:0'],
            'value_usd' => ['nullable', 'numeric', 'min:0'],
            'created_on' => ['nullable', 'date'],
        ]);

        $quantity = $request->filled('quantity') ? (float) $payload['quantity'] : 0.0;
        $priceUsd = $request->filled('price_usd') ? (float) $payload['price_usd'] : 0.0;
        $valueUsd = $request->filled('value_usd') ? (float) $payload['value_usd'] : $quantity * $priceUsd;
        $assetType = (string) $payload['asset_type'];
        $now = now();
        $key = [
            'project_id' => $projectId,
            'asset_type' => $assetType,
            'blockchain' => 'manual',
            'asset_address' => trim((string) $payload['asset_address']),
            'owner_address' => '',
            'token_id' => '',
        ];
        $values = [
            'name' => trim((string) $payload['name']),
            'symbol' => $assetType === 'pool' ? 'POOL' : 'TOKEN',
            'protocol' => 'bank/invest',
            'decimals' => null,
            'last_balance' => $quantity,
            'last_price_usd' => $priceUsd,
            'last_value_usd' => $valueUsd,
            'last_payload' => json_encode([
                'quantity' => $quantity,
                'price_usd' => $priceUsd,
                'value_usd' => $valueUsd,
            ]),
            'sync_status' => 'manual',
            'sync_error' => null,
            'hidden' => false,
            'last_synced_at' => $now,
            'updated_at' => $now,
        ];
        if (Schema::hasColumn('bank_tracked_assets', 'created_on')) {
            $values['created_on'] = $request->filled('created_on')
                ? Carbon::parse((string) $payload['created_on'])->toDateString()
                : $now->toDateString();
        }
        foreach ([
            'adapter' => 'manual',
            'available_fields' => json_encode([]),
            'selected_fields' => json_encode([]),
        ] as $column => $value) {
            if (Schema::hasColumn('bank_tracked_assets', $column)) {
                $values[$column] = $value;
            }
        }

        return [$key, $values];
    }

    private function createInvestOperationLedger(
        int $operationId,
        int $projectId,
        ?object $account,
        object $asset,
        string $direction,
        float $valueUsd,
        string $currency,
        string $operatedAt
    ) {
        if (abs($valueUsd) <= 0.00000001 || ! Schema::hasTable('accounts') || ! Schema::hasTable('transactions') || ! Schema::hasTable('entries')) {
            return null;
        }

        $investAccount = $this->ensureBankLedgerAccount(
            "141.{$projectId}." . substr(md5((string) $asset->asset_key), 0, 12),
            'Инвестиционный актив ' . trim((string) $asset->name),
            'asset',
            '141'
        );

        $amount = abs($valueUsd);
        if ($direction === 'revaluation') {
            $incomeAccount = $this->ensureBankLedgerAccount('746', 'Доход от переоценки инвестиционных активов', 'income');
            $expenseAccount = $this->ensureBankLedgerAccount('975', 'Расход от переоценки инвестиционных активов', 'expense');
            $debitAccountId = $valueUsd > 0 ? (int) $investAccount->id : (int) $expenseAccount->id;
            $creditAccountId = $valueUsd > 0 ? (int) $incomeAccount->id : (int) $investAccount->id;
            $description = $valueUsd > 0
                ? "Увеличение стоимости актива {$asset->name}"
                : "Уменьшение стоимости актива {$asset->name}";
        } else {
            $cashAccount = $this->ensureBankLedgerAccount(
                "311.{$projectId}." . (int) ($account->id ?? 0),
                'Операционный счет ' . trim((string) ($account->label ?? ('#' . (int) ($account->id ?? 0)))),
                'asset',
                '311'
            );
            $debitAccountId = $direction === 'asset_to_account' ? (int) $cashAccount->id : (int) $investAccount->id;
            $creditAccountId = $direction === 'asset_to_account' ? (int) $investAccount->id : (int) $cashAccount->id;
            $description = $direction === 'asset_to_account'
                ? "Возврат из актива {$asset->name} на операционный счет"
                : "Распределение средств операционного счета в актив {$asset->name}";
        }

        return app(AccountingService::class)->createTransaction(
            [
                ['account_id' => $debitAccountId, 'debit' => $amount, 'credit' => 0, 'currency' => $currency],
                ['account_id' => $creditAccountId, 'debit' => 0, 'credit' => $amount, 'currency' => $currency],
            ],
            $description,
            [
                'date' => $this->ledgerDate($operatedAt),
                'company_id' => $projectId,
                'reference_type' => 'bank_invest_operation',
                'reference_id' => $operationId,
                'currency' => $currency,
                'amount' => $amount,
                'amount_base' => $amount,
            ]
        );
    }

    private function ensureBankLedgerAccount(string $code, string $name, string $type, ?string $parentCode = null): object
    {
        $parentId = null;
        if ($parentCode !== null) {
            $parent = match ($parentCode) {
                '14' => $this->ensureBankLedgerAccount('14', 'Долгосрочные финансовые инвестиции', 'asset'),
                '141' => $this->ensureBankLedgerAccount('141', 'Инвестиционные активы', 'asset', '14'),
                default => DB::table('accounts')->where('code', $parentCode)->first(),
            };
            $parentId = $parent?->id;
        }

        DB::table('accounts')->updateOrInsert(
            ['code' => $code],
            [
                'name' => $name,
                'type' => $type,
                'parent_id' => $parentId,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        return DB::table('accounts')->where('code', $code)->first();
    }

    private function ledgerDate(string $value): string
    {
        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            return now()->toDateString();
        }
    }

    public function updateAssetManifestItem(Request $request, string $source, int $asset): RedirectResponse
    {
        $project = $this->bankProject();
        abort_unless(Schema::hasTable('bank_asset_manifest_items'), 404);
        abort_unless($this->assetManifestTargetExists($source, $asset, (int) $project->id), 404);

        $payload = $request->validate([
            'position' => ['nullable', 'integer', 'min:0', 'max:999999'],
            'hidden' => ['nullable', 'boolean'],
        ]);
        $now = now();
        $key = [
            'project_id' => (int) $project->id,
            'asset_type' => $source,
            'asset_id' => $asset,
        ];
        $values = [
            'position' => (int) ($payload['position'] ?? 0),
            'hidden' => $request->boolean('hidden'),
            'updated_at' => $now,
        ];

        $existing = DB::table('bank_asset_manifest_items')->where($key)->exists();
        if ($existing) {
            DB::table('bank_asset_manifest_items')->where($key)->update($values);
        } else {
            DB::table('bank_asset_manifest_items')->insert($key + $values + ['created_at' => $now]);
        }

        return redirect()
            ->route('bank.invest')
            ->with('success', 'Позиция Asset manifest обновлена.');
    }

    public function updateTokenManifestItem(Request $request, int $token): RedirectResponse
    {
        $project = $this->bankProject();
        abort_unless(Schema::hasTable('bank_token_manifest_items'), 404);
        abort_unless($this->tokenManifestTargetExists($token), 404);

        $request->validate([
            'hidden' => ['nullable', 'boolean'],
        ]);

        $now = now();
        $key = [
            'project_id' => (int) $project->id,
            'wallet_token_id' => $token,
        ];
        $values = [
            'hidden' => $request->boolean('hidden'),
            'updated_at' => $now,
        ];

        $existing = DB::table('bank_token_manifest_items')->where($key)->exists();
        if ($existing) {
            DB::table('bank_token_manifest_items')->where($key)->update($values);
        } else {
            DB::table('bank_token_manifest_items')->insert($key + $values + ['created_at' => $now]);
        }

        return redirect()
            ->route('bank.invest')
            ->with('success', 'Позиция Tokens обновлена.');
    }

    public function storeTrackedAsset(Request $request): RedirectResponse
    {
        $project = $this->bankProject();
        abort_unless(Schema::hasTable('bank_tracked_assets'), 404);

        $payload = $request->validate([
            'asset_type' => ['required', 'string', Rule::in(['token', 'nft', 'defi'])],
            'name' => ['nullable', 'string', 'max:160'],
            'symbol' => ['nullable', 'string', 'max:40'],
            'blockchain' => ['required', 'string', 'max:60'],
            'asset_address' => ['required', 'string', 'max:190'],
            'owner_address' => ['nullable', 'string', 'max:190'],
            'protocol' => ['nullable', 'string', 'max:120'],
            'token_id' => ['nullable', 'string', 'max:120'],
            'decimals' => ['nullable', 'integer', 'min:0', 'max:255'],
        ]);

        $now = now();
        $adapter = $this->assetAdapterService->adapterFor((string) $payload['blockchain'], (string) $payload['asset_type']);
        $key = [
            'project_id' => (int) $project->id,
            'asset_type' => $payload['asset_type'],
            'blockchain' => strtolower(trim((string) $payload['blockchain'])),
            'asset_address' => trim((string) $payload['asset_address']),
            'owner_address' => trim((string) ($payload['owner_address'] ?? '')),
            'token_id' => trim((string) ($payload['token_id'] ?? '')),
        ];
        $values = [
            'adapter' => $adapter,
            'name' => trim((string) ($payload['name'] ?? '')),
            'symbol' => strtoupper(trim((string) ($payload['symbol'] ?? ''))),
            'protocol' => trim((string) ($payload['protocol'] ?? '')),
            'decimals' => array_key_exists('decimals', $payload) ? $payload['decimals'] : null,
            'available_fields' => json_encode($this->assetAdapterService->availableFields($adapter)),
            'selected_fields' => json_encode($this->assetAdapterService->defaultSelectedFields($adapter)),
            'sync_status' => 'manual',
            'sync_error' => null,
            'hidden' => false,
            'updated_at' => $now,
        ];

        $existing = DB::table('bank_tracked_assets')->where($key)->exists();
        if ($existing) {
            DB::table('bank_tracked_assets')->where($key)->update($values);
        } else {
            DB::table('bank_tracked_assets')->insert($key + $values + ['created_at' => $now]);
        }

        return redirect()->route('bank.invest')->with('success', 'Актив добавлен для отслеживания.');
    }

    public function refreshTrackedAssets(Request $request): RedirectResponse
    {
        $project = $this->bankProject();
        abort_unless(Schema::hasTable('bank_tracked_assets'), 404);

        $payload = $request->validate([
            'asset_type' => ['nullable', 'string', Rule::in(['tokens', 'nft', 'defi', 'token'])],
        ]);
        $assetType = $this->normalizeTrackedAssetType((string) ($payload['asset_type'] ?? ''));
        $query = DB::table('bank_tracked_assets')->where('project_id', (int) $project->id);
        if ($assetType !== '') {
            $query->where('asset_type', $assetType);
        }

        $rows = $query->get();
        $updated = 0;
        foreach ($rows as $row) {
            $selectedFields = json_decode((string) ($row->selected_fields ?? '[]'), true);
            $selectedFields = is_array($selectedFields) ? $selectedFields : null;
            $result = $this->assetAdapterService->refresh([
                'adapter' => (string) ($row->adapter ?? ''),
                'asset_type' => (string) ($row->asset_type ?? ''),
                'blockchain' => (string) ($row->blockchain ?? ''),
                'asset_address' => (string) ($row->asset_address ?? ''),
                'owner_address' => (string) ($row->owner_address ?? ''),
                'name' => (string) ($row->name ?? ''),
                'symbol' => (string) ($row->symbol ?? ''),
                'selected_fields' => $selectedFields,
            ]);

            DB::table('bank_tracked_assets')->where('id', (int) $row->id)->update([
                'adapter' => (string) ($result['adapter'] ?? $row->adapter ?? 'manual'),
                'name' => array_key_exists('name', $result) ? (string) $result['name'] : (string) ($row->name ?? ''),
                'symbol' => array_key_exists('symbol', $result) ? (string) $result['symbol'] : (string) ($row->symbol ?? ''),
                'owner_address' => array_key_exists('owner_address', $result) ? (string) $result['owner_address'] : (string) ($row->owner_address ?? ''),
                'available_fields' => json_encode($result['available_fields'] ?? []),
                'selected_fields' => json_encode($result['selected_fields'] ?? []),
                'image_url' => array_key_exists('image_url', $result) ? (string) $result['image_url'] : (string) ($row->image_url ?? ''),
                'external_url' => array_key_exists('external_url', $result) ? (string) $result['external_url'] : (string) ($row->external_url ?? ''),
                'last_payload' => array_key_exists('last_payload', $result) ? json_encode($result['last_payload']) : $row->last_payload,
                'sync_status' => (string) ($result['sync_status'] ?? 'refresh_requested'),
                'sync_error' => $result['sync_error'] ?? null,
                'last_synced_at' => now(),
                'updated_at' => now(),
            ]);
            $updated++;
        }

        return redirect()
            ->route('bank.invest')
            ->with('success', $updated > 0 ? 'Обновление активов запрошено.' : 'Нет активов для обновления.');
    }

    public function updateTrackedAssetAdapter(Request $request, int $asset): RedirectResponse
    {
        $project = $this->bankProject();
        abort_unless(Schema::hasTable('bank_tracked_assets'), 404);

        $row = DB::table('bank_tracked_assets')
            ->where('project_id', (int) $project->id)
            ->where('id', $asset)
            ->first();
        abort_unless($row, 404);

        $payload = $request->validate([
            'selected_fields' => ['nullable', 'array'],
            'selected_fields.*' => ['string', 'max:80'],
        ]);

        $availableFields = json_decode((string) ($row->available_fields ?? '[]'), true);
        $availableKeys = collect(is_array($availableFields) ? $availableFields : [])
            ->pluck('key')
            ->filter()
            ->map(fn ($key) => (string) $key)
            ->all();
        $selectedFields = collect($payload['selected_fields'] ?? [])
            ->map(fn ($key) => (string) $key)
            ->filter(fn ($key) => in_array($key, $availableKeys, true))
            ->values()
            ->all();

        DB::table('bank_tracked_assets')->where('id', (int) $row->id)->update([
            'selected_fields' => json_encode($selectedFields),
            'updated_at' => now(),
        ]);

        return redirect()->route('bank.invest')->with('success', 'Настройки адаптера сохранены.');
    }

    public function bulkUpdateTrackedAssets(Request $request): RedirectResponse
    {
        $project = $this->bankProject();
        abort_unless(Schema::hasTable('bank_tracked_assets'), 404);

        $payload = $request->validate([
            'action' => ['required', 'string', Rule::in(['delete', 'hide', 'show'])],
            'tracked_assets' => ['required', 'array', 'min:1'],
            'tracked_assets.*' => ['integer', 'min:1'],
        ]);

        $ids = collect($payload['tracked_assets'])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return redirect()->route('bank.invest')->with('error', 'Выберите активы для действия.');
        }

        $query = DB::table('bank_tracked_assets')
            ->where('project_id', (int) $project->id)
            ->whereIn('id', $ids->all());

        if ($payload['action'] === 'delete') {
            $query->delete();

            return redirect()->route('bank.invest')->with('success', 'Выбранные отслеживаемые активы удалены.');
        }

        $hidden = $payload['action'] === 'hide';
        $query->update([
            'hidden' => $hidden,
            'updated_at' => now(),
        ]);

        return redirect()
            ->route('bank.invest')
            ->with('success', $hidden ? 'Выбранные отслеживаемые активы скрыты.' : 'Выбранные отслеживаемые активы показаны.');
    }

    public function bulkUpdateTokenManifestItems(Request $request): RedirectResponse
    {
        $project = $this->bankProject();
        abort_unless(Schema::hasTable('wallet_tokens'), 404);

        $payload = $request->validate([
            'action' => ['required', 'string', Rule::in(['delete', 'hide', 'show'])],
            'tokens' => ['required', 'array', 'min:1'],
            'tokens.*' => ['integer', 'min:1'],
        ]);

        $tokenIds = collect($payload['tokens'])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->filter(fn ($id) => $this->tokenManifestTargetExists($id))
            ->values();

        if ($tokenIds->isEmpty()) {
            return redirect()->route('bank.invest')->with('error', 'Выберите токены для действия.');
        }

        if ($payload['action'] === 'delete') {
            if (Schema::hasTable('bank_token_manifest_items')) {
                DB::table('bank_token_manifest_items')
                    ->where('project_id', (int) $project->id)
                    ->whereIn('wallet_token_id', $tokenIds->all())
                    ->delete();
            }
            DB::table('wallet_tokens')->whereIn('id', $tokenIds->all())->delete();

            return redirect()->route('bank.invest')->with('success', 'Выбранные токены удалены из кеша.');
        }

        abort_unless(Schema::hasTable('bank_token_manifest_items'), 404);
        $now = now();
        $hidden = $payload['action'] === 'hide';
        foreach ($tokenIds as $tokenId) {
            $key = [
                'project_id' => (int) $project->id,
                'wallet_token_id' => $tokenId,
            ];
            $values = [
                'hidden' => $hidden,
                'updated_at' => $now,
            ];
            $existing = DB::table('bank_token_manifest_items')->where($key)->exists();
            if ($existing) {
                DB::table('bank_token_manifest_items')->where($key)->update($values);
            } else {
                DB::table('bank_token_manifest_items')->insert($key + $values + ['created_at' => $now]);
            }
        }

        return redirect()
            ->route('bank.invest')
            ->with('success', $hidden ? 'Выбранные токены скрыты.' : 'Выбранные токены показаны.');
    }

    public function bulkUpdateAssetManifestItems(Request $request): RedirectResponse
    {
        $project = $this->bankProject();
        abort_unless(Schema::hasTable('bank_asset_manifest_items'), 404);

        $payload = $request->validate([
            'action' => ['required', 'string', Rule::in(['delete', 'hide', 'show'])],
            'assets' => ['required', 'array', 'min:1'],
            'assets.*' => ['string', 'max:80'],
        ]);

        $targets = collect($payload['assets'])
            ->map(function ($value) {
                [$source, $asset] = array_pad(explode(':', (string) $value, 2), 2, null);

                return [
                    'source' => (string) $source,
                    'asset' => (int) $asset,
                ];
            })
            ->filter(fn ($target) => in_array($target['source'], ['deposit', 'pool'], true)
                && $target['asset'] > 0
                && $this->assetManifestTargetExists($target['source'], $target['asset'], (int) $project->id))
            ->unique(fn ($target) => $target['source'] . ':' . $target['asset'])
            ->values();

        if ($targets->isEmpty()) {
            return redirect()->route('bank.invest')->with('error', 'Выберите позиции для действия.');
        }

        $now = now();
        $hidden = in_array($payload['action'], ['delete', 'hide'], true);
        foreach ($targets as $target) {
            $key = [
                'project_id' => (int) $project->id,
                'asset_type' => $target['source'],
                'asset_id' => $target['asset'],
            ];
            $existing = DB::table('bank_asset_manifest_items')->where($key)->first();
            $values = [
                'position' => (int) ($existing->position ?? 0),
                'hidden' => $hidden,
                'updated_at' => $now,
            ];
            if ($existing) {
                DB::table('bank_asset_manifest_items')->where($key)->update($values);
            } else {
                DB::table('bank_asset_manifest_items')->insert($key + $values + ['created_at' => $now]);
            }
        }

        $message = match ($payload['action']) {
            'show' => 'Выбранные позиции показаны.',
            'delete' => 'Выбранные позиции удалены из таблицы.',
            default => 'Выбранные позиции скрыты.',
        };

        return redirect()->route('bank.invest')->with('success', $message);
    }

    public function updateExchangeOrderStatus(Request $request, int $order): RedirectResponse
    {
        $project = $this->bankProject();
        abort_unless(Schema::hasTable('av8_swap_orders'), 404);

        $payload = $request->validate([
            'status' => ['required', 'string', Rule::in(array_keys(self::EXCHANGE_ORDER_STATUSES))],
        ]);

        $orderQuery = DB::table('av8_swap_orders')
            ->where('id', $order)
            ->where(function ($query) use ($project): void {
                $query->where('fid', (int) $project->id)
                    ->orWhere('fid', 0);
            });

        abort_unless($orderQuery->exists(), 404);

        $orderQuery->update([
            'status' => $payload['status'],
            'updated_at' => now(),
        ]);

        return redirect()
            ->route('bank.exchange')
            ->with('success', 'Статус заявки обновлен.')
            ->with('bank_exchange_tab', 'av8');
    }

    public function storeFiatCryptoExchange(Request $request): RedirectResponse
    {
        return $this->persistFiatCryptoExchange($request);
    }

    public function updateFiatCryptoExchange(Request $request, int $order): RedirectResponse
    {
        return $this->persistFiatCryptoExchange($request, $order);
    }

    private function persistFiatCryptoExchange(Request $request, ?int $orderId = null): RedirectResponse
    {
        $project = $this->bankProject();
        abort_unless(Schema::hasTable('av8_swap_orders'), 404);

        $currentMeta = [];
        if ($orderId !== null) {
            $current = DB::table('av8_swap_orders')
                ->where('id', $orderId)
                ->where('fid', (int) $project->id)
                ->where('source', 'bank.exchange.crypto')
                ->first();
            abort_unless($current, 404);
            $currentMeta = json_decode((string) ($current->meta ?? '{}'), true);
            $currentMeta = is_array($currentMeta) ? $currentMeta : [];
            $isReversed = ! empty($currentMeta['reversed_at']) || (string) ($current->status ?? '') === 'cancelled';
            if (! $isReversed && ! empty($currentMeta['ledger_transaction_id'])) {
                return redirect()
                    ->route('bank.exchange')
                    ->with('error', 'Проведенная операция не редактируется. Сначала отмените проводку.')
                    ->with('bank_exchange_tab', 'crypto');
            }
        }

        $payload = $request->validate([
            'side' => ['required', Rule::in(['buy', 'sell'])],
            'fiat_currency' => ['required', 'string', 'max:20'],
            'crypto_currency' => ['required', 'string', 'max:20'],
            'fiat_account_id' => ['required', 'integer'],
            'crypto_account_id' => ['required', 'integer'],
            'fiat_amount' => ['nullable', 'numeric', 'min:0'],
            'crypto_amount' => ['nullable', 'numeric', 'min:0'],
            'rate' => ['required', 'numeric', 'min:0.00000001'],
            'operated_at' => ['nullable', 'date'],
            'note' => ['nullable', 'string', 'max:2000'],
            'post_ledger' => ['nullable', 'boolean'],
        ]);

        $side = (string) $payload['side'];
        $rate = (float) $payload['rate'];
        $postLedger = $request->boolean('post_ledger');
        $fiatAmount = $request->filled('fiat_amount') ? (float) $payload['fiat_amount'] : 0.0;
        $cryptoAmount = $request->filled('crypto_amount') ? (float) $payload['crypto_amount'] : 0.0;

        if ($side === 'buy') {
            abort_unless($fiatAmount > 0, 422, 'Введите сумму фиата.');
            $cryptoAmount = $fiatAmount / $rate;
        } else {
            abort_unless($cryptoAmount > 0, 422, 'Введите сумму крипты.');
            $fiatAmount = $cryptoAmount * $rate;
        }

        $fiatCurrency = $this->normalizeCurrencyCode((string) $payload['fiat_currency']);
        $cryptoCurrency = strtoupper(trim((string) $payload['crypto_currency'])) ?: 'USDC';
        $now = now();
        $operatedAt = $request->filled('operated_at')
            ? Carbon::parse((string) $payload['operated_at'])->toDateString()
            : $now->toDateString();
        $operationalAccounts = $this->bankOperationalAccounts((string) $project->id);
        $operationalAccountsById = $operationalAccounts->keyBy(fn ($account) => (int) $account->id);
        $fiatAccount = $operationalAccountsById->get((int) $payload['fiat_account_id']);
        $cryptoAccount = $operationalAccountsById->get((int) $payload['crypto_account_id']);
        abort_unless($fiatAccount, 422, 'Фиатный операционный счет не найден.');
        abort_unless($cryptoAccount, 422, 'Крипто операционный счет не найден.');
        abort_unless($this->normalizeCurrencyCode((string) $fiatAccount->currency) === $fiatCurrency, 422, "Валюта фиатного счета должна быть {$fiatCurrency}.");
        abort_unless($this->normalizeCurrencyCode((string) $cryptoAccount->currency) === $cryptoCurrency, 422, "Валюта крипто-счета должна быть {$cryptoCurrency}.");

        if ($postLedger && $side === 'buy') {
            abort_unless((float) $fiatAccount->balance + 0.000001 >= $fiatAmount, 422, 'Недостаточно средств на фиатном операционном счете.');
        } elseif ($postLedger) {
            abort_unless((float) $cryptoAccount->balance + 0.00000001 >= $cryptoAmount, 422, 'Недостаточно средств на крипто операционном счете.');
        }

        $savedOrderId = DB::transaction(function () use ($project, $payload, $side, $fiatCurrency, $cryptoCurrency, $fiatAmount, $cryptoAmount, $rate, $fiatAccount, $cryptoAccount, $now, $operatedAt, $postLedger, $orderId, $currentMeta): int {
            if ($postLedger && $side === 'buy') {
                DB::table('conf')->where('id', (int) $fiatAccount->id)->where('type', 'oplata')->update([
                    'value' => DB::raw('COALESCE(value, 0) - ' . abs($fiatAmount)),
                ]);
                DB::table('conf')->where('id', (int) $cryptoAccount->id)->where('type', 'oplata')->update([
                    'value' => DB::raw('COALESCE(value, 0) + ' . abs($cryptoAmount)),
                ]);
            } elseif ($postLedger) {
                DB::table('conf')->where('id', (int) $cryptoAccount->id)->where('type', 'oplata')->update([
                    'value' => DB::raw('COALESCE(value, 0) - ' . abs($cryptoAmount)),
                ]);
                DB::table('conf')->where('id', (int) $fiatAccount->id)->where('type', 'oplata')->update([
                    'value' => DB::raw('COALESCE(value, 0) + ' . abs($fiatAmount)),
                ]);
            }

            $meta = array_merge($currentMeta, [
                'side' => $side,
                'fiat_currency' => $fiatCurrency,
                'fiat_amount' => $fiatAmount,
                'fiat_account_id' => (int) $fiatAccount->id,
                'fiat_account_label' => (string) $fiatAccount->label,
                'crypto_currency' => $cryptoCurrency,
                'crypto_amount' => $cryptoAmount,
                'crypto_account_id' => (int) $cryptoAccount->id,
                'crypto_account_label' => (string) $cryptoAccount->label,
                'rate_fiat_per_crypto' => $rate,
                'operated_at' => $operatedAt,
                'note' => trim((string) ($payload['note'] ?? '')),
            ]);
            unset($meta['ledger_transaction_id'], $meta['reversed_at'], $meta['reversal_ledger_transaction_id']);

            $values = [
                'fid' => (int) $project->id,
                'mode' => $side,
                'pay_currency' => $fiatCurrency,
                'pay_amount' => $fiatAmount,
                'rate_usdc' => $rate,
                'fee_percent' => 0,
                'fee_amount' => 0,
                'expected_av8' => $cryptoAmount,
                'payment_method' => "Fiat/{$cryptoCurrency}",
                'wallet_address' => '',
                'client_email' => null,
                'client_phone' => null,
                'status' => 'new',
                'source' => 'bank.exchange.crypto',
                'meta' => json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'updated_at' => $now,
            ];

            if ($orderId === null) {
                $values['created_at'] = Carbon::parse($operatedAt)->startOfDay();
                $savedOrderId = (int) DB::table('av8_swap_orders')->insertGetId($values);
            } else {
                DB::table('av8_swap_orders')->where('id', $orderId)->update($values);
                $savedOrderId = $orderId;
            }

            if ($postLedger) {
                $ledger = $this->createFiatCryptoExchangeLedger(
                    $savedOrderId,
                    (int) $project->id,
                    $side,
                    $fiatAccount,
                    $cryptoAccount,
                    $fiatAmount,
                    $cryptoAmount,
                    $fiatCurrency,
                    $cryptoCurrency,
                    $operatedAt
                );

                if ($ledger) {
                    $row = DB::table('av8_swap_orders')->where('id', $savedOrderId)->first();
                    $meta = json_decode((string) ($row->meta ?? '{}'), true);
                    $meta = is_array($meta) ? $meta : [];
                    $meta['ledger_transaction_id'] = (int) $ledger->id;
                    DB::table('av8_swap_orders')->where('id', $savedOrderId)->update([
                        'meta' => json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                        'updated_at' => now(),
                    ]);
                }
            }

            return $savedOrderId;
        });

        $message = $postLedger
            ? 'Операция Фиат/Крипта сохранена с проводкой.'
            : 'Операция Фиат/Крипта сохранена без проводки.';

        return redirect()
            ->route('bank.exchange')
            ->with('success', $message . " #{$savedOrderId}")
            ->with('bank_exchange_tab', 'crypto');
    }

    public function reverseFiatCryptoExchange(int $order): RedirectResponse
    {
        $project = $this->bankProject();
        abort_unless(Schema::hasTable('av8_swap_orders') && Schema::hasTable('conf'), 404);

        $row = DB::table('av8_swap_orders')
            ->where('id', $order)
            ->where('fid', (int) $project->id)
            ->where('source', 'bank.exchange.crypto')
            ->first();
        abort_unless($row, 404);

        $meta = json_decode((string) ($row->meta ?? '{}'), true);
        $meta = is_array($meta) ? $meta : [];
        if (! empty($meta['reversed_at']) || (string) ($row->status ?? '') === 'cancelled') {
            return redirect()
                ->route('bank.exchange')
                ->with('error', 'Проводка уже отменена.')
                ->with('bank_exchange_tab', 'crypto');
        }
        if (! $this->isLatestFiatCryptoLedger((int) $project->id, (int) ($meta['ledger_transaction_id'] ?? 0))) {
            return redirect()
                ->route('bank.exchange')
                ->with('error', 'Отменить можно только последнюю активную проводку обменки.')
                ->with('bank_exchange_tab', 'crypto');
        }

        $side = (string) ($meta['side'] ?? $row->mode ?? 'buy');
        $fiatAmount = (float) ($meta['fiat_amount'] ?? $row->pay_amount ?? 0);
        $cryptoAmount = (float) ($meta['crypto_amount'] ?? $row->expected_av8 ?? 0);
        $fiatAccountId = (int) ($meta['fiat_account_id'] ?? 0);
        $cryptoAccountId = (int) ($meta['crypto_account_id'] ?? 0);
        abort_unless(in_array($side, ['buy', 'sell'], true) && $fiatAmount > 0 && $cryptoAmount > 0 && $fiatAccountId > 0 && $cryptoAccountId > 0, 422);

        try {
            DB::transaction(function () use ($project, $order, $row, $meta, $side, $fiatAmount, $cryptoAmount, $fiatAccountId, $cryptoAccountId): void {
                $fiatAccount = DB::table('conf')->where('id', $fiatAccountId)->where('type', 'oplata')->lockForUpdate()->first();
                $cryptoAccount = DB::table('conf')->where('id', $cryptoAccountId)->where('type', 'oplata')->lockForUpdate()->first();
                if (! $fiatAccount || ! $cryptoAccount) {
                    throw new \RuntimeException('Счет операции не найден.');
                }

                if ($side === 'buy') {
                    if ((float) $cryptoAccount->value + 0.00000001 < $cryptoAmount) {
                        throw new \RuntimeException('Недостаточно средств на крипто-счете для отмены операции.');
                    }
                    DB::table('conf')->where('id', $fiatAccountId)->where('type', 'oplata')->update([
                        'value' => DB::raw('COALESCE(value, 0) + ' . abs($fiatAmount)),
                    ]);
                    DB::table('conf')->where('id', $cryptoAccountId)->where('type', 'oplata')->update([
                        'value' => DB::raw('COALESCE(value, 0) - ' . abs($cryptoAmount)),
                    ]);
                } else {
                    if ((float) $fiatAccount->value + 0.000001 < $fiatAmount) {
                        throw new \RuntimeException('Недостаточно средств на фиатном счете для отмены операции.');
                    }
                    DB::table('conf')->where('id', $cryptoAccountId)->where('type', 'oplata')->update([
                        'value' => DB::raw('COALESCE(value, 0) + ' . abs($cryptoAmount)),
                    ]);
                    DB::table('conf')->where('id', $fiatAccountId)->where('type', 'oplata')->update([
                        'value' => DB::raw('COALESCE(value, 0) - ' . abs($fiatAmount)),
                    ]);
                }

                $reversalLedger = $this->reverseFiatCryptoExchangeLedger((int) $project->id, $order, (int) ($meta['ledger_transaction_id'] ?? 0));
                if (! $reversalLedger) {
                    throw new \RuntimeException('Не удалось создать сторно-проводку. Остатки счетов не изменены.');
                }

                $meta['original_status'] = (string) ($row->status ?? '');
                $meta['reversed_at'] = now()->toDateTimeString();
                $meta['reversal_ledger_transaction_id'] = (int) $reversalLedger->id;

                DB::table('av8_swap_orders')->where('id', $order)->update([
                    'status' => 'cancelled',
                    'meta' => json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'updated_at' => now(),
                ]);
            });
        } catch (\RuntimeException $exception) {
            return redirect()
                ->route('bank.exchange')
                ->with('error', $exception->getMessage())
                ->with('bank_exchange_tab', 'crypto');
        }

        return redirect()
            ->route('bank.exchange')
            ->with('success', 'Проводка Фиат/Крипта отменена.')
            ->with('bank_exchange_tab', 'crypto');
    }

    private function isLatestFiatCryptoLedger(int $projectId, int $ledgerTransactionId): bool
    {
        if ($ledgerTransactionId <= 0 || ! Schema::hasTable('av8_swap_orders')) {
            return false;
        }

        $latestLedgerTransactionId = DB::table('av8_swap_orders')
            ->where('fid', $projectId)
            ->where('source', 'bank.exchange.crypto')
            ->where('status', '!=', 'cancelled')
            ->get(['meta'])
            ->map(function ($order): int {
                $meta = json_decode((string) ($order->meta ?? '{}'), true);
                $meta = is_array($meta) ? $meta : [];

                return ! empty($meta['reversed_at']) ? 0 : (int) ($meta['ledger_transaction_id'] ?? 0);
            })
            ->max();

        return (int) $latestLedgerTransactionId === $ledgerTransactionId;
    }

    private function createFiatCryptoExchangeLedger(
        int $orderId,
        int $projectId,
        string $side,
        object $fiatAccount,
        object $cryptoAccount,
        float $fiatAmount,
        float $cryptoAmount,
        string $fiatCurrency,
        string $cryptoCurrency,
        string $operatedAt
    ) {
        if ($fiatAmount <= 0 || ! Schema::hasTable('accounts') || ! Schema::hasTable('transactions') || ! Schema::hasTable('entries')) {
            return null;
        }

        $fiatLedgerAccount = $this->ensureBankLedgerAccount(
            "311.{$projectId}." . (int) $fiatAccount->id,
            'Операционный счет ' . trim((string) $fiatAccount->label),
            'asset',
            '311'
        );
        $cryptoLedgerAccount = $this->ensureBankLedgerAccount(
            "311.{$projectId}." . (int) $cryptoAccount->id,
            'Операционный счет ' . trim((string) $cryptoAccount->label),
            'asset',
            '311'
        );

        $debitAccountId = $side === 'buy' ? (int) $cryptoLedgerAccount->id : (int) $fiatLedgerAccount->id;
        $creditAccountId = $side === 'buy' ? (int) $fiatLedgerAccount->id : (int) $cryptoLedgerAccount->id;
        $description = $side === 'buy'
            ? "Покупка {$cryptoAmount} {$cryptoCurrency} за {$fiatAmount} {$fiatCurrency}"
            : "Продажа {$cryptoAmount} {$cryptoCurrency} за {$fiatAmount} {$fiatCurrency}";

        return app(AccountingService::class)->createTransaction(
            [
                ['account_id' => $debitAccountId, 'debit' => $fiatAmount, 'credit' => 0, 'currency' => $fiatCurrency],
                ['account_id' => $creditAccountId, 'debit' => 0, 'credit' => $fiatAmount, 'currency' => $fiatCurrency],
            ],
            $description,
            [
                'date' => $this->ledgerDate($operatedAt),
                'company_id' => $projectId,
                'reference_type' => 'bank_exchange_crypto',
                'reference_id' => $orderId,
                'currency' => $fiatCurrency,
                'amount' => $fiatAmount,
                'amount_base' => $fiatAmount,
            ]
        );
    }

    private function reverseFiatCryptoExchangeLedger(int $projectId, int $orderId, int $ledgerTransactionId = 0)
    {
        if (! Schema::hasTable('transactions') || ! Schema::hasTable('entries')) {
            return null;
        }

        $originalQuery = DB::table('transactions')
            ->where('company_id', $projectId)
            ->where('reference_type', 'bank_exchange_crypto')
            ->where('reference_id', (string) $orderId);
        if ($ledgerTransactionId > 0) {
            $originalQuery->where('id', $ledgerTransactionId);
        }

        $original = $originalQuery->latest('id')->first();
        if (! $original && $ledgerTransactionId > 0) {
            $original = DB::table('transactions')
                ->where('id', $ledgerTransactionId)
                ->where('company_id', $projectId)
                ->first();
        }
        if (! $original) {
            return null;
        }

        $existingReversal = DB::table('transactions')
            ->where('company_id', $projectId)
            ->where('reference_type', 'bank_exchange_crypto:reversal')
            ->where('reference_id', (string) $orderId)
            ->where('id', '>', (int) $original->id)
            ->first();
        if ($existingReversal) {
            return null;
        }

        $entries = DB::table('entries')
            ->where('transaction_id', (int) $original->id)
            ->get(['account_id', 'debit', 'credit'])
            ->map(fn ($entry): array => [
                'account_id' => (int) $entry->account_id,
                'debit' => (float) $entry->credit,
                'credit' => (float) $entry->debit,
            ])
            ->all();
        if ($entries === []) {
            return null;
        }

        return app(AccountingService::class)->createTransaction(
            $entries,
            'Сторно ' . trim((string) ($original->description ?? "Фиат/Крипта #{$orderId}")),
            [
                'date' => now()->toDateString(),
                'company_id' => $projectId,
                'reference_type' => 'bank_exchange_crypto:reversal',
                'reference_id' => (string) $orderId,
                'currency' => (string) ($original->currency ?? 'UAH'),
                'amount' => (float) ($original->amount ?? 0),
                'amount_base' => (float) ($original->amount_base ?? 0),
            ]
        );
    }

    public function clearing(): View
    {
        $project = $this->bankProject();
        $holdingProjects = $this->holdingProjects($project);
        $settlementEvents = $this->settlementEvents($holdingProjects, $project);
        $settlementRows = $settlementEvents->map(fn ($event) => $this->settlementRow($event, $holdingProjects, $project));
        $latestEvent = $settlementEvents->first();

        return view('bank.clearing', [
            'project' => $project,
            'holdingProjects' => $holdingProjects,
            'accountMatrix' => $this->accountMatrix($holdingProjects, $project),
            'settlementRows' => $settlementRows,
            'debtRows' => $this->intercompanyDebtRows($settlementRows),
            'serviceStatus' => [
                'listener_status' => $latestEvent ? 'online' : (Schema::hasTable('fund_pool_events') ? 'waiting' : 'not_configured'),
                'latest_tx' => (string) ($latestEvent->tx_digest ?? ''),
                'latest_event_at' => (string) ($latestEvent->event_at ?? ''),
                'events_total' => Schema::hasTable('fund_pool_events') ? (int) DB::table('fund_pool_events')->count() : 0,
                'ledger_ready' => Schema::hasTable('transactions') && Schema::hasTable('entries') && Schema::hasTable('accounts'),
            ],
        ]);
    }

    public function payments(Request $request): View
    {
        $project = $this->bankProject();
        $projectIds = HoldingScope::projectIdsFor((string) $project->id);
        [$datePreset, $dateFrom, $dateTo] = $this->paymentDateFilter(
            (string) $request->query('date_preset', 'current_month'),
            $request->query('date_from'),
            $request->query('date_to')
        );
        $filters = [
            'direction' => in_array($request->query('direction'), ['incoming', 'outgoing'], true)
                ? (string) $request->query('direction')
                : '',
            'status' => in_array($request->query('status'), ['posted', 'pending', 'reversed', 'ledger_error'], true)
                ? (string) $request->query('status')
                : '',
            'project' => in_array((string) $request->query('project'), $projectIds, true)
                ? (string) $request->query('project')
                : '',
            'date_preset' => $datePreset,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
        ];
        $paymentRows = $this->paymentRows($projectIds, $filters);

        return view('bank.payments', [
            'project' => $project,
            'holdingProjects' => $this->holdingProjects($project),
            'paymentRows' => $paymentRows,
            'ledgerRows' => $this->paymentLedgerRows($projectIds, $filters),
            'filters' => $filters,
            'summary' => [
                'incoming' => (float) $paymentRows->where('direction', 'incoming')->sum('amount'),
                'outgoing' => (float) $paymentRows->where('direction', 'outgoing')->sum('amount'),
                'posted' => $paymentRows->where('status', 'posted')->count(),
                'attention' => $paymentRows->whereIn('status', ['pending', 'ledger_error'])->count(),
            ],
            'currencyTotals' => $paymentRows
                ->groupBy('currency')
                ->map(fn ($rows) => (object) [
                    'incoming' => (float) $rows->where('direction', 'incoming')->sum('amount'),
                    'outgoing' => (float) $rows->where('direction', 'outgoing')->sum('amount'),
                ]),
        ]);
    }

    public function reconciliation(): View
    {
        return $this->placeholder('Сверка', 'Сверка остатков касс, ledger-проводок и blockchain-транзакций.');
    }

    private function placeholder(string $title, string $description): View
    {
        return view('bank.placeholder', [
            'project' => $this->bankProject(),
            'title' => $title,
            'description' => $description,
        ]);
    }

    private function bankProject(): Project
    {
        abort_unless(Schema::hasTable('project'), 404);

        $project = Project::query()->find((int) session('fid', 0));
        abort_unless($project instanceof Project, 404);
        abort_unless(strtolower(trim((string) ($project->project_type ?? ''))) === 'bank', 403);

        return $project;
    }

    private function validateDepositSettings(Request $request): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:200'],
            'deposit_type' => ['required', Rule::in(['bank', 'personal'])],
            'currency' => ['required', 'string', 'max:20'],
        ]);

        return [
            'name' => trim((string) $validated['name']),
            'deposit_type' => (string) $validated['deposit_type'],
            'currency' => $this->normalizeCurrencyCode($validated['currency']),
        ];
    }

    private function googleAccountWalletPortfolio(): array
    {
        $wallets = $this->googleAccountWallets();
        $addresses = $wallets->pluck('address')
            ->map(fn ($address) => strtolower(trim((string) $address)))
            ->filter()
            ->unique()
            ->values();

        if ($addresses->isEmpty() || ! Schema::hasTable('wallets')) {
            return [
                'wallets' => $wallets,
                'tokens' => collect(),
                'defiPositions' => collect(),
                'nfts' => collect(),
            ];
        }

        $walletRows = DB::table('wallets')
            ->whereIn(DB::raw('LOWER(address)'), $addresses->all())
            ->get(['id', 'address'])
            ->mapWithKeys(fn ($wallet) => [(int) $wallet->id => strtolower((string) $wallet->address)]);
        $tokenManifestSettings = $this->tokenManifestSettings((int) $this->bankProject()->id);

        return [
            'wallets' => $wallets,
            'tokens' => $this->walletPortfolioTokens($walletRows, $wallets, $tokenManifestSettings),
            'defiPositions' => $this->walletDefiPositions($walletRows, $wallets),
            'nfts' => $this->walletNftPositions($walletRows, $wallets),
        ];
    }

    private function googleAccountWallets()
    {
        $user = Auth::user();
        if (! $user) {
            return collect();
        }

        if (! Schema::hasTable('user_wallets')) {
            $address = trim((string) ($user->wallet_address ?? ''));

            return $address !== ''
                ? collect([(object) [
                    'address' => $address,
                    'address_short' => $this->shortHash($address),
                    'network' => (string) ($user->wallet_network ?? ''),
                    'chain_id' => (string) ($user->wallet_network ?? ''),
                    'connected_at' => (string) ($user->wallet_connected_at ?? ''),
                    'web3auth' => 0,
                    'source' => 'profile',
                ]])
                : collect();
        }

        $columns = ['address', 'network', 'connected_at'];
        if (Schema::hasColumn('user_wallets', 'web3auth')) {
            $columns[] = 'web3auth';
        }

        return DB::table('user_wallets')
            ->where('user_id', $user->id)
            ->orderByDesc(Schema::hasColumn('user_wallets', 'web3auth') ? 'web3auth' : 'id')
            ->orderByDesc('connected_at')
            ->orderByDesc('id')
            ->get($columns)
            ->map(fn ($wallet) => (object) [
                'address' => (string) $wallet->address,
                'address_short' => $this->shortHash((string) $wallet->address),
                'network' => (string) ($wallet->network ?? ''),
                'chain_id' => (string) ($wallet->network ?? ''),
                'connected_at' => (string) ($wallet->connected_at ?? ''),
                'web3auth' => (int) ($wallet->web3auth ?? 0),
                'source' => (int) ($wallet->web3auth ?? 0) === 1 ? 'google' : 'linked',
            ]);
    }

    private function walletPortfolioTokens($walletRows, $wallets, $tokenManifestSettings = null)
    {
        if ($walletRows->isEmpty() || ! Schema::hasTable('wallet_tokens')) {
            return collect();
        }

        $tokenManifestSettings = $tokenManifestSettings ?: collect();
        $walletAddressById = $walletRows->all();
        $linkedWallets = $wallets->keyBy(fn ($wallet) => strtolower((string) $wallet->address));

        return DB::table('wallet_tokens')
            ->whereIn('wallet_id', array_keys($walletAddressById))
            ->when(
                Schema::hasColumn('wallet_tokens', 'is_spam'),
                fn ($query) => $query->where(function ($nested): void {
                    $nested->whereNull('is_spam')->orWhere('is_spam', false);
                })
            )
            ->orderByDesc('value_usd')
            ->orderBy('symbol')
            ->limit(80)
            ->get()
            ->map(function ($token) use ($walletAddressById, $linkedWallets, $tokenManifestSettings) {
                $walletAddress = $walletAddressById[(int) $token->wallet_id] ?? '';
                $wallet = $linkedWallets->get(strtolower($walletAddress));

                return (object) [
                    'id' => (int) $token->id,
                    'wallet_address' => $walletAddress,
                    'wallet_short' => $this->shortHash($walletAddress),
                    'wallet_source' => $wallet?->source ?? 'linked',
                    'chain' => (string) ($token->chain ?? ''),
                    'symbol' => (string) ($token->symbol ?? 'TOKEN'),
                    'name' => (string) ($token->name ?? ''),
                    'token_address' => (string) ($token->token_address ?? ''),
                    'token_short' => $this->shortHash((string) ($token->token_address ?? '')),
                    'balance' => (float) ($token->balance ?? 0),
                    'price_usd' => $token->price_usd !== null ? (float) $token->price_usd : null,
                    'value_usd' => $token->value_usd !== null ? (float) $token->value_usd : 0.0,
                    'logo' => (string) ($token->logo ?? ''),
                    'synced_at' => (string) ($token->synced_at ?? ''),
                    'manifest_hidden' => (bool) ($tokenManifestSettings->get((int) $token->id)->hidden ?? false),
                ];
            });
    }

    private function tokenManifestRows($tokens, bool $includeHidden = false)
    {
        return $tokens
            ->when(! $includeHidden, fn ($rows) => $rows->reject(fn ($token) => (bool) ($token->manifest_hidden ?? false)))
            ->values();
    }

    private function trackedAssetRows(int $projectId)
    {
        if (! Schema::hasTable('bank_tracked_assets')) {
            return collect([
                'token' => collect(),
                'nft' => collect(),
                'defi' => collect(),
                'hidden_token' => collect(),
                'hidden_nft' => collect(),
                'hidden_defi' => collect(),
            ]);
        }

        $rows = DB::table('bank_tracked_assets')
            ->where('project_id', $projectId)
            ->orderBy('hidden')
            ->orderBy('asset_type')
            ->orderBy('name')
            ->orderByDesc('id')
            ->get()
            ->map(function ($row) {
                $address = (string) ($row->asset_address ?? '');
                $owner = (string) ($row->owner_address ?? '');

                return (object) [
                    'id' => (int) $row->id,
                    'asset_type' => (string) ($row->asset_type ?? 'token'),
                    'adapter' => (string) ($row->adapter ?? 'manual'),
                    'name' => trim((string) ($row->name ?? '')) ?: (trim((string) ($row->symbol ?? '')) ?: 'Tracked asset'),
                    'symbol' => (string) ($row->symbol ?? ''),
                    'blockchain' => (string) ($row->blockchain ?? ''),
                    'asset_address' => $address,
                    'asset_short' => $this->shortHash($address),
                    'owner_address' => $owner,
                    'owner_short' => $owner !== '' ? $this->shortHash($owner) : '—',
                    'protocol' => (string) ($row->protocol ?? ''),
                    'token_id' => (string) ($row->token_id ?? ''),
                    'decimals' => $row->decimals !== null ? (int) $row->decimals : null,
                    'last_balance' => $row->last_balance !== null ? (float) $row->last_balance : null,
                    'last_price_usd' => $row->last_price_usd !== null ? (float) $row->last_price_usd : null,
                    'last_value_usd' => $row->last_value_usd !== null ? (float) $row->last_value_usd : null,
                    'last_payload' => json_decode((string) ($row->last_payload ?? '[]'), true) ?: [],
                    'available_fields' => json_decode((string) ($row->available_fields ?? '[]'), true) ?: [],
                    'selected_fields' => json_decode((string) ($row->selected_fields ?? '[]'), true) ?: [],
                    'image_url' => (string) ($row->image_url ?? ''),
                    'external_url' => (string) ($row->external_url ?? ''),
                    'adapter_action' => route('bank.tracked-assets.adapter', ['asset' => (int) $row->id]),
                    'sync_status' => (string) ($row->sync_status ?? 'manual'),
                    'sync_error' => (string) ($row->sync_error ?? ''),
                    'hidden' => (bool) ($row->hidden ?? false),
                    'last_synced_at' => (string) ($row->last_synced_at ?? ''),
                ];
            });

        return collect([
            'token' => $rows->where('asset_type', 'token')->reject(fn ($row) => $row->hidden)->values(),
            'nft' => $rows->where('asset_type', 'nft')->reject(fn ($row) => $row->hidden)->values(),
            'defi' => $rows->where('asset_type', 'defi')->reject(fn ($row) => $row->hidden)->values(),
            'hidden_token' => $rows->where('asset_type', 'token')->filter(fn ($row) => $row->hidden)->values(),
            'hidden_nft' => $rows->where('asset_type', 'nft')->filter(fn ($row) => $row->hidden)->values(),
            'hidden_defi' => $rows->where('asset_type', 'defi')->filter(fn ($row) => $row->hidden)->values(),
        ]);
    }

    private function manualInvestmentAssetRows(int $projectId)
    {
        if (! Schema::hasTable('bank_tracked_assets')) {
            return collect();
        }

        return DB::table('bank_tracked_assets')
            ->where('project_id', $projectId)
            ->whereIn('asset_type', ['token', 'pool'])
            ->where('hidden', false)
            ->when(
                Schema::hasColumn('bank_tracked_assets', 'adapter'),
                fn ($query) => $query->where(function ($subQuery) {
                    $subQuery->where('adapter', 'manual')
                        ->orWhere('blockchain', 'manual');
                }),
                fn ($query) => $query->where('blockchain', 'manual')
            )
            ->orderBy('asset_type')
            ->orderBy('name')
            ->orderByDesc('id')
            ->get()
            ->map(function ($asset) {
                $address = (string) ($asset->asset_address ?? '');
                $type = (string) ($asset->asset_type ?? 'token');

                return (object) [
                    'asset_type' => $type,
                    'asset_key' => 'manual:' . (int) $asset->id,
                    'source_id' => (int) $asset->id,
                    'update_action' => route('bank.invest-assets.update', ['asset' => (int) $asset->id]),
                    'name' => trim((string) ($asset->name ?? '')) ?: ($type === 'pool' ? 'Пул' : 'Токен'),
                    'description' => $address,
                    'object_address' => $address,
                    'object_short' => $this->shortHash($address),
                    'currency' => (string) ($asset->symbol ?? ($type === 'pool' ? 'POOL' : 'TOKEN')),
                    'quantity' => $asset->last_balance !== null ? (float) $asset->last_balance : 0.0,
                    'price_usd' => $asset->last_price_usd !== null ? (float) $asset->last_price_usd : 0.0,
                    'value_usd' => $asset->last_value_usd !== null ? (float) $asset->last_value_usd : 0.0,
                    'created_on' => (string) ($asset->created_on ?? ''),
                    'source' => 'bank_tracked_assets',
                    'status' => (string) ($asset->sync_status ?? 'manual'),
                ];
            })
            ->values();
    }

    private function normalizeTrackedAssetType(string $assetType): string
    {
        return match (strtolower(trim($assetType))) {
            'tokens', 'token' => 'token',
            'nft' => 'nft',
            'defi' => 'defi',
            default => '',
        };
    }

    private function tokenManifestSettings(int $projectId)
    {
        if (! Schema::hasTable('bank_token_manifest_items')) {
            return collect();
        }

        return DB::table('bank_token_manifest_items')
            ->where('project_id', $projectId)
            ->get(['wallet_token_id', 'hidden'])
            ->mapWithKeys(fn ($item) => [
                (int) $item->wallet_token_id => (object) [
                    'hidden' => (bool) ($item->hidden ?? false),
                ],
            ]);
    }

    private function tokenManifestTargetExists(int $token): bool
    {
        $wallets = $this->googleAccountWallets();
        $addresses = $wallets->pluck('address')
            ->map(fn ($address) => strtolower(trim((string) $address)))
            ->filter()
            ->unique()
            ->values();

        if ($addresses->isEmpty() || ! Schema::hasTable('wallets') || ! Schema::hasTable('wallet_tokens')) {
            return false;
        }

        $walletIds = DB::table('wallets')
            ->whereIn(DB::raw('LOWER(address)'), $addresses->all())
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        return $walletIds !== []
            && DB::table('wallet_tokens')
                ->where('id', $token)
                ->whereIn('wallet_id', $walletIds)
                ->exists();
    }

    private function walletDefiPositions($walletRows, $wallets)
    {
        if ($walletRows->isEmpty() || ! Schema::hasTable('wallet_protocol_snapshots')) {
            return collect();
        }

        $walletAddressById = $walletRows->all();
        $linkedWallets = $wallets->keyBy(fn ($wallet) => strtolower((string) $wallet->address));

        return DB::table('wallet_protocol_snapshots')
            ->whereIn('wallet_id', array_keys($walletAddressById))
            ->orderByDesc('synced_at')
            ->get()
            ->flatMap(function ($snapshot) use ($walletAddressById, $linkedWallets) {
                $walletAddress = $walletAddressById[(int) $snapshot->wallet_id] ?? '';
                $wallet = $linkedWallets->get(strtolower($walletAddress));
                $payload = json_decode((string) $snapshot->payload, true);
                $payload = is_array($payload) ? $payload : [];

                return $this->extractDefiPositionsFromProtocols($payload, $walletAddress, $wallet?->source ?? 'linked', (string) $snapshot->chain_id, (string) ($snapshot->synced_at ?? ''));
            })
            ->sortByDesc('value_usd')
            ->values()
            ->take(80);
    }

    private function walletNftPositions($walletRows, $wallets)
    {
        if ($walletRows->isEmpty() || ! Schema::hasTable('wallet_protocol_snapshots')) {
            return collect();
        }

        $walletAddressById = $walletRows->all();
        $linkedWallets = $wallets->keyBy(fn ($wallet) => strtolower((string) $wallet->address));

        return DB::table('wallet_protocol_snapshots')
            ->whereIn('wallet_id', array_keys($walletAddressById))
            ->orderByDesc('synced_at')
            ->get()
            ->flatMap(function ($snapshot) use ($walletAddressById, $linkedWallets) {
                $walletAddress = $walletAddressById[(int) $snapshot->wallet_id] ?? '';
                $wallet = $linkedWallets->get(strtolower($walletAddress));
                $payload = json_decode((string) $snapshot->payload, true);
                $payload = is_array($payload) ? $payload : [];

                return $this->extractNftsFromPayload($payload, $walletAddress, $wallet?->source ?? 'linked', (string) $snapshot->chain_id, (string) ($snapshot->synced_at ?? ''));
            })
            ->sortByDesc('value_usd')
            ->values()
            ->take(80);
    }

    private function extractDefiPositionsFromProtocols(array $protocols, string $walletAddress, string $walletSource, string $chainId, string $syncedAt)
    {
        return collect($protocols)
            ->flatMap(function ($protocol) use ($walletAddress, $walletSource, $chainId, $syncedAt) {
                if (! is_array($protocol)) {
                    return [];
                }

                $protocolName = (string) ($protocol['name'] ?? 'Protocol');
                $protocolUrl = (string) ($protocol['url'] ?? '');
                $protocolIcon = (string) ($protocol['icon'] ?? '');
                $items = [];

                foreach (['tokens' => 'Token', 'pools' => 'Pool', 'loans' => 'Loan'] as $bucket => $kind) {
                    foreach ((array) ($protocol[$bucket] ?? []) as $position) {
                        if (! is_array($position)) {
                            continue;
                        }

                        $value = (float) ($position['usd_value'] ?? $position['asset_usd_value'] ?? 0);
                        if ($bucket === 'loans') {
                            $value *= -1;
                        }

                        $items[] = (object) [
                            'wallet_address' => $walletAddress,
                            'wallet_short' => $this->shortHash($walletAddress),
                            'wallet_source' => $walletSource,
                            'chain' => (string) ($position['chain'] ?? $chainId),
                            'protocol' => $protocolName,
                            'protocol_url' => $protocolUrl,
                            'protocol_icon' => $protocolIcon,
                            'kind' => $kind,
                            'name' => (string) ($position['name'] ?? $protocolName),
                            'symbol' => (string) ($position['symbol'] ?? ''),
                            'amount' => $position['amount'] ?? null,
                            'value_usd' => $value,
                            'link' => (string) ($position['link'] ?? $protocolUrl),
                            'synced_at' => $syncedAt,
                        ];
                    }
                }

                return $items;
            })
            ->filter(fn ($position) => abs((float) $position->value_usd) > 0.000001)
            ->values();
    }

    private function extractNftsFromPayload(array $payload, string $walletAddress, string $walletSource, string $chainId, string $syncedAt)
    {
        $candidates = collect();
        foreach (['nfts', 'nft', 'collectibles', 'assets'] as $key) {
            if (isset($payload[$key]) && is_array($payload[$key])) {
                $candidates = $candidates->concat($payload[$key]);
            }
        }

        return $candidates
            ->filter(fn ($item) => is_array($item))
            ->map(fn (array $item) => (object) [
                'wallet_address' => $walletAddress,
                'wallet_short' => $this->shortHash($walletAddress),
                'wallet_source' => $walletSource,
                'chain' => (string) ($item['chain'] ?? $chainId),
                'name' => (string) ($item['name'] ?? $item['title'] ?? $item['symbol'] ?? 'NFT'),
                'collection' => (string) ($item['collection'] ?? $item['collection_name'] ?? $item['protocol'] ?? ''),
                'object_id' => (string) ($item['object_id'] ?? $item['token_id'] ?? $item['id'] ?? ''),
                'object_short' => $this->shortHash((string) ($item['object_id'] ?? $item['token_id'] ?? $item['id'] ?? '')),
                'value_usd' => (float) ($item['value_usd'] ?? $item['usd_value'] ?? 0),
                'image_url' => (string) ($item['image_url'] ?? $item['image'] ?? $item['logo_url'] ?? ''),
                'synced_at' => $syncedAt,
            ])
            ->values();
    }

    private function investmentPools()
    {
        if (! Schema::hasTable('fund_pools')) {
            return collect();
        }

        $eventsByPool = Schema::hasTable('fund_pool_events')
            ? DB::table('fund_pool_events')
                ->orderByDesc('event_at')
                ->orderByDesc('id')
                ->get()
                ->groupBy(fn ($event) => strtolower((string) ($event->pool_object_id ?? '')))
            : collect();

        return DB::table('fund_pools')
            ->orderByDesc(Schema::hasColumn('fund_pools', 'active') ? 'active' : 'id')
            ->orderBy('risk_level')
            ->orderBy('name')
            ->get()
            ->map(function ($pool) use ($eventsByPool) {
                $poolObjectId = (string) ($pool->pool_object_id ?? '');
                $latestEvent = $eventsByPool->get(strtolower($poolObjectId), collect())->first();
                $balanceUsdc = $latestEvent
                    ? $this->usdcAtomicToFloat((string) ($latestEvent->balance_usdc ?? '0'))
                    : 0.0;
                $targetApy = (int) ($latestEvent->target_apy_bps ?? $pool->target_apy_bps ?? 0);
                $realizedApy = (int) ($latestEvent->realized_apy_bps ?? $pool->realized_apy_bps ?? 0);

                return (object) [
                    'id' => (int) $pool->id,
                    'name' => (string) ($pool->name ?? 'Pool #' . $pool->id),
                    'description' => (string) ($pool->description ?? ''),
                    'network' => (string) ($pool->network ?? ''),
                    'package_id' => (string) ($pool->package_id ?? ''),
                    'symbol' => (string) ($pool->symbol ?? $this->symbolFromCoinType((string) ($pool->coin_type ?? ''))),
                    'coin_type' => (string) ($pool->coin_type ?? ''),
                    'pool_object_id' => $poolObjectId,
                    'pool_object_short' => $this->shortHash($poolObjectId),
                    'chain_status' => $this->isOnChainPoolObject($poolObjectId) ? 'onchain' : 'offchain',
                    'chain_status_label' => $this->isOnChainPoolObject($poolObjectId) ? 'On-chain' : 'Off-chain',
                    'risk_level' => (int) ($pool->risk_level ?? 0),
                    'target_apy_bps' => $targetApy,
                    'realized_apy_bps' => $realizedApy,
                    'apy_bps' => $realizedApy > 0 ? $realizedApy : $targetApy,
                    'min_deposit_usdc' => $this->usdcAtomicToFloat((string) ($pool->min_deposit_usdc ?? '0')),
                    'min_av8_balance' => $this->tokenAtomicToFloat((string) ($pool->min_av8_balance ?? '0'), 9),
                    'max_weight_bps' => (int) ($pool->max_weight_bps ?? 0),
                    'active' => (bool) ($pool->active ?? true),
                    'is_default_deposit' => (bool) ($pool->is_default_deposit ?? false),
                    'logo_url' => (string) ($pool->logo_url ?? ''),
                    'notes' => (string) ($pool->notes ?? ''),
                    'source_type' => (string) ($pool->source_type ?? ''),
                    'credit_request_status' => (string) ($pool->credit_request_status ?? ''),
                    'collateral_label' => (string) ($pool->collateral_label ?? ''),
                    'collateral_protocol' => (string) ($pool->collateral_protocol ?? ''),
                    'balance_usdc' => $balanceUsdc,
                    'latest_event_at' => (string) ($latestEvent->event_at ?? ''),
                    'latest_event_type' => (string) ($latestEvent->event_type ?? ''),
                ];
            });
    }

    private function isOnChainPoolObject(string $poolObjectId): bool
    {
        $value = strtolower(trim($poolObjectId));

        return $value !== ''
            && $value !== '0x'
            && ! preg_match('/^0x0+$/', $value);
    }

    private function bankDepositPoolRows($deposits, int $projectId)
    {
        $depositsByCurrency = $deposits
            ->groupBy(fn ($deposit) => strtoupper((string) ($deposit->currency ?? '')));
        $accountingByAsset = $this->poolAccountingBalancesFromOperations($projectId);

        return $this->investmentPools()
            ->map(function ($pool) use ($depositsByCurrency, $accountingByAsset) {
                $symbol = strtoupper((string) ($pool->symbol ?? ''));
                $matchingDeposits = $depositsByCurrency->get($symbol, collect());
                $assetKey = 'pool:' . (int) $pool->id;
                $accounting = $accountingByAsset->get($assetKey, (object) [
                    'balance' => 0.0,
                    'operations_count' => 0,
                ]);

                $pool->deposit_currency = $symbol;
                $pool->deposit_count = $matchingDeposits->count();
                $pool->deposit_balance = (float) $matchingDeposits->sum('balance');
                $pool->deposit_limit = (float) $matchingDeposits->sum('limit');
                $pool->asset_key = $assetKey;
                $pool->accounting_balance_usd = (float) $accounting->balance;
                $pool->accounting_operations_count = (int) $accounting->operations_count;
                $pool->accounting_difference_usd = (float) $pool->accounting_balance_usd - (float) $pool->balance_usdc;

                return $pool;
            });
    }

    private function poolAccountingBalancesFromOperations(int $projectId)
    {
        if (! Schema::hasTable('bank_invest_operations')) {
            return collect();
        }

        return DB::table('bank_invest_operations')
            ->where('project_id', $projectId)
            ->where('asset_type', 'pool')
            ->where('asset_key', 'like', 'pool:%')
            ->get(['asset_key', 'direction', 'value_usd'])
            ->groupBy('asset_key')
            ->map(function ($operations) {
                $balance = (float) $operations->sum(function ($operation) {
                    $value = (float) ($operation->value_usd ?? 0);

                    return match ((string) $operation->direction) {
                        'asset_to_account' => -abs($value),
                        'revaluation' => $value,
                        default => abs($value),
                    };
                });

                return (object) [
                    'balance' => $balance,
                    'operations_count' => $operations->count(),
                ];
            });
    }

    private function investmentPoolEvents()
    {
        if (! Schema::hasTable('fund_pool_events')) {
            return collect();
        }

        return DB::table('fund_pool_events')
            ->orderByDesc('event_at')
            ->orderByDesc('id')
            ->limit(12)
            ->get()
            ->map(fn ($event) => (object) [
                'event_at' => (string) ($event->event_at ?? ''),
                'event_type' => (string) ($event->event_type ?? ''),
                'network' => (string) ($event->network ?? ''),
                'pool_object_id' => (string) ($event->pool_object_id ?? ''),
                'pool_object_short' => $this->shortHash((string) ($event->pool_object_id ?? '')),
                'owner_address' => (string) ($event->owner_address ?? ''),
                'owner_short' => $this->shortHash((string) ($event->owner_address ?? '')),
                'amount_usdc' => $this->usdcAtomicToFloat((string) ($event->amount_usdc ?? '0')),
                'balance_usdc' => $this->usdcAtomicToFloat((string) ($event->balance_usdc ?? '0')),
                'tx_digest' => (string) ($event->tx_digest ?? ''),
                'tx_short' => $this->shortHash((string) ($event->tx_digest ?? '')),
            ]);
    }

    private function fixedInvestmentAssetRows($tokenRows, $nftRows, $pools, $trackedAssets)
    {
        $tokens = $tokenRows
            ->groupBy(function ($token): string {
                $chain = strtolower(trim((string) $token->chain));
                $address = strtolower(trim((string) $token->token_address));
                $symbol = strtoupper(trim((string) $token->symbol));

                return implode(':', [
                    $chain !== '' ? $chain : 'chain',
                    $address !== '' ? $address : 'native',
                    $symbol !== '' ? $symbol : 'TOKEN',
                ]);
            })
            ->map(function ($rows, string $key) {
                $first = $rows->first();
                $symbol = trim((string) $first->symbol) ?: 'TOKEN';
                $chain = trim((string) $first->chain) ?: 'chain';
                $tokenAddress = trim((string) $first->token_address);
                $walletCount = $rows
                    ->pluck('wallet_address')
                    ->map(fn ($address) => strtolower(trim((string) $address)))
                    ->filter()
                    ->unique()
                    ->count();

                return (object) [
                    'asset_type' => 'token',
                    'asset_key' => 'token:' . md5($key),
                    'source_id' => (int) $first->id,
                    'name' => $symbol,
                    'description' => trim((string) $first->name) ?: ($tokenAddress !== '' ? $this->shortHash($tokenAddress) : 'native') . ' · ' . strtoupper($chain) . ' · ' . $walletCount . ' кош.',
                    'currency' => $symbol,
                    'value_usd' => (float) $rows->sum('value_usd'),
                    'source' => 'wallet_tokens',
                    'status' => 'cached',
                ];
            })
            ->values();

        $nfts = $nftRows->map(fn ($nft, int $index) => (object) [
            'asset_type' => 'nft',
            'asset_key' => 'nft:' . ($nft->object_id !== '' ? $nft->object_id : $index),
            'source_id' => 0,
            'name' => (string) $nft->name,
            'description' => (string) ($nft->collection !== '' ? $nft->collection : $nft->object_short),
            'currency' => 'NFT',
            'value_usd' => (float) $nft->value_usd,
            'source' => 'wallet_protocol_snapshots',
            'status' => 'cached',
        ]);

        $poolRows = $pools->map(fn ($pool) => (object) [
            'asset_type' => 'pool',
            'asset_key' => 'pool:' . (int) $pool->id,
            'source_id' => (int) $pool->id,
            'name' => (string) $pool->name,
            'description' => $pool->description !== '' ? (string) $pool->description : (string) $pool->pool_object_short,
            'currency' => 'USDC',
            'value_usd' => (float) $pool->balance_usdc,
            'source' => 'fund_pools',
            'status' => $pool->active ? 'active' : 'paused',
        ]);

        $tracked = collect(['token', 'nft', 'defi'])
            ->flatMap(fn ($type) => $trackedAssets->get($type, collect()))
            ->map(fn ($asset) => (object) [
                'asset_type' => $asset->asset_type === 'defi' ? 'defi' : (string) $asset->asset_type,
                'asset_key' => 'tracked:' . (int) $asset->id,
                'source_id' => (int) $asset->id,
                'name' => (string) $asset->name,
                'description' => trim((string) ($asset->protocol !== '' ? $asset->protocol : $asset->asset_short)),
                'currency' => trim((string) $asset->symbol) ?: strtoupper((string) $asset->asset_type),
                'value_usd' => (float) ($asset->last_value_usd ?? 0),
                'source' => 'bank_tracked_assets',
                'status' => (string) $asset->sync_status,
            ]);

        return $tokens
            ->concat($nfts)
            ->concat($poolRows)
            ->concat($tracked)
            ->sortBy([['asset_type', 'asc'], ['name', 'asc']])
            ->values();
    }

    private function investOperationAssetOptions(int $projectId)
    {
        return $this->manualInvestmentAssetRows($projectId)
            ->concat($this->investmentPoolAssetRows())
            ->unique('asset_key')
            ->values();
    }

    private function investmentPoolAssetRows()
    {
        return $this->investmentPools()
            ->map(fn ($pool) => (object) [
                'asset_type' => 'pool',
                'asset_key' => 'pool:' . (int) $pool->id,
                'source_id' => (int) $pool->id,
                'name' => (string) $pool->name,
                'description' => $pool->description !== '' ? (string) $pool->description : (string) $pool->pool_object_short,
                'currency' => 'USDC',
                'value_usd' => (float) $pool->balance_usdc,
                'source' => 'fund_pools',
                'status' => $pool->active ? 'active' : 'paused',
            ]);
    }

    private function bankInvestOperations(int $projectId, $operationalAccounts, $fixedAssetRows, array $filters = [])
    {
        if (! Schema::hasTable('bank_invest_operations')) {
            return collect();
        }

        $accountsById = $operationalAccounts->keyBy(fn ($account) => (int) $account->id);
        $assetsByKey = $fixedAssetRows->keyBy('asset_key');
        $assetKeys = collect($filters['asset_keys'] ?? [])
            ->map(fn ($key) => (string) $key)
            ->filter()
            ->values()
            ->all();

        return DB::table('bank_invest_operations')
            ->where('project_id', $projectId)
            ->when(
                isset($filters['asset_type']),
                fn ($query) => $query->where('asset_type', (string) $filters['asset_type'])
            )
            ->when(
                $assetKeys !== [],
                fn ($query) => $query->whereIn('asset_key', $assetKeys)
            )
            ->orderByDesc('operated_at')
            ->orderByDesc('id')
            ->get()
            ->map(function ($row) use ($accountsById, $assetsByKey) {
                $account = $accountsById->get((int) $row->account_id);
                $asset = $assetsByKey->get((string) $row->asset_key);

                return (object) [
                    'id' => (int) $row->id,
                    'account_id' => (int) $row->account_id,
                    'account_label' => $account?->label ?? ('Счет #' . (int) $row->account_id),
                    'direction' => (string) $row->direction,
                    'direction_label' => match ((string) $row->direction) {
                        'asset_to_account' => 'Актив -> Счет',
                        'revaluation' => 'Переоценка',
                        default => 'Счет -> Актив',
                    },
                    'asset_type' => (string) $row->asset_type,
                    'asset_key' => (string) $row->asset_key,
                    'asset_label' => $asset?->name ?? (string) $row->asset_label,
                    'currency' => (string) $row->currency,
                    'quantity' => (float) $row->quantity,
                    'amount' => (float) $row->amount,
                    'price_usd' => $row->price_usd !== null ? (float) $row->price_usd : null,
                    'value_usd' => (float) $row->value_usd,
                    'ledger_transaction_id' => (int) ($row->ledger_transaction_id ?? 0),
                    'status' => (string) ($row->status ?? 'pending'),
                    'note' => (string) ($row->note ?? ''),
                    'operated_at' => (string) ($row->operated_at ?? ''),
                ];
            });
    }

    private function investOperationRows($investOperations)
    {
        $latestOperationIdByAsset = $investOperations
            ->groupBy('asset_key')
            ->map(fn ($operations) => (int) $operations->first()->id);

        return $investOperations
            ->map(fn ($operation) => $this->investOperationMovementPayload($operation, $latestOperationIdByAsset))
            ->values();
    }

    private function investOperationMovementPayload(object $operation, $latestOperationIdByAsset): array
    {
        $canEdit = (int) $operation->id === (int) ($latestOperationIdByAsset[(string) $operation->asset_key] ?? 0);
        $isPosted = (int) $operation->ledger_transaction_id > 0 && (string) $operation->status === 'posted';

        return [
            'id' => (int) $operation->id,
            'date' => (string) $operation->operated_at,
            'direction' => (string) $operation->direction,
            'direction_label' => (string) $operation->direction_label,
            'account_id' => (int) $operation->account_id,
            'account_label' => (string) $operation->account_label,
            'asset_key' => (string) $operation->asset_key,
            'asset_label' => (string) $operation->asset_label,
            'asset_type' => (string) $operation->asset_type,
            'currency' => (string) $operation->currency,
            'quantity' => (float) $operation->quantity,
            'amount' => (float) $operation->amount,
            'price_usd' => $operation->price_usd !== null ? (float) $operation->price_usd : null,
            'value_usd' => (float) $operation->value_usd,
            'status' => (string) $operation->status,
            'ledger_transaction_id' => (int) $operation->ledger_transaction_id,
            'can_edit' => $canEdit,
            'is_posted' => $isPosted,
            'can_reverse' => $canEdit && $isPosted,
            'edit_hint' => $canEdit
                ? 'Можно изменить: это последний документ по активу.'
                : 'Закрыто: по активу есть более новый документ.',
            'update_action' => route('bank.invest-operations.update', ['operation' => (int) $operation->id]),
            'destroy_action' => route('bank.invest-operations.destroy', ['operation' => (int) $operation->id]),
            'reverse_action' => route('bank.invest-operations.reverse', ['operation' => (int) $operation->id]),
            'note' => (string) $operation->note,
        ];
    }

    private function accountAssetAllocations($operationalAccounts, $investOperations)
    {
        $operationsByAccount = $investOperations->groupBy('account_id');

        return $operationalAccounts->map(function ($account) use ($operationsByAccount) {
            $rows = $operationsByAccount
                ->get((int) $account->id, collect())
                ->groupBy('asset_key')
                ->map(function ($operations) {
                    $first = $operations->first();
                    $value = (float) $operations->sum(fn ($operation) => $operation->direction === 'asset_to_account'
                        ? -1 * (float) $operation->value_usd
                        : (float) $operation->value_usd);
                    $quantity = (float) $operations->sum(function ($operation) {
                        if ($operation->direction === 'revaluation') {
                            return 0;
                        }

                        return $operation->direction === 'asset_to_account'
                            ? -1 * (float) $operation->quantity
                            : (float) $operation->quantity;
                    });

                    return (object) [
                        'asset_key' => (string) $first->asset_key,
                        'asset_type' => (string) $first->asset_type,
                        'asset_label' => (string) $first->asset_label,
                        'currency' => (string) $first->currency,
                        'quantity' => $quantity,
                        'value_usd' => $value,
                    ];
                })
                ->filter(fn ($row) => abs((float) $row->value_usd) > 0.000001 || abs((float) $row->quantity) > 0.000001)
                ->sortByDesc('value_usd')
                ->values();

            $total = (float) $rows->sum('value_usd');
            $rows = $rows->map(function ($row) use ($total) {
                $row->share = $total > 0 ? (float) $row->value_usd / $total * 100 : 0.0;
                return $row;
            });

            return (object) [
                'account' => $account,
                'assets' => $rows,
                'invested_total' => $total,
                'available_balance' => (float) $account->balance,
            ];
        });
    }

    private function investmentPortfolioRows($deposits, $pools, $assetManifestSettings = null)
    {
        $assetManifestSettings = $assetManifestSettings ?: collect();

        $depositRows = $deposits->map(fn ($deposit) => (object) [
            'asset_type' => 'deposit',
            'asset_id' => (int) $deposit->id,
            'group' => 'liquid',
            'type' => 'Депозит',
            'name' => (string) $deposit->name,
            'description' => (string) $deposit->project_name,
            'currency' => (string) $deposit->currency,
            'value_usd' => (float) $deposit->balance,
            'share' => 0.0,
            'status' => $deposit->is_active ? 'active' : 'paused',
            'tone' => 'assets',
        ]);

        $poolRows = $pools->map(fn ($pool) => (object) [
            'asset_type' => 'pool',
            'asset_id' => (int) $pool->id,
            'group' => 'defi',
            'type' => $pool->source_type === 'credit_request' ? 'Кредитный пул' : 'Пул',
            'name' => $pool->name,
            'description' => $pool->collateral_label !== ''
                ? trim($pool->collateral_label . ' ' . $pool->collateral_protocol)
                : ($pool->description !== '' ? $pool->description : $pool->pool_object_short),
            'currency' => 'USDC',
            'value_usd' => (float) $pool->balance_usdc,
            'share' => 0.0,
            'status' => $pool->active ? 'active' : 'paused',
            'tone' => 'defi',
        ]);

        $rows = $depositRows->concat($poolRows)->values();
        $total = (float) $rows->sum('value_usd');

        return $rows->map(function ($row, int $index) use ($total, $assetManifestSettings) {
            $settings = $assetManifestSettings->get($row->asset_type . ':' . $row->asset_id);
            $row->share = $total > 0 ? (float) $row->value_usd / $total * 100 : 0.0;
            $row->manifest_position = (int) ($settings->position ?? 0);
            $row->manifest_hidden = (bool) ($settings->hidden ?? false);
            $row->manifest_order = $index;
            return $row;
        });
    }

    private function assetManifestRows($portfolioRows, bool $includeHidden = false)
    {
        return $portfolioRows
            ->when(! $includeHidden, fn ($rows) => $rows->reject(fn ($row) => (bool) ($row->manifest_hidden ?? false)))
            ->sortBy(fn ($row) => sprintf(
                '%010d:%010d',
                (int) ($row->manifest_position ?? 0) > 0 ? (int) $row->manifest_position : 999999999,
                (int) ($row->manifest_order ?? 0)
            ))
            ->values();
    }

    private function assetManifestSettings(int $projectId)
    {
        if (! Schema::hasTable('bank_asset_manifest_items')) {
            return collect();
        }

        return DB::table('bank_asset_manifest_items')
            ->where('project_id', $projectId)
            ->get(['asset_type', 'asset_id', 'position', 'hidden'])
            ->mapWithKeys(fn ($item) => [
                (string) $item->asset_type . ':' . (int) $item->asset_id => (object) [
                    'position' => (int) ($item->position ?? 0),
                    'hidden' => (bool) ($item->hidden ?? false),
                ],
            ]);
    }

    private function assetManifestTargetExists(string $source, int $asset, int $projectId): bool
    {
        if ($source === 'deposit') {
            return Schema::hasTable('conf')
                && DB::table('conf')
                    ->where('id', $asset)
                    ->where('type', 'deposit')
                    ->whereIn('firma', array_map('intval', HoldingScope::projectIdsFor((string) $projectId)))
                    ->exists();
        }

        if ($source === 'pool') {
            return Schema::hasTable('fund_pools')
                && DB::table('fund_pools')->where('id', $asset)->exists();
        }

        return false;
    }

    private function tokenAtomicToFloat(string $value, int $decimals): float
    {
        $value = trim($value);
        if ($value === '') {
            return 0.0;
        }
        if (str_contains($value, '.')) {
            return (float) $value;
        }

        return (float) $value / (10 ** $decimals);
    }

    private function usdcAtomicToFloat(string $value): float
    {
        return $this->tokenAtomicToFloat($value, 6);
    }

    private function symbolFromCoinType(string $coinType): string
    {
        $parts = explode('::', trim($coinType));
        $symbol = strtoupper((string) end($parts));

        return $symbol !== '' ? $symbol : 'TOKEN';
    }

    private function shortHash(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '—';
        }

        return mb_strlen($value) > 18
            ? mb_substr($value, 0, 10) . '...' . mb_substr($value, -6)
            : $value;
    }

    private function bankDeposits(array $projectIds)
    {
        if (! Schema::hasTable('conf')) {
            return collect();
        }

        return DB::table('conf as c')
            ->leftJoin('project as p', 'p.id', '=', 'c.firma')
            ->where('c.type', 'deposit')
            ->whereIn('c.firma', array_map('intval', $projectIds))
            ->orderBy('p.name')
            ->orderBy('c.name')
            ->get([
                'c.id',
                'c.firma',
                'c.name',
                'c.value',
                'c.value1',
                'c.currency',
                'c.status',
                'c.vision',
                'c.doc',
                'p.name as project_name',
            ])
            ->map(function ($deposit) {
                $status = (int) ($deposit->status ?? 0);
                $depositType = trim((string) ($deposit->doc ?? ''));
                $depositType = in_array($depositType, ['bank', 'personal'], true) ? $depositType : 'bank';

                return (object) [
                    'id' => (string) $deposit->id,
                    'name' => trim((string) $deposit->name) ?: 'Депозит #' . $deposit->id,
                    'project_name' => trim((string) $deposit->project_name) ?: 'Проект #' . $deposit->firma,
                    'balance' => (float) ($deposit->value ?? 0),
                    'limit' => (float) ($deposit->value1 ?? 0),
                    'currency' => $this->normalizeCurrencyCode($deposit->currency ?? 'UAH'),
                    'deposit_type' => $depositType,
                    'deposit_type_label' => $depositType === 'personal' ? 'Личный' : 'Банковский',
                    'is_active' => $status === 1,
                    'status_label' => $status === 1 ? 'Активен' : ($status === 3 ? 'Закрыт' : 'На проверке'),
                    'is_visible' => (string) ($deposit->vision ?? '1') !== '0',
                ];
            });
    }

    private function bankOperationalAccounts(string $projectId)
    {
        return $this->bankOperationalAccountsForProjects([$projectId]);
    }

    private function bankOperationalAccountsByAccountType(string $projectId, string $accountType)
    {
        if (! Schema::hasTable('conf')) {
            return collect();
        }

        $query = DB::table('conf')
            ->where('type', 'oplata')
            ->where('firma', $projectId);

        if (Schema::hasColumn('conf', 'doc')) {
            $query->where(function ($nested) use ($accountType): void {
                $nested->where('doc', $accountType);
                if ($accountType === 'bank') {
                    $nested->orWhereNull('doc')->orWhere('doc', '');
                }
            });
        }

        return $query
            ->orderBy('name')
            ->get()
            ->map(fn ($account) => $this->normalizeCashAccount($account))
            ->where('account_type', $accountType)
            ->values();
    }

    private function bankOperationalAccountsForProjects(array $projectIds)
    {
        if (! Schema::hasTable('conf')) {
            return collect();
        }

        return DB::table('conf')
            ->where('type', 'oplata')
            ->whereIn('firma', array_map('intval', $projectIds))
            ->orderBy('name')
            ->get()
            ->map(fn ($account) => $this->normalizeCashAccount($account));
    }

    private function operationalAccountReturnRoute(Request $request): string
    {
        return $request->input('redirect_to') === 'bank.operational-accounts'
            ? 'bank.operational-accounts'
            : 'bank.cash-accounts';
    }

    private function depositTransferAccountId(object $document, string $direction): string
    {
        $primary = $direction === 'deposit_to_account'
            ? trim((string) ($document->oplata2 ?? ''))
            : trim((string) ($document->oplata ?? ''));
        $fallback = $direction === 'deposit_to_account'
            ? trim((string) ($document->oplata ?? ''))
            : trim((string) ($document->oplata2 ?? ''));

        return $primary !== '' && $primary !== '0' ? $primary : $fallback;
    }

    private function depositTransferAccountName(object $document, string $direction): string
    {
        $primary = $direction === 'deposit_to_account'
            ? trim((string) ($document->account_to_name ?? ''))
            : trim((string) ($document->account_from_name ?? ''));
        $fallback = $direction === 'deposit_to_account'
            ? trim((string) ($document->account_from_name ?? ''))
            : trim((string) ($document->account_to_name ?? ''));

        return $primary !== '' ? $primary : $fallback;
    }

    private function createDepositTransferDocument(
        string $projectId,
        int $depositId,
        int $accountId,
        float $amount,
        string $currency,
        string $accountName,
        string $depositName,
        string $direction,
        bool $postLedger = true
    ): int {
        $columns = Schema::getColumnListing('z_document');
        $maxNum = DB::table('z_document')
            ->where('firma', $projectId)
            ->where('type', 'PP')
            ->max(DB::raw('CAST(num AS UNSIGNED)'));
        $isDepositToAccount = $direction === 'deposit_to_account';

        $payload = [
            'type' => 'PP',
            'firma' => $projectId,
            'num' => $maxNum ? (int) $maxNum + 1 : 1,
            'summa' => $amount,
            'content' => $isDepositToAccount
                ? trim("Трансфер с депозита {$depositName} на операционный счет {$accountName}")
                : trim("Трансфер с операционного счета {$accountName} на депозит {$depositName}"),
            'data' => date('d-m-Y'),
            'time' => date('H:i:s'),
            'docum' => $isDepositToAccount ? 'withdraw' : 'topup',
            'oplata' => $isDepositToAccount ? '' : (string) $accountId,
            'oplata2' => $isDepositToAccount ? (string) $accountId : '',
            'money' => (string) $depositId,
            'client1' => '0',
            'client2' => '0',
        ];

        if (in_array('currency_from', $columns, true)) {
            $payload['currency_from'] = $currency;
        }
        if (in_array('provodka', $columns, true)) {
            $payload['provodka'] = $postLedger ? 1 : 0;
        }

        return (int) DB::table('z_document')->insertGetId(
            array_intersect_key($payload, array_flip($columns))
        );
    }

    private function validateDepositTransferPayload(Request $request): array
    {
        return $request->validate([
            'deposit_id' => ['required', 'integer'],
            'operational_account_id' => ['required', 'integer'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'direction' => ['required', Rule::in(['account_to_deposit', 'deposit_to_account'])],
            'post_ledger' => ['sometimes', 'accepted'],
        ]);
    }

    private function depositTransferDocument(int $transfer, array $projectIds, bool $lock = false): ?object
    {
        $query = DB::table('z_document')
            ->where('id', $transfer)
            ->whereIn('firma', array_map('intval', $projectIds))
            ->where('type', 'PP')
            ->whereIn('docum', ['topup', 'withdraw'])
            ->where('status', '!=', '-1');

        if ($lock) {
            $query->lockForUpdate();
        }

        return $query->first();
    }

    private function applyDepositTransferBalances(object $project, array $projectIds, int $depositId, int $accountId, float $amount, string $direction): array
    {
        $deposit = DB::table('conf')
            ->where('id', $depositId)
            ->where('type', 'deposit')
            ->whereIn('firma', array_map('intval', $projectIds))
            ->lockForUpdate()
            ->first();

        $account = DB::table('conf')
            ->where('id', $accountId)
            ->where('type', 'oplata')
            ->where('firma', self::DEPOSIT_TRANSFER_ACCOUNT_FID)
            ->lockForUpdate()
            ->first();

        if (! $deposit || ! $account) {
            throw new \RuntimeException('Депозит или операционный счёт не найден.');
        }

        $depositCurrency = $this->normalizeCurrencyCode($deposit->currency ?? 'UAH');
        $accountCurrency = $this->normalizeCurrencyCode($account->currency ?? 'UAH');
        if ($depositCurrency !== $accountCurrency) {
            throw new \RuntimeException("Валюта депозита {$depositCurrency} не совпадает с валютой счета {$accountCurrency}.");
        }

        $accountBalance = round((float) ($account->value ?? 0), 2);
        $depositBalance = round((float) ($deposit->value ?? 0), 2);
        if ($direction === 'account_to_deposit' && $accountBalance + 0.000001 < $amount) {
            throw new \RuntimeException('Недостаточно средств на операционном счете.');
        }
        if ($direction === 'deposit_to_account' && $depositBalance + 0.000001 < $amount) {
            throw new \RuntimeException('Недостаточно средств на депозите.');
        }

        if ($direction === 'account_to_deposit') {
            DB::table('conf')->where('id', $accountId)->update(['value' => DB::raw('COALESCE(value, 0) - ' . $amount)]);
            DB::table('conf')->where('id', $depositId)->update(['value' => DB::raw('COALESCE(value, 0) + ' . $amount)]);
        } else {
            DB::table('conf')->where('id', $depositId)->update(['value' => DB::raw('COALESCE(value, 0) - ' . $amount)]);
            DB::table('conf')->where('id', $accountId)->update(['value' => DB::raw('COALESCE(value, 0) + ' . $amount)]);
        }

        return [$deposit, $account, $accountCurrency];
    }

    private function reverseDepositTransferBalances(object $project, array $projectIds, int $depositId, int $accountId, float $amount, string $direction): void
    {
        $reverseDirection = $direction === 'account_to_deposit' ? 'deposit_to_account' : 'account_to_deposit';
        $this->applyDepositTransferBalances($project, $projectIds, $depositId, $accountId, $amount, $reverseDirection);
    }

    private function updateDepositTransferDocument(
        int $documentId,
        string $projectId,
        int $depositId,
        int $accountId,
        float $amount,
        string $currency,
        string $accountName,
        string $depositName,
        string $direction,
        bool $postLedger = true
    ): void {
        $columns = Schema::getColumnListing('z_document');
        $isDepositToAccount = $direction === 'deposit_to_account';
        $payload = [
            'summa' => $amount,
            'content' => $isDepositToAccount
                ? trim("Трансфер с депозита {$depositName} на операционный счет {$accountName}")
                : trim("Трансфер с операционного счета {$accountName} на депозит {$depositName}"),
            'data' => date('d-m-Y'),
            'time' => date('H:i:s'),
            'docum' => $isDepositToAccount ? 'withdraw' : 'topup',
            'oplata' => $isDepositToAccount ? '' : (string) $accountId,
            'oplata2' => $isDepositToAccount ? (string) $accountId : '',
            'money' => (string) $depositId,
            'status' => '0',
            'close' => 0,
            'provodka' => $postLedger ? 1 : 0,
        ];
        if (in_array('currency_from', $columns, true)) {
            $payload['currency_from'] = $currency;
        }

        DB::table('z_document')
            ->where('id', $documentId)
            ->where('firma', $projectId)
            ->update(array_intersect_key($payload, array_flip($columns)));
    }

    private function reverseDepositTransferLedger(int $projectId, int $documentId): void
    {
        $document = DB::table('z_document')->where('id', $documentId)->first();
        if ($document) {
            app(AccountingService::class)->createDocumentTransaction(
                'z_document:deposit_operation',
                $documentId,
                'PP',
                $document,
                collect(),
                (string) $projectId,
                true
            );
        }
    }

    private function postDepositTransferLedger(int $projectId, int $documentId): void
    {
        $document = DB::table('z_document')->where('id', $documentId)->first();
        if ($document) {
            $transaction = app(AccountingService::class)->createDocumentTransaction(
                'z_document:deposit_operation',
                $documentId,
                'PP',
                $document,
                collect(),
                (string) $projectId
            );
            if (! $transaction) {
                throw new \RuntimeException('Проводка трансфера не создана.');
            }
        }
    }

    private function bankDepositTransfers(array $projectIds)
    {
        if (! Schema::hasTable('z_document')) {
            return collect();
        }

        return DB::table('z_document as d')
            ->leftJoin('conf as dep', function ($join): void {
                $join->on('dep.id', '=', 'd.money')->where('dep.type', '=', 'deposit');
            })
            ->leftJoin('conf as acc_from', function ($join): void {
                $join->on('acc_from.id', '=', 'd.oplata')->where('acc_from.type', '=', 'oplata');
            })
            ->leftJoin('conf as acc_to', function ($join): void {
                $join->on('acc_to.id', '=', 'd.oplata2')->where('acc_to.type', '=', 'oplata');
            })
            ->whereIn('d.firma', array_map('intval', $projectIds))
            ->where('d.type', 'PP')
            ->whereIn('d.docum', ['topup', 'withdraw'])
            ->where('d.status', '!=', '-1')
            ->orderByRaw("COALESCE(STR_TO_DATE(d.data, '%d-%m-%Y'), d.dt) DESC")
            ->orderByDesc('d.id')
            ->get([
                'd.id',
                'd.num',
                'd.data',
                'd.dt',
                'd.summa',
                'd.currency_from',
                'd.docum',
                'd.money',
                'd.oplata',
                'd.oplata2',
                'd.provodka',
                'd.content',
                'dep.name as deposit_name',
                'dep.currency as deposit_currency',
                'acc_from.name as account_from_name',
                'acc_from.currency as account_from_currency',
                'acc_to.name as account_to_name',
                'acc_to.currency as account_to_currency',
            ])
            ->map(function ($document) {
                $isWithdraw = (string) $document->docum === 'withdraw';
                $direction = $isWithdraw ? 'deposit_to_account' : 'account_to_deposit';
                $accountId = $this->depositTransferAccountId($document, $direction);
                $accountName = $this->depositTransferAccountName($document, $direction);

                return (object) [
                    'id' => (int) $document->id,
                    'number' => trim((string) $document->num) ?: (string) $document->id,
                    'date' => trim((string) $document->data) ?: (string) $document->dt,
                    'direction' => $direction,
                    'direction_label' => $isWithdraw ? 'Депозит → счет' : 'Счет → депозит',
                    'deposit_id' => (string) $document->money,
                    'deposit_name' => trim((string) $document->deposit_name) ?: 'Депозит #' . $document->money,
                    'account_id' => $accountId,
                    'account_name' => trim($accountName) ?: 'Счет #' . $accountId,
                    'amount' => (float) $document->summa,
                    'currency' => $this->normalizeCurrencyCode($document->deposit_currency ?: $document->currency_from ?: 'UAH'),
                    'description' => trim((string) $document->content),
                    'posted' => (int) ($document->provodka ?? 0) === 1,
                    'update_url' => route('bank.deposit.transfer.update', ['transfer' => (int) $document->id]),
                    'reverse_url' => route('bank.deposit.transfer.reverse', ['transfer' => (int) $document->id]),
                    'delete_url' => route('bank.deposit.transfer.destroy', ['transfer' => (int) $document->id]),
                ];
            });
    }

    private function bankDepositOperations(array $projectIds)
    {
        if (! Schema::hasTable('z_document')) {
            return collect();
        }

        $documents = DB::table('z_document as d')
            ->leftJoin('conf as dep', function ($join): void {
                $join->on('dep.id', '=', 'd.money')
                    ->where('dep.type', '=', 'deposit');
            })
            ->leftJoin('project as p', 'p.id', '=', 'd.firma')
            ->leftJoin('users as u', 'u.id', '=', 'd.client2')
            ->leftJoin('conf as acc_from', function ($join): void {
                $join->on('acc_from.id', '=', 'd.oplata')->where('acc_from.type', '=', 'oplata');
            })
            ->leftJoin('conf as acc_to', function ($join): void {
                $join->on('acc_to.id', '=', 'd.oplata2')->where('acc_to.type', '=', 'oplata');
            })
            ->whereIn('d.firma', array_map('intval', $projectIds))
            ->where('d.type', 'PP')
            ->whereIn('d.docum', ['topup', 'withdraw'])
            ->where('d.status', '!=', '-1')
            ->orderByRaw("COALESCE(STR_TO_DATE(d.data, '%d-%m-%Y'), d.dt) DESC")
            ->orderByDesc('d.id')
            ->limit(100)
            ->get([
                'd.id',
                'd.num',
                'd.firma',
                'd.data',
                'd.dt',
                'd.summa',
                'd.currency_from',
                'd.docum',
                'd.money',
                'd.oplata',
                'd.oplata2',
                'd.provodka',
                'd.content',
                'dep.name as deposit_name',
                'dep.currency as deposit_currency',
                'acc_from.name as account_from_name',
                'acc_to.name as account_to_name',
                'p.name as project_name',
                'u.orgname',
                'u.name',
                'u.secondname',
            ]);

        $ledgerByDocument = $this->ledgerByDocument(
            $documents->pluck('id')->map(fn ($id) => (string) $id)->all(),
            $projectIds
        );

        return $documents->map(function ($document) use ($ledgerByDocument) {
            $mode = (string) ($document->docum ?: 'topup');
            $ledger = $ledgerByDocument->get((string) $document->id);
            $status = $this->paymentStatus($document, $ledger);
            $isWithdraw = $mode === 'withdraw';
            $direction = $isWithdraw ? 'deposit_to_account' : 'account_to_deposit';
            $accountId = $this->depositTransferAccountId($document, $direction);
            $accountName = $this->depositTransferAccountName($document, $direction);

            return (object) [
                'id' => (int) $document->id,
                'deposit_id' => (string) $document->money,
                'number' => trim((string) $document->num) ?: (string) $document->id,
                'date' => trim((string) $document->data) ?: (string) $document->dt,
                'mode' => $mode,
                'mode_label' => $mode === 'withdraw' ? 'Вывод' : 'Пополнение',
                'amount' => (float) $document->summa,
                'currency' => $this->normalizeCurrencyCode(
                    $document->deposit_currency ?: $document->currency_from ?: 'UAH'
                ),
                'deposit_name' => trim((string) $document->deposit_name)
                    ?: (trim((string) $document->money) !== '' ? 'Депозит #' . $document->money : 'Не указан'),
                'project_name' => trim((string) $document->project_name) ?: 'Проект #' . $document->firma,
                'owner_name' => trim((string) ($document->orgname ?: implode(' ', array_filter([
                    (string) $document->secondname,
                    (string) $document->name,
                ])))) ?: 'Не указан',
                'description' => trim((string) $document->content),
                'status' => $status,
                'status_label' => $this->paymentStatusLabel($status),
                'ledger_id' => (int) ($ledger->id ?? 0),
                'transfer_direction' => $direction,
                'transfer_deposit_id' => (string) $document->money,
                'transfer_account_id' => $accountId,
                'transfer_account_name' => trim($accountName) ?: 'Счет #' . $accountId,
                'transfer_posted' => (int) ($document->provodka ?? 0) === 1,
                'transfer_update_url' => route('bank.deposit.transfer.update', ['transfer' => (int) $document->id]),
                'transfer_reverse_url' => route('bank.deposit.transfer.reverse', ['transfer' => (int) $document->id]),
                'transfer_delete_url' => route('bank.deposit.transfer.destroy', ['transfer' => (int) $document->id]),
            ];
        });
    }

    private function paymentRows(array $projectIds, array $filters)
    {
        if (! Schema::hasTable('z_document')) {
            return collect();
        }

        $query = DB::table('z_document as d')
            ->leftJoin('users as u', 'u.id', '=', 'd.client1')
            ->leftJoin('project as p', 'p.id', '=', 'd.firma')
            ->leftJoin('conf as cashbox', function ($join): void {
                $join->on('cashbox.id', '=', DB::raw("COALESCE(NULLIF(d.money, ''), NULLIF(d.oplata, ''))"))
                    ->where('cashbox.type', '=', 'oplata');
            })
            ->leftJoin('conf as payment_type', function ($join): void {
                $join->on('payment_type.id', '=', 'd.reestr')
                    ->where('payment_type.type', '=', 'reestr');
            })
            ->whereIn('d.firma', array_map('intval', $projectIds))
            ->whereIn('d.type', ['PO', 'RO', 'PPO', 'PRO']);

        if ($filters['direction'] === 'incoming') {
            $query->whereIn('d.type', ['PO', 'PPO']);
        } elseif ($filters['direction'] === 'outgoing') {
            $query->whereIn('d.type', ['RO', 'PRO']);
        }

        if ($filters['project'] !== '') {
            $query->where('d.firma', (int) $filters['project']);
        }

        if ($filters['date_from'] !== '') {
            $query->whereRaw("COALESCE(STR_TO_DATE(d.data, '%d-%m-%Y'), DATE(d.dt)) >= ?", [$filters['date_from']]);
        }

        if ($filters['date_to'] !== '') {
            $query->whereRaw("COALESCE(STR_TO_DATE(d.data, '%d-%m-%Y'), DATE(d.dt)) <= ?", [$filters['date_to']]);
        }

        $documents = $query
            ->orderByRaw("COALESCE(STR_TO_DATE(d.data, '%d-%m-%Y'), d.dt) DESC")
            ->orderByDesc('d.id')
            ->limit(200)
            ->get([
                'd.id',
                'd.num',
                'd.type',
                'd.firma',
                'd.data',
                'd.time',
                'd.dt',
                'd.summa',
                'd.currency_from',
                'd.content',
                'd.provodka',
                'd.status as document_status',
                'd.client1',
                DB::raw("COALESCE(NULLIF(d.money, ''), NULLIF(d.oplata, '')) as cashbox_id"),
                'p.name as project_name',
                'cashbox.name as cashbox_name',
                'cashbox.currency as cashbox_currency',
                'payment_type.name as payment_type_name',
                'u.orgname',
                'u.name',
                'u.name2',
                'u.secondname',
            ]);

        $ledgerByDocument = $this->ledgerByDocument($documents->pluck('id')->map(fn ($id) => (string) $id)->all(), $projectIds);

        return $documents
            ->map(function ($document) use ($ledgerByDocument) {
                $ledger = $ledgerByDocument->get((string) $document->id);
                $isIncoming = in_array((string) $document->type, ['PO', 'PPO'], true);
                $status = $this->paymentStatus($document, $ledger);

                return (object) [
                    'id' => (int) $document->id,
                    'number' => trim((string) $document->num) ?: (string) $document->id,
                    'type' => (string) $document->type,
                    'direction' => $isIncoming ? 'incoming' : 'outgoing',
                    'direction_label' => $isIncoming ? 'Входящий' : 'Исходящий',
                    'date' => trim((string) $document->data) ?: (string) $document->dt,
                    'amount' => (float) $document->summa,
                    'currency' => trim((string) ($document->cashbox_currency ?: $document->currency_from)) ?: 'UAH',
                    'project_name' => trim((string) $document->project_name) ?: 'Проект #' . $document->firma,
                    'cashbox_name' => trim((string) $document->cashbox_name)
                        ?: (trim((string) $document->cashbox_id) !== '' ? 'Касса #' . $document->cashbox_id : 'Не выбрана'),
                    'payment_type_name' => trim((string) $document->payment_type_name) ?: 'Не указан',
                    'counterparty' => $this->paymentCounterparty($document),
                    'description' => trim((string) $document->content),
                    'status' => $status,
                    'status_label' => $this->paymentStatusLabel($status),
                    'ledger_id' => (int) ($ledger->id ?? 0),
                    'entries_count' => (int) ($ledger->entries_count ?? 0),
                ];
            })
            ->when(
                $filters['status'] !== '',
                fn ($rows) => $rows->where('status', $filters['status'])
            )
            ->values();
    }

    private function ledgerByDocument(array $documentIds, array $projectIds)
    {
        if ($documentIds === [] || ! Schema::hasTable('transactions') || ! Schema::hasTable('entries')) {
            return collect();
        }

        return DB::table('transactions as t')
            ->leftJoin('entries as e', 'e.transaction_id', '=', 't.id')
            ->whereIn('t.company_id', array_map('intval', $projectIds))
            ->whereIn('t.reference_id', $documentIds)
            ->where('t.reference_type', 'like', 'z_document:%')
            ->groupBy('t.id', 't.reference_id', 't.reference_type', 't.date', 't.amount', 't.description')
            ->orderByDesc('t.id')
            ->get([
                't.id',
                't.reference_id',
                't.reference_type',
                't.date',
                't.amount',
                't.description',
                DB::raw('COUNT(e.id) as entries_count'),
                DB::raw('SUM(e.debit) as debit_total'),
                DB::raw('SUM(e.credit) as credit_total'),
            ])
            ->unique('reference_id')
            ->keyBy(fn ($row) => (string) $row->reference_id);
    }

    private function paymentLedgerRows(array $projectIds, array $filters = [])
    {
        if (! Schema::hasTable('transactions') || ! Schema::hasTable('entries') || ! Schema::hasTable('accounts')) {
            return collect();
        }

        $query = DB::table('transactions as t')
            ->join('entries as e', 'e.transaction_id', '=', 't.id')
            ->join('accounts as a', 'a.id', '=', 'e.account_id')
            ->join('z_document as d', function ($join): void {
                $join->on('d.id', '=', DB::raw('CAST(t.reference_id AS UNSIGNED)'));
            })
            ->leftJoin('project as p', 'p.id', '=', 't.company_id')
            ->whereIn('t.company_id', array_map('intval', $projectIds))
            ->where('t.reference_type', 'like', 'z_document:%')
            ->whereIn('d.type', ['PO', 'RO', 'PPO', 'PRO']);

        if (($filters['direction'] ?? '') === 'incoming') {
            $query->whereIn('d.type', ['PO', 'PPO']);
        } elseif (($filters['direction'] ?? '') === 'outgoing') {
            $query->whereIn('d.type', ['RO', 'PRO']);
        }

        if (($filters['project'] ?? '') !== '') {
            $query->where('t.company_id', (int) $filters['project']);
        }

        if (($filters['date_from'] ?? '') !== '') {
            $query->whereDate('t.date', '>=', $filters['date_from']);
        }

        if (($filters['date_to'] ?? '') !== '') {
            $query->whereDate('t.date', '<=', $filters['date_to']);
        }

        return $query
            ->groupBy(
                't.id',
                't.date',
                't.description',
                't.company_id',
                't.reference_type',
                't.reference_id',
                't.currency',
                'p.name'
            )
            ->orderByDesc('t.id')
            ->limit(100)
            ->get([
                't.id',
                't.date',
                't.description',
                't.reference_type',
                't.reference_id',
                't.currency',
                'p.name as project_name',
                DB::raw('SUM(e.debit) as debit_total'),
                DB::raw('SUM(e.credit) as credit_total'),
                DB::raw("GROUP_CONCAT(CASE WHEN e.debit > 0 THEN CONCAT(a.code, ' ', a.name) END ORDER BY e.id SEPARATOR ' / ') as debit_accounts"),
                DB::raw("GROUP_CONCAT(CASE WHEN e.credit > 0 THEN CONCAT(a.code, ' ', a.name) END ORDER BY e.id SEPARATOR ' / ') as credit_accounts"),
                DB::raw('COUNT(e.id) as entries_count'),
            ])
            ->map(function ($row) {
                $row->status = str_ends_with((string) $row->reference_type, ':reversal') ? 'reversed' : 'posted';
                $row->status_label = $this->paymentStatusLabel($row->status);
                $row->project_name = trim((string) $row->project_name) ?: 'Проект';

                return $row;
            })
            ->when(
                ($filters['status'] ?? '') !== '',
                fn ($rows) => $rows->where('status', $filters['status'])
            )
            ->values();
    }

    private function normalizeDateFilter(mixed $value): string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return '';
        }

        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1 ? $value : '';
    }

    private function paymentDateFilter(string $preset, mixed $dateFrom, mixed $dateTo): array
    {
        $allowedPresets = [
            'today',
            'yesterday',
            'week',
            'current_month',
            'previous_month',
            'year',
            'previous_year',
            'manual',
        ];
        $preset = in_array($preset, $allowedPresets, true) ? $preset : 'current_month';
        $today = Carbon::today();

        [$from, $to] = match ($preset) {
            'today' => [$today->copy(), $today->copy()],
            'yesterday' => [$today->copy()->subDay(), $today->copy()->subDay()],
            'week' => [$today->copy()->subDays(6), $today->copy()],
            'previous_month' => [
                $today->copy()->subMonthNoOverflow()->startOfMonth(),
                $today->copy()->subMonthNoOverflow()->endOfMonth(),
            ],
            'year' => [$today->copy()->startOfYear(), $today->copy()],
            'previous_year' => [
                $today->copy()->subYearNoOverflow()->startOfYear(),
                $today->copy()->subYearNoOverflow()->endOfYear(),
            ],
            'manual' => [
                $this->normalizeDateFilter($dateFrom),
                $this->normalizeDateFilter($dateTo),
            ],
            default => [$today->copy()->startOfMonth(), $today->copy()],
        };

        if ($preset === 'manual') {
            if ($from !== '' && $to !== '' && $from > $to) {
                [$from, $to] = [$to, $from];
            }

            return [$preset, $from, $to];
        }

        return [$preset, $from->toDateString(), $to->toDateString()];
    }

    private function paymentStatus(object $document, ?object $ledger): string
    {
        if ($ledger && (
            str_ends_with((string) $ledger->reference_type, ':reversal')
            || str_starts_with(mb_strtolower(trim((string) ($ledger->description ?? ''))), 'сторно')
        )) {
            return 'reversed';
        }
        if ((int) $document->provodka === 1 && $ledger) {
            return 'posted';
        }
        if ((int) $document->provodka === 1) {
            return 'ledger_error';
        }

        return 'pending';
    }

    private function paymentStatusLabel(string $status): string
    {
        return [
            'posted' => 'Проведен',
            'pending' => 'Ожидает проводку',
            'reversed' => 'Проводка отменена',
            'ledger_error' => 'Нет ledger-проводки',
        ][$status] ?? $status;
    }

    private function paymentCounterparty(object $document): string
    {
        $company = trim((string) ($document->orgname ?? ''));
        if ($company !== '') {
            return $company;
        }

        $person = trim(implode(' ', array_filter([
            (string) ($document->secondname ?? ''),
            (string) ($document->name ?? ''),
            (string) ($document->name2 ?? ''),
        ])));

        return $person !== '' ? $person : 'Не указан';
    }

    private function normalizeCashAccount(object $account): object
    {
        $account->balance = (float) ($account->value ?? 0);
        $account->currency = trim((string) ($account->currency ?? '')) ?: $this->currencyFromName((string) ($account->name ?? ''));
        $account->label = trim((string) ($account->name ?? '')) ?: 'Касса #' . (string) ($account->id ?? '');
        $account->doc = trim((string) ($account->doc ?? ''));
        $account->account_type = in_array($account->doc, ['bank', 'personal'], true) ? $account->doc : 'bank';
        $account->account_type_label = $account->account_type === 'personal' ? 'Личный' : 'Банк';
        $account->color = trim((string) ($account->color ?? ''));

        return $account;
    }

    private function clientAccounts(string $fid, $cashAccounts)
    {
        $userColumns = Schema::getColumnListing('users');
        $cacheColumns = Schema::getColumnListing('users_cashe');

        if (! in_array('userid', $cacheColumns, true) || ! in_array('balance', $cacheColumns, true)) {
            return collect();
        }

        $hasCacheFirma = in_array('firma', $cacheColumns, true);
        $hasValuta = in_array('valuta', $cacheColumns, true);
        $select = [
            'uc.id as account_id',
            'uc.userid',
            'uc.balance',
        ];
        $select[] = $hasCacheFirma ? 'uc.firma as account_firma' : DB::raw("'{$fid}' as account_firma");
        $select[] = $hasValuta ? 'uc.valuta' : DB::raw("'UAH' as valuta");

        foreach (['id', 'firma', 'orgname', 'kod1', 'name', 'name2', 'secondname', 'fathername', 'phone', 'email', 'city', 'idstatus', 'ustype', 'created_at'] as $column) {
            if (in_array($column, $userColumns, true)) {
                $select[] = "u.{$column}";
            }
        }

        $cashByCurrency = $cashAccounts->groupBy('currency');

        return DB::table('users_cashe as uc')
            ->leftJoin('users as u', 'u.id', '=', 'uc.userid')
            ->when($hasCacheFirma, function ($query) use ($fid): void {
                $firmaScope = HoldingScope::projectIdsFor($fid);
                if ($firmaScope !== []) {
                    $query->whereIn('uc.firma', array_map('intval', $firmaScope));
                }
            })
            ->when(in_array('orgname', $userColumns, true), fn ($query) => $query->orderBy('u.orgname'))
            ->when(in_array('secondname', $userColumns, true), fn ($query) => $query->orderBy('u.secondname'))
            ->when(in_array('name', $userColumns, true), fn ($query) => $query->orderBy('u.name'))
            ->orderBy('uc.userid')
            ->when($hasValuta, fn ($query) => $query->orderBy('uc.valuta'))
            ->get($select)
            ->map(function ($row) use ($cashByCurrency) {
                $currency = trim((string) ($row->valuta ?? 'UAH')) ?: 'UAH';
                $serviceAccount = optional($cashByCurrency->get($currency, collect())->first())->label;
                $ownerType = $this->ownerType($row);

                return (object) [
                    'account_number' => $this->clientAccountNumber($row, $currency),
                    'account_id' => (int) ($row->account_id ?? 0),
                    'owner_id' => (string) ($row->userid ?? ''),
                    'owner_name' => $this->ownerName($row),
                    'owner_type' => $ownerType,
                    'owner_type_label' => $ownerType === 'company' ? 'Компания' : 'Физлицо',
                    'tax_code' => trim((string) ($row->kod1 ?? '')),
                    'phone' => trim((string) ($row->phone ?? '')),
                    'email' => trim((string) ($row->email ?? '')),
                    'contact' => $this->ownerContact($row),
                    'city' => trim((string) ($row->city ?? '')),
                    'currency' => $currency,
                    'balance' => (float) ($row->balance ?? 0),
                    'status' => ((int) ($row->idstatus ?? $row->ustype ?? 1)) > 0 ? 'Активен' : 'На проверке',
                    'service_account' => $serviceAccount ?: 'Операционный счет не назначен',
                ];
            })
            ->values();
    }

    private function assertProjectInBankScope(int $projectId, Project $bankProject): void
    {
        abort_unless(
            in_array((string) $projectId, HoldingScope::projectIdsFor((string) $bankProject->id), true),
            404
        );
    }

    private function projectAccountHasDocuments(string $accountId): bool
    {
        if (! Schema::hasTable('z_document')) {
            return false;
        }

        return DB::table('z_document')
            ->where(function ($query) use ($accountId): void {
                foreach (['money', 'oplata', 'oplata2'] as $column) {
                    if (Schema::hasColumn('z_document', $column)) {
                        $query->orWhere($column, $accountId);
                    }
                }
            })
            ->exists();
    }

    private function projectAccounts(Project $bankProject)
    {
        $projectColumns = Schema::getColumnListing('project');

        $projects = Project::query()
            ->when(
                in_array('holding_id', $projectColumns, true) && ! empty($bankProject->holding_id),
                fn ($query) => $query->where('holding_id', $bankProject->holding_id),
                fn ($query) => $query->where('id', $bankProject->id)
            )
            ->orderBy('num')
            ->orderBy('name')
            ->get();

        $cashByProject = collect();
        if (Schema::hasTable('conf')) {
            $cashByProject = DB::table('conf')
                ->where('type', 'oplata')
                ->orderBy('name')
                ->get()
                ->map(fn ($account) => $this->normalizeCashAccount($account))
                ->groupBy(fn ($account) => (string) ($account->firma ?? ''));
        }

        return $projects
            ->map(function (Project $project) use ($cashByProject) {
                $accounts = $cashByProject->get((string) $project->id, collect())->values();

                return (object) [
                    'id' => (int) $project->id,
                    'name' => trim((string) ($project->name ?? '')) ?: 'Проект #' . $project->id,
                    'type' => trim((string) ($project->project_type ?? '')) ?: 'project',
                    'holding_name' => trim((string) ($project->holding_name ?? '')),
                    'phone' => trim((string) ($project->phone ?? '')),
                    'email' => trim((string) ($project->email ?? '')),
                    'cash_accounts' => $accounts,
                    'cash_count' => $accounts->count(),
                    'total_by_currency' => $accounts
                        ->groupBy('currency')
                        ->map(fn ($items) => (float) $items->sum('balance')),
                ];
            })
            ->values();
    }

    private function holdingProjects(Project $bankProject)
    {
        if (! Schema::hasTable('project')) {
            return collect();
        }

        $projectColumns = Schema::getColumnListing('project');
        $select = [];
        foreach (['id', 'num', 'name', 'project_type', 'holding_id', 'phone', 'email'] as $column) {
            if (in_array($column, $projectColumns, true)) {
                $select[] = $column;
            }
        }

        if ($select === []) {
            return collect();
        }

        $scope = HoldingScope::projectIdsFor((string) $bankProject->id);

        return DB::table('project')
            ->when($scope !== [], fn ($query) => $query->whereIn('id', array_map('intval', $scope)))
            ->orderBy('num')
            ->orderBy('name')
            ->get($select)
            ->map(function ($project) {
                $role = $this->clearingProjectRole($project);

                return (object) [
                    'id' => (int) ($project->id ?? 0),
                    'name' => trim((string) ($project->name ?? '')) ?: 'Проект #' . (string) ($project->id ?? ''),
                    'type' => trim((string) ($project->project_type ?? '')) ?: 'project',
                    'role' => $role,
                    'role_label' => $this->clearingRoleLabel($role),
                    'email' => trim((string) ($project->email ?? '')),
                    'phone' => trim((string) ($project->phone ?? '')),
                ];
            })
            ->values();
    }

    private function accountMatrix($holdingProjects, Project $bankProject)
    {
        $exchangeProject = $this->projectByRole($holdingProjects, 'exchange') ?: $this->projectObjectFromModel($bankProject, 'exchange');
        $financeProject = $this->projectByRole($holdingProjects, 'finance') ?: $this->projectObjectFromModel($bankProject, 'finance');
        $tradeProject = $this->projectByRole($holdingProjects, 'trade');

        $rows = collect([
            (object) [
                'operation' => 'Покупка AV8 за USDC',
                'source' => 'Blockchain Listener: успешный swap/deposit',
                'debit_project' => $exchangeProject,
                'credit_project' => $financeProject,
                'debit_account' => $this->intercompanyAccountCode('377', $exchangeProject, $financeProject, 'USDC'),
                'credit_account' => $this->intercompanyAccountCode('685', $financeProject, $exchangeProject, 'USDC'),
                'currency' => 'USDC',
                'rule' => 'Дт Проект_Обмен - Кт Проект_Финансы',
            ],
        ]);

        if ($tradeProject) {
            $rows->push((object) [
                'operation' => 'Отгрузка/услуга внутри холдинга',
                'source' => 'ERP/ордер проекта торговли',
                'debit_project' => $tradeProject,
                'credit_project' => $financeProject,
                'debit_account' => $this->intercompanyAccountCode('377', $tradeProject, $financeProject, 'UAH'),
                'credit_account' => $this->intercompanyAccountCode('685', $financeProject, $tradeProject, 'UAH'),
                'currency' => 'UAH',
                'rule' => 'Дт Проект_Торговля - Кт Проект_Финансы',
            ]);
        }

        return $rows;
    }

    private function settlementEvents($holdingProjects, Project $bankProject)
    {
        if (! Schema::hasTable('fund_pool_events')) {
            return collect();
        }

        return DB::table('fund_pool_events')
            ->whereIn('event_type', ['deposit', 'withdraw'])
            ->orderByDesc('event_at')
            ->orderByDesc('id')
            ->limit(80)
            ->get(['id', 'network', 'event_type', 'tx_digest', 'event_seq', 'pool_object_id', 'owner_address', 'amount_usdc', 'pool_shares', 'event_at'])
            ->map(function ($event) {
                $event->amount = $this->usdcAmount($event->amount_usdc ?? '0');
                return $event;
            })
            ->filter(fn ($event) => (float) $event->amount > 0)
            ->values();
    }

    private function settlementRow(object $event, $holdingProjects, Project $bankProject): object
    {
        $exchangeProject = $this->projectByRole($holdingProjects, 'exchange') ?: $this->projectObjectFromModel($bankProject, 'exchange');
        $financeProject = $this->projectByRole($holdingProjects, 'finance') ?: $this->projectObjectFromModel($bankProject, 'finance');
        $isWithdraw = (string) ($event->event_type ?? '') === 'withdraw';
        $debitProject = $isWithdraw ? $financeProject : $exchangeProject;
        $creditProject = $isWithdraw ? $exchangeProject : $financeProject;
        $debitPrefix = $isWithdraw ? '685' : '377';
        $creditPrefix = $isWithdraw ? '377' : '685';

        return (object) [
            'event_id' => (int) ($event->id ?? 0),
            'event_type' => (string) ($event->event_type ?? ''),
            'event_label' => $isWithdraw ? 'Закрытие/вывод' : 'Покупка AV8 за USDC',
            'event_at' => (string) ($event->event_at ?? ''),
            'tx_digest' => (string) ($event->tx_digest ?? ''),
            'network' => (string) ($event->network ?? ''),
            'pool_object_id' => (string) ($event->pool_object_id ?? ''),
            'owner_address' => (string) ($event->owner_address ?? ''),
            'amount' => (float) ($event->amount ?? 0),
            'currency' => 'USDC',
            'debit_project' => $debitProject,
            'credit_project' => $creditProject,
            'debit_account' => $this->intercompanyAccountCode($debitPrefix, $debitProject, $creditProject, 'USDC'),
            'credit_account' => $this->intercompanyAccountCode($creditPrefix, $creditProject, $debitProject, 'USDC'),
            'status' => 'Готово к двойной записи',
        ];
    }

    private function intercompanyDebtRows($settlementRows)
    {
        return $settlementRows
            ->groupBy(fn ($row) => $row->debit_project->id . ':' . $row->credit_project->id . ':' . $row->currency)
            ->map(function ($rows) {
                $first = $rows->first();

                return (object) [
                    'debtor' => $first->debit_project,
                    'creditor' => $first->credit_project,
                    'currency' => $first->currency,
                    'amount' => (float) $rows->sum('amount'),
                    'events_count' => $rows->count(),
                    'last_event_at' => (string) ($rows->max('event_at') ?? ''),
                ];
            })
            ->sortByDesc('amount')
            ->values();
    }

    private function clearingProjectRole(object $project): string
    {
        $type = mb_strtolower(trim((string) ($project->project_type ?? '')));
        $name = mb_strtolower(trim((string) ($project->name ?? '')));

        if (str_contains($name, 'обмен') || str_contains($name, 'exchange') || str_contains($name, 'swap')) {
            return 'exchange';
        }
        if ($type === 'bank' || str_contains($name, 'финанс') || str_contains($name, 'finance')) {
            return 'finance';
        }
        if ($type === 'trade' || str_contains($name, 'торг') || str_contains($name, 'trade')) {
            return 'trade';
        }

        return 'holding';
    }

    private function clearingRoleLabel(string $role): string
    {
        return [
            'exchange' => 'Проект обмена',
            'finance' => 'Проект финансов',
            'trade' => 'Проект торговли',
            'holding' => 'Проект холдинга',
        ][$role] ?? 'Проект холдинга';
    }

    private function projectByRole($projects, string $role): ?object
    {
        return $projects->first(fn ($project) => $project->role === $role);
    }

    private function projectObjectFromModel(Project $project, string $role): object
    {
        return (object) [
            'id' => (int) $project->id,
            'name' => trim((string) ($project->name ?? '')) ?: 'Проект #' . $project->id,
            'type' => trim((string) ($project->project_type ?? '')) ?: 'project',
            'role' => $role,
            'role_label' => $this->clearingRoleLabel($role),
        ];
    }

    private function intercompanyAccountCode(string $prefix, object $project, object $counterparty, string $currency): string
    {
        return $prefix . '.' . (string) $project->id . '.' . (string) $counterparty->id . '.' . mb_strtoupper($currency);
    }

    private function usdcAmount(mixed $value): float
    {
        $value = trim((string) $value);
        if ($value === '') {
            return 0.0;
        }

        if (str_contains($value, '.')) {
            return (float) $value;
        }

        return is_numeric($value) ? ((float) $value / 1000000) : 0.0;
    }

    private function exchangeSettings(): object
    {
        $defaults = (object) [
            'rate_usdc' => 1.0,
            'fee_percent' => 0.35,
            'price_impact_percent' => 0.0,
            'min_buy_usdc' => 0.0,
            'max_buy_usdc' => 0.0,
            'quote_ttl_seconds' => 30,
            'mint_paused' => false,
            'redeem_paused' => false,
            'pricing_model' => 'manual',
            'updated_at' => '',
        ];

        if (! Schema::hasTable('fund_share_settings')) {
            return $defaults;
        }

        $row = DB::table('fund_share_settings')
            ->orderByDesc('updated_at')
            ->first();

        if (! $row) {
            return $defaults;
        }

        return (object) [
            'rate_usdc' => $this->storedUsdcToDecimal($row->current_price_usdc ?? '0') ?: $defaults->rate_usdc,
            'fee_percent' => ((float) ($row->mint_fee_bps ?? 35)) / 100,
            'price_impact_percent' => ((float) ($row->price_impact_bps ?? 0)) / 100,
            'min_buy_usdc' => $this->storedUsdcToDecimal($row->min_buy_usdc ?? '0'),
            'max_buy_usdc' => $this->storedUsdcToDecimal($row->max_buy_usdc ?? '0'),
            'quote_ttl_seconds' => (int) ($row->quote_ttl_seconds ?? 30),
            'mint_paused' => (bool) ($row->mint_paused ?? false),
            'redeem_paused' => (bool) ($row->redeem_paused ?? false),
            'pricing_model' => trim((string) ($row->pricing_model ?? 'manual')),
            'updated_at' => (string) ($row->updated_at ?? ''),
        ];
    }

    private function swapOrders(string $fid)
    {
        if (! Schema::hasTable('av8_swap_orders')) {
            return collect();
        }

        return DB::table('av8_swap_orders')
            ->when($fid !== '', fn ($query) => $query->where(function ($nested) use ($fid): void {
                $nested->where('fid', (int) $fid)
                    ->orWhere('fid', 0);
            }))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(100)
            ->get();
    }

    private function blockchainExchangeEvents()
    {
        if (! Schema::hasTable('fund_pool_events')) {
            return collect();
        }

        return DB::table('fund_pool_events')
            ->whereIn('event_type', ['deposit', 'withdraw'])
            ->orderByDesc('event_at')
            ->orderByDesc('id')
            ->limit(30)
            ->get(['event_type', 'network', 'tx_digest', 'owner_address', 'amount_usdc', 'pool_shares', 'event_at'])
            ->map(function ($event) {
                $event->amount = $this->storedUsdcToDecimal($event->amount_usdc ?? '0');
                $txDigest = trim((string) ($event->tx_digest ?? ''));
                $event->tx_digest_short = $txDigest !== '' && mb_strlen($txDigest) > 18
                    ? mb_substr($txDigest, 0, 10) . '...' . mb_substr($txDigest, -6)
                    : $txDigest;
                $event->tx_explorer_url = $txDigest !== ''
                    ? 'https://suiexplorer.com/txblock/' . rawurlencode($txDigest) . '?network=mainnet'
                    : '';

                return $event;
            });
    }

    private function storedUsdcToDecimal(mixed $value): float
    {
        $value = trim((string) $value);
        if ($value === '') {
            return 0.0;
        }

        if (str_contains($value, '.')) {
            return (float) $value;
        }

        return is_numeric($value) ? ((float) $value / 1000000) : 0.0;
    }

    private function personOwners(string $fid, $clientAccounts)
    {
        if (! Schema::hasTable('users')) {
            return collect();
        }

        $userColumns = Schema::getColumnListing('users');
        $select = [];
        foreach (['id', 'firma', 'orgname', 'kod1', 'name', 'name2', 'secondname', 'fathername', 'phone', 'email', 'city', 'idstatus', 'ustype'] as $column) {
            if (in_array($column, $userColumns, true)) {
                $select[] = $column;
            }
        }

        if ($select === []) {
            return collect();
        }

        $accountsByUser = $clientAccounts
            ->filter(fn ($account) => $account->owner_type === 'person')
            ->groupBy('owner_id');
        $googleWalletsByUser = $this->googleWalletsByUser();

        return DB::table('users')
            ->when(in_array('firma', $userColumns, true), function ($query) use ($fid): void {
                $firmaScope = HoldingScope::projectIdsFor($fid);
                if ($firmaScope !== []) {
                    $query->whereIn('firma', $firmaScope);
                }
            })
            ->when(in_array('orgname', $userColumns, true), function ($query): void {
                $query->where(function ($nested): void {
                    $nested->whereNull('orgname')
                        ->orWhere('orgname', '');
                });
            })
            ->when(in_array('kod1', $userColumns, true), function ($query): void {
                $query->where(function ($nested): void {
                    $nested->whereNull('kod1')
                        ->orWhere('kod1', '');
                });
            })
            ->when(in_array('secondname', $userColumns, true), fn ($query) => $query->orderBy('secondname'))
            ->when(in_array('name', $userColumns, true), fn ($query) => $query->orderBy('name'))
            ->orderBy('id')
            ->get($select)
            ->map(function ($user) use ($accountsByUser, $googleWalletsByUser) {
                $accounts = $accountsByUser->get((string) ($user->id ?? ''), collect())->values();
                $wallets = $googleWalletsByUser->get((string) ($user->id ?? ''), collect())->values();
                $ownerName = $this->ownerName($user);
                $contact = $this->ownerContact($user);
                $phone = trim((string) ($user->phone ?? ''));
                $email = trim((string) ($user->email ?? ''));
                $city = trim((string) ($user->city ?? ''));

                return (object) [
                    'owner_id' => (string) ($user->id ?? ''),
                    'owner_name' => $ownerName,
                    'contact' => $contact,
                    'city' => $city,
                    'status' => ((int) ($user->idstatus ?? $user->ustype ?? 1)) > 0 ? 'Активен' : 'На проверке',
                    'accounts' => $accounts,
                    'accounts_count' => $accounts->count(),
                    'google_wallets' => $wallets,
                    'google_wallets_count' => $wallets->count(),
                    'search_text' => mb_strtolower(implode(' ', [
                        $ownerName,
                        $phone,
                        $email,
                        $contact,
                        $city,
                        $wallets->pluck('address')->implode(' '),
                    ])),
                    'total_by_currency' => $accounts
                        ->groupBy('currency')
                        ->map(fn ($items) => (float) $items->sum('balance')),
                ];
            })
            ->values();
    }

    private function googleWalletsByUser()
    {
        $wallets = collect();

        if (Schema::hasTable('zklogin_identities')) {
            $wallets = $wallets->merge(
                DB::table('zklogin_identities')
                    ->where('provider', 'google')
                    ->whereNotNull('wallet_address')
                    ->where('wallet_address', '!=', '')
                    ->orderByDesc('updated_at')
                    ->get(['user_id', 'wallet_address', 'updated_at'])
                    ->map(fn ($row) => (object) [
                        'user_id' => (string) $row->user_id,
                        'address' => (string) $row->wallet_address,
                        'network' => 'sui',
                        'source' => 'Google zkLogin',
                        'connected_at' => $row->updated_at,
                    ])
            );
        }

        if (Schema::hasTable('user_wallets') && Schema::hasColumn('user_wallets', 'web3auth')) {
            $wallets = $wallets->merge(
                DB::table('user_wallets')
                    ->where('web3auth', 1)
                    ->orderByDesc('connected_at')
                    ->orderByDesc('id')
                    ->get(['user_id', 'address', 'network', 'connected_at'])
                    ->map(fn ($row) => (object) [
                        'user_id' => (string) $row->user_id,
                        'address' => (string) $row->address,
                        'network' => (string) ($row->network ?? ''),
                        'source' => 'Google Web3Auth',
                        'connected_at' => $row->connected_at,
                    ])
            );
        }

        return $wallets
            ->filter(fn ($wallet) => trim((string) $wallet->address) !== '')
            ->unique(fn ($wallet) => $wallet->user_id . ':' . mb_strtolower((string) $wallet->address))
            ->groupBy('user_id');
    }

    private function emailWalletBindings(string $fid)
    {
        if (! Schema::hasTable('users')) {
            return collect();
        }

        $userColumns = Schema::getColumnListing('users');
        $select = [];
        foreach (['id', 'firma', 'orgname', 'kod1', 'name', 'name2', 'secondname', 'fathername', 'phone', 'email'] as $column) {
            if (in_array($column, $userColumns, true)) {
                $select[] = $column;
            }
        }

        if ($select === []) {
            return collect();
        }

        $users = DB::table('users')
            ->when(in_array('firma', $userColumns, true), function ($query) use ($fid): void {
                $firmaScope = HoldingScope::projectIdsFor($fid);
                if ($firmaScope !== []) {
                    $query->whereIn('firma', $firmaScope);
                }
            })
            ->get($select)
            ->keyBy(fn ($user) => (string) ($user->id ?? ''));

        if ($users->isEmpty()) {
            return collect();
        }

        $wallets = $this->walletRowsForUsers($users->keys()->all());
        $tokensByAddress = $this->walletTokensByAddress($wallets->pluck('address')->all());

        return $wallets
            ->filter(fn ($wallet) => $users->has((string) $wallet->user_id))
            ->map(function ($wallet) use ($users, $tokensByAddress) {
                $user = $users->get((string) $wallet->user_id);
                $addressKey = mb_strtolower(trim((string) $wallet->address));
                $tokens = $tokensByAddress->get($addressKey, collect())->values();

                return (object) [
                    'user_id' => (string) $wallet->user_id,
                    'email' => trim((string) ($user->email ?? '')),
                    'owner_name' => $this->ownerName($user),
                    'address' => trim((string) $wallet->address),
                    'network' => trim((string) ($wallet->network ?? '')),
                    'source' => trim((string) ($wallet->source ?? 'Криптокошелек')),
                    'connected_at' => $wallet->connected_at ?? null,
                    'tokens' => $tokens,
                    'token_count' => $tokens->count(),
                    'token_total_usd' => (float) $tokens->sum('value_usd'),
                ];
            })
            ->sortBy(fn ($row) => mb_strtolower($row->email . ' ' . $row->address))
            ->values();
    }

    private function walletRowsForUsers(array $userIds)
    {
        $userIds = collect($userIds)
            ->map(fn ($id) => (string) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($userIds === []) {
            return collect();
        }

        $wallets = collect();

        if (Schema::hasTable('user_wallets')) {
            $columns = Schema::getColumnListing('user_wallets');
            if (in_array('user_id', $columns, true) && in_array('address', $columns, true)) {
                $select = ['user_id', 'address'];
                $select[] = in_array('network', $columns, true) ? 'network' : DB::raw("'' as network");
                $select[] = in_array('connected_at', $columns, true) ? 'connected_at' : DB::raw('NULL as connected_at');
                $select[] = in_array('web3auth', $columns, true) ? 'web3auth' : DB::raw('0 as web3auth');

                $wallets = $wallets->merge(
                    DB::table('user_wallets')
                        ->whereIn('user_id', $userIds)
                        ->whereNotNull('address')
                        ->where('address', '!=', '')
                        ->when(in_array('connected_at', $columns, true), fn ($query) => $query->orderByDesc('connected_at'))
                        ->get($select)
                        ->map(fn ($row) => (object) [
                            'user_id' => (string) $row->user_id,
                            'address' => (string) $row->address,
                            'network' => (string) ($row->network ?? ''),
                            'source' => ((int) ($row->web3auth ?? 0)) === 1 ? 'Google Web3Auth' : 'User wallet',
                            'connected_at' => $row->connected_at,
                        ])
                );
            }
        }

        if (Schema::hasTable('zklogin_identities')) {
            $wallets = $wallets->merge(
                DB::table('zklogin_identities')
                    ->whereIn('user_id', $userIds)
                    ->where('provider', 'google')
                    ->whereNotNull('wallet_address')
                    ->where('wallet_address', '!=', '')
                    ->orderByDesc('updated_at')
                    ->get(['user_id', 'wallet_address', 'updated_at'])
                    ->map(fn ($row) => (object) [
                        'user_id' => (string) $row->user_id,
                        'address' => (string) $row->wallet_address,
                        'network' => 'sui',
                        'source' => 'Google zkLogin',
                        'connected_at' => $row->updated_at,
                    ])
            );
        }

        return $wallets
            ->filter(fn ($wallet) => trim((string) $wallet->address) !== '')
            ->unique(fn ($wallet) => $wallet->user_id . ':' . mb_strtolower((string) $wallet->address))
            ->values();
    }

    private function walletTokensByAddress(array $addresses)
    {
        if (! Schema::hasTable('wallets') || ! Schema::hasTable('wallet_tokens')) {
            return collect();
        }

        $addresses = collect($addresses)
            ->map(fn ($address) => mb_strtolower(trim((string) $address)))
            ->filter()
            ->unique()
            ->values();

        if ($addresses->isEmpty()) {
            return collect();
        }

        $walletRows = DB::table('wallets')
            ->whereIn(DB::raw('LOWER(address)'), $addresses->all())
            ->get(['id', 'address']);

        if ($walletRows->isEmpty()) {
            return collect();
        }

        $addressByWalletId = $walletRows->mapWithKeys(fn ($wallet) => [
            (int) $wallet->id => mb_strtolower((string) $wallet->address),
        ]);

        $tokens = DB::table('wallet_tokens')
            ->whereIn('wallet_id', $walletRows->pluck('id')->all())
            ->when(Schema::hasColumn('wallet_tokens', 'is_spam'), fn ($query) => $query->where('is_spam', 0))
            ->orderBy('chain')
            ->orderBy('symbol')
            ->get(['wallet_id', 'chain', 'token_address', 'symbol', 'name', 'balance', 'value_usd'])
            ->map(function ($token) use ($addressByWalletId) {
                $token->address_key = $addressByWalletId->get((int) $token->wallet_id, '');
                $token->symbol = trim((string) ($token->symbol ?? '')) ?: 'TOKEN';
                $token->name = trim((string) ($token->name ?? ''));
                $token->chain = trim((string) ($token->chain ?? ''));
                $token->balance = (string) ($token->balance ?? '0');
                $token->value_usd = (float) ($token->value_usd ?? 0);
                $token->token_address = trim((string) ($token->token_address ?? ''));

                return $token;
            });

        return $tokens
            ->filter(fn ($token) => $token->address_key !== '')
            ->groupBy('address_key');
    }

    private function ownerType(object $row): string
    {
        return trim((string) ($row->orgname ?? '')) !== '' || trim((string) ($row->kod1 ?? '')) !== ''
            ? 'company'
            : 'person';
    }

    private function ownerName(object $row): string
    {
        $orgName = trim((string) ($row->orgname ?? ''));
        if ($orgName !== '') {
            return $orgName;
        }

        $parts = array_filter([
            trim((string) ($row->secondname ?? '')),
            trim((string) ($row->name ?? $row->name2 ?? '')),
            trim((string) ($row->fathername ?? '')),
        ]);

        return $parts !== [] ? implode(' ', $parts) : 'Клиент #' . (string) ($row->userid ?? $row->id ?? '');
    }

    private function ownerContact(object $row): string
    {
        $email = trim((string) ($row->email ?? ''));
        $phone = trim((string) ($row->phone ?? ''));

        if ($email !== '' && $phone !== '') {
            return "{$phone} · {$email}";
        }

        return $phone !== '' ? $phone : ($email !== '' ? $email : '—');
    }

    private function clientAccountNumber(object $row, string $currency): string
    {
        return 'AV8-' . str_pad((string) ($row->account_firma ?? '0'), 4, '0', STR_PAD_LEFT)
            . '-' . str_pad((string) ($row->userid ?? '0'), 6, '0', STR_PAD_LEFT)
            . '-' . $currency;
    }

    private function currencyFromName(string $name): string
    {
        $upper = mb_strtoupper($name);
        foreach (['USDC', 'USDT', 'USD', 'EUR', 'UAH', 'AV8', 'SUI'] as $currency) {
            if (str_contains($upper, $currency)) {
                return $currency;
            }
        }

        return 'UAH';
    }

    private function normalizeCurrencyCode(mixed $currency): string
    {
        $normalized = mb_strtoupper(trim((string) $currency));
        $normalized = preg_replace('/[^A-Z0-9]/', '', $normalized) ?: '';

        return $normalized !== '' ? $normalized : 'UAH';
    }
}
