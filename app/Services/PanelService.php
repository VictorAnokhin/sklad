<?php

namespace App\Services;

/**
 * PanelService
 * Migrated from: resources/views/partials/panel.blade.php (@php block)
 *
 * Builds the navigation tab data for the document panel.
 */
class PanelService
{
    /**
     * Return tab groups visible for the given user status.
     *
     * @return array{salesTabs: array, managerTabs: array, productionTabs: array, reportTabs: array, investorReportTabs: array, financialReportTabs: array, strategicReportTabs: array}
     */
    public function getTabs(int $idstatus, string $currentDoc, string $currentReport = ''): array
    {
        $salesTabs = [];
        $managerTabs = [];
        $productionTabs = [];
        $reportTabs = [];
        $investorReportTabs = [];
        $financialReportTabs = [];
        $strategicReportTabs = [];
        $salesDocs = ['ZOUT', 'CH', 'RN', 'PO', 'RA'];
        $managerDocs = ['ZIN', 'PN', 'RO', 'PP', 'VN', 'ZP'];
        $productionDocs = ['WO1', 'SP'];

        $isSalesContext = in_array($currentDoc, $salesDocs, true);
        $isManagerContext = in_array($currentDoc, $managerDocs, true);
        $isProductionContext = in_array($currentDoc, $productionDocs, true);
        $hasExplicitContext = $isSalesContext || $isManagerContext || $isProductionContext;

        // If a document context is explicitly selected, render the matching panel
        // so the corresponding tab can be highlighted immediately.
        // Otherwise fall back to the user's current role/status.
        if ($isSalesContext || (!$hasExplicitContext && $idstatus !== 2 && $idstatus <= 2)) {
            $salesTabs = [
                $this->makeTab('ZOUT', __('document.tabs.orders'), 'icon-order.png', $currentDoc),
                $this->makeTab('CH', __('document.tabs.invoices'), 'icon-invoice.png', $currentDoc),
                $this->makeTab('RN', __('document.tabs.shipments'), 'icon-packing.png', $currentDoc),
                $this->makeTab('PO', __('document.tabs.money'), 'icon-business.png', $currentDoc),
                $this->makeTab('WO1', __('document.tabs.jobs'), 'icon-naryad.png', $currentDoc),
                $this->makeTab('RA', __('document.tabs.files'), 'icon-attach-file.png', $currentDoc),
            ];
        }

        if ($isManagerContext || (!$hasExplicitContext && $idstatus > 2)) {
            $managerTabs = [
                $this->makeTab('ZIN', __('document.tabs.purchases'), 'icon-order.png', $currentDoc),
                $this->makeTab('PN', __('document.tabs.goods_receipt'), 'icon-packing.png', $currentDoc),
                $this->makeTab('RO', __('document.tabs.money_expense'), 'icon-business.png', $currentDoc),
                $this->makeTab('ZP', __('document.tabs.salary_issue'), 'icon-business.png', $currentDoc),
                $this->makeTab('PP', __('document.tabs.deposits'), 'icon-wallet-income.png', $currentDoc),
                $this->makeTab('VN', __('document.tabs.returns'), 'icon-naryad.png', $currentDoc),
            ];
        }

        if ($isProductionContext || (!$hasExplicitContext && $idstatus === 2)) {
            $productionTabs = [
                $this->makeTextTab('WO1', __('document.tabs.jobs'), $currentDoc),
                $this->makeTextTab('SP', __('document.tabs.specifications'), $currentDoc),
            ];
        }

        if ($currentReport !== '') {
            $reportTabs = [
                $this->makeReportTab('summary', 'Зведення', $currentReport),
                $this->makeReportTab('sales', 'Продажі', $currentReport),
                $this->makeReportTab('abcxyz', 'ABC / XYZ', $currentReport),
                $this->makeReportTab('inventory', 'Остатки', $currentReport),
                $this->makeReportTab('turnover', 'Обіг', $currentReport),
                $this->makeReportTab('purchases', 'Закупки', $currentReport),
                $this->makeReportTab('stocks', 'Товарні залишки', $currentReport),
                $this->makeReportTab('finance', 'Фінанси', $currentReport),
            ];

            $investorReportTabs = [
                $this->makeReportTab('pnlsegments', 'P&L сегменти', $currentReport),
                $this->makeReportTab('uniteconomics', 'Unit-економіка', $currentReport),
                $this->makeReportTab('grossprofit', 'Валова прибуток', $currentReport),
            ];

            $financialReportTabs = [
                $this->makeReportTab('financialpnl', 'P&L', $currentReport),
                $this->makeReportTab('balancesheet', 'Баланс', $currentReport),
                $this->makeReportTab('cashflowstmt', 'Cash Flow', $currentReport),
                $this->makeReportTab('trialbalance', 'Оборотка', $currentReport),
                $this->makeReportTab('journal', 'Журнал проводок', $currentReport),
            ];

            $strategicReportTabs = [
                $this->makeReportTab('salesforecast', 'Forecast', $currentReport),
                $this->makeReportTab('purchaseplan', 'План закупок', $currentReport),
                $this->makeReportTab('profitplan', 'План прибыли', $currentReport),
                $this->makeReportTab('demandtrends', 'Тренды спроса', $currentReport),
            ];
        }

        return compact('salesTabs', 'managerTabs', 'productionTabs', 'reportTabs', 'investorReportTabs', 'financialReportTabs', 'strategicReportTabs');
    }

    /**
     * Build an icon tab entry.
     */
    private function makeTab(string $doc, string $label, string $icon, string $currentDoc): array
    {
        return [
            'doc'    => $doc,
            'label'  => $label,
            'icon'   => $icon,
            'url'    => route('document.index', ['doc' => $doc, 'num' => 0]),
            'active' => $doc === $currentDoc,
        ];
    }

    /**
     * Build a text-only tab entry (production panel).
     */
    private function makeTextTab(string $doc, string $label, string $currentDoc): array
    {
        return [
            'doc'    => $doc,
            'label'  => $label,
            'url'    => route('document.index', ['doc' => $doc, 'num' => 0]),
            'active' => $doc === $currentDoc,
        ];
    }

    private function makeReportTab(string $report, string $label, string $currentReport): array
    {
        return [
            'report' => $report,
            'label' => $label,
            'url' => match ($report) {
                'sales' => route('reports.sales'),
                'abcxyz' => route('reports.abcxyz'),
                'inventory' => route('reports.inventory'),
                'turnover' => route('reports.turnover'),
                'purchases' => route('reports.purchases'),
                'stocks' => route('reports.stocks'),
                'finance' => route('reports.finance'),
                'pnlsegments' => route('reports.pnlsegments'),
                'uniteconomics' => route('reports.uniteconomics'),
                'grossprofit' => route('reports.grossprofit'),
                'financialpnl' => route('reports.financialpnl'),
                'balancesheet' => route('reports.balancesheet'),
                'cashflowstmt' => route('reports.cashflowstmt'),
                'trialbalance' => route('reports.trialbalance'),
                'journal' => route('reports.journal'),
                'salesforecast' => route('reports.salesforecast'),
                'purchaseplan' => route('reports.purchaseplan'),
                'profitplan' => route('reports.profitplan'),
                'demandtrends' => route('reports.demandtrends'),
                default => route('reports.index'),
            },
            'active' => $report === $currentReport,
        ];
    }
}
