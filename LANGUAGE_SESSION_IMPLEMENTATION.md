# Language Session Implementation

## Overview
Language preference is stored in **session** during user interaction. No cookies, no GeoIP detection.

## Supported Languages
- `ru` — Russian (default)
- `ua` — Ukrainian
- `en` — English

## How It Works

### Language Switching
User clicks language link in header → URL gets `?lang=ua` parameter → Middleware reads it → Stores in session → Redirects to same page without `lang` param.

### Resolution Priority
1. **Request parameter** (`?lang=ua`) — saves to session
2. **Session** (`session('lang')`) — previously selected language
3. **Accept-Language** browser header — first visit only
4. **Default** — `ru`

### Session Key
- `lang` — current language code (`ru`, `ua`, `en`)

## Files Modified

| File | Change |
|------|--------|
| `app/Http/Middleware/SetLocale.php` | Simplified: request param → session → Accept-Language → default |
| `app/Http/Controllers/LanguageController.php` | Simplified: session only, no cookies |
| `app/Http/Controllers/Controller.php` | Removed GeoIP, REGION_LOCALE_MAP, cookie references |
| `app/Providers/AppServiceProvider.php` | Uses `app()->getLocale()` set by middleware |
| `resources/views/partials/top_reklama.blade.php` | GET links with `?lang=`, reads `session('lang')` |

## Testing

1. Open application (default: RU)
2. Click **UA** in header
3. Navigate to any page — language should stay **UA**
4. Refresh page — language should stay **UA**
5. Close browser — session expires, language resets to RU on next visit

## Note
Language persists **only during browser session**. Closing browser clears the preference.
