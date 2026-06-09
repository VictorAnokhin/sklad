<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CctpProxyController extends Controller
{
    public function v2Messages(Request $request, string $domain)
    {
        $payload = $request->validate([
            'transactionHash' => ['required', 'regex:/^0x[a-fA-F0-9]{64}$/'],
            'network' => ['nullable', 'in:mainnet,testnet'],
        ]);

        if (!preg_match('/^\d{1,4}$/', $domain)) {
            return response()->json(['message' => 'Invalid CCTP source domain.'], 422);
        }

        $network = $payload['network'] ?? 'mainnet';
        $baseUrl = $network === 'testnet'
            ? 'https://iris-api-sandbox.circle.com'
            : 'https://iris-api.circle.com';

        $url = "{$baseUrl}/v2/messages/{$domain}";

        try {
            $response = Http::acceptJson()
                ->timeout(20)
                ->retry(2, 500)
                ->get($url, [
                    'transactionHash' => $payload['transactionHash'],
                ]);
        } catch (\Throwable $error) {
            Log::warning('Circle CCTP proxy request failed.', [
                'domain' => $domain,
                'network' => $network,
                'transactionHash' => $payload['transactionHash'],
                'error' => $error->getMessage(),
            ]);

            return response()->json([
                'message' => 'Circle CCTP proxy request failed.',
                'error' => $error->getMessage(),
            ], 502);
        }

        $body = $response->json();
        if (!is_array($body)) {
            $body = [
                'message' => 'Circle Iris returned a non-JSON response.',
                'body' => mb_substr($response->body(), 0, 500),
            ];
        }

        return response()->json($body, $response->status());
    }
}
