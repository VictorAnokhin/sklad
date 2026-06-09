<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SolanaRpcProxyController extends Controller
{
    public function proxy(Request $request)
    {
        $target = rtrim((string) env('SOLANA_RPC_URL', 'https://solana-rpc.publicnode.com'), '/');
        $payload = $request->all();

        if (!is_array($payload) || empty($payload['jsonrpc']) || empty($payload['method'])) {
            return response()->json([
                'jsonrpc' => '2.0',
                'error' => [
                    'code' => -32600,
                    'message' => 'Invalid Solana JSON-RPC request.',
                ],
                'id' => $payload['id'] ?? null,
            ], 400);
        }

        try {
            $response = Http::acceptJson()
                ->asJson()
                ->timeout(25)
                ->retry(2, 300)
                ->post($target, $payload);
        } catch (\Throwable $error) {
            Log::warning('Solana RPC proxy request failed.', [
                'target' => $target,
                'method' => $payload['method'] ?? null,
                'error' => $error->getMessage(),
            ]);

            return response()->json([
                'jsonrpc' => '2.0',
                'error' => [
                    'code' => -32000,
                    'message' => 'Solana RPC proxy request failed: '.$error->getMessage(),
                ],
                'id' => $payload['id'] ?? null,
            ], 502);
        }

        $body = $response->json();
        if (!is_array($body)) {
            return response($response->body(), $response->status())
                ->header('Content-Type', $response->header('Content-Type', 'application/json'));
        }

        return response()->json($body, $response->status());
    }
}
