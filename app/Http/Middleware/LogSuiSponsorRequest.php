<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LogSuiSponsorRequest
{
    public function handle(Request $request, Closure $next): Response
    {
        $startedAt = microtime(true);

        Log::info('Sui gas sponsorship HTTP request received.', [
            'method' => $request->method(),
            'path' => $request->path(),
            'has_bearer_token' => $request->bearerToken() !== null,
            'sender' => is_string($request->input('sender')) ? $request->input('sender') : null,
            'tx_kind_base64_len' => is_string($request->input('transactionKindBase64'))
                ? strlen($request->input('transactionKindBase64'))
                : null,
        ]);

        $response = $next($request);

        Log::info('Sui gas sponsorship HTTP request finished.', [
            'status' => $response->getStatusCode(),
            'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
        ]);

        return $response;
    }
}
