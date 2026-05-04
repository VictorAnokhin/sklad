<?php

namespace App\Http\Controllers;

use App\Models\Conf;
use App\Models\Money;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * MoneyController — cash documents (PPO / PRO) and cash transfers.
 * All DB logic is in App\Models\Money.
 */
class MoneyController extends Controller
{
    private function buildIndexFilters(Request $request): array
    {
        $defaultDateFrom = now()->subDays(30)->format('Y-m-d');
        $defaultDateTo = now()->format('Y-m-d');

        return [
            'q' => trim((string) $request->input('q', '')),
            'type' => trim((string) $request->input('type', '')),
            'money' => trim((string) $request->input('money', '')),
            'reestr' => trim((string) $request->input('reestr', '')),
            'date_from' => trim((string) $request->input('date_from', $defaultDateFrom)),
            'date_to' => trim((string) $request->input('date_to', $defaultDateTo)),
            '_dates_are_default' => trim((string) $request->input('date_from', $defaultDateFrom)) === $defaultDateFrom
                && trim((string) $request->input('date_to', $defaultDateTo)) === $defaultDateTo,
        ];
    }

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
        $pos = (int) $request->input('pos', 0);
        $tab = 'orders';
        $filters = $this->buildIndexFilters($request);
        $datesAreDefault = (bool) ($filters['_dates_are_default'] ?? false);
        unset($filters['_dates_are_default']);

        $data = Money::init($fid, $pos, $filters);

        $paymentTypes = ($filters['type'] ?? '') !== ''
            ? Conf::paymentTypesForDocument($fid, $filters['type'])
            : DB::table('conf')
                ->where('type', 'reestr')
                ->where('firma', $fid)
                ->orderBy('name')
                ->get()
                ->map(fn ($item) => Conf::decoratePaymentType($item));

        $userBalance = (float) (Auth::user()->balance ?? 0);

        $indexRouteName = 'money.index';
        $showRouteName = 'money.show';
        $filterRouteName = 'money.index';

        return view('money.index', array_merge($data, compact('pos', 'fid', 'filters', 'paymentTypes', 'tab', 'userBalance', 'datesAreDefault', 'indexRouteName', 'showRouteName', 'filterRouteName')));
    }

    public function transfers(Request $request)
    {
        $fid = session('fid', '');
        $pos = (int) $request->input('pos', 0);
        $tab = 'transfers';
        $filters = $this->buildIndexFilters($request);
        $datesAreDefault = (bool) ($filters['_dates_are_default'] ?? false);
        unset($filters['_dates_are_default']);

        $data = Money::initTransfers($fid, $pos, $filters);
        $paymentTypes = collect();
        $userBalance = (float) (Auth::user()->balance ?? 0);
        $indexRouteName = 'money.transfers';
        $showRouteName = 'money.show';
        $filterRouteName = 'money.transfers';

        return view('money.index', array_merge($data, compact('pos', 'fid', 'filters', 'paymentTypes', 'tab', 'userBalance', 'datesAreDefault', 'indexRouteName', 'showRouteName', 'filterRouteName')));
    }

    public function show(Request $request)
    {
        $fid = session('fid', '');
        $docId = (int) $request->input('id', 0);
        $tab = $this->activeTab($request);
        $type = $request->input('type', 'PPO');
        $returnFilters = $this->extractReturnFilters($request);

        if ($tab === 'transfers') {
            $document = $docId === 0 ? Money::emptyTransferDocument() : Money::findTransfer($docId, $fid);

            if (!$document) {
                return redirect()->route('money.transfers')->with('error', 'Документ не знайдено');
            }

            $kassas = Money::kassas($fid, (string) ($document->oplata ?? ''));
            $targetKassas = Money::kassas($fid, (string) ($document->oplata2 ?? ''));
            $indexRouteName = 'money.transfers';

            return view('money.show', compact('document', 'kassas', 'targetKassas', 'returnFilters', 'tab', 'indexRouteName'));
        }

        if ($docId === 0) {
            $document = Money::emptyDocument($type);
            $document->client2 = (string) (Auth::id() ?: session('userid', '0'));
            $document->owner_balance = (float) (Auth::user()->balance ?? 0);
            $document->owner_name = (string) (Auth::user()->name ?? '');
            $document->owner_secondname = (string) (Auth::user()->secondname ?? '');
            $document->owner_fathername = (string) (Auth::user()->fathername ?? '');
            $document->owner_orgname = (string) (Auth::user()->orgname ?? '');
        } else {
            $document = Money::find($docId, $fid);

            if (!$document) {
                return redirect()->route('money.index', $returnFilters)->with('error', 'Документ не знайдено');
            }
        }

        $reestrList = Conf::paymentTypesForDocument($fid, $type);
        $clientStatuses = DB::table('conf')
            ->where('type', 'tclient')
            ->where('firma', $fid)
            ->orderBy('name')
            ->get();

        return view('money.show', compact('document', 'reestrList', 'returnFilters', 'tab', 'clientStatuses'));
    }

    public function save(Request $request)
    {
        $fid = session('fid', '');
        $id = (int) $request->input('id', 0);
        $tab = $this->activeTab($request);
        $type = $request->input('type', 'PPO');
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
                return redirect()->route('money.transfers')->with('error', 'Документ не знайдено');
            }

            $isCurrentlyPosted = (int) ($savedDocument->provodka ?? 0) === 1;
            $message = 'Збережено';

            if ($shouldPost !== $isCurrentlyPosted) {
                $result = Money::provodkaTransfer($savedId, $fid);

                if (!($result['document'] ?? null)) {
                    return redirect()->route('money.transfers')->with('error', 'Документ не знайдено');
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

        $client1 = trim((string) $request->input('client1', ''));
        if ($client1 === '' || $client1 === '0') {
            return redirect()
                ->back()
                ->withErrors(['client1' => 'Оберіть клієнта'])
                ->withInput();
        }

        $data = [
            'type' => in_array($type, ['PPO', 'PRO'], true) ? $type : 'PPO',
            'summa' => (float) $request->input('summa', 0),
            'content' => (string) $request->input('content', ''),
            'data' => $request->input('data', date('d-m-Y')),
            'money' => '',
            'oplata' => '',
            'reestr' => (string) $request->input('reestr', ''),
            'client1' => $client1,
            'client2' => (string) (Auth::id() ?: session('userid', '0')),
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
