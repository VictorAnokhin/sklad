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
        $request = request();
        $session = $request->hasSession() ? $request->session() : null;

        $locale = 'ru';
        if ($request->query('lang')) {
            $locale = Field::normalizeLocale((string) $request->query('lang'));
            if ($session) {
                $session->put('lang', $locale);
            }
        } elseif ($session && $session->has('lang')) {
            $locale = Field::normalizeLocale((string) $session->get('lang'));
        } elseif ($request->header('Accept-Language')) {
            $primary = trim(explode(',', (string) $request->header('Accept-Language'))[0] ?? '');
            $locale = Field::normalizeLocale($primary);
            if ($session) {
                $session->put('lang', $locale);
            }
        }

        App::setLocale($locale);
        View::share('currentBackendLocale', $locale);

        View::composer(['partials.panel', 'partials.report_panel'], PanelComposer::class);
    }
}
