<?php

namespace App\Services;

use App\Models\Conf;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class OneInchSwapService
{
    private const BASE_URL = 'https://api.1inch.com';

    public function quote(array $params): array
    {
        $normalized = $this->normalizeParams($params);

        return $this->request($normalized['chain_id'], '/quote', [
            'src' => $normalized['src'],
            'dst' => $normalized['dst'],
            'amount' => $normalized['amount'],
            'fee' => $normalized['fee'],
            'referrer' => $normalized['referrer'],
            'includeTokensInfo' => 'true',
            'includeGas' => 'true',
        ]);
    }

    public function swap(array $params): array
    {
        $normalized = $this->normalizeParams($params);

        return $this->request($normalized['chain_id'], '/swap', [
            'src' => $normalized['src'],
            'dst' => $normalized['dst'],
            'amount' => $normalized['amount'],
            'from' => $normalized['wallet_address'],
            'origin' => $normalized['wallet_address'],
            'slippage' => $normalized['slippage'],
            'fee' => $normalized['fee'],
            'referrer' => $normalized['referrer'],
            'includeTokensInfo' => 'true',
            'includeProtocols' => 'true',
            'includeGas' => 'true',
        ]);
    }

    public function allowance(array $params): array
    {
        $normalized = $this->normalizeParams($params);

        return $this->request($normalized['chain_id'], '/approve/allowance', [
            'tokenAddress' => $normalized['src'],
            'walletAddress' => $normalized['wallet_address'],
        ]);
    }

    public function approveTransaction(array $params): array
    {
        $normalized = $this->normalizeParams($params);

        return $this->request($normalized['chain_id'], '/approve/transaction', [
            'tokenAddress' => $normalized['src'],
            'amount' => $normalized['amount'],
        ]);
    }

    private function request(string $chainId, string $path, array $params): array
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('1inch API key is not configured.');
        }

        $response = $this->http()
            ->get("/swap/v6.1/{$chainId}{$path}", array_filter($params, fn ($value) => $value !== null && $value !== ''))
            ->throw()
            ->json();

        if (! is_array($response)) {
            throw new RuntimeException('Invalid 1inch API response.');
        }

        return $response;
    }

    private function normalizeParams(array $params): array
    {
        $chainId = Conf::normalizeWeb3ChainIdToDecimalString($params['chain_id'] ?? null);

        if ($chainId === null || $chainId === 'solana') {
            throw new RuntimeException('Unsupported chain for 1inch swap.');
        }

        $fee = $this->normalizeFeePercent($params['fee'] ?? 0);
        $referrer = strtolower(trim((string) config('services.oneinch.referrer', '')));
        $canUseFee = $fee > 0 && $referrer !== '';

        return [
            'chain_id' => $chainId,
            'src' => strtolower(trim((string) ($params['src'] ?? ''))),
            'dst' => strtolower(trim((string) ($params['dst'] ?? ''))),
            'amount' => trim((string) ($params['amount'] ?? '')),
            'wallet_address' => strtolower(trim((string) ($params['wallet_address'] ?? ''))),
            'slippage' => trim((string) ($params['slippage'] ?? '1')),
            'fee' => $canUseFee ? $this->trimFloat($fee) : null,
            'referrer' => $canUseFee ? $referrer : null,
        ];
    }

    private function normalizeFeePercent(mixed $value): float
    {
        if (is_string($value)) {
            $value = str_replace(',', '.', trim($value));
        }

        $fee = (float) $value;
        if ($fee < 0) {
            return 0.0;
        }

        return min($fee, 3.0);
    }

    private function trimFloat(float $value): string
    {
        $formatted = number_format($value, 4, '.', '');

        return rtrim(rtrim($formatted, '0'), '.');
    }

    private function http(): PendingRequest
    {
        return Http::baseUrl(self::BASE_URL)
            ->acceptJson()
            ->timeout(20)
            ->connectTimeout(10)
            ->withHeaders([
                'Authorization' => 'Bearer ' . trim((string) config('services.oneinch.api_key', '')),
            ]);
    }

    private function isConfigured(): bool
    {
        return trim((string) config('services.oneinch.api_key', '')) !== '';
    }
}
