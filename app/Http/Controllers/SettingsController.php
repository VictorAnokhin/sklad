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
use App\Support\HoldingScope;
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

        $holdingProjectIds = collect(HoldingScope::projectIdsFor($fid))->map(fn ($id) => (int) $id);
        $participatingProjectIds = $this->creatorProjectIdsForUser($user)
            ->merge($this->employeeProjectIdsForUser($user));
        $defaultProjectIds = $holdingProjectIds->merge($participatingProjectIds)->filter()->unique()->values();
        $projectsCount = Schema::hasTable('project') && $defaultProjectIds->isNotEmpty()
            ? Project::query()->whereIn('id', $defaultProjectIds->all())->count()
            : 0;

        // Statuses — conf where type='status'
        $statuses = DB::table('conf')->where('type', 'status')->where('firma', $fid)->orderBy('name')->get();

        // Payment types are shared by every project.
        $reestrs = DB::table('conf')->where('type', 'reestr')->orderBy('name')->get()
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
        $accountCurrencies = $this->currencyCodesForAccounts();

        // FAQ — conf where type='faq'
        $faqs = DB::table('conf')->where('type', 'faq')->where('firma', $fid)->orderBy('name')->get();

        // Офисы — conf where type='sklads'
        $sklads = DB::table('conf')->where('type', 'sklads')->where('firma', $fid)->orderBy('name')->get();

        // Депозиты в настройках редактируются как старый справочник conf type='deposit'.
        $settingsDepositsUsePools = false;
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
        $profileBalances = $user
            ? $this->profileBalancesFromCache($user, $fid)
            : [];

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
            ? (int) Account::query()
                ->when(Schema::hasColumn('accounts', 'project_id'), fn ($query) => $query->whereNull('project_id'))
                ->count()
            : 0;
        $sitemapService = app(SitemapService::class);
        $sitemapInfo = [
            'public_url' => $sitemapService->getPublicUrl($fid !== '' ? (int) $fid : null),
            'exists' => $sitemapService->exists($fid !== '' ? (int) $fid : null),
            'last_modified_at' => $sitemapService->lastModifiedAt($fid !== '' ? (int) $fid : null),
        ];

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

        return view('settings.index', array_merge($data, compact('fid', 'projectsCount', 'statuses', 'reestrs', 'tgroups', 'tclients', 'oplatas', 'currencies', 'accountCurrencies', 'faqs', 'sklads', 'deposits', 'settingsDepositsUsePools', 'user', 'myCompanies', 'fieldCatalogTopCount', 'fieldCityCount', 'currentCounterpartyType', 'userWallets', 'profileBalances', 'bannerCarouselCount', 'knowledgeBaseCount', 'accountsCount', 'sitemapInfo', 'catalogNewsOptions', 'catalogFiltersGroupCount')));
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

        return redirect()->route('dashboard')->with('success', 'Активний проєкт змінено');
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

        $email = $this->resolveUserEmailForProjectMetadata($user) ?? '';
        $identityUserIds = collect([(int) $user->id]);
        if ($email !== '' && Schema::hasTable('users') && Schema::hasColumn('users', 'email')) {
            $identityUserIds = $identityUserIds
                ->merge(User::query()
                    ->whereRaw('LOWER(TRIM(email)) = ?', [$email])
                    ->pluck('id')
                    ->map(fn ($id) => (int) $id))
                ->unique()
                ->values();
        }

        if (Schema::hasTable('team_memberships')) {
            if (DB::table('team_memberships')
                ->whereIn('user_id', $identityUserIds->all())
                ->where('project_id', $projectId)
                ->exists()) {
                return true;
            }
        }

        if (
            Schema::hasColumn('project', 'userid')
            && (int) ($project->userid ?? 0) > 0
            && $identityUserIds->contains((int) $project->userid)
        ) {
            return true;
        }

        if (
            $email !== ''
            && Schema::hasColumn('project', 'email')
            && mb_strtolower(trim((string) ($project->email ?? ''))) === $email
        ) {
            return true;
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
        if ($type === 'deposit' && $this->settingsUsesPoolDeposits($fid)) {
            return response()->json($this->settingsPoolDepositRows());
        }

        $items = DB::table('conf')
            ->where('type', $type)
            ->when($type !== 'reestr', fn ($query) => $query->where('firma', $fid))
            ->orderBy('name')
            ->get()
            ->map(fn ($item) => $this->decorateConfItem($item, $type));
        return response()->json($items);
    }

    public function publicCurrencies(Request $request)
    {
        $fid = (string) $request->query('fid', config('app.fid', '12'));
        $items = DB::table('conf')
            ->where('type', 'currency')
            ->where('firma', $fid)
            ->orderBy('name')
            ->get()
            ->map(function ($item) {
                $code = $this->normalizeCurrencyCode($item->currency ?? $item->name ?? '');
                return [
                    'id' => (int) $item->id,
                    'code' => $code,
                    'name' => $code,
                    'description' => trim((string) ($item->descript ?? '')),
                ];
            })
            ->filter(fn ($item) => $item['code'] !== '')
            ->values();

        return response()->json(['data' => $items]);
    }

    public function publicFaq(Request $request)
    {
        $fid = (string) $request->query('fid', config('app.fid', '12'));
        $pageKey = trim((string) $request->query('page_key', $request->query('title', $request->query('page', ''))));
        $language = $this->normalizeFaqLanguage((string) $request->query('lang', 'ru'));

        $query = DB::table('conf')
            ->where('type', 'faq')
            ->where('firma', $fid)
            ->where('status', '1');

        if ($pageKey !== '') {
            $query->where('name', $pageKey);
        }

        $items = $query
            ->orderBy('id')
            ->get()
            ->map(function ($item) use ($language) {
                $translations = $this->faqTranslationsFromItem($item);

                return [
                    'id' => (int) $item->id,
                    'page_key' => trim((string) ($item->name ?? '')),
                    'page' => trim((string) ($item->name ?? '')),
                    'question' => $this->faqTranslatedText($translations['questions'], $language),
                    'answer' => $this->faqTranslatedText($translations['answers'], $language),
                    'questions' => $translations['questions'],
                    'answers' => $translations['answers'],
                ];
            })
            ->filter(fn ($item) => $item['page_key'] !== '' && $item['question'] !== '' && $item['answer'] !== '')
            ->values();

        return response()->json(['data' => $items]);
    }

    /**
     * GET /settings/api/{type}/{id}
     * Return a single conf record.
     */
    public function apiShow($type, $id)
    {
        $fid = session('fid', '');
        if ($type === 'deposit' && $this->settingsUsesPoolDeposits($fid)) {
            $item = $this->settingsPoolDepositRows()->firstWhere('id', (int) $id);
            if (! $item) {
                return response()->json(['message' => 'Не знайдено'], 404);
            }

            return response()->json($item);
        }

        $item = DB::table('conf')
            ->where('id', $id)
            ->where('type', $type)
            ->when($type !== 'reestr', fn ($query) => $query->where('firma', $fid))
            ->first();
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
        if ((string) $request->input('type') === 'deposit' && $this->settingsUsesPoolDeposits($fid)) {
            return response()->json(['success' => false, 'message' => 'Депозиты-пулы доступны только для просмотра.'], 403);
        }

        $data = $this->validateConfRecord($request);
        $data['hide'] = '0';
        $data['firma'] = (string) ($data['type'] ?? '') === 'reestr' ? 0 : $fid;

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
        if ((string) $request->input('type') === 'deposit' && $this->settingsUsesPoolDeposits($fid)) {
            return response()->json(['success' => false, 'message' => 'Депозиты-пулы доступны только для просмотра.'], 403);
        }

        $type = (string) $request->input('type');
        $exists = DB::table('conf')
            ->where('id', $id)
            ->where('type', $type)
            ->when($type !== 'reestr', fn ($query) => $query->where('firma', $fid))
            ->first();
        if (!$exists) return response()->json(['success' => false, 'message' => 'Не знайдено'], 404);
        $update = $this->validateConfRecord($request, $exists);
        if ($type === 'reestr') {
            $update['firma'] = 0;
        }

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
        $exists = DB::table('conf')->where('id', $id)->first();
        if (!$exists) return response()->json(['success' => false, 'message' => 'Не знайдено'], 404);
        if ((string) $exists->type !== 'reestr' && (string) $exists->firma !== (string) $fid) {
            return response()->json(['success' => false, 'message' => 'Не знайдено'], 404);
        }

        DB::table('conf')->where('id', $id)->delete();
        return response()->json(['success' => true]);
    }

    public function reportRulesIndex()
    {
        abort_unless(Schema::hasTable('report_classification_rules'), 404);

        $fid = session('fid', '');
        $items = DB::table('report_classification_rules')
            ->where(function ($query) use ($fid) {
                $query->whereNull('firma')
                    ->orWhere('firma', $fid);
            })
            ->orderBy('rule_group')
            ->orderBy('priority')
            ->orderBy('id')
            ->get()
            ->map(fn ($item) => $this->decorateReportRule($item));

        return response()->json($items);
    }

    public function reportRulesShow($id)
    {
        abort_unless(Schema::hasTable('report_classification_rules'), 404);

        $fid = session('fid', '');
        $item = DB::table('report_classification_rules')
            ->where('id', $id)
            ->where(function ($query) use ($fid) {
                $query->whereNull('firma')
                    ->orWhere('firma', $fid);
            })
            ->first();

        if (! $item) {
            return response()->json(['message' => 'Не знайдено'], 404);
        }

        return response()->json($this->decorateReportRule($item));
    }

    public function reportRulesStore(Request $request)
    {
        abort_unless(Schema::hasTable('report_classification_rules'), 404);

        $payload = $this->validateReportRule($request);
        $payload['firma'] = $request->boolean('is_global') ? null : (string) session('fid', '');
        $payload['created_at'] = now();
        $payload['updated_at'] = now();

        $id = DB::table('report_classification_rules')->insertGetId($payload);

        return response()->json(['success' => true, 'id' => $id]);
    }

    public function reportRulesUpdate(Request $request, $id)
    {
        abort_unless(Schema::hasTable('report_classification_rules'), 404);

        $fid = session('fid', '');
        $exists = DB::table('report_classification_rules')
            ->where('id', $id)
            ->where(function ($query) use ($fid) {
                $query->whereNull('firma')
                    ->orWhere('firma', $fid);
            })
            ->first();

        if (! $exists) {
            return response()->json(['success' => false, 'message' => 'Не знайдено'], 404);
        }

        $payload = $this->validateReportRule($request);
        $payload['firma'] = $request->boolean('is_global') ? null : (string) session('fid', '');
        $payload['updated_at'] = now();

        DB::table('report_classification_rules')->where('id', $id)->update($payload);

        return response()->json(['success' => true]);
    }

    public function reportRulesDestroy($id)
    {
        abort_unless(Schema::hasTable('report_classification_rules'), 404);

        $fid = session('fid', '');
        $exists = DB::table('report_classification_rules')
            ->where('id', $id)
            ->where(function ($query) use ($fid) {
                $query->whereNull('firma')
                    ->orWhere('firma', $fid);
            })
            ->first();

        if (! $exists) {
            return response()->json(['success' => false, 'message' => 'Не знайдено'], 404);
        }

        DB::table('report_classification_rules')->where('id', $id)->delete();

        return response()->json(['success' => true]);
    }

    private function validateReportRule(Request $request): array
    {
        $validated = $request->validate([
            'rule_group' => ['required', 'string', 'max:80'],
            'rule_key' => ['nullable', 'string', 'max:120'],
            'rule_type' => ['required', 'string', 'max:40'],
            'source_table' => ['nullable', 'string', 'max:80'],
            'source_field' => ['nullable', 'string', 'max:80'],
            'operator' => ['required', 'string', 'max:40'],
            'match_value' => ['required', 'string', 'max:255'],
            'target_value' => ['nullable', 'string', 'max:120'],
            'document_type' => ['nullable', 'string', 'max:40'],
            'direction' => ['nullable', 'string', 'max:40'],
            'priority' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'is_active' => ['nullable', 'boolean'],
            'meta' => ['nullable', 'array'],
        ]);

        return [
            'rule_group' => trim((string) $validated['rule_group']),
            'rule_key' => trim((string) ($validated['rule_key'] ?? '')),
            'rule_type' => trim((string) $validated['rule_type']),
            'source_table' => trim((string) ($validated['source_table'] ?? '')),
            'source_field' => trim((string) ($validated['source_field'] ?? '')),
            'operator' => trim((string) $validated['operator']),
            'match_value' => trim((string) $validated['match_value']),
            'target_value' => trim((string) ($validated['target_value'] ?? '')),
            'document_type' => trim((string) ($validated['document_type'] ?? '')),
            'direction' => trim((string) ($validated['direction'] ?? '')),
            'priority' => (int) ($validated['priority'] ?? 100),
            'is_active' => (bool) ($validated['is_active'] ?? true),
            'meta' => isset($validated['meta']) ? json_encode($validated['meta'], JSON_UNESCAPED_UNICODE) : null,
        ];
    }

    private function decorateReportRule(object $item): array
    {
        return [
            'id' => (int) $item->id,
            'firma' => $item->firma,
            'is_global' => $item->firma === null || $item->firma === '',
            'rule_group' => (string) ($item->rule_group ?? ''),
            'rule_key' => (string) ($item->rule_key ?? ''),
            'rule_type' => (string) ($item->rule_type ?? ''),
            'source_table' => (string) ($item->source_table ?? ''),
            'source_field' => (string) ($item->source_field ?? ''),
            'operator' => (string) ($item->operator ?? ''),
            'match_value' => (string) ($item->match_value ?? ''),
            'target_value' => (string) ($item->target_value ?? ''),
            'document_type' => (string) ($item->document_type ?? ''),
            'direction' => (string) ($item->direction ?? ''),
            'priority' => (int) ($item->priority ?? 100),
            'is_active' => (bool) ($item->is_active ?? false),
            'meta' => $this->decodeJsonObject($item->meta ?? null),
        ];
    }

    private function decodeJsonObject(mixed $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        if (is_array($value)) {
            return $value;
        }

        $decoded = json_decode((string) $value, true);

        return is_array($decoded) ? $decoded : [];
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

    private function settingsUsesPoolDeposits(mixed $fid): bool
    {
        return false;
    }

    private function settingsPoolDepositRows()
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
            ->orderBy('name')
            ->get()
            ->map(function ($pool) use ($eventsByPool) {
                $symbol = $this->normalizeCurrencyCode($pool->symbol ?? 'USDC');
                $active = (bool) ($pool->active ?? true);
                $latestEvent = $eventsByPool->get(strtolower((string) ($pool->pool_object_id ?? '')), collect())->first();
                $targetApy = (int) ($latestEvent->target_apy_bps ?? $pool->target_apy_bps ?? 0);
                $realizedApy = (int) ($latestEvent->realized_apy_bps ?? $pool->realized_apy_bps ?? 0);

                return (object) [
                    'id' => (int) $pool->id,
                    'type' => 'deposit',
                    'name' => (string) ($pool->name ?? 'Pool #' . $pool->id),
                    'color' => '',
                    'status' => $active ? '1' : '0',
                    'vision' => '1',
                    'constanta' => Schema::hasColumn('fund_pools', 'balance') ? (string) ($pool->balance ?? '0') : '0',
                    'currency' => $symbol,
                    'is_default' => (int) ($pool->is_default_deposit ?? 0),
                    'pool_object_id' => (string) ($pool->pool_object_id ?? ''),
                    'balance' => Schema::hasColumn('fund_pools', 'balance') ? (float) ($pool->balance ?? 0) : 0.0,
                    'balance_usdc' => $latestEvent
                        ? $this->settingsUsdcAtomicToFloat((string) ($latestEvent->balance_usdc ?? '0'))
                        : 0.0,
                    'apy_bps' => $realizedApy > 0 ? $realizedApy : $targetApy,
                    'target_apy_bps' => $targetApy,
                    'realized_apy_bps' => $realizedApy,
                    'description' => (string) ($pool->description ?? ''),
                    'notes' => (string) ($pool->notes ?? ''),
                ];
            });
    }

    private function settingsUsdcAtomicToFloat(string $value): float
    {
        $value = trim($value);
        if ($value === '') {
            return 0.0;
        }
        if (str_contains($value, '.')) {
            return (float) $value;
        }

        return (float) $value / 1_000_000;
    }

    private function storeSettingsPoolDeposit(Request $request): int
    {
        abort_unless(Schema::hasTable('fund_pools'), 404);

        $payload = $this->settingsPoolDepositPayload($request, true);
        $payload += [
            'pool_registry_id' => '',
            'pool_admin_cap_id' => '',
            'pool_object_id' => 'internal-' . bin2hex(random_bytes(8)),
            'created_by' => Auth::id(),
            'created_at' => now(),
            'updated_at' => now(),
        ];

        return (int) DB::table('fund_pools')->insertGetId($payload);
    }

    private function updateSettingsPoolDeposit(Request $request, int $poolId): bool
    {
        abort_unless(Schema::hasTable('fund_pools'), 404);

        $payload = $this->settingsPoolDepositPayload($request, false);
        $payload['updated_at'] = now();

        return DB::table('fund_pools')->where('id', $poolId)->update($payload) > 0;
    }

    private function settingsPoolDepositPayload(Request $request, bool $forCreate): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'currency' => ['nullable', 'string', 'max:10'],
            'status' => ['nullable', 'string'],
        ]);

        $symbol = $this->normalizeCurrencyCode($validated['currency'] ?? 'USDC');
        $payload = [
            'coin_type' => "internal::pool::{$symbol}",
            'symbol' => $symbol,
            'name' => trim((string) $validated['name']),
            'active' => (string) ($validated['status'] ?? '1') === '1',
        ];

        if ($forCreate) {
            $payload += [
                'network' => 'internal',
                'package_id' => '',
                'risk_level' => 1,
                'target_apy_bps' => 0,
                'realized_apy_bps' => 0,
                'min_deposit_usdc' => '0',
                'min_av8_balance' => '0',
                'max_weight_bps' => 10000,
                'logo_url' => '',
                'notes' => null,
            ];
        }

        if ($forCreate && Schema::hasColumn('fund_pools', 'balance')) {
            $payload['balance'] = 0;
        }
        if ($forCreate && Schema::hasColumn('fund_pools', 'is_default_deposit')) {
            $payload['is_default_deposit'] = false;
        }

        return $payload;
    }

    public function accountsIndex()
    {
        if (!Schema::hasTable('accounts')) {
            return response()->json([]);
        }

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
        if (Schema::hasColumn('accounts', 'project_id')) {
            $columns[] = 'accounts.project_id';
        }

        $items = Account::query()
            ->leftJoin('accounts as parent', 'accounts.parent_id', '=', 'parent.id')
            ->when(Schema::hasColumn('accounts', 'project_id'), fn ($query) => $query->whereNull('accounts.project_id'))
            ->orderBy('accounts.code')
            ->get($columns)
            ->map(fn ($item) => $this->decorateAccountItem($item));

        return response()->json($items);
    }

    public function analyticalAccountsIndex()
    {
        if (! Schema::hasTable('accounts') || ! Schema::hasColumn('accounts', 'project_id')) {
            return response()->json([]);
        }

        $columns = [
            'accounts.id',
            'accounts.code',
            'accounts.name',
            'accounts.type',
            'accounts.parent_id',
            'accounts.project_id',
            'parent.code as parent_code',
            'parent.name as parent_name',
        ];
        if (Schema::hasColumn('accounts', 'currency')) {
            $columns[] = 'accounts.currency';
        }

        $items = Account::query()
            ->leftJoin('accounts as parent', 'accounts.parent_id', '=', 'parent.id')
            ->where('accounts.project_id', (int) session('fid', 0))
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

        $item = $this->accessibleAccountQuery()->find($id);
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
        $this->validateAccountParentScope($validated['parent_id'] ?? null, null);

        $account = Account::query()->create($validated);

        return response()->json(['success' => true, 'id' => $account->id]);
    }

    public function accountsUpdate(Request $request, $id)
    {
        $account = $this->accessibleAccountQuery()->find($id);
        if (!$account) {
            return response()->json(['success' => false, 'message' => 'Не знайдено'], 404);
        }

        $validated = $this->validateAccountRecord($request, [
            'code' => 'required|string|max:255|unique:accounts,code,' . $account->id,
        ]);
        $this->validateAccountParentScope($validated['parent_id'] ?? null, $account->project_id ?? null);

        if ((int) ($validated['parent_id'] ?? 0) === (int) $account->id) {
            return response()->json(['success' => false, 'message' => 'Рахунок не може бути батьківським сам для себе'], 422);
        }

        $account->update($validated);

        return response()->json(['success' => true]);
    }

    public function accountsDestroy($id)
    {
        $account = $this->accessibleAccountQuery()->find($id);
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

    private function accessibleAccountQuery()
    {
        return Account::query()->when(
            Schema::hasColumn('accounts', 'project_id'),
            fn ($query) => $query->where(function ($scope) {
                $scope->whereNull('project_id')
                    ->orWhere('project_id', (int) session('fid', 0));
            })
        );
    }

    private function validateAccountParentScope(mixed $parentId, ?int $projectId): void
    {
        if (! $parentId || ! Schema::hasColumn('accounts', 'project_id')) {
            return;
        }

        $parentProjectId = Account::query()->whereKey($parentId)->value('project_id');
        if ($parentProjectId !== null && (int) $parentProjectId !== (int) $projectId) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'parent_id' => 'Родительский счет должен быть общим или относиться к текущему проекту.',
            ]);
        }
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
        $availableCurrencies = $this->currencyCodesForAccounts();
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
        if (!Schema::hasTable('conf')) {
            return response()->json([]);
        }

        $paymentTypes = DB::table('conf as c')
            ->leftJoin('accounts as da', 'c.debit_account_id', '=', 'da.id')
            ->leftJoin('accounts as ca', 'c.credit_account_id', '=', 'ca.id')
            ->where('c.type', 'reestr')
            ->orderBy('c.name')
            ->get([
                'c.id',
                'c.name',
                'c.doc',
                'c.constanta',
                'c.vision',
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
        $validated = $request->validate([
            'debit_account_id' => 'nullable|integer|exists:accounts,id',
            'credit_account_id' => 'nullable|integer|exists:accounts,id',
        ]);

        if (Schema::hasColumn('accounts', 'project_id')) {
            $accountIds = collect($validated)
                ->filter(fn ($value) => $value !== null)
                ->map(fn ($value) => (int) $value)
                ->values();
            if ($accountIds->isNotEmpty() && Account::query()->whereIn('id', $accountIds)->whereNotNull('project_id')->exists()) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'account_id' => 'Вид платежа можно привязать только к общему счету.',
                ]);
            }
        }

        $paymentType = DB::table('conf')
            ->where('id', $id)
            ->where('type', 'reestr')
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

        if (! $this->canUseProfileBalanceCache()) {
            return redirect()->back()->with('error', 'Таблицю кешу балансів не знайдено');
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

        $this->saveProfileBalancesToCache($user, session('fid', ''), $balances);

        return redirect()->route('settings.index')->with('success', 'Баланси профілю оновлено');
    }

    private function profileBalancesFromCache(object $user, mixed $fid): array
    {
        if (! $this->canUseProfileBalanceCache()) {
            return $this->parseUserBalance(Schema::hasColumn('users', 'balance') ? ($user->balance ?? '') : '');
        }

        $columns = Schema::getColumnListing('users_cashe');
        $hasValuta = in_array('valuta', $columns, true);
        $query = DB::table('users_cashe')->where('userid', (string) $user->id);

        if (in_array('firma', $columns, true)) {
            $firmaScope = HoldingScope::projectIdsFor($fid);
            if ($firmaScope !== []) {
                $query->whereIn('firma', array_map('intval', $firmaScope));
            }
        }

        $select = ['balance'];
        $select[] = $hasValuta ? 'valuta' : DB::raw("'UAH' as valuta");

        $query->orderByDesc('balance');
        if ($hasValuta) {
            $query->orderBy('valuta');
        }

        $rows = $query->get($select);

        if ($rows->isEmpty()) {
            return $this->parseUserBalance(Schema::hasColumn('users', 'balance') ? ($user->balance ?? '') : '');
        }

        return $rows
            ->map(function ($row, int $index) {
                return [
                    'amount' => $this->formatProfileBalanceAmount((string) ($row->balance ?? '0')),
                    'currency' => $this->normalizeCurrencyCode($row->valuta ?? 'UAH'),
                    'is_default' => $index === 0,
                ];
            })
            ->values()
            ->all();
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

    private function saveProfileBalancesToCache(object $user, mixed $fid, array $balances): void
    {
        $columns = Schema::getColumnListing('users_cashe');
        $hasFirma = in_array('firma', $columns, true);
        $hasUserId = in_array('user_id', $columns, true);
        $hasValuta = in_array('valuta', $columns, true);

        if (! $hasValuta && count($balances) > 1) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'balance_currencies' => 'Для кількох валют у users_cashe потрібне поле valuta.',
            ]);
        }

        DB::transaction(function () use ($user, $fid, $balances, $hasFirma, $hasUserId, $hasValuta): void {
            $deleteQuery = DB::table('users_cashe')->where('userid', (string) $user->id);

            if ($hasFirma) {
                $firmaScope = HoldingScope::projectIdsFor($fid);
                if ($firmaScope !== []) {
                    $deleteQuery->whereIn('firma', array_map('intval', $firmaScope));
                }
            }

            $deleteQuery->delete();

            foreach ($balances as $balance) {
                $currency = $this->normalizeCurrencyCode($balance['currency'] ?? 'UAH');
                $values = [
                    'userid' => (string) $user->id,
                    'balance' => round((float) ($balance['amount'] ?? 0), 2),
                ];

                if ($hasFirma) {
                    $values['firma'] = (int) $fid;
                }
                if ($hasUserId) {
                    $values['user_id'] = (int) $user->id;
                }
                if ($hasValuta) {
                    $values['valuta'] = $currency;
                }

                DB::table('users_cashe')->insert($values);
            }
        });
    }

    private function canUseProfileBalanceCache(): bool
    {
        if (! Schema::hasTable('users_cashe')) {
            return false;
        }

        $columns = Schema::getColumnListing('users_cashe');

        return in_array('userid', $columns, true) && in_array('balance', $columns, true);
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

    public function projectsIndex(Request $request)
    {
        $user = $this->currentUser();
        if (!Schema::hasTable('project')) {
            return response()->json([
                'data' => [],
                'current_page' => 1,
                'last_page' => 1,
                'per_page' => 10,
                'total' => 0,
            ]);
        }

        $query = Project::query();
        $fid = session('fid', '');
        $holdingProjectIds = collect(HoldingScope::projectIdsFor($fid))
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();
        $creatorProjectIds = $this->creatorProjectIdsForUser($user);
        $employeeProjectIds = $this->employeeProjectIdsForUser($user)->diff($creatorProjectIds)->values();
        $participatingProjectIds = $creatorProjectIds->merge($employeeProjectIds)->unique()->values();
        $otherProjectIds = $participatingProjectIds->diff($holdingProjectIds)->values();

        if (! $request->boolean('all_projects')) {
            $projectIds = $holdingProjectIds->merge($otherProjectIds)->unique()->values();
            if ($projectIds->isEmpty()) {
                $query->whereRaw('1 = 0');
            } else {
                $query->whereIn('id', $projectIds->all());
            }
        }

        $searchValue = $request->query('search', '');
        $search = is_string($searchValue) ? trim($searchValue) : '';
        if ($search !== '') {
            $query->where('name', 'like', '%' . mb_substr($search, 0, 100) . '%');
        }

        if ($holdingProjectIds->isNotEmpty()) {
            $holdingPlaceholders = implode(',', array_fill(0, $holdingProjectIds->count(), '?'));
            $bindings = $holdingProjectIds->all();
            $groupOrderSql = "CASE WHEN id IN ({$holdingPlaceholders}) THEN 0";
            if ($otherProjectIds->isNotEmpty()) {
                $otherPlaceholders = implode(',', array_fill(0, $otherProjectIds->count(), '?'));
                $groupOrderSql .= " WHEN id IN ({$otherPlaceholders}) THEN 1";
                $bindings = array_merge($bindings, $otherProjectIds->all());
            }
            $query->orderByRaw($groupOrderSql . ' ELSE 2 END', $bindings);
        }

        $hasHolding = Schema::hasColumn('project', 'holding_id')
            && (int) (Project::query()->where('id', $fid)->value('holding_id') ?? 0) > 0;

        $items = $query
            ->orderBy('num')
            ->orderBy('name')
            ->orderBy('id')
            ->paginate(10);

        $items->getCollection()->transform(function (Project $project) use ($user, $holdingProjectIds, $otherProjectIds, $creatorProjectIds, $employeeProjectIds, $hasHolding): array {
            $payload = $this->normalizeProject($project, $user);
            $projectId = (int) $project->id;
            $payload['user_role'] = $creatorProjectIds->contains($projectId)
                ? 'creator'
                : ($employeeProjectIds->contains($projectId) ? 'employee' : '');

            if ($holdingProjectIds->contains($projectId)) {
                $payload['scope_group'] = 'holding';
                $payload['scope_group_label'] = $hasHolding ? 'Проекты холдинга' : 'Текущий проект';
            } elseif ($otherProjectIds->contains($projectId)) {
                $payload['scope_group'] = 'other';
                $payload['scope_group_label'] = 'Другие проекты';
            } else {
                $payload['scope_group'] = 'all';
                $payload['scope_group_label'] = 'Все проекты';
            }

            return $payload;
        });

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

    public function holdingsIndex()
    {
        if (! Schema::hasTable('holding')) {
            return response()->json([]);
        }

        $items = DB::table('holding')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (object $holding) => [
                'id' => (int) $holding->id,
                'name' => (string) $holding->name,
            ]);

        return response()->json($items);
    }

    public function holdingsDestroy($id)
    {
        if (! Schema::hasTable('holding')) {
            return response()->json(['success' => false, 'message' => 'Таблицю holding не знайдено'], 404);
        }

        $holding = DB::table('holding')->where('id', $id)->first();
        if (! $holding) {
            return response()->json(['success' => false, 'message' => 'Холдинг не знайдено'], 404);
        }

        if (Schema::hasTable('project') && Schema::hasColumn('project', 'holding_id')) {
            $isUsed = DB::table('project')->where('holding_id', $holding->id)->exists();
            if ($isUsed) {
                return response()->json([
                    'success' => false,
                    'message' => 'Нельзя удалить холдинг, который привязан к проектам.',
                ], 422);
            }
        }

        DB::table('holding')->where('id', $holding->id)->delete();

        return response()->json(['success' => true]);
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

        $wasActiveProject = (int) session('fid', 0) === (int) $project->id;
        $previousProjectType = strtolower(trim((string) ($project->project_type ?? '')));
        $payload = $this->validateProject($request);
        $nextProjectType = strtolower(trim((string) ($payload['project_type'] ?? '')));

        $project->fill($payload)->save();
        $projectUserId = $this->ensureProjectUserCopy($project);

        if ($projectUserId && Schema::hasColumn('project', 'userid')) {
            $project->forceFill(['userid' => $projectUserId])->save();
        }

        return response()->json([
            'success' => true,
            'redirect_url' => $wasActiveProject && $previousProjectType !== $nextProjectType
                ? route('dashboard')
                : null,
        ]);
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

    // ── Region cities (filter.keyfield = city, idkeyfield = field.id region) ──

    public function regionCitiesIndex(Request $request)
    {
        if (!Schema::hasTable('filter')) {
            return response()->json(['items' => [], 'region_id' => null]);
        }

        $regionId = (int) $request->query('region_id', 0);
        $ignoreFirma = $this->shouldIgnoreCityFirma($request, 'city');
        if (!$this->fieldRegionFind(session('fid', ''), $regionId, $ignoreFirma)) {
            return response()->json(['message' => 'Регіон не знайдено'], 404);
        }

        $items = Filter::query()
            ->where('keyfield', 'city')
            ->where('idkeyfield', $regionId)
            ->orderBy('num')
            ->orderBy('val')
            ->orderBy('id')
            ->get()
            ->map(fn ($row) => $this->serializeRegionCity($row))
            ->values();

        return response()->json(['items' => $items, 'region_id' => $regionId]);
    }

    public function regionCitiesShow(Request $request, $id)
    {
        $ignoreFirma = $this->shouldIgnoreCityFirma($request, 'city');
        $row = $this->regionCityFind(session('fid', ''), $id, $ignoreFirma);
        if (!$row) {
            return response()->json(['message' => 'Місто не знайдено'], 404);
        }

        return response()->json($this->serializeRegionCity($row));
    }

    public function regionCitiesStore(Request $request)
    {
        if (!Schema::hasTable('filter')) {
            return response()->json(['success' => false, 'message' => 'Таблиця filter відсутня'], 404);
        }

        $data = $this->validateRegionCity($request, true);
        $regionId = (int) $data['region_id'];
        $ignoreFirma = $this->shouldIgnoreCityFirma($request, 'city');
        if (!$this->fieldRegionFind(session('fid', ''), $regionId, $ignoreFirma)) {
            return response()->json(['success' => false, 'message' => 'Регіон не знайдено'], 404);
        }

        $payload = $this->regionCityPayload($data);
        $payload['idkeyfield'] = $regionId;
        $payload['idfilter'] = 0;
        $payload['keyfield'] = 'city';
        $payload['count'] = 0;
        $payload['top'] = 0;
        foreach (['description', 'descriptionen', 'descriptionru'] as $column) {
            if (Schema::hasColumn('filter', $column)) {
                $payload[$column] = '';
            }
        }

        $id = Filter::query()->insertGetId($payload);
        $row = Filter::query()->find($id);

        return response()->json([
            'success' => true,
            'item' => $row ? $this->serializeRegionCity($row) : null,
        ]);
    }

    public function regionCitiesUpdate(Request $request, $id)
    {
        $ignoreFirma = $this->shouldIgnoreCityFirma($request, 'city');
        $row = $this->regionCityFind(session('fid', ''), $id, $ignoreFirma);
        if (!$row) {
            return response()->json(['success' => false, 'message' => 'Місто не знайдено'], 404);
        }

        $data = $this->validateRegionCity($request);
        Filter::query()->where('id', $row->id)->update($this->regionCityPayload($data));
        $fresh = Filter::query()->find($row->id);

        return response()->json([
            'success' => true,
            'item' => $fresh ? $this->serializeRegionCity($fresh) : null,
        ]);
    }

    public function regionCitiesDestroy(Request $request, $id)
    {
        $ignoreFirma = $this->shouldIgnoreCityFirma($request, 'city');
        $row = $this->regionCityFind(session('fid', ''), $id, $ignoreFirma);
        if (!$row) {
            return response()->json(['success' => false, 'message' => 'Місто не знайдено'], 404);
        }

        Filter::query()->where('id', $row->id)->delete();

        return response()->json(['success' => true]);
    }

    private function validateRegionCity(Request $request, bool $withRegion = false): array
    {
        $rules = [
            'val' => 'required|string|max:60',
            'valru' => 'nullable|string|max:60',
            'valen' => 'nullable|string|max:60',
            'num' => 'nullable|integer|min:0|max:65535',
        ];
        if ($withRegion) {
            $rules['region_id'] = 'required|integer|min:1';
        }

        return $request->validate($rules);
    }

    private function regionCityPayload(array $data): array
    {
        $payload = [
            'val' => $data['val'],
            'valru' => (string) ($data['valru'] ?? ''),
            'valen' => (string) ($data['valen'] ?? ''),
            'num' => (int) ($data['num'] ?? 0),
        ];

        return $payload;
    }

    private function serializeRegionCity(object $row): array
    {
        return [
            'id' => (int) $row->id,
            'region_id' => (int) ($row->idkeyfield ?? 0),
            'val' => (string) ($row->val ?? ''),
            'valru' => (string) ($row->valru ?? ''),
            'valen' => (string) ($row->valen ?? ''),
            'num' => (int) ($row->num ?? 0),
        ];
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
            'description' => 'nullable|string|max:65535',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:255',
            'google_map' => 'nullable|string|max:65535',
            'foto' => 'nullable|string|max:255',
            'foto_file' => 'nullable|image|max:4096',
            'is_default' => 'nullable|boolean',
            'faq' => 'nullable|array',
            'faq.questions' => 'nullable|array',
            'faq.answers' => 'nullable|array',
            'faq.questions.ua' => 'nullable|string|max:65535',
            'faq.questions.ru' => 'nullable|string|max:65535',
            'faq.questions.en' => 'nullable|string|max:65535',
            'faq.questions.es' => 'nullable|string|max:65535',
            'faq.questions.fr' => 'nullable|string|max:65535',
            'faq.answers.ua' => 'nullable|string|max:65535',
            'faq.answers.ru' => 'nullable|string|max:65535',
            'faq.answers.en' => 'nullable|string|max:65535',
            'faq.answers.es' => 'nullable|string|max:65535',
            'faq.answers.fr' => 'nullable|string|max:65535',
        ]);

        $type = (string) ($validated['type'] ?? '');
        $currency = $this->normalizeCurrencyCode($validated['currency'] ?? 'UAH');
        $vision = $validated['vision'] ?? '1';
        $faqTranslations = $this->normalizeFaqTranslations(
            $request->input('faq.questions', []),
            $request->input('faq.answers', [])
        );
        if ($type === 'web3_token') {
            $vision = Conf::normalizeWeb3ChainIdToDecimalString($vision) ?? $vision;
        }
        if ($type === 'currency') {
            $currency = $this->normalizeCurrencyCode($validated['name'] ?? $currency);
        }
        if ($type === 'faq') {
            if (! $this->faqHasCompleteTranslation($faqTranslations)) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'faq' => 'Заполните вопрос и ответ хотя бы на одном языке.',
                ]);
            }
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
            'color' => $type === 'faq'
                ? $this->faqTranslatedText($faqTranslations['questions'], 'ru')
                : trim((string) ($validated['color'] ?? '')),
            'status' => (string) ($validated['status'] ?? '1'),
            'vision' => (string) $vision,
            'constanta' => (string) ($validated['constanta'] ?? '0'),
        ];

        if ($type === 'reestr') {
            $data['constanta'] = (string) ($validated['constanta'] ?? '1') === '0' ? '0' : '1';
            $data['vision'] = Conf::normalizeCashFlowActivity($validated['vision'] ?? 'operating');
        }

        if (Schema::hasColumn('conf', 'is_default') && in_array($type, ['sklads', 'oplata'], true)) {
            $data['is_default'] = $request->boolean('is_default') ? 1 : 0;
        }

        if (Schema::hasColumn('conf', 'currency')) {
            $data['currency'] = $type === 'oplata' || $type === 'deposit' || $type === 'currency' ? $currency : '';
        }

        if (in_array($type, ['currency', 'faq'], true) && Schema::hasColumn('conf', 'descript')) {
            $data['descript'] = $type === 'faq'
                ? $this->faqTranslatedText($faqTranslations['answers'], 'ru')
                : trim((string) ($validated['description'] ?? ''));
        }

        if ($type === 'faq' && Schema::hasColumn('conf', 'htmlkeys')) {
            $data['htmlkeys'] = json_encode($faqTranslations, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
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

    private function faqLanguages(): array
    {
        return ['ua', 'ru', 'en', 'es', 'fr'];
    }

    private function normalizeFaqLanguage(string $language): string
    {
        $language = strtolower(trim($language));
        $language = $language === 'uk' ? 'ua' : $language;

        return in_array($language, $this->faqLanguages(), true) ? $language : 'ru';
    }

    private function normalizeFaqTranslations(mixed $questions, mixed $answers): array
    {
        $questions = is_array($questions) ? $questions : [];
        $answers = is_array($answers) ? $answers : [];
        $translations = ['questions' => [], 'answers' => []];

        foreach ($this->faqLanguages() as $language) {
            $translations['questions'][$language] = trim((string) ($questions[$language] ?? ''));
            $translations['answers'][$language] = trim((string) ($answers[$language] ?? ''));
        }

        return $translations;
    }

    private function faqTranslationsFromItem(object $item): array
    {
        $decoded = json_decode((string) ($item->htmlkeys ?? ''), true);
        $decoded = is_array($decoded) ? $decoded : [];
        $translations = $this->normalizeFaqTranslations(
            is_array($decoded['questions'] ?? null) ? $decoded['questions'] : [],
            is_array($decoded['answers'] ?? null) ? $decoded['answers'] : []
        );

        $legacyQuestion = trim((string) ($item->color ?? ''));
        $legacyAnswer = trim((string) ($item->descript ?? ''));
        if ($legacyQuestion !== '' && ! $this->faqHasText($translations['questions'])) {
            $translations['questions']['ru'] = $legacyQuestion;
        }
        if ($legacyAnswer !== '' && ! $this->faqHasText($translations['answers'])) {
            $translations['answers']['ru'] = $legacyAnswer;
        }

        return $translations;
    }

    private function faqHasCompleteTranslation(array $translations): bool
    {
        foreach ($this->faqLanguages() as $language) {
            if (($translations['questions'][$language] ?? '') !== '' && ($translations['answers'][$language] ?? '') !== '') {
                return true;
            }
        }

        return false;
    }

    private function faqHasText(array $values): bool
    {
        foreach ($values as $value) {
            if (trim((string) $value) !== '') {
                return true;
            }
        }

        return false;
    }

    private function faqTranslatedText(array $values, string $language): string
    {
        $language = $this->normalizeFaqLanguage($language);
        $fallbacks = array_unique([$language, 'ru', 'ua', 'en', 'es', 'fr']);

        foreach ($fallbacks as $fallback) {
            $value = trim((string) ($values[$fallback] ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }

    private function decorateConfItem(object $item, string $type): object
    {
        if ($type === 'reestr') {
            $item = Conf::decoratePaymentType($item);
        }

        if ($type === 'sklads') {
            $item->foto_preview = MediaUrl::image((string) ($item->foto ?? ''));
        }

        if (in_array($type, ['currency', 'faq'], true)) {
            $item->description = trim((string) ($item->descript ?? ''));
        }

        if ($type === 'faq') {
            $translations = $this->faqTranslationsFromItem($item);
            $item->page_key = trim((string) ($item->name ?? ''));
            $item->page = $item->page_key;
            $item->questions = $translations['questions'];
            $item->answers = $translations['answers'];
            $item->question = $this->faqTranslatedText($translations['questions'], 'ru');
            $item->answer = $this->faqTranslatedText($translations['answers'], 'ru');
            $item->description = $item->answer;
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

    private function currencyCodesForAccounts(): \Illuminate\Support\Collection
    {
        $currencies = DB::table('conf')
            ->where('type', 'currency')
            ->orderBy('name')
            ->get(['name', 'currency'])
            ->map(fn ($item) => $this->normalizeCurrencyCode($item->currency ?? $item->name ?? ''))
            ->filter()
            ->unique()
            ->values();

        return $currencies->isNotEmpty() ? $currencies : collect(['UAH']);
    }

    private function validateProject(Request $request): array
    {
        $validated = $request->validate([
            'num' => 'nullable|integer|min:0',
            'name' => 'required|string|max:50',
            'project_type' => ['nullable', 'string', Rule::in(array_keys($this->projectTypeOptions()))],
            'holding_name' => 'nullable|string|max:255',
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
        if (in_array('project_type', $projectColumns, true)) {
            $payload['project_type'] = trim((string) ($validated['project_type'] ?? '')) ?: null;
        }
        if (in_array('holding_id', $projectColumns, true)) {
            $payload['holding_id'] = $this->resolveHoldingId((string) ($validated['holding_name'] ?? ''));
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

    private function resolveHoldingId(string $name): ?int
    {
        $name = trim($name);

        if ($name === '' || ! Schema::hasTable('holding')) {
            return null;
        }

        $existingId = DB::table('holding')
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->value('id');

        if ($existingId) {
            return (int) $existingId;
        }

        return (int) DB::table('holding')->insertGetId([
            'name' => $name,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
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

    private function projectIdentityUserIds(?object $user): \Illuminate\Support\Collection
    {
        if (! $user instanceof User) {
            return collect();
        }

        $ids = collect([(int) $user->id]);
        $email = mb_strtolower(trim((string) ($user->email ?? '')));
        if ($email !== '' && Schema::hasTable('users') && Schema::hasColumn('users', 'email')) {
            $ids = $ids->merge(
                User::query()
                    ->whereRaw('LOWER(TRIM(email)) = ?', [$email])
                    ->pluck('id')
                    ->map(fn ($id) => (int) $id)
            );
        }

        return $ids->filter()->unique()->values();
    }

    private function creatorProjectIdsForUser(?object $user): \Illuminate\Support\Collection
    {
        if (! $user instanceof User || ! Schema::hasTable('project')) {
            return collect();
        }

        $identityUserIds = $this->projectIdentityUserIds($user);
        $email = mb_strtolower(trim((string) ($user->email ?? '')));
        $hasUserId = Schema::hasColumn('project', 'userid') && $identityUserIds->isNotEmpty();
        $hasEmail = Schema::hasColumn('project', 'email') && $email !== '';
        if (! $hasUserId && ! $hasEmail) {
            return collect();
        }

        return Project::query()
            ->where(function ($query) use ($hasUserId, $hasEmail, $identityUserIds, $email): void {
                if ($hasUserId) {
                    $query->whereIn('userid', $identityUserIds->all());
                }
                if ($hasEmail) {
                    $method = $hasUserId ? 'orWhereRaw' : 'whereRaw';
                    $query->{$method}('LOWER(TRIM(email)) = ?', [$email]);
                }
            })
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();
    }

    private function employeeProjectIdsForUser(?object $user): \Illuminate\Support\Collection
    {
        $identityUserIds = $this->projectIdentityUserIds($user);
        if (! Schema::hasTable('team_memberships') || $identityUserIds->isEmpty()) {
            return collect();
        }

        return DB::table('team_memberships')
            ->whereIn('user_id', $identityUserIds->all())
            ->pluck('project_id')
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();
    }

    private function normalizeProject(Project $project, ?object $user): array
    {
        $payload = Project::decorateMedia($project)->toArray();
        $payload['phone'] = (string) ($payload['phone'] ?? '');
        $payload['email'] = (string) ($payload['email'] ?? '');
        $payload['url'] = (string) ($payload['url'] ?? '');
        $payload['project_type'] = (string) ($payload['project_type'] ?? '');
        $payload['project_type_label'] = $this->projectTypeOptions()[$payload['project_type']] ?? '';
        $payload['holding_id'] = (int) ($payload['holding_id'] ?? 0);
        $payload['holding_name'] = $this->holdingNameById($payload['holding_id']);
        $payload['constanta'] = (int) ($payload['constanta'] ?? 0);
        $payload['can_delete'] = $this->userCanDeleteProjectByEmail($user, $project);

        return $payload;
    }

    private function holdingNameById(int $id): string
    {
        if ($id <= 0 || ! Schema::hasTable('holding')) {
            return '';
        }

        return (string) (DB::table('holding')->where('id', $id)->value('name') ?? '');
    }

    private function projectTypeOptions(): array
    {
        return [
            'trade' => 'Торговля',
            'bank' => 'Банк',
            'insurance' => 'Страхование',
            'education' => 'Образование',
        ];
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

    private function fieldBaseQuery($fid, string $keyfield, bool $ignoreFirma = false)
    {
        $query = DB::table('field')->where('keyfield', $keyfield);

        if (!$ignoreFirma) {
            $query->where('firma', $fid);
        }

        return $query;
    }

    private function fieldChildrenQuery($fid, string $keyfield, string $parentId, bool $ignoreFirma = false)
    {
        $query = $this->fieldBaseQuery($fid, $keyfield, $ignoreFirma);

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

    private function fieldFind($fid, string $keyfield, $id, bool $ignoreFirma = false)
    {
        return $this->fieldBaseQuery($fid, $keyfield, $ignoreFirma)->where('id', $id)->first();
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

    private function fieldRegionFind($fid, $regionId, bool $ignoreFirma = false): ?object
    {
        if (!Schema::hasTable('field')) {
            return null;
        }

        $id = (int) $regionId;
        if ($id <= 0) {
            return null;
        }

        return $this->fieldBaseQuery($fid, 'city', $ignoreFirma)->where('id', $id)->first();
    }

    private function regionCityFind($fid, $cityId, bool $ignoreFirma = false): ?object
    {
        if (!Schema::hasTable('filter')) {
            return null;
        }

        $row = Filter::query()
            ->where('id', (int) $cityId)
            ->where('keyfield', 'city')
            ->first();

        return $row && $this->fieldRegionFind($fid, $row->idkeyfield, $ignoreFirma) ? $row : null;
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
            'image_url' => in_array('foto1', $fieldColumns, true) ? MediaUrl::image((string) ($item->foto1 ?? '')) : null,
            'foto1_url' => in_array('foto1', $fieldColumns, true) ? MediaUrl::image((string) ($item->foto1 ?? '')) : null,
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

    private function shouldIgnoreCityFirma(Request $request, string $keyfield): bool
    {
        return $keyfield === 'city' && $request->boolean('ignore_firma');
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
        $ignoreFirma = $this->shouldIgnoreCityFirma($request, $keyfield);

        $itemsQuery = $this->fieldChildrenQuery($fid, $keyfield, $parentId, $ignoreFirma);
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
            : (Schema::hasTable('filter')
                ? Filter::query()
                    ->where('keyfield', 'city')
                    ->selectRaw('idkeyfield, COUNT(*) as total')
                    ->groupBy('idkeyfield')
                    ->pluck('total', 'idkeyfield')
                : collect());

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
        $ignoreFirma = $this->shouldIgnoreCityFirma($request, $keyfield);
        $item = $this->fieldFind($fid, $keyfield, $id, $ignoreFirma);
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
        $ignoreFirma = $this->shouldIgnoreCityFirma($request, $keyfield);
        $item = $this->fieldFind($fid, $keyfield, $id, $ignoreFirma);
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
        $ignoreFirma = $this->shouldIgnoreCityFirma($request, $keyfield);
        $item = $this->fieldFind($fid, $keyfield, $id, $ignoreFirma);
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
        } elseif (Schema::hasTable('filter')) {
            $hasCities = Filter::query()
                ->where('keyfield', 'city')
                ->where('idkeyfield', (int) $id)
                ->exists();

            if ($hasCities) {
                return response()->json(['success' => false, 'message' => 'Спочатку видаліть міста регіону'], 422);
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
