<?php

namespace App\Models;

use App\Services\AccountingService;
use App\Services\InventoryCostService;
use App\Support\HoldingScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class Document extends Model
{
    protected $table = 'document';
    public $timestamps = false;
    protected $guarded = [];


    public function firmaObj()
    {
        return $this->belongsTo(Firma::class , 'firma');
    }
    public static function tableForType($doc)
    {
        return match ($doc) {
                'ZIN', 'ZOUT' => 'document',
                default => 'z_document',
            };
    }

    /**
     * Human-readable Ukrainian name for a document type code.
     */
    public static function typeName($doc): string
    {
        $key = strtolower((string) $doc);
        $translated = __("document.doctypes.{$key}");

        return $translated === "document.doctypes.{$key}" ? (string) $doc : $translated;
    }

    public function scopeFilter($query, $filters)
    {
        return $query->when($filters['year'] ?? null, function ($q) use ($filters) {
            $q->whereYear('data', $filters['year']);
        });
    }

    /**
     * Fetch document list rows + confMap from DB.
     *
     * @return array{rows: array, total: int, confMap: array}
     */
    public static function init($doc, $pos, $fd, $fid, $login, $status, $idsklad, $idkassa)
    {
        $table = self::tableForType($doc);
        $hasUserF = !empty($fd['userSql']) || !empty($fd['fName']);
        $status = (int)$status ?? 0;

        $userFilter = !empty($fd['userSql']) ? "AND ( {$fd['userSql']} )" : '';
        $docFilter = !empty($fd['docSql']) ? $fd['docSql'] : '';


        if ($hasUserF) {
            $base = "FROM {$table} d JOIN users u ON u.id = d.client1 ";
            $base .= "WHERE d.firma = ? AND d.type = ? {$userFilter} {$docFilter}";
            $bp = [$fid, "{$doc}", ...$fd['params']];
        }
        else {
            $base = "FROM {$table} d JOIN users u ON u.id = d.client1 ";
            $base .= "WHERE d.firma = ? AND d.type LIKE ? {$docFilter}";
            $bp = [$fid, "%{$doc}%", ...$fd['params']];
        }

        $total = DB::selectOne("SELECT COUNT(*) AS n {$base}", $bp)->n;

        $cols = "d.id, d.num, d.client1, d.time, d.data, d.data2, d.type,
                 d.summa, d.bonus, d.status, d.content, d.ttn,
                 d.sklads, d.reteil, d.oplata, d.reestr, d.docum,
                 d.manager, d.provodka, d.money, d.numz, d.typez, d.client2,
                 u.orgname, u.kod1, u.secondname, u.name, u.fathername,
                 u.name2, u.region, u.city, u.poshta, u.phone, u.top";

        $sort = 'ORDER BY d.dt DESC, d.time DESC, d.num DESC';
        $pos = (int) $pos;
        $rows = DB::select("SELECT {$cols} {$base} {$sort} LIMIT {$pos}, 30", $bp);
        //.dd("SQL: SELECT * {$base} {$sort} LIMIT ?, ? ", "PARAMS:", $bp, "LIMIT: ", $pos, 30);
        // Batch-load conf (status, money, sklads, reteil) to avoid N+1
        $confIds = [];
        foreach ($rows as $r) {
            if ($r->status)
                $confIds[] = $r->status;
            if ($r->money)
                $confIds[] = $r->money;
            if ($r->sklads)
                $confIds[] = $r->sklads;
            if ($r->reteil)
                $confIds[] = $r->reteil;
            if ($r->reestr)
                $confIds[] = $r->reestr;
        }
        $confMap = [];
        if (!empty($confIds)) {
            $confMap = DB::table('conf')
                ->whereIn('id', array_unique($confIds))
                ->get(['id', 'name', 'color', 'status'])
                ->keyBy('id')->toArray();
        }

        return [
            'rows' => $rows,
            'total' => $total,
            'confMap' => $confMap,
        ];
    }

    public static function showDocumentList($rows, $confMap, $doc)
    {
        $data = [];
        $total_sum = 0;

        // Batch-load child document posting states — one query for the whole page (avoids N+1)
        $childStatuses = [];
        if ($doc === 'ZOUT' && !empty($rows)) {
            $docIds = array_map(fn($r) => $r->id, (array)$rows);
            $childRows = DB::table('z_document')
                ->whereIn('docid', $docIds)
                ->selectRaw('docid, type, MAX(provodka) as has_posted, COUNT(*) as cnt')
                ->groupBy('docid', 'type')
                ->get();
            foreach ($childRows as $cr) {
                $childStatuses[$cr->docid][$cr->type] = [
                    'posted' => (int)$cr->has_posted === 1,
                    'cnt'    => (int)$cr->cnt,
                ];
            }
        }

        foreach ($rows as $row) {
            $statusId = $row->status ?? '';
            $conf = $confMap[$statusId] ?? null;
            $statusName = $conf ? h($conf->name) : '';
            $color = h($conf->color ?? '');

            $summa = (float)$row->summa;
            $total_sum += $summa;
            $summaFmt = number_format($summa, 2, ',', "'");

            $year = strlen((string)($row->data ?? '')) >= 10 ? substr((string)$row->data, 6, 4) : date('Y');

            $content = h($row->content ?? '');
            if ($row->ttn) {
                $content .= '<br>НП:' . h($row->ttn);
            }

            $orgname = h($row->orgname ?? '');
            $kod1 = h($row->kod1 ?? '');
            $org = $orgname ? "{$orgname}, {$kod1}" : '';

            $fullName = h(trim(
                ($row->secondname ?? '') . ' '
                . ($row->name ?? '') . ' '
                . ($row->fathername ?? '')
            ));

            $city = h($row->city ?? '');
            $poshta = $row->poshta ? 'НП ' . h($row->poshta) : '';
            $phone = h(formatPhone((string)($row->phone ?? '')));
            $manager = h(strtolower($row->manager ?? ''));

            $signal = ($statusName === '' && $doc === 'ZOUT') ? "<span class='alink3'>new</span>" : '';

            // ── Signal icons ─────────────────────────────────────────────────
            $statusIcons = [];

            // Child-document state badges (ZOUT only — one batch query above)
            if ($doc === 'ZOUT') {
                $ch = $childStatuses[$row->id] ?? [];

                if (isset($ch['WO1'])) {
                    $p = $ch['WO1']['posted'];
                    $statusIcons[] = '<span class="signal-badge' . ($p ? ' signal-badge--ok' : '') . '" title="В роботі">🔧</span>';
                }
                if (isset($ch['RN'])) {
                    $p = $ch['RN']['posted'];
                    $statusIcons[] = '<span class="signal-badge' . ($p ? ' signal-badge--ok' : '') . '" title="Відвантаження">🚚</span>';
                }
                if (isset($ch['CH'])) {
                    $statusIcons[] = '<span class="signal-badge signal-badge--ok" title="Рахунок">📄</span>';
                }
                if (isset($ch['PO'])) {
                    $p = $ch['PO']['posted'];
                    $statusIcons[] = '<span class="signal-badge' . ($p ? ' signal-badge--ok' : '') . '" title="Оплата">💰</span>';
                }
            }

            // Conf-based status icons (existing logic — status=2 delivery, status=3 payment)
            if ($conf) {
                $confStatus = (int)($conf->status ?? 0);
                if ($confStatus == 2) {
                    $statusIcons[] = '<img src="' . asset('img/icon-truck.png') . '" alt="Доставка" title="Доставка" class="status-icon status-icon-truck">';
                }
                if ($confStatus == 3) {
                    $statusIcons[] = '<img src="' . asset('img/icon-coins.png') . '" alt="Оплата" title="Оплата" class="status-icon status-icon-payment">';
                }
            }
            // TTN tracking number → truck icon (if not already shown via conf)
            $hasTruckFromConf = $conf && (int)($conf->status ?? 0) === 2;
            if (!empty($row->ttn) && !$hasTruckFromConf) {
                $statusIcons[] = '<img src="' . asset('img/icon-truck.png') . '" alt="НП" title="Нова пошта" class="status-icon status-icon-truck">';
            }
            $signalIcons = implode('', $statusIcons);

            // sklads name (used in index view)
            $skladsConf = $confMap[$row->sklads ?? ''] ?? null;
            $skladsName = $skladsConf ? h($skladsConf->name) : '';

            // top rating (used in index view)
            $top = (int)($row->top ?? 0);
            $topImg = $top >= 5 ? "⭐" : "[{$top}]";

            $linkUrl = route('document.show', [
                'doc_id' => $row->id, 'num' => $row->num, 'year' => $year, 'doc' => $doc,
            ]);

            $data[] = [
                'id' => $row->id,
                'num' => h($row->num),
                'linkUrl' => $linkUrl,
                'data' => h($row->data),
                'time' => h($row->time),
                'org' => $org,
                'fullName' => $fullName,
                'city' => $city,
                'poshta' => $poshta,
                'phone' => $phone,
                'color' => $color,
                'statusName' => $statusName,
                'signal' => $signal,
                'signalIcons' => $signalIcons,
                'summaFmt' => $summaFmt,
                'content' => $content,
                'manager' => $manager,
                'skladsName' => $skladsName,
                'moneyName' => $confMap[$row->money ?? '']->name ?? '',
                'reestrName' => $confMap[$row->reestr ?? '']->name ?? '',
                'topImg' => $topImg,
            ];
        }

        return [
            'items' => $data,
            'total_sum' => $total_sum,
        ];
    }

    public static function saldo($id, $fid)
    {
        $zout = DB::table('document')
            ->where('client1', $id)->where('firma', $fid)->where('type', 'ZOUT')
            ->sum('summa');
        $paid = DB::table('z_document')
            ->where('client1', $id)->where('firma', $fid)->where('type', 'PO')
            ->where('provodka', 1)->sum('summa');
        $salary = DB::table('z_document')
            ->where('client1', $id)->where('firma', $fid)->where('type', 'ZP')
            ->where('provodka', 1)->sum('summa');

        return [
            'debt' => (float)$zout,
            'paid' => (float)$paid + (float)$salary,
            'balance' => (float)$paid + (float)$salary - (float)$zout,
        ];
    }

    /**
     * Get the next sequence number for a document type.
     * Field 'data' is typically saved as 'd-m-Y'.
     *
     * @param string $docType
     * @param string $fid
     * @param string|int $year
     * @return int
     */
    public static function nextNum($docType, $fid, $year)
    {
        $table = self::tableForType($docType);

        // Find max numeric 'num' for this type/firma/year
        $maxNum = DB::table($table)
            ->where('type', $docType)
            ->where('firma', $fid)
            ->where('data', 'like', '%' . $year)
            ->max(DB::raw('CAST(num AS UNSIGNED)'));

        return $maxNum ? (int) $maxNum + 1 : 1;
    }

    /**
     * Auto-assign a sequential number for a new document.
     * Called when creating a document with id=0 (new).
     * Returns max(num) + 1 for the given type/firma/year.
     */
    public static function assignNextNum($docType, $fid, $year)
    {
        $table = self::tableForType($docType);

        // Get all nums for this type/firma/year, find the true max
        $nums = DB::table($table)
            ->where('type', $docType)
            ->where('firma', $fid)
            ->where('data', 'like', '%' . $year)
            ->pluck('num');

        $maxNum = 0;
        foreach ($nums as $n) {
            $val = (int) filter_var($n, FILTER_VALIDATE_INT) ?: 0;
            if ($val > $maxNum) {
                $maxNum = $val;
            }
        }

        return $maxNum + 1;
    }

    public static function provodka(string $docId, string $docType, string $fid): array
    {
        $table = self::tableForType($docType);
        DB::beginTransaction();
        try {
            $doc = DB::table($table)
                ->where('id', $docId)
                ->where('firma', $fid)
                ->where('type', $docType)
                ->lockForUpdate()
                ->first();

            if (!$doc) {
                DB::rollBack();

                return [
                    'isPosted' => false,
                    'document' => null,
                ];
            }

            $lineDocId = in_array($docType, ['ZIN', 'ZOUT', 'RN', 'PN'], true)
                ? $docId
                : (string) ($doc->docid ?: $docId);
            $lineItems = ZBody::where('docid', $lineDocId)
                ->where('firma', $fid)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();
            $summa = (float) $doc->summa;
            $oplata = (string) $doc->oplata;
            $client1 = (string) $doc->client1;
            $numz = (string) $doc->numz;
            $typez = (string) $doc->typez;
            $parentDocId = (int) ($doc->docid ?? 0);
            $wasPosted = (int) ($doc->provodka ?? 0) === 1;
            $direction = $wasPosted ? -1 : 1;

            foreach ($lineItems as $item) {
                $pnum = $item->pnum;
                $count = (float) $item->pcount;

                $priceQuery = DB::table('price')
                    ->where('pnum', $pnum)
                    ->where('firma', $fid);

                match ($docType) {
                    'ZOUT' => self::applyColumnDelta(clone $priceQuery, 'reserved', $direction * $count),
                    default => null,
                };
            }

            $inventoryMovements = collect();
            if (in_array($docType, ['PN', 'RN'], true)) {
                $inventoryService = app(InventoryCostService::class);
                $inventoryMovements = $wasPosted
                    ? $inventoryService->reverse($doc, $fid)
                    : $inventoryService->post($doc, $lineItems, $fid);
            }

            if (in_array($docType, ['PO', 'RO', 'PP'], true)) {
                $sign = $docType === 'RO' ? -1 : 1;
                $kasId = $oplata;
                $confColumns = Schema::getColumnListing('conf');
                $delta = $sign * $summa * $direction;

                if (trim($kasId) === '') {
                    throw new \RuntimeException('Для проводки грошового документа потрібно вибрати касу.');
                }

                if (in_array($docType, ['PO', 'RO'], true) && in_array('value', $confColumns, true)) {
                    $currentValue = (float) DB::table('conf')
                        ->where('id', $kasId)
                        ->where('type', 'oplata')
                        ->where('firma', $fid)
                        ->value('value');

                    DB::table('conf')
                        ->where('id', $kasId)
                        ->where('type', 'oplata')
                        ->where('firma', $fid)
                        ->update(['value' => $currentValue + $delta]);
                } else {
                    self::applyColumnDelta(
                        DB::table('kassa')->where('id', $kasId),
                        'balance',
                        $delta
                    );
                }
            }

            if ($docType === 'ZP') {
                $kasId = $oplata;
                $confColumns = Schema::getColumnListing('conf');
                $currency = self::currencyForCashbox($kasId, $fid, $confColumns);
                $cashDelta = -1 * $summa * $direction;

                if (trim($kasId) === '') {
                    throw new \RuntimeException('Для проводки зарплати потрібно вибрати касу.');
                }

                if (in_array('value', $confColumns, true)) {
                    $currentValue = (float) DB::table('conf')
                        ->where('id', $kasId)
                        ->where('type', 'oplata')
                        ->where('firma', $fid)
                        ->value('value');

                    DB::table('conf')
                        ->where('id', $kasId)
                        ->where('type', 'oplata')
                        ->where('firma', $fid)
                        ->update(['value' => $currentValue + $cashDelta]);
                } else {
                    self::applyColumnDelta(
                        DB::table('kassa')->where('id', $kasId),
                        'balance',
                        $cashDelta
                    );
                }

                if ($client1 !== '' && $client1 !== '0') {
                    self::applyUserBalanceDelta($client1, $fid, $summa * $direction, $currency);
                }
            }

            $accountingService = app(AccountingService::class);
            $ledgerTransaction = $accountingService->createDocumentTransaction(
                "{$table}:{$docType}",
                $docId,
                $docType,
                $doc,
                $lineItems,
                $fid,
                $wasPosted
            );

            if (in_array($docType, ['PN', 'RN', 'PO', 'RO'], true) && $ledgerTransaction === null) {
                throw new \RuntimeException(
                    "Бухгалтерський регістр недоступний: {$docType} не може бути проведений без подвійного запису."
                );
            }

            $mirrorLedgerTransaction = null;
            if (in_array($docType, ['PN', 'RN', 'PO', 'RO'], true)) {
                $mirrorLedgerTransaction = $accountingService->createProjectMirrorTransaction(
                    "{$table}:{$docType}",
                    $docId,
                    $docType,
                    $doc,
                    $lineItems,
                    $fid,
                    $wasPosted
                );
            }

            if (! $wasPosted && in_array($docType, ['PN', 'RN'], true)) {
                $inventoryService->attachLedgerTransaction(
                    $inventoryMovements,
                    $ledgerTransaction?->id
                );

                $mirrorInventoryMovements = $inventoryService->postProjectMirror($doc, $lineItems, $fid);
                $inventoryService->attachLedgerTransaction(
                    $mirrorInventoryMovements,
                    $mirrorLedgerTransaction?->id
                );
            }

            if ($wasPosted && in_array($docType, ['PN', 'RN'], true)) {
                $inventoryService->reverseProjectMirror($doc, $fid);
            }

            DB::table($table)->where('id', $docId)->update(['provodka' => $wasPosted ? 0 : 1]);

            self::refreshLinkedOrderCloseState($docType, $typez, $numz, $parentDocId, $fid);
            self::refreshLinkedOrderPostingState($docType, $typez, $numz, $parentDocId, $fid);
            if ($client1 !== '') {
                self::updateCache($client1, $fid);
            }

            DB::commit();

            return [
                'isPosted' => !$wasPosted,
                'document' => DB::table($table)->where('id', $docId)->first(),
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Provodka failed', ['docId' => $docId, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    private static function refreshLinkedOrderCloseState(string $docType, string $typez, string $numz, int $parentDocId, string $fid): void
    {
        if ($docType !== 'PO') {
            return;
        }

        if ($parentDocId <= 0 && $typez !== 'ZOUT') {
            return;
        }

        $zout = $parentDocId > 0
            ? DB::table('document')
                ->where('id', $parentDocId)
                ->where('type', 'ZOUT')
                ->where('firma', $fid)
                ->first()
            : null;

        if (!$zout && $numz !== '0') {
            $zout = DB::table('document')
                ->where('num', $numz)
                ->where('type', 'ZOUT')
                ->where('firma', $fid)
                ->first();
        }

        if (!$zout) {
            return;
        }

        $paidQuery = DB::table('z_document')
            ->where('type', 'PO')
            ->where('firma', $fid)
            ->where('provodka', 1);

        if ($parentDocId > 0) {
            $paidQuery->where('docid', $zout->id);
        } else {
            $paidQuery
                ->where('numz', $numz)
                ->where('typez', 'ZOUT');
        }

        $paid = (float) $paidQuery->sum('summa');

        DB::table('document')
            ->where('id', $zout->id)
            ->update(['close' => $paid >= (float) $zout->summa ? 1 : 0]);
    }

    private static function refreshLinkedOrderPostingState(string $docType, string $typez, string $numz, int $parentDocId, string $fid): void
    {
        if (!in_array($docType, ['RN', 'PO'], true)) {
            return;
        }

        if ($parentDocId <= 0 && $typez !== 'ZOUT') {
            return;
        }

        $zout = $parentDocId > 0
            ? DB::table('document')
                ->where('id', $parentDocId)
                ->where('type', 'ZOUT')
                ->where('firma', $fid)
                ->first()
            : null;

        if (!$zout && $numz !== '0') {
            $zout = DB::table('document')
                ->where('num', $numz)
                ->where('type', 'ZOUT')
                ->where('firma', $fid)
                ->first();
        }

        if (!$zout) {
            return;
        }

        $postedChildrenBase = DB::table('z_document')
            ->where('firma', $fid)
            ->where('provodka', 1);

        if ($parentDocId > 0) {
            $postedChildrenBase->where('docid', $zout->id);
        } else {
            $postedChildrenBase
                ->where('numz', $numz)
                ->where('typez', 'ZOUT');
        }

        $hasPostedRn = (clone $postedChildrenBase)
            ->where('type', 'RN')
            ->exists();

        $hasPostedPo = (clone $postedChildrenBase)
            ->where('type', 'PO')
            ->exists();

        if ($hasPostedRn || $hasPostedPo) {
            DB::table('document')
                ->where('id', $zout->id)
                ->update(['provodka' => 1]);
            return;
        }

        if (!$hasPostedRn && !$hasPostedPo) {
            DB::table('document')
                ->where('id', $zout->id)
                ->update(['provodka' => 0]);
        }
    }

    private static function updateCache(string $userId, string $fid): void
    {
        $zout = DB::table('document')
            ->where('client1', $userId)->where('firma', $fid)
            ->where('type', 'ZOUT')->sum('summa');
        $paid = DB::table('z_document')
            ->where('client1', $userId)->where('firma', $fid)
            ->where('type', 'PO')->where('provodka', 1)->sum('summa');
        $salary = DB::table('z_document')
            ->where('client1', $userId)->where('firma', $fid)
            ->where('type', 'ZP')->where('provodka', 1)->sum('summa');
        $balance = (float) $paid + (float) $salary - (float) $zout;

        DB::table('users_cashe')->updateOrInsert(
            ['userid' => $userId],
            [
                'balance' => $balance,
                'firma' => (int) $fid,
                'user_id' => (int) $userId,
            ]
        );
    }

    private static function applyColumnDelta($query, string $column, float $delta): void
    {
        if ($delta > 0) {
            $query->increment($column, $delta);
            return;
        }

        if ($delta < 0) {
            $query->decrement($column, abs($delta));
        }
    }

    private static function applyUserBalanceDelta(string $userId, string $fid, float $delta, string $currency): void
    {
        if ($delta == 0.0 || !Schema::hasColumn('users', 'balance')) {
            return;
        }

        $firmaScope = HoldingScope::projectIdsFor($fid);
        $user = DB::table('users')
            ->where('id', $userId)
            ->whereIn('firma', $firmaScope)
            ->lockForUpdate()
            ->first(['id', 'balance']);

        if (!$user) {
            return;
        }

        $balances = self::parseBalanceString($user->balance ?? '');
        $currency = self::normalizeCurrencyCode($currency);
        $found = false;

        foreach ($balances as &$balance) {
            if ($balance['currency'] !== $currency) {
                continue;
            }

            $balance['amount'] = self::formatBalanceAmount((float) $balance['amount'] + $delta);
            $found = true;
            break;
        }
        unset($balance);

        if (!$found) {
            $balances[] = [
                'amount' => self::formatBalanceAmount($delta),
                'currency' => $currency,
            ];
        }

        DB::table('users')->where('id', $user->id)->update([
            'balance' => self::serializeBalanceString($balances),
        ]);
    }

    private static function parseBalanceString(mixed $value): array
    {
        $raw = trim((string) ($value ?? ''));
        if ($raw === '') {
            return [];
        }

        if (!str_contains($raw, ':') && is_numeric($raw)) {
            return [[
                'amount' => self::formatBalanceAmount((float) $raw),
                'currency' => 'UAH',
            ]];
        }

        $balances = [];
        foreach (explode(';', $raw) as $segment) {
            $segment = trim($segment);
            if ($segment === '' || !str_contains($segment, ':')) {
                continue;
            }

            [$amount, $currency] = array_map('trim', explode(':', $segment, 2));
            $amount = str_replace(',', '.', $amount);
            if ($amount === '' || !is_numeric($amount)) {
                continue;
            }

            $balances[] = [
                'amount' => self::formatBalanceAmount((float) $amount),
                'currency' => self::normalizeCurrencyCode($currency),
            ];
        }

        return $balances;
    }

    private static function serializeBalanceString(array $balances): ?string
    {
        $segments = [];
        foreach ($balances as $balance) {
            $amount = self::formatBalanceAmount((float) ($balance['amount'] ?? 0));
            $currency = self::normalizeCurrencyCode($balance['currency'] ?? 'UAH');
            $segments[] = "{$amount}:{$currency};";
        }

        return $segments === [] ? null : implode('', $segments);
    }

    private static function currencyForCashbox(string $cashboxId, string $fid, array $confColumns): string
    {
        if (trim($cashboxId) === '') {
            return 'UAH';
        }

        $select = ['name'];
        if (in_array('currency', $confColumns, true)) {
            $select[] = 'currency';
        }

        $cashbox = DB::table('conf')
            ->where('id', $cashboxId)
            ->where('type', 'oplata')
            ->where('firma', $fid)
            ->first($select);

        if (!$cashbox) {
            return 'UAH';
        }

        if (in_array('currency', $confColumns, true)) {
            $configuredCurrency = trim((string) ($cashbox->currency ?? ''));
            if ($configuredCurrency !== '') {
                return self::normalizeCurrencyCode($configuredCurrency);
            }
        }

        return self::currencyFromCashboxName((string) ($cashbox->name ?? ''));
    }

    private static function currencyFromCashboxName(string $name): string
    {
        $upperName = strtoupper($name);
        foreach (['UAH', 'USD', 'EUR', 'GBP', 'PLN', 'USDT', 'USDC', 'BTC', 'ETH'] as $currency) {
            if (preg_match('/(^|[^A-Z0-9])' . preg_quote($currency, '/') . '([^A-Z0-9]|$)/', $upperName) === 1) {
                return $currency;
            }
        }

        return 'UAH';
    }

    private static function normalizeCurrencyCode(mixed $value): string
    {
        $currency = strtoupper(preg_replace('/[^A-Z0-9]/', '', (string) $value) ?? '');

        return $currency !== '' ? substr($currency, 0, 10) : 'UAH';
    }

    private static function formatBalanceAmount(float $amount): string
    {
        $formatted = number_format($amount, 8, '.', '');
        $formatted = rtrim(rtrim($formatted, '0'), '.');

        if ($formatted === '' || $formatted === '-0') {
            return '0';
        }

        return $formatted;
    }

}
