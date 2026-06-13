<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Support\HoldingScope;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class BankController extends Controller
{
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

    public function loans(): View
    {
        return $this->placeholder('Внутренние кредиты', 'Учет займов между проектами холдинга, графиков погашения и начисленных процентов.');
    }

    public function exchange(): View
    {
        return $this->placeholder('Обмен фиат/крипта', 'Операции обмена между фиатными кассами и крипто-кошельками с курсом, комиссией и проводками.');
    }

    public function clearing(): View
    {
        return $this->placeholder('Клиринг проектов', 'Взаимозачеты, долги и требования между проектами холдинга.');
    }

    public function payments(): View
    {
        return $this->placeholder('Платежи', 'Исходящие и входящие платежи через кассы-кошельки банка.');
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
}
