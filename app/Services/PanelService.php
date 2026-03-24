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

        // Panel 1: sales staff (idstatus ≠ 2)
        if ($idstatus !== 2) {
            $salesTabs = [
                $this->makeTab('ZOUT', 'Замовлення', 'icon-order.png', $currentDoc),
                $this->makeTab('CH', 'Рахунки', 'icon-invoice.png', $currentDoc),
                $this->makeTab('RN', 'Накладні', 'icon-packing.png', $currentDoc),
                $this->makeTab('PO', 'Гроші', 'icon-business.png', $currentDoc),
                $this->makeTab('WO1', 'Наряди', 'icon-naryad.png', $currentDoc),
                $this->makeTab('RA', 'Файли', 'icon-attach-file.png', $currentDoc),
            ];
        }

        // Panel 2: managers/admins (idstatus > 2)
        if ($idstatus > 2) {
            $managerTabs = [
                $this->makeTab('ZIN', 'Закупки', 'icon-order.png', $currentDoc),
                $this->makeTab('PN', 'Прихід товару', 'icon-packing.png', $currentDoc),
                $this->makeTab('RO', 'Витрата грошей', 'icon-business.png', $currentDoc),
                $this->makeTab('VN', 'Повернення', 'icon-naryad.png', $currentDoc),
            ];
        }

        // Panel 3: production workers (idstatus == 2)
        if ($idstatus === 2) {
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
