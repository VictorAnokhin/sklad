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
        $currentDoc = session('doc', '');
        $num = session('num', '0');

        $tabs = $this->panelService->getTabs($idstatus, $currentDoc);

        $view->with([
            'idstatus'       => $idstatus,
            'num'            => $num,
            'salesTabs'      => $tabs['salesTabs'],
            'managerTabs'    => $tabs['managerTabs'],
            'productionTabs' => $tabs['productionTabs'],
        ]);
    }
}
