<?php

namespace App\Http\ViewComposers;

use App\Services\PanelService;
use Illuminate\View\View;

/**
 * PanelComposer
 *
 * Automatically attaches panel tab data every time
 * the 'partials.panel' view is rendered via @include.
 */
class PanelComposer
{
    public function __construct(private PanelService $panelService)
    {
    }

    public function compose(View $view): void
    {
        $idstatus = (int) session('idstatus', 0);
        $viewDoc = $view->getData()['doc'] ?? null;
        $currentDoc = is_string($viewDoc) && $viewDoc !== ''
            ? $viewDoc
            : (string) request()->input('doc', session('doc', ''));
        $num = session('num', '0');
        $currentReport = match (true) {
            request()->routeIs('reports.sales') => 'sales',
            request()->routeIs('reports.abcxyz') => 'abcxyz',
            request()->routeIs('reports.inventory') => 'inventory',
            request()->routeIs('reports.turnover') => 'turnover',
            request()->routeIs('reports.purchases') => 'purchases',
            request()->routeIs('reports.stocks') => 'stocks',
            request()->routeIs('reports.finance') => 'finance',
            request()->routeIs('reports.pnlsegments') => 'pnlsegments',
            request()->routeIs('reports.uniteconomics') => 'uniteconomics',
            request()->routeIs('reports.grossprofit') => 'grossprofit',
            request()->routeIs('reports.financialpnl') => 'financialpnl',
            request()->routeIs('reports.balancesheet') => 'balancesheet',
            request()->routeIs('reports.cashflowstmt') => 'cashflowstmt',
            request()->routeIs('reports.trialbalance') => 'trialbalance',
            request()->routeIs('reports.journal') => 'journal',
            request()->routeIs('reports.salesforecast') => 'salesforecast',
            request()->routeIs('reports.purchaseplan') => 'purchaseplan',
            request()->routeIs('reports.profitplan') => 'profitplan',
            request()->routeIs('reports.demandtrends') => 'demandtrends',
            request()->routeIs('reports.webchatactivity') => 'webchatactivity',
            request()->routeIs('reports.index') => 'summary',
            default => '',
        };

        $tabs = $this->panelService->getTabs($idstatus, $currentDoc, $currentReport);

        $view->with([
            'idstatus'       => $idstatus,
            'num'            => $num,
            'salesTabs'      => $tabs['salesTabs'],
            'managerTabs'    => $tabs['managerTabs'],
            'productionTabs' => $tabs['productionTabs'],
            'reportTabs'     => $tabs['reportTabs'],
            'investorReportTabs' => $tabs['investorReportTabs'],
            'financialReportTabs' => $tabs['financialReportTabs'],
            'strategicReportTabs' => $tabs['strategicReportTabs'],
        ]);
    }
}
