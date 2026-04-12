<?php

namespace App\Http\Controllers;

use App\Models\Field;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    protected function resolveBackendLocale(?Request $request = null): string
    {
        if ($request) {
            return $this->resolveApiLocale($request);
        }

        $locale = Field::normalizeLocale((string) session('lang', app()->getLocale()));
        app()->setLocale($locale);

        return $locale;
    }

    protected function resolveApiLocale(Request $request): string
    {
        // 1. Явный параметр в запросе — сохраняем в сессию
        $direct = trim((string) $request->input('lang', $request->input('locale', '')));
        if ($direct !== '') {
            $locale = Field::normalizeLocale($direct);
            if ($request->hasSession()) {
                $request->session()->put('lang', $locale);
            }
            app()->setLocale($locale);

            return $locale;
        }

        // 2. Сессия (если язык был выбран ранее)
        if ($request->hasSession() && $request->session()->has('lang')) {
            $sessionLocale = Field::normalizeLocale((string) $request->session()->get('lang'));
            app()->setLocale($sessionLocale);

            return $sessionLocale;
        }

        // 3. Accept-Language header
        $acceptLang = $request->header('Accept-Language', '');
        if ($acceptLang) {
            $primary = trim(explode(',', $acceptLang)[0] ?? '');
            if ($primary !== '') {
                $locale = Field::normalizeLocale($primary);
                app()->setLocale($locale);
                return $locale;
            }
        }

        // 4. Default
        return 'ru';
    }
}
