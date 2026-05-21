<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class FundPoolController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        if (! Schema::hasTable('fund_pools')) {
            return response()->json(['data' => []]);
        }

        $network = trim((string) $request->query('network', 'testnet'));
        $packageId = strtolower(trim((string) $request->query('package_id', '')));
        $coinType = trim((string) $request->query('coin_type', ''));
        $includeInactive = filter_var($request->query('include_inactive', true), FILTER_VALIDATE_BOOLEAN);

        $query = DB::table('fund_pools')
            ->when($network !== '', fn ($q) => $q->where('network', $network))
            ->when($packageId !== '', fn ($q) => $q->where('package_id', $packageId))
            ->when($coinType !== '', fn ($q) => $q->where('coin_type', $this->normalizeCoinType($coinType)))
            ->when(! $includeInactive, fn ($q) => $q->where('active', true))
            ->orderByDesc('active')
            ->orderByDesc('is_default_deposit')
            ->orderBy('risk_level')
            ->orderBy('name')
            ->orderBy('id');

        return response()->json([
            'data' => $query->get()->map(fn ($row) => $this->mapRow($row))->values(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        if (! Schema::hasTable('fund_pools')) {
            throw ValidationException::withMessages([
                'fund_pools' => 'Run migrations before saving fund pools.',
            ]);
        }

        $this->requireRwaAdminWallet($request);
        $validated = $this->validatePayload($request);
        $network = trim((string) ($validated['network'] ?? 'testnet')) ?: 'testnet';
        $poolObjectId = strtolower(trim((string) $validated['pool_object_id']));
        $now = now();

        DB::table('fund_pools')->updateOrInsert(
            [
                'network' => $network,
                'pool_object_id' => $poolObjectId,
            ],
            [
                'package_id' => strtolower(trim((string) ($validated['package_id'] ?? ''))),
                'pool_registry_id' => strtolower(trim((string) ($validated['pool_registry_id'] ?? ''))),
                'pool_admin_cap_id' => strtolower(trim((string) ($validated['pool_admin_cap_id'] ?? ''))),
                'pool_accounting_id' => $this->normalizeOptionalSuiAddress((string) ($validated['pool_accounting_id'] ?? '')),
                'basket_vault_id' => $this->normalizeOptionalSuiAddress((string) ($validated['basket_vault_id'] ?? '')),
                'liquidity_wallet_address' => $this->normalizeOptionalSuiAddress((string) ($validated['liquidity_wallet_address'] ?? '')),
                'coin_type' => $this->normalizeCoinType($validated['coin_type']),
                'symbol' => $this->symbolFromCoinType($this->normalizeCoinType($validated['coin_type'])),
                'name' => trim((string) $validated['name']),
                'description' => trim((string) ($validated['description'] ?? '')) ?: null,
                'risk_level' => (int) ($validated['risk_level'] ?? 1),
                'target_apy_bps' => (int) ($validated['target_apy_bps'] ?? 0),
                'realized_apy_bps' => (int) ($validated['realized_apy_bps'] ?? 0),
                'min_deposit_usdc' => trim((string) ($validated['min_deposit_usdc'] ?? '0')),
                'min_av8_balance' => trim((string) ($validated['min_av8_balance'] ?? '0')),
                'max_weight_bps' => (int) ($validated['max_weight_bps'] ?? 10000),
                'active' => (bool) ($validated['active'] ?? true),
                'is_default_deposit' => (bool) ($validated['is_default_deposit'] ?? false),
                'logo_url' => trim((string) ($validated['logo_url'] ?? '')),
                'notes' => trim((string) ($validated['notes'] ?? '')) ?: null,
                'created_by' => Auth::id(),
                'updated_at' => $now,
                'created_at' => $now,
            ] + $this->optionalCreditPoolColumns($validated)
        );
        $this->clearOtherDefaultDepositPools($network, $poolObjectId, (bool) ($validated['is_default_deposit'] ?? false));

        $row = DB::table('fund_pools')
            ->where('network', $network)
            ->where('pool_object_id', $poolObjectId)
            ->first();

        return response()->json(['data' => $this->mapRow($row)], 201);
    }

    public function creditRequests(Request $request): JsonResponse
    {
        if (! Schema::hasTable('fund_pools') || ! Schema::hasColumn('fund_pools', 'source_type')) {
            return response()->json(['data' => []]);
        }

        $owner = $this->normalizeSuiAddress((string) $request->query('owner_address', ''));
        $query = DB::table('fund_pools')
            ->where('source_type', 'credit_request')
            ->when($owner !== '', fn ($q) => $q->whereRaw('LOWER(borrower_address) = ?', [$owner]))
            ->orderByDesc('id');

        return response()->json([
            'data' => $query->get()->map(fn ($row) => $this->mapRow($row))->values(),
        ]);
    }

    public function storeCreditRequest(Request $request): JsonResponse
    {
        if (! Schema::hasTable('fund_pools')) {
            throw ValidationException::withMessages([
                'fund_pools' => 'Run migrations before saving fund pools.',
            ]);
        }

        $validated = $request->validate([
            'network' => ['nullable', 'string', 'max:40'],
            'package_id' => ['nullable', 'string', 'max:80', 'regex:/^(|0x[a-fA-F0-9]{64})$/'],
            'pool_registry_id' => ['nullable', 'string', 'max:80', 'regex:/^(|0x[a-fA-F0-9]{64})$/'],
            'pool_admin_cap_id' => ['nullable', 'string', 'max:80', 'regex:/^(|0x[a-fA-F0-9]{64})$/'],
            'coin_type' => [
                'required',
                'string',
                'max:500',
                'regex:/^0x[a-fA-F0-9]+::[A-Za-z_][A-Za-z0-9_]*::[A-Za-z_][A-Za-z0-9_]*(<.*>)?$/',
            ],
            'owner_address' => ['required', 'string', 'max:80', 'regex:/^0x[a-fA-F0-9]{1,64}$/'],
            'collateral.kind' => ['required', 'string', Rule::in(['nft', 'lp'])],
            'collateral.object_id' => ['required', 'string', 'max:80', 'regex:/^0x[a-fA-F0-9]{1,64}$/'],
            'collateral.type' => ['nullable', 'string', 'max:500'],
            'collateral.label' => ['required', 'string', 'max:255'],
            'collateral.protocol' => ['nullable', 'string', 'max:120'],
            'collateral.image_url' => ['nullable', 'string', 'max:500'],
            'collateral.valuation' => ['nullable', 'string', 'max:80'],
            'collateral.status' => ['nullable', 'string', 'max:80'],
            'requested_loan_usdc' => ['required', 'string', 'max:80', 'regex:/^\d+$/'],
            'requested_loan_rate_bps' => ['required', 'integer', 'min:500', 'max:4500'],
            'requested_loan_term_months' => ['required', 'integer', Rule::in($this->creditTermMonthOptions())],
        ]);

        $network = trim((string) ($validated['network'] ?? 'testnet')) ?: 'testnet';
        $poolObjectId = $this->generateDraftPoolObjectId($network);
        $collateral = $validated['collateral'];
        $label = trim((string) $collateral['label']);
        $kind = trim((string) $collateral['kind']);
        $requestedLoanUsdc = trim((string) $validated['requested_loan_usdc']);
        $collateralValuation = trim((string) ($collateral['valuation'] ?? ''));
        if ($collateralValuation !== '' && preg_match('/^\d+$/', $collateralValuation)) {
            $maxLoanUsdc = intdiv(((int) $collateralValuation) * 1_000_000 * 60, 100);
            if ((int) $requestedLoanUsdc > $maxLoanUsdc) {
                throw ValidationException::withMessages([
                    'requested_loan_usdc' => 'Requested loan amount cannot exceed 60% of NFT valuation.',
                ]);
            }
        }
        $now = now();

        $data = [
            'network' => $network,
            'package_id' => strtolower(trim((string) ($validated['package_id'] ?? ''))),
            'pool_registry_id' => strtolower(trim((string) ($validated['pool_registry_id'] ?? ''))),
            'pool_admin_cap_id' => strtolower(trim((string) ($validated['pool_admin_cap_id'] ?? ''))),
            'pool_object_id' => $poolObjectId,
            'pool_accounting_id' => '',
            'basket_vault_id' => '',
            'liquidity_wallet_address' => '',
            'coin_type' => $this->normalizeCoinType($validated['coin_type']),
            'symbol' => $this->symbolFromCoinType($this->normalizeCoinType($validated['coin_type'])),
            'name' => trim('Credit '.strtoupper($kind).' '.$label),
            'description' => $label,
            'risk_level' => 3,
            'target_apy_bps' => 0,
            'realized_apy_bps' => 0,
            'min_deposit_usdc' => '0',
            'min_av8_balance' => '0',
            'max_weight_bps' => 10000,
            'active' => false,
            'is_default_deposit' => false,
            'logo_url' => trim((string) ($collateral['image_url'] ?? '')),
            'notes' => 'Ожидает проверки администратором и on-chain создания пула.',
            'created_by' => Auth::id(),
            'updated_at' => $now,
            'created_at' => $now,
        ];

        if (Schema::hasColumn('fund_pools', 'source_type')) {
            $data['source_type'] = 'credit_request';
        }
        if (Schema::hasColumn('fund_pools', 'credit_request_status')) {
            $data['credit_request_status'] = 'requested';
        }
        if (Schema::hasColumn('fund_pools', 'requested_loan_usdc')) {
            $data['requested_loan_usdc'] = $requestedLoanUsdc;
        }
        if (Schema::hasColumn('fund_pools', 'requested_loan_rate_bps')) {
            $data['requested_loan_rate_bps'] = (int) $validated['requested_loan_rate_bps'];
        }
        if (Schema::hasColumn('fund_pools', 'requested_loan_term_months')) {
            $data['requested_loan_term_months'] = (int) $validated['requested_loan_term_months'];
        }
        if (Schema::hasColumn('fund_pools', 'borrower_address')) {
            $data['borrower_address'] = $this->normalizeSuiAddress((string) $validated['owner_address']);
        }
        if (Schema::hasColumn('fund_pools', 'collateral_kind')) {
            $data['collateral_kind'] = $kind;
            $data['collateral_object_id'] = $this->normalizeSuiAddress((string) $collateral['object_id']);
            $data['collateral_type'] = trim((string) ($collateral['type'] ?? ''));
            $data['collateral_label'] = $label;
            $data['collateral_protocol'] = trim((string) ($collateral['protocol'] ?? ''));
            $data['collateral_image_url'] = trim((string) ($collateral['image_url'] ?? ''));
            $data['collateral_valuation'] = trim((string) ($collateral['valuation'] ?? ''));
            $data['collateral_status'] = trim((string) ($collateral['status'] ?? ''));
        }

        $id = DB::table('fund_pools')->insertGetId($data);
        $row = DB::table('fund_pools')->where('id', $id)->first();

        return response()->json(['data' => $this->mapRow($row)], 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        if (! Schema::hasTable('fund_pools')) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $this->requireRwaAdminWallet($request);
        $validated = $this->validatePayload($request, $id);
        $network = trim((string) ($validated['network'] ?? 'testnet')) ?: 'testnet';

        $updated = DB::table('fund_pools')
            ->where('id', $id)
            ->update([
                'network' => $network,
                'package_id' => strtolower(trim((string) ($validated['package_id'] ?? ''))),
                'pool_registry_id' => strtolower(trim((string) ($validated['pool_registry_id'] ?? ''))),
                'pool_admin_cap_id' => strtolower(trim((string) ($validated['pool_admin_cap_id'] ?? ''))),
                'pool_object_id' => strtolower(trim((string) $validated['pool_object_id'])),
                'pool_accounting_id' => $this->normalizeOptionalSuiAddress((string) ($validated['pool_accounting_id'] ?? '')),
                'basket_vault_id' => $this->normalizeOptionalSuiAddress((string) ($validated['basket_vault_id'] ?? '')),
                'liquidity_wallet_address' => $this->normalizeOptionalSuiAddress((string) ($validated['liquidity_wallet_address'] ?? '')),
                'coin_type' => $this->normalizeCoinType($validated['coin_type']),
                'symbol' => $this->symbolFromCoinType($this->normalizeCoinType($validated['coin_type'])),
                'name' => trim((string) $validated['name']),
                'description' => trim((string) ($validated['description'] ?? '')) ?: null,
                'risk_level' => (int) ($validated['risk_level'] ?? 1),
                'target_apy_bps' => (int) ($validated['target_apy_bps'] ?? 0),
                'realized_apy_bps' => (int) ($validated['realized_apy_bps'] ?? 0),
                'min_deposit_usdc' => trim((string) ($validated['min_deposit_usdc'] ?? '0')),
                'min_av8_balance' => trim((string) ($validated['min_av8_balance'] ?? '0')),
                'max_weight_bps' => (int) ($validated['max_weight_bps'] ?? 10000),
                'active' => (bool) ($validated['active'] ?? true),
                'is_default_deposit' => (bool) ($validated['is_default_deposit'] ?? false),
                'logo_url' => trim((string) ($validated['logo_url'] ?? '')),
                'notes' => trim((string) ($validated['notes'] ?? '')) ?: null,
                'updated_at' => now(),
            ] + $this->optionalCreditPoolColumns($validated));

        if ($updated === 0 && ! DB::table('fund_pools')->where('id', $id)->exists()) {
            return response()->json(['message' => 'Not found'], 404);
        }
        $this->clearOtherDefaultDepositPools($network, strtolower(trim((string) $validated['pool_object_id'])), (bool) ($validated['is_default_deposit'] ?? false));

        $row = DB::table('fund_pools')->where('id', $id)->first();

        return response()->json(['data' => $this->mapRow($row)]);
    }

    public function updateCreditRequestTerms(Request $request, string $id): JsonResponse
    {
        if (! Schema::hasTable('fund_pools')) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $row = DB::table('fund_pools')->where('id', $id)->first();
        if (! $row || (string) ($row->source_type ?? '') !== 'credit_request') {
            return response()->json(['message' => 'Credit request not found'], 404);
        }

        $validated = $request->validate([
            'owner_address' => ['required', 'string', 'max:80', 'regex:/^0x[a-fA-F0-9]{1,64}$/'],
            'requested_loan_usdc' => ['required', 'string', 'max:80', 'regex:/^\d+$/'],
            'requested_loan_rate_bps' => ['required', 'integer', 'min:500', 'max:4500'],
            'requested_loan_term_months' => ['required', 'integer', Rule::in($this->creditTermMonthOptions())],
        ]);

        $owner = $this->normalizeSuiAddress((string) $validated['owner_address']);
        if ($owner !== strtolower((string) ($row->borrower_address ?? ''))) {
            return response()->json(['message' => 'Only the borrower can update this credit request.'], 403);
        }
        if ((string) ($row->credit_request_status ?? '') === 'closed') {
            throw ValidationException::withMessages([
                'credit_request_status' => 'Closed credit requests cannot be changed.',
            ]);
        }
        if ((int) $validated['requested_loan_rate_bps'] < (int) ($row->requested_loan_rate_bps ?? 500)) {
            throw ValidationException::withMessages([
                'requested_loan_rate_bps' => 'Loan rate can only be increased by the borrower.',
            ]);
        }
        $this->assertRequestedLoanWithinCollateralLimit(
            trim((string) $validated['requested_loan_usdc']),
            trim((string) ($row->collateral_valuation ?? ''))
        );

        DB::table('fund_pools')
            ->where('id', $id)
            ->update([
                'requested_loan_usdc' => trim((string) $validated['requested_loan_usdc']),
                'requested_loan_rate_bps' => (int) $validated['requested_loan_rate_bps'],
                'requested_loan_term_months' => (int) $validated['requested_loan_term_months'],
                'updated_at' => now(),
            ]);

        return response()->json(['data' => $this->mapRow(DB::table('fund_pools')->where('id', $id)->first())]);
    }

    public function claimCreditRequest(Request $request, string $id): JsonResponse
    {
        if (! Schema::hasTable('fund_pools')) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $row = DB::table('fund_pools')->where('id', $id)->first();
        if (! $row || (string) ($row->source_type ?? '') !== 'credit_request') {
            return response()->json(['message' => 'Credit request not found'], 404);
        }

        $validated = $request->validate([
            'owner_address' => ['required', 'string', 'max:80', 'regex:/^0x[a-fA-F0-9]{1,64}$/'],
            'tx_digest' => ['nullable', 'string', 'max:120'],
        ]);

        $owner = $this->normalizeSuiAddress((string) $validated['owner_address']);
        if ($owner !== strtolower((string) ($row->borrower_address ?? ''))) {
            return response()->json(['message' => 'Only the borrower can claim this credit request.'], 403);
        }
        if ((string) ($row->credit_request_status ?? '') !== 'approved') {
            throw ValidationException::withMessages([
                'credit_request_status' => 'Credit request is not funded yet.',
            ]);
        }
        if ($row->requested_loan_claimed_at !== null) {
            throw ValidationException::withMessages([
                'requested_loan_claimed_at' => 'Credit request has already been claimed.',
            ]);
        }

        $repayment = $this->calculateCreditRepayment(
            (string) ($row->requested_loan_usdc ?? '0'),
            (int) ($row->requested_loan_rate_bps ?? 500),
            (int) ($row->requested_loan_term_months ?? 12)
        );

        DB::table('fund_pools')
            ->where('id', $id)
            ->update([
                'credit_request_status' => 'closed',
                'requested_loan_claimed_at' => now(),
                'requested_loan_claim_tx_digest' => trim((string) ($validated['tx_digest'] ?? '')),
                'repayment_total_usdc' => $repayment['total'],
                'repayment_monthly_usdc' => $repayment['monthly'],
                'repayment_paid_usdc' => (string) ($row->repayment_paid_usdc ?? '0'),
                'updated_at' => now(),
            ]);

        return response()->json(['data' => $this->mapRow(DB::table('fund_pools')->where('id', $id)->first())]);
    }

    public function payCreditRequestInstallment(Request $request, string $id): JsonResponse
    {
        if (! Schema::hasTable('fund_pools')) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $row = DB::table('fund_pools')->where('id', $id)->first();
        if (! $row || (string) ($row->source_type ?? '') !== 'credit_request') {
            return response()->json(['message' => 'Credit request not found'], 404);
        }

        $validated = $request->validate([
            'owner_address' => ['required', 'string', 'max:80', 'regex:/^0x[a-fA-F0-9]{1,64}$/'],
            'amount_usdc' => ['required', 'string', 'max:80', 'regex:/^\d+$/'],
        ]);

        $owner = $this->normalizeSuiAddress((string) $validated['owner_address']);
        if ($owner !== strtolower((string) ($row->borrower_address ?? ''))) {
            return response()->json(['message' => 'Only the borrower can pay this credit request.'], 403);
        }
        if ($row->requested_loan_claimed_at === null) {
            throw ValidationException::withMessages([
                'requested_loan_claimed_at' => 'Credit request must be claimed before repayment.',
            ]);
        }

        $amount = (int) trim((string) $validated['amount_usdc']);
        $total = (int) ($row->repayment_total_usdc ?? '0');
        $paid = (int) ($row->repayment_paid_usdc ?? '0');
        $remaining = max(0, $total - $paid);
        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'amount_usdc' => 'Payment amount must be greater than zero.',
            ]);
        }
        if ($amount > $remaining) {
            throw ValidationException::withMessages([
                'amount_usdc' => 'Payment amount cannot exceed remaining debt.',
            ]);
        }

        DB::table('fund_pools')
            ->where('id', $id)
            ->update([
                'repayment_paid_usdc' => (string) ($paid + $amount),
                'repayment_last_paid_at' => now(),
                'updated_at' => now(),
            ]);

        return response()->json(['data' => $this->mapRow(DB::table('fund_pools')->where('id', $id)->first())]);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        if (! Schema::hasTable('fund_pools')) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $this->requireRwaAdminWallet($request);
        $deleted = DB::table('fund_pools')->where('id', $id)->delete();

        return response()->json(['deleted' => $deleted > 0]);
    }

    public function events(Request $request, string $id): JsonResponse
    {
        if (! Schema::hasTable('fund_pools') || ! Schema::hasTable('fund_pool_events')) {
            return response()->json(['data' => [], 'chart' => []]);
        }

        $pool = DB::table('fund_pools')->where('id', $id)->first();
        if (! $pool) {
            $poolObjectId = $this->normalizeSuiAddress($id);
            $pool = DB::table('fund_pools')
                ->whereRaw('LOWER(pool_object_id) = ?', [$poolObjectId])
                ->first();
        }

        if (! $pool) {
            return response()->json(['message' => 'Pool not found'], 404);
        }

        $limit = max(1, min(500, (int) $request->query('limit', 200)));
        $events = DB::table('fund_pool_events')
            ->whereRaw('LOWER(pool_object_id) = ?', [strtolower((string) $pool->pool_object_id)])
            ->orderBy('event_at')
            ->orderBy('id')
            ->limit($limit)
            ->get();

        return response()->json([
            'pool' => $this->mapRow($pool),
            'data' => $events->map(fn ($row) => $this->mapEventRow($row))->values(),
            'chart' => $this->chartRows($events),
        ]);
    }

    private function validatePayload(Request $request, ?string $id = null): array
    {
        $network = trim((string) $request->input('network', 'testnet')) ?: 'testnet';

        $validated = $request->validate([
            'network' => ['nullable', 'string', 'max:40'],
            'package_id' => ['nullable', 'string', 'max:80', 'regex:/^(|0x[a-fA-F0-9]{64})$/'],
            'pool_registry_id' => ['nullable', 'string', 'max:80', 'regex:/^(|0x[a-fA-F0-9]{64})$/'],
            'pool_admin_cap_id' => ['nullable', 'string', 'max:80', 'regex:/^(|0x[a-fA-F0-9]{64})$/'],
            'pool_accounting_id' => ['nullable', 'string', 'max:80', 'regex:/^(|0x[a-fA-F0-9]{1,64})$/'],
            'basket_vault_id' => ['nullable', 'string', 'max:80', 'regex:/^(|0x[a-fA-F0-9]{1,64})$/'],
            'liquidity_wallet_address' => ['nullable', 'string', 'max:80', 'regex:/^(|0x[a-fA-F0-9]{1,64})$/'],
            'pool_object_id' => [
                'required',
                'string',
                'max:80',
                'regex:/^0x[a-fA-F0-9]{64}$/',
                Rule::unique('fund_pools', 'pool_object_id')->where(fn ($query) => $query->where('network', $network))->ignore($id),
            ],
            'coin_type' => [
                'required',
                'string',
                'max:500',
                'regex:/^0x[a-fA-F0-9]+::[A-Za-z_][A-Za-z0-9_]*::[A-Za-z_][A-Za-z0-9_]*(<.*>)?$/',
            ],
            'symbol' => ['nullable', 'string', 'max:32'],
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:5000'],
            'risk_level' => ['nullable', 'integer', 'min:1', 'max:5'],
            'target_apy_bps' => ['nullable', 'integer', 'min:0', 'max:10000'],
            'realized_apy_bps' => ['nullable', 'integer', 'min:0', 'max:10000'],
            'min_deposit_usdc' => ['nullable', 'string', 'max:80', 'regex:/^\d+$/'],
            'min_av8_balance' => ['nullable', 'string', 'max:80', 'regex:/^\d+$/'],
            'max_weight_bps' => ['nullable', 'integer', 'min:0', 'max:10000'],
            'active' => ['nullable', 'boolean'],
            'is_default_deposit' => ['nullable', 'boolean'],
            'logo_url' => ['nullable', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'source_type' => ['nullable', 'string', 'max:40'],
            'credit_request_status' => ['nullable', 'string', Rule::in(['', 'requested', 'review', 'approved', 'closed'])],
            'requested_loan_usdc' => ['nullable', 'string', 'max:80', 'regex:/^\d+$/'],
            'requested_loan_rate_bps' => ['nullable', 'integer', 'min:500', 'max:4500'],
            'requested_loan_term_months' => ['nullable', 'integer', Rule::in($this->creditTermMonthOptions())],
            'repayment_total_usdc' => ['nullable', 'string', 'max:80', 'regex:/^\d+$/'],
            'repayment_monthly_usdc' => ['nullable', 'string', 'max:80', 'regex:/^\d+$/'],
            'repayment_paid_usdc' => ['nullable', 'string', 'max:80', 'regex:/^\d+$/'],
            'borrower_address' => ['nullable', 'string', 'max:80', 'regex:/^(|0x[a-fA-F0-9]{1,64})$/'],
            'collateral_kind' => ['nullable', 'string', Rule::in(['', 'nft', 'lp'])],
            'collateral_object_id' => ['nullable', 'string', 'max:80', 'regex:/^(|0x[a-fA-F0-9]{1,64})$/'],
            'collateral_type' => ['nullable', 'string', 'max:500'],
            'collateral_label' => ['nullable', 'string', 'max:255'],
            'collateral_protocol' => ['nullable', 'string', 'max:120'],
            'collateral_image_url' => ['nullable', 'string', 'max:500'],
            'collateral_valuation' => ['nullable', 'string', 'max:80'],
            'collateral_status' => ['nullable', 'string', 'max:80'],
        ]);

        $this->assertRequestedLoanWithinCollateralLimit(
            trim((string) ($validated['requested_loan_usdc'] ?? '0')),
            trim((string) ($validated['collateral_valuation'] ?? ''))
        );

        return $validated;
    }

    private function assertRequestedLoanWithinCollateralLimit(string $requestedLoanUsdc, string $collateralValuation): void
    {
        if ($requestedLoanUsdc === '' || $collateralValuation === '' || ! preg_match('/^\d+$/', $requestedLoanUsdc) || ! preg_match('/^\d+$/', $collateralValuation)) {
            return;
        }

        $maxLoanUsdc = intdiv(((int) $collateralValuation) * 1_000_000 * 60, 100);
        if ((int) $requestedLoanUsdc > $maxLoanUsdc) {
            throw ValidationException::withMessages([
                'requested_loan_usdc' => 'Requested loan amount cannot exceed 60% of NFT valuation.',
            ]);
        }
    }

    private function optionalCreditPoolColumns(array $validated): array
    {
        $data = [];
        $stringColumns = [
            'source_type',
            'credit_request_status',
            'requested_loan_usdc',
            'repayment_total_usdc',
            'repayment_monthly_usdc',
            'repayment_paid_usdc',
            'collateral_type',
            'collateral_label',
            'collateral_protocol',
            'collateral_image_url',
            'collateral_valuation',
            'collateral_status',
        ];

        foreach ($stringColumns as $column) {
            if (Schema::hasColumn('fund_pools', $column) && array_key_exists($column, $validated)) {
                $data[$column] = trim((string) ($validated[$column] ?? ''));
            }
        }

        foreach (['borrower_address', 'collateral_object_id'] as $column) {
            if (Schema::hasColumn('fund_pools', $column) && array_key_exists($column, $validated)) {
                $value = trim((string) ($validated[$column] ?? ''));
                $data[$column] = $value === '' ? '' : $this->normalizeSuiAddress($value);
            }
        }

        if (Schema::hasColumn('fund_pools', 'requested_loan_rate_bps') && array_key_exists('requested_loan_rate_bps', $validated)) {
            $data['requested_loan_rate_bps'] = (int) ($validated['requested_loan_rate_bps'] ?? 500);
        }

        if (Schema::hasColumn('fund_pools', 'requested_loan_term_months') && array_key_exists('requested_loan_term_months', $validated)) {
            $data['requested_loan_term_months'] = (int) ($validated['requested_loan_term_months'] ?? 12);
        }

        if (Schema::hasColumn('fund_pools', 'collateral_kind') && array_key_exists('collateral_kind', $validated)) {
            $data['collateral_kind'] = trim((string) ($validated['collateral_kind'] ?? ''));
        }

        return $data;
    }

    private function requireRwaAdminWallet(Request $request): void
    {
        if (! Schema::hasTable('rwa_admin_caps')) {
            throw ValidationException::withMessages([
                'admin' => 'RWA admin caps table is missing.',
            ]);
        }

        $address = $this->normalizeSuiAddress((string) $request->header('X-Sui-Admin-Address', ''));
        if (! preg_match('/^0x[a-f0-9]{64}$/', $address)) {
            throw ValidationException::withMessages([
                'admin' => 'Connect a registered Sui admin wallet before changing fund pools.',
            ]);
        }

        $exists = DB::table('rwa_admin_caps')
            ->whereRaw('LOWER(owner_address) = ?', [$address])
            ->exists();

        if (! $exists) {
            throw ValidationException::withMessages([
                'admin' => 'This wallet is not registered as an RWA AdminCap owner.',
            ]);
        }
    }

    private function normalizeCoinType(string $coinType): string
    {
        $parts = explode('::', trim($coinType), 3);
        if (count($parts) !== 3) {
            return trim($coinType);
        }

        $parts[0] = strtolower($parts[0]);

        return implode('::', $parts);
    }

    /**
     * Derive a ticker symbol from a Sui coin type string.
     * E.g. "0x2::sui::SUI" -> "SUI", "0xabc::usdc::USDC" -> "USDC".
     */
    private function symbolFromCoinType(string $coinType): string
    {
        $parts = explode('::', trim($coinType));
        $last = end($parts);
        return $last && strlen($last) <= 12 ? strtoupper($last) : 'TOKEN';
    }

    private function normalizeSuiAddress(string $value): string
    {
        $value = strtolower(trim($value));

        if (preg_match('/^0x([a-f0-9]{1,64})$/', $value, $matches)) {
            return '0x'.str_pad($matches[1], 64, '0', STR_PAD_LEFT);
        }

        return $value;
    }

    private function normalizeOptionalSuiAddress(string $value): string
    {
        $value = trim($value);

        return $value === '' ? '' : $this->normalizeSuiAddress($value);
    }

    private function generateDraftPoolObjectId(string $network): string
    {
        do {
            $value = '0x'.bin2hex(random_bytes(32));
        } while (DB::table('fund_pools')
            ->where('network', $network)
            ->where('pool_object_id', $value)
            ->exists());

        return $value;
    }

    private function clearOtherDefaultDepositPools(string $network, string $poolObjectId, bool $isDefaultDeposit): void
    {
        if (! $isDefaultDeposit || ! Schema::hasColumn('fund_pools', 'is_default_deposit')) {
            return;
        }

        DB::table('fund_pools')
            ->where('network', $network)
            ->whereRaw('LOWER(pool_object_id) <> ?', [strtolower($poolObjectId)])
            ->update(['is_default_deposit' => false]);
    }

    private function creditTermMonthOptions(): array
    {
        return range(3, 36, 3);
    }

    private function calculateCreditRepayment(string $principalUsdc, int $rateBps, int $termMonths): array
    {
        $principal = max(0, (int) $principalUsdc);
        $months = in_array($termMonths, $this->creditTermMonthOptions(), true) ? $termMonths : 12;
        $interest = intdiv($principal * max(0, $rateBps) * $months, 10_000 * 12);
        $total = $principal + $interest;
        $monthly = $months > 0 ? intdiv($total + $months - 1, $months) : $total;

        return [
            'total' => (string) $total,
            'monthly' => (string) $monthly,
        ];
    }

    private function mapRow(object $row): array
    {
        return [
            'id' => (int) $row->id,
            'network' => (string) $row->network,
            'package_id' => (string) ($row->package_id ?? ''),
            'pool_registry_id' => (string) ($row->pool_registry_id ?? ''),
            'pool_admin_cap_id' => (string) ($row->pool_admin_cap_id ?? ''),
            'pool_object_id' => (string) ($row->pool_object_id ?? ''),
            'pool_accounting_id' => (string) ($row->pool_accounting_id ?? ''),
            'basket_vault_id' => (string) ($row->basket_vault_id ?? ''),
            'liquidity_wallet_address' => (string) ($row->liquidity_wallet_address ?? ''),
            'coin_type' => (string) $row->coin_type,
            'symbol' => $this->symbolFromCoinType((string) $row->coin_type),
            'name' => (string) $row->name,
            'description' => (string) ($row->description ?? ''),
            'risk_level' => (int) $row->risk_level,
            'target_apy_bps' => (int) $row->target_apy_bps,
            'realized_apy_bps' => (int) $row->realized_apy_bps,
            'min_deposit_usdc' => (string) ($row->min_deposit_usdc ?? '0'),
            'min_av8_balance' => (string) ($row->min_av8_balance ?? '0'),
            'max_weight_bps' => (int) $row->max_weight_bps,
            'active' => (bool) $row->active,
            'is_default_deposit' => (bool) ($row->is_default_deposit ?? false),
            'logo_url' => (string) ($row->logo_url ?? ''),
            'notes' => (string) ($row->notes ?? ''),
            'source_type' => Schema::hasColumn('fund_pools', 'source_type') ? (string) ($row->source_type ?? '') : '',
            'credit_request_status' => Schema::hasColumn('fund_pools', 'credit_request_status') ? (string) ($row->credit_request_status ?? '') : '',
            'requested_loan_usdc' => Schema::hasColumn('fund_pools', 'requested_loan_usdc') ? (string) ($row->requested_loan_usdc ?? '0') : '0',
            'requested_loan_rate_bps' => Schema::hasColumn('fund_pools', 'requested_loan_rate_bps') ? (int) ($row->requested_loan_rate_bps ?? 500) : 500,
            'requested_loan_term_months' => Schema::hasColumn('fund_pools', 'requested_loan_term_months') ? (int) ($row->requested_loan_term_months ?? 12) : 12,
            'requested_loan_claimed_at' => Schema::hasColumn('fund_pools', 'requested_loan_claimed_at') && $row->requested_loan_claimed_at ? (string) $row->requested_loan_claimed_at : null,
            'requested_loan_claim_tx_digest' => Schema::hasColumn('fund_pools', 'requested_loan_claim_tx_digest') ? (string) ($row->requested_loan_claim_tx_digest ?? '') : '',
            'repayment_total_usdc' => Schema::hasColumn('fund_pools', 'repayment_total_usdc') ? (string) ($row->repayment_total_usdc ?? '0') : '0',
            'repayment_monthly_usdc' => Schema::hasColumn('fund_pools', 'repayment_monthly_usdc') ? (string) ($row->repayment_monthly_usdc ?? '0') : '0',
            'repayment_paid_usdc' => Schema::hasColumn('fund_pools', 'repayment_paid_usdc') ? (string) ($row->repayment_paid_usdc ?? '0') : '0',
            'repayment_last_paid_at' => Schema::hasColumn('fund_pools', 'repayment_last_paid_at') && $row->repayment_last_paid_at ? (string) $row->repayment_last_paid_at : null,
            'borrower_address' => Schema::hasColumn('fund_pools', 'borrower_address') ? (string) ($row->borrower_address ?? '') : '',
            'collateral_kind' => Schema::hasColumn('fund_pools', 'collateral_kind') ? (string) ($row->collateral_kind ?? '') : '',
            'collateral_object_id' => Schema::hasColumn('fund_pools', 'collateral_object_id') ? (string) ($row->collateral_object_id ?? '') : '',
            'collateral_type' => Schema::hasColumn('fund_pools', 'collateral_type') ? (string) ($row->collateral_type ?? '') : '',
            'collateral_label' => Schema::hasColumn('fund_pools', 'collateral_label') ? (string) ($row->collateral_label ?? '') : '',
            'collateral_protocol' => Schema::hasColumn('fund_pools', 'collateral_protocol') ? (string) ($row->collateral_protocol ?? '') : '',
            'collateral_image_url' => Schema::hasColumn('fund_pools', 'collateral_image_url') ? (string) ($row->collateral_image_url ?? '') : '',
            'collateral_valuation' => Schema::hasColumn('fund_pools', 'collateral_valuation') ? (string) ($row->collateral_valuation ?? '') : '',
            'collateral_status' => Schema::hasColumn('fund_pools', 'collateral_status') ? (string) ($row->collateral_status ?? '') : '',
            'created_at' => $row->created_at ? (string) $row->created_at : null,
            'updated_at' => $row->updated_at ? (string) $row->updated_at : null,
        ];
    }

    private function mapEventRow(object $row): array
    {
        return [
            'id' => (int) $row->id,
            'network' => (string) $row->network,
            'package_id' => (string) $row->package_id,
            'event_type' => (string) $row->event_type,
            'move_event_type' => (string) $row->move_event_type,
            'tx_digest' => (string) $row->tx_digest,
            'event_seq' => (int) $row->event_seq,
            'checkpoint' => $row->checkpoint !== null ? (int) $row->checkpoint : null,
            'pool_object_id' => (string) $row->pool_object_id,
            'owner_address' => (string) $row->owner_address,
            'amount_usdc' => (string) $row->amount_usdc,
            'pool_shares' => (string) $row->pool_shares,
            'burned_pool_shares' => (string) $row->burned_pool_shares,
            'balance_usdc' => (string) $row->balance_usdc,
            'active' => $row->active === null ? null : (bool) $row->active,
            'target_apy_bps' => $row->target_apy_bps === null ? null : (int) $row->target_apy_bps,
            'realized_apy_bps' => $row->realized_apy_bps === null ? null : (int) $row->realized_apy_bps,
            'min_deposit_usdc' => $row->min_deposit_usdc === null ? null : (string) $row->min_deposit_usdc,
            'max_weight_bps' => $row->max_weight_bps === null ? null : (int) $row->max_weight_bps,
            'event_at' => $row->event_at ? (string) $row->event_at : null,
        ];
    }

    private function chartRows($events): array
    {
        $lastBalance = '0';
        $lastTargetApy = null;
        $lastRealizedApy = null;

        return $events->map(function ($row) use (&$lastBalance, &$lastTargetApy, &$lastRealizedApy) {
            if ((string) $row->balance_usdc !== '0') {
                $lastBalance = (string) $row->balance_usdc;
            }
            if ($row->target_apy_bps !== null) {
                $lastTargetApy = (int) $row->target_apy_bps;
            }
            if ($row->realized_apy_bps !== null) {
                $lastRealizedApy = (int) $row->realized_apy_bps;
            }

            return [
                'label' => $row->event_at ? date('M d', strtotime((string) $row->event_at)) : '#'.$row->id,
                'event_at' => $row->event_at ? (string) $row->event_at : null,
                'event_type' => (string) $row->event_type,
                'tvl_usdc' => $lastBalance,
                'target_apy_bps' => $lastTargetApy,
                'realized_apy_bps' => $lastRealizedApy,
            ];
        })->values()->all();
    }
}
