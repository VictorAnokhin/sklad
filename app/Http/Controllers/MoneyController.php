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
    private function extractReturnFilters(Request $request): array
    {
        $mapping = [
            'q' => 'return_q',
            'type' => 'return_filter_type',
            'money' => 'return_money',
            'reestr' => 'return_reestr',
            'date_from' => 'return_date_from',
            'date_to' => 'return_date_to',
            'pos' => 'return_pos',
        ];

        $filters = [];

        foreach ($mapping as $key => $inputName) {
            $value = $request->input($inputName, '');

            if ($value !== '' && $value !== null) {
                $filters[$key] = (string) $value;
            }
        }

        return $filters;
    }

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
        $returnFilters = $this->extractReturnFilters($request);

        if ($doc_id === 0) {
            $document = Money::emptyDocument($type);
        } else {
            $document = Money::find($doc_id, $fid);

            if (!$document) {
                return redirect()->route('money.index', $returnFilters)->with('error', 'Документ не знайдено');
            }
        }

        $kassas = Money::kassas($fid);

        return view('money.show', compact('document', 'kassas', 'returnFilters'));
    }

    public function save(Request $request)
    {
        $fid  = session('fid', '');
        $id   = (int)$request->input('id', 0);
        $type = $request->input('type', 'PO');
        $shouldPost = $request->boolean('post_after_save');
        $returnFilters = $this->extractReturnFilters($request);

        $data = [
            'type'    => in_array($type, ['PO', 'RO']) ? $type : 'PO',
            'summa'   => (float)$request->input('summa', 0),
            'content' => (string)$request->input('content', ''),
            'data'    => $request->input('data', date('d-m-Y')),
            'money'   => (string)$request->input('money', ''),
            'client1' => $request->input('client1', '') ?: '0',
        ];

        $savedId = Money::saveDocument($id, $fid, $data);
        $savedDocument = Money::find($savedId, $fid);

        if (!$savedDocument) {
            return redirect()->route('money.index', $returnFilters)->with('error', 'Документ не знайдено');
        }

        $isCurrentlyPosted = (int) ($savedDocument->provodka ?? 0) === 1;
        $message = 'Збережено';

        if ($shouldPost !== $isCurrentlyPosted) {
            $result = Money::provodka($savedId, $fid);

            if (!($result['document'] ?? null)) {
                return redirect()->route('money.index', $returnFilters)->with('error', 'Документ не знайдено');
            }

            $message = $shouldPost ? 'Збережено та проведено' : 'Збережено, проводку скасовано';
        }

        return redirect()->route('money.show', [
            'id' => $savedId,
            'type' => $data['type'],
            'return_q' => $returnFilters['q'] ?? null,
            'return_filter_type' => $returnFilters['type'] ?? null,
            'return_money' => $returnFilters['money'] ?? null,
            'return_reestr' => $returnFilters['reestr'] ?? null,
            'return_date_from' => $returnFilters['date_from'] ?? null,
            'return_date_to' => $returnFilters['date_to'] ?? null,
            'return_pos' => $returnFilters['pos'] ?? null,
        ])->with('success', $message);
    }

    public function destroy(Request $request)
    {
        $fid = session('fid', '');
        $id = (int)$request->input('id', 0);
        $returnFilters = $this->extractReturnFilters($request);

        if ($id > 0) {
            Money::deleteDocument($id, $fid);
            return redirect()->route('money.index', $returnFilters)->with('success', 'Документ видалено');
        }

        return redirect()->route('money.index', $returnFilters)->with('error', 'Помилка видалення');
    }

    public function provodka(Request $request)
    {
        $fid = session('fid', '');
        $id = (int) $request->input('id', 0);
        $returnFilters = $this->extractReturnFilters($request);

        if ($id <= 0) {
            return redirect()->route('money.index', $returnFilters)->with('error', 'Документ не знайдено');
        }

        $result = Money::provodka($id, $fid);
        $document = $result['document'] ?? null;
        $isPosted = (bool) ($result['isPosted'] ?? false);

        if (!$document) {
            return redirect()->route('money.index', $returnFilters)->with('error', 'Документ не знайдено');
        }

        return redirect()->route('money.show', [
            'id' => $document->id,
            'type' => $document->type,
            'return_q' => $returnFilters['q'] ?? null,
            'return_filter_type' => $returnFilters['type'] ?? null,
            'return_money' => $returnFilters['money'] ?? null,
            'return_reestr' => $returnFilters['reestr'] ?? null,
            'return_date_from' => $returnFilters['date_from'] ?? null,
            'return_date_to' => $returnFilters['date_to'] ?? null,
            'return_pos' => $returnFilters['pos'] ?? null,
        ])->with('success', $isPosted ? 'Проводку виконано' : 'Проводку скасовано');
    }
}
