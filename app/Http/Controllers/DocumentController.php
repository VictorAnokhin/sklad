<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\ZBody;
use App\Models\Conf;
use App\Models\Docs;
use App\Services\FilterService;
use App\Services\DocumentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * DocumentController
 * Migrated from:
 *   document/index.php   (routing + list views)
 *   library/doc-run.php  (save head + body + provodka)
 *   doc-index.php        (bodyAdd / bodyDelete / setClient)
 *   result.php / result2.php (list rendering)
 */
class DocumentController extends Controller
{
    public function __construct(
        private FilterService $filter,
        private DocumentService $docService
        )
    {
    }

    // ── List view ─────────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $fid = session('fid', '');
        $login = session('login', '');
        $status = (int) session('idstatus', session('status', 0));
        $idsklad = session('idsklad', '');
        $idkassa = session('idkassa', '');

        $doc = $request->input('doc', session('doc', 'ZOUT'));
        $pos = (int)$request->input('pos', 0);

        if ($doc !== session('doc')) {
            $pos = 0;
        }
        if ($request->input('num') === '0') {
            session(['client1' => '0', 'client2' => '0', 'num' => '0',
                'numz' => '0', 'typez' => '']);
        }

        session(['doc' => $doc, 'pos' => $pos, 'num' => '0']);

        $this->filter->save($request, $doc, $fid);
        $fd = $this->filter->resolve($doc, $fid);

        $result = Document::init($doc, $pos, $fd, $fid, $login, $status, $idsklad, $idkassa);
        $rows = $result['rows'];
        $total = $result['total'];
        $confMap = $result['confMap'];

        $listData = Document::showDocumentList($rows, $confMap, $doc);
        $items = $listData['items'];
        $total_sum = $listData['total_sum'];

        // Attach clientInfo icons strip to each item
        $viewYear = session('year', date('Y'));
        foreach ($rows as $i => $row) {
            $clientId = $row->client1 ?? 0;
            $numz = $row->numz ?? '0';
            $typez = $row->typez ?? '';
            $rowDocid = in_array($doc, ['ZIN', 'ZOUT'], true) ? ($row->id ?? 0) : ($row->docid ?? 0);
            $summa_ = $row->summa ?? 0;

            // For ZIN/ZOUT root docs: if typez is empty, use the doc's own type
            if (in_array($doc, ['ZIN', 'ZOUT'], true) && ($typez === '' || $typez === '0')) {
                $typez = $doc;
            }

            // For ZIN/ZOUT: show clientInfo even if typez is empty (they ARE the root)
            // For child docs: show only if their parent is ZIN/ZOUT
            $showIcons = false;
            if ($clientId > 0) {
                if (in_array($doc, ['ZIN', 'ZOUT'], true)) {
                    $showIcons = true;
                } elseif (in_array($typez, ['ZIN', 'ZOUT'], true)) {
                    $showIcons = true;
                }
            }

            $items[$i]['clientInfoHtml'] = $showIcons
                ? Docs::clientInfo($clientId, $numz, $typez, $viewYear, $rowDocid, $summa_)
                : '';
        }

        $view = in_array($doc, ['ZIN', 'ZOUT'], true) ? 'document.zakaz' : 'document.index';

        return view($view, compact('items', 'total_sum', 'rows', 'doc', 'pos', 'total', 'fd', 'fid'));
    }

    // ── Show single document ──────────────────────────────────────────────────

    public function show(Request $request)
    {
        $docId = $request->input('doc_id', session('doc_id', '0'));
        $num = $request->input('num', session('num', '0'));
        $year = $request->input('year', session('year', date('Y')));
        $doc = $request->input('doc', session('doc', 'ZOUT'));
        $fid = session('fid', '');
        $incomingParentDocId = (string) $request->input('parent_doc_id', $request->input('doc_id', '0'));

        // Fix year format: "2-28" → "2025" → detect properly
        if (preg_match('/^\d{4}$/', $year)) {
            // already valid
        } elseif (preg_match('/^(\d{2})-(\d{2})$/', $year, $m)) {
            // "2-28" → assume current year
            $year = date('Y');
        } else {
            $year = date('Y');
        }

        session([
            'doc_id' => $docId,
            'num' => $num,
            'year' => $year,
            'doc' => $doc,
            'parent_doc_id' => $incomingParentDocId,
        ]);

        // If num=0 or doc_id=0 → auto-create new document
        if ($num == '0' || $docId == '0') {
            $table = Document::tableForType($doc);
            $newNum = Document::assignNextNum($doc, $fid, $year);
            $now = now();
            $dt = $now->timestamp;

            // Resolve parent order/purchase for child docs
            $parentDocid = session('docid', '0');
            $parentNumz = session('numz', '0');
            $parentTypez = session('typez', '');
            $sumFromRequest = (float) $request->input('sumPO', 0);
            $parentDocument = null;

            if (!in_array($doc, ['ZIN', 'ZOUT'], true) && $incomingParentDocId !== '0') {
                $parentDocid = $incomingParentDocId;
                $parentDocument = DB::table('document')
                    ->where('id', $parentDocid)
                    ->where('firma', $fid)
                    ->first();

                if ($parentDocument) {
                    $parentNumz = (string) ($parentDocument->num ?: $parentDocument->numz ?: '0');
                    $parentTypez = (string) ($parentDocument->type ?: 'ZOUT');
                    session([
                        'docid' => $parentDocid,
                        'numz' => $parentNumz,
                        'typez' => $parentTypez,
                        'client1' => $parentDocument->client1 ?? session('client1', '0'),
                        'client2' => $parentDocument->client2 ?? session('client2', '0'),
                        'sklads' => $parentDocument->sklads ?? session('sklads', ''),
                        'oplata' => $parentDocument->oplata ?? session('oplata', ''),
                        'reteil' => $parentDocument->reteil ?? session('reteil', ''),
                        'reestr' => $parentDocument->reestr ?? session('reestr', ''),
                    ]);
                }
            }

            if (!$parentDocument && $parentDocid !== '0' && !in_array($doc, ['ZIN', 'ZOUT'], true)) {
                $parentDocument = DB::table('document')
                    ->where('id', $parentDocid)
                    ->where('firma', $fid)
                    ->first();
            }

            $newSumma = 0.0;
            if (in_array($doc, ['PO', 'RO'], true)) {
                $newSumma = $sumFromRequest;
            } elseif ($parentDocument) {
                $newSumma = (float) ($parentDocument->summa ?? 0);
            }

            $oplata = (string) ($parentDocument->oplata ?? session('oplata', '') ?? '');
            $reteil = (string) ($parentDocument->reteil ?? session('reteil', '') ?? '');
            $reestr = (string) ($parentDocument->reestr ?? session('reestr', '') ?? '');
            $sklads = (string) ($parentDocument->sklads ?? session('sklads', '') ?? '');
            $money = (string) ($parentDocument->money ?? session('money', '') ?? '');
            $content = (string) ($parentDocument->content ?? '');
            $ttn = (string) ($parentDocument->ttn ?? '');

            $newId = DB::table($table)->insertGetId([
                'id' => 0,
                'num' => $newNum,
                'client1' => session('client1', '0'),
                'client2' => session('client2', '0'),
                'type' => $doc,
                'summa' => $newSumma,
                'status' => 0,
                'data' => $now->format('d-m-Y'),
                'data2' => $now->format('d-m-Y'),
                'time' => $now->format('H:i:s'),
                'firma' => $fid,
                'dt' => $dt,
                'numz' => $parentNumz,
                'typez' => $parentTypez,
                'docid' => in_array($doc, ['ZIN', 'ZOUT'], true) ? 0 : $parentDocid,
                'manager' => session('login', ''),
                'user' => session('login', ''),
                'content' => $content,
                'ttn' => $ttn,
                'oplata' => $oplata,
                'reteil' => $reteil,
                'reestr' => $reestr,
                'sklads' => $sklads,
                'money' => $money,
                'dostup' => 1,
                'work' => session('work', '1'),
            ]);

            if (in_array($doc, ['ZIN', 'ZOUT'], true)) {
                DB::table($table)->where('id', $newId)->update(['docid' => $newId, 'numz' => $newNum]);
                session(['docid' => $newId]);
            }

            session(['doc_id' => $newId, 'num' => $newNum]);

            // Redirect with new params so reload works correctly
            return redirect()->route('document.show', [
                'doc' => $doc,
                'doc_id' => $newId,
                'parent_doc_id' => in_array($doc, ['ZIN', 'ZOUT'], true) ? $newId : $parentDocid,
                'num' => $newNum,
                'year' => $year,
            ]);
        }

        $table = Document::tableForType($doc);
        $document = DB::table($table)->where('id', $docId)->first();

        if (!$document) {
            // Document not found — check if we should auto-create one
            // Only auto-create when num=0; otherwise show error
            return redirect()->route('document.index')->with('error',
                "Документ {$doc} (id={$docId}, num={$num}) не знайдено в таблиці {$table}");
        }

        // Populate session from doc (mirrors legacy class document constructor)
        $parentNumz = in_array($doc, ['ZIN', 'ZOUT'], true)
            ? ($document->num ?: $document->numz)
            : ($document->numz ?: '0');
        $parentTypez = in_array($doc, ['ZIN', 'ZOUT'], true)
            ? $doc
            : ($document->typez ?: '');
        $parentDocid = in_array($doc, ['ZIN', 'ZOUT'], true)
            ? $docId
            : ($document->docid ?: $docId);
        $parentDocument = (!in_array($doc, ['ZIN', 'ZOUT'], true) && $parentDocid)
            ? DB::table('document')->where('id', $parentDocid)->first()
            : null;

        session([
            'numz' => $parentNumz,
            'typez' => $parentTypez,
            'docid' => $parentDocid,
            'parent_doc_id' => $parentDocid,
            'client1' => $document->client1,
            'client2' => $document->client2,
            'sklads' => $document->sklads,
            'oplata' => $document->oplata,
            'reteil' => $document->reteil,
            'reestr' => $document->reestr,
        ]);

        $docIdToFind = in_array($doc, ['ZIN', 'ZOUT']) ? $docId : $parentDocid;
        $lineItems = ZBody::from('z_body as zb')
            ->leftJoin('comp as c', function ($join) {
            $join->on('zb.pnum', '=', 'c.id')
                ->on('zb.firma', '=', 'c.firma');
        })
            ->where('zb.docid', $docIdToFind)
            ->select('zb.*', 'c.name as name')
            ->orderBy('zb.id')
            ->get()
            ->map(function ($item) {
            $item->name = (string)$item->name;
            return $item;
        });

        // Client info (related docs / balance)
        $client = $document->client1 ? DB::table('users')->where('id', $document->client1)->first() : null;

        // Load conf lookups for this doc
        $confIds = array_filter([
            $document->status, $document->sklads, $document->oplata,
            $document->reteil, $document->reestr, $document->typeproduct,
        ]);
        $confMap = [];
        if (!empty($confIds)) {
            $confMap = DB::table('conf')
                ->whereIn('id', array_unique($confIds))
                ->get(['id', 'name', 'color'])->keyBy('id')->toArray();
        }

        // Load all oplata and reestr options for PO/RO dropdowns
        $oplataList = DB::table('conf')->where('type', 'oplata')->where('firma', $fid)->orderBy('name')->get();
        $reestrList = DB::table('conf')->where('type', 'reestr')->where('firma', $fid)->orderBy('name')->get();
        $skladsList = DB::table('conf')->where('type', 'sklads')->where('firma', $fid)->orderBy('name')->get();

        // Related documents (legacy client_info / client_info1)
        $clientId = $document->client1 ?? 0;
        $numz = $parentNumz;
        $typez = $parentTypez;
        $docid = $parentDocid;
        $idstatus = (int)session('idstatus', 0);
        $orderPosted = (int) (
            in_array($doc, ['ZIN', 'ZOUT'], true)
                ? ($document->provodka ?? 0)
                : ($parentDocument->provodka ?? 0)
        ) === 1;

        // Show related docs for root orders/purchases and their child documents
        $isZakazType = in_array($typez, ['ZIN', 'ZOUT'], true);

        // client_info1 — full block with action buttons
        $relatedDocs = null;
        if ($isZakazType && $clientId > 0) {
            $relatedDocs = Docs::clientInfo1(
                $clientId, $numz, $typez, $doc, $idstatus, $year, $docid,
                $document->summa ?? 0, $orderPosted
            );
        }

        // client_info — compact icon strip: only for ZIN / ZOUT documents
        $relatedIcons = null;
        if (in_array($doc, ['ZIN', 'ZOUT'], true) && $clientId > 0) {
            $relatedIcons = Docs::clientInfo(
                $clientId, $numz, $typez, $year, $docid, $document->summa ?? 0
            );
        }

        $documentIndexUrl = route('document.index', ['doc' => $doc]);
        $parentDocumentUrl = $parentDocument
            ? route('document.show', [
                'doc' => $parentDocument->type,
                'doc_id' => $parentDocument->id,
                'num' => $parentDocument->num,
                'year' => strlen((string) ($parentDocument->data ?? '')) >= 10
                    ? substr((string) $parentDocument->data, 6, 4)
                    : $year,
            ])
            : null;

        return view('document.show', compact(
            'document', 'lineItems', 'doc', 'year', 'client', 'confMap',
            'fid', 'relatedDocs', 'relatedIcons', 'oplataList', 'reestrList', 'skladsList',
            'documentIndexUrl', 'parentDocumentUrl', 'parentDocument'
        ));
    }

    // ── Save ──────────────────────────────────────────────────────────────────

    public function save(Request $request)
    {
        $doc = session('doc', '');
        $fid = session('fid', '');
        $year = session('year', date('Y'));
        $run = $request->input('run', '');
        $createDocType = strtoupper((string)$request->input('create_doc_type', ''));

        // ── New document creation buttons ─────────────────────────────────────
        $docTypeMap = [
            'Нова закупівля' => 'ZIN', 'Новый заказ' => 'ZOUT',
            'Новий замовлення' => 'ZOUT', 'Новая закупка' => 'ZIN',
            'Пропозиція' => 'CH', 'Предложение' => 'CH',
            'На виготовлення' => 'WO1', 'На изготовление' => 'WO1',
            'Видача товару' => 'RN', 'Выдача товара' => 'RN',
            'Отримання товару' => 'PN', 'Получение товара' => 'PN',
            'Отримання грошей' => 'PO', 'Получение денег' => 'PO',
            'Видача грошей' => 'RO', 'Выдача денег' => 'RO',
            'Додати фото' => 'RA', 'Добавить фото' => 'RA',
        ];

        if ($createDocType !== '' || isset($docTypeMap[$run])) {
            $docType = $createDocType !== '' ? $createDocType : $docTypeMap[$run];
            $summaPO = in_array($docType, ['PO', 'RO'], true) ? (float)$request->input('sumPO', 0) : 0.0;
            $client1 = session('client1', '0');
            $client2 = session('client2', '0');
            $numz = session('numz', '0');
            $typez = session('typez', '');
            $docid = session('docid', '0');
            $oplata = (string) (session('oplata', '') ?? '');
            $reteil = (string) (session('reteil', '') ?? '');
            $reestr = (string) (session('reestr', '') ?? '');
            $sklads = (string) (session('sklads', '') ?? '');
            $money = (string) (session('money', '') ?? '');

            // Get next number — max+1 for this doc type & firma
            $num = Document::assignNextNum($docType, $fid, $year);

            $table = Document::tableForType($docType);
            $now = now();
            $dt = $now->timestamp;


            $id = DB::table($table)->insertGetId([
                'id' => 0,
                'num' => $num,
                'client1' => $client1,
                'client2' => $client2,
                'type' => $docType,
                'summa' => $summaPO,
                'status' => 0,
                'data' => $now->format('d-m-Y'),
                'data2' => $now->format('d-m-Y'),
                'time' => $now->format('H:i:s'),
                'firma' => $fid,
                'dt' => $dt,
                'numz' => $numz,
                'typez' => $typez,
                'docid' => in_array($docType, ['ZIN', 'ZOUT'], true) ? 0 : $docid,
                'manager' => session('login', ''),
                'user' => session('login', ''),
                'oplata' => $oplata,
                'reteil' => $reteil,
                'reestr' => $reestr,
                'sklads' => $sklads,
                'money' => $money,
                'dostup' => 1,
                'work' => session('work', '1'),
            ]);

            if (in_array($docType, ['ZIN', 'ZOUT'], true)) {
                DB::table($table)->where('id', $id)->update(['docid' => $id, 'numz' => $num]);
                session(['docid' => $id]);
            }

            session(['doc' => $docType, 'doc_id' => $id, 'num' => $num]);

            /* For ZIN/ZOUT just redirect to list; others go to edit form
             if (in_array($docType, ['ZIN', 'ZOUT'], true)) {
             return redirect()->route('document.index', ['doc' => $docType]);
             }
             */
            return redirect()->route('document.show', [
                'doc' => $docType, 'doc_id' => $id, 'num' => $num, 'year' => $year,
            ]);
        }

        // ── Save / Зберегти ───────────────────────────────────────────────────
        if (in_array($run, ['Зберегти', 'Save', 'Сохранить'], true)) {
            $docId = session('doc_id', '0');
            $this->docService->saveHead($request, $docId, $doc, $fid);
            $this->docService->saveBody($request, $docId, $doc, $fid);
            return redirect()->back()->with('success', 'Збережено');
        }

        return redirect()->back();
    }

    // ── Provodka ──────────────────────────────────────────────────────────────

    public function provodka(Request $request)
    {
        $docId = $request->input('doc_id', session('doc_id', '0'));
        $doc = $request->input('doc', session('doc', ''));
        $table = Document::tableForType($doc);
        $document = DB::table($table)->where('id', $docId)->first();
        $isPosted = $this->docService->provodka($request);
        if (!$document) {
            return redirect()->route('document.index', ['doc' => $doc])
                ->with('success', $isPosted ? 'Проводку виконано' : 'Проводку скасовано');
        }

        $year = strlen((string) ($document->data ?? '')) >= 10
            ? substr((string) $document->data, 6, 4)
            : date('Y');

        return redirect()->route('document.show', [
            'doc' => $doc,
            'doc_id' => $docId,
            'num' => $document->num,
            'year' => $year,
        ])->with('success', $isPosted ? 'Проводку виконано' : 'Проводку скасовано');
    }

    // ── Delete ────────────────────────────────────────────────────────────────

    public function destroy(Request $request)
    {
        $docId = $request->input('doc_id', session('doc_id'));
        $doc = $request->input('doc', session('doc', ''));
        $table = Document::tableForType($doc);
        $fid = session('fid', '');

        // Delete related z_body rows (goods) first
        $document = DB::table($table)->where('id', $docId)->where('firma', $fid)->first();
        if ($document) {
            $docIdToFind = in_array($doc, ['ZIN', 'ZOUT']) ? $docId : ($document->docid ?? $docId);
            ZBody::where('docid', $docIdToFind)->delete();
        }

        DB::table($table)->where('id', $docId)->where('firma', $fid)->delete();
        session(['num' => '0', 'doc_id' => '0']);

        return redirect()->route('document.index', ['doc' => $doc]);
    }

    // ── Bulk status ───────────────────────────────────────────────────────────

    public function bulkStatus(Request $request)
    {
        $ids = $request->input('ids', []);
        $status = $request->input('status1', '');
        $doc = $request->input('doc', session('doc', ''));
        $table = Document::tableForType($doc);

        if ($status !== '' && !empty($ids)) {
            DB::table($table)->whereIn('id', $ids)->update(['status' => $status]);
        }

        return redirect()->back();
    }

    // ── z_body: add item ─────────────────────────────────────────────────────

    public function bodyAdd(Request $request)
    {
        $doc = session('doc', '');
        $fid = session('fid', '');
        $docid = session('docid', '0');
        $typez = session('typez', '');
        $numz = session('numz', '0');

        $pnum = $request->input('pnum', '');
        $pid = $request->input('pid', '');
        $pprice = $request->input('pprice', '0');
        $psumma = $request->input('psumma', '0');
        $pcount = $request->input('pcount', '1');

        $docTypes = ['CH', 'PN', 'RN', 'VN', 'WO1', 'AO', 'ZOUT', 'ZIN'];

        if (in_array($doc, $docTypes, true)) {
            ZBody::addOrIncrement($typez, $numz, $pnum, $fid, $docid, $pid, $pprice, $psumma);
        }
        elseif ($doc === 'SP') {
            $exists = ZBody::where('type', $typez)->where('docnum', $numz)
                ->where('pnum', $pnum)->where('firma', $fid)->exists();
            if (!$exists) {
                ZBody::create([
                    'docnum' => $numz, 'pid' => $pid, 'pnum' => $pnum,
                    'pcount' => $pcount, 'pprice' => $pprice, 'psumma' => $psumma,
                    'type' => $typez, 'firma' => $fid,
                ]);
            }
        }

        return redirect()->back();
    }

    // ── z_body: delete item ───────────────────────────────────────────────────

    public function bodyDelete(Request $request)
    {
        $bid = $request->input('bid', '');
        if ($bid !== '')
            ZBody::where('id', $bid)->delete();
        return redirect()->back();
    }

    // ── z_body: update quantity/price ────────────────────────────────────────

    public function bodyUpdate(Request $request)
    {
        $bid = $request->input('bid', '');
        $field = $request->input('field', '');
        $value = $request->input('value', '');
        $allowed = ['pcount', 'pprice', 'psumma'];

        if ($bid !== '' && in_array($field, $allowed, true)) {
            ZBody::where('id', $bid)->update([$field => $value]);
        }

        return response()->json(['ok' => true]);
    }

    // ── Set client on doc ─────────────────────────────────────────────────────

    public function setClient(Request $request)
    {
        $docId = session('doc_id', '0');
        $client1 = session('client1', '0');
        $client2 = session('client2', '0');
        $doc = session('doc', '');
        $table = Document::tableForType($doc);

        DB::table($table)->where('id', $docId)
            ->update(['client1' => $client1, 'client2' => $client2]);

        return redirect()->back();
    }
}
