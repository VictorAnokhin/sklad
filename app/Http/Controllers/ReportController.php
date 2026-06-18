<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Report;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $fid = (string) session('fid', '');
        $summary = Report::summary(
            $fid,
            (string) $request->input('date_from', ''),
            (string) $request->input('date_to', '')
        );

        if ($this->isBankProject($fid)) {
            return view('reports.bank_index', array_merge($summary, [
                'bankReportCards' => $this->bankReportCards(),
            ]));
        }

        return view('reports.index', $summary);
    }

    public function stocks(Request $request)
    {
        $fid = (string) session('fid', '');
        $data = Report::stockBalances(
            $fid,
            (string) $request->input('sklad', ''),
            (string) $request->input('q', ''),
            (string) $request->input('date_from', ''),
            (string) $request->input('date_to', ''),
            (string) $request->input('sort', 'product_name'),
            (string) $request->input('direction', 'asc')
        );

        return view('reports.stocks', $data);
    }

    public function sales(Request $request)
    {
        $fid = (string) session('fid', '');
        $data = Report::salesOperations(
            $fid,
            (string) $request->input('date_from', ''),
            (string) $request->input('date_to', '')
        );

        return view('reports.sales', $data);
    }

    public function abcXyz(Request $request)
    {
        $fid = (string) session('fid', '');
        $data = Report::abcXyzAnalysis(
            $fid,
            (string) $request->input('date_from', ''),
            (string) $request->input('date_to', '')
        );

        return view('reports.abcxyz', $data);
    }

    public function inventory(Request $request)
    {
        $fid = (string) session('fid', '');
        $data = Report::inventoryOperations(
            $fid,
            (string) $request->input('date_from', ''),
            (string) $request->input('date_to', ''),
            (string) $request->input('product_name', ''),
            (string) $request->input('product_code', ''),
            (string) $request->input('sklad', '')
        );

        return view('reports.inventory', $data);
    }

    public function turnover(Request $request)
    {
        $fid = (string) session('fid', '');
        $data = Report::turnoverOperations(
            $fid,
            (string) $request->input('date_from', ''),
            (string) $request->input('date_to', '')
        );

        return view('reports.turnover', $data);
    }

    public function purchases(Request $request)
    {
        $fid = (string) session('fid', '');
        $data = Report::purchaseOperations(
            $fid,
            (string) $request->input('date_from', ''),
            (string) $request->input('date_to', '')
        );

        return view('reports.purchases', $data);
    }

    public function pnlSegments(Request $request)
    {
        $fid = (string) session('fid', '');
        $data = Report::pnlSegments(
            $fid,
            (string) $request->input('date_from', ''),
            (string) $request->input('date_to', '')
        );

        return view('reports.pnl_segments', $data);
    }

    public function unitEconomics(Request $request)
    {
        $fid = (string) session('fid', '');
        $data = Report::unitEconomics(
            $fid,
            (string) $request->input('date_from', ''),
            (string) $request->input('date_to', '')
        );

        return view('reports.unit_economics', $data);
    }

    public function grossProfit(Request $request)
    {
        $fid = (string) session('fid', '');
        $data = Report::grossProfitAnalysis(
            $fid,
            (string) $request->input('date_from', ''),
            (string) $request->input('date_to', '')
        );

        return view('reports.gross_profit', $data);
    }

    public function financialPnl(Request $request)
    {
        $fid = (string) session('fid', '');
        $data = Report::financialPnl(
            $fid,
            (string) $request->input('date_from', ''),
            (string) $request->input('date_to', '')
        );

        return view('reports.financial_pnl', $data);
    }

    public function balanceSheet(Request $request)
    {
        $fid = (string) session('fid', '');
        $data = Report::balanceSheet(
            $fid,
            (string) $request->input('date_from', ''),
            (string) $request->input('date_to', '')
        );

        return view('reports.balance_sheet', $data);
    }

    public function cashFlowStatement(Request $request)
    {
        $fid = (string) session('fid', '');
        $data = Report::cashFlowStatement(
            $fid,
            (string) $request->input('date_from', ''),
            (string) $request->input('date_to', '')
        );

        return view('reports.cash_flow_statement', $data);
    }

    public function salesForecast(Request $request)
    {
        $fid = (string) session('fid', '');
        $data = Report::salesForecast(
            $fid,
            (string) $request->input('date_from', ''),
            (string) $request->input('date_to', '')
        );

        return view('reports.sales_forecast', $data);
    }

    public function purchasePlan(Request $request)
    {
        $fid = (string) session('fid', '');
        $data = Report::purchasePlan(
            $fid,
            (string) $request->input('date_from', ''),
            (string) $request->input('date_to', '')
        );

        return view('reports.purchase_plan', $data);
    }

    public function profitPlan(Request $request)
    {
        $fid = (string) session('fid', '');
        $data = Report::profitPlan(
            $fid,
            (string) $request->input('date_from', ''),
            (string) $request->input('date_to', '')
        );

        return view('reports.profit_plan', $data);
    }

    public function demandTrends(Request $request)
    {
        $fid = (string) session('fid', '');
        $data = Report::demandTrends(
            $fid,
            (string) $request->input('date_from', ''),
            (string) $request->input('date_to', '')
        );

        return view('reports.demand_trends', $data);
    }

    public function webchatActivity(Request $request)
    {
        $fid = (string) session('fid', '');
        $data = Report::webchatActivity(
            $fid,
            (string) $request->input('date_from', ''),
            (string) $request->input('date_to', '')
        );

        return view('reports.webchat_activity', $data);
    }

    public function trialBalance(Request $request)
    {
        $fid = (string) session('fid', '');
        $data = Report::trialBalance(
            $fid,
            (string) $request->input('date_from', ''),
            (string) $request->input('date_to', '')
        );

        return view('reports.trial_balance', $data);
    }

    public function journal(Request $request)
    {
        $fid = (string) session('fid', '');
        $data = Report::journal(
            $fid,
            (string) $request->input('date_from', ''),
            (string) $request->input('date_to', ''),
            (string) $request->input('account_id', '')
        );

        return view('reports.journal', $data);
    }

    public function strategicExport(Request $request, string $report, string $format)
    {
        $fid = (string) session('fid', '');
        $dateFrom = (string) $request->input('date_from', '');
        $dateTo = (string) $request->input('date_to', '');

        [$title, $rows] = match ($report) {
            'salesforecast' => $this->exportSalesForecast($fid, $dateFrom, $dateTo),
            'purchaseplan' => $this->exportPurchasePlan($fid, $dateFrom, $dateTo),
            'profitplan' => $this->exportProfitPlan($fid, $dateFrom, $dateTo),
            'demandtrends' => $this->exportDemandTrends($fid, $dateFrom, $dateTo),
            default => abort(404),
        };

        $format = strtolower($format);
        if (!in_array($format, ['csv', 'xls'], true)) {
            abort(404);
        }

        $filename = $report . '-' . now()->format('Y-m-d-His') . '.' . $format;

        if ($format === 'csv') {
            return response()->streamDownload(function () use ($rows) {
                $handle = fopen('php://output', 'w');
                fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
                foreach ($rows as $row) {
                    fputcsv($handle, $row, ';');
                }
                fclose($handle);
            }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
        }

        return response()->streamDownload(function () use ($title, $rows) {
            echo '<html><head><meta charset="UTF-8"></head><body>';
            echo '<table border="1">';
            echo '<tr><th colspan="' . count($rows[0] ?? [1]) . '">' . e($title) . '</th></tr>';
            foreach ($rows as $index => $row) {
                echo '<tr>';
                foreach ($row as $cell) {
                    $tag = $index === 0 ? 'th' : 'td';
                    echo '<' . $tag . '>' . e((string) $cell) . '</' . $tag . '>';
                }
                echo '</tr>';
            }
            echo '</table></body></html>';
        }, $filename, ['Content-Type' => 'application/vnd.ms-excel; charset=UTF-8']);
    }

    public function stocksExport(Request $request)
    {
        $fid = (string) session('fid', '');
        $data = Report::stockBalances(
            $fid,
            (string) $request->input('sklad', ''),
            (string) $request->input('q', ''),
            (string) $request->input('date_from', ''),
            (string) $request->input('date_to', ''),
            (string) $request->input('sort', 'product_name'),
            (string) $request->input('direction', 'asc'),
            true
        );

        $filename = 'stocks-report-' . now()->format('Y-m-d-His') . '.csv';
        $items = $data['items'];

        return response()->streamDownload(function () use ($items) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($handle, [
                'Товар',
                'Код',
                'Склад',
                'Залишок',
                'Середня собівартість',
                'Вартість залишку',
                'Продано, од.',
                'Виручка',
                'Собівартість',
                'Валовий прибуток',
                'Маржа %',
                'Гарантія',
            ], ';');

            foreach ($items as $item) {
                fputcsv($handle, [
                    $item->product_name,
                    $item->pnum,
                    $item->sklad_name,
                    number_format((float) $item->count, 3, '.', ''),
                    number_format((float) ($item->average_cost ?? 0), 6, '.', ''),
                    number_format((float) ($item->inventory_value ?? 0), 2, '.', ''),
                    number_format((float) ($item->sold_qty ?? 0), 3, '.', ''),
                    number_format((float) ($item->sold_sum ?? 0), 2, '.', ''),
                    number_format((float) ($item->estimated_cost ?? 0), 2, '.', ''),
                    number_format((float) ($item->gross_profit ?? 0), 2, '.', ''),
                    number_format((float) ($item->gross_margin ?? 0), 1, '.', ''),
                    $item->garant ?: '',
                ], ';');
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function finance(Request $request)
    {
        $fid = (string) session('fid', '');
        $period = (string) $request->input('period', 'month');
        [$dateFrom, $dateTo, $period] = $this->resolveFinancePeriod(
            $period,
            (string) $request->input('date_from', ''),
            (string) $request->input('date_to', '')
        );
        $data = Report::finance(
            $fid,
            $dateFrom,
            $dateTo,
            (string) $request->input('oplata', '')
        );

        return view('reports.finance', array_merge($data, [
            'periodFilter' => $period,
        ]));
    }

    private function resolveFinancePeriod(string $period, string $dateFromInput, string $dateToInput): array
    {
        $today = CarbonImmutable::today();
        $period = in_array($period, [
            'today',
            'yesterday',
            'week',
            'month',
            'same_month_last_year',
            'year',
            'manual',
        ], true) ? $period : 'month';

        [$start, $end] = match ($period) {
            'today' => [$today, $today],
            'yesterday' => [$today->subDay(), $today->subDay()],
            'week' => [$today->subDays(6), $today],
            'same_month_last_year' => [
                $today->subYear()->startOfMonth(),
                $today->subYear()->endOfMonth(),
            ],
            'year' => [$today->subDays(364), $today],
            'manual' => [
                $this->parseFinanceDate($dateFromInput, $today->startOfMonth()),
                $this->parseFinanceDate($dateToInput, $today),
            ],
            default => [$today->subDays(29), $today],
        };

        if ($start->gt($end)) {
            [$start, $end] = [$end, $start];
        }

        return [$start->format('Y-m-d'), $end->format('Y-m-d'), $period];
    }

    private function parseFinanceDate(string $value, CarbonImmutable $fallback): CarbonImmutable
    {
        if ($value === '') {
            return $fallback;
        }

        try {
            return CarbonImmutable::createFromFormat('Y-m-d', $value)->startOfDay();
        } catch (\Throwable) {
            return $fallback;
        }
    }

    private function isBankProject(string $fid): bool
    {
        if ($fid === '' || !Schema::hasTable('project') || !Schema::hasColumn('project', 'project_type')) {
            return false;
        }

        $projectType = Project::query()
            ->whereKey((int) $fid)
            ->value('project_type');

        return strtolower(trim((string) $projectType)) === 'bank';
    }

    private function bankReportCards(): array
    {
        return [
            [
                'title' => 'Ликвидность',
                'description' => 'Движение денег по кассам и счетам, поступления, списания и чистый денежный поток.',
                'url' => route('reports.finance'),
                'accent' => 'text-success',
            ],
            [
                'title' => 'Cash Flow',
                'description' => 'Денежные потоки по операционной, инвестиционной и финансовой активности.',
                'url' => route('reports.cashflowstmt'),
                'accent' => 'text-primary',
            ],
            [
                'title' => 'Баланс банка',
                'description' => 'Активы, обязательства и капитал на дату отчёта.',
                'url' => route('reports.balancesheet'),
                'accent' => 'text-info',
            ],
            [
                'title' => 'Оборотно-сальдовая ведомость',
                'description' => 'Обороты и остатки по счетам для контроля бухгалтерской модели.',
                'url' => route('reports.trialbalance'),
                'accent' => 'text-warning',
            ],
            [
                'title' => 'Журнал проводок',
                'description' => 'Детальный audit trail транзакций и проводок по счетам.',
                'url' => route('reports.journal'),
                'accent' => 'text-light',
            ],
            [
                'title' => 'Доходы / расходы',
                'description' => 'Финансовый результат периода и структура операционных расходов.',
                'url' => route('reports.financialpnl'),
                'accent' => 'text-danger',
            ],
        ];
    }

    private function exportSalesForecast(string $fid, string $dateFrom, string $dateTo): array
    {
        $data = Report::salesForecast($fid, $dateFrom, $dateTo);
        $rows = [
            ['Розділ', 'Показник', 'Значення'],
            ['Summary', 'Forecast виручки', number_format((float) $data['forecastRevenue'], 2, '.', '')],
            ['Summary', 'Forecast продажів', number_format((float) $data['forecastDocs'], 0, '.', '')],
            ['Summary', 'Forecast одиниць', number_format((float) $data['forecastQty'], 2, '.', '')],
            ['Summary', 'Факт виручки', number_format((float) $data['actualRevenue'], 2, '.', '')],
            ['Summary', 'План/факт виручки %', number_format((float) $data['planFactRevenuePercent'], 1, '.', '')],
            [''],
            ['Історія', 'Місяць', 'Виручка', 'Одиниць', 'Продажів'],
        ];

        foreach ($data['history'] as $item) {
            $rows[] = ['Історія', $item->month_label, number_format((float) $item->revenue, 2, '.', ''), number_format((float) $item->qty, 2, '.', ''), (string) $item->sales_docs];
        }

        $rows[] = [''];
        $rows[] = ['Сезонність', 'Місяць', 'Виручка', 'Одиниць'];
        foreach ($data['seasonality'] as $item) {
            $rows[] = ['Сезонність', $item->month_label, number_format((float) $item->revenue, 2, '.', ''), number_format((float) $item->qty, 2, '.', '')];
        }

        $rows[] = [''];
        $rows[] = ['Категорії', 'Сегмент', 'Forecast'];
        foreach ($data['categoryForecasts'] as $item) {
            $rows[] = ['Категорії', $item->segment_name, number_format((float) $item->forecast_revenue, 2, '.', '')];
        }

        $rows[] = [''];
        $rows[] = ['Канали', 'Сегмент', 'Forecast'];
        foreach ($data['channelForecasts'] as $item) {
            $rows[] = ['Канали', $item->segment_name, number_format((float) $item->forecast_revenue, 2, '.', '')];
        }

        return ['Стратегічний forecast продажів', $rows];
    }

    private function exportPurchasePlan(string $fid, string $dateFrom, string $dateTo): array
    {
        $data = Report::purchasePlan($fid, $dateFrom, $dateTo);
        $rows = [
            ['Товар', 'Код', 'Поточний залишок', 'Сер. попит / міс', 'Плановий попит', 'Докупить', 'Сума'],
        ];

        foreach ($data['items'] as $item) {
            $rows[] = [
                $item->product_name,
                $item->pnum,
                number_format((float) $item->current_stock, 2, '.', ''),
                number_format((float) $item->avg_monthly_qty, 2, '.', ''),
                number_format((float) $item->planned_demand, 2, '.', ''),
                number_format((float) $item->planned_purchase_qty, 2, '.', ''),
                number_format((float) $item->planned_purchase_sum, 2, '.', ''),
            ];
        }

        return ['План закупок', $rows];
    }

    private function exportProfitPlan(string $fid, string $dateFrom, string $dateTo): array
    {
        $data = Report::profitPlan($fid, $dateFrom, $dateTo);
        $rows = [
            ['Показник', 'План', 'Факт', 'План/факт %'],
            ['Виручка', number_format((float) $data['plannedRevenue'], 2, '.', ''), number_format((float) $data['actualRevenue'], 2, '.', ''), number_format((float) $data['planFactRevenuePercent'], 1, '.', '')],
            ['Валова прибуток', number_format((float) $data['plannedGrossProfit'], 2, '.', ''), number_format((float) $data['actualGrossProfit'], 2, '.', ''), ''],
            ['OPEX', number_format((float) $data['plannedOpex'], 2, '.', ''), number_format((float) $data['actualOpex'], 2, '.', ''), ''],
            ['Чиста прибуток', number_format((float) $data['plannedNetProfit'], 2, '.', ''), number_format((float) $data['actualNetProfit'], 2, '.', ''), number_format((float) $data['planFactNetProfitPercent'], 1, '.', '')],
        ];

        return ['План прибыли', $rows];
    }

    private function exportDemandTrends(string $fid, string $dateFrom, string $dateTo): array
    {
        $data = Report::demandTrends($fid, $dateFrom, $dateTo);
        $rows = [
            ['Товар', 'Код', 'Сер. попит', 'Зміна %', 'CV', 'Тренд'],
        ];

        foreach ($data['items'] as $item) {
            $rows[] = [
                $item->product_name,
                $item->pnum,
                number_format((float) $item->avg_qty, 2, '.', ''),
                number_format((float) $item->trend_growth, 1, '.', ''),
                number_format((float) $item->cv, 2, '.', ''),
                $item->trend_label,
            ];
        }

        $rows[] = [''];
        $rows[] = ['Сезонність', 'Місяць', 'Виручка', 'Одиниць'];
        foreach ($data['seasonality'] as $item) {
            $rows[] = ['Сезонність', $item->month_label, number_format((float) $item->revenue, 2, '.', ''), number_format((float) $item->qty, 2, '.', '')];
        }

        return ['Аналіз трендів попиту', $rows];
    }
}
