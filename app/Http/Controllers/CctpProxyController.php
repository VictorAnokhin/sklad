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
        $proxyUrl = trim((string) config('services.circle_cctp.proxy_url', ''));

        try {
            $request = Http::acceptJson()
                ->timeout(20)
                ->connectTimeout(10)
                ->retry(2, 500, throw: false);

            if ($proxyUrl !== '') {
                $request = $request->withOptions(['proxy' => $proxyUrl]);
            }

            $response = $request->get($url, [
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
                'message' => $response->successful()
                    ? 'Circle Iris returned a non-JSON response.'
                    : 'Circle Iris returned an HTTP error.',
                'body' => mb_substr($response->body(), 0, 500),
            ];
        }

        if ($response->failed()) {
            Log::warning('Circle CCTP upstream returned an error.', [
                'domain' => $domain,
                'network' => $network,
                'transactionHash' => $payload['transactionHash'],
                'status' => $response->status(),
                'body' => mb_substr($response->body(), 0, 500),
            ]);
        }

        return response()->json($body, $response->status());
    }
}
