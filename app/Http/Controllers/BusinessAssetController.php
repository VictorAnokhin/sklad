<?php

namespace App\Http\Controllers;

use App\Models\AssetOperation;
use App\Models\BusinessAsset;
use App\Models\Conf;
use App\Services\AccountingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class BusinessAssetController extends Controller
{
    public function index()
    {
        $fid = (string) session('fid', '');

        $assets = BusinessAsset::query()
            ->leftJoin('conf as asset_type', 'asset_type.id', '=', 'business_assets.asset_type_id')
            ->where('business_assets.fid', (int) $fid)
            ->orderByDesc('business_assets.id')
            ->get([
                'business_assets.*',
                DB::raw("COALESCE(NULLIF(asset_type.name, ''), '') as asset_type_name"),
            ]);

        $operations = AssetOperation::query()
            ->leftJoin('business_assets as asset', 'asset.id', '=', 'asset_operations.business_asset_id')
            ->leftJoin('conf as cash', 'cash.id', '=', 'asset_operations.cash_account_id')
            ->leftJoin('conf as payment_type', 'payment_type.id', '=', 'asset_operations.payment_type_id')
            ->where('asset_operations.fid', (int) $fid)
            ->orderByDesc('asset_operations.operation_date')
            ->orderByDesc('asset_operations.id')
            ->limit(300)
            ->get([
                'asset_operations.*',
                DB::raw("COALESCE(NULLIF(asset.name, ''), '') as asset_name"),
                DB::raw("COALESCE(NULLIF(cash.name, ''), '') as cash_account_name"),
                DB::raw("COALESCE(NULLIF(payment_type.name, ''), '') as payment_type_name"),
            ]);

        $assetTypes = DB::table('conf')
            ->where('type', 'asset_type')
            ->where(function ($query) use ($fid) {
                $query->where('firma', '0')->orWhere('firma', $fid);
            })
            ->orderBy('name')
            ->get();

        $cashAccounts = DB::table('conf')
            ->where('type', 'oplata')
            ->where('firma', $fid)
            ->orderBy('name')
            ->get();

        $paymentTypes = Conf::paymentTypesForDocument($fid, 'ASSET');
        $summary = [
            'active_assets' => $assets->whereNotIn('status', ['sold', 'disposed'])->count(),
            'current_value' => (float) $assets->whereNotIn('status', ['sold', 'disposed'])->sum('current_value'),
            'posted_operations' => $operations->where('provodka', true)->count(),
        ];

        return view('document.assets', compact(
            'fid',
            'assets',
            'operations',
            'assetTypes',
            'cashAccounts',
            'paymentTypes',
            'summary'
        ));
    }

    public function storeOperation(Request $request, AccountingService $accounting)
    {
        $fid = (string) session('fid', '');
        $validated = $request->validate([
            'business_asset_id' => ['nullable', 'integer'],
            'asset_type_id' => ['nullable', 'integer'],
            'asset_kind' => ['nullable', 'string', 'max:40'],
            'asset_name' => ['nullable', 'string', 'max:255'],
            'operation_type' => ['required', 'string', 'in:purchase,sell,depreciation,impairment,revalue,rd_capitalize,rd_expense'],
            'operation_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'carrying_amount' => ['nullable', 'numeric', 'min:0'],
            'cash_account_id' => ['nullable', 'string', 'max:80'],
            'payment_type_id' => ['nullable', 'integer'],
            'description' => ['nullable', 'string', 'max:2000'],
            'post_after_save' => ['nullable', 'boolean'],
        ]);

        $operationType = (string) $validated['operation_type'];
        $cashRequired = in_array($operationType, ['purchase', 'sell', 'rd_capitalize', 'rd_expense'], true);
        if ($cashRequired && trim((string) ($validated['cash_account_id'] ?? '')) === '') {
            return back()->withErrors(['cash_account_id' => 'Выберите денежный счет.'])->withInput();
        }

        $asset = null;
        $assetId = (int) ($validated['business_asset_id'] ?? 0);
        if ($assetId > 0) {
            $asset = BusinessAsset::query()
                ->where('fid', (int) $fid)
                ->where('id', $assetId)
                ->firstOrFail();
        }

        if (!$asset && in_array($operationType, ['purchase', 'rd_capitalize'], true)) {
            $name = trim((string) ($validated['asset_name'] ?? ''));
            if ($name === '') {
                return back()->withErrors(['asset_name' => 'Укажите название актива.'])->withInput();
            }

            $asset = BusinessAsset::create([
                'fid' => (int) $fid,
                'asset_type_id' => $validated['asset_type_id'] ?? null,
                'type' => $validated['asset_kind'] ?: 'equipment',
                'name' => $name,
                'currency' => 'UAH',
                'initial_cost' => 0,
                'current_value' => 0,
                'accumulated_depreciation' => 0,
                'status' => 'draft',
                'description' => $validated['description'] ?? null,
            ]);
        }

        if (!$asset && $operationType !== 'rd_expense') {
            return back()->withErrors(['business_asset_id' => 'Выберите актив.'])->withInput();
        }

        $operation = AssetOperation::create([
            'fid' => (int) $fid,
            'business_asset_id' => $asset?->id,
            'operation_type' => $operationType,
            'operation_date' => $validated['operation_date'],
            'amount' => round((float) $validated['amount'], 2),
            'carrying_amount' => round((float) ($validated['carrying_amount'] ?? ($asset->current_value ?? 0)), 2),
            'cash_account_id' => $validated['cash_account_id'] ?? null,
            'payment_type_id' => $validated['payment_type_id'] ?? null,
            'description' => $validated['description'] ?? null,
            'provodka' => false,
        ]);

        if ($request->boolean('post_after_save')) {
            $this->postOperationInternal($operation, $accounting);
        }

        return redirect()->route('document.assets.index')->with('success', 'Операция по активу сохранена.');
    }

    public function post(AssetOperation $operation, AccountingService $accounting)
    {
        $this->authorizeProjectOperation($operation);
        $this->postOperationInternal($operation, $accounting);

        return redirect()->route('document.assets.index')->with('success', 'Операция проведена.');
    }

    public function reverse(AssetOperation $operation, AccountingService $accounting)
    {
        $this->authorizeProjectOperation($operation);
        $this->reverseOperationInternal($operation, $accounting);

        return redirect()->route('document.assets.index')->with('success', 'Проводка операции снята.');
    }

    public function destroy(AssetOperation $operation)
    {
        $this->authorizeProjectOperation($operation);
        if ($operation->provodka) {
            return redirect()->route('document.assets.index')->with('error', 'Сначала снимите проводку операции.');
        }

        $operation->delete();

        return redirect()->route('document.assets.index')->with('success', 'Операция удалена.');
    }

    private function postOperationInternal(AssetOperation $operation, AccountingService $accounting): void
    {
        $operation->refresh();
        if ($operation->provodka) {
            return;
        }

        DB::transaction(function () use ($operation, $accounting) {
            $asset = $operation->asset()->lockForUpdate()->first();
            $transaction = $accounting->createAssetOperationTransaction($operation, $asset, (string) $operation->fid);
            if (!$transaction || $transaction->entries->count() < 2) {
                throw new RuntimeException('Не удалось создать двойную запись по операции с активом.');
            }

            $this->applyAssetEffect($operation, 1, $asset);
            $this->shiftCashAccountValue($operation, $this->cashDelta($operation, 1));

            $operation->ledger_transaction_id = $transaction->id;
            $operation->provodka = true;
            $operation->save();
        });
    }

    private function reverseOperationInternal(AssetOperation $operation, AccountingService $accounting): void
    {
        $operation->refresh();
        if (!$operation->provodka) {
            return;
        }

        DB::transaction(function () use ($operation, $accounting) {
            $asset = $operation->asset()->lockForUpdate()->first();
            $transaction = $accounting->createAssetOperationTransaction($operation, $asset, (string) $operation->fid, true);
            if (!$transaction || $transaction->entries->count() < 2) {
                throw new RuntimeException('Не удалось создать сторно операции с активом.');
            }

            $this->applyAssetEffect($operation, -1, $asset);
            $this->shiftCashAccountValue($operation, $this->cashDelta($operation, -1));

            $operation->reversal_transaction_id = $transaction->id;
            $operation->provodka = false;
            $operation->save();
        });
    }

    private function applyAssetEffect(AssetOperation $operation, int $direction, ?BusinessAsset $asset): void
    {
        if (!$asset) {
            return;
        }

        $amount = round((float) $operation->amount, 2);
        $carryingAmount = round((float) ($operation->carrying_amount ?: $asset->current_value), 2);

        if (in_array($operation->operation_type, ['purchase', 'rd_capitalize'], true)) {
            $asset->initial_cost = max(0, (float) $asset->initial_cost + ($amount * $direction));
            $asset->current_value = max(0, (float) $asset->current_value + ($amount * $direction));
            if ($direction > 0 && !$asset->acquired_at) {
                $asset->acquired_at = $operation->operation_date;
            }
            $asset->status = $asset->current_value > 0 ? 'active' : 'draft';
        } elseif ($operation->operation_type === 'sell') {
            if ($direction > 0) {
                $asset->current_value = 0;
                $asset->disposed_at = $operation->operation_date;
                $asset->status = 'sold';
            } else {
                $asset->current_value = max(0, (float) $asset->current_value + $carryingAmount);
                $asset->disposed_at = null;
                $asset->status = 'active';
            }
        } elseif ($operation->operation_type === 'depreciation') {
            $asset->accumulated_depreciation = max(0, (float) $asset->accumulated_depreciation + ($amount * $direction));
            $asset->current_value = max(0, (float) $asset->current_value - ($amount * $direction));
        } elseif ($operation->operation_type === 'impairment') {
            $asset->current_value = max(0, (float) $asset->current_value - ($amount * $direction));
        } elseif ($operation->operation_type === 'revalue') {
            $asset->current_value = max(0, (float) $asset->current_value + ($amount * $direction));
        }

        $asset->save();
    }

    private function cashDelta(AssetOperation $operation, int $direction): float
    {
        $amount = round((float) $operation->amount, 2);
        $base = match ($operation->operation_type) {
            'purchase', 'rd_capitalize', 'rd_expense' => -1 * $amount,
            'sell' => $amount,
            default => 0.0,
        };

        return $base * $direction;
    }

    private function shiftCashAccountValue(AssetOperation $operation, float $delta): void
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

    private function authorizeProjectOperation(AssetOperation $operation): void
    {
        abort_unless((string) $operation->fid === (string) session('fid', ''), 403);
    }
}
