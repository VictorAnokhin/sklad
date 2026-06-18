<?php

namespace App\Http\Controllers;

use App\Models\Deposit;
use App\Models\Money;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DepositController extends Controller
{
    public function index(Request $request)
    {
        $fid = session('fid', '');
        $pos = (int) $request->input('pos', 0);
        $defaultDateFrom = now()->subDays(30)->format('Y-m-d');
        $defaultDateTo = now()->format('Y-m-d');
        $filters = [
            'q' => trim((string) $request->input('q', '')),
            'mode' => trim((string) $request->input('mode', '')),
            'date_from' => trim((string) $request->input('date_from', $defaultDateFrom)),
            'date_to' => trim((string) $request->input('date_to', $defaultDateTo)),
        ];
        $datesAreDefault = $filters['date_from'] === $defaultDateFrom
            && $filters['date_to'] === $defaultDateTo;
        $data = Deposit::init($fid, $pos, $filters);

        return view('deposit.index', array_merge($data, compact('pos', 'filters', 'datesAreDefault')));
    }

    public function show(Request $request)
    {
        $fid = session('fid', '');
        $id = (int) $request->input('id', 0);
        $requestedMode = (string) $request->input('mode', 'topup');

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

        $deposits = Deposit::deposits($fid);
        $ownerUserId = (string) (($document->client2 ?? '') ?: (Auth::id() ?: session('userid', '0')));
        $ownerBalances = Money::cachedUserBalances($ownerUserId, $fid, $document->owner_balance ?? '');
        if ($ownerBalances === []) {
            $ownerBalances = [[
                'amount' => '0',
                'currency' => (string) ($document->currency_from ?? 'UAH'),
                'is_default' => true,
            ]];
        }

        return view('deposit.show', compact('document', 'deposits', 'ownerBalances'));
    }

    public function save(Request $request)
    {
        $fid = session('fid', '');
        $id = (int) $request->input('id', 0);
        $shouldPost = $request->boolean('post_after_save');
        $mode = (string) $request->input('mode', 'topup');
        $summa = (float) $request->input('summa', 0);
        $money = (string) $request->input('money', '');
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
            Deposit::provodka($savedId, $fid);
        }

        return redirect()
            ->route('deposit.show', ['id' => $savedId])
            ->with('success', $shouldPost ? 'Збережено та проведено' : 'Збережено');
    }

    public function destroy(Request $request)
    {
        $fid = session('fid', '');
        $id = (int) $request->input('id', 0);

        if ($id > 0) {
            Deposit::deleteDocument($id, $fid);

            return redirect()->route('deposit.index')->with('success', 'Документ видалено');
        }

        return redirect()->route('deposit.index')->with('error', 'Помилка видалення');
    }

    public function provodka(Request $request)
    {
        $fid = session('fid', '');
        $id = (int) $request->input('id', 0);

        if ($id <= 0) {
            return redirect()->route('deposit.index')->with('error', 'Документ не знайдено');
        }

        $result = Deposit::provodka($id, $fid);
        $document = $result['document'] ?? null;

        if (!$document) {
            return redirect()->route('deposit.index')->with('error', 'Документ не знайдено');
        }

        return redirect()
            ->route('deposit.show', ['id' => $document->id])
            ->with('success', ($result['isPosted'] ?? false) ? 'Проводку виконано' : 'Проводку скасовано');
    }
}
