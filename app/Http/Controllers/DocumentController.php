<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Goods;
use App\Models\Field;
use App\Models\ZBody;
use App\Models\Conf;
use App\Models\Docs;
use App\Models\Conf as ConfModel;
use App\Services\FilterService;
use App\Services\DocumentService;
use App\Services\SmsClubService;
use App\Support\HoldingScope;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

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
        private DocumentService $docService,
        private SmsClubService $smsClub
        )
    {
    }

    private function resolveGoodsUnitPrice(
        string $docType,
        float $quantity,
        float $pricePay,
        float $pricePay1,
        int $priceCount,
        float $compPay1
    ): float {
        if (in_array($docType, ['ZIN', 'PN'], true)) {
            return $compPay1 > 0 ? $compPay1 : 0.0;
        }

        if (in_array($docType, ['ZOUT', 'RN'], true)) {
            if ($priceCount > 0 && $quantity >= $priceCount && $pricePay1 > 0) {
                return $pricePay1;
            }

            if ($pricePay > 0) {
                return $pricePay;
            }
        }

        return $pricePay > 0 ? $pricePay : $compPay1;
    }

    private function goodsPricingMetaByPnum(iterable $pnums, string $fid): array
    {
        $normalized = collect($pnums)
            ->map(fn ($pnum) => (string) $pnum)
            ->filter(fn ($pnum) => $pnum !== '' && $pnum !== '0')
            ->unique()
            ->values();

        if ($normalized->isEmpty()) {
            return [];
        }

        $goods = DB::table('comp')
            ->whereIn('id', $normalized)
            ->where('firma', $fid)
            ->select('id', 'firma', 'pay', 'pay1')
            ->get();

        $user = Auth::user();
        $tgroupId = $user ? ($user->idstatus ?: $user->ustype) : null;

        $goods = Goods::attachPreferredPricesByItemFirma($goods, $tgroupId);

        $meta = [];
        foreach ($goods as $good) {
            $meta[(string) $good->id] = [
                'price_pay' => (float) ($good->price_pay ?? 0),
                'price_pay1' => (float) ($good->price_pay1 ?? 0),
                'price_count' => (int) ($good->price_count ?? 0),
                'comp_pay1' => (float) ($good->pay1 ?? 0),
                'comp_pay' => (float) ($good->pay ?? 0),
            ];
        }

        return $meta;
    }

    private function cloneBodyRowsToChild(string $sourceDocId, string $targetDocId, string $fid, string $docType, string $docNum): void
    {
        if ($sourceDocId === '0' || $targetDocId === '0') {
            return;
        }

        if (ZBody::where('docid', $targetDocId)->where('firma', $fid)->exists()) {
            return;
        }

        $rows = ZBody::where('docid', $sourceDocId)
            ->where('firma', $fid)
            ->orderBy('id')
            ->get();

        $pricingMeta = $this->goodsPricingMetaByPnum($rows->pluck('pnum'), $fid);

        foreach ($rows as $row) {
            $meta = $pricingMeta[(string) $row->pnum] ?? null;
            $unitPrice = $meta
                ? $this->resolveGoodsUnitPrice(
                    $docType,
                    (float) ($row->pcount ?? 0),
                    (float) ($meta['price_pay'] ?? 0),
                    (float) ($meta['price_pay1'] ?? 0),
                    (int) ($meta['price_count'] ?? 0),
                    (float) ($meta['comp_pay1'] ?? 0)
                )
                : (float) ($row->pprice ?? 0);

            ZBody::create([
                'docnum' => $docNum,
                'pid' => $row->pid,
                'pnum' => $row->pnum,
                'pcount' => $row->pcount,
                'pprice' => $unitPrice,
                'psumma' => (float) ($row->pcount ?? 0) * $unitPrice,
                'type' => $docType,
                'firma' => $fid,
                'docid' => $targetDocId,
                'zvalue' => $row->zvalue ?? '',
            ]);
        }
    }

    private function isRootDocumentLocked(string $docType, string $docId, string $fid): bool
    {
        if (!in_array($docType, ['ZOUT', 'ZIN'], true) || $docId === '' || $docId === '0') {
            return false;
        }

        return DB::table('document')
            ->where('id', $docId)
            ->where('type', $docType)
            ->where('firma', $fid)
            ->where('provodka', 1)
            ->exists();
    }

    // ── List view ─────────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $documentRoutePrefix = $this->documentRoutePrefix();
        $fid = session('fid', '');
        $login = session('login', '');
        $status = (int) session('idstatus', session('status', 0));
        $idsklad = session('idsklad', '');
        $idkassa = session('idkassa', '');

        $doc = $request->input('doc', session('doc', 'ZOUT'));
        $pos = (int)$request->input('pos', 0);

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

        $listData = Document::showDocumentList($rows, $confMap, $doc, $documentRoutePrefix);
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

        return view($view, compact(
            'items', 'total_sum', 'rows', 'doc', 'pos', 'total', 'fd', 'fid', 'documentRoutePrefix'
        ));
    }

    // ── Show single document ──────────────────────────────────────────────────

    public function show(Request $request)
    {
        $docId = $request->input('doc_id', session('doc_id', '0'));
        $num = $request->input('num', session('num', '0'));
        $year = $request->input('year', session('year', date('Y')));
        $doc = $request->input('doc', session('doc', 'ZOUT'));
        $fid = session('fid', '');
        $locale = $this->resolveBackendLocale($request);
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

            if (in_array($doc, ['ZIN', 'ZOUT'], true)) {
                $existingDraft = DB::table($table)
                    ->where('firma', $fid)
                    ->where('type', $doc)
                    ->whereIn('client1', [0, '0'])
                    ->where('manager', session('login', ''))
                    ->where('summa', 0)
                    ->where('provodka', 0)
                    ->orderBy('id', 'desc')
                    ->first();

                if ($existingDraft) {
                    session([
                        'doc_id' => $existingDraft->id, 
                        'num' => $existingDraft->num, 
                        'docid' => $existingDraft->id
                    ]);
                    
                    return redirect()->route($this->documentRoutePrefix() . '.show', [
                        'doc' => $doc,
                        'doc_id' => $existingDraft->id,
                        'parent_doc_id' => $existingDraft->id,
                        'num' => $existingDraft->num,
                        'year' => $year,
                    ]);
                }
            }

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
                'docum' => '',
                'dostup' => 1,
                'work' => session('work', '1'),
            ]);

            if (in_array($doc, ['ZIN', 'ZOUT'], true)) {
                DB::table($table)->where('id', $newId)->update(['docid' => $newId, 'numz' => $newNum]);
                session(['docid' => $newId]);
            }

            session(['doc_id' => $newId, 'num' => $newNum]);

            if (in_array($doc, ['RN', 'PN'], true) && $parentDocid !== '0') {
                $this->cloneBodyRowsToChild((string) $parentDocid, (string) $newId, $fid, $doc, (string) $newNum);
            }

            // Redirect with new params so reload works correctly
            return redirect()->route($this->documentRoutePrefix() . '.show', [
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
            return redirect()->route($this->documentRoutePrefix() . '.index')->with('error',
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

        if (in_array($doc, ['RN', 'PN'], true) && $parentDocid && !ZBody::where('docid', $docId)->where('firma', $fid)->exists()) {
            $this->cloneBodyRowsToChild((string) $parentDocid, (string) $docId, $fid, $doc, (string) ($document->num ?? '0'));
        }

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

        $docIdToFind = in_array($doc, ['ZIN', 'ZOUT', 'RN', 'PN'], true) ? $docId : $parentDocid;
        $lineItems = ZBody::from('z_body as zb')
            ->leftJoin('comp as c', function ($join) {
                $join->on('zb.pnum', '=', 'c.id')
                    ->on('zb.firma', '=', 'c.firma');
            })
            ->leftJoin('descript as d', function ($join) {
                $join->on('d.pnum', '=', 'zb.pnum')
                    ->on('d.firma', '=', 'zb.firma');
            })
            ->where('zb.docid', $docIdToFind)
            ->select(
                'zb.*',
                'c.name as comp_name',
                'c.pay as comp_pay',
                'c.pay1 as comp_pay1',
                DB::raw('COALESCE(d.name, "") as descript_name'),
                DB::raw('COALESCE(d.name_ua, "") as descript_name_ua'),
                DB::raw('COALESCE(d.name_en, "") as descript_name_en')
            )
            ->orderBy('zb.id')
            ->get();

        $pricingMeta = $this->goodsPricingMetaByPnum($lineItems->pluck('pnum'), $fid);

        $lineItems = $lineItems->map(function ($item) use ($pricingMeta, $locale) {
            $meta = $pricingMeta[(string) $item->pnum] ?? [];
            $item->name = Field::localizedValue(
                $locale,
                $item->descript_name ?? '',
                $item->descript_name_ua ?? '',
                $item->descript_name_en ?? ''
            ) ?: (string) ($item->comp_name ?? '');
            $item->price_pay = (float) ($meta['price_pay'] ?? 0);
            $item->price_pay1 = (float) ($meta['price_pay1'] ?? 0);
            $item->price_count = (int) ($meta['price_count'] ?? 0);
            $item->comp_pay = (float) ($meta['comp_pay'] ?? ($item->comp_pay ?? 0));
            $item->comp_pay1 = (float) ($meta['comp_pay1'] ?? ($item->comp_pay1 ?? 0));

            return $item;
        });

        // Client info (related docs / balance)
        $client = $document->client1 ? DB::table('users')->where('id', $document->client1)->first() : null;
        $mappingTargetProjectId = $this->counterpartyProjectIdForDocument($document, $fid);
        $productMappings = $this->productMappingsForDocument($document, $fid, $mappingTargetProjectId);
        $lineItems = $lineItems->map(function ($item) use ($productMappings) {
            $item->mapped_product_id = $productMappings[(string) $item->pnum] ?? '';

            return $item;
        });

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

        $documentRoutePrefix = $this->documentRoutePrefix();
        $isLoanRoDocument = $documentRoutePrefix === 'bank.loanDocs' && $doc === 'RO';

        // Load all oplata and reestr options for PO/RO dropdowns
        $oplataList = collect();
        $reestrList = collect();
        if (in_array($doc, ['PO', 'RO', 'ZP'], true)) {
            $oplataList = DB::table('conf')
                ->where('type', 'oplata')
                ->where('firma', $fid)
                ->when(in_array($doc, ['PO', 'RO'], true), function ($query) {
                    $query->where('vision', '1');
                })
                ->when($isLoanRoDocument && Schema::hasColumn('conf', 'doc'), function ($query) {
                    $query->where(function ($nested) {
                        $nested->where('doc', 'bank')
                            ->orWhereNull('doc')
                            ->orWhere('doc', '');
                    });
                })
                ->orderBy('name')
                ->get();
            $reestrList = ConfModel::paymentTypesForDocument($fid, $doc);
        }
        $statusList = DB::table('conf')
            ->where('type', 'status')
            ->where('firma', $fid)
            ->orderBy('name')
            ->get(['id', 'name', 'color']);
        $savedSkladId = trim((string) ($document->sklads ?? ''));
        $skladsList = DB::table('conf')
            ->where('type', 'sklads')
            ->where(function ($query) use ($fid, $savedSkladId) {
                $query->where('firma', $fid);

                if ($savedSkladId !== '') {
                    $query->orWhere('id', $savedSkladId);
                }
            })
            ->orderBy('name')
            ->get();
        $clientStatuses = DB::table('conf')->where('type', 'tclient')->where('firma', $fid)->orderBy('name')->get();
        $clientGroups = DB::table('conf')->where('type', 'usergroup')->where('firma', $fid)->orderBy('name')->get();
        $myCompanies = collect();

        if ($doc === 'CH') {
            $authUser = Auth::user();
            if (!$authUser) {
                $login = session('login', '');
                if ($login !== '') {
                    $authUser = DB::table('users')->where('login', $login)->first();
                }
            }

            if ($authUser) {
                $myCompanies = DB::table('firma')
                    ->where(function ($query) use ($authUser) {
                        $query->where('userid', $authUser->id);

                        if (!empty($authUser->firma ?? null)) {
                            $query->orWhere('firma', $authUser->firma);
                        }
                    })
                    ->orderBy('id')
                    ->get();
            }
        }

        $isLoanRequestDocument = $documentRoutePrefix === 'bank.loanDocs' && $doc === 'ZOUT';
        $isLoanIssueDocument = $documentRoutePrefix === 'bank.loanDocs' && $doc === 'RN';
        $loanRoUrl = null;
        $loanMeta = [];
        $loanCollateralOptions = collect(['Автомобиль', 'Спецтехника', 'Госномер']);
        $loanRepaymentSchedule = null;
        if ($isLoanRequestDocument) {
            $loanRoUrl = route('bank.loanDocs.show', [
                'doc' => 'RO',
                'doc_id' => 0,
                'parent_doc_id' => (int) $document->id,
                'num' => 0,
                'year' => strlen((string) ($document->data ?? '')) >= 10
                    ? substr((string) $document->data, 6, 4)
                    : $year,
                'sumRO' => (float) ($document->summa ?? 0),
            ]);
            $loanMeta = $this->parseLoanRequestContent((string) ($document->content ?? ''));
            $loanMeta['loan_amount'] = $loanMeta['loan_amount'] !== '' ? $loanMeta['loan_amount'] : (string) ($document->summa ?? '');
            $loanMeta['ltv'] = $loanMeta['ltv'] !== '' ? $loanMeta['ltv'] : (string) ($document->reteil ?? '70');
            $loanCollateralOptions = $loanCollateralOptions
                ->merge(
                    DB::table('document')
                        ->where('type', 'ZOUT')
                        ->where('firma', $fid)
                        ->where(function ($query) {
                            $query->where('typeproduct', 'credit_request')
                                ->orWhere('numorder', 'AV8-LOAN')
                                ->orWhere('content', 'like', '%[AV8_LOAN_REQUEST]%');
                        })
                        ->pluck('content')
                        ->map(fn ($content) => $this->parseLoanRequestContent((string) $content)['collateral_type'] ?? '')
                        ->filter()
                )
                ->unique()
                ->values();
        }
        if ($isLoanIssueDocument && $parentDocument && ($parentDocument->type ?? '') === 'ZOUT') {
            $loanMeta = $this->parseLoanRequestContent((string) ($parentDocument->content ?? ''));
            $loanRepaymentSchedule = $this->loanRepaymentSchedule($parentDocument, $loanMeta);
        }

        // Related documents (legacy client_info / client_info1)
        $clientId = $document->client1 ?? 0;
        $numz = $parentNumz;
        $typez = $parentTypez;
        $docid = $parentDocid;
        $relatedDocTotal = (float) (
            in_array($doc, ['ZIN', 'ZOUT'], true)
                ? ($document->summa ?? 0)
                : ($parentDocument->summa ?? $document->summa ?? 0)
        );

        if ($relatedDocTotal <= 0 && $docid) {
            $relatedDocTotal = (float) DB::table('z_body')
                ->where('docid', $docid)
                ->where('firma', $fid)
                ->sum('psumma');
        }

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
                $relatedDocTotal, $orderPosted, $documentRoutePrefix
            );
        }

        // client_info — compact icon strip: only for ZIN / ZOUT documents
        $relatedIcons = null;
        if (in_array($doc, ['ZIN', 'ZOUT'], true) && $clientId > 0) {
            $relatedIcons = Docs::clientInfo(
                $clientId, $numz, $typez, $year, $docid, $document->summa ?? 0
            );
        }

        $documentIndexUrl = route($documentRoutePrefix . '.index', ['doc' => $doc]);
        $parentDocumentUrl = $parentDocument
            ? route($documentRoutePrefix . '.show', [
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
            'fid', 'relatedDocs', 'relatedIcons', 'oplataList', 'reestrList', 'statusList', 'skladsList',
            'documentIndexUrl', 'parentDocumentUrl', 'parentDocument', 'myCompanies', 'clientStatuses', 'clientGroups',
            'mappingTargetProjectId', 'documentRoutePrefix', 'loanMeta', 'loanCollateralOptions', 'loanRepaymentSchedule',
            'loanRoUrl'
        ));
    }

    public function print(Request $request)
    {
        $doc = (string) $request->input('doc', session('doc', ''));
        $docId = (string) $request->input('doc_id', session('doc_id', '0'));
        $fid = (string) session('fid', '');
        $locale = $this->resolveBackendLocale($request);

        if (!in_array($doc, ['CH', 'RN'], true)) {
            return redirect()->route($this->documentRoutePrefix() . '.show', [
                'doc' => $doc,
                'doc_id' => $docId,
                'num' => $request->input('num', session('num', '0')),
                'year' => $request->input('year', session('year', date('Y'))),
            ])->with('error', 'Печать доступна тільки для документів CH та RN');
        }

        $table = Document::tableForType($doc);
        $document = DB::table($table)
            ->where('id', $docId)
            ->where('firma', $fid)
            ->first();

        if (!$document) {
            return redirect()->route($this->documentRoutePrefix() . '.index', ['doc' => $doc])
                ->with('error', 'Документ для друку не знайдено');
        }

        $docIdToFind = in_array($doc, ['ZIN', 'ZOUT', 'RN', 'PN'], true)
            ? $docId
            : ($document->docid ?: $docId);

        $lineItems = ZBody::from('z_body as zb')
            ->leftJoin('comp as c', function ($join) {
                $join->on('zb.pnum', '=', 'c.id')
                    ->on('zb.firma', '=', 'c.firma');
            })
            ->leftJoin('descript as d', function ($join) {
                $join->on('d.pnum', '=', 'zb.pnum')
                    ->on('d.firma', '=', 'zb.firma');
            })
            ->where('zb.docid', $docIdToFind)
            ->select(
                'zb.*',
                'c.name as comp_name',
                DB::raw('COALESCE(d.name, "") as descript_name'),
                DB::raw('COALESCE(d.name_ua, "") as descript_name_ua'),
                DB::raw('COALESCE(d.name_en, "") as descript_name_en')
            )
            ->orderBy('zb.id')
            ->get();

        $lineItems = $lineItems->map(function ($item) use ($locale) {
            $item->name = Field::localizedValue(
                $locale,
                $item->descript_name ?? '',
                $item->descript_name_ua ?? '',
                $item->descript_name_en ?? ''
            ) ?: (string) ($item->comp_name ?? '');

            return $item;
        });

        $client = $document->client1
            ? DB::table('users')->where('id', $document->client1)->first()
            : null;

        $firma = null;
        $selectedFirmaId = (int) ($document->schet ?? 0);

        if ($selectedFirmaId > 0) {
            $firma = DB::table('firma')->where('id', $selectedFirmaId)->first();
        }

        if (!$firma) {
            $firma = DB::table('firma')
                ->where(function ($query) use ($fid) {
                    $query->where('id', $fid)
                        ->orWhere('firma', $fid);
                })
                ->orderByDesc('id')
                ->first();
        }

        $docTitle = $doc === 'RN'
            ? __('document.print.titles.rn')
            : __('document.print.titles.ch');
        $itemsTitle = $doc === 'RN'
            ? __('document.print.items.shipment')
            : __('document.print.items.invoice');
        $skladName = '';

        if (!empty($document->sklads)) {
            $skladName = (string) DB::table('conf')
                ->where('id', $document->sklads)
                ->where('type', 'sklads')
                ->value('name');
        }

        return view('document.print_ch', compact('document', 'lineItems', 'client', 'firma', 'doc', 'docTitle', 'itemsTitle', 'skladName'));
    }

    // ── Save ──────────────────────────────────────────────────────────────────

    public function save(Request $request)
    {
        \Illuminate\Support\Facades\Log::info('save() method called', [
            'method' => $request->method(),
            'path' => $request->path(),
            'allInputs' => $request->all(),
            'session_doc' => session('doc', ''),
            'session_doc_id' => session('doc_id', '0'),
        ]);
        
        $doc = (string) $request->input('doc', session('doc', ''));
        $fid = session('fid', '');
        $year = session('year', date('Y'));
        $run = $request->input('run', '');
        
        \Illuminate\Support\Facades\Log::info('save() variables', [
            'doc' => $doc,
            'run' => $run,
            'fid' => $fid,
        ]);
        
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
            'Видати ЗП' => 'ZP', 'Выдать ЗП' => 'ZP',
            'Додати фото' => 'RA', 'Добавить фото' => 'RA',
        ];

        if ($createDocType !== '' || isset($docTypeMap[$run])) {
            $docType = $createDocType !== '' ? $createDocType : $docTypeMap[$run];

            if (in_array($docType, ['ZIN', 'ZOUT'], true)) {
                $table = Document::tableForType($docType);
                $existingDraft = DB::table($table)
                    ->where('firma', $fid)
                    ->where('type', $docType)
                    ->whereIn('client1', [0, '0'])
                    ->where('manager', session('login', ''))
                    ->where('summa', 0)
                    ->where('provodka', 0)
                    ->orderBy('id', 'desc')
                    ->first();

                if ($existingDraft) {
                    session([
                        'doc' => $docType, 
                        'doc_id' => $existingDraft->id, 
                        'num' => $existingDraft->num, 
                        'docid' => $existingDraft->id
                    ]);
                    
                    return redirect()->route($this->documentRoutePrefix() . '.show', [
                        'doc' => $docType, 
                        'doc_id' => $existingDraft->id, 
                        'num' => $existingDraft->num, 
                        'year' => $year,
                    ]);
                }
            }

            $summaPO = in_array($docType, ['PO', 'RO', 'ZP'], true) ? (float)$request->input('sumPO', 0) : 0.0;
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
                'docum' => '',
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
             return redirect()->route($this->documentRoutePrefix() . '.index', ['doc' => $docType]);
             }
             */
            return redirect()->route($this->documentRoutePrefix() . '.show', [
                'doc' => $docType, 'doc_id' => $id, 'num' => $num, 'year' => $year,
            ]);
        }

        \Illuminate\Support\Facades\Log::info('About to check save block', [
            'run' => $run,
            'willCheckSave' => in_array($run, ['Зберегти', 'Save', 'Сохранить'], true),
        ]);

        // ── Save / Зберегти ───────────────────────────────────────────────────
        if (in_array($run, ['Зберегти', 'Save', 'Сохранить'], true)) {
            $docId = (string) $request->input('doc_id', session('doc_id', '0'));

            $errors = [];
            
            // Client validation (not required for RA documents)
            if ($doc !== 'RA' && trim((string) $request->input('client1', '')) === '') {
                $errors['client1'] = 'Оберіть клієнта';
            }

            if ($doc === 'ZP' && ! isset($errors['client1'])) {
                $employeeId = trim((string) $request->input('client1', ''));
                $employeeFirmaScope = HoldingScope::projectIdsFor($fid);
                $employeeExists = DB::table('users')
                    ->where('id', $employeeId)
                    ->whereIn('firma', $employeeFirmaScope)
                    ->where('firmuser', '1')
                    ->exists();

                if (! $employeeExists) {
                    $errors['client1'] = 'Оберіть співробітника поточного холдингу';
                }
            }

            if (in_array($doc, ['ZOUT', 'ZIN'], true) && trim((string) $request->input('status', '')) === '') {
                $errors['status'] = 'Оберіть статус';
            }

            if (in_array($doc, ['PO', 'RO', 'ZP'], true)) {
                if (trim((string) $request->input('oplata', '')) === '') {
                    $errors['oplata'] = 'Оберіть касу';
                }
                if (trim((string) $request->input('reestr', '')) === '') {
                    $errors['reestr'] = 'Оберіть вид платежу';
                }
            }

            if (in_array($doc, ['PN', 'RN', 'WO1'], true) && trim((string) $request->input('sklads', '')) === '') {
                $errors['sklads'] = 'Оберіть склад';
            }

            \Illuminate\Support\Facades\Log::info('Validation errors check', [
                'doc' => $doc,
                'errors' => $errors,
                'skladsFromRequest' => $request->input('sklads', ''),
            ]);

            if ($errors !== []) {
                return redirect()->back()->withErrors($errors)->withInput();
            }

            session(['doc' => $doc, 'doc_id' => $docId]);

            if ($this->documentRoutePrefix() === 'bank.loanDocs' && $doc === 'ZOUT') {
                return $this->saveLoanRequestDocument($request, $docId, $fid);
            }
            
            \Illuminate\Support\Facades\Log::info('Document save started', [
                'doc' => $doc,
                'docId' => $docId,
                'fid' => $fid,
                'run' => $run,
            ]);
            
            try {
                $conductableDocs = ['RN', 'PN', 'PO', 'RO', 'ZP', 'VN', 'AO', 'WO1'];
                $currentPosted = false;
                $desiredPosted = $request->boolean('post_after_save');
                $wasSmsFlagEnabled = false;

                if ($doc === 'ZOUT') {
                    $wasSmsFlagEnabled = (string) DB::table(Document::tableForType($doc))
                        ->where('id', $docId)
                        ->where('firma', $fid)
                        ->value('sms_flag') === '1';
                }

                if (in_array($doc, $conductableDocs, true)) {
                    $table = Document::tableForType($doc);
                    $currentPosted = (int) DB::table($table)
                        ->where('id', $docId)
                        ->where('firma', $fid)
                        ->value('provodka') === 1;

                    if ($currentPosted) {
                        if ($desiredPosted) {
                            return redirect()->back()->with(
                                'error',
                                'Проведений документ змінювати не можна. Спочатку зніміть проводку.'
                            );
                        }

                        Document::provodka($docId, $doc, $fid);

                        return redirect()->back()->with('success', 'Проводку скасовано');
                    }
                }

                $this->docService->saveHead($request, $docId, $doc, $fid);
                $this->docService->saveBody($request, $docId, $doc, $fid);

                \Illuminate\Support\Facades\Log::info('Document head and body saved', [
                    'doc' => $doc,
                    'docId' => $docId,
                ]);

                $message = 'Збережено';

                $smsWarning = null;
                if ($doc === 'ZOUT' && $request->boolean('sms_flag') && ! $wasSmsFlagEnabled) {
                    $savedDocument = DB::table(Document::tableForType($doc))
                        ->where('id', $docId)
                        ->where('firma', $fid)
                        ->first();

                    $smsWarning = $savedDocument
                        ? $this->sendOrderTtnSms($savedDocument, $fid)
                        : 'SMS не відправлено: замовлення не знайдено після збереження.';
                }

                if (in_array($doc, $conductableDocs, true)) {
                    \Illuminate\Support\Facades\Log::info('Checking provodka state', [
                        'doc' => $doc,
                        'docId' => $docId,
                        'currentPosted' => $currentPosted,
                        'desiredPosted' => $desiredPosted,
                    ]);

                    if ($desiredPosted !== $currentPosted) {
                        $result = Document::provodka($docId, $doc, $fid);
                        $message = ($result['isPosted'] ?? false)
                            ? 'Збережено та проведено'
                            : 'Збережено, проводку скасовано';
                        
                        \Illuminate\Support\Facades\Log::info('Provodka executed', [
                            'doc' => $doc,
                            'docId' => $docId,
                            'result' => $result,
                        ]);
                    }
                }

                \Illuminate\Support\Facades\Log::info('Document save completed', [
                    'doc' => $doc,
                    'docId' => $docId,
                    'message' => $message,
                ]);
                $redirect = redirect()->back()->with('success', $message);

                return $smsWarning === null
                    ? $redirect
                    : $redirect->with('warning', $smsWarning);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('Document save failed', [
                    'doc' => $doc,
                    'docId' => $docId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                return redirect()->back()->with('error', $e->getMessage());
            }
        }

        return redirect()->back();
    }

    // ── Provodka ──────────────────────────────────────────────────────────────

    public function provodka(Request $request)
    {
        $docId = $request->input('doc_id', session('doc_id', '0'));
        $doc = $request->input('doc', session('doc', ''));
        $fid = (string) session('fid', '');
        try {
            $result = Document::provodka($docId, $doc, $fid);
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
        $document = $result['document'] ?? null;
        $isPosted = (bool) ($result['isPosted'] ?? false);
        if (!$document) {
            return redirect()->route($this->documentRoutePrefix() . '.index', ['doc' => $doc])
                ->with('success', $isPosted ? 'Проводку виконано' : 'Проводку скасовано');
        }

        $year = strlen((string) ($document->data ?? '')) >= 10
            ? substr((string) $document->data, 6, 4)
            : date('Y');

        return redirect()->route($this->documentRoutePrefix() . '.show', [
            'doc' => $doc,
            'doc_id' => $docId,
            'num' => $document->num,
            'year' => $year,
        ])->with('success', $isPosted ? 'Проводку виконано' : 'Проводку скасовано');
    }

    private function sendOrderTtnSms(object $document, string $fid): ?string
    {
        $clientId = (int) ($document->client1 ?? 0);
        if ($clientId <= 0) {
            return 'SMS не відправлено: у замовленні не вибраний клієнт.';
        }

        $ttn = trim((string) ($document->ttn ?? ''));
        if ($ttn === '') {
            return 'SMS не відправлено: у замовленні не вказаний номер ТТН.';
        }

        $client = DB::table('users')
            ->where('id', $clientId)
            ->where('firma', $fid)
            ->first(['id', 'phone', 'name', 'secondname', 'fathername', 'orgname']);

        if (!$client) {
            return 'SMS не відправлено: клієнта не знайдено.';
        }

        $phone = $this->normalizeSmsPhone((string) ($client->phone ?? ''));
        if ($phone === null) {
            return 'SMS не відправлено: у клієнта некоректний телефон.';
        }

        $message = $this->makeOrderTtnSmsMessage($document, $client);

        try {
            $this->smsClub->sendOtp($phone, $message);
        } catch (\Throwable $e) {
            Log::warning('Failed to send order TTN SMS.', [
                'document_id' => $document->id ?? null,
                'document_type' => $document->type ?? 'ZOUT',
                'client_id' => $clientId,
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);

            return 'Замовлення збережено, але SMS не відправлено: '.$e->getMessage();
        }

        Log::info('Order TTN SMS sent.', [
            'document_id' => $document->id ?? null,
            'client_id' => $clientId,
            'phone' => $phone,
        ]);

        return null;
    }

    private function normalizeSmsPhone(string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '0')) {
            $digits = '38'.$digits;
        }

        if (!str_starts_with($digits, '38') && strlen($digits) === 10) {
            $digits = '38'.$digits;
        }

        if (!preg_match('/^380\d{9}$/', $digits)) {
            return null;
        }

        return $digits;
    }

    private function makeOrderTtnSmsMessage(object $document, object $client): string
    {
        $clientName = trim((string) ($client->orgname ?? ''));
        if ($clientName === '') {
            $clientName = trim(implode(' ', array_filter([
                (string) ($client->secondname ?? ''),
                (string) ($client->name ?? ''),
                (string) ($client->fathername ?? ''),
            ])));
        }

        $ttn = trim((string) ($document->ttn ?? ''));
        $content = trim((string) ($document->content ?? ''));
        $prefix = $clientName !== '' ? $clientName.', ' : '';
        $message = $content !== '' ? $content : 'Ваш заказ отправлен.';

        return "{$prefix}{$message} ТТН: {$ttn}";
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
            if ($this->isRootDocumentLocked($doc, (string) $docId, $fid)) {
                return redirect()->back()->with('error', 'Проведений документ видаляти не можна. Спочатку зніміть проводку з пов’язаних документів.');
            }
            $docIdToFind = in_array($doc, ['ZIN', 'ZOUT', 'RN', 'PN'], true) ? $docId : ($document->docid ?? $docId);
            ZBody::where('docid', $docIdToFind)->delete();
        }

        DB::table($table)->where('id', $docId)->where('firma', $fid)->delete();
        session(['num' => '0', 'doc_id' => '0']);

        return redirect()->route($this->documentRoutePrefix() . '.index', ['doc' => $doc]);
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
        $docid = in_array($doc, ['RN', 'PN'], true) ? session('doc_id', '0') : session('docid', '0');
        $typez = in_array($doc, ['RN', 'PN'], true) ? $doc : session('typez', '');
        $numz = session('numz', '0');

        if ($this->isRootDocumentLocked($doc, (string) $docid, $fid)) {
            return redirect()->back()->with('error', 'Проведений документ змінювати не можна. Спочатку зніміть проводку з пов’язаних документів.');
        }

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
        $doc = session('doc', '');
        $fid = session('fid', '');
        $docId = (string) session('doc_id', session('docid', '0'));

        if ($this->isRootDocumentLocked($doc, $docId, $fid)) {
            return redirect()->back()->with('error', 'Проведений документ змінювати не можна. Спочатку зніміть проводку з пов’язаних документів.');
        }

        $bid = $request->input('bid', '');
        if ($bid !== '')
            ZBody::where('id', $bid)->delete();
        return redirect()->back();
    }

    // ── z_body: update quantity/price ────────────────────────────────────────

    public function bodyUpdate(Request $request)
    {
        $doc = session('doc', '');
        $fid = session('fid', '');
        $docId = (string) session('doc_id', session('docid', '0'));

        if ($this->isRootDocumentLocked($doc, $docId, $fid)) {
            return response()->json([
                'ok' => false,
                'message' => 'Проведений документ змінювати не можна. Спочатку зніміть проводку з пов’язаних документів.',
            ], 422);
        }

        $bid = $request->input('bid', '');
        $field = $request->input('field', '');
        $value = $request->input('value', '');
        $allowed = ['pcount', 'pprice', 'psumma'];

        if ($bid !== '' && in_array($field, $allowed, true)) {
            ZBody::where('id', $bid)->update([$field => $value]);
        }

        return response()->json(['ok' => true]);
    }

    public function productMappingSearch(Request $request)
    {
        $fid = (string) session('fid', '');
        $doc = (string) $request->query('doc', session('doc', ''));
        $docId = (string) $request->query('doc_id', session('doc_id', '0'));
        $q = trim((string) $request->query('q', ''));

        if ($fid === '' || $doc === '' || $docId === '') {
            return response()->json(['message' => 'Документ не визначено.'], 422);
        }

        $table = Document::tableForType($doc);
        $document = DB::table($table)
            ->where('id', $docId)
            ->where('firma', $fid)
            ->first();

        if (! $document) {
            return response()->json(['message' => 'Документ не знайдено.'], 404);
        }

        $counterpartyUserId = (int) $request->query('counterparty_user_id', 0);
        $targetProjectId = $counterpartyUserId > 0
            ? $this->counterpartyProjectId($counterpartyUserId, $fid)
            : $this->counterpartyProjectIdForDocument($document, $fid);
        if ($targetProjectId === null) {
            return response()->json(['message' => 'У клієнта документа не задано project_id.'], 422);
        }

        $items = DB::table('comp')
            ->leftJoin('descript as d', function ($join) {
                $join->on('d.pnum', '=', 'comp.id')
                    ->whereColumn('d.firma', '=', 'comp.firma');
            })
            ->leftJoin('price_sklad as ps', function ($join) use ($targetProjectId) {
                $join->on('ps.pnum', '=', 'comp.id')
                    ->where('ps.firma', '=', $targetProjectId);
            })
            ->where('comp.firma', (string) $targetProjectId)
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($search) use ($q) {
                    $search->where('comp.id', $q)
                        ->orWhere('comp.nickname', 'like', "%{$q}%")
                        ->orWhere('comp.cod', 'like', "%{$q}%")
                        ->orWhere('comp.name', 'like', "%{$q}%")
                        ->orWhere('comp.namedoc', 'like', "%{$q}%")
                        ->orWhere('d.name', 'like', "%{$q}%")
                        ->orWhere('d.name_ua', 'like', "%{$q}%")
                        ->orWhere('d.name_en', 'like', "%{$q}%");
                });
            })
            ->groupBy(
                'comp.id',
                'comp.nickname',
                'comp.cod',
                'comp.firma',
                'comp.pay',
                'comp.pay1',
                'comp.name',
                'comp.namedoc',
                'd.name',
                'd.name_ua',
                'd.name_en'
            )
            ->orderByDesc('comp.id')
            ->limit(30)
            ->get([
                'comp.id',
                'comp.nickname',
                'comp.cod',
                'comp.firma',
                'comp.pay',
                'comp.pay1',
                DB::raw("COALESCE(NULLIF(d.name, ''), NULLIF(d.name_ua, ''), NULLIF(d.name_en, ''), NULLIF(comp.nickname, ''), NULLIF(comp.namedoc, ''), NULLIF(comp.name, ''), CONCAT('Товар #', comp.id)) as name"),
                DB::raw('COALESCE(SUM(ps.count), 0) as stock_count'),
            ]);

        return response()->json([
            'target_project_id' => $targetProjectId,
            'items' => $items->map(fn ($item) => [
                'id' => (string) $item->id,
                'name' => (string) $item->name,
                'code' => (string) ($item->nickname ?: $item->cod ?: $item->id),
                'price' => (float) ($item->pay ?? 0),
                'purchase_price' => (float) ($item->pay1 ?? 0),
                'stock_count' => (float) ($item->stock_count ?? 0),
            ])->values(),
        ]);
    }

    public function productMappingSave(Request $request)
    {
        $fid = (string) session('fid', '');
        $doc = (string) $request->input('doc', session('doc', ''));
        $docId = (string) $request->input('doc_id', session('doc_id', '0'));
        $sourceProductId = trim((string) $request->input('source_product_id', ''));
        $targetProductId = trim((string) $request->input('target_product_id', ''));

        if ($fid === '' || $doc === '' || $docId === '' || $sourceProductId === '' || $targetProductId === '') {
            return response()->json(['message' => 'Не всі дані для маппінгу передані.'], 422);
        }

        if (! Schema::hasTable('product_project_mappings')) {
            return response()->json(['message' => 'Таблиця product_project_mappings не створена.'], 422);
        }

        $table = Document::tableForType($doc);
        $document = DB::table($table)
            ->where('id', $docId)
            ->where('firma', $fid)
            ->first();

        if (! $document) {
            return response()->json(['message' => 'Документ не знайдено.'], 404);
        }

        $counterpartyUserId = (int) $request->input('counterparty_user_id', 0);
        if ($counterpartyUserId <= 0) {
            $counterpartyUserId = (int) ($document->client1 ?? 0);
        }
        if ($counterpartyUserId <= 0) {
            return response()->json(['message' => 'Спочатку виберіть клієнта/продавця документа.'], 422);
        }

        $targetProjectId = $this->counterpartyProjectId($counterpartyUserId, $fid);
        if ($targetProjectId === null) {
            return response()->json(['message' => 'У клієнта документа не задано project_id.'], 422);
        }

        $sourceExists = DB::table('comp')
            ->where('firma', $fid)
            ->where('id', $sourceProductId)
            ->exists();
        if (! $sourceExists) {
            return response()->json(['message' => "Товар {$sourceProductId} не знайдено в поточному проекті."], 422);
        }

        $targetExists = DB::table('comp')
            ->where('firma', (string) $targetProjectId)
            ->where('id', $targetProductId)
            ->exists();
        if (! $targetExists) {
            return response()->json(['message' => "Товар {$targetProductId} не знайдено в проекті {$targetProjectId}."], 422);
        }

        $mappingKey = [
            'source_company_id' => (int) $fid,
            'counterparty_user_id' => $counterpartyUserId,
            'source_product_id' => $sourceProductId,
            'target_company_id' => $targetProjectId,
        ];

        $mappingExists = DB::table('product_project_mappings')
            ->where($mappingKey)
            ->exists();

        if ($mappingExists) {
            DB::table('product_project_mappings')
                ->where($mappingKey)
                ->update([
                    'target_product_id' => $targetProductId,
                    'updated_at' => now(),
                ]);
        } else {
            DB::table('product_project_mappings')
                ->insert(array_merge($mappingKey, [
                    'target_product_id' => $targetProductId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
        }

        return response()->json([
            'ok' => true,
            'source_product_id' => $sourceProductId,
            'target_project_id' => $targetProjectId,
            'target_product_id' => $targetProductId,
        ]);
    }

    private function counterpartyProjectIdForDocument(object $document, string $sourceCompanyId): ?int
    {
        $counterpartyId = trim((string) ($document->client1 ?? ''));
        if ($counterpartyId === '' || $counterpartyId === '0') {
            return null;
        }

        return $this->counterpartyProjectId((int) $counterpartyId, $sourceCompanyId);
    }

    private function counterpartyProjectId(int $counterpartyUserId, string $sourceCompanyId): ?int
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'project_id')) {
            return null;
        }

        $projectId = DB::table('users')
            ->where('id', $counterpartyUserId)
            ->where('firma', $sourceCompanyId)
            ->value('project_id');

        if ($projectId === null || (string) $projectId === (string) $sourceCompanyId) {
            return null;
        }

        return DB::table('project')->where('id', $projectId)->exists()
            ? (int) $projectId
            : null;
    }

    private function productMappingsForDocument(object $document, string $sourceCompanyId, ?int $targetProjectId): array
    {
        if ($targetProjectId === null || ! Schema::hasTable('product_project_mappings')) {
            return [];
        }

        $counterpartyUserId = (int) ($document->client1 ?? 0);

        return DB::table('product_project_mappings')
            ->where('source_company_id', (int) $sourceCompanyId)
            ->where('target_company_id', $targetProjectId)
            ->whereIn('counterparty_user_id', [$counterpartyUserId, 0])
            ->orderByRaw('CASE WHEN counterparty_user_id = ? THEN 0 ELSE 1 END', [$counterpartyUserId])
            ->get(['source_product_id', 'target_product_id'])
            ->groupBy(fn ($row) => (string) $row->source_product_id)
            ->map(fn ($rows) => (string) $rows->first()->target_product_id)
            ->all();
    }

    private function saveLoanRequestDocument(Request $request, string $docId, string $fid)
    {
        $payload = [
            'client1' => trim((string) $request->input('client1', '')),
            'collateral_type' => trim((string) $request->input('collateral_type', '')),
            'market_value' => (float) $request->input('market_value', 0),
            'ltv' => (string) $request->input('ltv', ''),
            'loan_amount' => (float) $request->input('loan_amount', 0),
            'interest_rate' => (float) $request->input('interest_rate', 0),
            'loan_term_months' => (string) $request->input('loan_term_months', ''),
            'investor_yield' => (float) $request->input('investor_yield', 0),
            'deadline_days' => (string) $request->input('deadline_days', ''),
            'comment' => trim((string) $request->input('comment', '')),
        ];

        $errors = [];
        if ($payload['client1'] === '') {
            $errors['client1'] = 'Выберите заемщика';
        }
        if ($payload['collateral_type'] === '') {
            $errors['collateral_type'] = 'Укажите тип залога';
        }
        if ($payload['market_value'] <= 0) {
            $errors['market_value'] = 'Укажите рыночную стоимость';
        }
        if (! in_array($payload['ltv'], ['40', '50', '60', '70', '80', '90', '100'], true)) {
            $errors['ltv'] = 'Выберите LTV сделки';
        }
        if ($payload['loan_amount'] <= 0) {
            $errors['loan_amount'] = 'Укажите сумму кредита';
        }
        if ($payload['interest_rate'] < 0 || $payload['interest_rate'] > 100) {
            $errors['interest_rate'] = 'Укажите процентную ставку от 0 до 100';
        }
        if (! in_array($payload['loan_term_months'], ['1', '3', '6', '9', '12', '24', '36'], true)) {
            $errors['loan_term_months'] = 'Выберите срок кредита';
        }
        if ($payload['investor_yield'] < 0 || $payload['investor_yield'] > 100) {
            $errors['investor_yield'] = 'Укажите доходность для инвесторов от 0 до 100';
        }
        if (! in_array($payload['deadline_days'], ['0', '1', '3', '7', '14', '21'], true)) {
            $errors['deadline_days'] = 'Выберите дедлайн';
        }

        $borrowerExists = $payload['client1'] !== ''
            && DB::table('users')
                ->where('id', (int) $payload['client1'])
                ->when(Schema::hasColumn('users', 'firma'), fn ($query) => $query->whereIn('firma', HoldingScope::projectIdsFor($fid)))
                ->exists();

        if ($payload['client1'] !== '' && ! $borrowerExists) {
            $errors['client1'] = 'Выберите заемщика из клиентов текущего bank scope.';
        }

        if ($errors !== []) {
            return redirect()->back()->withErrors($errors)->withInput();
        }

        $document = DB::table('document')
            ->where('id', $docId)
            ->where('firma', $fid)
            ->where('type', 'ZOUT')
            ->first();

        if (! $document) {
            return redirect()->route($this->documentRoutePrefix() . '.index')->with('error', 'Кредитная заявка не найдена.');
        }

        $deadlineAt = now()->addDays((int) $payload['deadline_days']);
        $content = $this->buildLoanRequestContent(
            $payload['collateral_type'],
            $payload['market_value'],
            (int) $payload['ltv'],
            $payload['loan_amount'],
            $payload['interest_rate'],
            (int) $payload['loan_term_months'],
            $payload['investor_yield'],
            (int) $payload['deadline_days'],
            $deadlineAt->format('d-m-Y'),
            $payload['comment']
        );

        $documentDate = trim((string) $request->input('data', ''));
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $documentDate) === 1) {
            $documentDate = \DateTimeImmutable::createFromFormat('Y-m-d', $documentDate)?->format('d-m-Y') ?? '';
        }
        if ($documentDate === '') {
            $documentDate = (string) ($document->data ?? date('d-m-Y'));
        }
        $num = trim((string) $request->input('num', $document->num ?? ''));

        DB::table('document')->where('id', $docId)->update([
            'num' => $num,
            'client1' => $payload['client1'],
            'summa' => round($payload['loan_amount'], 2),
            'status' => (string) $request->input('status', $document->status ?? 0),
            'data' => $documentDate,
            'data2' => $deadlineAt->format('d-m-Y'),
            'time' => trim((string) $request->input('time', $document->time ?? '')),
            'content' => $content,
            'ttn' => trim((string) $request->input('ttn', $document->ttn ?? '')),
            'reteil' => (string) $payload['ltv'],
            'manager' => session('login', ''),
            'user' => session('login', ''),
            'numorder' => 'AV8-LOAN',
            'typeproduct' => 'credit_request',
        ]);

        return redirect()->route($this->documentRoutePrefix() . '.show', [
            'doc' => 'ZOUT',
            'doc_id' => $docId,
            'num' => $num,
            'year' => strlen($documentDate) >= 10 ? substr($documentDate, 6, 4) : date('Y'),
        ])->with('success', 'Кредитная заявка сохранена.');
    }

    private function parseLoanRequestContent(string $content): array
    {
        $read = static function (string $label) use ($content): string {
            if (preg_match('/^' . preg_quote($label, '/') . ':\s*(.+)$/mu', $content, $matches) === 1) {
                return trim((string) $matches[1]);
            }

            return '';
        };

        $termLabel = $read('Срок кредита');
        $termMonths = match ($termLabel) {
            '1 мес.' => '1',
            '3 мес.' => '3',
            '6 мес.' => '6',
            '9 мес.' => '9',
            '2 года' => '24',
            '3 года' => '36',
            default => '12',
        };
        $deadlineText = $read('Дедлайн сбора');

        return [
            'collateral_type' => $read('Тип залога') ?: 'Автомобиль',
            'market_value' => preg_replace('/[^\d.]/', '', str_replace(' ', '', $read('Рыночная стоимость'))) ?: '',
            'loan_amount' => preg_replace('/[^\d.]/', '', str_replace(' ', '', $read('Сумма кредита'))) ?: '',
            'ltv' => preg_replace('/\D+/', '', $read('LTV сделки')) ?: '70',
            'interest_rate' => preg_replace('/[^\d.]/', '', str_replace(' ', '', $read('Процентная ставка заемщика'))) ?: '',
            'loan_term_months' => $termMonths,
            'investor_yield' => preg_replace('/[^\d.]/', '', str_replace(' ', '', $read('Доходность для инвесторов'))) ?: '',
            'deadline_days' => preg_match('/^(\d+)/', $deadlineText, $m) === 1 ? $m[1] : '7',
            'comment' => $read('Комментарий'),
        ];
    }

    private function buildLoanRequestContent(
        string $collateralType,
        float $marketValue,
        int $ltv,
        float $loanAmount,
        float $interestRate,
        int $termMonths,
        float $investorYield,
        int $deadlineDays,
        string $deadlineDate,
        string $comment
    ): string {
        return implode("\n", array_filter([
            '[AV8_LOAN_REQUEST]',
            'Тип залога: ' . $collateralType,
            'Рыночная стоимость: ' . number_format($marketValue, 2, '.', ' '),
            'LTV сделки: ' . $ltv . '%',
            'Сумма кредита: ' . number_format($loanAmount, 2, '.', ' '),
            'Процентная ставка заемщика: ' . number_format($interestRate, 2, '.', ' ') . '%',
            'Срок кредита: ' . $this->loanTermLabel($termMonths),
            'Доходность для инвесторов: ' . number_format($investorYield, 2, '.', ' ') . '%',
            'Дедлайн сбора: ' . $deadlineDays . ' дн. (' . $deadlineDate . ')',
            $comment !== '' ? 'Комментарий: ' . $comment : '',
        ]));
    }

    private function loanTermLabel(int $months): string
    {
        return match ($months) {
            1 => '1 мес.',
            3 => '3 мес.',
            6 => '6 мес.',
            9 => '9 мес.',
            12 => '1 год',
            24 => '2 года',
            36 => '3 года',
            default => $months . ' мес.',
        };
    }

    private function loanRepaymentSchedule(object $loan, array $loanMeta): array
    {
        $principal = round((float) ($loan->summa ?? 0), 2);
        $termMonths = max(1, (int) ($loanMeta['loan_term_months'] ?? 12));
        $annualRate = max(0, (float) ($loanMeta['interest_rate'] ?? 0));
        $totalDue = round($principal * (1 + ($annualRate / 100) * ($termMonths / 12)), 2);
        $installment = $termMonths > 0 ? round($totalDue / $termMonths, 2) : $totalDue;
        $paidTotal = round($this->loanPaymentTotal((int) ($loan->id ?? 0), (string) ($loan->firma ?? '')), 2);
        $remainingPaid = $paidTotal;
        $startDate = $this->loanScheduleStartDate((string) ($loan->data ?? ''));
        $rows = [];

        for ($month = 1; $month <= $termMonths; $month++) {
            $dueAmount = $month === $termMonths
                ? round($totalDue - ($installment * ($termMonths - 1)), 2)
                : $installment;
            $paidAmount = min($dueAmount, max(0, $remainingPaid));
            $remainingPaid = max(0, $remainingPaid - $dueAmount);

            $rows[] = [
                'number' => $month,
                'due_date' => $startDate->copy()->addMonthsNoOverflow($month)->format('d-m-Y'),
                'amount' => $dueAmount,
                'paid' => round($paidAmount, 2),
                'remaining' => round(max(0, $dueAmount - $paidAmount), 2),
                'status' => $paidAmount >= $dueAmount ? 'paid' : ($paidAmount > 0 ? 'partial' : 'pending'),
            ];
        }

        $remainingTotal = round(max(0, $totalDue - $paidTotal), 2);

        return [
            'principal' => $principal,
            'annual_rate' => $annualRate,
            'term_months' => $termMonths,
            'total_due' => $totalDue,
            'paid_total' => min($paidTotal, $totalDue),
            'overpaid' => round(max(0, $paidTotal - $totalDue), 2),
            'remaining_total' => $remainingTotal,
            'rows' => $rows,
        ];
    }

    private function loanPaymentTotal(int $loanId, string $projectId): float
    {
        if ($loanId <= 0 || ! Schema::hasTable('z_document')) {
            return 0.0;
        }

        return (float) DB::table('z_document')
            ->where('docid', (string) $loanId)
            ->where('firma', $projectId)
            ->where('type', 'PO')
            ->where(function ($query) {
                $query->where('typeproduct', 'credit_payment')
                    ->orWhere('numorder', 'AV8-LOAN-PAYMENT')
                    ->orWhere('content', 'like', '%[AV8_LOAN_PAYMENT]%');
            })
            ->sum('summa');
    }

    private function loanScheduleStartDate(string $date): Carbon
    {
        try {
            return Carbon::createFromFormat('d-m-Y', $date)->startOfDay();
        } catch (\Throwable) {
            return now()->startOfDay();
        }
    }

    private function documentRoutePrefix(): string
    {
        $routeName = (string) request()->route()?->getName();
        if (str_starts_with($routeName, 'bank.loanDocs.')) {
            return 'bank.loanDocs';
        }

        return str_starts_with($routeName, 'loan.') ? 'loan' : 'document';
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
