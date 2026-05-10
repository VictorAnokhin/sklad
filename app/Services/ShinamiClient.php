<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * JSON-RPC client for Shinami (zkProver, Gas Station). Keys must stay server-side.
 *
 * @see https://docs.shinami.com/api-docs/sui/wallet-services/zklogin-wallet-api
 * @see https://docs.shinami.com/api-docs/sui/gas-station/api
 */
class ShinamiClient
{
    public static function apiBase(): string
    {
        return rtrim((string) config('services.shinami.api_base', 'https://api.us1.shinami.com'), '/');
    }

    public static function walletApiKey(): ?string
    {
        $key = trim((string) config('services.shinami.wallet_access_key', ''));

        return $key !== '' ? $key : null;
    }

    public static function gasApiKey(): ?string
    {
        $key = trim((string) config('services.shinami.gas_access_key', ''));

        return $key !== '' ? $key : null;
    }

    /**
     * @param  array<int, mixed>  $params
     * @return array<string, mixed>
     */
    public static function zkProverRpc(string $method, array $params): array
    {
        $apiKey = self::walletApiKey();
        if ($apiKey === null) {
            throw new \RuntimeException('Shinami wallet access key is not configured (SHINAMI_WALLET_ACCESS_KEY).');
        }

        return self::postJsonRpc(self::apiBase().'/sui/zkprover/v1', $apiKey, $method, $params);
    }

    /**
     * @return array{txBytes: string, signature: string, txDigest: string, expireAtTime?: int}
     */
    public static function sponsorTransactionBlock(
        string $transactionKindBase64,
        string $sender,
        ?string $gasBudget = null,
        ?string $gasPrice = null,
    ): array {
        $apiKey = self::gasApiKey();
        if ($apiKey === null) {
            throw new \RuntimeException('Shinami gas access key is not configured (SHINAMI_GAS_ACCESS_KEY).');
        }

        // JSON-RPC must pass four positional args (see Shinami Gas Station curl template). Omitting
        // gasBudget/gasPrice as fewer array elements yields Invalid params (-32602).
        $params = [
            $transactionKindBase64,
            $sender,
            ($gasBudget !== null && $gasBudget !== '') ? $gasBudget : null,
            ($gasPrice !== null && $gasPrice !== '') ? $gasPrice : null,
        ];

        $json = self::postJsonRpc(self::apiBase().'/sui/gas/v1', $apiKey, 'gas_sponsorTransactionBlock', $params);

        if (! isset($json['txBytes'], $json['signature'])) {
            throw new \RuntimeException('Shinami gas station returned an unexpected payload.');
        }

        return [
            'txBytes' => (string) $json['txBytes'],
            'signature' => (string) $json['signature'],
            'txDigest' => (string) ($json['txDigest'] ?? ''),
            'expireAtTime' => isset($json['expireAtTime']) ? (int) $json['expireAtTime'] : 0,
        ];
    }

    /**
     * @param  array<int, mixed>  $params
     * @return array<string, mixed>
     */
    private static function postJsonRpc(string $url, string $apiKey, string $method, array $params): array
    {
        $body = [
            'jsonrpc' => '2.0',
            'method' => $method,
            'params' => $params,
            'id' => 1,
        ];

        try {
            $response = Http::acceptJson()
                ->withHeaders([
                    'X-Api-Key' => $apiKey,
                ])
                ->timeout(90)
                ->connectTimeout(15)
                ->post($url, $body);
        } catch (Throwable $e) {
            throw new \RuntimeException('Shinami request failed: '.$e->getMessage(), 0, $e);
        }

        $payload = $response->json();

        if (! is_array($payload)) {
            throw new \RuntimeException('Shinami returned non-JSON (HTTP '.$response->status().').');
        }

        if (isset($payload['error']) && is_array($payload['error'])) {
            $code = $payload['error']['code'] ?? null;
            $message = (string) ($payload['error']['message'] ?? 'Shinami JSON-RPC error');

            throw new \RuntimeException($message.' (code: '.json_encode($code).')');
        }

        if (! isset($payload['result'])) {
            throw new \RuntimeException('Shinami JSON-RPC response missing result.');
        }

        $result = $payload['result'];

        return is_array($result) ? $result : ['value' => $result];
    }
}
