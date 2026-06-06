<?php

namespace App\Http\ViewComposers;

use App\Models\Project;
use App\Models\User;
use Illuminate\View\View;

class SiteFooterComposer
{
    public function compose(View $view): void
    {
        $footer = (array) config('app.footer', []);

        $companyName = trim((string) ($footer['company_name'] ?? ''));
        if ($companyName === '') {
            $companyName = (string) config('app.name');
        }

        $view->with([
            'siteFooterCompanyName' => $companyName,
            'siteFooterProjectsCount' => Project::query()->count(),
            'siteFooterUsersCount' => User::query()->count(),
            'siteFooterPlatformUrl' => (string) ($footer['platform_url'] ?? 'https://autoagent.in.ua'),
            'siteFooterPlatformLabel' => (string) ($footer['platform_label'] ?? 'autoagent.in.ua'),
            'siteFooterBankUrl' => (string) ($footer['bank_url'] ?? 'https://av8.fund'),
            'siteFooterBankLabel' => (string) ($footer['bank_label'] ?? 'av8.fund'),
        ]);
    }
}
