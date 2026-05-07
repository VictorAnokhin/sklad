<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SyncLegacySessionFromAuth
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check()) {
            $user = Auth::user();
            if ($user instanceof User) {
                self::applyWorkspaceSession($user);
            }
        }

        return $next($request);
    }

    /**
     * Оновлює legacy-ключі сесії (userid, fid, …) відповідно до поточного User та активного проєкту.
     */
    public static function applyWorkspaceSession(User $user): void
    {
        $defaultFirma = $user->firma ?: $user->fid ?: 0;
        $firma = session()->has('fid') ? session('fid') : $defaultFirma;
        $idstatus = $user->idstatus ?: $user->ustype ?: 1;

        session([
            'id' => $user->id,
            'fid' => $firma,
            'userid' => $user->id,
            'idstatus' => $idstatus,
            'status' => $idstatus,
            'doc' => (int) $idstatus === 2 ? 'WO1' : 'ZOUT',
            'idkassa' => $user->idkassa,
            'idsklad' => $user->idsklad,
            'idreestr' => $user->idreestr,
            'domen' => $user->domen,
            'bonus' => $user->bonus,
            'balans' => $user->balans,
            'name1' => $user->name,
            'fname' => $user->fathername,
            'login' => method_exists($user, 'legacyLoginValue') ? $user->legacyLoginValue() : ($user->login ?? $user->email ?? $user->phone ?? ''),
        ]);
    }
}
