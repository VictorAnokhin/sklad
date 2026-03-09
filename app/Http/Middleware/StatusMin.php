<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * StatusMin — gates routes by minimum idstatus level.
 * Replaces: if ($idstatus < 3) { header(...); exit; } scattered in admin/, sadmin2/.
 *
 * Usage in routes: ->middleware('status.min:3')
 */
class StatusMin
{
    public function handle(Request $request, Closure $next, int $min = 1)
    {
        if ((int)session('idstatus', 0) < $min) {
            abort(403, 'Недостатньо прав доступу');
        }
        return $next($request);
    }
}
