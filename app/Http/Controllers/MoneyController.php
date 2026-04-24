<?php

namespace App\Http\Controllers;

use App\Models\Conf;
use App\Models\Money;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * MoneyController — cash documents (PO / RO) and cash transfers.
 * All DB logic is in App\Models\Money.
 */
class MoneyController extends Controller
{
    private function activeTab(Request $request): string
    {
        return $request->input('tab') === 'transfers' ? 'transfers' : 'orders';
    }

    private function extractReturnFilters(Request $request): array
    {
        $mapping = [
            'tab' => 'tab',
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
            $value = $key === 'tab'
                ? $request->input($inputName, $request->input('tab', ''))
                : $request->input($inputName, '');

            if ($value !== '' && $value !== null) {
                $filters[$key] = (string) $value;
            }
        }

        return $filters;
    }

    public function index(Request $request)
    {
        $fid = session('fid', '');
        $tab = $this->activeTab($request);
        $pos = (int) $request->input('pos', 0);
        $filters = [
            'q' => trim((string) $request->input('q', '')),
            'type' => trim((string) $request->input('type', '')),
            'money' => trim((string) $request->input('money', '')),
            'reestr' => trim((string) $request->input('reestr', '')),
            'date_from' => trim((string) $request->input('date_from', '')),
            'date_to' => trim((string) $request->input('date_to', '')),
        ];

        $data = $tab === 'transfers'
            ? Money::initTransfers($fid, $pos, $filters)
            : Money::init($fid, $pos, $filters);

        $paymentTypes = ($filters['type'] ?? '') !== ''
            ? Conf::paymentTypesForDocument($fid, $filters['type'])
            : DB::table('conf')
                ->where('type', 'reestr')
                ->where('firma', $fid)
                ->orderBy('name')
                ->get()
                ->map(fn ($item) => Conf::decoratePaymentType($item));

        return view('money.index', array_merge($data, compact('pos', 'fid', 'filters', 'paymentTypes', 'tab')));
    }

    public function show(Request $request)
    {
        $fid = session('fid', '');
        $docId = (int) $request->input('id', 0);
        $tab = $this->activeTab($request);
        $type = $request->input('type', 'PO');
        $returnFilters = $this->extractReturnFilters($request);

        if ($tab === 'transfers') {
            $document = $docId === 0 ? Money::emptyTransferDocument() : Money::findTransfer($docId, $fid);

            if (!$document) {
                return redirect()->route('money.index', ['tab' => 'transfers'])->with('error', 'Документ не знайдено');
            }

            $kassas = Money::kassas($fid, (string) ($document->oplata ?? ''));
            $targetKassas = Money::kassas($fid, (string) ($document->oplata2 ?? ''));

            return view('money.show', compact('document', 'kassas', 'targetKassas', 'returnFilters', 'tab'));
        }

        if ($docId === 0) {
            $document = Money::emptyDocument($type);
        } else {
            $document = Money::find($docId, $fid);

            if (!$document) {
                return redirect()->route('money.index', $returnFilters)->with('error', 'Документ не знайдено');
            }
        }

        $kassas = Money::kassas($fid, (string) ($document->effective_cashbox_id ?? $document->money ?? ''));
        $reestrList = Conf::paymentTypesForDocument($fid, $type);

        return view('money.show', compact('document', 'kassas', 'reestrList', 'returnFilters', 'tab'));
    }

    public function save(Request $request)
    {
        $fid = session('fid', '');
        $id = (int) $request->input('id', 0);
        $tab = $this->activeTab($request);
        $type = $request->input('type', 'PO');
        $shouldPost = $request->boolean('post_after_save');
        $returnFilters = $this->extractReturnFilters($request);

        if ($tab === 'transfers') {
            $fromCashbox = trim((string) $request->input('oplata', ''));
            $toCashbox = trim((string) $request->input('oplata2', ''));
            $summa = (float) $request->input('summa', 0);

            if ($summa <= 0 || $fromCashbox === '' || $toCashbox === '') {
                return redirect()->back()->withInput()->with('error', 'Заповніть суму і обидві каси');
            }

            if ($fromCashbox === $toCashbox) {
                return redirect()->back()->withInput()->with('error', 'Для переводу оберіть різні каси');
            }

            $savedId = Money::saveTransferDocument($id, $fid, [
                'summa' => $summa,
                'content' => (string) $request->input('content', ''),
                'data' => (string) $request->input('data', date('d-m-Y')),
                'oplata' => $fromCashbox,
                'oplata2' => $toCashbox,
            ]);

            $savedDocument = Money::findTransfer($savedId, $fid);
            if (!$savedDocument) {
                return redirect()->route('money.index', ['tab' => 'transfers'])->with('error', 'Документ не знайдено');
            }

            $isCurrentlyPosted = (int) ($savedDocument->provodka ?? 0) === 1;
            $message = 'Збережено';

            if ($shouldPost !== $isCurrentlyPosted) {
                $result = Money::provodkaTransfer($savedId, $fid);

                if (!($result['document'] ?? null)) {
                    return redirect()->route('money.index', ['tab' => 'transfers'])->with('error', 'Документ не знайдено');
                }

                $message = $shouldPost ? 'Збережено та проведено' : 'Збережено, проводку скасовано';
            }

            return redirect()->route('money.show', [
                'id' => $savedId,
                'tab' => 'transfers',
                'return_q' => $returnFilters['q'] ?? null,
                'return_money' => $returnFilters['money'] ?? null,
                'return_date_from' => $returnFilters['date_from'] ?? null,
                'return_date_to' => $returnFilters['date_to'] ?? null,
                'return_pos' => $returnFilters['pos'] ?? null,
            ])->with('success', $message);
        }

        $money = trim((string) $request->input('money', ''));
        if ($money === '') {
            return redirect()
                ->back()
                ->withErrors(['money' => 'Оберіть касу'])
                ->withInput();
        }

        $data = [
            'type' => in_array($type, ['PO', 'RO'], true) ? $type : 'PO',
            'summa' => (float) $request->input('summa', 0),
            'content' => (string) $request->input('content', ''),
            'data' => $request->input('data', date('d-m-Y')),
            'money' => $money,
            'oplata' => $money,
            'reestr' => (string) $request->input('reestr', ''),
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
            'tab' => 'orders',
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
        $id = (int) $request->input('id', 0);
        $tab = $this->activeTab($request);
        $returnFilters = $this->extractReturnFilters($request);

        if ($id > 0) {
            if ($tab === 'transfers') {
                Money::deleteTransferDocument($id, $fid);
            } else {
                Money::deleteDocument($id, $fid);
            }

            return redirect()->route('money.index', $returnFilters)->with('success', 'Документ видалено');
        }

        return redirect()->route('money.index', $returnFilters)->with('error', 'Помилка видалення');
    }

    public function provodka(Request $request)
    {
        $fid = session('fid', '');
        $id = (int) $request->input('id', 0);
        $tab = $this->activeTab($request);
        $returnFilters = $this->extractReturnFilters($request);

        if ($id <= 0) {
            return redirect()->route('money.index', $returnFilters)->with('error', 'Документ не знайдено');
        }

        $result = $tab === 'transfers'
            ? Money::provodkaTransfer($id, $fid)
            : Money::provodka($id, $fid);
        $document = $result['document'] ?? null;
        $isPosted = (bool) ($result['isPosted'] ?? false);

        if (!$document) {
            return redirect()->route('money.index', $returnFilters)->with('error', 'Документ не знайдено');
        }

        return redirect()->route('money.show', [
            'id' => $document->id,
            'type' => $tab === 'transfers' ? null : $document->type,
            'tab' => $tab,
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
