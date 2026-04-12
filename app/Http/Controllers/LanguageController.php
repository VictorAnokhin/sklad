<?php

namespace App\Http\Controllers;

use App\Models\Field;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

class LanguageController extends Controller
{
    /**
     * Switch the application language and persist it in session.
     */
    public function switch(Request $request): RedirectResponse
    {
        $locale = Field::normalizeLocale($request->input('lang', 'ru'));
        
        // Store in session
        if ($request->hasSession()) {
            $request->session()->put('lang', $locale);
        }
        
        // Set application locale
        app()->setLocale($locale);
        
        // Redirect back
        return redirect()->back();
    }

    /**
     * Get current language information.
     */
    public function current(Request $request): array
    {
        return [
            'current' => app()->getLocale(),
            'session' => $request->hasSession() ? $request->session()->get('lang', null) : null,
            'supported' => ['ru', 'ua', 'en'],
        ];
    }
}
