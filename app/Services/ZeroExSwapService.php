<?php

namespace App\Services;

use App\Models\Conf;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class ZeroExSwapService
{
    private const BASE_URL = 'https://api.0x.org';

    public function price(array $params): array
    {
        return $this->request('/swap/allowance-holder/price', $this->normalizeParams($params));
    }

    public function quote(array $params): array
    {
        return $this->request('/swap/allowance-holder/quote', $this->normalizeParams($params));
    }

    private function request(string $path, array $params): array
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('0x API key is not configured.');
        }

        $response = $this->http()
            ->get($path, $params)
            ->throw()
            ->json();

        if (! is_array($response)) {
            throw new RuntimeException('Invalid 0x API response.');
        }

        return $response;
    }

    private function normalizeParams(array $params): array
    {
        $chainId = Conf::normalizeWeb3ChainIdToDecimalString($params['chainId'] ?? null);

        if ($chainId === null || $chainId === 'solana') {
            throw new RuntimeException('Unsupported chain for swap.');
        }

        return array_filter([
            'chainId' => $chainId,
            'sellToken' => trim((string) ($params['sellToken'] ?? '')),
            'buyToken' => trim((string) ($params['buyToken'] ?? '')),
            'sellAmount' => trim((string) ($params['sellAmount'] ?? '')),
            'taker' => strtolower(trim((string) ($params['taker'] ?? ''))),
            'slippageBps' => trim((string) ($params['slippageBps'] ?? '100')),
        ], fn ($value) => $value !== '');
    }

    private function http(): PendingRequest
    {
        return Http::baseUrl(self::BASE_URL)
            ->acceptJson()
            ->timeout(20)
            ->connectTimeout(10)
            ->withHeaders([
                '0x-api-key' => (string) config('services.zerox.api_key', ''),
                '0x-version' => 'v2',
            ]);
    }

    private function isConfigured(): bool
    {
        return trim((string) config('services.zerox.api_key', '')) !== '';
    }
}
