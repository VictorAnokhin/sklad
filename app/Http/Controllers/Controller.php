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

    private const REGION_LOCALE_MAP = [
        'UA' => 'ua',
        'RU' => 'ru',
        'BY' => 'ru',
        'KZ' => 'ru',
        'KG' => 'ru',
        'AM' => 'ru',
        'TJ' => 'ru',
        'UZ' => 'ru',
        'MD' => 'ru',
    ];

    protected function resolveBackendLocale(?Request $request = null): string
    {
        if ($request) {
            return $this->resolveApiLocale($request);
        }

        return Field::normalizeLocale((string) session('lang', app()->getLocale()));
    }

    protected function resolveApiLocale(Request $request): string
    {
        $direct = trim((string) $request->input('lang', $request->input('locale', '')));
        if ($direct !== '') {
            $locale = Field::normalizeLocale($direct);
            if ($request->hasSession()) {
                $request->session()->put('lang', $locale);
                $request->session()->put('lang_manual', true);
            }
            app()->setLocale($locale);

            return $locale;
        }

        if ($request->hasSession() && $request->session()->get('lang_manual', false)) {
            $sessionLocale = trim((string) $request->session()->get('lang', ''));
            if ($sessionLocale !== '') {
                $locale = Field::normalizeLocale($sessionLocale);
                app()->setLocale($locale);

                return $locale;
            }
        }

        $detectedLocale = $this->detectLocaleByRegion($request)
            ?? $this->detectLocaleByAcceptLanguage($request)
            ?? 'ru';

        if ($request->hasSession()) {
            $request->session()->put('lang', $detectedLocale);
        }

        app()->setLocale($detectedLocale);

        return $detectedLocale;
    }

    private function detectLocaleByRegion(Request $request): ?string
    {
        $countryCode = trim((string) (
            $request->header('CF-IPCountry')
            ?? $request->header('X-Country-Code')
            ?? $request->header('X-Geo-Country')
            ?? $request->server('GEOIP_COUNTRY_CODE')
            ?? ''
        ));

        if ($countryCode === '') {
            return null;
        }

        $countryCode = strtoupper($countryCode);
        if (in_array($countryCode, ['XX', 'T1', 'A1', 'A2'], true)) {
            return null;
        }

        return self::REGION_LOCALE_MAP[$countryCode] ?? 'en';
    }

    private function detectLocaleByAcceptLanguage(Request $request): ?string
    {
        $header = trim((string) $request->header('Accept-Language', ''));
        if ($header === '') {
            return null;
        }

        $primary = trim(explode(',', $header)[0] ?? '');
        if ($primary === '') {
            return null;
        }

        return Field::normalizeLocale($primary);
    }
}
