<?php

namespace App\Http\Controllers;

use App\Models\BannerCarousel;
use App\Models\Account;
use App\Models\Filter;
use App\Models\Firma;
use App\Models\News;
use App\Models\Project;
use App\Models\Settings;
use App\Services\AutoAgentSitemapBuildService;
use App\Services\ZerionWalletService;
use App\Services\SitemapService;
use App\Support\MediaUrl;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use App\Models\Conf;
use App\Http\Middleware\SyncLegacySessionFromAuth;

/**
 * SettingsController — migrated from admin/ module (idstatus >= 3)
 */
class SettingsController extends Controller
{
    public function index(Request $request)
    {
        $fid = session('fid', '');
        $user = $this->currentUser();

        $data = Settings::init($fid);

        $projects = Schema::hasTable('project')
            ? Project::query()
                ->orderBy('num')
                ->orderBy('name')
                ->get()
            : collect();

        // Statuses — conf where type='status'
        $statuses = DB::table('conf')->where('type', 'status')->where('firma', $fid)->orderBy('name')->get();

        // Reestr (payment types) — conf where type='reestr'
        $reestrs = DB::table('conf')->where('type', 'reestr')->where('firma', $fid)->orderBy('name')->get()
            ->map(fn ($item) => Conf::decoratePaymentType($item));

        // Client types — той самий набір, що Conf::tgroupsForFirma (форма товару / price)
        $tgroups = Conf::tgroupsForFirma($fid);

        // Counterparty types — conf where type='tclient'
        $tclients = DB::table('conf')->where('type', 'tclient')->where('firma', $fid)->orderBy('name')->get();
        $currentCounterpartyType = null;
        if ($user) {
            $counterpartyTypeId = (int) ($user->idstatus ?: $user->ustype ?: 0);
            if ($counterpartyTypeId > 0) {
                $currentCounterpartyType = $tclients->firstWhere('id', $counterpartyTypeId);
            }
        }

        // Каса — conf where type='oplata'
        $oplatas = DB::table('conf')->where('type', 'oplata')->where('firma', $fid)->orderBy('name')->get();

        // Валюты — conf where type='currency'
        $currencies = DB::table('conf')->where('type', 'currency')->where('firma', $fid)->orderBy('name')->get();

        // Офисы — conf where type='sklads'
        $sklads = DB::table('conf')->where('type', 'sklads')->where('firma', $fid)->orderBy('name')->get();

        // Депозиты — conf where type='deposit'
        $deposits = DB::table('conf')->where('type', 'deposit')->where('firma', $fid)->orderBy('name')->get();

        $myCompanies = collect();
        if ($user) {
            $myCompanies = $this->companiesQuery($user)->orderBy('id')->get();
        }

        $userWallets = collect();
        if ($user && Schema::hasTable('user_wallets')) {
            $userWallets = DB::table('user_wallets')
                ->where('user_id', $user->id)
                ->orderByDesc('connected_at')
                ->orderByDesc('id')
                ->get();
        } elseif ($user && !empty($user->wallet_address)) {
            $userWallets = collect([(object) [
                'address' => $user->wallet_address,
                'network' => $user->wallet_network,
                'connected_at' => $user->wallet_connected_at,
                'web3auth' => 0,
            ]]);
        }
        $profileBalances = $this->parseUserBalance($user && Schema::hasColumn('users', 'balance') ? ($user->balance ?? '') : '');

        $fieldCatalogTopCount = 0;
        $fieldCityCount = 0;
        if (Schema::hasTable('field')) {
            $fieldCatalogTopCount = $this->fieldBaseQuery($fid, 'catalog')
                ->where(function ($query) {
                    $query->where('idkeyfield', '0')
                        ->orWhere('idkeyfield', 0)
                        ->orWhereNull('idkeyfield')
                        ->orWhere('idkeyfield', '');
                })
                ->count();

            $fieldCityCount = $this->fieldBaseQuery($fid, 'city')->count();
        }

        $bannerCarouselCount = Schema::hasTable('banner_carousels')
            ? (int) DB::table('banner_carousels')->where('firma', $fid)->count()
            : 0;

        $knowledgeBaseCount = Schema::hasTable('ai_knowledge_base')
            ? (int) DB::table('ai_knowledge_base')
                ->where('fid', (int) $fid)
                ->where('active', true)
                ->count()
            : 0;
        $accountsCount = Schema::hasTable('accounts')
            ? (int) Account::query()->count()
            : 0;
        $sitemapService = app(SitemapService::class);
        $sitemapInfo = [
            'public_url' => $sitemapService->getPublicUrl($fid !== '' ? (int) $fid : null),
            'exists' => $sitemapService->exists($fid !== '' ? (int) $fid : null),
            'last_modified_at' => $sitemapService->lastModifiedAt($fid !== '' ? (int) $fid : null),
        ];

        $fieldTranslationsCount = $fieldCatalogTopCount + $fieldCityCount;
        $catalogNewsOptions = Schema::hasTable('news')
            ? DB::table('news')
                ->where(function ($query) use ($fid) {
                    $query->where('firma', (int) $fid)
                        ->orWhere('firma', 0);
                })
                ->orderByDesc('hot')
                ->orderByDesc('id')
                ->limit(500)
                ->get()
                ->map(function ($item) {
                    $title = News::decorateTitle($item);

                    return [
                        'id' => (int) $item->id,
                        'title' => $title !== '' ? $title : ('Новина #' . $item->id),
                    ];
                })
                ->values()
            : collect();

        $catalogFiltersGroupCount = 0;
        if (Schema::hasTable('filter') && Schema::hasTable('field')) {
            $catalogIds = $this->fieldFilterCatalogBaseQuery($fid)->pluck('id');
            if ($catalogIds->isNotEmpty()) {
                $catalogFiltersGroupCount = (int) Filter::query()
                    ->where('keyfield', 'filter')
                    ->where('idfilter', 0)
                    ->whereIn('idkeyfield', $catalogIds)
                    ->count();
            }
        }

        return view('settings.index', array_merge($data, compact('fid', 'projects', 'statuses', 'reestrs', 'tgroups', 'tclients', 'oplatas', 'currencies', 'sklads', 'deposits', 'user', 'myCompanies', 'fieldCatalogTopCount', 'fieldCityCount', 'fieldTranslationsCount', 'currentCounterpartyType', 'userWallets', 'profileBalances', 'bannerCarouselCount', 'knowledgeBaseCount', 'accountsCount', 'sitemapInfo', 'catalogNewsOptions', 'catalogFiltersGroupCount')));
    }

    public function show(Request $request)
    {
        $id = $request->input('id', '');
        $fid = session('fid', '');
        
        $setting = null;
        if ($id) {
            $setting = \Illuminate\Support\Facades\DB::table('conf')->where('id', $id)->where('firma', $fid)->first();
        } else {
            $setting = (object)[];
        }

        return view('settings.show', compact('setting', 'fid'));
    }

    public function switchProject(Request $request)
    {
        $request->validate([
            'fid' => 'required|integer|min:1',
        ]);

        if (!Schema::hasTable('project')) {
            return redirect()->back()->with('error', 'Таблицю project не знайдено');
        }

        $fid = (int) $request->input('fid');
        $project = Project::query()->find($fid);

        if (!$project) {
            return redirect()->back()->with('error', 'Проєкт не знайдено');
        }

        if (Auth::check() && ! $this->canSwitchToProject(Auth::user(), $project)) {
            abort(403, 'Немає доступу до цього проєкту');
        }

        $this->resetWorkspaceSessionState($request);
        session(['fid' => $project->id]);

        if (Auth::check()) {
            $authUser = Auth::user();
            if ($authUser instanceof User) {
                SyncLegacySessionFromAuth::applyWorkspaceSession($authUser);
            }
        }

        return redirect()->back()->with('success', 'Активний проєкт змінено');
    }

    private function resetWorkspaceSessionState(Request $request): void
    {
        $request->session()->forget([
            'client1',
            'client2',
            'doc_id',
            'parent_doc_id',
            'num',
            'numz',
            'typez',
            'pos',
            'year',
            'client_pos',
            'cl_search',
            'cl_city',
            'cl_idstatus',
            'cl_phone',
            'cl_email',
            'idcaption',
            'idglava',
            'goods_pos',
            'sort',
            'filter1',
            'filter_brand',
            'sklad_none',
        ]);

        $request->session()->put('doc', 'ZOUT');
    }

    private function canSwitchToProject(?object $user, Project $project): bool
    {
        if (! $user instanceof User) {
            return false;
        }

        $projectId = (string) $project->id;
        $userFirma = (string) (($user->firma ?? '') ?: ($user->fid ?? ''));
        if ($userFirma !== '' && $userFirma === $projectId) {
            return true;
        }

        if ((int) ($project->userid ?? 0) > 0 && (int) $project->userid === (int) $user->id) {
            return true;
        }

        $email = $this->resolveUserEmailForProjectMetadata($user) ?? '';
        if ($email !== '') {
            if (Schema::hasColumn('project', 'email') && mb_strtolower(trim((string) ($project->email ?? ''))) === $email) {
                return true;
            }

            if (Schema::hasTable('users') && Schema::hasColumn('users', 'email') && Schema::hasColumn('users', 'firma')) {
                return User::query()
                    ->where('firma', $projectId)
                    ->whereRaw('LOWER(TRIM(email)) = ?', [$email])
                    ->exists();
            }
        }

        return false;
    }

    public function save(Request $request)
    {
        $fid = session('fid', '');
        $run = $request->input('run', '');

        if ($run === 'save_conf') {
            $id = $request->input('id', '');
            $data = [
                'name' => $request->input('name', ''),
                'type' => $request->input('type', ''),
                'color' => $request->input('color', ''),
                'status' => $request->input('status', '1'),
                'vision' => $request->input('vision', '1'),
                'hide' => $request->input('hide', '0'),
                'constanta' => $request->input('constanta', '0'),
                'firma' => $fid,
            ];

            Settings::saveConf($id, $data);
        }

        if ($run === 'del_conf') {
            $id = $request->input('id', '');
            Settings::deleteConf($id, $fid);
        }

        return redirect()->route('settings.index')->with('success', 'Дані змінено!');
    }

    // ── API endpoints for async CRUD ──────────────────────────────────────────

    /**
     * GET /settings/api/{type}
     * Return all conf records for a given type (reteil|status|reestr).
     */
    public function apiIndex($type)
    {
        $fid = session('fid', '');
        $items = DB::table('conf')->where('type', $type)->where('firma', $fid)->orderBy('name')->get()
            ->map(fn ($item) => $this->decorateConfItem($item, $type));
        return response()->json($items);
    }

    /**
     * GET /settings/api/{type}/{id}
     * Return a single conf record.
     */
    public function apiShow($type, $id)
    {
        $fid = session('fid', '');
        $item = DB::table('conf')->where('id', $id)->where('type', $type)->where('firma', $fid)->first();
        if (!$item) return response()->json(['message' => 'Не знайдено'], 404);
        return response()->json($this->decorateConfItem($item, $type));
    }

    /**
     * POST /settings/api
     * Create a new conf record.
     */
    public function apiStore(Request $request)
    {
        $fid = session('fid', '');
        $data = $this->validateConfRecord($request);
        $data['hide'] = '0';
        $data['firma'] = $fid;

        $id = DB::table('conf')->insertGetId($data);
        $this->syncDefaultConfRecord((int) $id, $data);

        return response()->json(['success' => true, 'id' => $id]);
    }

    /**
     * PUT /settings/api/{id}
     * Update an existing conf record.
     */
    public function apiUpdate(Request $request, $id)
    {
        $fid = session('fid', '');
        $exists = DB::table('conf')->where('id', $id)->where('firma', $fid)->first();
        if (!$exists) return response()->json(['success' => false, 'message' => 'Не знайдено'], 404);
        $update = $this->validateConfRecord($request, $exists);

        DB::table('conf')->where('id', $id)->update($update);
        $this->syncDefaultConfRecord((int) $id, $update);

        return response()->json(['success' => true]);
    }

    /**
     * DELETE /settings/api/{id}
     * Delete a conf record.
     */
    public function apiDestroy($id)
    {
        $fid = session('fid', '');
        $exists = DB::table('conf')->where('id', $id)->where('firma', $fid)->first();
        if (!$exists) return response()->json(['success' => false, 'message' => 'Не знайдено'], 404);

        DB::table('conf')->where('id', $id)->delete();
        return response()->json(['success' => true]);
    }

    public function web3TokenSearch(Request $request, ZerionWalletService $zerionWalletService)
    {
        $validated = $request->validate([
            'query' => ['required', 'string', 'min:2', 'max:66'],
            'chain_id' => ['nullable', 'string', 'max:20'],
        ]);

        return response()->json(
            $zerionWalletService->searchFungibles(
                $validated['query'],
                $validated['chain_id'] ?? null,
                10
            )
        );
    }

    public function accountsIndex()
    {
        if (!Schema::hasTable('accounts')) {
            return response()->json([]);
        }

        $fid = (string) session('fid', '');
        $columns = [
            'accounts.id',
            'accounts.code',
            'accounts.name',
            'accounts.type',
            'accounts.parent_id',
            'parent.code as parent_code',
            'parent.name as parent_name',
        ];
        if (Schema::hasColumn('accounts', 'currency')) {
            $columns[] = 'accounts.currency';
        }

        $items = Account::query()
            ->leftJoin('accounts as parent', 'accounts.parent_id', '=', 'parent.id')
            ->where(function ($query) use ($fid) {
                $query->where('accounts.code', 'not like', '%.%');
                if ($fid !== '') {
                    $query->orWhere('accounts.code', 'like', "%.{$fid}.%");
                }
            })
            ->orderBy('accounts.code')
            ->get($columns)
            ->map(fn ($item) => $this->decorateAccountItem($item));

        return response()->json($items);
    }

    public function accountsShow($id)
    {
        if (!Schema::hasTable('accounts')) {
            return response()->json(['message' => 'Не знайдено'], 404);
        }

        $item = Account::query()->find($id);
        if (!$item) {
            return response()->json(['message' => 'Не знайдено'], 404);
        }

        return response()->json($this->decorateAccountItem($item));
    }

    public function accountsStore(Request $request)
    {
        $validated = $this->validateAccountRecord($request, [
            'code' => 'required|string|max:255|unique:accounts,code',
        ]);

        $account = Account::query()->create($validated);

        return response()->json(['success' => true, 'id' => $account->id]);
    }

    public function accountsUpdate(Request $request, $id)
    {
        $account = Account::query()->find($id);
        if (!$account) {
            return response()->json(['success' => false, 'message' => 'Не знайдено'], 404);
        }

        $validated = $this->validateAccountRecord($request, [
            'code' => 'required|string|max:255|unique:accounts,code,' . $account->id,
        ]);

        if ((int) ($validated['parent_id'] ?? 0) === (int) $account->id) {
            return response()->json(['success' => false, 'message' => 'Рахунок не може бути батьківським сам для себе'], 422);
        }

        $account->update($validated);

        return response()->json(['success' => true]);
    }

    public function accountsDestroy($id)
    {
        $account = Account::query()->find($id);
        if (!$account) {
            return response()->json(['success' => false, 'message' => 'Не знайдено'], 404);
        }

        if (Account::query()->where('parent_id', $account->id)->exists()) {
            return response()->json(['success' => false, 'message' => 'Спочатку перенесіть або видаліть дочірні рахунки'], 422);
        }

        if (Schema::hasTable('entries') && DB::table('entries')->where('account_id', $account->id)->exists()) {
            return response()->json(['success' => false, 'message' => 'Рахунок уже використаний у проводках і не може бути видалений'], 422);
        }

        if (Schema::hasTable('conf') && Schema::hasColumn('conf', 'debit_account_id') && Schema::hasColumn('conf', 'credit_account_id')) {
            DB::table('conf')
                ->where('debit_account_id', $account->id)
                ->orWhere('credit_account_id', $account->id)
                ->update([
                    'debit_account_id' => DB::raw("IF(debit_account_id = {$account->id}, NULL, debit_account_id)"),
                    'credit_account_id' => DB::raw("IF(credit_account_id = {$account->id}, NULL, credit_account_id)"),
                ]);
        }

        $account->delete();

        return response()->json(['success' => true]);
    }

    private function validateAccountRecord(Request $request, array $codeRule): array
    {
        $rules = array_merge($codeRule, [
            'name' => 'required|string|max:255',
            'type' => 'required|in:asset,liability,equity,income,expense',
            'parent_id' => 'nullable|integer|exists:accounts,id',
            'currency' => 'nullable|string|max:10',
        ]);

        $validated = $request->validate($rules);

        if (! Schema::hasColumn('accounts', 'currency')) {
            unset($validated['currency']);

            return $validated;
        }

        $currency = $this->normalizeCurrencyCode($validated['currency'] ?? 'UAH');
        $availableCurrencies = $this->currencyCodesForFirma(session('fid', ''));
        if ($availableCurrencies->isNotEmpty() && ! $availableCurrencies->contains($currency)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'currency' => 'Валюта счета должна быть выбрана из справочника валют.',
            ]);
        }

        $validated['currency'] = $currency;

        return $validated;
    }

    private function decorateAccountItem(object $item): object
    {
        $item->currency = Schema::hasColumn('accounts', 'currency')
            ? $this->normalizeCurrencyCode($item->currency ?? 'UAH')
            : 'UAH';

        return $item;
    }

    public function paymentTypeAccountBindings()
    {
        $fid = session('fid', '');

        if (!Schema::hasTable('conf')) {
            return response()->json([]);
        }

        $paymentTypes = DB::table('conf as c')
            ->leftJoin('accounts as da', 'c.debit_account_id', '=', 'da.id')
            ->leftJoin('accounts as ca', 'c.credit_account_id', '=', 'ca.id')
            ->where('c.type', 'reestr')
            ->where('c.firma', $fid)
            ->orderBy('c.name')
            ->get([
                'c.id',
                'c.name',
                'c.doc',
                'c.debit_account_id',
                'c.credit_account_id',
                'da.code as debit_account_code',
                'da.name as debit_account_name',
                'ca.code as credit_account_code',
                'ca.name as credit_account_name',
            ])
            ->map(fn ($item) => Conf::decoratePaymentType($item));

        return response()->json($paymentTypes);
    }

    public function updatePaymentTypeAccountBinding(Request $request, $id)
    {
        $fid = session('fid', '');

        $validated = $request->validate([
            'debit_account_id' => 'nullable|integer|exists:accounts,id',
            'credit_account_id' => 'nullable|integer|exists:accounts,id',
        ]);

        $paymentType = DB::table('conf')
            ->where('id', $id)
            ->where('type', 'reestr')
            ->where('firma', $fid)
            ->first();

        if (!$paymentType) {
            return response()->json(['success' => false, 'message' => 'Не знайдено'], 404);
        }

        DB::table('conf')
            ->where('id', $id)
            ->update([
                'debit_account_id' => $validated['debit_account_id'] ?? null,
                'credit_account_id' => $validated['credit_account_id'] ?? null,
            ]);

        return response()->json(['success' => true]);
    }

    // ── Profile ───────────────────────────────────────────────────────────────

    public function profileUpdate(Request $request)
    {
        $user = $this->currentUser();
        if (!$user) return redirect()->back()->with('error', 'Користувача не знайдено');

        $request->validate([
            'name' => 'nullable|string|max:255',
            'secondname' => 'nullable|string|max:255',
            'fathername' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'city' => 'nullable|string|max:100',
            'hbd' => 'nullable|string|max:50',
        ]);

        $stringValue = static fn ($value): string => trim((string) ($value ?? ''));

        DB::table('users')->where('id', $user->id)->update([
            'name'       => $stringValue($request->input('name')),
            'secondname' => $stringValue($request->input('secondname')),
            'fathername' => $stringValue($request->input('fathername')),
            'email'      => $stringValue($request->input('email')),
            'phone'      => $stringValue($request->input('phone')),
            'city'       => $stringValue($request->input('city')),
            'hbd'        => $stringValue($request->input('hbd')),
        ]);

        return redirect()->route('settings.index')->with('success', 'Профіль оновлено');
    }

    public function profileBalancesUpdate(Request $request)
    {
        $user = $this->currentUser();
        if (!$user) return redirect()->back()->with('error', 'Користувача не знайдено');

        if (! Schema::hasColumn('users', 'balance')) {
            return redirect()->back()->with('error', 'Поле балансу не знайдено');
        }

        $validated = $request->validate([
            'balance_amounts' => 'nullable|array',
            'balance_amounts.*' => ['nullable', 'string', 'max:50', 'regex:/^-?\d+(?:[.,]\d{1,18})?$/'],
            'balance_currencies' => 'nullable|array',
            'balance_currencies.*' => 'nullable|string|max:20',
            'balance_delete' => 'nullable|array',
            'default_balance_key' => 'nullable|string|max:50',
        ]);

        $balances = $this->normalizeProfileBalanceRows(
            $validated['balance_amounts'] ?? [],
            $validated['balance_currencies'] ?? [],
            $validated['balance_delete'] ?? [],
            (string) ($validated['default_balance_key'] ?? '')
        );

        DB::table('users')->where('id', $user->id)->update([
            'balance' => $this->serializeProfileBalances($balances),
        ]);

        return redirect()->route('settings.index')->with('success', 'Баланси профілю оновлено');
    }

    private function parseUserBalance(mixed $value): array
    {
        $raw = trim((string) ($value ?? ''));
        if ($raw === '') {
            return [];
        }

        if (! str_contains($raw, ':') && is_numeric($raw) && (float) $raw != 0.0) {
            return [[
                'amount' => $raw,
                'currency' => 'UAH',
                'is_default' => true,
            ]];
        }

        $balances = [];
        foreach (explode(';', $raw) as $segment) {
            $segment = trim($segment);
            if ($segment === '' || ! str_contains($segment, ':')) {
                continue;
            }

            [$amount, $currency] = array_map('trim', explode(':', $segment, 2));
            $currency = $this->normalizeCurrencyCode($currency);
            $amount = str_replace(',', '.', $amount);
            if ($amount === '' || ! is_numeric($amount)) {
                continue;
            }

            $balances[] = [
                'amount' => $amount,
                'currency' => $currency,
                'is_default' => count($balances) === 0,
            ];
        }

        return $balances;
    }

    private function normalizeProfileBalanceRows(array $amounts, array $currencies, array $deleted, string $defaultKey): array
    {
        $availableCurrencies = $this->currencyCodesForFirma(session('fid', ''));
        $rows = [];
        $seenCurrencies = [];

        foreach ($amounts as $key => $amount) {
            if (array_key_exists((string) $key, $deleted) || array_key_exists($key, $deleted)) {
                continue;
            }

            $amount = str_replace(',', '.', trim((string) $amount));
            if ($amount === '') {
                continue;
            }

            if (! is_numeric($amount)) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'balance_amounts.' . $key => 'Сума балансу має бути числом.',
                ]);
            }

            $currency = $this->normalizeCurrencyCode($currencies[$key] ?? 'UAH');
            if ($availableCurrencies->isNotEmpty() && ! $availableCurrencies->contains($currency)) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'balance_currencies.' . $key => 'Валюта балансу має бути вибрана з довідника валют.',
                ]);
            }

            if (isset($seenCurrencies[$currency])) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'balance_currencies.' . $key => 'Для однієї валюти можна залишити тільки один баланс.',
                ]);
            }

            $seenCurrencies[$currency] = true;
            $rows[] = [
                'key' => (string) $key,
                'amount' => $this->formatProfileBalanceAmount($amount),
                'currency' => $currency,
            ];
        }

        if ($rows === []) {
            return [];
        }

        $defaultIndex = 0;
        foreach ($rows as $index => $row) {
            if ($row['key'] === $defaultKey) {
                $defaultIndex = $index;
                break;
            }
        }

        $defaultRow = $rows[$defaultIndex];
        unset($rows[$defaultIndex]);

        return array_values(array_map(
            fn (array $row): array => ['amount' => $row['amount'], 'currency' => $row['currency']],
            array_merge([$defaultRow], $rows)
        ));
    }

    private function serializeProfileBalances(array $balances): ?string
    {
        if ($balances === []) {
            return null;
        }

        return collect($balances)
            ->map(fn (array $balance): string => $balance['amount'] . ':' . $balance['currency'] . ';')
            ->implode('');
    }

    private function formatProfileBalanceAmount(string $amount): string
    {
        $amount = trim($amount);
        if (str_contains($amount, '.')) {
            $amount = rtrim(rtrim($amount, '0'), '.');
        }

        if ($amount === '' || $amount === '-0') {
            return '0';
        }

        return $amount;
    }

    public function passwordChange(Request $request)
    {
        $user = $this->currentUser();
        if (!$user) return redirect()->back()->with('error', 'Користувача не знайдено');

        $currentPassword = $request->input('current_password', '');
        $newPassword = $request->input('new_password', '');
        $confirmPassword = $request->input('new_password_confirmation', '');

        $currentHash = ($user->pass ?? '') ?: ($user->password ?? '');

        // Verify current password
        if (
            !Hash::check($currentPassword, $currentHash)
            && $currentHash !== md5($currentPassword)
            && $currentHash !== md5(md5($currentPassword))
        ) {
            return redirect()->back()->with('error', 'Поточний пароль введено неправильно');
        }

        if ($newPassword !== $confirmPassword) {
            return redirect()->back()->with('error', 'Нові паролі не збігаються');
        }

        if (strlen($newPassword) < 4) {
            return redirect()->back()->with('error', 'Пароль має бути не менше 4 символів');
        }

        $hash = Hash::make($newPassword);
        $update = ['pass' => $hash];
        if (Schema::hasColumn('users', 'password')) {
            $update['password'] = $hash;
        }

        DB::table('users')->where('id', $user->id)->update($update);

        return redirect()->route('settings.index')->with('success', 'Пароль змінено');
    }

    // ── Firma CRUD ───────────────────────────────────────────────────────────

    public function firmsIndex()
    {
        $user = $this->currentUser();
        if (!$user) {
            return response()->json(['message' => 'Користувача не знайдено'], 404);
        }

        return response()->json(
            $this->companiesQuery($user)
                ->orderBy('id')
                ->get()
                ->map(fn ($company) => Firma::decorateMedia($company))
        );
    }

    public function firmsShow($id)
    {
        $user = $this->currentUser();
        if (!$user) {
            return response()->json(['message' => 'Користувача не знайдено'], 404);
        }

        $company = $this->companiesQuery($user)->where('id', $id)->first();
        if (!$company) {
            return response()->json(['message' => 'Компанію не знайдено'], 404);
        }

        return response()->json(Firma::decorateMedia($company));
    }

    public function firmsStore(Request $request)
    {
        $user = $this->currentUser();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Користувача не знайдено'], 404);
        }

        $validated = $this->validateFirma($request);

        $payload = array_merge($validated, [
            'userid' => $user->id,
            'firma' => ($user->firma ?? '') ?: session('fid', 0),
        ]);

        $id = Firma::query()->insertGetId($payload);

        return response()->json(['success' => true, 'id' => $id]);
    }

    public function firmsUpdate(Request $request, $id)
    {
        $user = $this->currentUser();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Користувача не знайдено'], 404);
        }

        $company = $this->companiesQuery($user)->where('id', $id)->first();
        if (!$company) {
            return response()->json(['success' => false, 'message' => 'Компанію не знайдено'], 404);
        }

        Firma::query()->where('id', $id)->update($this->validateFirma($request, $company));

        return response()->json(['success' => true]);
    }

    public function firmsDestroy($id)
    {
        $user = $this->currentUser();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Користувача не знайдено'], 404);
        }

        $company = $this->companiesQuery($user)->where('id', $id)->first();
        if (!$company) {
            return response()->json(['success' => false, 'message' => 'Компанію не знайдено'], 404);
        }

        Firma::query()->where('id', $id)->delete();

        return response()->json(['success' => true]);
    }

    public function projectsIndex()
    {
        $user = $this->currentUser();
        if (!Schema::hasTable('project')) {
            return response()->json([]);
        }

        $items = Project::query()
            ->orderBy('num')
            ->orderBy('name')
            ->get()
            ->map(fn (Project $project) => $this->normalizeProject($project, $user));

        return response()->json($items);
    }

    public function projectsShow($id)
    {
        $user = $this->currentUser();
        if (!Schema::hasTable('project')) {
            return response()->json(['success' => false, 'message' => 'Таблицю project не знайдено'], 404);
        }

        $project = Project::query()->find($id);

        if (!$project) {
            return response()->json(['success' => false, 'message' => 'Проєкт не знайдено'], 404);
        }

        return response()->json($this->normalizeProject($project, $user));
    }

    public function projectsPublicShow($id)
    {
        if (!Schema::hasTable('project')) {
            return response()->json(['success' => false, 'message' => 'Таблицю project не знайдено'], 404);
        }

        $project = Project::query()->find($id);
        if (!$project) {
            return response()->json(['success' => false, 'message' => 'Проєкт не знайдено'], 404);
        }

        return response()->json([
            'item' => $this->normalizeProject($project, null),
        ]);
    }

    public function managerAiProjectsIndex(Request $request)
    {
        if (! $this->canUseManagerAiProjectApi($request)) {
            return response()->json(['success' => false, 'message' => 'Forbidden.'], 403);
        }

        if (!Schema::hasTable('project')) {
            return response()->json(['success' => false, 'message' => 'Таблицю project не знайдено'], 404);
        }

        if (! Schema::hasColumn('project', 'email')) {
            return response()->json([
                'success' => false,
                'message' => 'Таблиця project не має поля email.',
            ], 422);
        }

        $email = $this->managerAiEmailFromRequest($request);
        if ($email === null) {
            return response()->json([
                'success' => false,
                'message' => 'Передайте коректний email у полі email або заголовку X-ManagerAI-User-Email.',
            ], 422);
        }

        $items = Project::query()
            ->whereRaw('LOWER(TRIM(email)) = ?', [$email])
            ->orderBy('num')
            ->orderBy('name')
            ->get()
            ->map(fn (Project $project) => $this->normalizeProject($project, null))
            ->values();

        return response()->json([
            'success' => true,
            'email' => $email,
            'items' => $items,
        ]);
    }

    public function managerAiProjectsStore(Request $request)
    {
        if (! $this->canUseManagerAiProjectApi($request)) {
            return response()->json(['success' => false, 'message' => 'Forbidden.'], 403);
        }

        if (!Schema::hasTable('project')) {
            return response()->json(['success' => false, 'message' => 'Таблицю project не знайдено'], 404);
        }

        if (! Schema::hasColumn('project', 'email')) {
            return response()->json([
                'success' => false,
                'message' => 'Неможливо створити проєкт: таблиця project не має поля email.',
            ], 422);
        }

        $email = $this->managerAiEmailFromRequest($request);
        if ($email === null) {
            return response()->json([
                'success' => false,
                'message' => 'Передайте коректний email у полі email або заголовку X-ManagerAI-User-Email.',
            ], 422);
        }

        $payload = $this->validateProject($request);
        $payload['email'] = $email;

        $owner = $this->managerAiProjectOwnerByEmail($email);
        if ($owner instanceof User && Schema::hasColumn('project', 'userid')) {
            $payload['userid'] = (int) $owner->id;
        }

        $project = Project::query()->create($payload);
        $projectUserId = $owner instanceof User
            ? $this->ensureProjectUserCopyForUser($project, $owner)
            : null;

        if ($projectUserId && Schema::hasColumn('project', 'userid')) {
            $project->forceFill(['userid' => $projectUserId])->save();
        }

        return response()->json([
            'success' => true,
            'id' => $project->id,
            'item' => $this->normalizeProject($project->refresh(), null),
        ], 201);
    }

    public function managerAiProjectsUpdate(Request $request, $id)
    {
        if (! $this->canUseManagerAiProjectApi($request)) {
            return response()->json(['success' => false, 'message' => 'Forbidden.'], 403);
        }

        if (!Schema::hasTable('project')) {
            return response()->json(['success' => false, 'message' => 'Таблицю project не знайдено'], 404);
        }

        if (! Schema::hasColumn('project', 'email')) {
            return response()->json([
                'success' => false,
                'message' => 'Неможливо оновити проєкт: таблиця project не має поля email.',
            ], 422);
        }

        $email = $this->managerAiEmailFromRequest($request);
        if ($email === null) {
            return response()->json([
                'success' => false,
                'message' => 'Передайте коректний email у полі email або заголовку X-ManagerAI-User-Email.',
            ], 422);
        }

        $project = Project::query()->find($id);
        if (! $project) {
            return response()->json(['success' => false, 'message' => 'Проєкт не знайдено'], 404);
        }

        $projectEmail = mb_strtolower(trim((string) ($project->email ?? '')));
        if ($projectEmail === '' || $projectEmail !== $email) {
            return response()->json([
                'success' => false,
                'message' => 'ManagerAI може редагувати лише проєкти з тим самим project.email.',
            ], 403);
        }

        $payload = $this->validateProject($request);
        $payload['email'] = $email;

        $owner = $this->managerAiProjectOwnerByEmail($email);
        if ($owner instanceof User && Schema::hasColumn('project', 'userid')) {
            $payload['userid'] = (int) $owner->id;
        }

        $project->fill($payload)->save();
        $projectUserId = $owner instanceof User
            ? $this->ensureProjectUserCopyForUser($project, $owner)
            : null;

        if ($projectUserId && Schema::hasColumn('project', 'userid')) {
            $project->forceFill(['userid' => $projectUserId])->save();
        }

        return response()->json([
            'success' => true,
            'item' => $this->normalizeProject($project->refresh(), null),
        ]);
    }

    public function officesPublicIndex(Request $request)
    {
        $fid = (string) $request->input('fid', session('fid', '2'));

        if (!Schema::hasTable('conf')) {
            return response()->json(['items' => []]);
        }

        $items = DB::table('conf')
            ->where('type', 'sklads')
            ->where('firma', $fid)
            ->where('vision', '1')
            ->orderBy('name')
            ->get()
            ->map(function ($item) {
                $item->phone = trim((string) ($item->phone ?? ''));
                if ($item->phone === '') {
                    $item->phone = trim((string) ($item->descript2 ?? ''));
                }

                $item->address = trim((string) ($item->address ?? ''));
                if ($item->address === '') {
                    $item->address = trim((string) ($item->descript ?? ''));
                }

                $item->google_map = trim((string) ($item->google_map ?? ''));
                if ($item->google_map === '') {
                    $item->google_map = trim((string) ($item->descript3 ?? ''));
                }
                $item->foto_preview = MediaUrl::image((string) ($item->foto ?? ''));

                return $item;
            })
            ->values();

        return response()->json([
            'items' => $items,
        ]);
    }

    public function projectsStore(Request $request)
    {
        if (!Schema::hasTable('project')) {
            return response()->json(['success' => false, 'message' => 'Таблицю project не знайдено'], 404);
        }

        $creator = $this->currentUser();
        if (! $creator instanceof User) {
            return response()->json(['success' => false, 'message' => 'Потрібна авторизація'], 401);
        }

        if (! Schema::hasColumn('users', 'email')) {
            return response()->json([
                'success' => false,
                'message' => 'Неможливо створити проєкт: у профілі користувача відсутнє поле email.',
            ], 422);
        }

        $profileEmail = trim((string) ($creator->email ?? ''));
        if ($profileEmail === '' || ! filter_var($profileEmail, FILTER_VALIDATE_EMAIL)) {
            return response()->json([
                'success' => false,
                'message' => 'Щоб створити проєкт, вкажіть коректний email у профілі (картка користувача).',
            ], 422);
        }

        $payload = $this->validateProject($request);
        if (Schema::hasColumn('project', 'email')) {
            $payload['email'] = mb_strtolower($profileEmail);
        }

        $project = Project::query()->create($payload);
        $projectUserId = $this->ensureProjectUserCopy($project);

        if ($projectUserId && Schema::hasColumn('project', 'userid')) {
            $project->forceFill(['userid' => $projectUserId])->save();
        }

        return response()->json(['success' => true, 'id' => $project->id]);
    }

    public function projectsUpdate(Request $request, $id)
    {
        if (!Schema::hasTable('project')) {
            return response()->json(['success' => false, 'message' => 'Таблицю project не знайдено'], 404);
        }

        $project = Project::query()->find($id);

        if (!$project) {
            return response()->json(['success' => false, 'message' => 'Проєкт не знайдено'], 404);
        }

        $project->fill($this->validateProject($request))->save();
        $projectUserId = $this->ensureProjectUserCopy($project);

        if ($projectUserId && Schema::hasColumn('project', 'userid')) {
            $project->forceFill(['userid' => $projectUserId])->save();
        }

        return response()->json(['success' => true]);
    }

    public function projectsDestroy($id)
    {
        $user = $this->currentUser();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Користувача не знайдено'], 404);
        }

        if (!Schema::hasTable('project')) {
            return response()->json(['success' => false, 'message' => 'Таблицю project не знайдено'], 404);
        }

        $project = Project::query()->find($id);
        if (!$project) {
            return response()->json(['success' => false, 'message' => 'Проєкт не знайдено'], 404);
        }

        if (! $this->userCanDeleteProjectByEmail($user, $project)) {
            return response()->json([
                'success' => false,
                'message' => 'Видалити проєкт може лише користувач, у якого email збігається з email проєкту (project.email).',
            ], 403);
        }

        $project->delete();

        return response()->json(['success' => true]);
    }

    public function bannersIndex()
    {
        $fid = session('fid', '');

        $items = DB::table('banner_carousels')
            ->where('firma', $fid)
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->get()
            ->map(static fn ($item) => BannerCarousel::decorate($item));

        return response()->json($items);
    }

    public function bannersShow($id)
    {
        $fid = session('fid', '');

        $item = DB::table('banner_carousels')
            ->where('id', $id)
            ->where('firma', $fid)
            ->first();

        if (!$item) {
            return response()->json(['success' => false, 'message' => 'Банер не знайдено'], 404);
        }

        return response()->json(BannerCarousel::decorate($item));
    }

    public function bannersStore(Request $request)
    {
        $fid = session('fid', '');
        $payload = $this->validateBannerCarousel($request);
        $payload['firma'] = $fid;
        $payload['created_at'] = now();
        $payload['updated_at'] = now();

        $id = DB::table('banner_carousels')->insertGetId($payload);

        return response()->json(['success' => true, 'id' => $id]);
    }

    public function bannersUpdate(Request $request, $id)
    {
        $fid = session('fid', '');
        $existing = DB::table('banner_carousels')
            ->where('id', $id)
            ->where('firma', $fid)
            ->first();

        if (!$existing) {
            return response()->json(['success' => false, 'message' => 'Банер не знайдено'], 404);
        }

        $payload = $this->validateBannerCarousel($request, $existing);
        $payload['updated_at'] = now();

        DB::table('banner_carousels')->where('id', $id)->update($payload);

        return response()->json(['success' => true]);
    }

    public function bannersDestroy($id)
    {
        $fid = session('fid', '');
        $exists = DB::table('banner_carousels')
            ->where('id', $id)
            ->where('firma', $fid)
            ->exists();

        if (!$exists) {
            return response()->json(['success' => false, 'message' => 'Банер не знайдено'], 404);
        }

        DB::table('banner_carousels')->where('id', $id)->delete();

        return response()->json(['success' => true]);
    }

    // ── Catalog CRUD (field.keyfield = catalog) ─────────────────────────────

    public function fieldIndex(Request $request)
    {
        return $this->fieldIndexByKeyfield($request, $this->resolveFieldKeyfield($request));
    }

    public function fieldShow(Request $request, $id)
    {
        return $this->fieldShowByKeyfield($request, $id, $this->resolveFieldKeyfield($request));
    }

    public function fieldStore(Request $request)
    {
        return $this->fieldStoreByKeyfield($request, $this->resolveFieldKeyfield($request));
    }

    public function fieldUpdate(Request $request, $id)
    {
        return $this->fieldUpdateByKeyfield($request, $id, $this->resolveFieldKeyfield($request));
    }

    public function fieldDestroy(Request $request, $id)
    {
        return $this->fieldDestroyByKeyfield($request, $id, $this->resolveFieldKeyfield($request));
    }

    public function catalogIndex(Request $request)
    {
        return $this->fieldIndexByKeyfield($request, 'catalog');
    }

    public function catalogShow($id)
    {
        return $this->fieldShowByKeyfield(request(), $id, 'catalog');
    }

    public function catalogStore(Request $request)
    {
        return $this->fieldStoreByKeyfield($request, 'catalog');
    }

    public function catalogUpdate(Request $request, $id)
    {
        return $this->fieldUpdateByKeyfield($request, $id, 'catalog');
    }

    public function catalogDestroy($id)
    {
        return $this->fieldDestroyByKeyfield(request(), $id, 'catalog');
    }

    // ── Catalog filters (filter.keyfield = filter, idkeyfield = field.id catalog) ─

    public function catalogFiltersCategories()
    {
        if (!Schema::hasTable('field')) {
            return response()->json(['categories' => []]);
        }

        $fid = session('fid', '');
        $columns = $this->fieldColumns();
        $select = ['id', 'idkeyfield'];
        foreach (['val', 'valua', 'val_ua', 'valru'] as $col) {
            if (in_array($col, $columns, true)) {
                $select[] = $col;
            }
        }

        $rows = $this->fieldFilterCatalogBaseQuery($fid)
            ->orderBy('id')
            ->get($select);

        $byId = $rows->keyBy(fn ($r) => (int) $r->id);
        $categories = $rows->map(function ($r) use ($byId) {
            return [
                'id' => (int) $r->id,
                'label' => $this->catalogFieldLabelPath($byId, (int) $r->id),
            ];
        })->values();

        return response()->json(['categories' => $categories]);
    }

    public function catalogFiltersIndex(Request $request)
    {
        if (!Schema::hasTable('filter')) {
            return response()->json(['groups' => [], 'catalog_id' => null]);
        }

        $catalogId = (int) $request->query('catalog_id', 0);
        if ($catalogId <= 0) {
            return response()->json(['message' => 'Вкажіть catalog_id'], 422);
        }

        $fid = session('fid', '');
        if (! $this->fieldFilterCatalogFind($fid, $catalogId)) {
            return response()->json(['message' => 'Категорію не знайдено'], 404);
        }

        $groups = Filter::query()
            ->where('keyfield', 'filter')
            ->where('idkeyfield', $catalogId)
            ->where('idfilter', 0)
            ->orderBy('num')
            ->orderBy('id')
            ->get();

        $payload = $groups->map(function ($g) use ($catalogId) {
            $values = Filter::query()
                ->where('keyfield', 'filter')
                ->where('idkeyfield', $catalogId)
                ->where('idfilter', $g->id)
                ->orderBy('num')
                ->orderBy('id')
                ->get();

            return [
                'group' => $this->serializeCatalogFilter($g),
                'values' => $values->map(fn ($v) => $this->serializeCatalogFilter($v))->values(),
            ];
        })->values();

        return response()->json([
            'groups' => $payload,
            'catalog_id' => $catalogId,
        ]);
    }

    public function catalogFiltersShow(Request $request, $id)
    {
        if (!Schema::hasTable('filter')) {
            return response()->json(['message' => 'Не знайдено'], 404);
        }

        $row = Filter::query()->where('id', $id)->where('keyfield', 'filter')->first();
        if (!$row) {
            return response()->json(['message' => 'Не знайдено'], 404);
        }

        $fid = session('fid', '');
        if (! $this->fieldFilterCatalogFind($fid, (string) $row->idkeyfield)) {
            return response()->json(['message' => 'Не знайдено'], 404);
        }

        return response()->json($this->serializeCatalogFilter($row));
    }

    public function catalogFiltersStore(Request $request)
    {
        if (!Schema::hasTable('filter')) {
            return response()->json(['success' => false, 'message' => 'Таблиця filter відсутня'], 404);
        }

        $data = $request->validate([
            'catalog_id' => 'required|integer|min:1',
            'is_group' => 'required|boolean',
            'parent_group_id' => 'nullable|integer|min:1',
            'val' => 'required|string|max:60',
            'valru' => 'nullable|string|max:60',
            'valen' => 'nullable|string|max:60',
            'num' => 'nullable|integer|min:0|max:65535',
        ]);

        $fid = session('fid', '');
        $catalogId = (int) $data['catalog_id'];
        if (! $this->fieldFilterCatalogFind($fid, $catalogId)) {
            return response()->json(['success' => false, 'message' => 'Категорію не знайдено'], 404);
        }

        $isGroup = (bool) $data['is_group'];
        $idfilter = 0;
        if (!$isGroup) {
            $parentId = (int) ($data['parent_group_id'] ?? 0);
            if ($parentId < 1) {
                return response()->json(['success' => false, 'message' => 'Для значення потрібна група (parent_group_id)'], 422);
            }
            $parent = Filter::query()
                ->where('id', $parentId)
                ->where('keyfield', 'filter')
                ->where('idkeyfield', $catalogId)
                ->where('idfilter', 0)
                ->first();
            if (!$parent) {
                return response()->json(['success' => false, 'message' => 'Групу фільтра не знайдено'], 404);
            }
            $idfilter = $parentId;
        }

        $payload = [
            'idkeyfield' => $catalogId,
            'idfilter' => $idfilter,
            'keyfield' => 'filter',
            'val' => $data['val'],
            'valru' => (string) ($data['valru'] ?? ''),
            'valen' => (string) ($data['valen'] ?? ''),
            'count' => 0,
            'top' => 0,
            'num' => (int) ($data['num'] ?? 0),
        ];

        foreach (['description', 'descriptionen', 'descriptionru'] as $col) {
            if (Schema::hasColumn('filter', $col)) {
                $payload[$col] = '';
            }
        }

        $newId = Filter::query()->insertGetId($payload);
        $row = Filter::query()->find($newId);

        return response()->json([
            'success' => true,
            'item' => $row ? $this->serializeCatalogFilter($row) : null,
        ]);
    }

    public function catalogFiltersUpdate(Request $request, $id)
    {
        if (!Schema::hasTable('filter')) {
            return response()->json(['success' => false, 'message' => 'Таблиця filter відсутня'], 404);
        }

        $row = Filter::query()->where('id', $id)->where('keyfield', 'filter')->first();
        if (!$row) {
            return response()->json(['success' => false, 'message' => 'Запис не знайдено'], 404);
        }

        $fid = session('fid', '');
        if (! $this->fieldFilterCatalogFind($fid, (string) $row->idkeyfield)) {
            return response()->json(['success' => false, 'message' => 'Запис не знайдено'], 404);
        }

        $data = $request->validate([
            'val' => 'required|string|max:60',
            'valru' => 'nullable|string|max:60',
            'valen' => 'nullable|string|max:60',
            'num' => 'nullable|integer|min:0|max:65535',
        ]);

        $update = [
            'val' => $data['val'],
            'valru' => (string) ($data['valru'] ?? ''),
            'valen' => (string) ($data['valen'] ?? ''),
            'num' => (int) ($data['num'] ?? 0),
        ];

        Filter::query()->where('id', $id)->update($update);
        $fresh = Filter::query()->find($id);

        return response()->json([
            'success' => true,
            'item' => $fresh ? $this->serializeCatalogFilter($fresh) : null,
        ]);
    }

    public function catalogFiltersDestroy(Request $request, $id)
    {
        if (!Schema::hasTable('filter')) {
            return response()->json(['success' => false, 'message' => 'Таблиця filter відсутня'], 404);
        }

        $row = Filter::query()->where('id', $id)->where('keyfield', 'filter')->first();
        if (!$row) {
            return response()->json(['success' => false, 'message' => 'Запис не знайдено'], 404);
        }

        $fid = session('fid', '');
        if (! $this->fieldFilterCatalogFind($fid, (string) $row->idkeyfield)) {
            return response()->json(['success' => false, 'message' => 'Запис не знайдено'], 404);
        }

        DB::transaction(function () use ($row) {
            if ((int) $row->idfilter === 0) {
                Filter::query()
                    ->where('keyfield', 'filter')
                    ->where('idkeyfield', $row->idkeyfield)
                    ->where('idfilter', $row->id)
                    ->delete();
            }
            Filter::query()->where('id', $row->id)->delete();
        });

        return response()->json(['success' => true]);
    }

    private function catalogFieldLabelPath($byId, int $id): string
    {
        $segments = [];
        $guard = 0;
        $current = $id;

        while ($current > 0 && $guard++ < 80) {
            $row = $byId->firstWhere('id', $current);
            if (! $row) {
                $row = $byId->firstWhere('id', (string) $current);
            }
            if (! $row) {
                break;
            }

            $ua = '';
            if (property_exists($row, 'valua')) {
                $ua = trim((string) ($row->valua ?? ''));
            } elseif (property_exists($row, 'val_ua')) {
                $ua = trim((string) ($row->val_ua ?? ''));
            }
            $ru = trim((string) ($row->val ?? ''));
            $legacyRu = property_exists($row, 'valru') ? trim((string) ($row->valru ?? '')) : '';

            $name = $ua !== '' ? $ua : ($ru !== '' ? $ru : ($legacyRu !== '' ? $legacyRu : '#' . $row->id));
            $segments[] = $name;
            $pid = (int) ($row->idkeyfield ?? 0);
            if ($pid <= 0) {
                break;
            }
            $current = $pid;
        }

        return implode(' → ', array_reverse($segments));
    }

    private function serializeCatalogFilter(object $row): array
    {
        return [
            'id' => (int) $row->id,
            'idkeyfield' => (int) ($row->idkeyfield ?? 0),
            'idfilter' => (int) ($row->idfilter ?? 0),
            'val' => (string) ($row->val ?? ''),
            'valru' => (string) ($row->valru ?? ''),
            'valen' => (string) ($row->valen ?? ''),
            'num' => (int) ($row->num ?? 0),
        ];
    }

    private function currentUser()
    {
        if (Auth::check()) {
            return Auth::user();
        }

        $login = session('login', '');
        if ($login === '') {
            return null;
        }

        return User::forLogin($login)->first();
    }

    private function companiesQuery(object $user)
    {
        return Firma::query()->where(function ($query) use ($user) {
            $query->where('userid', $user->id);

            if (!empty($user->firma ?? null)) {
                $query->orWhere('firma', $user->firma);
            }
        });
    }

    private function validateFirma(Request $request, ?object $existing = null): array
    {
        $firmaId = $existing?->id;
        $user = $this->currentUser();
        $userId = $user?->id;
        
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'regnum' => 'nullable|string|max:12',
            'inn' => 'nullable|string|max:15',
            'schet' => 'nullable|string|max:30',
            'bank' => 'nullable|string|max:50',
            'mfo' => 'nullable|string|max:6',
            'town' => 'nullable|string|max:25',
            'address' => 'nullable|string|max:50',
            'map' => 'nullable|string|max:200',
            'view' => 'nullable|string|max:15',
            'phone' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('firma', 'phone')
                    ->where('userid', $userId)
                    ->ignore($firmaId),
            ],
            'direktor' => 'nullable|string|max:30',
            'pidpys' => 'nullable|string|max:255',
            'pechat' => 'nullable|string|max:255',
            'pidpys_file' => 'nullable|image|max:4096',
            'pechat_file' => 'nullable|image|max:4096',
        ], [
            'phone.unique' => 'Компанія з таким телефоном вже існує',
        ]);

        $pidpys = trim((string) ($validated['pidpys'] ?? ($existing->pidpys ?? '')));
        if ($request->hasFile('pidpys_file')) {
            $uploadedFile = $request->file('pidpys_file');
            if ($uploadedFile && $uploadedFile->isValid()) {
                $extension = strtolower($uploadedFile->getClientOriginalExtension() ?: $uploadedFile->extension() ?: 'png');
                $filename = 'firma_signature_' . date('Ymd_His') . '_' . uniqid() . '.' . $extension;
                $path = $uploadedFile->storeAs('files/firma', $filename, 'public');
                $pidpys = $path ?: $pidpys;
            }
        }

        $pechat = trim((string) ($validated['pechat'] ?? ($existing->pechat ?? '')));
        if ($request->hasFile('pechat_file')) {
            $uploadedFile = $request->file('pechat_file');
            if ($uploadedFile && $uploadedFile->isValid()) {
                $extension = strtolower($uploadedFile->getClientOriginalExtension() ?: $uploadedFile->extension() ?: 'png');
                $filename = 'firma_stamp_' . date('Ymd_His') . '_' . uniqid() . '.' . $extension;
                $path = $uploadedFile->storeAs('files/firma', $filename, 'public');
                $pechat = $path ?: $pechat;
            }
        }

        return [
            'name' => $validated['name'],
            'regnum' => $validated['regnum'] ?? '',
            'inn' => $validated['inn'] ?? '',
            'schet' => $validated['schet'] ?? '',
            'bank' => $validated['bank'] ?? '',
            'mfo' => $validated['mfo'] ?? '',
            'town' => $validated['town'] ?? '',
            'address' => $validated['address'] ?? '',
            'map' => $validated['map'] ?? '',
            'view' => $validated['view'] ?? '',
            'phone' => $validated['phone'] ?? '',
            'direktor' => $validated['direktor'] ?? '',
            'pidpys' => $pidpys,
            'pechat' => $pechat,
        ];
    }

    private function validateBannerCarousel(Request $request, ?object $existing = null): array
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'subtitle' => 'nullable|string|max:5000',
            'button_text' => 'nullable|string|max:120',
            'link_url' => 'nullable|string|max:500',
            'sort_order' => 'nullable|integer|min:0',
            'vision' => 'nullable|boolean',
            'image_path' => 'nullable|string|max:255',
            'image_file' => 'nullable|image|max:6144',
        ]);

        $imagePath = trim((string) ($validated['image_path'] ?? ($existing->image_path ?? '')));
        if ($request->hasFile('image_file')) {
            $uploadedFile = $request->file('image_file');
            if ($uploadedFile && $uploadedFile->isValid()) {
                $extension = strtolower($uploadedFile->getClientOriginalExtension() ?: $uploadedFile->extension() ?: 'jpg');
                $filename = 'banner_' . date('Ymd_His') . '_' . uniqid() . '.' . $extension;
                $path = $uploadedFile->storeAs('files/banners', $filename, 'public');
                $imagePath = $path ?: $imagePath;
            }
        }

        return [
            'title' => trim((string) ($validated['title'] ?? '')),
            'subtitle' => trim((string) ($validated['subtitle'] ?? '')),
            'button_text' => trim((string) ($validated['button_text'] ?? '')),
            'link_url' => trim((string) ($validated['link_url'] ?? '')),
            'sort_order' => (int) ($validated['sort_order'] ?? ($existing->sort_order ?? 0)),
            'vision' => $request->boolean('vision', (bool) ($existing->vision ?? true)) ? 1 : 0,
            'image_path' => $imagePath,
        ];
    }

    private function validateConfRecord(Request $request, ?object $existing = null): array
    {
        $hasCommissionColumn = Schema::hasColumn('conf', 'commission');
        $validated = $request->validate([
            'type' => 'required|string',
            'name' => 'required|string|max:255',
            'color' => 'nullable|string|max:255',
            'status' => 'nullable|string',
            'vision' => 'nullable|string',
            'commission' => $hasCommissionColumn ? 'nullable|numeric|min:0|max:3' : 'nullable',
            'doc' => 'nullable|string|max:100',
            'constanta' => 'nullable|string|max:255',
            'currency' => 'nullable|string|max:10',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:255',
            'google_map' => 'nullable|string|max:65535',
            'foto' => 'nullable|string|max:255',
            'foto_file' => 'nullable|image|max:4096',
            'is_default' => 'nullable|boolean',
        ]);

        $type = (string) ($validated['type'] ?? '');
        $currency = $this->normalizeCurrencyCode($validated['currency'] ?? 'UAH');
        $vision = $validated['vision'] ?? '1';
        if ($type === 'web3_token') {
            $vision = Conf::normalizeWeb3ChainIdToDecimalString($vision) ?? $vision;
        }
        if ($type === 'currency') {
            $currency = $this->normalizeCurrencyCode($validated['name'] ?? $currency);
        }
        if (($type === 'oplata' || $type === 'deposit') && Schema::hasColumn('conf', 'currency')) {
            $availableCurrencies = $this->currencyCodesForFirma(session('fid', ''));
            if ($availableCurrencies->isNotEmpty() && ! $availableCurrencies->contains($currency)) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'currency' => 'Валюта должна быть выбрана из справочника валют.',
                ]);
            }
        }

        $data = [
            'name' => $type === 'currency' ? $currency : trim((string) ($validated['name'] ?? '')),
            'type' => $type,
            'color' => trim((string) ($validated['color'] ?? '')),
            'status' => (string) ($validated['status'] ?? '1'),
            'vision' => (string) $vision,
            'constanta' => (string) ($validated['constanta'] ?? '0'),
        ];

        if (Schema::hasColumn('conf', 'is_default') && in_array($type, ['sklads', 'oplata'], true)) {
            $data['is_default'] = $request->boolean('is_default') ? 1 : 0;
        }

        if (Schema::hasColumn('conf', 'currency')) {
            $data['currency'] = $type === 'oplata' || $type === 'deposit' || $type === 'currency' ? $currency : '';
        }

        if ($hasCommissionColumn) {
            $data['commission'] = array_key_exists('commission', $validated) && $validated['commission'] !== null
                ? round((float) $validated['commission'], 4)
                : null;
        }

        if (Schema::hasColumn('conf', 'doc')) {
            if ($type === 'reestr') {
                $data['doc'] = Conf::normalizePaymentDocFlags($validated['doc'] ?? '');
            } else {
                $data['doc'] = trim((string) ($validated['doc'] ?? ''));
            }
        }

        if ($type === 'sklads') {
            $foto = trim((string) ($validated['foto'] ?? ($existing->foto ?? '')));
            if ($request->hasFile('foto_file')) {
                $uploadedFile = $request->file('foto_file');
                if ($uploadedFile && $uploadedFile->isValid()) {
                    $extension = strtolower($uploadedFile->getClientOriginalExtension() ?: $uploadedFile->extension() ?: 'jpg');
                    $filename = 'office_' . date('Ymd_His') . '_' . uniqid() . '.' . $extension;
                    $path = $uploadedFile->storeAs('files/sklads', $filename, 'public');
                    $foto = $path ?: $foto;
                }
            }

            if (Schema::hasColumn('conf', 'phone')) {
                $data['phone'] = trim((string) ($validated['phone'] ?? ''));
            }

            if (Schema::hasColumn('conf', 'address')) {
                $data['address'] = trim((string) ($validated['address'] ?? ''));
            }

            if (Schema::hasColumn('conf', 'google_map')) {
                $data['google_map'] = trim((string) ($validated['google_map'] ?? ''));
            }

            if (Schema::hasColumn('conf', 'foto')) {
                $data['foto'] = $foto;
            }
        }

        return $data;
    }

    private function syncDefaultConfRecord(int $id, array $data): void
    {
        if (
            $id <= 0
            || ! Schema::hasColumn('conf', 'is_default')
            || ! in_array((string) ($data['type'] ?? ''), ['sklads', 'oplata'], true)
            || (int) ($data['is_default'] ?? 0) !== 1
        ) {
            return;
        }

        DB::table('conf')
            ->where('id', '<>', $id)
            ->where('type', (string) $data['type'])
            ->where('firma', session('fid', ''))
            ->update(['is_default' => 0]);
    }

    private function decorateConfItem(object $item, string $type): object
    {
        if ($type === 'reestr') {
            $item = Conf::decoratePaymentType($item);
        }

        if ($type === 'sklads') {
            $item->foto_preview = MediaUrl::image((string) ($item->foto ?? ''));
        }

        if ($type === 'web3_token') {
            $item->commission = property_exists($item, 'commission') && $item->commission !== null
                ? (float) $item->commission
                : 0.0;
        }

        if ($type === 'oplata' || $type === 'deposit') {
            $item->currency = $this->normalizeCurrencyCode($item->currency ?? 'UAH');
        }

        if (in_array($type, ['sklads', 'oplata'], true)) {
            $item->is_default = property_exists($item, 'is_default') ? (int) ($item->is_default ?? 0) : 0;
        }

        if ($type === 'currency') {
            $item->currency = $this->normalizeCurrencyCode($item->currency ?? $item->name ?? 'UAH');
        }

        return $item;
    }

    private function normalizeCurrencyCode(mixed $value): string
    {
        $currency = strtoupper(preg_replace('/[^A-Z0-9]/', '', (string) $value) ?? '');

        return $currency !== '' ? substr($currency, 0, 10) : 'UAH';
    }

    private function currencyCodesForFirma(mixed $fid): \Illuminate\Support\Collection
    {
        return DB::table('conf')
            ->where('type', 'currency')
            ->where('firma', $fid)
            ->orderBy('name')
            ->get(['name', 'currency'])
            ->map(fn ($item) => $this->normalizeCurrencyCode($item->currency ?? $item->name ?? ''))
            ->filter()
            ->unique()
            ->values();
    }

    private function validateProject(Request $request): array
    {
        $validated = $request->validate([
            'num' => 'nullable|integer|min:0',
            'name' => 'required|string|max:50',
            'phone' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'url' => 'nullable|string|max:65535',
            'telegram' => 'nullable|string|max:65535',
            'instagram' => 'nullable|string|max:65535',
            'twitter' => 'nullable|string|max:65535',
            'facebook' => 'nullable|string|max:65535',
            'userid' => 'nullable|integer|min:0',
            'foto' => 'nullable|string|max:255',
            'foto_header' => 'nullable|string|max:255',
            'foto_footer' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:65535',
            'web' => 'nullable|boolean',
            'hit' => 'nullable|boolean',
            'constanta' => 'nullable|boolean',
            'htmlkeys' => 'nullable|string|max:65535',
            'foto_file' => 'nullable|image|max:4096',
            'foto_header_file' => 'nullable|image|max:4096',
            'foto_footer_file' => 'nullable|image|max:4096',
        ]);

        $foto = trim((string) ($validated['foto'] ?? ''));
        if ($request->hasFile('foto_file')) {
            $uploadedFile = $request->file('foto_file');
            if ($uploadedFile && $uploadedFile->isValid()) {
                $extension = strtolower($uploadedFile->getClientOriginalExtension() ?: $uploadedFile->extension() ?: 'png');
                $filename = 'project_foto_' . date('Ymd_His') . '_' . uniqid() . '.' . $extension;
                $path = $uploadedFile->storeAs('files/projects', $filename, 'public');
                $foto = $path ?: $foto;
            }
        }

        $fotoHeader = trim((string) ($validated['foto_header'] ?? ''));
        if ($request->hasFile('foto_header_file')) {
            $uploadedFile = $request->file('foto_header_file');
            if ($uploadedFile && $uploadedFile->isValid()) {
                $extension = strtolower($uploadedFile->getClientOriginalExtension() ?: $uploadedFile->extension() ?: 'png');
                $filename = 'project_header_' . date('Ymd_His') . '_' . uniqid() . '.' . $extension;
                $path = $uploadedFile->storeAs('files/projects', $filename, 'public');
                $fotoHeader = $path ?: $fotoHeader;
            }
        }

        $fotoFooter = trim((string) ($validated['foto_footer'] ?? ''));
        if ($request->hasFile('foto_footer_file')) {
            $uploadedFile = $request->file('foto_footer_file');
            if ($uploadedFile && $uploadedFile->isValid()) {
                $extension = strtolower($uploadedFile->getClientOriginalExtension() ?: $uploadedFile->extension() ?: 'png');
                $filename = 'project_footer_' . date('Ymd_His') . '_' . uniqid() . '.' . $extension;
                $path = $uploadedFile->storeAs('files/projects', $filename, 'public');
                $fotoFooter = $path ?: $fotoFooter;
            }
        }

        $projectColumns = Schema::hasTable('project')
            ? Schema::getColumnListing('project')
            : [];

        $payload = [
            'num' => (int) ($validated['num'] ?? 0),
            'name' => trim((string) ($validated['name'] ?? '')),
            'telegram' => trim((string) ($validated['telegram'] ?? '')),
            'instagram' => trim((string) ($validated['instagram'] ?? '')),
            'twitter' => trim((string) ($validated['twitter'] ?? '')),
            'facebook' => trim((string) ($validated['facebook'] ?? '')),
            'userid' => (int) ($validated['userid'] ?? 0),
            'foto' => $foto,
            'foto_header' => $fotoHeader,
            'foto_footer' => $fotoFooter,
            'description' => trim((string) ($validated['description'] ?? '')),
            'web' => $request->boolean('web') ? 1 : 0,
            'hit' => $request->boolean('hit') ? 1 : 0,
            'htmlkeys' => trim((string) ($validated['htmlkeys'] ?? '')),
        ];

        $projectPhone = trim((string) ($validated['phone'] ?? ''));
        $projectEmail = mb_strtolower(trim((string) ($validated['email'] ?? '')));
        $projectUrl = trim((string) ($validated['url'] ?? ''));
        if (in_array('phone', $projectColumns, true)) {
            $payload['phone'] = $projectPhone;
        }
        if (in_array('email', $projectColumns, true)) {
            $payload['email'] = $projectEmail === '' ? null : $projectEmail;
        }
        if (in_array('url', $projectColumns, true)) {
            $payload['url'] = $projectUrl;
        }
        if (in_array('constanta', $projectColumns, true)) {
            $payload['constanta'] = $request->boolean('constanta') ? 1 : 0;
        }

        return $payload;
    }

    /**
     * Видалення проєкту: users.email (профіль) має збігатися з project.email, без урахування регістру.
     */
    private function userCanDeleteProjectByEmail(?object $user, Project $project): bool
    {
        if (! $user instanceof User) {
            return false;
        }
        if (! Schema::hasColumn('project', 'email') || ! Schema::hasColumn('users', 'email')) {
            return false;
        }
        $projectEmail = mb_strtolower(trim((string) ($project->email ?? '')));
        $userEmail = mb_strtolower(trim((string) ($user->email ?? '')));

        return $projectEmail !== '' && $userEmail !== '' && $projectEmail === $userEmail;
    }

    private function normalizeProject(Project $project, ?object $user): array
    {
        $payload = Project::decorateMedia($project)->toArray();
        $payload['phone'] = (string) ($payload['phone'] ?? '');
        $payload['email'] = (string) ($payload['email'] ?? '');
        $payload['url'] = (string) ($payload['url'] ?? '');
        $payload['constanta'] = (int) ($payload['constanta'] ?? 0);
        $payload['can_delete'] = $this->userCanDeleteProjectByEmail($user, $project);

        return $payload;
    }

    private function denyInvalidManagerAiSecret(Request $request)
    {
        $expectedSecret = trim((string) config('services.manager_ai.bridge_secret', ''));
        $providedSecret = trim((string) (
            $request->header('X-ManagerAI-Bridge-Secret')
            ?: $request->header('X-Manager-AI-Bridge-Secret')
            ?: ''
        ));

        if ($expectedSecret === '' || $providedSecret === '' || ! hash_equals($expectedSecret, $providedSecret)) {
            return response()->json(['message' => 'Invalid ManagerAI bridge secret.'], 403);
        }

        return null;
    }

    /**
     * Після перемикання проєкту (firma = project.id): знаходимо users.id за email (спільний для клієнта в усіх проєктах),
     * інакше fallback — ensureUserRowForProjectFirma (клон / існуючий рядок).
     */
    private function resolveUserIdAfterProjectSwitch(User $authUser, Project $project): ?int
    {
        if (!Schema::hasTable('users') || !Schema::hasColumn('users', 'firma')) {
            return null;
        }

        $email = $this->resolveUserEmailForProjectMetadata($authUser) ?? '';
        if ($email === '' && Schema::hasColumn('project', 'email')) {
            $email = mb_strtolower(trim((string) ($project->email ?? '')));
        }

        if ($email !== '' && Schema::hasColumn('users', 'email')) {
            $foundId = User::query()
                ->where('firma', (string) $project->id)
                ->whereRaw('LOWER(TRIM(email)) = ?', [$email])
                ->value('id');
            if ($foundId) {
                return (int) $foundId;
            }
        }

        return $this->ensureUserRowForProjectFirma($authUser, (string) $project->id);
    }

    /**
     * Пошта для project.email при створенні: users.email, інакше login якщо це валідний email (legacy).
     */
    private function resolveUserEmailForProjectMetadata(?object $user): ?string
    {
        if (! $user instanceof User) {
            return null;
        }

        if (Schema::hasColumn('users', 'email')) {
            $email = mb_strtolower(trim((string) ($user->email ?? '')));
            if ($email !== '') {
                return $email;
            }
        }

        if (User::hasUsersColumn('login')) {
            $login = trim((string) ($user->login ?? ''));
            if ($login !== '' && filter_var($login, FILTER_VALIDATE_EMAIL)) {
                return mb_strtolower($login);
            }
        }

        return null;
    }

    private function ensureProjectUserCopy(Project $project): ?int
    {
        $user = $this->currentUser();
        if (! $user instanceof User) {
            return null;
        }

        return $this->ensureProjectUserCopyForUser($project, $user);
    }

    private function ensureProjectUserCopyForUser(Project $project, User $user): ?int
    {
        return $this->ensureUserRowForProjectFirma($user, (string) $project->id);
    }

    private function canUseManagerAiProjectApi(Request $request): bool
    {
        $secret = trim((string) config('services.manager_ai.bridge_secret', ''));
        if ($secret === '') {
            return false;
        }

        $provided = trim((string) $request->header('X-ManagerAI-Bridge-Secret', ''));
        if ($provided === '') {
            $bearer = trim((string) $request->bearerToken());
            if ($bearer !== '') {
                $provided = $bearer;
            }
        }

        return $provided !== '' && hash_equals($secret, $provided);
    }

    private function managerAiEmailFromRequest(Request $request): ?string
    {
        $email = mb_strtolower(trim((string) (
            $request->input('email')
            ?: $request->input('manager_ai_email')
            ?: $request->input('user_email')
            ?: $request->header('X-ManagerAI-User-Email')
        )));

        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
    }

    private function managerAiProjectOwnerByEmail(string $email): ?User
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'email')) {
            return null;
        }

        return User::query()
            ->whereRaw('LOWER(TRIM(email)) = ?', [$email])
            ->orderBy('id')
            ->first();
    }

    /**
     * Один обліковий запис (email / телефон / login) може мати окремий рядок users для кожного проєкту (firma = project.id).
     * Повертає id користувача у цьому проєкті: існуючий або клон поточного рядка.
     */
    private function ensureUserRowForProjectFirma(User $user, string $firmaId): ?int
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'firma')) {
            return null;
        }

        $firmaId = trim($firmaId);
        if ($firmaId === '') {
            return null;
        }

        $existingId = $this->findUserIdInFirma($user, $firmaId);
        if ($existingId !== null) {
            return $existingId;
        }

        $source = DB::table('users')->where('id', $user->id)->first();
        if (! $source) {
            return null;
        }

        $payload = (array) $source;
        unset($payload['id']);

        $payload['firma'] = $firmaId;

        if (Schema::hasColumn('users', 'updated_at')) {
            $payload['updated_at'] = now();
        }

        if (Schema::hasColumn('users', 'created_at')) {
            $payload['created_at'] = now();
        }

        return (int) DB::table('users')->insertGetId(User::filterUsersColumns($payload));
    }

    private function findUserIdInFirma(User $user, string $firmaId): ?int
    {
        $firmaId = trim($firmaId);
        if ($firmaId === '') {
            return null;
        }

        if ((string) ($user->firma ?? '') === $firmaId) {
            return (int) $user->id;
        }

        $email = trim((string) ($user->email ?? ''));
        if ($email !== '' && Schema::hasColumn('users', 'email')) {
            $id = User::query()->where('firma', $firmaId)->where('email', $email)->value('id');
            if ($id) {
                return (int) $id;
            }
        }

        $phone = trim((string) ($user->phone ?? ''));
        if ($phone !== '' && Schema::hasColumn('users', 'phone')) {
            $byPhone = $this->findUserIdByPhoneInFirma($firmaId, $phone);
            if ($byPhone !== null) {
                return $byPhone;
            }
        }

        $login = trim((string) ($user->login ?? ''));
        if ($login !== '' && User::hasUsersColumn('login')) {
            $id = User::query()->where('firma', $firmaId)->where('login', $login)->value('id');
            if ($id) {
                return (int) $id;
            }
        }

        return null;
    }

    private function findUserIdByPhoneInFirma(string $firmaId, string $phone): ?int
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        $id = User::query()
            ->where('firma', $firmaId)
            ->where(function ($b) use ($phone, $digits) {
                $b->where('phone', $phone);

                if ($digits === '') {
                    return;
                }

                $normalizedPhoneSql = "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(phone, ' ', ''), '-', ''), '(', ''), ')', ''), '+', '')";

                $b->orWhereRaw("{$normalizedPhoneSql} = ?", [$digits]);

                if (str_starts_with($digits, '38')) {
                    $b->orWhereRaw("{$normalizedPhoneSql} = ?", [substr($digits, 2)]);
                } elseif (str_starts_with($digits, '0')) {
                    $b->orWhereRaw("{$normalizedPhoneSql} = ?", ['38' . $digits]);
                }
            })
            ->value('id');

        return $id ? (int) $id : null;
    }

    private function fieldBaseQuery($fid, string $keyfield)
    {
        return DB::table('field')
            ->where('keyfield', $keyfield)
            ->where('firma', $fid);
    }

    private function fieldChildrenQuery($fid, string $keyfield, string $parentId)
    {
        $query = $this->fieldBaseQuery($fid, $keyfield);

        if ($keyfield !== 'catalog') {
            return $query;
        }

        if ($parentId === '0') {
            $query->where(function ($nested) {
                $nested->where('idkeyfield', '0')
                    ->orWhere('idkeyfield', 0)
                    ->orWhereNull('idkeyfield')
                    ->orWhere('idkeyfield', '');
            });
        } else {
            $query->where('idkeyfield', $parentId);
        }

        return $query;
    }

    private function fieldFind($fid, string $keyfield, $id)
    {
        return $this->fieldBaseQuery($fid, $keyfield)->where('id', $id)->first();
    }

    /**
     * Категорії каталогу, доступні для прив’язки фільтрів: firma = поточний проєкт або 0 (спільні).
     */
    private function fieldFilterCatalogBaseQuery($fid)
    {
        $firma = ($fid === null || $fid === '') ? 0 : (int) $fid;

        return DB::table('field')
            ->where('keyfield', 'catalog')
            ->where(function ($nested) use ($firma) {
                $nested->where('firma', $firma);
                if ($firma !== 0) {
                    $nested->orWhere('firma', 0);
                }
            });
    }

    private function fieldFilterCatalogFind($fid, $catalogId): ?object
    {
        if (! Schema::hasTable('field')) {
            return null;
        }
        $id = (int) $catalogId;
        if ($id <= 0) {
            return null;
        }

        return $this->fieldFilterCatalogBaseQuery($fid)->where('id', $id)->first();
    }

    private function fieldColumns(): array
    {
        return Schema::hasTable('field') ? Schema::getColumnListing('field') : [];
    }

    private function validateField(Request $request, $fid, string $keyfield, string $parentId, bool $isUpdate = false, ?object $existing = null): array
    {
        $request->merge([
            'news_catalog_id' => $this->normalizeOptionalInteger($request->input('news_catalog_id')),
        ]);

        $validated = $request->validate([
            'name_ru' => 'required|string|max:255',
            'name_ua' => 'nullable|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'link' => 'nullable|string|max:35',
            'foto1' => 'nullable|string|max:255',
            'foto1_file' => 'nullable|file|max:10240',
            'news_catalog_id' => [
                'nullable',
                'integer',
                Rule::exists('news', 'id')->where(function ($query) use ($fid) {
                    $query->where(function ($nested) use ($fid) {
                        $nested->where('firma', (int) $fid)
                            ->orWhere('firma', 0);
                    });
                }),
            ],
            'description_ru' => 'nullable|string|max:5000',
            'description_ua' => 'nullable|string|max:5000',
            'description_en' => 'nullable|string|max:5000',
            'num' => 'nullable|integer|min:0',
            'visible' => 'nullable|boolean',
            'firstpage' => 'nullable|boolean',
        ]);

        $columns = $this->fieldColumns();
        $payload = [];

        if (in_array('keyfield', $columns, true)) {
            $payload['keyfield'] = $keyfield;
        }
        if (in_array('firma', $columns, true)) {
            $payload['firma'] = $fid;
        }
        if ($keyfield === 'catalog' && in_array('idkeyfield', $columns, true)) {
            $payload['idkeyfield'] = $parentId;
        } elseif ($keyfield !== 'catalog' && in_array('idkeyfield', $columns, true)) {
            $payload['idkeyfield'] = 0;
        }
        if (in_array('val', $columns, true)) {
            $payload['val'] = $validated['name_ru'];
        }
        if (in_array('valua', $columns, true)) {
            $payload['valua'] = $validated['name_ua'] ?? '';
        } elseif (in_array('val_ua', $columns, true)) {
            $payload['val_ua'] = $validated['name_ua'] ?? '';
        }
        if (in_array('valen', $columns, true)) {
            $payload['valen'] = $validated['name_en'] ?? '';
        } elseif (in_array('val_en', $columns, true)) {
            $payload['val_en'] = $validated['name_en'] ?? '';
        }
        if (in_array('description', $columns, true)) {
            $payload['description'] = $validated['description_ru'] ?? '';
        }
        if (in_array('descriptionua', $columns, true)) {
            $payload['descriptionua'] = $validated['description_ua'] ?? '';
        } elseif (in_array('description_ua', $columns, true)) {
            $payload['description_ua'] = $validated['description_ua'] ?? '';
        }
        if (in_array('descriptionen', $columns, true)) {
            $payload['descriptionen'] = $validated['description_en'] ?? '';
        } elseif (in_array('description_en', $columns, true)) {
            $payload['description_en'] = $validated['description_en'] ?? '';
        }
        if (in_array('link', $columns, true)) {
            $payload['link'] = $validated['link'] ?? '';
        }
        if (in_array('news_catalog_id', $columns, true)) {
            $payload['news_catalog_id'] = $validated['news_catalog_id'] ?? null;
        } elseif (in_array('nw', $columns, true)) {
            $payload['nw'] = $validated['news_catalog_id'] ?? 0;
        }
        if (in_array('foto1', $columns, true)) {
            $foto1 = trim((string) ($validated['foto1'] ?? ($existing->foto1 ?? '')));
            if ($request->hasFile('foto1_file')) {
                $uploadedFile = $request->file('foto1_file');
                if ($uploadedFile && $uploadedFile->isValid()) {
                    $extension = strtolower($uploadedFile->getClientOriginalExtension() ?: $uploadedFile->extension() ?: 'bin');
                    $filename = 'cf_' . date('YmdHis') . '_' . substr(uniqid('', true), -8) . '.' . $extension;
                    $path = $uploadedFile->storeAs('files/catalog', $filename, 'public');
                    $foto1 = $path ?: $foto1;
                }
            }
            $payload['foto1'] = $foto1;
        }
        if (in_array('visible', $columns, true)) {
            $payload['visible'] = $request->boolean('visible') ? '1' : '0';
        }
        if (in_array('firstpage', $columns, true)) {
            $payload['firstpage'] = $request->boolean('firstpage') ? '1' : '0';
        }
        if (in_array('num', $columns, true)) {
            $payload['num'] = array_key_exists('num', $validated) && $validated['num'] !== null
                ? (int) $validated['num']
                : (!$isUpdate
                    ? (int) $this->fieldChildrenQuery($fid, $keyfield, $parentId)->max('num') + 1
                    : (property_exists($existing, 'num') ? (int) ($existing->num ?? 0) : 0));
        }

        if ($isUpdate && $existing) {
            if (in_array('visible', $columns, true) && !isset($payload['visible'])) {
                $payload['visible'] = property_exists($existing, 'visible') ? ($existing->visible ?? '1') : '1';
            }
            if (in_array('firstpage', $columns, true) && !isset($payload['firstpage'])) {
                $payload['firstpage'] = property_exists($existing, 'firstpage') ? ($existing->firstpage ?? '0') : '0';
            }
        }

        return $payload;
    }

    private function fieldBreadcrumb($fid, string $keyfield, string $parentId): array
    {
        $rootLabel = $keyfield === 'city' ? 'Регионы' : 'Категории/Надписи';
        $trail = [['id' => 0, 'name' => $rootLabel]];
        if ($keyfield !== 'catalog') {
            return $trail;
        }

        if ($parentId === '0') {
            return $trail;
        }

        $visited = [];
        $currentId = $parentId;

        while ($currentId !== '0' && $currentId !== '' && !in_array($currentId, $visited, true)) {
            $visited[] = $currentId;
            $item = $this->fieldFind($fid, $keyfield, $currentId);
            if (!$item) {
                break;
            }

            array_splice($trail, 1, 0, [[
                'id' => (int) $item->id,
                'name' => $item->val ?? ('#' . $item->id),
            ]]);

            $currentId = (string) ($item->idkeyfield ?? '0');
            if ($currentId === '') {
                $currentId = '0';
            }
        }

        return $trail;
    }

    private function normalizeFieldItem(object $item, $childCounts, array $fieldColumns): array
    {
        $descriptionRu = in_array('description', $fieldColumns, true) ? ($item->description ?? '') : '';
        $descriptionUa = in_array('descriptionua', $fieldColumns, true)
            ? ($item->descriptionua ?? '')
            : (in_array('description_ua', $fieldColumns, true) ? ($item->description_ua ?? '') : '');
        $descriptionEn = in_array('descriptionen', $fieldColumns, true)
            ? ($item->descriptionen ?? '')
            : (in_array('description_en', $fieldColumns, true) ? ($item->description_en ?? '') : '');

        return [
            'id' => (int) $item->id,
            'keyfield' => (string) ($item->keyfield ?? ''),
            'parent_id' => (string) (($item->idkeyfield ?? '0') === '' ? '0' : ($item->idkeyfield ?? '0')),
            'name_ru' => $item->val ?? '',
            'name_ua' => in_array('valua', $fieldColumns, true)
                ? ($item->valua ?? '')
                : (in_array('val_ua', $fieldColumns, true) ? ($item->val_ua ?? '') : ''),
            'name_en' => in_array('valen', $fieldColumns, true)
                ? ($item->valen ?? '')
                : (in_array('val_en', $fieldColumns, true) ? ($item->val_en ?? '') : ''),
            'description_ru' => $descriptionRu,
            'description_ua' => $descriptionUa,
            'description_en' => $descriptionEn,
            'link' => in_array('link', $fieldColumns, true) ? ($item->link ?? '') : '',
            'news_catalog_id' => in_array('news_catalog_id', $fieldColumns, true)
                ? ($item->news_catalog_id ? (int) $item->news_catalog_id : null)
                : (in_array('nw', $fieldColumns, true) && (int) ($item->nw ?? 0) > 0 ? (int) $item->nw : null),
            'foto1' => in_array('foto1', $fieldColumns, true) ? ($item->foto1 ?? '') : '',
            'foto1_url' => in_array('foto1', $fieldColumns, true) ? MediaUrl::storage((string) ($item->foto1 ?? '')) : null,
            'children_count' => (int) ($childCounts[(string) $item->id] ?? 0),
            'num' => (int) (property_exists($item, 'num') ? ($item->num ?? 0) : 0),
            'visible' => (string) (property_exists($item, 'visible') ? ($item->visible ?? '1') : '1'),
            'firstpage' => (string) (property_exists($item, 'firstpage') ? ($item->firstpage ?? '0') : '0'),
        ];
    }

    private function normalizeOptionalInteger($value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        return null;
    }

    private function resolveFieldKeyfield(Request $request): string
    {
        $keyfield = strtolower(trim((string) $request->input('keyfield', 'catalog')));

        return in_array($keyfield, ['catalog', 'city'], true) ? $keyfield : 'catalog';
    }

    private function fieldIndexByKeyfield(Request $request, string $keyfield)
    {
        if (!Schema::hasTable('field')) {
            return response()->json([
                'items' => [],
                'breadcrumb' => $this->fieldBreadcrumb(session('fid', ''), $keyfield, '0'),
                'currentParentId' => '0',
                'currentParent' => null,
                'keyfield' => $keyfield,
                'total' => 0,
            ]);
        }

        $fid = session('fid', '');
        $parentId = $keyfield === 'catalog'
            ? (string) $request->input('parent_id', '0')
            : '0';
        $parentId = $parentId === '' ? '0' : $parentId;
        $fieldColumns = $this->fieldColumns();

        $itemsQuery = $this->fieldChildrenQuery($fid, $keyfield, $parentId);
        if (in_array('num', $fieldColumns, true)) {
            $itemsQuery->orderBy('num');
        }
        if (in_array('val', $fieldColumns, true)) {
            $itemsQuery->orderBy('val');
        } else {
            $itemsQuery->orderBy('id');
        }
        $items = $itemsQuery->get();

        $childCounts = $keyfield === 'catalog'
            ? $this->fieldBaseQuery($fid, $keyfield)
                ->selectRaw('idkeyfield, COUNT(*) as total')
                ->groupBy('idkeyfield')
                ->pluck('total', 'idkeyfield')
            : collect();

        $payload = $items->map(function ($item) use ($childCounts, $fieldColumns) {
            return $this->normalizeFieldItem($item, $childCounts, $fieldColumns);
        })->values();

        $currentParent = $keyfield === 'catalog' && $parentId !== '0'
            ? $this->fieldFind($fid, $keyfield, $parentId)
            : null;

        return response()->json([
            'items' => $payload,
            'breadcrumb' => $this->fieldBreadcrumb($fid, $keyfield, $parentId),
            'currentParentId' => $parentId,
            'currentParent' => $currentParent ? $this->normalizeFieldItem($currentParent, collect(), $fieldColumns) : null,
            'keyfield' => $keyfield,
            'total' => $payload->count(),
        ]);
    }

    private function fieldShowByKeyfield(Request $request, $id, string $keyfield)
    {
        if (!Schema::hasTable('field')) {
            return response()->json(['message' => 'Таблицю field не знайдено'], 404);
        }

        $fid = session('fid', '');
        $item = $this->fieldFind($fid, $keyfield, $id);
        if (!$item) {
            return response()->json(['message' => 'Запис не знайдено'], 404);
        }

        return response()->json($this->normalizeFieldItem($item, collect(), $this->fieldColumns()));
    }

    private function fieldStoreByKeyfield(Request $request, string $keyfield)
    {
        if (!Schema::hasTable('field')) {
            return response()->json(['success' => false, 'message' => 'Таблицю field не знайдено'], 404);
        }

        $fid = session('fid', '');
        $parentId = $keyfield === 'catalog'
            ? (string) $request->input('parent_id', '0')
            : '0';
        $parentId = $parentId === '' ? '0' : $parentId;

        if ($keyfield === 'catalog' && $parentId !== '0' && !$this->fieldFind($fid, $keyfield, $parentId)) {
            return response()->json(['success' => false, 'message' => 'Батьківську категорію не знайдено'], 404);
        }

        $data = $this->validateField($request, $fid, $keyfield, $parentId);
        $id = DB::table('field')->insertGetId($data);

        return response()->json(['success' => true, 'id' => $id]);
    }

    private function fieldUpdateByKeyfield(Request $request, $id, string $keyfield)
    {
        if (!Schema::hasTable('field')) {
            return response()->json(['success' => false, 'message' => 'Таблицю field не знайдено'], 404);
        }

        $fid = session('fid', '');
        $item = $this->fieldFind($fid, $keyfield, $id);
        if (!$item) {
            return response()->json(['success' => false, 'message' => 'Запис не знайдено'], 404);
        }

        $parentId = $keyfield === 'catalog'
            ? (string) ($request->input('parent_id', $item->idkeyfield ?? '0'))
            : '0';
        $parentId = $parentId === '' ? '0' : $parentId;

        if ($keyfield === 'catalog' && $parentId !== '0' && !$this->fieldFind($fid, $keyfield, $parentId)) {
            return response()->json(['success' => false, 'message' => 'Батьківську категорію не знайдено'], 404);
        }

        DB::table('field')
            ->where('id', $id)
            ->update($this->validateField($request, $fid, $keyfield, $parentId, true, $item));

        return response()->json(['success' => true]);
    }

    private function fieldDestroyByKeyfield(Request $request, $id, string $keyfield)
    {
        if (!Schema::hasTable('field')) {
            return response()->json(['success' => false, 'message' => 'Таблицю field не знайдено'], 404);
        }

        $fid = session('fid', '');
        $item = $this->fieldFind($fid, $keyfield, $id);
        if (!$item) {
            return response()->json(['success' => false, 'message' => 'Запис не знайдено'], 404);
        }

        if ($keyfield === 'catalog') {
            $hasChildren = $this->fieldBaseQuery($fid, $keyfield)
                ->where('idkeyfield', (string) $id)
                ->exists();

            if ($hasChildren) {
                return response()->json(['success' => false, 'message' => 'Спочатку видаліть або перенесіть підкатегорії'], 422);
            }

            if (Schema::hasTable('comp')) {
                $usedInGoods = DB::table('comp')
                    ->where('firma', $fid)
                    ->where(function ($query) use ($id) {
                        $query->where('idcaption', (string) $id)
                            ->orWhere('idglava', (string) $id);
                    })
                    ->exists();

                if ($usedInGoods) {
                    return response()->json(['success' => false, 'message' => 'Категорія використовується в товарах'], 422);
                }
            }
        }

        DB::table('field')->where('id', $id)->delete();

        return response()->json(['success' => true]);
    }

    public function sitemapStatus(SitemapService $sitemapService)
    {
        $fid = (int) session('fid', 0);

        return response()->json([
            'exists' => $sitemapService->exists($fid > 0 ? $fid : null),
            'public_url' => $sitemapService->getPublicUrl($fid > 0 ? $fid : null),
            'last_modified_at' => $sitemapService->lastModifiedAt($fid > 0 ? $fid : null),
            'fid' => $fid > 0 ? $fid : null,
        ]);
    }

    public function sitemapGenerate(SitemapService $sitemapService, AutoAgentSitemapBuildService $autoAgentSitemapBuildService)
    {
        $fid = (int) session('fid', 0);

        try {
            $result = $sitemapService->generate($fid > 0 ? $fid : null);
            $autoAgentSitemap = $autoAgentSitemapBuildService->build($fid > 0 ? $fid : null, $result['path'] ?? null);
            $message = 'Sitemap успішно згенеровано';
            if (($autoAgentSitemap['status'] ?? null) === 'completed') {
                $message .= '; AutoAgent sitemap.xml оновлено';
            } elseif (($autoAgentSitemap['status'] ?? null) === 'failed') {
                $message .= '; AutoAgent sitemap.xml не оновлено: ' . ($autoAgentSitemap['message'] ?? 'unknown error');
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'fid' => $result['fid'],
                'public_url' => $result['url'],
                'frontend_url' => $result['frontend_url'],
                'path' => $result['path'],
                'last_modified_at' => $sitemapService->lastModifiedAt($result['fid']),
                'autoagent_sitemap' => $autoAgentSitemap,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Не вдалося згенерувати sitemap',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
