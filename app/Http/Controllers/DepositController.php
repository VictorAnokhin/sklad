<?php

namespace App\Http\Controllers;

use App\Models\Deposit;
use App\Models\Money;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DepositController extends Controller
{
    public function index(Request $request)
    {
        $fid = session('fid', '');
        $pos = (int) $request->input('pos', 0);
        $defaultDateFrom = now()->subDays(30)->format('Y-m-d');
        $defaultDateTo = now()->format('Y-m-d');
        $filters = [
            'q' => $this->safeFilterText($request->input('q', ''), 30),
            'mode' => trim((string) $request->input('mode', '')),
            'date_from' => trim((string) $request->input('date_from', $defaultDateFrom)),
            'date_to' => trim((string) $request->input('date_to', $defaultDateTo)),
            'tab' => in_array((string) $request->input('tab', ''), ['deposits', 'pools'], true)
                ? (string) $request->input('tab')
                : '',
        ];
        $datesAreDefault = $filters['date_from'] === $defaultDateFrom
            && $filters['date_to'] === $defaultDateTo;
        $usesPoolDeposits = $this->usesPoolDeposits($fid);
        if (! $usesPoolDeposits) {
            $filters['tab'] = '';
        } elseif ($filters['tab'] === '') {
            $filters['tab'] = 'deposits';
        }
        $data = Deposit::init($fid, $pos, $filters);
        $depositPools = $this->depositPoolOptions();
        $poolMap = $depositPools->pluck('name', 'asset_key');

        return view('deposit.index', array_merge($data, compact('pos', 'filters', 'datesAreDefault', 'usesPoolDeposits', 'depositPools', 'poolMap')));
    }

    private function safeFilterText(mixed $value, int $maxLength): string
    {
        $text = preg_replace('/[\x00-\x1F\x7F<>{}\[\]\\\\\/=;:*|~^$#@!?%&+]/u', '', (string) ($value ?? ''));
        $text = preg_replace("/[^\p{L}\p{M}\p{N}\s.,'\"’`-]/u", '', (string) $text);
        $text = preg_replace('/\s+/u', ' ', trim(strip_tags((string) $text)));

        return mb_substr((string) $text, 0, $maxLength);
    }

    public function show(Request $request)
    {
        $fid = session('fid', '');
        $id = (int) $request->input('id', 0);
        $requestedMode = (string) $request->input('mode', 'topup');
        $requestedTarget = (string) $request->input('target', '');

        $document = $id > 0 ? Deposit::find($id, $fid) : Deposit::emptyDocument();
        if (!$document) {
            return redirect()->route('deposit.index')->with('error', 'Документ не знайдено');
        }
        if ($id > 0 && (string) ($document->docum ?? '') === 'exchange') {
            return redirect()->route('money.show', ['id' => $document->id, 'tab' => 'transfers']);
        }
        if ($id === 0) {
            $document->docum = in_array($requestedMode, ['topup', 'withdraw'], true) ? $requestedMode : 'topup';
            $document->client2 = (string) (Auth::id() ?: session('userid', '0'));
            $document->owner_balance = (string) (Auth::user()->balance ?? '');
            $document->owner_name = (string) (Auth::user()->name ?? '');
            $document->owner_secondname = (string) (Auth::user()->secondname ?? '');
            $document->owner_fathername = (string) (Auth::user()->fathername ?? '');
            $document->owner_orgname = (string) (Auth::user()->orgname ?? '');
        }

        $depositPools = $this->depositPoolOptions();
        $usesPoolDeposits = $this->usesPoolDeposits($fid);
        $documentMoney = (string) ($document->money ?? '');
        $target = in_array($requestedTarget, ['deposit', 'pool'], true)
            ? $requestedTarget
            : ($documentMoney !== ''
                ? (str_starts_with($documentMoney, 'pool:') ? 'pool' : 'deposit')
                : ($usesPoolDeposits ? 'pool' : 'deposit'));
        $deposits = $target === 'pool' ? collect() : Deposit::deposits($fid);
        $depositPools = $target === 'pool' ? $depositPools : collect();
        $ownerUserId = (string) (($document->client2 ?? '') ?: (Auth::id() ?: session('userid', '0')));
        $ownerBalances = Money::cachedUserBalances($ownerUserId, $fid, $document->owner_balance ?? '');
        if ($ownerBalances === []) {
            $ownerBalances = [[
                'amount' => '0',
                'currency' => (string) ($document->currency_from ?? 'UAH'),
                'is_default' => true,
            ]];
        }

        return view('deposit.show', compact('document', 'deposits', 'depositPools', 'ownerBalances', 'usesPoolDeposits', 'target'));
    }

    public function save(Request $request)
    {
        $fid = session('fid', '');
        $id = (int) $request->input('id', 0);
        $shouldPost = $request->boolean('post_after_save');
        $mode = (string) $request->input('mode', 'topup');
        $summa = (float) $request->input('summa', 0);
        $money = (string) $request->input('money', '');
        $target = $this->depositTarget((string) $request->input('target', ''), $money);
        $tab = $this->depositTargetTab($target);
        $balanceCurrency = strtoupper(preg_replace('/[^A-Z0-9]/', '', (string) $request->input('balance_currency', 'UAH')) ?: 'UAH');

        if (!in_array($mode, ['topup', 'withdraw'], true)) {
            $mode = 'topup';
        }

        $isInvalid = match ($mode) {
            'topup' => $summa <= 0 || $money === '',
            'withdraw' => $summa <= 0 || $money === '',
            default => true,
        };

        if ($isInvalid) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Заповніть суму і обидва рахунки для вибраного типу операції');
        }

        $depositCurrency = Deposit::depositCurrency($fid, $money);
        if ($depositCurrency !== $balanceCurrency) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', "Валюта балансу {$balanceCurrency} не збігається з валютою депозиту {$depositCurrency}");
        }

        $data = [
            'summa' => $summa,
            'content' => (string) $request->input('content', ''),
            'data' => (string) $request->input('data', date('d-m-Y')),
            'docum' => $mode,
            'oplata' => '',
            'oplata2' => '',
            'money' => $money,
            'currency_from' => $balanceCurrency,
            'client2' => (string) (Auth::id() ?: session('userid', '0')),
        ];

        $savedId = Deposit::saveDocument($id, $fid, $data);

        if ($shouldPost) {
            $postingResult = Deposit::provodka($savedId, $fid);
            if (($postingResult['error'] ?? '') !== '') {
                return redirect()
                    ->route('deposit.show', ['id' => $savedId, 'target' => $target])
                    ->withInput()
                    ->with('error', $postingResult['error']);
            }
        }

        return redirect()
            ->route('deposit.index', ['tab' => $tab])
            ->with('success', $shouldPost ? 'Збережено та проведено' : 'Збережено');
    }

    public function destroy(Request $request)
    {
        $fid = session('fid', '');
        $id = (int) $request->input('id', 0);
        $document = $id > 0 ? Deposit::find($id, $fid) : null;
        $target = $this->depositTarget((string) $request->input('target', ''), (string) ($document->money ?? ''));
        $tab = $this->depositTargetTab($target);

        if ($id > 0) {
            Deposit::deleteDocument($id, $fid);

            return redirect()->route('deposit.index', ['tab' => $tab])->with('success', 'Документ видалено');
        }

        return redirect()->route('deposit.index', ['tab' => $tab])->with('error', 'Помилка видалення');
    }

    public function provodka(Request $request)
    {
        $fid = session('fid', '');
        $id = (int) $request->input('id', 0);
        $target = (string) $request->input('target', '');

        if ($id <= 0) {
            return redirect()->route('deposit.index', ['tab' => $this->depositTargetTab($this->depositTarget($target, ''))])->with('error', 'Документ не знайдено');
        }

        $result = Deposit::provodka($id, $fid);
        $document = $result['document'] ?? null;
        $target = $this->depositTarget($target, (string) ($document->money ?? ''));
        $tab = $this->depositTargetTab($target);

        if (!$document) {
            return redirect()->route('deposit.index', ['tab' => $tab])->with('error', 'Документ не знайдено');
        }

        if (($result['error'] ?? '') !== '') {
            return redirect()
                ->route('deposit.show', ['id' => $document->id, 'target' => $target])
                ->with('error', $result['error']);
        }

        return redirect()
            ->route('deposit.index', ['tab' => $tab])
            ->with('success', ($result['isPosted'] ?? false) ? 'Проводку виконано' : 'Проводку скасовано');
    }

    private function depositTarget(string $target, string $money): string
    {
        if (in_array($target, ['deposit', 'pool'], true)) {
            return $target;
        }

        return str_starts_with($money, 'pool:') ? 'pool' : 'deposit';
    }

    private function depositTargetTab(string $target): string
    {
        return $target === 'pool' ? 'pools' : 'deposits';
    }

    private function depositPoolOptions()
    {
        if (!Schema::hasTable('fund_pools')) {
            return collect();
        }

        $query = DB::table('fund_pools');

        if (Schema::hasColumn('fund_pools', 'active')) {
            $query->orderByDesc('active');
        }
        if (Schema::hasColumn('fund_pools', 'risk_level')) {
            $query->orderBy('risk_level');
        }

        return $query
            ->orderBy('name')
            ->get()
            ->map(function ($pool) {
                $symbol = $this->normalizeCurrencyCode($pool->symbol ?? $this->symbolFromCoinType((string) ($pool->coin_type ?? '')));
                $targetApy = (int) ($pool->target_apy_bps ?? 0);
                $realizedApy = (int) ($pool->realized_apy_bps ?? 0);

                return (object) [
                    'id' => (int) $pool->id,
                    'asset_key' => 'pool:' . (int) $pool->id,
                    'name' => (string) ($pool->name ?? 'Pool #' . $pool->id),
                    'currency' => $symbol !== '' ? $symbol : 'USDC',
                    'active' => (bool) ($pool->active ?? true),
                    'is_default_deposit' => (bool) ($pool->is_default_deposit ?? false),
                    'balance' => Schema::hasColumn('fund_pools', 'balance') ? (float) ($pool->balance ?? 0) : 0.0,
                    'apy_bps' => $realizedApy > 0 ? $realizedApy : $targetApy,
                    'description' => (string) ($pool->description ?? ''),
                ];
            });
    }

    private function usesPoolDeposits(mixed $fid): bool
    {
        if (!Schema::hasTable('project') || $fid === '' || $fid === null) {
            return false;
        }

        $project = DB::table('project')->where('id', (int) $fid)->first();
        if (!$project) {
            return false;
        }

        $projectType = strtolower(trim((string) ($project->project_type ?? '')));
        $typeLabel = mb_strtolower(trim((string) ($project->type ?? $project->name ?? '')));

        return $projectType === 'trade' || $typeLabel === 'торговля';
    }

    private function normalizeCurrencyCode(mixed $value): string
    {
        $currency = strtoupper(preg_replace('/[^A-Z0-9]/', '', (string) $value) ?? '');

        return $currency !== '' ? substr($currency, 0, 10) : 'USDC';
    }

    private function symbolFromCoinType(string $coinType): string
    {
        $parts = explode('::', trim($coinType));

        return strtoupper((string) end($parts));
    }

}
