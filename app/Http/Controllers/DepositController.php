<?php

namespace App\Http\Controllers;

use App\Models\Deposit;
use Illuminate\Http\Request;

class DepositController extends Controller
{
    public function index(Request $request)
    {
        $fid = session('fid', '');
        $pos = (int) $request->input('pos', 0);
        $data = Deposit::init($fid, $pos);

        return view('deposit.index', array_merge($data, compact('pos')));
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
        if ($id === 0) {
            $document->docum = in_array($requestedMode, ['topup', 'withdraw', 'exchange'], true) ? $requestedMode : 'topup';
        }

        $oplatas = Deposit::oplatas($fid);
        $deposits = Deposit::deposits($fid);

        return view('deposit.show', compact('document', 'oplatas', 'deposits'));
    }

    public function save(Request $request)
    {
        $fid = session('fid', '');
        $id = (int) $request->input('id', 0);
        $shouldPost = $request->boolean('post_after_save');
        $mode = (string) $request->input('mode', 'topup');
        $summa = (float) $request->input('summa', 0);
        $oplata = (string) $request->input('oplata', '');
        $oplata2 = (string) $request->input('oplata2', '');
        $money = (string) $request->input('money', '');

        if (!in_array($mode, ['topup', 'withdraw', 'exchange'], true)) {
            $mode = 'topup';
        }

        $isInvalid = match ($mode) {
            'topup' => $summa <= 0 || $oplata === '' || $money === '',
            'withdraw' => $summa <= 0 || $money === '' || $oplata2 === '',
            'exchange' => $summa <= 0 || $oplata === '' || $oplata2 === '',
            default => true,
        };

        if ($isInvalid) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Заповніть суму і обидва рахунки для вибраного типу операції');
        }

        if ($mode === 'exchange' && $oplata === $oplata2) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Для обмена выберите разные кассы');
        }

        $data = [
            'summa' => $summa,
            'content' => (string) $request->input('content', ''),
            'data' => (string) $request->input('data', date('d-m-Y')),
            'docum' => $mode,
            'oplata' => $oplata,
            'oplata2' => $oplata2,
            'money' => $money,
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
