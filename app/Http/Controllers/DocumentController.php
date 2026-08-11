<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Goods;
use App\Models\EducationTopic;
use App\Models\Project;
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

    private function syncProjectMirrorDocuments(string $docType, string $docId, string $sourceCompanyId): void
    {
        if (! in_array($docType, ['PN', 'RN'], true)) {
            return;
        }

        $sourceTable = Document::tableForType($docType);
        $sourceDocument = DB::table($sourceTable)
            ->where('id', $docId)
            ->where('firma', $sourceCompanyId)
            ->where('type', $docType)
            ->first();

        if (! $sourceDocument) {
            return;
        }

        $targetCompanyId = $this->counterpartyProjectIdForDocument($sourceDocument, $sourceCompanyId);
        if ($targetCompanyId === null) {
            return;
        }

        $targetRootType = $docType === 'PN' ? 'ZOUT' : 'ZIN';
        $targetChildType = $docType === 'PN' ? 'RN' : 'PN';
        $lineItems = ZBody::where('docid', $docId)
            ->where('firma', $sourceCompanyId)
            ->orderBy('id')
            ->get();

        if ($lineItems->isEmpty()) {
            return;
        }

        $targetRows = $this->projectMirrorRows(
            $lineItems,
            $sourceDocument,
            (int) $sourceCompanyId,
            $targetCompanyId,
            $targetChildType
        );

        if ($targetRows === []) {
            return;
        }

        DB::transaction(function () use (
            $sourceDocument,
            $sourceCompanyId,
            $docType,
            $docId,
            $targetCompanyId,
            $targetRootType,
            $targetChildType,
            $targetRows
        ): void {
            $marker = $this->projectMirrorMarker($sourceCompanyId, $docType, $docId);
            $mirrorClientId = $this->mirrorCounterpartyUserId($sourceCompanyId, $targetCompanyId, (int) ($sourceDocument->client1 ?? 0));
            $targetWarehouseId = $this->defaultWarehouseId($targetCompanyId, (string) ($sourceDocument->sklads ?? ''));
            $root = $this->upsertMirrorHeader(
                'document',
                $targetRootType,
                $targetCompanyId,
                $marker,
                [
                    'client1' => $mirrorClientId,
                    'client2' => (string) ($sourceDocument->client2 ?? '0'),
                    'summa' => (float) ($sourceDocument->summa ?? 0),
                    'status' => (string) ($sourceDocument->status ?? '0'),
                    'data' => (string) ($sourceDocument->data ?? date('d-m-Y')),
                    'data2' => (string) ($sourceDocument->data2 ?? date('d-m-Y')),
                    'time' => (string) ($sourceDocument->time ?? date('H:i:s')),
                    'firma' => (string) $targetCompanyId,
                    'content' => trim($marker.' Дзеркальний документ для '.$docType.' #'.$docId),
                    'ttn' => (string) ($sourceDocument->ttn ?? ''),
                    'oplata' => (string) ($sourceDocument->oplata ?? ''),
                    'reteil' => (string) ($sourceDocument->reteil ?? ''),
                    'reestr' => (string) ($sourceDocument->reestr ?? ''),
                    'sklads' => $targetWarehouseId,
                    'money' => (string) ($sourceDocument->money ?? ''),
                ]
            );

            $rootId = (string) $root->id;
            $rootNum = (string) ($root->num ?: $root->numz ?: '0');

            DB::table('document')
                ->where('id', $rootId)
                ->where('firma', (string) $targetCompanyId)
                ->update([
                    'docid' => $rootId,
                    'numz' => $rootNum,
                    'typez' => $targetRootType,
                ]);

            $child = $this->upsertMirrorHeader(
                'z_document',
                $targetChildType,
                $targetCompanyId,
                $marker,
                [
                    'client1' => $mirrorClientId,
                    'client2' => (string) ($sourceDocument->client2 ?? '0'),
                    'summa' => (float) ($sourceDocument->summa ?? 0),
                    'status' => (string) ($sourceDocument->status ?? '0'),
                    'data' => (string) ($sourceDocument->data ?? date('d-m-Y')),
                    'data2' => (string) ($sourceDocument->data2 ?? date('d-m-Y')),
                    'time' => (string) ($sourceDocument->time ?? date('H:i:s')),
                    'firma' => (string) $targetCompanyId,
                    'numz' => $rootNum,
                    'typez' => $targetRootType,
                    'docid' => $rootId,
                    'content' => trim($marker.' Дзеркальная накладная для '.$docType.' #'.$docId),
                    'ttn' => (string) ($sourceDocument->ttn ?? ''),
                    'oplata' => (string) ($sourceDocument->oplata ?? ''),
                    'reteil' => (string) ($sourceDocument->reteil ?? ''),
                    'reestr' => (string) ($sourceDocument->reestr ?? ''),
                    'sklads' => $targetWarehouseId,
                    'money' => (string) ($sourceDocument->money ?? ''),
                ]
            );

            $this->replaceMirrorBodyRows($rootId, $rootNum, $targetRootType, (string) $targetCompanyId, $targetRows);
            $this->replaceMirrorBodyRows((string) $child->id, (string) $child->num, $targetChildType, (string) $targetCompanyId, $targetRows);
        });
    }

    private function ensureSourceParentOrder(string $docType, string $docId, string $companyId): void
    {
        if (! in_array($docType, ['PN', 'RN'], true)) {
            return;
        }

        $child = DB::table('z_document')
            ->where('id', $docId)
            ->where('firma', $companyId)
            ->where('type', $docType)
            ->first();

        if (! $child) {
            return;
        }

        $parentId = (int) ($child->docid ?? 0);
        $parentType = $docType === 'PN' ? 'ZIN' : 'ZOUT';
        if ($parentId > 0 && DB::table('document')
            ->where('id', $parentId)
            ->where('firma', $companyId)
            ->where('type', $parentType)
            ->exists()) {
            return;
        }

        $lineItems = ZBody::where('docid', $docId)
            ->where('firma', $companyId)
            ->orderBy('id')
            ->get();

        if ($lineItems->isEmpty()) {
            return;
        }

        DB::transaction(function () use ($child, $docType, $docId, $companyId, $parentType, $lineItems): void {
            $marker = "[AV8_SOURCE_ORDER child={$docType} id={$docId}]";
            $parent = $this->upsertMirrorHeader(
                'document',
                $parentType,
                (int) $companyId,
                $marker,
                [
                    'client1' => (string) ($child->client1 ?? '0'),
                    'client2' => (string) ($child->client2 ?? '0'),
                    'summa' => (float) ($child->summa ?? 0),
                    'status' => (string) ($child->status ?? '0'),
                    'data' => (string) ($child->data ?? date('d-m-Y')),
                    'data2' => (string) ($child->data2 ?? date('d-m-Y')),
                    'time' => (string) ($child->time ?? date('H:i:s')),
                    'firma' => (string) $companyId,
                    'content' => trim($marker.' Автоматическая заявка для '.$docType.' #'.$docId),
                    'ttn' => (string) ($child->ttn ?? ''),
                    'oplata' => (string) ($child->oplata ?? ''),
                    'reteil' => (string) ($child->reteil ?? ''),
                    'reestr' => (string) ($child->reestr ?? ''),
                    'sklads' => (string) ($child->sklads ?? ''),
                    'money' => (string) ($child->money ?? ''),
                ]
            );

            $parentId = (string) $parent->id;
            $parentNum = (string) ($parent->num ?: $parent->numz ?: '0');

            DB::table('document')
                ->where('id', $parentId)
                ->where('firma', $companyId)
                ->update([
                    'docid' => $parentId,
                    'numz' => $parentNum,
                    'typez' => $parentType,
                ]);

            DB::table('z_document')
                ->where('id', $docId)
                ->where('firma', $companyId)
                ->update([
                    'docid' => $parentId,
                    'numz' => $parentNum,
                    'typez' => $parentType,
                ]);

            $rows = $lineItems->map(fn ($line): array => [
                'pid' => $line->pid ?? '',
                'pnum' => (string) $line->pnum,
                'pcount' => (float) ($line->pcount ?? 0),
                'pprice' => (float) ($line->pprice ?? 0),
                'psumma' => (float) ($line->psumma ?? 0),
                'zvalue' => (string) ($line->zvalue ?? ''),
            ])->all();

            $this->replaceMirrorBodyRows($parentId, $parentNum, $parentType, $companyId, $rows);
        });
    }

    private function syncLinkedDocumentPostingState(string $docType, string $docId, string $companyId, bool $isPosted): void
    {
        if (! in_array($docType, ['PN', 'RN'], true)) {
            return;
        }

        $sourceDocument = DB::table(Document::tableForType($docType))
            ->where('id', $docId)
            ->where('firma', $companyId)
            ->where('type', $docType)
            ->first();

        if (! $sourceDocument) {
            return;
        }

        $postedValue = $isPosted ? 1 : 0;
        $parentType = $docType === 'PN' ? 'ZIN' : 'ZOUT';
        $parentId = (int) ($sourceDocument->docid ?? 0);

        if ($parentId > 0) {
            DB::table('document')
                ->where('id', $parentId)
                ->where('firma', $companyId)
                ->where('type', $parentType)
                ->update(['provodka' => $postedValue]);
        }

        $targetCompanyId = $this->counterpartyProjectIdForDocument($sourceDocument, $companyId);
        if ($targetCompanyId === null) {
            return;
        }

        $marker = $this->projectMirrorMarker($companyId, $docType, $docId);
        $targetRootType = $docType === 'PN' ? 'ZOUT' : 'ZIN';
        $targetChildType = $docType === 'PN' ? 'RN' : 'PN';

        DB::table('document')
            ->where('firma', (string) $targetCompanyId)
            ->where('type', $targetRootType)
            ->where('content', 'like', '%'.$marker.'%')
            ->update(['provodka' => $postedValue]);

        DB::table('z_document')
            ->where('firma', (string) $targetCompanyId)
            ->where('type', $targetChildType)
            ->where('content', 'like', '%'.$marker.'%')
            ->update(['provodka' => $postedValue]);
    }

    private function projectMirrorRows($lineItems, object $sourceDocument, int $sourceCompanyId, int $targetCompanyId, string $targetDocType): array
    {
        $counterpartyUserId = (int) ($sourceDocument->client1 ?? 0);
        $rows = [];

        foreach ($lineItems as $line) {
            $targetProductId = $this->mappedProjectProductId(
                $sourceCompanyId,
                (string) $line->pnum,
                $targetCompanyId,
                $counterpartyUserId
            );
            $quantity = (float) ($line->pcount ?? 0);
            $price = (float) ($line->pprice ?? 0);

            $rows[] = [
                'pid' => $line->pid ?? '',
                'pnum' => $targetProductId,
                'pcount' => $quantity,
                'pprice' => $price,
                'psumma' => (float) ($line->psumma ?? ($quantity * $price)),
                'zvalue' => $targetDocType === 'RN'
                    ? ZBody::resolveUnitCost($targetProductId, (string) $targetCompanyId)
                    : '',
            ];
        }

        return $rows;
    }

    private function mappedProjectProductId(int $sourceCompanyId, string $sourceProductId, int $targetCompanyId, int $counterpartyUserId): string
    {
        if (! Schema::hasTable('product_project_mappings')) {
            throw new \RuntimeException('Таблиця маппінгу товарів product_project_mappings не створена.');
        }

        $query = DB::table('product_project_mappings')
            ->where('source_company_id', $sourceCompanyId)
            ->where('source_product_id', $sourceProductId)
            ->where('target_company_id', $targetCompanyId);

        if (Schema::hasColumn('product_project_mappings', 'counterparty_user_id')) {
            $query->whereIn('counterparty_user_id', [$counterpartyUserId, 0])
                ->orderByRaw('CASE WHEN counterparty_user_id = ? THEN 0 ELSE 1 END', [$counterpartyUserId]);
        }

        $targetProductId = trim((string) $query->value('target_product_id'));
        if ($targetProductId === '') {
            throw new \RuntimeException(
                "Не знайдено маппінг товару {$sourceProductId}: проект {$sourceCompanyId}, контрагент {$counterpartyUserId} -> проект {$targetCompanyId}."
            );
        }

        return $targetProductId;
    }

    private function upsertMirrorHeader(string $table, string $docType, int $companyId, string $marker, array $attributes): object
    {
        $existing = DB::table($table)
            ->where('firma', (string) $companyId)
            ->where('type', $docType)
            ->where('content', 'like', '%'.$marker.'%')
            ->first();

        $columns = Schema::getColumnListing($table);
        $now = now();
        $payload = array_merge([
            'client1' => '0',
            'client2' => '0',
            'type' => $docType,
            'summa' => 0,
            'status' => 0,
            'data' => $now->format('d-m-Y'),
            'data2' => $now->format('d-m-Y'),
            'time' => $now->format('H:i:s'),
            'firma' => (string) $companyId,
            'dt' => $now->timestamp,
            'numz' => '0',
            'typez' => '',
            'docid' => '0',
            'manager' => session('login', ''),
            'user' => session('login', ''),
            'docum' => '',
            'dostup' => 1,
            'work' => session('work', '1'),
        ], $attributes);

        $payload['type'] = $docType;
        $payload['firma'] = (string) $companyId;
        $payload = array_intersect_key($payload, array_flip($columns));

        if ($existing) {
            DB::table($table)
                ->where('id', $existing->id)
                ->where('firma', (string) $companyId)
                ->update($payload);

            return DB::table($table)->where('id', $existing->id)->first();
        }

        $year = yearFromDMY((string) ($payload['data'] ?? date('d-m-Y')));
        $num = Document::assignNextNum($docType, (string) $companyId, $year);
        $payload['num'] = $num;
        $payload['numz'] = $payload['numz'] ?? $num;

        $id = DB::table($table)->insertGetId($payload);

        return DB::table($table)->where('id', $id)->first();
    }

    private function replaceMirrorBodyRows(string $docId, string $docNum, string $docType, string $companyId, array $rows): void
    {
        ZBody::where('docid', $docId)
            ->where('firma', $companyId)
            ->delete();

        foreach ($rows as $row) {
            ZBody::create([
                'docnum' => $docNum,
                'pid' => $row['pid'] ?? '',
                'pnum' => $row['pnum'],
                'pcount' => $row['pcount'],
                'pprice' => $row['pprice'],
                'psumma' => $row['psumma'],
                'type' => $docType,
                'firma' => $companyId,
                'docid' => $docId,
                'zvalue' => $row['zvalue'] ?? '',
            ]);
        }
    }

    private function projectMirrorMarker(string $sourceCompanyId, string $docType, string $docId): string
    {
        return "[AV8_PROJECT_MIRROR source={$sourceCompanyId} doc={$docType} id={$docId}]";
    }

    private function mirrorCounterpartyUserId(string $sourceCompanyId, int $targetCompanyId, int $fallbackUserId): string
    {
        if (Schema::hasColumn('users', 'project_id')) {
            $userId = DB::table('users')
                ->where('firma', (string) $targetCompanyId)
                ->where('project_id', (int) $sourceCompanyId)
                ->orderBy('id')
                ->value('id');

            if ($userId !== null) {
                return (string) $userId;
            }
        }

        return $fallbackUserId > 0 ? (string) $fallbackUserId : '0';
    }

    private function defaultWarehouseId(int $companyId, string $sourceWarehouseId): string
    {
        if ($sourceWarehouseId !== '') {
            $sameWarehouseExists = DB::table('conf')
                ->where('id', $sourceWarehouseId)
                ->where('type', 'sklads')
                ->where('firma', (string) $companyId)
                ->exists();

            if ($sameWarehouseExists) {
                return $sourceWarehouseId;
            }
        }

        if (Schema::hasColumn('conf', 'is_default')) {
            $defaultId = DB::table('conf')
                ->where('type', 'sklads')
                ->where('firma', (string) $companyId)
                ->where('is_default', 1)
                ->orderBy('id')
                ->value('id');

            if ($defaultId !== null) {
                return (string) $defaultId;
            }
        }

        return (string) (DB::table('conf')
            ->where('type', 'sklads')
            ->where('firma', (string) $companyId)
            ->orderBy('id')
            ->value('id') ?? '');
    }

    private function isRootDocumentLocked(string $docType, string $docId, string $fid): bool
    {
        if (!in_array($docType, ['ZOUT', 'ZIN', 'CRDT'], true) || $docId === '' || $docId === '0') {
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
        $salaryEmployees = collect();
        $salaryCashboxes = collect();
        $salaryPaymentTypes = collect();
        $unassignedSalaryDocuments = collect();
        if ($doc === 'ZV') {
            $salaryEmployees = $this->salaryStatementEmployees((int) $fid);
            $salaryCashboxes = DB::table('conf')
                ->where('type', 'oplata')
                ->where('firma', $fid)
                ->where('vision', '1')
                ->orderBy('name')
                ->get(['id', 'name', 'currency']);
            $salaryPaymentTypes = ConfModel::paymentTypesForDocument($fid, 'ZP');
            $unassignedSalaryDocuments = $this->unassignedSalaryDocuments((int) $fid);
        }

        // Attach clientInfo icons strip to each item
        $viewYear = session('year', date('Y'));
        foreach ($rows as $i => $row) {
            $clientId = $row->client1 ?? 0;
            $numz = $row->numz ?? '0';
            $typez = $row->typez ?? '';
            $rowDocid = in_array($doc, ['ZIN', 'ZOUT', 'CRDT'], true) ? ($row->id ?? 0) : ($row->docid ?? 0);
            $summa_ = $row->summa ?? 0;

            // For ZIN/ZOUT root docs: if typez is empty, use the doc's own type
            if (in_array($doc, ['ZIN', 'ZOUT', 'CRDT'], true) && ($typez === '' || $typez === '0')) {
                $typez = $doc;
            }

            // For ZIN/ZOUT: show clientInfo even if typez is empty (they ARE the root)
            // For child docs: show only if their parent is ZIN/ZOUT
            $showIcons = false;
            if ($clientId > 0) {
                if (in_array($doc, ['ZIN', 'ZOUT', 'CRDT'], true)) {
                    $showIcons = true;
                } elseif (in_array($typez, ['ZIN', 'ZOUT', 'CRDT'], true)) {
                    $showIcons = true;
                }
            }

            $items[$i]['clientInfoHtml'] = $showIcons
                ? Docs::clientInfo($clientId, $numz, $typez, $viewYear, $rowDocid, $summa_)
                : '';
        }

        $view = in_array($doc, ['ZIN', 'ZOUT', 'CRDT'], true) ? 'document.zakaz' : 'document.index';

        return view($view, compact(
            'items', 'total_sum', 'rows', 'doc', 'pos', 'total', 'fd', 'fid', 'documentRoutePrefix',
            'salaryEmployees', 'salaryCashboxes', 'salaryPaymentTypes', 'unassignedSalaryDocuments'
        ));
    }

    public function salaryStatementCreate()
    {
        abort_unless(Schema::hasTable('salary_statement_lines'), 503, 'Сначала выполните миграции базы данных.');

        return $this->salaryStatementEditorView(0);
    }

    public function salaryStatementShow(Request $request, int $id)
    {
        abort_unless(Schema::hasTable('salary_statement_lines'), 503, 'Сначала выполните миграции базы данных.');
        $statement = $this->salaryStatement($id, (int) session('fid', 0));
        abort_unless($statement, 404);

        if (! $request->expectsJson()) {
            return $this->salaryStatementEditorView($id);
        }

        return response()->json($this->salaryStatementPayload($statement));
    }

    private function salaryStatementEditorView(int $statementId)
    {
        $fid = (int) session('fid', 0);
        if ($statementId > 0) {
            abort_unless($this->salaryStatement($statementId, $fid), 404);
        }

        $salaryEmployees = $this->salaryStatementEmployees($fid);
        $salaryCashboxes = DB::table('conf')
            ->where('type', 'oplata')
            ->where('firma', $fid)
            ->where('vision', '1')
            ->orderBy('name')
            ->get(['id', 'name', 'currency']);
        $salaryPaymentTypes = ConfModel::paymentTypesForDocument($fid, 'ZP');

        return view('document.salary_statement_page', [
            'initialStatementId' => $statementId,
            'salaryEmployees' => $salaryEmployees,
            'salaryCashboxes' => $salaryCashboxes,
            'salaryPaymentTypes' => $salaryPaymentTypes,
        ]);
    }

    public function salaryStatementStore(Request $request)
    {
        abort_unless(Schema::hasTable('salary_statement_lines'), 503, 'Сначала выполните миграции базы данных.');

        $fid = (int) session('fid', 0);
        $validated = $this->validateSalaryStatement($request, $fid);
        $now = now();

        $statementId = DB::transaction(function () use ($validated, $fid, $now): int {
            $statementDate = curdate($validated['data']);
            $num = Document::assignNextNum('ZV', (string) $fid, $this->documentYear($statementDate));
            $statementId = DB::table('z_document')->insertGetId([
                'num' => $num,
                'client1' => 0,
                'client2' => 0,
                'type' => 'ZV',
                'summa' => collect($validated['employees'])->sum('salary_amount'),
                'status' => 0,
                'data' => $statementDate,
                'data2' => $statementDate,
                'time' => $now->format('H:i:s'),
                'firma' => $fid,
                'dt' => $now->timestamp,
                'numz' => $num,
                'typez' => 'ZV',
                'docid' => 0,
                'manager' => session('login', ''),
                'user' => session('login', ''),
                'reestr' => (string) $validated['reestr'],
                'content' => $validated['content'] ?? '',
                'docum' => '',
                'dostup' => 1,
                'work' => session('work', '1'),
            ]);

            DB::table('z_document')->where('id', $statementId)->update(['docid' => $statementId]);
            $this->replaceSalaryStatementLines($statementId, $fid, $validated['employees']);

            return $statementId;
        });

        $statement = $this->salaryStatement($statementId, $fid);

        return response()->json(['success' => true, 'statement' => $this->salaryStatementPayload($statement)], 201);
    }

    public function salaryStatementUpdate(Request $request, int $id)
    {
        abort_unless(Schema::hasTable('salary_statement_lines'), 503, 'Сначала выполните миграции базы данных.');

        $fid = (int) session('fid', 0);
        $statement = $this->salaryStatement($id, $fid);
        abort_unless($statement, 404);
        $validated = $this->validateSalaryStatement($request, $fid);

        DB::transaction(function () use ($id, $fid, $validated): void {
            $paidEmployeeIds = DB::table('salary_statement_lines')
                ->where('statement_document_id', $id)
                ->whereNotNull('zp_document_id')
                ->pluck('employee_id')
                ->map(fn ($employeeId) => (int) $employeeId);
            $submittedEmployeeIds = collect($validated['employees'])->pluck('employee_id')->map(fn ($employeeId) => (int) $employeeId);

            if ($paidEmployeeIds->diff($submittedEmployeeIds)->isNotEmpty()) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'employees' => 'Нельзя удалить сотрудника, по которому уже создан документ ZP.',
                ]);
            }

            DB::table('z_document')->where('id', $id)->where('firma', $fid)->where('type', 'ZV')->update([
                'data' => curdate($validated['data']),
                'data2' => curdate($validated['data']),
                'reestr' => (string) $validated['reestr'],
                'content' => $validated['content'] ?? '',
            ]);
            $this->replaceSalaryStatementLines($id, $fid, $validated['employees']);
            $this->refreshSalaryStatementTotal($id, $fid);
        });

        return response()->json([
            'success' => true,
            'statement' => $this->salaryStatementPayload($this->salaryStatement($id, $fid)),
        ]);
    }

    public function salaryStatementDestroy(int $id)
    {
        abort_unless(Schema::hasTable('salary_statement_lines'), 503, 'Сначала выполните миграции базы данных.');

        $fid = (int) session('fid', 0);
        $statement = $this->salaryStatement($id, $fid);
        abort_unless($statement, 404);

        if ((int) ($statement->provodka ?? 0) === 1) {
            return response()->json([
                'success' => false,
                'message' => 'Проведенную ведомость удалить нельзя. Сначала снимите проводку.',
            ], 422);
        }

        $hasPayouts = DB::table('salary_statement_lines')
            ->where('statement_document_id', $id)
            ->whereNotNull('zp_document_id')
            ->exists();

        if ($hasPayouts) {
            return response()->json([
                'success' => false,
                'message' => 'Нельзя удалить ведомость, пока в ней есть документы ZP.',
            ], 422);
        }

        DB::transaction(function () use ($id, $fid): void {
            DB::table('salary_statement_lines')->where('statement_document_id', $id)->where('project_id', $fid)->delete();
            DB::table('z_document')->where('id', $id)->where('firma', $fid)->where('type', 'ZV')->delete();
        });

        session(['num' => '0', 'doc_id' => '0']);

        return response()->json(['success' => true]);
    }

    public function salaryStatementEmployeeDestroy(int $id, int $lineId)
    {
        abort_unless(Schema::hasTable('salary_statement_lines'), 503, 'Сначала выполните миграции базы данных.');
        $fid = (int) session('fid', 0);
        abort_unless($this->salaryStatement($id, $fid), 404);

        $line = DB::table('salary_statement_lines')
            ->where('id', $lineId)
            ->where('statement_document_id', $id)
            ->where('project_id', $fid)
            ->first();
        abort_unless($line, 404);

        if ($line->zp_document_id) {
            return response()->json(['success' => false, 'message' => 'Сотрудника с созданным ZP удалить нельзя.'], 422);
        }

        DB::table('salary_statement_lines')->where('id', $lineId)->delete();
        $this->refreshSalaryStatementTotal($id, $fid);

        return response()->json(['success' => true]);
    }

    public function salaryStatementPayout(Request $request, int $id, int $lineId)
    {
        abort_unless(Schema::hasTable('salary_statement_lines'), 503, 'Сначала выполните миграции базы данных.');
        $fid = (int) session('fid', 0);
        $statement = $this->salaryStatement($id, $fid);
        abort_unless($statement, 404);

        $validated = $request->validate([
            'salary_amount' => ['required', 'numeric', 'min:0.01', 'max:9999999999.99'],
            'oplata' => ['required', 'integer'],
            'reestr' => ['required', 'integer'],
            'data' => ['required', 'string', 'max:20'],
            'content' => ['nullable', 'string', 'max:65535'],
        ]);
        $this->validateSalaryPayoutClassifiers($validated, $fid);

        $zpId = DB::transaction(function () use ($id, $lineId, $fid, $statement, $validated): int {
            $line = DB::table('salary_statement_lines')
                ->where('id', $lineId)
                ->where('statement_document_id', $id)
                ->where('project_id', $fid)
                ->lockForUpdate()
                ->first();
            abort_unless($line, 404);

            if ($line->zp_document_id) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'employee' => 'По сотруднику уже создан документ ZP.',
                ]);
            }

            $now = now();
            $payoutDate = curdate($validated['data']);
            $zpId = DB::table('z_document')->insertGetId([
                'num' => Document::assignNextNum('ZP', (string) $fid, $this->documentYear($payoutDate)),
                'client1' => $line->employee_id,
                'client2' => 0,
                'type' => 'ZP',
                'summa' => round((float) $validated['salary_amount'], 2),
                'status' => 0,
                'data' => $payoutDate,
                'data2' => $payoutDate,
                'time' => $now->format('H:i:s'),
                'firma' => $fid,
                'dt' => $now->timestamp,
                'numz' => $statement->num,
                'typez' => 'ZV',
                'docid' => $id,
                'manager' => session('login', ''),
                'user' => session('login', ''),
                'oplata' => (string) $validated['oplata'],
                'reestr' => (string) $validated['reestr'],
                'content' => $validated['content'] ?? ('Выплата по ведомости ZV №' . $statement->num),
                'docum' => '',
                'dostup' => 1,
                'work' => session('work', '1'),
            ]);

            DB::table('salary_statement_lines')->where('id', $lineId)->update([
                'salary_amount' => round((float) $validated['salary_amount'], 2),
                'zp_document_id' => $zpId,
                'updated_at' => $now,
            ]);

            return $zpId;
        });

        try {
            $result = Document::provodka((string) $zpId, 'ZP', (string) $fid);
            if (! ($result['isPosted'] ?? false)) {
                throw new \RuntimeException('Документ ZP не проведен.');
            }
        } catch (\Throwable $exception) {
            DB::table('salary_statement_lines')->where('id', $lineId)->where('zp_document_id', $zpId)->update(['zp_document_id' => null]);
            DB::table('z_document')->where('id', $zpId)->where('provodka', 0)->delete();

            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 422);
        }

        $this->refreshSalaryStatementTotal($id, $fid);

        return response()->json([
            'success' => true,
            'statement' => $this->salaryStatementPayload($this->salaryStatement($id, $fid)),
        ]);
    }

    private function salaryStatement(int $id, int $fid): ?object
    {
        return DB::table('z_document')
            ->where('id', $id)
            ->where('firma', $fid)
            ->where('type', 'ZV')
            ->first();
    }

    private function salaryStatementEmployees(int $fid)
    {
        if (! Schema::hasTable('team_memberships')) {
            return collect();
        }

        return DB::table('team_memberships as tm')
            ->join('users as u', 'u.id', '=', 'tm.user_id')
            ->where('tm.project_id', $fid)
            ->orderBy('u.secondname')
            ->orderBy('u.name')
            ->orderBy('u.id')
            ->get([
                'u.id',
                'u.name',
                'u.secondname',
                'u.fathername',
                'u.orgname',
                'u.email',
            ])
            ->map(function ($employee) {
                $employee->display_name = trim(implode(' ', array_filter([
                    $employee->secondname ?? '',
                    $employee->name ?? '',
                    $employee->fathername ?? '',
                ]))) ?: trim((string) ($employee->orgname ?? '')) ?: ('Сотрудник #' . $employee->id);

                return $employee;
            })
            ->values();
    }

    private function defaultSalaryStatement(int $fid): ?object
    {
        if (! Schema::hasTable('salary_statement_lines')) {
            return null;
        }

        return DB::table('z_document')
            ->where('firma', $fid)
            ->where('type', 'ZV')
            ->where('provodka', 0)
            ->orderByDesc('dt')
            ->orderByDesc('id')
            ->first(['id', 'num', 'reestr']);
    }

    private function availableSalaryStatements(int $fid, int $zpDocumentId)
    {
        $currentStatementId = (int) (
            DB::table('salary_statement_lines')
                ->where('zp_document_id', $zpDocumentId)
                ->where('project_id', $fid)
                ->value('statement_document_id')
            ?? 0
        );

        return DB::table('z_document')
            ->where('firma', $fid)
            ->where('type', 'ZV')
            ->where(function ($query) use ($currentStatementId) {
                $query->where('provodka', 0);

                if ($currentStatementId > 0) {
                    $query->orWhere('id', $currentStatementId);
                }
            })
            ->orderByDesc('dt')
            ->orderByDesc('id')
            ->get(['id', 'num', 'data', 'summa', 'reestr', 'provodka']);
    }

    private function unassignedSalaryDocuments(int $fid)
    {
        if (! Schema::hasTable('salary_statement_lines')) {
            return collect();
        }

        return DB::table('z_document as zp')
            ->leftJoin('salary_statement_lines as line', function ($join) use ($fid) {
                $join->on('line.zp_document_id', '=', 'zp.id')
                    ->where('line.project_id', '=', $fid);
            })
            ->leftJoin('z_document as statement', function ($join) use ($fid) {
                $join->on('statement.id', '=', 'line.statement_document_id')
                    ->where('statement.firma', '=', $fid)
                    ->where('statement.type', '=', 'ZV');
            })
            ->leftJoin('users as employee', 'employee.id', '=', 'zp.client1')
            ->where('zp.firma', $fid)
            ->where('zp.type', 'ZP')
            ->where(function ($query) {
                $query->whereNull('line.id')
                    ->orWhereNull('statement.id')
                    ->orWhereNull('zp.typez')
                    ->orWhere('zp.typez', '<>', 'ZV')
                    ->orWhereNull('zp.docid')
                    ->orWhere('zp.docid', 0)
                    ->orWhereColumn('zp.docid', '<>', 'line.statement_document_id');
            })
            ->orderByDesc('zp.dt')
            ->orderByDesc('zp.id')
            ->get([
                'zp.id',
                'zp.num',
                'zp.data',
                'zp.summa',
                'zp.provodka',
                'employee.name',
                'employee.secondname',
                'employee.fathername',
                'employee.orgname',
            ])
            ->map(function ($document) {
                $document->employee_name = trim(implode(' ', array_filter([
                    $document->secondname ?? '',
                    $document->name ?? '',
                    $document->fathername ?? '',
                ]))) ?: trim((string) ($document->orgname ?? '')) ?: 'Сотрудник не выбран';

                return $document;
            });
    }

    private function syncSalaryStatementAssignment(int $zpDocumentId, int $fid, ?int $statementId): void
    {
        abort_unless(Schema::hasTable('salary_statement_lines'), 503, 'Сначала выполните миграции базы данных.');

        $zpDocument = DB::table('z_document')
            ->where('id', $zpDocumentId)
            ->where('firma', $fid)
            ->where('type', 'ZP')
            ->lockForUpdate()
            ->first();
        abort_unless($zpDocument, 404);

        if ((int) ($zpDocument->provodka ?? 0) === 1) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'salary_statement_id' => 'Проведенный документ ZP нельзя переносить между ведомостями.',
            ]);
        }

        $existingLine = DB::table('salary_statement_lines')
            ->where('zp_document_id', $zpDocumentId)
            ->where('project_id', $fid)
            ->lockForUpdate()
            ->first();
        $oldStatementId = (int) ($existingLine->statement_document_id ?? 0);

        if ($statementId === null) {
            if ($existingLine) {
                DB::table('salary_statement_lines')->where('id', $existingLine->id)->delete();
            }
            DB::table('z_document')->where('id', $zpDocumentId)->update([
                'docid' => 0,
                'numz' => '0',
                'typez' => '',
            ]);

            if ($oldStatementId > 0) {
                $this->refreshSalaryStatementTotal($oldStatementId, $fid);
            }

            return;
        }

        $statement = DB::table('z_document')
            ->where('id', $statementId)
            ->where('firma', $fid)
            ->where('type', 'ZV')
            ->where('provodka', 0)
            ->lockForUpdate()
            ->first();
        if (! $statement) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'salary_statement_id' => 'Выберите непроведенную платежную ведомость текущего проекта.',
            ]);
        }

        $employeeId = (int) ($zpDocument->client1 ?? 0);
        if ($employeeId <= 0) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'client1' => 'Сначала выберите сотрудника.',
            ]);
        }

        $targetLine = DB::table('salary_statement_lines')
            ->where('statement_document_id', $statementId)
            ->where('project_id', $fid)
            ->where('employee_id', $employeeId)
            ->lockForUpdate()
            ->first();
        if ($targetLine?->zp_document_id && (int) $targetLine->zp_document_id !== $zpDocumentId) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'salary_statement_id' => 'В выбранной ведомости для этого сотрудника уже создан документ ZP.',
            ]);
        }

        if ($existingLine && (! $targetLine || (int) $existingLine->id !== (int) $targetLine->id)) {
            DB::table('salary_statement_lines')->where('id', $existingLine->id)->delete();
        }

        if ($targetLine) {
            DB::table('salary_statement_lines')->where('id', $targetLine->id)->update([
                'salary_amount' => round((float) $zpDocument->summa, 2),
                'zp_document_id' => $zpDocumentId,
                'updated_at' => now(),
            ]);
        } else {
            DB::table('salary_statement_lines')->insert([
                'statement_document_id' => $statementId,
                'employee_id' => $employeeId,
                'project_id' => $fid,
                'salary_amount' => round((float) $zpDocument->summa, 2),
                'zp_document_id' => $zpDocumentId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('z_document')->where('id', $zpDocumentId)->update([
            'docid' => $statementId,
            'numz' => (string) $statement->num,
            'typez' => 'ZV',
        ]);

        if ($oldStatementId > 0 && $oldStatementId !== $statementId) {
            $this->refreshSalaryStatementTotal($oldStatementId, $fid);
        }
        $this->refreshSalaryStatementTotal($statementId, $fid);
    }

    private function validateSalaryStatement(Request $request, int $fid): array
    {
        $validated = $request->validate([
            'data' => ['required', 'string', 'max:20'],
            'reestr' => ['required', 'integer'],
            'content' => ['nullable', 'string', 'max:65535'],
            'employees' => ['required', 'array', 'min:1'],
            'employees.*.employee_id' => ['required', 'integer', 'distinct'],
            'employees.*.salary_amount' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
        ]);
        $this->validateSalaryPaymentType((int) $validated['reestr']);

        $employeeIds = collect($validated['employees'])
            ->pluck('employee_id')
            ->map(fn ($employeeId) => (int) $employeeId)
            ->values();
        $validEmployeeIds = Schema::hasTable('team_memberships')
            ? DB::table('team_memberships')
                ->where('project_id', $fid)
                ->whereIn('user_id', $employeeIds)
                ->pluck('user_id')
                ->map(fn ($employeeId) => (int) $employeeId)
            : collect();

        if ($validEmployeeIds->count() !== $employeeIds->unique()->count()) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'employees' => 'В ведомости могут быть только сотрудники текущего проекта.',
            ]);
        }
        if (collect($validated['employees'])->sum('salary_amount') > 9999999999.99) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'employees' => 'Итоговая сумма ведомости слишком велика.',
            ]);
        }

        $validated['employees'] = collect($validated['employees'])
            ->map(fn ($employee) => [
                'employee_id' => (int) $employee['employee_id'],
                'salary_amount' => round((float) $employee['salary_amount'], 2),
            ])
            ->values()
            ->all();

        return $validated;
    }

    private function replaceSalaryStatementLines(int $statementId, int $fid, array $employees): void
    {
        $submittedIds = collect($employees)->pluck('employee_id')->map(fn ($employeeId) => (int) $employeeId);

        DB::table('salary_statement_lines')
            ->where('statement_document_id', $statementId)
            ->where('project_id', $fid)
            ->whereNull('zp_document_id')
            ->when($submittedIds->isNotEmpty(), fn ($query) => $query->whereNotIn('employee_id', $submittedIds))
            ->delete();

        foreach ($employees as $employee) {
            $existing = DB::table('salary_statement_lines')
                ->where('statement_document_id', $statementId)
                ->where('employee_id', $employee['employee_id'])
                ->first();
            if ($existing?->zp_document_id) {
                continue;
            }

            DB::table('salary_statement_lines')->updateOrInsert(
                [
                    'statement_document_id' => $statementId,
                    'employee_id' => $employee['employee_id'],
                ],
                [
                    'project_id' => $fid,
                    'salary_amount' => $employee['salary_amount'],
                    'updated_at' => now(),
                    'created_at' => $existing?->created_at ?? now(),
                ]
            );
        }
    }

    private function salaryStatementPayload(object $statement): array
    {
        $lines = DB::table('salary_statement_lines as l')
            ->join('users as u', 'u.id', '=', 'l.employee_id')
            ->leftJoin('z_document as zp', 'zp.id', '=', 'l.zp_document_id')
            ->where('l.statement_document_id', $statement->id)
            ->where('l.project_id', $statement->firma)
            ->orderBy('u.secondname')
            ->orderBy('u.name')
            ->orderBy('u.id')
            ->get([
                'l.id',
                'l.employee_id',
                'l.salary_amount',
                'l.zp_document_id',
                'u.name',
                'u.secondname',
                'u.fathername',
                'u.orgname',
                'u.email',
                'zp.num as zp_num',
                'zp.provodka as zp_posted',
                'zp.data as zp_date',
            ])
            ->map(function ($line) {
                $line->employee_name = trim(implode(' ', array_filter([
                    $line->secondname ?? '',
                    $line->name ?? '',
                    $line->fathername ?? '',
                ]))) ?: trim((string) ($line->orgname ?? '')) ?: ('Сотрудник #' . $line->employee_id);
                $line->salary_amount = round((float) $line->salary_amount, 2);
                $line->zp_url = $line->zp_document_id
                    ? route('document.show', [
                        'doc' => 'ZP',
                        'doc_id' => $line->zp_document_id,
                        'num' => $line->zp_num,
                        'year' => $this->documentYear((string) ($line->zp_date ?? '')),
                    ])
                    : null;

                return $line;
            })
            ->values();
        $linkedDocuments = DB::table('z_document as zp')
            ->leftJoin('salary_statement_lines as linked_line', function ($join) use ($statement) {
                $join->on('linked_line.zp_document_id', '=', 'zp.id')
                    ->where('linked_line.project_id', '=', $statement->firma);
            })
            ->leftJoin('users as employee', 'employee.id', '=', 'zp.client1')
            ->where('zp.firma', $statement->firma)
            ->where('zp.type', 'ZP')
            ->where(function ($query) use ($statement) {
                $query->where('linked_line.statement_document_id', $statement->id)
                    ->orWhere(function ($parentQuery) use ($statement) {
                        $parentQuery->where('zp.typez', 'ZV')
                            ->where('zp.docid', $statement->id);
                    });
            })
            ->orderBy('zp.data')
            ->orderBy('zp.num')
            ->get([
                'zp.id',
                'zp.num',
                'zp.data',
                'zp.summa',
                'zp.provodka',
                'employee.name',
                'employee.secondname',
                'employee.fathername',
                'employee.orgname',
            ])
            ->unique('id')
            ->map(function ($document) {
                $employeeName = trim(implode(' ', array_filter([
                    $document->secondname ?? '',
                    $document->name ?? '',
                    $document->fathername ?? '',
                ]))) ?: trim((string) ($document->orgname ?? '')) ?: 'Сотрудник не выбран';

                return [
                    'id' => (int) $document->id,
                    'num' => (string) $document->num,
                    'data' => (string) $document->data,
                    'summa' => round((float) $document->summa, 2),
                    'posted' => (int) $document->provodka === 1,
                    'employee_name' => $employeeName,
                    'url' => route('document.show', [
                        'doc' => 'ZP',
                        'doc_id' => $document->id,
                        'num' => $document->num,
                        'year' => $this->documentYear((string) ($document->data ?? '')),
                    ]),
                ];
            })
            ->values();

        return [
            'id' => (int) $statement->id,
            'num' => (string) $statement->num,
            'data' => (string) $statement->data,
            'reestr' => (string) ($statement->reestr ?? ''),
            'content' => (string) ($statement->content ?? ''),
            'summa' => round((float) $statement->summa, 2),
            'lines' => $lines,
            'zp_documents' => $linkedDocuments,
        ];
    }

    private function validateSalaryPayoutClassifiers(array $validated, int $fid): void
    {
        $cashboxExists = DB::table('conf')
            ->where('id', $validated['oplata'])
            ->where('type', 'oplata')
            ->where('firma', $fid)
            ->exists();
        if (! $cashboxExists) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'oplata' => 'Выберите кассу текущего проекта.',
            ]);
        }

        $this->validateSalaryPaymentType((int) $validated['reestr']);
    }

    private function validateSalaryPaymentType(int $paymentTypeId): void
    {
        $paymentType = DB::table('conf')
            ->where('id', $paymentTypeId)
            ->where('type', 'reestr')
            ->first();
        $paymentDocFlags = $paymentType ? ConfModel::paymentDocFlags($paymentType->doc ?? '') : [];
        if (! $paymentType || ($paymentDocFlags !== [] && ! in_array('ZP', $paymentDocFlags, true))) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'reestr' => 'Выберите вид платежа, доступный для ZP.',
            ]);
        }
    }

    private function refreshSalaryStatementTotal(int $statementId, int $fid): void
    {
        $total = DB::table('salary_statement_lines')
            ->where('statement_document_id', $statementId)
            ->where('project_id', $fid)
            ->sum('salary_amount');

        DB::table('z_document')
            ->where('id', $statementId)
            ->where('firma', $fid)
            ->where('type', 'ZV')
            ->update(['summa' => round((float) $total, 2)]);
    }

    private function documentYear(string $date): int
    {
        return preg_match('/(\d{4})$/', $date, $matches) ? (int) $matches[1] : (int) now()->year;
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

            if (in_array($doc, ['ZIN', 'ZOUT', 'CRDT'], true)) {
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
            $sumFromRequest = (float) $request->input(
                'sumPO',
                $request->input('sumRO', 0)
            );
            $parentDocument = null;

            if (!in_array($doc, ['ZIN', 'ZOUT', 'CRDT'], true) && $incomingParentDocId !== '0') {
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

            if (!$parentDocument && $parentDocid !== '0' && !in_array($doc, ['ZIN', 'ZOUT', 'CRDT'], true)) {
                $parentDocument = DB::table('document')
                    ->where('id', $parentDocid)
                    ->where('firma', $fid)
                    ->first();
            }

            $newSumma = 0.0;
            if (in_array($doc, ['PO', 'CPO', 'RO', 'CRO'], true)) {
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
                'docid' => in_array($doc, ['ZIN', 'ZOUT', 'CRDT'], true) ? 0 : $parentDocid,
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

            if (in_array($doc, ['ZIN', 'ZOUT', 'CRDT'], true)) {
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
                'parent_doc_id' => in_array($doc, ['ZIN', 'ZOUT', 'CRDT'], true) ? $newId : $parentDocid,
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
        $isOwnLineDocument = in_array($doc, ['ZIN', 'ZOUT', 'CRDT', 'WO1', 'SP'], true);
        $parentNumz = $isOwnLineDocument
            ? ($document->num ?: $document->numz)
            : ($document->numz ?: '0');
        $parentTypez = $isOwnLineDocument
            ? $doc
            : ($document->typez ?: '');
        $parentDocid = $isOwnLineDocument
            ? $docId
            : ($document->docid ?: $docId);
        $parentDocument = (!in_array($doc, ['ZIN', 'ZOUT', 'CRDT'], true) && $parentDocid)
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

        $docIdToFind = in_array($doc, ['ZIN', 'ZOUT', 'CRDT', 'RN', 'CPLAN', 'PN', 'WO1', 'SP'], true) ? $docId : $parentDocid;
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

        $isEducationProject = Schema::hasTable('project')
            && Schema::hasColumn('project', 'project_type')
            && strtolower(trim((string) Project::query()->whereKey((int) $fid)->value('project_type'))) === 'education';
        $educationCourseNames = collect();
        if ($isEducationProject && Schema::hasTable('education_topics')) {
            $educationCourseNames = EducationTopic::query()
                ->where('project_id', (int) $fid)
                ->whereIn('id', $lineItems->pluck('pnum')->filter()->map(fn ($id) => (int) $id))
                ->get()
                ->mapWithKeys(function (EducationTopic $course) use ($locale) {
                    $translations = is_array($course->title_translations ?? null) ? $course->title_translations : [];
                    $name = trim((string) ($translations[$locale] ?? '')) ?: trim((string) $course->title);

                    return [(string) $course->id => $name];
                });
        }

        $lineItems = $lineItems->map(function ($item) use ($pricingMeta, $locale, $educationCourseNames) {
            $meta = $pricingMeta[(string) $item->pnum] ?? [];
            $item->name = Field::localizedValue(
                $locale,
                $item->descript_name ?? '',
                $item->descript_name_ua ?? '',
                $item->descript_name_en ?? ''
            ) ?: (string) ($item->comp_name ?? '');
            if ($educationCourseNames->has((string) $item->pnum)) {
                $item->name = (string) $educationCourseNames->get((string) $item->pnum);
                $meta = [
                    'price_pay' => (float) ($item->pprice ?? 0),
                    'price_pay1' => 0,
                    'price_count' => 0,
                    'comp_pay' => (float) ($item->pprice ?? 0),
                    'comp_pay1' => 0,
                ];
            }
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
        $finishedProduct = null;
        if ($doc === 'SP' && trim((string) ($document->typeproduct ?? '')) !== '') {
            $finishedProduct = DB::table('comp')
                ->leftJoin('descript as d', function ($join) {
                    $join->on('d.pnum', '=', 'comp.id')
                        ->whereColumn('d.firma', '=', 'comp.firma');
                })
                ->where('comp.id', (string) $document->typeproduct)
                ->where('comp.firma', $fid)
                ->select(
                    'comp.id',
                    DB::raw("COALESCE(NULLIF(d.name, ''), NULLIF(d.name_ua, ''), NULLIF(d.name_en, ''), NULLIF(comp.nickname, ''), NULLIF(comp.namedoc, ''), NULLIF(comp.name, ''), CONCAT('Товар #', comp.id)) as name")
                )
                ->first();
        }

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
        $isLoanRoDocument = $documentRoutePrefix === 'bank.loanDocs' && $doc === 'CRO';

        // Load all oplata and reestr options for PO/RO dropdowns
        $oplataList = collect();
        $reestrList = collect();
        if (in_array($doc, ['PO', 'CPO', 'RO', 'CRO', 'ZP'], true)) {
            $oplataList = DB::table('conf')
                ->where('type', 'oplata')
                ->where('firma', $fid)
                ->when(in_array($doc, ['PO', 'CPO', 'RO', 'CRO'], true), function ($query) {
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
            $paymentDocType = match ($doc) {
                'CPO' => 'PO',
                'CRO' => 'RO',
                default => $doc,
            };
            $reestrList = ConfModel::paymentTypesForDocument($fid, $paymentDocType);
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
        $clientStatuses = DB::table('conf')->where('type', 'tclient')->where('firma', 0)->orderBy('name')->get();
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

        $isLoanRequestDocument = $documentRoutePrefix === 'bank.loanDocs' && $doc === 'CRDT';
        $isLoanIssueDocument = $documentRoutePrefix === 'bank.loanDocs' && $doc === 'CPLAN';
        $loanRoUrl = null;
        $loanRoIsIssued = false;
        $loanMeta = [];
        $loanCollateralOptions = collect(['Автомобиль', 'Спецтехника', 'Госномер']);
        $loanRepaymentSchedule = null;
        $loanRootDocument = $isLoanRequestDocument
            ? $document
            : (($documentRoutePrefix === 'bank.loanDocs' && ($parentDocument->type ?? '') === 'CRDT')
                ? $parentDocument
                : null);

        if ($loanRootDocument) {
            $postedLoanRo = DB::table('z_document')
                ->where('docid', (string) $loanRootDocument->id)
                ->where('firma', $fid)
                ->where('type', 'CRO')
                ->where('provodka', 1)
                ->orderByDesc('id')
                ->first();
            $loanRoIsIssued = $postedLoanRo !== null;
            $loanRoUrl = $postedLoanRo
                ? route('bank.loanDocs.show', [
                    'doc' => 'CRO',
                    'doc_id' => (int) $postedLoanRo->id,
                    'parent_doc_id' => (int) $loanRootDocument->id,
                    'num' => $postedLoanRo->num,
                    'year' => strlen((string) ($postedLoanRo->data ?? '')) >= 10
                        ? substr((string) $postedLoanRo->data, 6, 4)
                        : $year,
                ])
                : ($isLoanRequestDocument ? route('bank.loanDocs.show', [
                    'doc' => 'CRO',
                    'doc_id' => 0,
                    'parent_doc_id' => (int) $loanRootDocument->id,
                    'num' => 0,
                    'year' => strlen((string) ($loanRootDocument->data ?? '')) >= 10
                        ? substr((string) $loanRootDocument->data, 6, 4)
                        : $year,
                    'sumPO' => (float) ($loanRootDocument->summa ?? 0),
                ]) : null);
        }

        if ($isLoanRequestDocument) {
            $loanMeta = $this->parseLoanRequestContent((string) ($document->content ?? ''));
            $loanMeta['loan_amount'] = $loanMeta['loan_amount'] !== '' ? $loanMeta['loan_amount'] : (string) ($document->summa ?? '');
            $loanMeta['ltv'] = $loanMeta['ltv'] !== '' ? $loanMeta['ltv'] : (string) ($document->reteil ?? '70');
            $loanCollateralOptions = $loanCollateralOptions
                ->merge(
                    DB::table('document')
                        ->where('type', 'CRDT')
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
        if ($isLoanIssueDocument && $parentDocument && ($parentDocument->type ?? '') === 'CRDT') {
            $loanMeta = $this->parseLoanRequestContent((string) ($parentDocument->content ?? ''));
            $loanRepaymentSchedule = $this->loanRepaymentSchedule($parentDocument, $loanMeta);
        }

        // Related documents (legacy client_info / client_info1)
        $clientId = $document->client1 ?? 0;
        $numz = $parentNumz;
        $typez = $parentTypez;
        $docid = $parentDocid;
        $relatedDocTotal = (float) (
            in_array($doc, ['ZIN', 'ZOUT', 'CRDT'], true)
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
            in_array($doc, ['ZIN', 'ZOUT', 'CRDT'], true)
                ? ($document->provodka ?? 0)
                : ($parentDocument->provodka ?? 0)
        ) === 1;

        // Show related docs for root orders/purchases and their child documents
        $isZakazType = in_array($typez, ['ZIN', 'ZOUT', 'CRDT'], true);

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
        if (in_array($doc, ['ZIN', 'ZOUT', 'CRDT'], true) && $clientId > 0) {
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
        $salaryStatements = collect();
        $selectedSalaryStatementId = '';
        if ($doc === 'ZP' && Schema::hasTable('salary_statement_lines')) {
            $salaryStatements = $this->availableSalaryStatements((int) $fid, (int) $document->id);
            $selectedSalaryStatementId = (string) (
                DB::table('salary_statement_lines')
                    ->where('zp_document_id', $document->id)
                    ->where('project_id', $fid)
                    ->value('statement_document_id')
                ?? (
                    (string) ($document->typez ?? '') === 'ZV'
                        ? (int) ($document->docid ?? 0)
                        : ''
                )
            );
        }

        return view('document.show', compact(
            'document', 'lineItems', 'doc', 'year', 'client', 'confMap',
            'fid', 'relatedDocs', 'relatedIcons', 'oplataList', 'reestrList', 'statusList', 'skladsList',
            'documentIndexUrl', 'parentDocumentUrl', 'parentDocument', 'myCompanies', 'clientStatuses', 'clientGroups',
            'mappingTargetProjectId', 'documentRoutePrefix', 'loanMeta', 'loanCollateralOptions', 'loanRepaymentSchedule',
            'loanRoUrl', 'loanRoIsIssued', 'isEducationProject', 'finishedProduct',
            'salaryStatements', 'selectedSalaryStatementId'
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

            $summaPO = in_array($docType, ['PO', 'RO', 'ZP'], true)
                ? (float) $request->input('sumPO', $request->input('sumRO', 0))
                : 0.0;
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
            if ($docType === 'ZP') {
                $defaultSalaryStatement = $this->defaultSalaryStatement((int) $fid);
                if ($defaultSalaryStatement) {
                    $numz = (string) $defaultSalaryStatement->num;
                    $typez = 'ZV';
                    $docid = (string) $defaultSalaryStatement->id;
                    $reestr = (string) ($defaultSalaryStatement->reestr ?? $reestr);
                } else {
                    $numz = '0';
                    $typez = '';
                    $docid = '0';
                }
            }

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

            if (in_array($docType, ['ZIN', 'ZOUT', 'WO1', 'SP'], true)) {
                DB::table($table)->where('id', $id)->update(['docid' => $id, 'numz' => $num, 'typez' => $docType]);
                session(['docid' => $id, 'numz' => $num, 'typez' => $docType]);
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
            if (!in_array($doc, ['RA', 'CDOC', 'WO1', 'SP'], true) && trim((string) $request->input('client1', '')) === '') {
                $errors['client1'] = 'Оберіть клієнта';
            }

            if ($doc === 'ZP' && ! isset($errors['client1'])) {
                $employeeId = trim((string) $request->input('client1', ''));
                $employeeFirmaScope = HoldingScope::projectIdsFor($fid);
                $employeeExists = Schema::hasTable('team_memberships') && DB::table('team_memberships')
                    ->where('user_id', $employeeId)
                    ->whereIn('project_id', $employeeFirmaScope)
                    ->exists();

                if (! $employeeExists) {
                    $errors['client1'] = 'Оберіть співробітника поточного холдингу';
                }
            }

            if (in_array($doc, ['ZOUT', 'ZIN', 'CRDT'], true) && trim((string) $request->input('status', '')) === '') {
                $errors['status'] = 'Оберіть статус';
            }

            if (in_array($doc, ['PO', 'CPO', 'RO', 'CRO', 'ZP'], true)) {
                if (trim((string) $request->input('oplata', '')) === '') {
                    $errors['oplata'] = 'Оберіть касу';
                }
                if (trim((string) $request->input('reestr', '')) === '') {
                    $errors['reestr'] = 'Оберіть вид платежу';
                }
            }

            if (in_array($doc, ['PN', 'RN', 'WO1', 'SP'], true) && trim((string) $request->input('sklads', '')) === '') {
                $errors['sklads'] = 'Оберіть склад';
            }

            if ($doc === 'SP' && trim((string) $request->input('typeproduct', '')) === '') {
                $errors['typeproduct'] = 'Оберіть готову продукцію';
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

            if ($this->documentRoutePrefix() === 'bank.loanDocs' && $doc === 'CRDT') {
                return $this->saveLoanRequestDocument($request, $docId, $fid);
            }
            
            \Illuminate\Support\Facades\Log::info('Document save started', [
                'doc' => $doc,
                'docId' => $docId,
                'fid' => $fid,
                'run' => $run,
            ]);
            
            try {
                $conductableDocs = ['RN', 'PN', 'PO', 'CPO', 'RO', 'CRO', 'ZP', 'VN', 'AO', 'WO1'];
                $currentPosted = false;
                $desiredPosted = $request->boolean('post_after_save');
                $wasSmsFlagEnabled = false;

                if ($doc === 'ZOUT') {
                    if ($request->boolean('sms_flag') && ! $this->canAttemptOrderTtnSms($request)) {
                        $request->merge(['sms_flag' => '0']);
                    }

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

                        $result = Document::provodka($docId, $doc, $fid);
                        $this->syncLinkedDocumentPostingState($doc, $docId, $fid, (bool) ($result['isPosted'] ?? false));

                        return redirect()->back()->with('success', 'Проводку скасовано');
                    }
                }

                if ($doc === 'ZP') {
                    DB::transaction(function () use ($request, $docId, $doc, $fid): void {
                        $this->docService->saveHead($request, $docId, $doc, $fid);
                        $this->syncSalaryStatementAssignment(
                            (int) $docId,
                            (int) $fid,
                            $request->filled('salary_statement_id')
                                ? (int) $request->input('salary_statement_id')
                                : null
                        );
                    });
                } else {
                    $this->docService->saveHead($request, $docId, $doc, $fid);
                    $this->docService->saveBody($request, $docId, $doc, $fid);
                }
                $this->ensureSourceParentOrder($doc, $docId, $fid);
                $this->syncProjectMirrorDocuments($doc, $docId, $fid);

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
                        $this->syncLinkedDocumentPostingState($doc, $docId, $fid, (bool) ($result['isPosted'] ?? false));
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
            } catch (\Illuminate\Validation\ValidationException $e) {
                return redirect()->back()->withErrors($e->errors())->withInput();
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

    private function canAttemptOrderTtnSms(Request $request): bool
    {
        return trim((string) $request->input('ttn', '')) !== ''
            && (int) $request->input('client1', 0) > 0;
    }

    // ── Provodka ──────────────────────────────────────────────────────────────

    public function provodka(Request $request)
    {
        $docId = $request->input('doc_id', session('doc_id', '0'));
        $doc = $request->input('doc', session('doc', ''));
        $fid = (string) session('fid', '');
        try {
            $this->ensureSourceParentOrder($doc, (string) $docId, $fid);
            $this->syncProjectMirrorDocuments($doc, (string) $docId, $fid);
            $result = Document::provodka($docId, $doc, $fid);
            $this->syncLinkedDocumentPostingState($doc, (string) $docId, $fid, (bool) ($result['isPosted'] ?? false));
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
        $documentRoutePrefix = $this->documentRoutePrefix();

        // Delete related z_body rows (goods) first
        $document = DB::table($table)->where('id', $docId)->where('firma', $fid)->first();
        $loanParentDocument = null;
        if ($document) {
            if ((int) ($document->provodka ?? 0) === 1) {
                return redirect()->back()->with('error', 'Проведений документ видаляти не можна. Спочатку зніміть проводку.');
            }
            if ($this->isRootDocumentLocked($doc, (string) $docId, $fid)) {
                return redirect()->back()->with('error', 'Проведений документ видаляти не можна. Спочатку зніміть проводку з пов’язаних документів.');
            }
            if ($doc === 'ZV' && Schema::hasTable('salary_statement_lines')) {
                $hasPayouts = DB::table('salary_statement_lines')
                    ->where('statement_document_id', $docId)
                    ->whereNotNull('zp_document_id')
                    ->exists();
                if ($hasPayouts) {
                    return redirect()->back()->with('error', 'Нельзя удалить ведомость, пока в ней есть документы ZP.');
                }
                DB::table('salary_statement_lines')->where('statement_document_id', $docId)->delete();
            }
            if ($doc === 'ZP' && Schema::hasTable('salary_statement_lines')) {
                DB::table('salary_statement_lines')
                    ->where('zp_document_id', $docId)
                    ->update(['zp_document_id' => null, 'updated_at' => now()]);
            }
            if ($documentRoutePrefix === 'bank.loanDocs' && $doc !== 'CRDT') {
                $parentDocumentId = (int) ($document->docid ?? 0);
                if ($parentDocumentId > 0) {
                    $loanParentDocument = DB::table('document')
                        ->where('id', $parentDocumentId)
                        ->where('firma', $fid)
                        ->where('type', 'CRDT')
                        ->first();
                }
            }
            if ($doc !== 'ZV') {
                $docIdToFind = in_array($doc, ['ZIN', 'ZOUT', 'CRDT', 'RN', 'CPLAN', 'PN'], true) ? $docId : ($document->docid ?? $docId);
                ZBody::where('docid', $docIdToFind)->delete();
            }
        }

        DB::table($table)->where('id', $docId)->where('firma', $fid)->delete();
        session(['num' => '0', 'doc_id' => '0']);

        if ($documentRoutePrefix === 'bank.loanDocs') {
            if ($doc === 'CRDT' || ! $loanParentDocument) {
                return redirect()->route('bank.loanDocs.index');
            }

            return redirect()->route('bank.loanDocs.show', [
                'doc' => 'CRDT',
                'doc_id' => (int) $loanParentDocument->id,
                'parent_doc_id' => (int) $loanParentDocument->id,
                'num' => $loanParentDocument->num,
                'year' => strlen((string) ($loanParentDocument->data ?? '')) >= 10
                    ? substr((string) $loanParentDocument->data, 6, 4)
                    : date('Y'),
            ]);
        }

        return redirect()->route($documentRoutePrefix . '.index', ['doc' => $doc]);
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
        $docid = in_array($doc, ['RN', 'PN', 'WO1', 'SP'], true) ? session('doc_id', '0') : session('docid', '0');
        $typez = in_array($doc, ['RN', 'PN', 'WO1', 'SP'], true) ? $doc : session('typez', '');
        $numz = session('numz', '0');

        if ($this->isRootDocumentLocked($doc, (string) $docid, $fid)) {
            return redirect()->back()->with('error', 'Проведений документ змінювати не можна. Спочатку зніміть проводку з пов’язаних документів.');
        }

        $pnum = $request->input('pnum', '');
        $pid = $request->input('pid', '');
        $pprice = $request->input('pprice', '0');
        $psumma = $request->input('psumma', '0');
        $pcount = $request->input('pcount', '1');

        $docTypes = ['CH', 'PN', 'RN', 'VN', 'WO1', 'SP', 'AO', 'ZOUT', 'ZIN'];

        if (in_array($doc, $docTypes, true)) {
            ZBody::addOrIncrement($typez, $numz, $pnum, $fid, $docid, $pid, $pprice, $psumma);
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
            ->where('type', 'CRDT')
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
            'doc' => 'CRDT',
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
            ->where('type', 'CPO')
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
