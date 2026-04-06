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
     * @return array{salesTabs: array, managerTabs: array, productionTabs: array}
     */
    public function getTabs(int $idstatus, string $currentDoc): array
    {
        $salesTabs = [];
        $managerTabs = [];
        $productionTabs = [];
        $salesDocs = ['ZOUT', 'CH', 'RN', 'PO', 'RA'];
        $managerDocs = ['ZIN', 'PN', 'RO', 'VN'];
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
                $this->makeTab('ZOUT', 'Замовлення', 'icon-order.png', $currentDoc),
                $this->makeTab('CH', 'Рахунки', 'icon-invoice.png', $currentDoc),
                $this->makeTab('RN', 'Накладні', 'icon-packing.png', $currentDoc),
                $this->makeTab('PO', 'Гроші', 'icon-business.png', $currentDoc),
                $this->makeTab('WO1', 'Наряди', 'icon-naryad.png', $currentDoc),
                $this->makeTab('RA', 'Файли', 'icon-attach-file.png', $currentDoc),
            ];
        }

        if ($isManagerContext || (!$hasExplicitContext && $idstatus > 2)) {
            $managerTabs = [
                $this->makeTab('ZIN', 'Закупки', 'icon-order.png', $currentDoc),
                $this->makeTab('PN', 'Прихід товару', 'icon-packing.png', $currentDoc),
                $this->makeTab('RO', 'Витрата грошей', 'icon-business.png', $currentDoc),
                $this->makeTab('VN', 'Повернення', 'icon-naryad.png', $currentDoc),
            ];
        }

        if ($isProductionContext || (!$hasExplicitContext && $idstatus === 2)) {
            $productionTabs = [
                $this->makeTextTab('WO1', 'Наряди', $currentDoc),
                $this->makeTextTab('SP', 'Специфікації', $currentDoc),
            ];
        }

        return compact('salesTabs', 'managerTabs', 'productionTabs');
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
}
