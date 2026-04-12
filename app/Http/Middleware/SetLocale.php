<?php

namespace App\Http\Middleware;

use App\Models\Field;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Приоритет 1: Явный параметр в запросе (?lang=ua)
        $explicitLocale = trim((string) $request->input('lang', ''));

        if ($explicitLocale !== '') {
            $locale = Field::normalizeLocale($explicitLocale);
        }
        // Приоритет 2: Cookie lang_locale
        elseif ($request->hasCookie('lang_locale')) {
            $locale = Field::normalizeLocale((string) $request->cookie('lang_locale'));
        }
        // Приоритет 3: Сессия
        elseif ($request->hasSession() && $request->session()->has('lang')) {
            $locale = Field::normalizeLocale((string) $request->session()->get('lang'));
        }
        // Приоритет 4: Accept-Language header
        elseif ($request->header('Accept-Language')) {
            $primary = trim(explode(',', $request->header('Accept-Language'))[0] ?? '');
            $locale = $primary !== '' ? Field::normalizeLocale($primary) : 'ru';
        }
        // Приоритет 5: Default
        else {
            $locale = 'ru';
        }

        // Устанавливаем locale ДО рендера view
        app()->setLocale($locale);

        // Сохраняем в сессию
        if ($request->hasSession()) {
            $request->session()->put('lang', $locale);
        }

        $response = $next($request);

        // Устанавливаем cookie на ответе (только если явно указан язык или cookie еще нет)
        if ($explicitLocale !== '' || !$request->hasCookie('lang_locale')) {
            $response->headers->setCookie(
                \Symfony\Component\HttpFoundation\Cookie::create(
                    'lang_locale',
                    $locale,
                    time() + 31536000, // 1 year
                    '/',
                    null,
                    false,
                    false,
                    false,
                    'lax'
                )
            );
        }

        return $response;
    }
}
