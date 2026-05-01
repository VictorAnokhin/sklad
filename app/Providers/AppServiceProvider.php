<?php

namespace App\Providers;

use App\Http\ViewComposers\PanelComposer;
use App\Models\Field;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Use View::composer with closure to ensure locale is resolved on every request
        View::composer('*', function ($view) {
            $view->with('currentBackendLocale', app()->getLocale());
        });

        View::composer(['partials.panel', 'partials.report_panel'], PanelComposer::class);

    }
}
