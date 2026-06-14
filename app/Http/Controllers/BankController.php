<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Support\HoldingScope;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BankController extends Controller
{
    private const EXCHANGE_ORDER_STATUSES = [
        'new' => 'Новая',
        'awaiting_payment' => 'Ожидает оплату',
        'paid' => 'Оплачена',
        'processing' => 'В обработке',
        'completed' => 'Выполнена',
        'cancelled' => 'Отменена',
        'failed' => 'Ошибка',
    ];

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
        $operationalTotalByCurrency = $cashAccounts
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
            'emailWalletBindings' => $this->emailWalletBindings((string) $project->id),
            'totalByCurrency' => $totalByCurrency,
            'operationalTotalByCurrency' => $operationalTotalByCurrency,
            'ownerTypeTotals' => $ownerTypeTotals,
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

        return view('bank.deposit', [
            'project' => $project,
            'deposits' => $deposits,
            'operations' => $operations,
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

    public function exchange(): View
    {
        $project = $this->bankProject();

        return view('bank.exchange', [
            'project' => $project,
            'exchangeSettings' => $this->exchangeSettings(),
            'swapOrders' => $this->swapOrders((string) $project->id),
            'exchangeOrderStatuses' => self::EXCHANGE_ORDER_STATUSES,
            'blockchainExchangeEvents' => $this->blockchainExchangeEvents(),
        ]);
    }

    public function invest(): View
    {
        $project = $this->bankProject();
        $projectIds = HoldingScope::projectIdsFor((string) $project->id);
        $deposits = $this->bankDeposits($projectIds);
        $pools = $this->investmentPools();
        $poolEvents = $this->investmentPoolEvents();
        $walletPortfolio = $this->googleAccountWalletPortfolio();
        $tokenRows = $this->tokenManifestRows($walletPortfolio['tokens']);
        $hiddenTokenRows = $this->tokenManifestRows($walletPortfolio['tokens'], true)
            ->filter(fn ($token) => (bool) ($token->manifest_hidden ?? false))
            ->values();
        $assetManifestSettings = $this->assetManifestSettings((int) $project->id);
        $portfolioRows = $this->investmentPortfolioRows($deposits, $pools, $assetManifestSettings);
        $assetManifestRows = $this->assetManifestRows($portfolioRows);
        $assetManifestHiddenRows = $this->assetManifestRows($portfolioRows, true)
            ->filter(fn ($row) => (bool) ($row->manifest_hidden ?? false))
            ->values();
        $portfolioTotal = (float) $portfolioRows->sum('value_usd');
        $liquidTotal = (float) $portfolioRows->where('group', 'liquid')->sum('value_usd');
        $defiTotal = (float) $portfolioRows->where('group', 'defi')->sum('value_usd');
        $walletTokensTotal = (float) $walletPortfolio['tokens']->sum('value_usd');
        $walletDefiTotal = (float) $walletPortfolio['defiPositions']->sum('value_usd');
        $walletNftTotal = (float) $walletPortfolio['nfts']->sum('value_usd');

        return view('bank.invest', [
            'project' => $project,
            'portfolioRows' => $portfolioRows,
            'assetManifestRows' => $assetManifestRows,
            'assetManifestHiddenRows' => $assetManifestHiddenRows,
            'tokenRows' => $tokenRows,
            'hiddenTokenRows' => $hiddenTokenRows,
            'pools' => $pools,
            'poolEvents' => $poolEvents,
            'walletPortfolio' => $walletPortfolio,
            'summary' => [
                'nav' => $portfolioTotal,
                'liquid' => $liquidTotal,
                'defi' => $defiTotal,
                'wallet_tokens' => $walletTokensTotal,
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
            ->with('success', 'Статус заявки обновлен.');
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
                    'symbol' => (string) ($pool->symbol ?? $this->symbolFromCoinType((string) ($pool->coin_type ?? ''))),
                    'coin_type' => (string) ($pool->coin_type ?? ''),
                    'pool_object_id' => $poolObjectId,
                    'pool_object_short' => $this->shortHash($poolObjectId),
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
                'p.name as project_name',
            ])
            ->map(function ($deposit) {
                $status = (int) ($deposit->status ?? 0);

                return (object) [
                    'id' => (string) $deposit->id,
                    'name' => trim((string) $deposit->name) ?: 'Депозит #' . $deposit->id,
                    'project_name' => trim((string) $deposit->project_name) ?: 'Проект #' . $deposit->firma,
                    'balance' => (float) ($deposit->value ?? 0),
                    'limit' => (float) ($deposit->value1 ?? 0),
                    'currency' => $this->normalizeCurrencyCode($deposit->currency ?? 'UAH'),
                    'is_active' => $status === 1,
                    'status_label' => $status === 1 ? 'Активен' : ($status === 3 ? 'Закрыт' : 'На проверке'),
                    'is_visible' => (string) ($deposit->vision ?? '1') !== '0',
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
            ->whereIn('d.firma', array_map('intval', $projectIds))
            ->where('d.type', 'PP')
            ->whereIn('d.docum', ['topup', 'withdraw'])
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
                'd.provodka',
                'd.content',
                'dep.name as deposit_name',
                'dep.currency as deposit_currency',
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
