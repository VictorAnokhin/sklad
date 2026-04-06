<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SyncLegacySessionFromAuth
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check()) {
            $user = Auth::user();
            $firma = $user->firma ?: $user->fid ?: $user->idfirma ?: 0;
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

        return $next($request);
    }
}
