<?php namespace App\Http\Middleware;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;
class VerifyCsrfToken extends Middleware { 
    protected $except = [
        'sanctum/csrf-cookie',
        'api/auth/login',
        'api/auth/logout',
        'api/auth/register',
        'api/auth/web3/challenge',
        'api/auth/web3/login',
        'api/order',
    ]; 
}
