<?php

namespace App\Http\Controllers;

use App\Models\Money;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * MoneyController — cash documents (PO / RO)
 * All DB logic is in App\Models\Money
 */
class MoneyController extends Controller
{
    public function index(Request $request)
    {
        $fid = session('fid', '');
        $pos = (int)$request->input('pos', 0);
        $filters = [
            'q' => trim((string)$request->input('q', '')),
            'type' => trim((string)$request->input('type', '')),
            'money' => trim((string)$request->input('money', '')),
            'reestr' => trim((string)$request->input('reestr', '')),
            'date_from' => trim((string)$request->input('date_from', '')),
            'date_to' => trim((string)$request->input('date_to', '')),
        ];

        $data = Money::init($fid, $pos, $filters);
        $paymentTypes = DB::table('conf')
            ->where('type', 'reestr')
            ->where('firma', $fid)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('money.index', array_merge($data, compact('pos', 'fid', 'filters', 'paymentTypes')));
    }

    public function show(Request $request)
    {
        $fid    = session('fid', '');
        $doc_id = (int)$request->input('id', 0);
        $type   = $request->input('type', 'PO');

        if ($doc_id === 0) {
            $document = Money::emptyDocument($type);
        } else {
            $document = Money::find($doc_id, $fid);

            if (!$document) {
                return redirect()->route('money.index')->with('error', 'Документ не знайдено');
            }
        }

        $kassas = Money::kassas($fid);

        return view('money.show', compact('document', 'kassas'));
    }

    public function save(Request $request)
    {
        $fid  = session('fid', '');
        $id   = (int)$request->input('id', 0);
        $type = $request->input('type', 'PO');

        $data = [
            'type'    => in_array($type, ['PO', 'RO']) ? $type : 'PO',
            'summa'   => (float)$request->input('summa', 0),
            'content' => (string)$request->input('content', ''),
            'data'    => $request->input('data', date('d-m-Y')),
            'money'   => (string)$request->input('money', ''),
            'client1' => $request->input('client1', '') ?: '0',
        ];

        Money::saveDocument($id, $fid, $data);

        return redirect()->route('money.index')->with('success', 'Збережено');
    }

    public function destroy(Request $request)
    {
        $fid = session('fid', '');
        $id = (int)$request->input('id', 0);

        if ($id > 0) {
            Money::deleteDocument($id, $fid);
            return redirect()->route('money.index')->with('success', 'Документ видалено');
        }

        return redirect()->route('money.index')->with('error', 'Помилка видалення');
    }

    public function provodka(Request $request)
    {
        $fid = session('fid', '');
        $id = (int) $request->input('id', 0);

        if ($id <= 0) {
            return redirect()->route('money.index')->with('error', 'Документ не знайдено');
        }

        $result = Money::provodka($id, $fid);
        $document = $result['document'] ?? null;
        $isPosted = (bool) ($result['isPosted'] ?? false);

        if (!$document) {
            return redirect()->route('money.index')->with('error', 'Документ не знайдено');
        }

        return redirect()->route('money.show', [
            'id' => $document->id,
            'type' => $document->type,
        ])->with('success', $isPosted ? 'Проводку виконано' : 'Проводку скасовано');
    }
}
