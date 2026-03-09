<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * WorkspaceAuth
 * Replaces: include '../login.php' at top of every file.
 *
 * Legacy check was:
 *   if (!isset($_SESSION['login'])) { header('Location:/'); exit; }
 */
class WorkspaceAuth
{
    public function handle(Request $request, Closure $next)
    {
        if (!session()->has('login') || session('login') === '') {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Unauthenticated'], 401);
            }
            return redirect()->route('login');
        }

        return $next($request);
    }
}
