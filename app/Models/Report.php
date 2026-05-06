<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class Report extends Model
{
    public $timestamps = false;
    protected $guarded = [];

    public static function summary(string $fid, string $dateFromInput = '', string $dateToInput = ''): array
    {
        $today = now()->format('d-m-Y');
        [$dateFromUi, $dateToUi, $dateFromLegacy, $dateToLegacy] = self::normalizePeriod($dateFromInput, $dateToInput);

        $newOrdersCount = (int) DB::table('document')
            ->where('firma', $fid)
            ->where('type', 'ZOUT')
            ->where(function ($query) {
                $query->where('status', 0)
                    ->orWhere('status', '')
                    ->orWhereNull('status');
            })
            ->count();

        $postedIncomeToday = (float) DB::table('z_document')
            ->where('firma', $fid)
            ->where('type', 'PO')
            ->where('provodka', 1)
            ->where('data', $today)
            ->sum('summa');

        $postedExpenseToday = (float) DB::table('z_document')
            ->where('firma', $fid)
            ->where('type', 'RO')
            ->where('provodka', 1)
            ->where('data', $today)
            ->sum('summa');

        $monthIncome = (float) DB::table('z_document')
            ->where('firma', $fid)
            ->where('type', 'PO')
            ->where('provodka', 1)
            ->whereRaw("STR_TO_DATE(data, '%d-%m-%Y') BETWEEN STR_TO_DATE(?, '%d-%m-%Y') AND STR_TO_DATE(?, '%d-%m-%Y')", [$dateFromLegacy, $dateToLegacy])
            ->sum('summa');

        $monthExpense = (float) DB::table('z_document')
            ->where('firma', $fid)
            ->where('type', 'RO')
            ->where('provodka', 1)
            ->whereRaw("STR_TO_DATE(data, '%d-%m-%Y') BETWEEN STR_TO_DATE(?, '%d-%m-%Y') AND STR_TO_DATE(?, '%d-%m-%Y')", [$dateFromLegacy, $dateToLegacy])
            ->sum('summa');

        $salesBaseQuery = DB::table('z_document as zd')
            ->join('z_body as zb', function ($join) {
                $join->on('zb.firma', '=', 'zd.firma')
                    ->whereRaw("
                        zb.docid = CASE
                            WHEN zd.type = 'RN' AND EXISTS(
                                SELECT 1
                                FROM z_body zb2
                                WHERE zb2.firma = zd.firma
                                  AND zb2.docid = CAST(zd.id AS CHAR)
                                LIMIT 1
                            ) THEN CAST(zd.id AS CHAR)
                            WHEN zd.docid IS NULL OR zd.docid = '' OR zd.docid = '0' THEN CAST(zd.id AS CHAR)
                            ELSE zd.docid
                        END
                    ");
            })
            ->where('zd.firma', $fid)
            ->where('zd.type', 'RN')
            ->where('zd.provodka', 1)
            ->whereRaw(
                "STR_TO_DATE(zd.data, '%d-%m-%Y') BETWEEN STR_TO_DATE(?, '%d-%m-%Y') AND STR_TO_DATE(?, '%d-%m-%Y')",
                [$dateFromLegacy, $dateToLegacy]
            );

        $salesTotals = (clone $salesBaseQuery)->selectRaw('
            COUNT(DISTINCT zd.id) as sales_docs_count,
            COALESCE(SUM(zb.pcount), 0) as sold_units_total,
            COALESCE(SUM(zb.psumma), 0) as sales_revenue_total
        ')->first();

        $salesDocsCount = (int) ($salesTotals->sales_docs_count ?? 0);
        $soldUnitsTotal = (float) ($salesTotals->sold_units_total ?? 0);
        $salesRevenueTotal = (float) ($salesTotals->sales_revenue_total ?? 0);
        $averageSalesDoc = $salesDocsCount > 0 ? $salesRevenueTotal / $salesDocsCount : 0.0;
        $cashFlowNet = $monthIncome - $monthExpense;

        $cashboxes = DB::table('conf')
            ->where('type', 'oplata')
            ->where('vision', '1')
            ->where('firma', $fid)
            ->orderBy('name')
            ->get(['id', 'name', 'value']);

        $cashBalanceTotal = (float) $cashboxes->sum(fn ($cashbox) => (float) ($cashbox->value ?? 0));

        $salesByGoods = DB::table('z_document as zd')
            ->join('z_body as zb', function ($join) {
                $join->on('zb.firma', '=', 'zd.firma')
                    ->whereRaw("
                        zb.docid = CASE
                            WHEN zd.type = 'RN' AND EXISTS(
                                SELECT 1
                                FROM z_body zb2
                                WHERE zb2.firma = zd.firma
                                  AND zb2.docid = CAST(zd.id AS CHAR)
                                LIMIT 1
                            ) THEN CAST(zd.id AS CHAR)
                            WHEN zd.docid IS NULL OR zd.docid = '' OR zd.docid = '0' THEN CAST(zd.id AS CHAR)
                            ELSE zd.docid
                        END
                    ");
            })
            ->leftJoin('comp as c', function ($join) {
                $join->on('zb.pnum', '=', 'c.id')
                    ->on('zb.firma', '=', 'c.firma');
            })
            ->where('zd.firma', $fid)
            ->where('zd.type', 'RN')
            ->where('zd.provodka', 1)
            ->whereRaw("STR_TO_DATE(zd.data, '%d-%m-%Y') BETWEEN STR_TO_DATE(?, '%d-%m-%Y') AND STR_TO_DATE(?, '%d-%m-%Y')", [$dateFromLegacy, $dateToLegacy])
            ->groupBy('zb.pnum', 'c.name')
            ->orderByDesc(DB::raw('SUM(zb.pcount)'))
            ->orderBy('c.name')
            ->get([
                'zb.pnum',
                DB::raw("COALESCE(NULLIF(c.name, ''), CONCAT('Товар #', zb.pnum)) as product_name"),
                DB::raw('SUM(zb.pcount) as sold_qty'),
                DB::raw('COUNT(DISTINCT zd.id) as documents_count'),
                DB::raw('SUM(zb.psumma) as sold_sum'),
            ]);

        $topProducts = $salesByGoods->take(10)->values();

        $recentOrders = DB::table('document as d')
            ->leftJoin('users as u', 'u.id', '=', 'd.client1')
            ->leftJoin('conf as st', function ($join) use ($fid) {
                $join->on('d.status', '=', 'st.id')
                    ->where('st.type', '=', 'status')
                    ->where('st.firma', '=', $fid);
            })
            ->where('d.firma', $fid)
            ->where('d.type', 'ZOUT')
            ->whereRaw(
                "STR_TO_DATE(d.data, '%d-%m-%Y') BETWEEN STR_TO_DATE(?, '%d-%m-%Y') AND STR_TO_DATE(?, '%d-%m-%Y')",
                [$dateFromLegacy, $dateToLegacy]
            )
            ->orderByRaw("STR_TO_DATE(d.data, '%d-%m-%Y') DESC")
            ->orderByDesc('d.id')
            ->limit(12)
            ->get([
                'd.id',
                'd.num',
                'd.data',
                'd.time',
                'd.summa',
                'd.content',
                'd.status',
                'd.provodka',
                'u.orgname',
                'u.name',
                'u.secondname',
                'u.name2',
                DB::raw("COALESCE(NULLIF(st.name, ''), 'Новий') as status_name"),
            ]);

        $largestCashbox = $cashboxes
            ->sortByDesc(fn ($cashbox) => (float) ($cashbox->value ?? 0))
            ->first();

        return [
            'today' => $today,
            'monthLabel' => self::periodLabel($dateFromUi, $dateToUi),
            'dateFrom' => $dateFromUi,
            'dateTo' => $dateToUi,
            'newOrdersCount' => $newOrdersCount,
            'postedIncomeToday' => $postedIncomeToday,
            'postedExpenseToday' => $postedExpenseToday,
            'monthIncome' => $monthIncome,
            'monthExpense' => $monthExpense,
            'cashFlowNet' => $cashFlowNet,
            'salesDocsCount' => $salesDocsCount,
            'soldUnitsTotal' => $soldUnitsTotal,
            'salesRevenueTotal' => $salesRevenueTotal,
            'averageSalesDoc' => $averageSalesDoc,
            'cashboxes' => $cashboxes,
            'cashBalanceTotal' => $cashBalanceTotal,
            'largestCashbox' => $largestCashbox,
            'salesByGoods' => $salesByGoods,
            'topProducts' => $topProducts,
            'recentOrders' => $recentOrders,
        ];
    }

    public static function trialBalance(string $fid, string $dateFromInput = '', string $dateToInput = ''): array
    {
        [$dateFromUi, $dateToUi] = self::normalizePeriod($dateFromInput, $dateToInput);

        $accounts = collect();
        if (DB::getSchemaBuilder()->hasTable('accounts') && DB::getSchemaBuilder()->hasTable('entries') && DB::getSchemaBuilder()->hasTable('transactions')) {
            $accounts = DB::table('accounts as a')
                ->leftJoin('entries as e', function ($join) use ($fid) {
                    $join->on('a.id', '=', 'e.account_id')
                        ->where('e.company_id', '=', (int) $fid);
                })
                ->leftJoin('transactions as t', 'e.transaction_id', '=', 't.id')
                ->groupBy('a.id', 'a.code', 'a.name', 'a.type')
                ->orderBy('a.code')
                ->get([
                    'a.id',
                    'a.code',
                    'a.name',
                    'a.type',
                    DB::raw("COALESCE(SUM(CASE WHEN t.date < '{$dateFromUi}' THEN e.debit ELSE 0 END), 0) as opening_debit"),
                    DB::raw("COALESCE(SUM(CASE WHEN t.date < '{$dateFromUi}' THEN e.credit ELSE 0 END), 0) as opening_credit"),
                    DB::raw("COALESCE(SUM(CASE WHEN t.date BETWEEN '{$dateFromUi}' AND '{$dateToUi}' THEN e.debit ELSE 0 END), 0) as period_debit"),
                    DB::raw("COALESCE(SUM(CASE WHEN t.date BETWEEN '{$dateFromUi}' AND '{$dateToUi}' THEN e.credit ELSE 0 END), 0) as period_credit"),
                ])
                ->map(function ($account) {
                    $openingNet = self::accountNet($account->type, (float) $account->opening_debit, (float) $account->opening_credit);
                    $closingNet = self::accountNet(
                        $account->type,
                        (float) $account->opening_debit + (float) $account->period_debit,
                        (float) $account->opening_credit + (float) $account->period_credit
                    );

                    $account->opening_balance_debit = $openingNet >= 0 ? $openingNet : 0.0;
                    $account->opening_balance_credit = $openingNet < 0 ? abs($openingNet) : 0.0;
                    $account->closing_balance_debit = $closingNet >= 0 ? $closingNet : 0.0;
                    $account->closing_balance_credit = $closingNet < 0 ? abs($closingNet) : 0.0;

                    return $account;
                })
                ->filter(function ($account) {
                    return abs((float) $account->opening_balance_debit) > 0.0001
                        || abs((float) $account->opening_balance_credit) > 0.0001
                        || abs((float) $account->period_debit) > 0.0001
                        || abs((float) $account->period_credit) > 0.0001
                        || abs((float) $account->closing_balance_debit) > 0.0001
                        || abs((float) $account->closing_balance_credit) > 0.0001;
                })
                ->values();
        }

        return [
            'dateFrom' => $dateFromUi,
            'dateTo' => $dateToUi,
            'periodLabel' => self::periodLabel($dateFromUi, $dateToUi),
            'rows' => $accounts,
            'totals' => [
                'opening_debit' => (float) $accounts->sum('opening_balance_debit'),
                'opening_credit' => (float) $accounts->sum('opening_balance_credit'),
                'period_debit' => (float) $accounts->sum('period_debit'),
                'period_credit' => (float) $accounts->sum('period_credit'),
                'closing_debit' => (float) $accounts->sum('closing_balance_debit'),
                'closing_credit' => (float) $accounts->sum('closing_balance_credit'),
            ],
        ];
    }

    public static function journal(string $fid, string $dateFromInput = '', string $dateToInput = '', string $accountId = ''): array
    {
        [$dateFromUi, $dateToUi] = self::normalizePeriod($dateFromInput, $dateToInput);
        $accountId = trim($accountId);

        $accounts = DB::getSchemaBuilder()->hasTable('accounts')
            ? DB::table('accounts')->orderBy('code')->get(['id', 'code', 'name'])
            : collect();

        $rows = collect();
        if (DB::getSchemaBuilder()->hasTable('transactions') && DB::getSchemaBuilder()->hasTable('entries') && DB::getSchemaBuilder()->hasTable('accounts')) {
            $query = DB::table('entries as e')
                ->join('transactions as t', 'e.transaction_id', '=', 't.id')
                ->join('accounts as a', 'e.account_id', '=', 'a.id')
                ->where('e.company_id', (int) $fid)
                ->whereBetween('t.date', [$dateFromUi, $dateToUi]);

            if ($accountId !== '') {
                $query->where('e.account_id', $accountId);
            }

            $rows = $query
                ->orderBy('t.date')
                ->orderBy('t.id')
                ->orderBy('e.id')
                ->get([
                    'e.id',
                    'e.transaction_id',
                    't.date',
                    't.description',
                    't.reference_type',
                    't.reference_id',
                    'a.code as account_code',
                    'a.name as account_name',
                    'e.debit',
                    'e.credit',
                    'e.currency',
                    'e.amount',
                ]);
        }

        return [
            'dateFrom' => $dateFromUi,
            'dateTo' => $dateToUi,
            'periodLabel' => self::periodLabel($dateFromUi, $dateToUi),
            'accountId' => $accountId,
            'accounts' => $accounts,
            'rows' => $rows,
            'totalDebit' => (float) $rows->sum('debit'),
            'totalCredit' => (float) $rows->sum('credit'),
        ];
    }

    public static function stockBalances(
        string $fid,
        string $skladId = '',
        string $search = '',
        string $dateFromInput = '',
        string $dateToInput = '',
        string $sort = 'product_name',
        string $direction = 'asc',
        bool $exportAll = false
    ): array
    {
        [$dateFromUi, $dateToUi, $dateFromLegacy, $dateToLegacy] = self::normalizePeriod($dateFromInput, $dateToInput);
        $skladId = trim($skladId);
        $search = trim($search);
        $direction = strtolower($direction) === 'desc' ? 'desc' : 'asc';
        $productNameSql = "COALESCE(NULLIF(d.name, ''), NULLIF(d.name_ua, ''), NULLIF(d.name_en, ''), NULLIF(c.nickname, ''), NULLIF(c.namedoc, ''), NULLIF(c.name, ''), CONCAT('Товар #', ps.pnum))";

        $sklads = DB::table('conf')
            ->where('type', 'sklads')
            ->where('firma', $fid)
            ->orderBy('name')
            ->get(['id', 'name']);

        $salesQuery = DB::table('z_document as zd')
            ->join('z_body as zb', function ($join) {
                $join->on('zb.firma', '=', 'zd.firma')
                    ->whereRaw("
                        zb.docid = CASE
                            WHEN zd.type = 'RN' AND EXISTS(
                                SELECT 1
                                FROM z_body zb2
                                WHERE zb2.firma = zd.firma
                                  AND zb2.docid = CAST(zd.id AS CHAR)
                                LIMIT 1
                            ) THEN CAST(zd.id AS CHAR)
                            WHEN zd.docid IS NULL OR zd.docid = '' OR zd.docid = '0' THEN CAST(zd.id AS CHAR)
                            ELSE zd.docid
                        END
                    ");
            })
            ->leftJoin('comp as c2', function ($join) {
                $join->on('zb.pnum', '=', 'c2.id')
                    ->on('zb.firma', '=', 'c2.firma');
            })
            ->leftJoin('price as pr', function ($join) {
                $join->on('zb.pnum', '=', 'pr.pnum')
                    ->on('zb.firma', '=', 'pr.firma');
            })
            ->where('zd.firma', $fid)
            ->where('zd.type', 'RN')
            ->where('zd.provodka', 1)
            ->whereRaw(
                "STR_TO_DATE(zd.data, '%d-%m-%Y') BETWEEN STR_TO_DATE(?, '%d-%m-%Y') AND STR_TO_DATE(?, '%d-%m-%Y')",
                [$dateFromLegacy, $dateToLegacy]
            );

        if ($skladId !== '') {
            $salesQuery->where('zd.sklads', $skladId);
        }

        if ($search !== '') {
            $salesQuery->where(function ($nested) use ($search) {
                $nested->where('zb.pnum', 'like', "%{$search}%")
                    ->orWhere('c2.nickname', 'like', "%{$search}%")
                    ->orWhere('c2.namedoc', 'like', "%{$search}%")
                    ->orWhere('c2.name', 'like', "%{$search}%");
            });
        }

        $salesSummary = (clone $salesQuery)->selectRaw('
            COUNT(DISTINCT zb.pnum) as sold_sku_count,
            COALESCE(SUM(zb.pcount), 0) as sold_qty_total,
            COALESCE(SUM(zb.psumma), 0) as revenue_total,
            COALESCE(SUM(COALESCE(NULLIF(zb.zvalue, ""), pr.pay, 0) * zb.pcount), 0) as estimated_cost_total
        ')->first();

        $salesSubquery = (clone $salesQuery)
            ->groupBy('zb.pnum', 'zd.sklads')
            ->select([
                'zb.pnum',
                DB::raw('zd.sklads as sklad'),
                DB::raw('SUM(zb.pcount) as sold_qty'),
                DB::raw('SUM(zb.psumma) as sold_sum'),
                DB::raw('SUM(COALESCE(NULLIF(zb.zvalue, ""), pr.pay, 0) * zb.pcount) as estimated_cost'),
                DB::raw('COUNT(DISTINCT zd.id) as sales_docs_count'),
            ]);

        $query = DB::table('price_sklad as ps')
            ->leftJoin('comp as c', function ($join) {
                $join->on('ps.pnum', '=', 'c.id')
                    ->on('ps.firma', '=', 'c.firma');
            })
            ->leftJoin('descript as d', function ($join) {
                $join->on('d.pnum', '=', 'c.id')
                    ->on('d.firma', '=', 'c.firma');
            })
            ->leftJoin('conf as skl', function ($join) {
                $join->on('ps.sklad', '=', 'skl.id')
                    ->where('skl.type', '=', 'sklads');
            })
            ->leftJoinSub($salesSubquery, 'sg', function ($join) {
                $join->on('ps.pnum', '=', 'sg.pnum')
                    ->on('ps.sklad', '=', 'sg.sklad');
            })
            ->where('ps.firma', $fid);

        if ($skladId !== '') {
            $query->where('ps.sklad', $skladId);
        }

        if ($search !== '') {
            $query->where(function ($nested) use ($search) {
                $nested->where('ps.pnum', 'like', "%{$search}%")
                    ->orWhere('d.name', 'like', "%{$search}%")
                    ->orWhere('d.name_ua', 'like', "%{$search}%")
                    ->orWhere('d.name_en', 'like', "%{$search}%")
                    ->orWhere('c.nickname', 'like', "%{$search}%")
                    ->orWhere('c.namedoc', 'like', "%{$search}%")
                    ->orWhere('c.name', 'like', "%{$search}%");
            });
        }

        $totalCount = (clone $query)->count();
        $totalQty = (float) (clone $query)->sum('ps.count');
        $sortableColumns = [
            'product_name' => DB::raw($productNameSql),
            'sklad_name' => DB::raw("COALESCE(NULLIF(skl.name, ''), CONCAT('Склад #', ps.sklad))"),
            'count' => 'ps.count',
            'sold_qty' => DB::raw('COALESCE(sg.sold_qty, 0)'),
            'sold_sum' => DB::raw('COALESCE(sg.sold_sum, 0)'),
            'estimated_cost' => DB::raw('COALESCE(sg.estimated_cost, 0)'),
            'gross_profit' => DB::raw('(COALESCE(sg.sold_sum, 0) - COALESCE(sg.estimated_cost, 0))'),
            'gross_margin' => DB::raw('CASE WHEN COALESCE(sg.sold_sum, 0) > 0 THEN ((COALESCE(sg.sold_sum, 0) - COALESCE(sg.estimated_cost, 0)) / COALESCE(sg.sold_sum, 0)) * 100 ELSE 0 END'),
        ];
        $sortColumn = $sortableColumns[$sort] ?? $sortableColumns['product_name'];

        $query->orderBy($sortColumn, $direction)
            ->orderBy('ps.pnum');

        $selectColumns = [
                'ps.pnum',
                'ps.sklad',
                'ps.count',
                'ps.garant',
                DB::raw($productNameSql . ' as product_name'),
                DB::raw("COALESCE(NULLIF(skl.name, ''), CONCAT('Склад #', ps.sklad)) as sklad_name"),
                DB::raw('COALESCE(sg.sold_qty, 0) as sold_qty'),
                DB::raw('COALESCE(sg.sold_sum, 0) as sold_sum'),
                DB::raw('COALESCE(sg.estimated_cost, 0) as estimated_cost'),
                DB::raw('COALESCE(sg.sales_docs_count, 0) as sales_docs_count'),
                DB::raw('(COALESCE(sg.sold_sum, 0) - COALESCE(sg.estimated_cost, 0)) as gross_profit'),
                DB::raw('CASE WHEN COALESCE(sg.sold_sum, 0) > 0 THEN ((COALESCE(sg.sold_sum, 0) - COALESCE(sg.estimated_cost, 0)) / COALESCE(sg.sold_sum, 0)) * 100 ELSE 0 END as gross_margin'),
        ];

        if ($exportAll) {
            $items = $query->get($selectColumns);
        } else {
            $items = $query->paginate(20, $selectColumns)->withQueryString();
        }

        $soldQtyTotal = (float) ($salesSummary->sold_qty_total ?? 0);
        $revenueTotal = (float) ($salesSummary->revenue_total ?? 0);
        $estimatedCostTotal = (float) ($salesSummary->estimated_cost_total ?? 0);
        $grossProfitTotal = $revenueTotal - $estimatedCostTotal;
        $grossMarginTotal = $revenueTotal > 0 ? ($grossProfitTotal / $revenueTotal) * 100 : 0;
        $soldSkuCount = (int) ($salesSummary->sold_sku_count ?? 0);

        return [
            'dateFrom' => $dateFromUi,
            'dateTo' => $dateToUi,
            'monthLabel' => self::periodLabel($dateFromUi, $dateToUi),
            'skladId' => $skladId,
            'search' => $search,
            'sort' => $sort,
            'direction' => $direction,
            'sklads' => $sklads,
            'items' => $items,
            'totalCount' => $totalCount,
            'totalQty' => $totalQty,
            'soldQtyTotal' => $soldQtyTotal,
            'revenueTotal' => $revenueTotal,
            'estimatedCostTotal' => $estimatedCostTotal,
            'grossProfitTotal' => $grossProfitTotal,
            'grossMarginTotal' => $grossMarginTotal,
            'soldSkuCount' => $soldSkuCount,
        ];
    }

    public static function salesOperations(string $fid, string $dateFromInput = '', string $dateToInput = ''): array
    {
        [$dateFromUi, $dateToUi, $dateFromLegacy, $dateToLegacy] = self::normalizePeriod($dateFromInput, $dateToInput);
        $periodDays = max(1, Carbon::createFromFormat('Y-m-d', $dateFromUi)->diffInDays(Carbon::createFromFormat('Y-m-d', $dateToUi)) + 1);

        $salesDocs = DB::table('z_document as d')
            ->where('d.firma', $fid)
            ->where('d.type', 'RN')
            ->where('d.provodka', 1)
            ->whereRaw(
                "STR_TO_DATE(d.data, '%d-%m-%Y') BETWEEN STR_TO_DATE(?, '%d-%m-%Y') AND STR_TO_DATE(?, '%d-%m-%Y')",
                [$dateFromLegacy, $dateToLegacy]
            )
            ->orderByRaw("STR_TO_DATE(d.data, '%d-%m-%Y') ASC")
            ->get(['d.id', 'd.num', 'd.data', 'd.summa', 'd.client1']);

        $salesDocsCount = (int) $salesDocs->count();
        $salesRevenueTotal = (float) $salesDocs->sum(fn ($item) => (float) ($item->summa ?? 0));
        $averageCheck = $salesDocsCount > 0 ? $salesRevenueTotal / $salesDocsCount : 0.0;
        $salesOrdersCount = (int) DB::table('document')
            ->where('firma', $fid)
            ->where('type', 'ZOUT')
            ->whereRaw(
                "STR_TO_DATE(data, '%d-%m-%Y') BETWEEN STR_TO_DATE(?, '%d-%m-%Y') AND STR_TO_DATE(?, '%d-%m-%Y')",
                [$dateFromLegacy, $dateToLegacy]
            )
            ->count();
        $conversionRate = $salesOrdersCount > 0 ? ($salesDocsCount / $salesOrdersCount) * 100 : 0.0;

        $soldUnitsTotal = (float) DB::table('z_document as zd')
            ->join('z_body as zb', function ($join) {
                $join->on('zb.firma', '=', 'zd.firma')
                    ->whereRaw("
                        zb.docid = CASE
                            WHEN zd.type = 'RN' AND EXISTS(
                                SELECT 1 FROM z_body zb2
                                WHERE zb2.firma = zd.firma
                                  AND zb2.docid = CAST(zd.id AS CHAR)
                                LIMIT 1
                            ) THEN CAST(zd.id AS CHAR)
                            WHEN zd.docid IS NULL OR zd.docid = '' OR zd.docid = '0' THEN CAST(zd.id AS CHAR)
                            ELSE zd.docid
                        END
                    ");
            })
            ->where('zd.firma', $fid)
            ->where('zd.type', 'RN')
            ->where('zd.provodka', 1)
            ->whereRaw(
                "STR_TO_DATE(zd.data, '%d-%m-%Y') BETWEEN STR_TO_DATE(?, '%d-%m-%Y') AND STR_TO_DATE(?, '%d-%m-%Y')",
                [$dateFromLegacy, $dateToLegacy]
            )
            ->sum('zb.pcount');

        $salesByDay = $salesDocs
            ->groupBy(fn ($item) => Carbon::createFromFormat('d-m-Y', (string) $item->data)->format('d-m-Y'))
            ->map(function ($group, $day) {
                return (object) [
                    'label' => $day,
                    'sales_count' => $group->count(),
                    'revenue_sum' => (float) $group->sum(fn ($item) => (float) ($item->summa ?? 0)),
                    'avg_check' => $group->count() > 0 ? (float) $group->sum('summa') / $group->count() : 0.0,
                ];
            })
            ->values();

        $salesByWeek = $salesDocs
            ->groupBy(function ($item) {
                $date = Carbon::createFromFormat('d-m-Y', (string) $item->data)->startOfWeek(Carbon::MONDAY);
                return $date->format('d-m-Y');
            })
            ->map(function ($group, $weekStart) {
                $start = Carbon::createFromFormat('d-m-Y', $weekStart);
                $end = $start->copy()->endOfWeek(Carbon::SUNDAY);
                return (object) [
                    'label' => $start->format('d-m-Y') . ' - ' . $end->format('d-m-Y'),
                    'sales_count' => $group->count(),
                    'revenue_sum' => (float) $group->sum(fn ($item) => (float) ($item->summa ?? 0)),
                    'avg_check' => $group->count() > 0 ? (float) $group->sum('summa') / $group->count() : 0.0,
                ];
            })
            ->values();

        $salesByMonth = $salesDocs
            ->groupBy(fn ($item) => Carbon::createFromFormat('d-m-Y', (string) $item->data)->format('m-Y'))
            ->map(function ($group, $month) {
                return (object) [
                    'label' => $month,
                    'sales_count' => $group->count(),
                    'revenue_sum' => (float) $group->sum(fn ($item) => (float) ($item->summa ?? 0)),
                    'avg_check' => $group->count() > 0 ? (float) $group->sum('summa') / $group->count() : 0.0,
                ];
            })
            ->values();

        return [
            'dateFrom' => $dateFromUi,
            'dateTo' => $dateToUi,
            'monthLabel' => self::periodLabel($dateFromUi, $dateToUi),
            'periodDays' => $periodDays,
            'salesDocsCount' => $salesDocsCount,
            'salesOrdersCount' => $salesOrdersCount,
            'salesRevenueTotal' => $salesRevenueTotal,
            'averageCheck' => $averageCheck,
            'soldUnitsTotal' => $soldUnitsTotal,
            'conversionRate' => $conversionRate,
            'salesByDay' => $salesByDay,
            'salesByWeek' => $salesByWeek,
            'salesByMonth' => $salesByMonth,
        ];
    }

    public static function abcXyzAnalysis(string $fid, string $dateFromInput = '', string $dateToInput = ''): array
    {
        [$dateFromUi, $dateToUi, $dateFromLegacy, $dateToLegacy] = self::normalizePeriod($dateFromInput, $dateToInput);
        $period = collect();
        $periodCursor = Carbon::createFromFormat('Y-m-d', $dateFromUi)->startOfMonth();
        $periodEnd = Carbon::createFromFormat('Y-m-d', $dateToUi)->startOfMonth();
        while ($periodCursor->lte($periodEnd)) {
            $period->push($periodCursor->format('m-Y'));
            $periodCursor->addMonth();
        }

        $rows = DB::table('z_document as zd')
            ->join('z_body as zb', function ($join) {
                $join->on('zb.firma', '=', 'zd.firma')
                    ->whereRaw("
                        zb.docid = CASE
                            WHEN zd.type = 'RN' AND EXISTS(
                                SELECT 1 FROM z_body zb2
                                WHERE zb2.firma = zd.firma
                                  AND zb2.docid = CAST(zd.id AS CHAR)
                                LIMIT 1
                            ) THEN CAST(zd.id AS CHAR)
                            WHEN zd.docid IS NULL OR zd.docid = '' OR zd.docid = '0' THEN CAST(zd.id AS CHAR)
                            ELSE zd.docid
                        END
                    ");
            })
            ->leftJoin('comp as c', function ($join) {
                $join->on('zb.pnum', '=', 'c.id')
                    ->on('zb.firma', '=', 'c.firma');
            })
            ->where('zd.firma', $fid)
            ->where('zd.type', 'RN')
            ->where('zd.provodka', 1)
            ->whereRaw(
                "STR_TO_DATE(zd.data, '%d-%m-%Y') BETWEEN STR_TO_DATE(?, '%d-%m-%Y') AND STR_TO_DATE(?, '%d-%m-%Y')",
                [$dateFromLegacy, $dateToLegacy]
            )
            ->get([
                'zb.pnum',
                'zb.pcount',
                'zb.psumma',
                'zd.data',
                DB::raw("COALESCE(NULLIF(c.name, ''), CONCAT('Товар #', zb.pnum)) as product_name"),
            ]);

        $totalRevenue = (float) $rows->sum(fn ($item) => (float) ($item->psumma ?? 0));
        $grouped = $rows->groupBy('pnum')->map(function ($items) use ($period) {
            $monthlyRevenue = collect($period)->mapWithKeys(fn ($month) => [$month => 0.0]);
            foreach ($items as $item) {
                $monthKey = Carbon::createFromFormat('d-m-Y', (string) $item->data)->format('m-Y');
                $monthlyRevenue[$monthKey] = (float) $monthlyRevenue[$monthKey] + (float) ($item->psumma ?? 0);
            }

            $values = $monthlyRevenue->values();
            $avg = $values->count() > 0 ? $values->avg() : 0.0;
            $variance = $values->count() > 0
                ? $values->reduce(fn ($carry, $value) => $carry + (($value - $avg) ** 2), 0.0) / max(1, $values->count())
                : 0.0;
            $stdDev = sqrt($variance);
            $cv = $avg > 0 ? $stdDev / $avg : 999.0;

            return (object) [
                'pnum' => $items->first()->pnum,
                'product_name' => $items->first()->product_name,
                'revenue_total' => (float) $items->sum(fn ($item) => (float) ($item->psumma ?? 0)),
                'qty_total' => (float) $items->sum(fn ($item) => (float) ($item->pcount ?? 0)),
                'monthly_revenue' => $monthlyRevenue,
                'cv' => $cv,
            ];
        })->sortByDesc('revenue_total')->values();

        $cumulative = 0.0;
        $items = $grouped->map(function ($item) use ($totalRevenue, &$cumulative) {
            $share = $totalRevenue > 0 ? ($item->revenue_total / $totalRevenue) * 100 : 0.0;
            $cumulative += $share;
            $abcClass = $cumulative <= 80 ? 'A' : ($cumulative <= 95 ? 'B' : 'C');
            $xyzClass = $item->cv <= 0.5 ? 'X' : ($item->cv <= 1.0 ? 'Y' : 'Z');
            $item->revenue_share = $share;
            $item->cumulative_share = $cumulative;
            $item->abc_class = $abcClass;
            $item->xyz_class = $xyzClass;
            $item->matrix_class = $abcClass . $xyzClass;
            return $item;
        });

        return [
            'dateFrom' => $dateFromUi,
            'dateTo' => $dateToUi,
            'monthLabel' => self::periodLabel($dateFromUi, $dateToUi),
            'totalRevenue' => $totalRevenue,
            'items' => $items,
            'abcSummary' => [
                'A' => $items->where('abc_class', 'A')->count(),
                'B' => $items->where('abc_class', 'B')->count(),
                'C' => $items->where('abc_class', 'C')->count(),
            ],
            'xyzSummary' => [
                'X' => $items->where('xyz_class', 'X')->count(),
                'Y' => $items->where('xyz_class', 'Y')->count(),
                'Z' => $items->where('xyz_class', 'Z')->count(),
            ],
        ];
    }

    public static function inventoryOperations(string $fid, string $dateFromInput = '', string $dateToInput = ''): array
    {
        $stocks = self::stockBalances($fid, '', '', $dateFromInput, $dateToInput, 'count', 'desc', true);
        $periodDays = max(1, Carbon::createFromFormat('Y-m-d', $stocks['dateFrom'])->diffInDays(Carbon::createFromFormat('Y-m-d', $stocks['dateTo'])) + 1);

        $items = collect($stocks['items'])->map(function ($item) use ($periodDays) {
            $soldQty = (float) ($item->sold_qty ?? 0);
            $stockQty = (float) ($item->count ?? 0);
            $dailySales = $soldQty / $periodDays;
            $daysInventory = $dailySales > 0 ? $stockQty / $dailySales : null;
            $item->daily_sales = $dailySales;
            $item->days_inventory = $daysInventory;
            $item->stock_status = $stockQty <= 0 ? 'out_of_stock' : (($daysInventory !== null && $daysInventory < 14) ? 'low_stock' : 'normal');
            return $item;
        })->sortBy([
            fn ($item) => $item->stock_status === 'out_of_stock' ? 0 : ($item->stock_status === 'low_stock' ? 1 : 2),
            fn ($item) => $item->days_inventory ?? 999999,
        ])->values();

        return [
            'dateFrom' => $stocks['dateFrom'],
            'dateTo' => $stocks['dateTo'],
            'monthLabel' => $stocks['monthLabel'],
            'periodDays' => $periodDays,
            'items' => $items,
            'skuCount' => $items->count(),
            'totalQty' => (float) $items->sum(fn ($item) => (float) ($item->count ?? 0)),
            'outOfStockCount' => $items->where('stock_status', 'out_of_stock')->count(),
            'lowStockCount' => $items->where('stock_status', 'low_stock')->count(),
            'soldQtyTotal' => (float) $items->sum(fn ($item) => (float) ($item->sold_qty ?? 0)),
        ];
    }

    public static function turnoverOperations(string $fid, string $dateFromInput = '', string $dateToInput = ''): array
    {
        $inventory = self::inventoryOperations($fid, $dateFromInput, $dateToInput);
        $items = collect($inventory['items'])->map(function ($item) {
            $stockQty = (float) ($item->count ?? 0);
            $soldQty = (float) ($item->sold_qty ?? 0);
            $item->dead_stock = $stockQty > 0 && $soldQty <= 0;
            $item->slow_moving = !$item->dead_stock && (($item->days_inventory ?? 0) > 90);
            return $item;
        })->sortBy([
            fn ($item) => $item->dead_stock ? 0 : ($item->slow_moving ? 1 : 2),
            fn ($item) => -1 * (float) ($item->count ?? 0),
        ])->values();

        return array_merge($inventory, [
            'items' => $items,
            'deadStockCount' => $items->where('dead_stock', true)->count(),
            'slowMovingCount' => $items->where('slow_moving', true)->count(),
        ]);
    }

    public static function purchaseOperations(string $fid, string $dateFromInput = '', string $dateToInput = ''): array
    {
        [$dateFromUi, $dateToUi, $dateFromLegacy, $dateToLegacy] = self::normalizePeriod($dateFromInput, $dateToInput);

        $supplierSummary = DB::table('document as d')
            ->leftJoin('users as u', 'u.id', '=', 'd.client1')
            ->where('d.firma', $fid)
            ->where('d.type', 'ZIN')
            ->whereRaw(
                "STR_TO_DATE(d.data, '%d-%m-%Y') BETWEEN STR_TO_DATE(?, '%d-%m-%Y') AND STR_TO_DATE(?, '%d-%m-%Y')",
                [$dateFromLegacy, $dateToLegacy]
            )
            ->groupBy('d.client1', 'u.orgname', 'u.name', 'u.secondname')
            ->orderByDesc(DB::raw('SUM(d.summa)'))
            ->get([
                'd.client1',
                DB::raw("TRIM(CONCAT(COALESCE(u.orgname, ''), ' ', COALESCE(u.secondname, ''), ' ', COALESCE(u.name, ''))) as supplier_name"),
                DB::raw('COUNT(*) as orders_count'),
                DB::raw('SUM(d.summa) as ordered_sum'),
            ]);

        $purchasePrices = DB::table('z_document as zd')
            ->join('z_body as zb', function ($join) {
                $join->on('zb.firma', '=', 'zd.firma')
                    ->whereRaw("
                        zb.docid = CASE
                            WHEN zd.type = 'PN' AND EXISTS(
                                SELECT 1 FROM z_body zb2
                                WHERE zb2.firma = zd.firma
                                  AND zb2.docid = CAST(zd.id AS CHAR)
                                LIMIT 1
                            ) THEN CAST(zd.id AS CHAR)
                            WHEN zd.docid IS NULL OR zd.docid = '' OR zd.docid = '0' THEN CAST(zd.id AS CHAR)
                            ELSE zd.docid
                        END
                    ");
            })
            ->leftJoin('comp as c', function ($join) {
                $join->on('zb.pnum', '=', 'c.id')
                    ->on('zb.firma', '=', 'c.firma');
            })
            ->where('zd.firma', $fid)
            ->where('zd.type', 'PN')
            ->where('zd.provodka', 1)
            ->whereRaw(
                "STR_TO_DATE(zd.data, '%d-%m-%Y') BETWEEN STR_TO_DATE(?, '%d-%m-%Y') AND STR_TO_DATE(?, '%d-%m-%Y')",
                [$dateFromLegacy, $dateToLegacy]
            )
            ->groupBy('zb.pnum', 'c.name')
            ->orderByDesc(DB::raw('SUM(zb.psumma)'))
            ->limit(20)
            ->get([
                'zb.pnum',
                DB::raw("COALESCE(NULLIF(c.name, ''), CONCAT('Товар #', zb.pnum)) as product_name"),
                DB::raw('AVG(zb.pprice) as avg_purchase_price'),
                DB::raw('SUM(zb.pcount) as purchased_qty'),
                DB::raw('SUM(zb.psumma) as purchased_sum'),
            ]);

        $receivedSubquery = DB::table('z_document')
            ->where('firma', $fid)
            ->where('type', 'PN')
            ->selectRaw('docid, SUM(summa) as received_sum, AVG(DATEDIFF(STR_TO_DATE(data, "%d-%m-%Y"), STR_TO_DATE(data2, "%d-%m-%Y"))) as avg_gap')
            ->groupBy('docid');

        $orders = DB::table('document as d')
            ->leftJoin('users as u', 'u.id', '=', 'd.client1')
            ->leftJoinSub($receivedSubquery, 'pn', function ($join) {
                $join->on('d.id', '=', 'pn.docid');
            })
            ->where('d.firma', $fid)
            ->where('d.type', 'ZIN')
            ->whereRaw(
                "STR_TO_DATE(d.data, '%d-%m-%Y') BETWEEN STR_TO_DATE(?, '%d-%m-%Y') AND STR_TO_DATE(?, '%d-%m-%Y')",
                [$dateFromLegacy, $dateToLegacy]
            )
            ->orderByRaw("STR_TO_DATE(d.data, '%d-%m-%Y') DESC")
            ->orderByDesc('d.id')
            ->limit(25)
            ->get([
                'd.id',
                'd.num',
                'd.data',
                'd.summa',
                'd.provodka',
                DB::raw("TRIM(CONCAT(COALESCE(u.orgname, ''), ' ', COALESCE(u.secondname, ''), ' ', COALESCE(u.name, ''))) as supplier_name"),
                DB::raw('COALESCE(pn.received_sum, 0) as received_sum'),
            ])->map(function ($item) {
                $ordered = (float) ($item->summa ?? 0);
                $received = (float) ($item->received_sum ?? 0);
                $item->fulfillment_rate = $ordered > 0 ? ($received / $ordered) * 100 : 0.0;
                return $item;
            });

        return [
            'dateFrom' => $dateFromUi,
            'dateTo' => $dateToUi,
            'monthLabel' => self::periodLabel($dateFromUi, $dateToUi),
            'supplierSummary' => $supplierSummary,
            'purchasePrices' => $purchasePrices,
            'orders' => $orders,
            'purchaseOrdersCount' => $orders->count(),
            'purchaseOrderedTotal' => (float) $orders->sum(fn ($item) => (float) ($item->summa ?? 0)),
            'purchaseReceivedTotal' => (float) $orders->sum(fn ($item) => (float) ($item->received_sum ?? 0)),
        ];
    }

    public static function pnlSegments(string $fid, string $dateFromInput = '', string $dateToInput = ''): array
    {
        [$dateFromUi, $dateToUi, $dateFromLegacy, $dateToLegacy] = self::normalizePeriod($dateFromInput, $dateToInput);
        $lines = self::salesLineItems($fid, $dateFromLegacy, $dateToLegacy);

        $totals = self::segmentTotals($lines);
        $byCategory = self::groupSalesLines($lines, 'category_name');
        $byChannel = self::groupSalesLines($lines, 'channel_name');
        $byRegion = self::groupSalesLines($lines, 'region_name');

        return [
            'dateFrom' => $dateFromUi,
            'dateTo' => $dateToUi,
            'monthLabel' => self::periodLabel($dateFromUi, $dateToUi),
            'salesDocsCount' => $totals['salesDocsCount'],
            'soldQtyTotal' => $totals['soldQtyTotal'],
            'revenueTotal' => $totals['revenueTotal'],
            'costTotal' => $totals['costTotal'],
            'grossProfitTotal' => $totals['grossProfitTotal'],
            'grossMarginTotal' => $totals['grossMarginTotal'],
            'byCategory' => $byCategory,
            'byChannel' => $byChannel,
            'byRegion' => $byRegion,
        ];
    }

    public static function unitEconomics(string $fid, string $dateFromInput = '', string $dateToInput = ''): array
    {
        [$dateFromUi, $dateToUi, $dateFromLegacy, $dateToLegacy] = self::normalizePeriod($dateFromInput, $dateToInput);
        $lines = self::salesLineItems($fid, $dateFromLegacy, $dateToLegacy);
        $periodStart = Carbon::createFromFormat('Y-m-d', $dateFromUi)->startOfDay();
        $periodEnd = Carbon::createFromFormat('Y-m-d', $dateToUi)->endOfDay();

        $skuEconomics = $lines->groupBy('pnum')->map(function ($items) {
            $qty = (float) $items->sum(fn ($item) => (float) ($item->qty ?? 0));
            $revenue = (float) $items->sum(fn ($item) => (float) ($item->revenue ?? 0));
            $cost = (float) $items->sum(fn ($item) => (float) ($item->cost_total ?? 0));
            $grossProfit = $revenue - $cost;

            return (object) [
                'pnum' => $items->first()->pnum,
                'product_name' => $items->first()->product_name,
                'qty' => $qty,
                'revenue' => $revenue,
                'cost' => $cost,
                'gross_profit' => $grossProfit,
                'gross_margin' => $revenue > 0 ? ($grossProfit / $revenue) * 100 : 0.0,
                'avg_unit_revenue' => $qty > 0 ? $revenue / $qty : 0.0,
                'avg_unit_profit' => $qty > 0 ? $grossProfit / $qty : 0.0,
            ];
        })->sortByDesc('gross_profit')->values();

        $firstPurchases = DB::table('z_document')
            ->where('firma', $fid)
            ->where('type', 'RN')
            ->where('provodka', 1)
            ->whereNotNull('client1')
            ->where('client1', '<>', '')
            ->groupBy('client1')
            ->get([
                'client1',
                DB::raw("MIN(STR_TO_DATE(data, '%d-%m-%Y')) as first_purchase_at"),
            ]);

        $periodCustomers = $lines
            ->pluck('client1')
            ->filter(fn ($value) => (string) $value !== '')
            ->unique();

        $newCustomersCount = $firstPurchases
            ->filter(function ($item) use ($periodStart, $periodEnd, $periodCustomers) {
                if (!$periodCustomers->contains((string) $item->client1)) {
                    return false;
                }

                if (!$item->first_purchase_at) {
                    return false;
                }

                $firstPurchase = Carbon::parse($item->first_purchase_at);
                return $firstPurchase->between($periodStart, $periodEnd);
            })
            ->count();

        $allTimeCustomerStats = DB::table('z_document')
            ->where('firma', $fid)
            ->where('type', 'RN')
            ->where('provodka', 1)
            ->selectRaw('COUNT(DISTINCT client1) as customers_count, COALESCE(SUM(summa), 0) as revenue_total')
            ->first();

        $marketingSpend = self::marketingSpend($fid, $dateFromLegacy, $dateToLegacy);
        $periodGrossProfit = (float) $skuEconomics->sum(fn ($item) => (float) ($item->gross_profit ?? 0));
        $activeCustomersCount = $periodCustomers->count();
        $cac = $newCustomersCount > 0 ? $marketingSpend / $newCustomersCount : 0.0;
        $ltv = (int) ($allTimeCustomerStats->customers_count ?? 0) > 0
            ? (float) ($allTimeCustomerStats->revenue_total ?? 0) / (int) $allTimeCustomerStats->customers_count
            : 0.0;
        $marketingRoi = $marketingSpend > 0 ? (($periodGrossProfit - $marketingSpend) / $marketingSpend) * 100 : 0.0;

        return [
            'dateFrom' => $dateFromUi,
            'dateTo' => $dateToUi,
            'monthLabel' => self::periodLabel($dateFromUi, $dateToUi),
            'skuEconomics' => $skuEconomics,
            'marketingSpend' => $marketingSpend,
            'newCustomersCount' => $newCustomersCount,
            'activeCustomersCount' => $activeCustomersCount,
            'cac' => $cac,
            'ltv' => $ltv,
            'marketingRoi' => $marketingRoi,
            'periodGrossProfit' => $periodGrossProfit,
            'revenueTotal' => (float) $skuEconomics->sum(fn ($item) => (float) ($item->revenue ?? 0)),
            'avgOrderValue' => $activeCustomersCount > 0
                ? (float) $skuEconomics->sum(fn ($item) => (float) ($item->revenue ?? 0)) / max($activeCustomersCount, 1)
                : 0.0,
            'marketingAssumption' => 'CAC та ROI розраховані за проведеними RO з маркетинговими ключовими словами в призначенні платежу.',
        ];
    }

    public static function grossProfitAnalysis(string $fid, string $dateFromInput = '', string $dateToInput = ''): array
    {
        [$dateFromUi, $dateToUi, $dateFromLegacy, $dateToLegacy] = self::normalizePeriod($dateFromInput, $dateToInput);
        $lines = self::salesLineItems($fid, $dateFromLegacy, $dateToLegacy);

        $byProduct = $lines->groupBy('pnum')->map(function ($items) {
            $qty = (float) $items->sum(fn ($item) => (float) ($item->qty ?? 0));
            $revenue = (float) $items->sum(fn ($item) => (float) ($item->revenue ?? 0));
            $cost = (float) $items->sum(fn ($item) => (float) ($item->cost_total ?? 0));
            $grossProfit = $revenue - $cost;
            $discountImpact = (float) $items->sum(fn ($item) => (float) ($item->line_discount_impact ?? 0));

            return (object) [
                'pnum' => $items->first()->pnum,
                'product_name' => $items->first()->product_name,
                'qty' => $qty,
                'revenue' => $revenue,
                'cost' => $cost,
                'gross_profit' => $grossProfit,
                'gross_margin' => $revenue > 0 ? ($grossProfit / $revenue) * 100 : 0.0,
                'markup_percent' => $cost > 0 ? ($grossProfit / $cost) * 100 : 0.0,
                'discount_impact' => $discountImpact,
            ];
        })->sortByDesc('gross_profit')->values();

        $discountDocIds = DB::table('z_document')
            ->where('firma', $fid)
            ->where('type', 'RN')
            ->where('provodka', 1)
            ->whereRaw(
                "STR_TO_DATE(data, '%d-%m-%Y') BETWEEN STR_TO_DATE(?, '%d-%m-%Y') AND STR_TO_DATE(?, '%d-%m-%Y')",
                [$dateFromLegacy, $dateToLegacy]
            )
            ->whereNotNull('docid')
            ->where('docid', '<>', '')
            ->distinct()
            ->pluck('docid');

        $discountDocs = DB::table('document as d')
            ->leftJoin('users as u', 'u.id', '=', 'd.client1')
            ->where('d.firma', $fid)
            ->where('d.type', 'ZOUT')
            ->whereIn('d.id', $discountDocIds)
            ->whereRaw('COALESCE(d.discount, 0) > 0')
            ->orderByRaw("STR_TO_DATE(d.data, '%d-%m-%Y') DESC")
            ->get([
                'd.id',
                'd.num',
                'd.data',
                'd.summa',
                'd.discount',
                DB::raw("TRIM(CONCAT(COALESCE(u.orgname, ''), ' ', COALESCE(u.secondname, ''), ' ', COALESCE(u.name, ''))) as client_name"),
            ]);

        return [
            'dateFrom' => $dateFromUi,
            'dateTo' => $dateToUi,
            'monthLabel' => self::periodLabel($dateFromUi, $dateToUi),
            'byProduct' => $byProduct,
            'revenueTotal' => (float) $byProduct->sum(fn ($item) => (float) ($item->revenue ?? 0)),
            'costTotal' => (float) $byProduct->sum(fn ($item) => (float) ($item->cost ?? 0)),
            'grossProfitTotal' => (float) $byProduct->sum(fn ($item) => (float) ($item->gross_profit ?? 0)),
            'discountImpactTotal' => (float) $byProduct->sum(fn ($item) => (float) ($item->discount_impact ?? 0)),
            'discountDocs' => $discountDocs,
            'discountDocsTotal' => (float) $discountDocs->sum(fn ($item) => (float) ($item->discount ?? 0)),
        ];
    }

    public static function financialPnl(string $fid, string $dateFromInput = '', string $dateToInput = ''): array
    {
        [$dateFromUi, $dateToUi, $dateFromLegacy, $dateToLegacy] = self::normalizePeriod($dateFromInput, $dateToInput);
        $lines = self::salesLineItems($fid, $dateFromLegacy, $dateToLegacy);
        $totals = self::segmentTotals($lines);

        $operatingExpensesByType = DB::table('z_document as d')
            ->leftJoin('conf as reg', function ($join) use ($fid) {
                $join->on('d.reestr', '=', 'reg.id')
                    ->where('reg.type', '=', 'reestr')
                    ->where('reg.firma', '=', $fid);
            })
            ->where('d.firma', $fid)
            ->where('d.type', 'RO')
            ->where('d.provodka', 1)
            ->whereRaw(
                "STR_TO_DATE(d.data, '%d-%m-%Y') BETWEEN STR_TO_DATE(?, '%d-%m-%Y') AND STR_TO_DATE(?, '%d-%m-%Y')",
                [$dateFromLegacy, $dateToLegacy]
            )
            ->groupBy('d.reestr', 'reg.name')
            ->orderByDesc(DB::raw('SUM(d.summa)'))
            ->get([
                DB::raw("COALESCE(NULLIF(reg.name, ''), CONCAT('Стаття #', d.reestr)) as expense_name"),
                DB::raw('SUM(d.summa) as expense_sum'),
                DB::raw('COUNT(*) as docs_count'),
            ]);

        $operatingExpensesTotal = (float) $operatingExpensesByType->sum(fn ($item) => (float) ($item->expense_sum ?? 0));
        $netProfit = (float) $totals['grossProfitTotal'] - $operatingExpensesTotal;

        return [
            'dateFrom' => $dateFromUi,
            'dateTo' => $dateToUi,
            'monthLabel' => self::periodLabel($dateFromUi, $dateToUi),
            'revenueTotal' => $totals['revenueTotal'],
            'cogsTotal' => $totals['costTotal'],
            'grossProfitTotal' => $totals['grossProfitTotal'],
            'grossMarginTotal' => $totals['grossMarginTotal'],
            'operatingExpensesTotal' => $operatingExpensesTotal,
            'netProfit' => $netProfit,
            'operatingExpensesByType' => $operatingExpensesByType,
        ];
    }

    public static function balanceSheet(string $fid, string $dateFromInput = '', string $dateToInput = ''): array
    {
        [$dateFromUi, $dateToUi, $dateFromLegacy, $dateToLegacy] = self::normalizePeriod($dateFromInput, $dateToInput);

        $inventoryValue = (float) DB::table('price_sklad as ps')
            ->leftJoin('price as pr', function ($join) {
                $join->on('ps.pnum', '=', 'pr.pnum')
                    ->on('ps.firma', '=', 'pr.firma');
            })
            ->where('ps.firma', $fid)
            ->sum(DB::raw('COALESCE(ps.count, 0) * COALESCE(pr.pay, 0)'));

        $cashBalance = (float) DB::table('conf')
            ->where('type', 'oplata')
            ->where('firma', $fid)
            ->sum('value');

        $depositBalance = (float) DB::table('conf')
            ->where('type', 'deposit')
            ->where('firma', $fid)
            ->sum('value');

        $receivables = self::receivablesTotal($fid);
        $payables = self::payablesTotal($fid);
        $loans = self::financingFlow($fid, $dateFromLegacy, $dateToLegacy)['loan_balance_proxy'];

        $totalAssets = $inventoryValue + $cashBalance + $depositBalance + $receivables;
        $totalLiabilities = $payables + $loans;
        $equity = $totalAssets - $totalLiabilities;

        return [
            'dateFrom' => $dateFromUi,
            'dateTo' => $dateToUi,
            'monthLabel' => self::periodLabel($dateFromUi, $dateToUi),
            'inventoryValue' => $inventoryValue,
            'cashBalance' => $cashBalance,
            'depositBalance' => $depositBalance,
            'receivables' => $receivables,
            'payables' => $payables,
            'loans' => $loans,
            'totalAssets' => $totalAssets,
            'totalLiabilities' => $totalLiabilities,
            'equity' => $equity,
        ];
    }

    public static function cashFlowStatement(string $fid, string $dateFromInput = '', string $dateToInput = ''): array
    {
        [$dateFromUi, $dateToUi, $dateFromLegacy, $dateToLegacy] = self::normalizePeriod($dateFromInput, $dateToInput);

        $operatingInflows = (float) DB::table('z_document')
            ->where('firma', $fid)
            ->where('type', 'PO')
            ->where('provodka', 1)
            ->whereRaw(
                "STR_TO_DATE(data, '%d-%m-%Y') BETWEEN STR_TO_DATE(?, '%d-%m-%Y') AND STR_TO_DATE(?, '%d-%m-%Y')",
                [$dateFromLegacy, $dateToLegacy]
            )
            ->sum('summa');

        $operatingOutflows = (float) DB::table('z_document')
            ->where('firma', $fid)
            ->where('type', 'RO')
            ->where('provodka', 1)
            ->whereRaw(
                "STR_TO_DATE(data, '%d-%m-%Y') BETWEEN STR_TO_DATE(?, '%d-%m-%Y') AND STR_TO_DATE(?, '%d-%m-%Y')",
                [$dateFromLegacy, $dateToLegacy]
            )
            ->sum('summa');

        $investing = self::investmentFlow($fid, $dateFromLegacy, $dateToLegacy);
        $financing = self::financingFlow($fid, $dateFromLegacy, $dateToLegacy);

        $operatingNet = $operatingInflows - $operatingOutflows;
        $investingNet = $investing['inflows'] - $investing['outflows'];
        $financingNet = $financing['inflows'] - $financing['outflows'];
        $netChange = $operatingNet + $investingNet + $financingNet;

        return [
            'dateFrom' => $dateFromUi,
            'dateTo' => $dateToUi,
            'monthLabel' => self::periodLabel($dateFromUi, $dateToUi),
            'operatingInflows' => $operatingInflows,
            'operatingOutflows' => $operatingOutflows,
            'operatingNet' => $operatingNet,
            'investingInflows' => $investing['inflows'],
            'investingOutflows' => $investing['outflows'],
            'investingNet' => $investingNet,
            'financingInflows' => $financing['inflows'],
            'financingOutflows' => $financing['outflows'],
            'financingNet' => $financingNet,
            'netCashFlow' => $netChange,
            'financingAssumption' => $financing['assumption'],
        ];
    }

    public static function salesForecast(string $fid, string $dateFromInput = '', string $dateToInput = ''): array
    {
        [$dateFromUi, $dateToUi, $dateFromLegacy, $dateToLegacy] = self::normalizePeriod($dateFromInput, $dateToInput);
        $history = self::salesMonthlyHistory($fid, 6);
        $avgRevenue = (float) $history->avg(fn ($item) => (float) ($item->revenue ?? 0));
        $avgDocs = (float) $history->avg(fn ($item) => (float) ($item->sales_docs ?? 0));
        $avgQty = (float) $history->avg(fn ($item) => (float) ($item->qty ?? 0));
        $trendGrowth = self::trendGrowthPercent($history->pluck('revenue')->map(fn ($value) => (float) $value)->all());
        $forecastRevenue = $avgRevenue * (1 + ($trendGrowth / 100));
        $forecastDocs = $avgDocs * (1 + ($trendGrowth / 100));
        $forecastQty = $avgQty * (1 + ($trendGrowth / 100));
        $actual = self::segmentTotals(self::salesLineItems($fid, $dateFromLegacy, $dateToLegacy));
        $seasonality = self::salesSeasonality($fid, 12);
        $segmentForecasts = self::segmentForecasts($fid, 6);

        return [
            'dateFrom' => $dateFromUi,
            'dateTo' => $dateToUi,
            'monthLabel' => self::periodLabel($dateFromUi, $dateToUi),
            'history' => $history,
            'forecastRevenue' => $forecastRevenue,
            'forecastDocs' => $forecastDocs,
            'forecastQty' => $forecastQty,
            'trendGrowth' => $trendGrowth,
            'actualRevenue' => $actual['revenueTotal'],
            'actualDocs' => $actual['salesDocsCount'],
            'actualQty' => $actual['soldQtyTotal'],
            'planFactRevenuePercent' => $forecastRevenue > 0 ? ($actual['revenueTotal'] / $forecastRevenue) * 100 : 0.0,
            'planFactDocsPercent' => $forecastDocs > 0 ? ($actual['salesDocsCount'] / $forecastDocs) * 100 : 0.0,
            'planFactQtyPercent' => $forecastQty > 0 ? ($actual['soldQtyTotal'] / $forecastQty) * 100 : 0.0,
            'seasonality' => $seasonality,
            'categoryForecasts' => $segmentForecasts['categories'],
            'channelForecasts' => $segmentForecasts['channels'],
        ];
    }

    public static function purchasePlan(string $fid, string $dateFromInput = '', string $dateToInput = ''): array
    {
        [$dateFromUi, $dateToUi] = self::normalizePeriod($dateFromInput, $dateToInput);
        $history = self::salesMonthlyHistory($fid, 3, true);
        $stockData = self::stockBalances($fid, '', '', $dateFromUi, $dateToUi, 'sold_qty', 'desc', true);
        $items = collect($stockData['items'])->map(function ($item) use ($history) {
            $avgMonthlyQty = (float) $history
                ->where('pnum', $item->pnum)
                ->avg(fn ($row) => (float) ($row->qty ?? 0));
            $plannedDemand = max($avgMonthlyQty, (float) ($item->sold_qty ?? 0));
            $currentStock = (float) ($item->count ?? 0);
            $plannedPurchaseQty = max($plannedDemand - $currentStock, 0.0);
            $unitCost = (float) (($item->estimated_cost ?? 0) > 0 && ($item->sold_qty ?? 0) > 0
                ? ((float) $item->estimated_cost / max((float) $item->sold_qty, 1))
                : 0);

            return (object) [
                'pnum' => $item->pnum,
                'product_name' => $item->product_name,
                'current_stock' => $currentStock,
                'avg_monthly_qty' => $avgMonthlyQty,
                'planned_demand' => $plannedDemand,
                'planned_purchase_qty' => $plannedPurchaseQty,
                'planned_purchase_sum' => $plannedPurchaseQty * $unitCost,
            ];
        })->filter(fn ($item) => (float) $item->planned_purchase_qty > 0)
            ->sortByDesc('planned_purchase_sum')
            ->values();

        return [
            'dateFrom' => $dateFromUi,
            'dateTo' => $dateToUi,
            'monthLabel' => self::periodLabel($dateFromUi, $dateToUi),
            'items' => $items,
            'plannedPurchaseTotal' => (float) $items->sum(fn ($item) => (float) ($item->planned_purchase_sum ?? 0)),
            'plannedPurchaseQtyTotal' => (float) $items->sum(fn ($item) => (float) ($item->planned_purchase_qty ?? 0)),
        ];
    }

    public static function profitPlan(string $fid, string $dateFromInput = '', string $dateToInput = ''): array
    {
        [$dateFromUi, $dateToUi] = self::normalizePeriod($dateFromInput, $dateToInput);
        $forecast = self::salesForecast($fid, $dateFromUi, $dateToUi);
        $pnl = self::financialPnl($fid, $dateFromUi, $dateToUi);
        $marginRate = (float) ($pnl['revenueTotal'] ?? 0) > 0
            ? ((float) ($pnl['grossProfitTotal'] ?? 0) / (float) $pnl['revenueTotal'])
            : 0.0;
        $opexRate = (float) ($pnl['revenueTotal'] ?? 0) > 0
            ? ((float) ($pnl['operatingExpensesTotal'] ?? 0) / (float) $pnl['revenueTotal'])
            : 0.0;

        $plannedRevenue = (float) ($forecast['forecastRevenue'] ?? 0);
        $plannedGrossProfit = $plannedRevenue * $marginRate;
        $plannedOpex = $plannedRevenue * $opexRate;
        $plannedNetProfit = $plannedGrossProfit - $plannedOpex;
        $actualRevenue = (float) ($forecast['actualRevenue'] ?? 0);
        $actualGrossProfit = (float) ($pnl['grossProfitTotal'] ?? 0);
        $actualOpex = (float) ($pnl['operatingExpensesTotal'] ?? 0);
        $actualNetProfit = (float) ($pnl['netProfit'] ?? 0);

        return [
            'dateFrom' => $dateFromUi,
            'dateTo' => $dateToUi,
            'monthLabel' => self::periodLabel($dateFromUi, $dateToUi),
            'plannedRevenue' => $plannedRevenue,
            'plannedGrossProfit' => $plannedGrossProfit,
            'plannedOpex' => $plannedOpex,
            'plannedNetProfit' => $plannedNetProfit,
            'marginRate' => $marginRate * 100,
            'opexRate' => $opexRate * 100,
            'actualRevenue' => $actualRevenue,
            'actualGrossProfit' => $actualGrossProfit,
            'actualOpex' => $actualOpex,
            'actualNetProfit' => $actualNetProfit,
            'planFactRevenuePercent' => $plannedRevenue > 0 ? ($actualRevenue / $plannedRevenue) * 100 : 0.0,
            'planFactNetProfitPercent' => $plannedNetProfit != 0.0 ? ($actualNetProfit / $plannedNetProfit) * 100 : 0.0,
        ];
    }

    public static function demandTrends(string $fid, string $dateFromInput = '', string $dateToInput = ''): array
    {
        [$dateFromUi, $dateToUi] = self::normalizePeriod($dateFromInput, $dateToInput);
        $history = self::salesMonthlyHistory($fid, 6, true);
        $seasonality = self::salesSeasonality($fid, 12);
        $items = $history->groupBy('pnum')->map(function ($rows) {
            $sorted = $rows->sortBy('month_key')->values();
            $qtySeries = $sorted->pluck('qty')->map(fn ($value) => (float) $value)->all();
            $first = (float) ($qtySeries[0] ?? 0);
            $last = (float) ($qtySeries[count($qtySeries) - 1] ?? 0);
            $growth = $first > 0 ? (($last - $first) / $first) * 100 : ($last > 0 ? 100.0 : 0.0);
            $avg = count($qtySeries) > 0 ? array_sum($qtySeries) / count($qtySeries) : 0.0;
            $variance = count($qtySeries) > 0
                ? array_sum(array_map(fn ($value) => ($value - $avg) ** 2, $qtySeries)) / count($qtySeries)
                : 0.0;
            $cv = $avg > 0 ? sqrt($variance) / $avg : 999.0;

            return (object) [
                'pnum' => $sorted->first()->pnum,
                'product_name' => $sorted->first()->product_name,
                'avg_qty' => $avg,
                'trend_growth' => $growth,
                'cv' => $cv,
                'trend_label' => $growth > 15 ? 'Зростає' : ($growth < -15 ? 'Падає' : 'Стабільно'),
            ];
        })->sortByDesc(fn ($item) => abs((float) $item->trend_growth))->values();

        return [
            'dateFrom' => $dateFromUi,
            'dateTo' => $dateToUi,
            'monthLabel' => self::periodLabel($dateFromUi, $dateToUi),
            'items' => $items,
            'seasonality' => $seasonality,
        ];
    }

    public static function finance(string $fid, string $dateFromInput = '', string $dateToInput = '', string $oplataId = ''): array
    {
        [$dateFromUi, $dateToUi, $dateFromLegacy, $dateToLegacy] = self::normalizePeriod($dateFromInput, $dateToInput);
        $oplataId = trim($oplataId);

        $oplatas = DB::table('conf')
            ->where('type', 'oplata')
            ->where('firma', $fid)
            ->orderBy('name')
            ->get(['id', 'name', 'vision']);

        $baseQuery = DB::table('z_document as d')
            ->leftJoin('users as u', 'u.id', '=', 'd.client1')
            ->leftJoin('conf as opl', function ($join) use ($fid) {
                $join->on('d.money', '=', 'opl.id')
                    ->where('opl.type', '=', 'oplata')
                    ->where('opl.firma', '=', $fid);
            })
            ->leftJoin('conf as reg', function ($join) use ($fid) {
                $join->on('d.reestr', '=', 'reg.id')
                    ->where('reg.type', '=', 'reestr')
                    ->where('reg.firma', '=', $fid);
            })
            ->where('d.firma', $fid)
            ->whereIn('d.type', ['PO', 'RO'])
            ->where('d.provodka', 1)
            ->whereRaw(
                "STR_TO_DATE(d.data, '%d-%m-%Y') BETWEEN STR_TO_DATE(?, '%d-%m-%Y') AND STR_TO_DATE(?, '%d-%m-%Y')",
                [$dateFromLegacy, $dateToLegacy]
            );

        if ($oplataId !== '') {
            $baseQuery->where('d.money', $oplataId);
        }

        $totalIncome = (float) (clone $baseQuery)->where('d.type', 'PO')->sum('d.summa');
        $totalExpense = (float) (clone $baseQuery)->where('d.type', 'RO')->sum('d.summa');
        $postedCount = (int) (clone $baseQuery)->count();
        $operatingCashFlow = $totalIncome - $totalExpense;
        $paymentTypes = (clone $baseQuery)
            ->groupBy('d.reestr', 'reg.name')
            ->orderByRaw("COALESCE(NULLIF(reg.name, ''), CONCAT('Вид #', d.reestr))")
            ->get([
                'd.reestr',
                DB::raw("COALESCE(NULLIF(reg.name, ''), CONCAT('Вид #', d.reestr)) as reestr_name"),
                DB::raw("SUM(CASE WHEN d.type = 'PO' THEN d.summa ELSE 0 END) as income_sum"),
                DB::raw("SUM(CASE WHEN d.type = 'RO' THEN d.summa ELSE 0 END) as expense_sum"),
                DB::raw("SUM(CASE WHEN d.type = 'PO' THEN d.summa ELSE -d.summa END) as profit_sum"),
                DB::raw('COUNT(*) as docs_count'),
            ]);

        $payments = $baseQuery
            ->orderByRaw("STR_TO_DATE(d.data, '%d-%m-%Y') DESC")
            ->orderByDesc('d.id')
            ->paginate(20, [
                'd.id',
                'd.num',
                'd.type',
                'd.data',
                'd.time',
                'd.summa',
                'd.content',
                'd.money',
                'd.reestr',
                'd.client1',
                'u.orgname',
                'u.name',
                'u.name2',
                'u.secondname',
                'u.phone',
                DB::raw("COALESCE(NULLIF(opl.name, ''), CONCAT('Каса #', d.money)) as oplata_name"),
                DB::raw("COALESCE(NULLIF(reg.name, ''), CONCAT('Вид #', d.reestr)) as reestr_name"),
            ])
            ->withQueryString();

        $depositBaseQuery = DB::table('z_document as d')
            ->leftJoin('conf as dep', function ($join) use ($fid) {
                $join->on('d.money', '=', 'dep.id')
                    ->where('dep.type', '=', 'deposit')
                    ->where('dep.firma', '=', $fid);
            })
            ->leftJoin('conf as cash_from', function ($join) use ($fid) {
                $join->on('d.oplata', '=', 'cash_from.id')
                    ->where('cash_from.type', '=', 'oplata')
                    ->where('cash_from.firma', '=', $fid);
            })
            ->leftJoin('conf as cash_to', function ($join) use ($fid) {
                $join->on('d.oplata2', '=', 'cash_to.id')
                    ->where('cash_to.type', '=', 'oplata')
                    ->where('cash_to.firma', '=', $fid);
            })
            ->where('d.firma', $fid)
            ->where('d.type', 'PP')
            ->where('d.provodka', 1)
            ->whereRaw(
                "STR_TO_DATE(d.data, '%d-%m-%Y') BETWEEN STR_TO_DATE(?, '%d-%m-%Y') AND STR_TO_DATE(?, '%d-%m-%Y')",
                [$dateFromLegacy, $dateToLegacy]
            );

        if ($oplataId !== '') {
            $depositBaseQuery->where(function ($query) use ($oplataId) {
                $query->where('d.oplata', $oplataId)
                    ->orWhere('d.oplata2', $oplataId);
            });
        }

        $depositTopups = (float) (clone $depositBaseQuery)
            ->where('d.docum', 'topup')
            ->sum('d.summa');

        $depositWithdrawals = (float) (clone $depositBaseQuery)
            ->where('d.docum', 'withdraw')
            ->sum('d.summa');

        $depositExchanges = (float) (clone $depositBaseQuery)
            ->where('d.docum', 'exchange')
            ->sum('d.summa');

        $depositOpsCount = (int) (clone $depositBaseQuery)
            ->whereIn('d.docum', ['topup', 'withdraw'])
            ->count();

        $depositMovementItems = (clone $depositBaseQuery)
            ->whereIn('d.docum', ['topup', 'withdraw'])
            ->groupBy('d.money', 'dep.name')
            ->orderByRaw("COALESCE(NULLIF(dep.name, ''), CONCAT('Депозит #', d.money))")
            ->get([
                'd.money as deposit_id',
                DB::raw("COALESCE(NULLIF(dep.name, ''), CONCAT('Депозит #', d.money)) as deposit_name"),
                DB::raw("SUM(CASE WHEN d.docum = 'topup' THEN d.summa ELSE 0 END) as topup_sum"),
                DB::raw("SUM(CASE WHEN d.docum = 'withdraw' THEN d.summa ELSE 0 END) as withdraw_sum"),
                DB::raw("SUM(CASE WHEN d.docum = 'topup' THEN d.summa WHEN d.docum = 'withdraw' THEN -d.summa ELSE 0 END) as net_flow"),
                DB::raw('COUNT(*) as docs_count'),
            ]);

        $depositTransactions = (clone $depositBaseQuery)
            ->orderByRaw("STR_TO_DATE(d.data, '%d-%m-%Y') DESC")
            ->orderByDesc('d.id')
            ->get([
                'd.id',
                'd.num',
                'd.data',
                'd.summa',
                'd.content',
                'd.docum',
                DB::raw("COALESCE(NULLIF(dep.name, ''), CONCAT('Депозит #', d.money)) as deposit_name"),
                DB::raw("COALESCE(NULLIF(cash_from.name, ''), CONCAT('Каса #', d.oplata)) as cash_from_name"),
                DB::raw("COALESCE(NULLIF(cash_to.name, ''), CONCAT('Каса #', d.oplata2)) as cash_to_name"),
            ]);

        $depositPortfolio = DB::table('conf')
            ->where('type', 'deposit')
            ->where('firma', $fid)
            ->orderBy('name')
            ->get(['id', 'name', 'value', 'value1']);

        $depositPortfolioTotal = (float) $depositPortfolio->sum(fn ($item) => (float) ($item->value ?? 0));
        $depositLimitTotal = (float) $depositPortfolio->sum(fn ($item) => (float) ($item->value1 ?? 0));
        $cashBalanceTotal = (float) DB::table('conf')
            ->where('type', 'oplata')
            ->where('firma', $fid)
            ->sum('value');
        $treasuryTotal = $cashBalanceTotal + $depositPortfolioTotal;
        $depositNetFlow = $depositTopups - $depositWithdrawals;

        return [
            'dateFrom' => $dateFromUi,
            'dateTo' => $dateToUi,
            'monthLabel' => self::periodLabel($dateFromUi, $dateToUi),
            'oplataId' => $oplataId,
            'oplatas' => $oplatas,
            'totalIncome' => $totalIncome,
            'totalExpense' => $totalExpense,
            'profit' => $operatingCashFlow,
            'operatingCashFlow' => $operatingCashFlow,
            'postedCount' => $postedCount,
            'paymentTypes' => $paymentTypes,
            'payments' => $payments,
            'depositTopups' => $depositTopups,
            'depositWithdrawals' => $depositWithdrawals,
            'depositExchanges' => $depositExchanges,
            'depositNetFlow' => $depositNetFlow,
            'depositOpsCount' => $depositOpsCount,
            'depositMovementItems' => $depositMovementItems,
            'depositTransactions' => $depositTransactions,
            'depositPortfolio' => $depositPortfolio,
            'depositPortfolioTotal' => $depositPortfolioTotal,
            'depositLimitTotal' => $depositLimitTotal,
            'cashBalanceTotal' => $cashBalanceTotal,
            'treasuryTotal' => $treasuryTotal,
        ];
    }

    private static function salesLineItems(string $fid, string $dateFromLegacy, string $dateToLegacy)
    {
        return DB::table('z_document as zd')
            ->join('z_body as zb', function ($join) {
                $join->on('zb.firma', '=', 'zd.firma')
                    ->whereRaw("
                        zb.docid = CASE
                            WHEN zd.type = 'RN' AND EXISTS(
                                SELECT 1
                                FROM z_body zb2
                                WHERE zb2.firma = zd.firma
                                  AND zb2.docid = CAST(zd.id AS CHAR)
                                LIMIT 1
                            ) THEN CAST(zd.id AS CHAR)
                            WHEN zd.docid IS NULL OR zd.docid = '' OR zd.docid = '0' THEN CAST(zd.id AS CHAR)
                            ELSE zd.docid
                        END
                    ");
            })
            ->leftJoin('comp as c', function ($join) {
                $join->on('zb.pnum', '=', 'c.id')
                    ->on('zb.firma', '=', 'c.firma');
            })
            ->leftJoin('descript as d', function ($join) {
                $join->on('c.id', '=', 'd.pnum')
                    ->on('c.firma', '=', 'd.firma');
            })
            ->leftJoin('field as cat', function ($join) {
                $join->on('c.idglava', '=', 'cat.id')
                    ->on('c.firma', '=', 'cat.firma')
                    ->where('cat.keyfield', '=', 'catalog');
            })
            ->leftJoin('conf as rt', function ($join) use ($fid) {
                $join->on('zd.reteil', '=', 'rt.id')
                    ->where('rt.type', '=', 'reteil')
                    ->where('rt.firma', '=', $fid);
            })
            ->leftJoin('users as u', 'u.id', '=', 'zd.client1')
            ->leftJoin('price as pr', function ($join) {
                $join->on('zb.pnum', '=', 'pr.pnum')
                    ->on('zb.firma', '=', 'pr.firma');
            })
            ->where('zd.firma', $fid)
            ->where('zd.type', 'RN')
            ->where('zd.provodka', 1)
            ->whereRaw(
                "STR_TO_DATE(zd.data, '%d-%m-%Y') BETWEEN STR_TO_DATE(?, '%d-%m-%Y') AND STR_TO_DATE(?, '%d-%m-%Y')",
                [$dateFromLegacy, $dateToLegacy]
            )
            ->get([
                'zd.id as doc_id',
                'zd.num as doc_num',
                'zd.data as doc_date',
                'zd.client1',
                'zb.pnum',
                DB::raw('COALESCE(zb.pcount, 0) as qty'),
                DB::raw('COALESCE(zb.pprice, 0) as unit_price'),
                DB::raw('COALESCE(zb.psumma, 0) as revenue'),
                DB::raw('COALESCE(NULLIF(zb.zvalue, ""), pr.pay, 0) as cost_unit'),
                DB::raw('COALESCE(NULLIF(zb.zvalue, ""), pr.pay, 0) * COALESCE(zb.pcount, 0) as cost_total'),
                DB::raw('(COALESCE(zb.psumma, 0) - (COALESCE(NULLIF(zb.zvalue, ""), pr.pay, 0) * COALESCE(zb.pcount, 0))) as gross_profit'),
                DB::raw('GREATEST((COALESCE(zb.pprice, 0) * COALESCE(zb.pcount, 0)) - COALESCE(zb.psumma, 0), 0) as line_discount_impact'),
                DB::raw("COALESCE(NULLIF(d.name, ''), NULLIF(d.name_ua, ''), NULLIF(d.name_en, ''), NULLIF(c.nickname, ''), NULLIF(c.namedoc, ''), NULLIF(c.name, ''), CONCAT('Товар #', zb.pnum)) as product_name"),
                DB::raw("COALESCE(NULLIF(cat.val, ''), 'Без категорії') as category_name"),
                DB::raw("COALESCE(NULLIF(rt.name, ''), 'Без каналу') as channel_name"),
                DB::raw("COALESCE(NULLIF(u.region, ''), NULLIF(u.city, ''), 'Невизначено') as region_name"),
            ]);
    }

    private static function groupSalesLines($lines, string $groupField)
    {
        return $lines
            ->groupBy(fn ($item) => (string) ($item->{$groupField} ?? 'Невизначено'))
            ->map(function ($items, $segmentName) {
                $revenue = (float) $items->sum(fn ($item) => (float) ($item->revenue ?? 0));
                $cost = (float) $items->sum(fn ($item) => (float) ($item->cost_total ?? 0));
                $grossProfit = $revenue - $cost;

                return (object) [
                    'segment_name' => $segmentName,
                    'sales_docs_count' => $items->pluck('doc_id')->unique()->count(),
                    'sold_qty' => (float) $items->sum(fn ($item) => (float) ($item->qty ?? 0)),
                    'revenue' => $revenue,
                    'cost' => $cost,
                    'gross_profit' => $grossProfit,
                    'gross_margin' => $revenue > 0 ? ($grossProfit / $revenue) * 100 : 0.0,
                ];
            })
            ->sortByDesc('gross_profit')
            ->values();
    }

    private static function segmentTotals($lines): array
    {
        $revenueTotal = (float) $lines->sum(fn ($item) => (float) ($item->revenue ?? 0));
        $costTotal = (float) $lines->sum(fn ($item) => (float) ($item->cost_total ?? 0));
        $grossProfitTotal = $revenueTotal - $costTotal;

        return [
            'salesDocsCount' => $lines->pluck('doc_id')->unique()->count(),
            'soldQtyTotal' => (float) $lines->sum(fn ($item) => (float) ($item->qty ?? 0)),
            'revenueTotal' => $revenueTotal,
            'costTotal' => $costTotal,
            'grossProfitTotal' => $grossProfitTotal,
            'grossMarginTotal' => $revenueTotal > 0 ? ($grossProfitTotal / $revenueTotal) * 100 : 0.0,
        ];
    }

    private static function marketingSpend(string $fid, string $dateFromLegacy, string $dateToLegacy): float
    {
        return (float) DB::table('z_document')
            ->where('firma', $fid)
            ->where('type', 'RO')
            ->where('provodka', 1)
            ->whereRaw(
                "STR_TO_DATE(data, '%d-%m-%Y') BETWEEN STR_TO_DATE(?, '%d-%m-%Y') AND STR_TO_DATE(?, '%d-%m-%Y')",
                [$dateFromLegacy, $dateToLegacy]
            )
            ->where(function ($query) {
                foreach (['маркет', 'реклам', 'ads', 'google', 'meta', 'facebook', 'instagram', 'smm', 'seo', 'promo', 'просув'] as $keyword) {
                    $query->orWhereRaw('LOWER(COALESCE(content, "")) LIKE ?', ['%' . mb_strtolower($keyword) . '%']);
                }
            })
            ->sum('summa');
    }

    private static function receivablesTotal(string $fid): float
    {
        $salesOrders = (float) DB::table('document')
            ->where('firma', $fid)
            ->where('type', 'ZOUT')
            ->sum('summa');

        $paymentsIn = (float) DB::table('z_document')
            ->where('firma', $fid)
            ->where('type', 'PO')
            ->where('provodka', 1)
            ->sum('summa');

        return max($salesOrders - $paymentsIn, 0.0);
    }

    private static function payablesTotal(string $fid): float
    {
        $purchaseOrders = (float) DB::table('document')
            ->where('firma', $fid)
            ->where('type', 'ZIN')
            ->sum('summa');

        $paymentsOut = (float) DB::table('z_document')
            ->where('firma', $fid)
            ->where('type', 'RO')
            ->where('provodka', 1)
            ->sum('summa');

        return max($purchaseOrders - $paymentsOut, 0.0);
    }

    private static function investmentFlow(string $fid, string $dateFromLegacy, string $dateToLegacy): array
    {
        $topups = (float) DB::table('z_document')
            ->where('firma', $fid)
            ->where('type', 'PP')
            ->where('provodka', 1)
            ->where('docum', 'topup')
            ->whereRaw(
                "STR_TO_DATE(data, '%d-%m-%Y') BETWEEN STR_TO_DATE(?, '%d-%m-%Y') AND STR_TO_DATE(?, '%d-%m-%Y')",
                [$dateFromLegacy, $dateToLegacy]
            )
            ->sum('summa');

        $withdrawals = (float) DB::table('z_document')
            ->where('firma', $fid)
            ->where('type', 'PP')
            ->where('provodka', 1)
            ->where('docum', 'withdraw')
            ->whereRaw(
                "STR_TO_DATE(data, '%d-%m-%Y') BETWEEN STR_TO_DATE(?, '%d-%m-%Y') AND STR_TO_DATE(?, '%d-%m-%Y')",
                [$dateFromLegacy, $dateToLegacy]
            )
            ->sum('summa');

        return [
            'inflows' => $withdrawals,
            'outflows' => $topups,
        ];
    }

    private static function financingFlow(string $fid, string $dateFromLegacy, string $dateToLegacy): array
    {
        $keywords = ['кредит', 'loan', 'інвестор', 'investor', 'дивіденд', 'dividend', 'внесок', 'capital'];

        $inflows = (float) DB::table('z_document')
            ->where('firma', $fid)
            ->where('type', 'PO')
            ->where('provodka', 1)
            ->whereRaw(
                "STR_TO_DATE(data, '%d-%m-%Y') BETWEEN STR_TO_DATE(?, '%d-%m-%Y') AND STR_TO_DATE(?, '%d-%m-%Y')",
                [$dateFromLegacy, $dateToLegacy]
            )
            ->where(function ($query) use ($keywords) {
                foreach ($keywords as $keyword) {
                    $query->orWhereRaw('LOWER(COALESCE(content, "")) LIKE ?', ['%' . mb_strtolower($keyword) . '%']);
                }
            })
            ->sum('summa');

        $outflows = (float) DB::table('z_document')
            ->where('firma', $fid)
            ->where('type', 'RO')
            ->where('provodka', 1)
            ->whereRaw(
                "STR_TO_DATE(data, '%d-%m-%Y') BETWEEN STR_TO_DATE(?, '%d-%m-%Y') AND STR_TO_DATE(?, '%d-%m-%Y')",
                [$dateFromLegacy, $dateToLegacy]
            )
            ->where(function ($query) use ($keywords) {
                foreach ($keywords as $keyword) {
                    $query->orWhereRaw('LOWER(COALESCE(content, "")) LIKE ?', ['%' . mb_strtolower($keyword) . '%']);
                }
            })
            ->sum('summa');

        return [
            'inflows' => $inflows,
            'outflows' => $outflows,
            'loan_balance_proxy' => max($inflows - $outflows, 0.0),
            'assumption' => 'Фінансова діяльність визначається за PO/RO з ключовими словами: кредит, investor, dividend, capital.',
        ];
    }

    private static function salesMonthlyHistory(string $fid, int $months = 6, bool $byProduct = false)
    {
        $start = now()->startOfMonth()->subMonths(max($months - 1, 0));
        $end = now()->endOfMonth();

        $query = DB::table('z_document as zd')
            ->join('z_body as zb', function ($join) {
                $join->on('zb.firma', '=', 'zd.firma')
                    ->whereRaw("
                        zb.docid = CASE
                            WHEN zd.type = 'RN' AND EXISTS(
                                SELECT 1
                                FROM z_body zb2
                                WHERE zb2.firma = zd.firma
                                  AND zb2.docid = CAST(zd.id AS CHAR)
                                LIMIT 1
                            ) THEN CAST(zd.id AS CHAR)
                            WHEN zd.docid IS NULL OR zd.docid = '' OR zd.docid = '0' THEN CAST(zd.id AS CHAR)
                            ELSE zd.docid
                        END
                    ");
            })
            ->leftJoin('comp as c', function ($join) {
                $join->on('zb.pnum', '=', 'c.id')
                    ->on('zb.firma', '=', 'c.firma');
            })
            ->leftJoin('descript as d', function ($join) {
                $join->on('c.id', '=', 'd.pnum')
                    ->on('c.firma', '=', 'd.firma');
            })
            ->where('zd.firma', $fid)
            ->where('zd.type', 'RN')
            ->where('zd.provodka', 1)
            ->whereRaw(
                "STR_TO_DATE(zd.data, '%d-%m-%Y') BETWEEN ? AND ?",
                [$start->format('Y-m-d'), $end->format('Y-m-d')]
            );

        if ($byProduct) {
            return $query
                ->groupBy(DB::raw("DATE_FORMAT(STR_TO_DATE(zd.data, '%d-%m-%Y'), '%Y-%m')"), 'zb.pnum', 'c.name', 'd.name', 'd.name_ua', 'd.name_en', 'c.nickname', 'c.namedoc')
                ->orderBy(DB::raw("DATE_FORMAT(STR_TO_DATE(zd.data, '%d-%m-%Y'), '%Y-%m')"))
                ->get([
                    DB::raw("DATE_FORMAT(STR_TO_DATE(zd.data, '%d-%m-%Y'), '%Y-%m') as month_key"),
                    DB::raw("DATE_FORMAT(STR_TO_DATE(zd.data, '%d-%m-%Y'), '%m-%Y') as month_label"),
                    'zb.pnum',
                    DB::raw("COALESCE(NULLIF(d.name, ''), NULLIF(d.name_ua, ''), NULLIF(d.name_en, ''), NULLIF(c.nickname, ''), NULLIF(c.namedoc, ''), NULLIF(c.name, ''), CONCAT('Товар #', zb.pnum)) as product_name"),
                    DB::raw('SUM(zb.pcount) as qty'),
                    DB::raw('SUM(zb.psumma) as revenue'),
                    DB::raw('COUNT(DISTINCT zd.id) as sales_docs'),
                ]);
        }

        return $query
            ->groupBy(DB::raw("DATE_FORMAT(STR_TO_DATE(zd.data, '%d-%m-%Y'), '%Y-%m')"))
            ->orderBy(DB::raw("DATE_FORMAT(STR_TO_DATE(zd.data, '%d-%m-%Y'), '%Y-%m')"))
            ->get([
                DB::raw("DATE_FORMAT(STR_TO_DATE(zd.data, '%d-%m-%Y'), '%Y-%m') as month_key"),
                DB::raw("DATE_FORMAT(STR_TO_DATE(zd.data, '%d-%m-%Y'), '%m-%Y') as month_label"),
                DB::raw('SUM(zb.pcount) as qty'),
                DB::raw('SUM(zb.psumma) as revenue'),
                DB::raw('COUNT(DISTINCT zd.id) as sales_docs'),
            ]);
    }

    private static function salesSeasonality(string $fid, int $months = 12)
    {
        $start = now()->startOfMonth()->subMonths(max($months - 1, 0));
        $end = now()->endOfMonth();

        return DB::table('z_document as zd')
            ->join('z_body as zb', function ($join) {
                $join->on('zb.firma', '=', 'zd.firma')
                    ->whereRaw("
                        zb.docid = CASE
                            WHEN zd.type = 'RN' AND EXISTS(
                                SELECT 1
                                FROM z_body zb2
                                WHERE zb2.firma = zd.firma
                                  AND zb2.docid = CAST(zd.id AS CHAR)
                                LIMIT 1
                            ) THEN CAST(zd.id AS CHAR)
                            WHEN zd.docid IS NULL OR zd.docid = '' OR zd.docid = '0' THEN CAST(zd.id AS CHAR)
                            ELSE zd.docid
                        END
                    ");
            })
            ->where('zd.firma', $fid)
            ->where('zd.type', 'RN')
            ->where('zd.provodka', 1)
            ->whereRaw(
                "STR_TO_DATE(zd.data, '%d-%m-%Y') BETWEEN ? AND ?",
                [$start->format('Y-m-d'), $end->format('Y-m-d')]
            )
            ->groupBy(DB::raw("DATE_FORMAT(STR_TO_DATE(zd.data, '%d-%m-%Y'), '%m')"))
            ->orderBy(DB::raw("DATE_FORMAT(STR_TO_DATE(zd.data, '%d-%m-%Y'), '%m')"))
            ->get([
                DB::raw("DATE_FORMAT(STR_TO_DATE(zd.data, '%d-%m-%Y'), '%m') as month_num"),
                DB::raw("DATE_FORMAT(STR_TO_DATE(zd.data, '%d-%m-%Y'), '%m') as month_label"),
                DB::raw('SUM(zb.pcount) as qty'),
                DB::raw('SUM(zb.psumma) as revenue'),
            ]);
    }

    private static function segmentForecasts(string $fid, int $months = 6): array
    {
        $start = now()->startOfMonth()->subMonths(max($months - 1, 0));
        $end = now()->endOfMonth();

        $lines = DB::table('z_document as zd')
            ->join('z_body as zb', function ($join) {
                $join->on('zb.firma', '=', 'zd.firma')
                    ->whereRaw("
                        zb.docid = CASE
                            WHEN zd.type = 'RN' AND EXISTS(
                                SELECT 1
                                FROM z_body zb2
                                WHERE zb2.firma = zd.firma
                                  AND zb2.docid = CAST(zd.id AS CHAR)
                                LIMIT 1
                            ) THEN CAST(zd.id AS CHAR)
                            WHEN zd.docid IS NULL OR zd.docid = '' OR zd.docid = '0' THEN CAST(zd.id AS CHAR)
                            ELSE zd.docid
                        END
                    ");
            })
            ->leftJoin('comp as c', function ($join) {
                $join->on('zb.pnum', '=', 'c.id')
                    ->on('zb.firma', '=', 'c.firma');
            })
            ->leftJoin('field as cat', function ($join) {
                $join->on('c.idglava', '=', 'cat.id')
                    ->on('c.firma', '=', 'cat.firma')
                    ->where('cat.keyfield', '=', 'catalog');
            })
            ->leftJoin('conf as rt', function ($join) use ($fid) {
                $join->on('zd.reteil', '=', 'rt.id')
                    ->where('rt.type', '=', 'reteil')
                    ->where('rt.firma', '=', $fid);
            })
            ->where('zd.firma', $fid)
            ->where('zd.type', 'RN')
            ->where('zd.provodka', 1)
            ->whereRaw(
                "STR_TO_DATE(zd.data, '%d-%m-%Y') BETWEEN ? AND ?",
                [$start->format('Y-m-d'), $end->format('Y-m-d')]
            )
            ->get([
                DB::raw("COALESCE(NULLIF(cat.val, ''), 'Без категорії') as category_name"),
                DB::raw("COALESCE(NULLIF(rt.name, ''), 'Без каналу') as channel_name"),
                DB::raw('COALESCE(zb.psumma, 0) as revenue'),
            ]);

        $categories = $lines->groupBy('category_name')->map(function ($items, $name) use ($months) {
            $avgRevenue = (float) $items->sum(fn ($item) => (float) ($item->revenue ?? 0)) / max($months, 1);
            return (object) ['segment_name' => $name, 'forecast_revenue' => $avgRevenue];
        })->sortByDesc('forecast_revenue')->values();

        $channels = $lines->groupBy('channel_name')->map(function ($items, $name) use ($months) {
            $avgRevenue = (float) $items->sum(fn ($item) => (float) ($item->revenue ?? 0)) / max($months, 1);
            return (object) ['segment_name' => $name, 'forecast_revenue' => $avgRevenue];
        })->sortByDesc('forecast_revenue')->values();

        return ['categories' => $categories, 'channels' => $channels];
    }

    private static function trendGrowthPercent(array $values): float
    {
        $filtered = array_values(array_map('floatval', $values));
        if (count($filtered) < 2) {
            return 0.0;
        }

        $first = $filtered[0];
        $last = $filtered[count($filtered) - 1];

        if ($first <= 0) {
            return $last > 0 ? 100.0 : 0.0;
        }

        return (($last - $first) / $first) * 100;
    }

    /**
     * Платіжна відомість: проведені документи ZP (видача зарплати) по учасниках команди за період.
     *
     * @return array{
     *   dateFrom: string,
     *   dateTo: string,
     *   periodLabel: string,
     *   ledgerRows: \Illuminate\Support\Collection,
     *   detailLines: \Illuminate\Support\Collection,
     *   grandTotal: float,
     *   teamMemberCount: int
     * }
     */
    public static function teamPayrollLedger(string $fid, string $dateFromInput = '', string $dateToInput = ''): array
    {
        [$dateFromUi, $dateToUi, $dateFromLegacy, $dateToLegacy] = self::normalizePeriod($dateFromInput, $dateToInput);
        $periodLabel = self::periodLabel($dateFromUi, $dateToUi);

        $empty = [
            'dateFrom' => $dateFromUi,
            'dateTo' => $dateToUi,
            'periodLabel' => $periodLabel,
            'ledgerRows' => collect(),
            'detailLines' => collect(),
            'grandTotal' => 0.0,
            'teamMemberCount' => 0,
        ];

        if (! Schema::hasTable('document') || ! Schema::hasTable('users') || ! Schema::hasColumn('users', 'firmuser')) {
            return $empty;
        }

        $teamMembers = DB::table('users')
            ->where('firma', $fid)
            ->where('firmuser', '1')
            ->orderBy('secondname')
            ->orderBy('name')
            ->get(['id', 'name', 'secondname', 'fathername', 'orgname', 'name2']);

        if ($teamMembers->isEmpty()) {
            return $empty;
        }

        $teamIds = $teamMembers->pluck('id')->map(static fn ($id): string => (string) $id)->all();

        $totalsRows = DB::table('document')
            ->where('firma', $fid)
            ->where('type', 'ZP')
            ->where('provodka', 1)
            ->whereIn('client1', $teamIds)
            ->whereRaw(
                "STR_TO_DATE(data, '%d-%m-%Y') BETWEEN STR_TO_DATE(?, '%d-%m-%Y') AND STR_TO_DATE(?, '%d-%m-%Y')",
                [$dateFromLegacy, $dateToLegacy]
            )
            ->groupBy('client1')
            ->selectRaw('client1, SUM(summa) as total_paid, COUNT(*) as payment_count')
            ->get();

        $totalsByClient = [];
        foreach ($totalsRows as $row) {
            $totalsByClient[(string) $row->client1] = $row;
        }

        $ledgerRows = $teamMembers->map(function ($user) use ($totalsByClient) {
            $key = (string) $user->id;
            $agg = $totalsByClient[$key] ?? null;
            $fullName = trim(implode(' ', array_filter([
                $user->name ?? '',
                $user->secondname ?? '',
                $user->fathername ?? '',
            ])));
            if ($fullName === '') {
                $fullName = trim((string) ($user->orgname ?? ''));
            }
            if ($fullName === '') {
                $fullName = 'User #' . $user->id;
            }

            return (object) [
                'user_id' => $user->id,
                'full_name' => $fullName,
                'position' => trim((string) ($user->name2 ?? '')),
                'payment_count' => (int) ($agg->payment_count ?? 0),
                'total_paid' => (float) ($agg->total_paid ?? 0),
            ];
        })->values();

        $grandTotal = (float) $ledgerRows->sum('total_paid');

        $detailLines = DB::table('document as d')
            ->join('users as u', 'u.id', '=', 'd.client1')
            ->where('d.firma', $fid)
            ->where('u.firma', $fid)
            ->where('u.firmuser', '1')
            ->where('d.type', 'ZP')
            ->where('d.provodka', 1)
            ->whereIn('d.client1', $teamIds)
            ->whereRaw(
                "STR_TO_DATE(d.data, '%d-%m-%Y') BETWEEN STR_TO_DATE(?, '%d-%m-%Y') AND STR_TO_DATE(?, '%d-%m-%Y')",
                [$dateFromLegacy, $dateToLegacy]
            )
            ->orderByRaw("STR_TO_DATE(d.data, '%d-%m-%Y') ASC")
            ->orderBy('d.id')
            ->get([
                'd.id',
                'd.num',
                'd.data',
                'd.summa',
                'd.content',
                'd.oplata',
                'u.id as employee_id',
                DB::raw("TRIM(CONCAT(COALESCE(u.name,''),' ',COALESCE(u.secondname,''))) as employee_name"),
            ]);

        return [
            'dateFrom' => $dateFromUi,
            'dateTo' => $dateToUi,
            'periodLabel' => $periodLabel,
            'ledgerRows' => $ledgerRows,
            'detailLines' => $detailLines,
            'grandTotal' => $grandTotal,
            'teamMemberCount' => $teamMembers->count(),
        ];
    }

    private static function normalizePeriod(string $dateFromInput, string $dateToInput): array
    {
        $start = now()->startOfMonth();
        $end = now()->endOfMonth();

        if ($dateFromInput !== '') {
            try {
                $start = Carbon::createFromFormat('Y-m-d', $dateFromInput)->startOfDay();
            } catch (\Throwable) {
            }
        }

        if ($dateToInput !== '') {
            try {
                $end = Carbon::createFromFormat('Y-m-d', $dateToInput)->startOfDay();
            } catch (\Throwable) {
            }
        }

        if ($start->gt($end)) {
            [$start, $end] = [$end->copy(), $start->copy()];
        }

        return [
            $start->format('Y-m-d'),
            $end->format('Y-m-d'),
            $start->format('d-m-Y'),
            $end->format('d-m-Y'),
        ];
    }

    private static function periodLabel(string $dateFromUi, string $dateToUi): string
    {
        if ($dateFromUi === $dateToUi) {
            return $dateFromUi;
        }

        return $dateFromUi . ' - ' . $dateToUi;
    }

    private static function accountNet(string $type, float $debit, float $credit): float
    {
        return match ($type) {
            'asset', 'expense' => $debit - $credit,
            'liability', 'equity', 'income' => $credit - $debit,
            default => 0.0,
        };
    }
}
