<?php

namespace App\Http\Controllers;

use App\Models\Money;
use Illuminate\Http\Request;

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

        $data = Money::init($fid, $pos);

        return view('money.index', array_merge($data, compact('pos', 'fid')));
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

        $kassas = Money::kassas();

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
            'content' => $request->input('content', ''),
            'data'    => $request->input('data', date('d-m-Y')),
            'money'   => $request->input('money', ''),
            'client1' => $request->input('client1', '') ?: null,
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
}
