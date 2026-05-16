<?php namespace App\Http\Middleware;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;
class VerifyCsrfToken extends Middleware {
    protected $except = [
        'sanctum/csrf-cookie',
        'login/google',
        'api/auth/login',
        'api/auth/google',
        'api/auth/logout',
        'api/auth/register',
        'api/auth/phone/send-code',
        'api/auth/phone/verify',
        'api/auth/web3/challenge',
        'api/auth/web3/login',
        'api/goods/rating/*',
        'api/goods/*/rating',
        'api/order',
        'api/ai/*',
    ];
}
