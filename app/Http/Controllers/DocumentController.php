<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\ZBody;
use App\Models\Conf;
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
        private FilterService  $filter,
        private DocumentService $docService
    ) {}

    // ── List view ─────────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $fid      = session('fid', '');
        $login    = session('login', '');
        $idstatus = (int)session('idstatus', 0);
        $idsklad  = session('idsklad', '');
        $idkassa  = session('idkassa', '');

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

        $table    = Document::tableForType($doc);
        $hasUserF = $fd['userSql'] !== '' || $fd['fName'] !== '';

        if ($hasUserF) {
            $base = "FROM {$table} d JOIN users u ON u.id = d.client1
                     WHERE d.firma = ? AND d.type LIKE ?
                     {$fd['userSql']} {$fd['docSql']}";
            $bp   = [$fid, "%{$doc}%", ...$fd['params']];
        } else {
            $base = "FROM {$table} d JOIN users u ON u.id = d.client1
                     WHERE (d.dostup <= ? OR d.manager = ? OR d.sklads = ? OR d.oplata = ?)
                       AND d.firma = ? AND d.type LIKE ?
                     {$fd['docSql']}";
            $bp   = [$idstatus, $login, $idsklad, $idkassa, $fid, "%{$doc}%", ...$fd['params']];
        }

        $total = DB::selectOne("SELECT COUNT(*) AS n {$base}", $bp)->n;

        $cols = "d.id, d.num, d.client1, d.time, d.data, d.data2, d.type,
                 d.summa, d.bonus, d.status, d.content, d.ttn,
                 d.sklads, d.reteil, d.oplata, d.reestr, d.docum,
                 d.manager, d.provodka, d.money, d.numz, d.typez, d.client2,
                 u.orgname, u.kod1, u.secondname, u.name, u.fathername,
                 u.name2, u.region, u.city, u.poshta, u.phone, u.top";

        $sort = 'ORDER BY d.dt DESC, d.time DESC, d.num DESC';
        $rows = DB::select(
            "SELECT {$cols} {$base} {$sort} LIMIT ?, ?",
            [...$bp, $pos, 30]
        );

        // Batch-load conf (status, money, sklads, reteil) to avoid N+1
        $confIds = [];
        foreach ($rows as $r) {
            if ($r->status) $confIds[] = $r->status;
            if ($r->money)  $confIds[] = $r->money;
            if ($r->sklads) $confIds[] = $r->sklads;
            if ($r->reteil) $confIds[] = $r->reteil;
        }
        $confMap = [];
        if (!empty($confIds)) {
            $confMap = DB::table('conf')
                ->whereIn('id', array_unique($confIds))
                ->get(['id', 'name', 'color', 'status'])
                ->keyBy('id')->toArray();
        }

        $view = in_array($doc, ['ZIN', 'ZOUT'], true) ? 'document.zakaz' : 'document.index';

        return view($view, compact('rows', 'doc', 'pos', 'total', 'confMap', 'fd', 'fid'));
    }

    // ── Show single document ──────────────────────────────────────────────────

    public function show(Request $request)
    {
        $docId = $request->input('doc_id', session('doc_id', '0'));
        $num   = $request->input('num',    session('num', '0'));
        $year  = $request->input('year',   session('year', date('Y')));
        $doc   = $request->input('doc',    session('doc', 'ZOUT'));
        $fid   = session('fid', '');

        session(['doc_id' => $docId, 'num' => $num, 'year' => $year, 'doc' => $doc]);

        $table    = Document::tableForType($doc);
        $document = DB::table($table)->where('id', $docId)->first();

        if (!$document) return redirect()->route('document.index');

        // Populate session from doc (mirrors legacy class document constructor)
        session([
            'numz'     => $document->numz,
            'typez'    => $document->typez,
            'docid'    => in_array($doc, ['ZIN', 'ZOUT'], true) ? $docId : $document->docid,
            'client1'  => $document->client1,
            'client2'  => $document->client2,
            'sklads'   => $document->sklads,
            'oplata'   => $document->oplata,
            'reteil'   => $document->reteil,
            'reestr'   => $document->reestr,
        ]);

        $lineItems = ZBody::where('docid', in_array($doc, ['ZIN','ZOUT']) ? $docId : $document->docid)
                          ->orderBy('id')->get();

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

        return view('document.show', compact(
            'document', 'lineItems', 'doc', 'year', 'client', 'confMap', 'fid'
        ));
    }

    // ── Save ──────────────────────────────────────────────────────────────────

    public function save(Request $request)
    {
        $doc   = session('doc', '');
        $fid   = session('fid', '');
        $year  = session('year', date('Y'));
        $run   = $request->input('run', '');

        // ── New document creation buttons ─────────────────────────────────────
        $docTypeMap = [
            'Нова закупівля'   => 'ZIN',  'Новый заказ'    => 'ZOUT',
            'Новий замовлення' => 'ZOUT', 'Новая закупка'  => 'ZIN',
            'Пропозиція'       => 'CH',   'Предложение'    => 'CH',
            'На виготовлення'  => 'WO1',  'На изготовление'=> 'WO1',
            'Видача товару'    => 'RN',   'Выдача товара'  => 'RN',
            'Отримання товару' => 'PN',   'Получение товара'=> 'PN',
            'Отримання грошей' => 'PO',   'Получение денег'=> 'PO',
            'Видача грошей'    => 'RO',   'Выдача денег'   => 'RO',
            'Додати фото'      => 'RA',   'Добавить фото'  => 'RA',
        ];

        if (isset($docTypeMap[$run])) {
            $docType  = $docTypeMap[$run];
            $summaPO  = in_array($docType, ['PO', 'RO'], true) ? (float)$request->input('sumPO', 0) : 0.0;
            $client1  = session('client1', '0');
            $client2  = session('client2', '0');
            $numz     = session('numz', '0');
            $typez    = session('typez', '');
            $docid    = session('docid', '0');

            // Get next number
            $num = Document::nextNum($docType, $fid, $year);

            $table = Document::tableForType($docType);
            $now   = now();
            $dt    = $now->timestamp;

            $id = DB::table($table)->insertGetId([
                'num'      => $num,
                'client1'  => $client1,
                'client2'  => $client2,
                'type'     => $docType,
                'summa'    => $summaPO,
                'status'   => 0,
                'data'     => $now->format('d-m-Y'),
                'data2'    => $now->format('d-m-Y'),
                'time'     => $now->format('H:i:s'),
                'firma'    => $fid,
                'dt'       => $dt,
                'numz'     => $numz,
                'typez'    => $typez,
                'docid'    => in_array($docType, ['ZIN','ZOUT'], true) ? 0 : $docid,
                'manager'  => convert_to_base(session('login', '')),
                'user'     => convert_to_base(session('login', '')),
                'dostup'   => 1,
                'work'     => session('work', '1'),
            ]);

            if (in_array($docType, ['ZIN', 'ZOUT'], true)) {
                DB::table($table)->where('id', $id)->update(['docid' => $id, 'numz' => $num]);
                session(['docid' => $id]);
            }

            session(['doc' => $docType, 'doc_id' => $id, 'num' => $num]);

            // For ZIN/ZOUT just redirect to list; others go to edit form
            if (in_array($docType, ['ZIN', 'ZOUT'], true)) {
                return redirect()->route('document.index', ['doc' => $docType]);
            }

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
        $this->docService->provodka($request);
        return redirect()->back()->with('success', 'Проведено');
    }

    // ── Delete ────────────────────────────────────────────────────────────────

    public function destroy(Request $request)
    {
        $docId = $request->input('doc_id', session('doc_id'));
        $doc   = $request->input('doc', session('doc', ''));
        $table = Document::tableForType($doc);
        $fid   = session('fid', '');

        // Guard: can't delete if z_body rows exist
        if (ZBody::where('docid', $docId)->exists()) {
            return back()->withErrors(['delete' => 'Спочатку видаліть рядки документа']);
        }

        DB::table($table)->where('id', $docId)->where('firma', $fid)->delete();
        session(['num' => '0', 'doc_id' => '0']);

        return redirect()->route('document.index', ['doc' => $doc]);
    }

    // ── Bulk status ───────────────────────────────────────────────────────────

    public function bulkStatus(Request $request)
    {
        $ids    = $request->input('ids', []);
        $status = $request->input('status1', '');
        $doc    = $request->input('doc', session('doc', ''));
        $table  = Document::tableForType($doc);

        if ($status !== '' && !empty($ids)) {
            DB::table($table)->whereIn('id', $ids)->update(['status' => $status]);
        }

        return redirect()->back();
    }

    // ── z_body: add item ─────────────────────────────────────────────────────

    public function bodyAdd(Request $request)
    {
        $doc    = session('doc', '');
        $fid    = session('fid', '');
        $docid  = session('docid', '0');
        $typez  = session('typez', '');
        $numz   = session('numz', '0');

        $pnum   = $request->input('pnum', '');
        $pid    = $request->input('pid', '');
        $pprice = $request->input('pprice', '0');
        $psumma = $request->input('psumma', '0');
        $pcount = $request->input('pcount', '1');

        $docTypes = ['CH','PN','RN','VN','WO1','AO','ZOUT','ZIN'];

        if (in_array($doc, $docTypes, true)) {
            ZBody::addOrIncrement($typez, $numz, $pnum, $fid, $docid, $pid, $pprice, $psumma);
        } elseif ($doc === 'SP') {
            $exists = ZBody::where('type', $typez)->where('docnum', $numz)
                            ->where('pnum', $pnum)->where('firma', $fid)->exists();
            if (!$exists) {
                ZBody::create([
                    'docnum' => $numz, 'pid' => $pid, 'pnum' => $pnum,
                    'pcount' => $pcount, 'pprice' => $pprice, 'psumma' => $psumma,
                    'type'   => $typez, 'firma' => $fid,
                ]);
            }
        }

        return redirect()->back();
    }

    // ── z_body: delete item ───────────────────────────────────────────────────

    public function bodyDelete(Request $request)
    {
        $bid = $request->input('bid', '');
        if ($bid !== '') ZBody::where('id', $bid)->delete();
        return redirect()->back();
    }

    // ── z_body: update quantity/price ────────────────────────────────────────

    public function bodyUpdate(Request $request)
    {
        $bid    = $request->input('bid', '');
        $field  = $request->input('field', '');
        $value  = $request->input('value', '');
        $allowed = ['pcount', 'pprice', 'psumma'];

        if ($bid !== '' && in_array($field, $allowed, true)) {
            ZBody::where('id', $bid)->update([$field => $value]);
        }

        return response()->json(['ok' => true]);
    }

    // ── Set client on doc ─────────────────────────────────────────────────────

    public function setClient(Request $request)
    {
        $docId   = session('doc_id', '0');
        $client1 = session('client1', '0');
        $client2 = session('client2', '0');
        $doc     = session('doc', '');
        $table   = Document::tableForType($doc);

        DB::table($table)->where('id', $docId)
            ->update(['client1' => $client1, 'client2' => $client2]);

        return redirect()->back();
    }
}
