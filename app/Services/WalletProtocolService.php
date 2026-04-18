<?php

namespace App\Services;

use App\Models\Conf;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class WalletProtocolService
{
    private const SOLANA_RPC_ENDPOINT = 'https://api.mainnet-beta.solana.com';

    private const RPC_ENDPOINTS = [
        '0x1' => 'https://cloudflare-eth.com',
        '0x38' => 'https://bsc-dataseed.bnbchain.org',
        '0x89' => 'https://polygon.drpc.org',
        '0xa4b1' => 'https://arb1.arbitrum.io/rpc',
        '0xa' => 'https://mainnet.optimism.io',
        '0x2105' => 'https://mainnet.base.org',
        '0xa86a' => 'https://api.avax.network/ext/bc/C/rpc',
    ];

    private const NATIVE_ASSETS = [
        '0x1' => ['symbol' => 'ETH', 'name' => 'Ethereum', 'decimals' => 18],
        '0x38' => ['symbol' => 'BNB', 'name' => 'BNB', 'decimals' => 18],
        '0x89' => ['symbol' => 'POL', 'name' => 'Polygon', 'decimals' => 18],
        '0xa4b1' => ['symbol' => 'ETH', 'name' => 'Ethereum', 'decimals' => 18],
        '0xa' => ['symbol' => 'ETH', 'name' => 'Ethereum', 'decimals' => 18],
        '0x2105' => ['symbol' => 'ETH', 'name' => 'Ethereum', 'decimals' => 18],
        '0xa86a' => ['symbol' => 'AVAX', 'name' => 'Avalanche', 'decimals' => 18],
        'solana' => ['symbol' => 'SOL', 'name' => 'Solana', 'decimals' => 9],
    ];

    private const AAVE_SUBGRAPH_IDS = [
        '0x1' => 'Cd2gEDVeqnjBn1hSeqFMitw8Q1iiyV9FYUZkLNRcL87g',
        '0x89' => 'Co2URyXjnxaw8WqxKyVHdirq9Ahhm5vcTs4dMedAq211',
        '0xa86a' => '2h9woxy8RTjHu1HJsCEnmzpPHFArU33avmUh4f71JpVn',
        '0xa4b1' => 'DLuE98kEb5pQNXAcKFQGQgfSQ57Xdou4jnVbAEqMfy3B',
        '0xa' => 'DSfLz8oQBUeU5atALgUFQKMTSYV9mZAVYp4noLSXAfvb',
        '0x38' => '7Jk85XgkV1MQ7u56hD8rr65rfASbayJXopugWkUoBMnZ',
        '0x2105' => 'GQFbb95cE6d8mV989mL5figjaGaKCQB3xqYrr1bRyXqF',
    ];

    public function load(string $address, string|int|null $chainId): array
    {
        $normalizedChainId = $this->normalizeWalletChainId($chainId);
        $normalizedAddress = $this->normalizeWalletAddress($address, $normalizedChainId);

        return [
            'aave' => $normalizedChainId === 'solana'
                ? $this->emptyProtocolPayload('Aave')
                : $this->fetchAave($normalizedAddress, $normalizedChainId),
            'gmx' => $this->emptyProtocolPayload('GMX'),
        ];
    }

    public function loadAssets(string $address, string|int|null $chainId, array $configuredTokens = []): array
    {
        $normalizedChainId = $this->normalizeWalletChainId($chainId);
        $normalizedAddress = $this->normalizeWalletAddress($address, $normalizedChainId);

        if ($normalizedChainId === 'solana') {
            return $this->loadSolanaAssets($normalizedAddress, $configuredTokens);
        }

        $rpcUrl = self::RPC_ENDPOINTS[$normalizedChainId] ?? null;

        if (!$rpcUrl) {
            return [
                'available' => false,
                'address' => $normalizedAddress,
                'chain_id' => $normalizedChainId,
                'assets' => [],
                'error' => 'Unsupported network',
            ];
        }

        $assets = [];
        $nativeAsset = self::NATIVE_ASSETS[$normalizedChainId] ?? self::NATIVE_ASSETS['0x1'];
        $nativeBalanceHex = $this->rpcCall($rpcUrl, 'eth_getBalance', [$normalizedAddress, 'latest']);

        $assets[] = [
            'symbol' => $nativeAsset['symbol'],
            'name' => $nativeAsset['name'],
            'address' => null,
            'decimals' => $nativeAsset['decimals'],
            'balance' => $this->normalizeHexTokenAmount($nativeBalanceHex, $nativeAsset['decimals']),
            'is_native' => true,
        ];

        foreach ($configuredTokens as $token) {
            $tokenChainId = Conf::normalizeWeb3ChainIdToHex($token->vision ?? null);
            $tokenAddress = strtolower(trim((string) ($token->color ?? '')));

            if ($tokenChainId !== $normalizedChainId || !preg_match('/^0x[0-9a-f]{40}$/', $tokenAddress)) {
                continue;
            }

            $callData = '0x70a08231' . str_pad(substr($normalizedAddress, 2), 64, '0', STR_PAD_LEFT);
            $balanceHex = $this->rpcCall($rpcUrl, 'eth_call', [[
                'to' => $tokenAddress,
                'data' => $callData,
            ], 'latest']);

            $decimals = max(0, (int) ($token->status ?? 18));
            $assets[] = [
                'symbol' => (string) ($token->name ?? 'TOKEN'),
                'name' => (string) (($token->doc ?? '') !== '' ? $token->doc : ($token->name ?? 'Token')),
                'address' => $tokenAddress,
                'decimals' => $decimals,
                'balance' => $this->normalizeHexTokenAmount($balanceHex, $decimals),
                'is_native' => false,
                'coingecko_id' => ($token->constanta ?? '0') !== '0' ? $token->constanta : null,
            ];
        }

        return [
            'available' => true,
            'address' => $normalizedAddress,
            'chain_id' => $normalizedChainId,
            'assets' => $assets,
            'error' => null,
        ];
    }

    private function loadSolanaAssets(string $address, array $configuredTokens = []): array
    {
        if (!$this->isValidSolanaAddress($address)) {
            return [
                'available' => false,
                'address' => $address,
                'chain_id' => 'solana',
                'assets' => [],
                'error' => 'Unsupported wallet address',
            ];
        }

        $assets = [];
        $nativeAsset = self::NATIVE_ASSETS['solana'];
        $nativeBalance = $this->solanaRpcCall('getBalance', [
            $address,
            ['commitment' => 'confirmed'],
        ]);

        $assets[] = [
            'symbol' => $nativeAsset['symbol'],
            'name' => $nativeAsset['name'],
            'address' => null,
            'decimals' => $nativeAsset['decimals'],
            'balance' => $this->normalizeTokenAmount((string) data_get($nativeBalance, 'value', 0), $nativeAsset['decimals']),
            'is_native' => true,
            'coingecko_id' => 'solana',
        ];

        foreach ($configuredTokens as $token) {
            $tokenChainId = strtolower(trim((string) ($token->vision ?? '')));
            $tokenAddress = trim((string) ($token->color ?? ''));

            if ($tokenChainId !== 'solana' || !$this->isValidSolanaAddress($tokenAddress)) {
                continue;
            }

            $tokenAccounts = $this->solanaRpcCall('getTokenAccountsByOwner', [
                $address,
                ['mint' => $tokenAddress],
                ['encoding' => 'jsonParsed', 'commitment' => 'confirmed'],
            ]);

            $amount = 0.0;
            foreach ((array) data_get($tokenAccounts, 'value', []) as $account) {
                $amount += (float) data_get($account, 'account.data.parsed.info.tokenAmount.uiAmount', 0);
            }

            $decimals = max(0, (int) ($token->status ?? 9));
            $assets[] = [
                'symbol' => (string) ($token->name ?? 'TOKEN'),
                'name' => (string) (($token->doc ?? '') !== '' ? $token->doc : ($token->name ?? 'Token')),
                'address' => $tokenAddress,
                'decimals' => $decimals,
                'balance' => $amount,
                'is_native' => false,
                'coingecko_id' => ($token->constanta ?? '0') !== '0' ? $token->constanta : null,
            ];
        }

        return [
            'available' => true,
            'address' => $address,
            'chain_id' => 'solana',
            'assets' => $assets,
            'error' => null,
        ];
    }

    private function fetchAave(string $address, string $chainId): array
    {
        $endpoint = $this->resolveAaveEndpoint($chainId);
        if (!$endpoint) {
            return $this->emptyProtocolPayload('Aave');
        }

        $query = <<<'GRAPHQL'
query WalletPositions($user: String!) {
  userReserves(where: { user: $user }) {
    currentATokenBalance
    currentVariableDebt
    currentStableDebt
    usageAsCollateralEnabledOnUser
    reserve {
      symbol
      name
      underlyingAsset
      decimals
      liquidityRate
      variableBorrowRate
      availableLiquidity
      totalLiquidity
      totalCurrentVariableDebt
      price {
        priceInEth
      }
    }
  }
}
GRAPHQL;

        try {
            $response = $this->theGraphHttpJson()
                ->post($endpoint, [
                    'query' => $query,
                    'variables' => ['user' => $address],
                ])
                ->throw()
                ->json();
        } catch (\Throwable $e) {
            return $this->emptyProtocolPayload('Aave', $e->getMessage());
        }

        $graphqlErrors = data_get($response, 'errors');
        if (is_array($graphqlErrors) && $graphqlErrors !== []) {
            $message = (string) data_get($graphqlErrors, '0.message', 'GraphQL query failed');

            return $this->emptyProtocolPayload('Aave', $message);
        }

        $items = data_get($response, 'data.userReserves', []);
        if (!is_array($items)) {
            return $this->emptyProtocolPayload('Aave');
        }

        $tokens = [];
        $loans = [];
        $pools = [];

        foreach ($items as $item) {
            $reserve = $item['reserve'] ?? [];
            $decimals = (int) ($reserve['decimals'] ?? 18);
            $symbol = (string) ($reserve['symbol'] ?? 'TOKEN');
            $name = (string) ($reserve['name'] ?? $symbol);
            $price = $this->normalizeUsdPrice(
                $reserve['price']['priceInUsd']
                ?? $reserve['price']['priceInEth']
                ?? null
            );

            $supply = $this->normalizeTokenAmount($item['currentATokenBalance'] ?? '0', $decimals);
            $variableDebt = $this->normalizeTokenAmount($item['currentVariableDebt'] ?? '0', $decimals);
            $stableDebt = $this->normalizeTokenAmount($item['currentStableDebt'] ?? '0', $decimals);
            $debt = $variableDebt + $stableDebt;

            if ($supply > 0) {
                $tokens[] = [
                    'symbol' => $symbol,
                    'name' => $name,
                    'balance' => $supply,
                    'usd_value' => $supply * $price,
                    'price' => $price,
                    'apy' => $this->normalizeRayRate($reserve['liquidityRate'] ?? null),
                    'collateral' => (bool) ($item['usageAsCollateralEnabledOnUser'] ?? false),
                    'address' => $reserve['underlyingAsset'] ?? null,
                ];
            }

            if ($debt > 0) {
                $loans[] = [
                    'symbol' => $symbol,
                    'name' => $name,
                    'balance' => $debt,
                    'usd_value' => $debt * $price,
                    'price' => $price,
                    'apy' => $this->normalizeRayRate($reserve['variableBorrowRate'] ?? null),
                    'address' => $reserve['underlyingAsset'] ?? null,
                ];
            }

            if ($supply > 0 || $debt > 0) {
                $pools[] = [
                    'symbol' => $symbol,
                    'name' => $name,
                    'available_liquidity' => $this->normalizeTokenAmount($reserve['availableLiquidity'] ?? '0', $decimals),
                    'total_liquidity' => $this->normalizeTokenAmount($reserve['totalLiquidity'] ?? '0', $decimals),
                    'total_borrowed' => $this->normalizeTokenAmount($reserve['totalCurrentVariableDebt'] ?? '0', $decimals),
                    'supply_apy' => $this->normalizeRayRate($reserve['liquidityRate'] ?? null),
                    'borrow_apy' => $this->normalizeRayRate($reserve['variableBorrowRate'] ?? null),
                ];
            }
        }

        return [
            'name' => 'Aave',
            'available' => true,
            'tokens' => array_values($tokens),
            'loans' => array_values($loans),
            'pools' => array_values($pools),
            'error' => null,
        ];
    }

    private function httpJson(): PendingRequest
    {
        return Http::acceptJson()->timeout(15)->connectTimeout(10);
    }

    private function resolveAaveEndpoint(string $chainId): ?string
    {
        $subgraphId = self::AAVE_SUBGRAPH_IDS[$chainId] ?? null;
        if (!$subgraphId) {
            return null;
        }

        $gatewayUrl = rtrim((string) config('services.thegraph.gateway_url', 'https://gateway.thegraph.com/api'), '/');
        $apiKey = trim((string) config('services.thegraph.api_key', ''));

        if ($apiKey !== '') {
            return $gatewayUrl . '/' . rawurlencode($apiKey) . '/subgraphs/id/' . $subgraphId;
        }

        return $gatewayUrl . '/subgraphs/id/' . $subgraphId;
    }

    private function theGraphHttpJson(): PendingRequest
    {
        return $this->httpJson();
    }

    private function rpcCall(string $url, string $method, array $params): mixed
    {
        try {
            $response = Http::acceptJson()
                ->timeout(15)
                ->connectTimeout(10)
                ->post($url, [
                    'jsonrpc' => '2.0',
                    'method' => $method,
                    'params' => $params,
                    'id' => 1,
                ])
                ->throw()
                ->json();
        } catch (\Throwable) {
            return null;
        }

        return $response['result'] ?? null;
    }

    private function solanaRpcCall(string $method, array $params): mixed
    {
        return $this->rpcCall(self::SOLANA_RPC_ENDPOINT, $method, $params);
    }

    private function emptyProtocolPayload(string $name, ?string $error = null): array
    {
        return [
            'name' => $name,
            'available' => false,
            'tokens' => [],
            'loans' => [],
            'pools' => [],
            'error' => $error,
        ];
    }

    private function normalizeUsdPrice(mixed $value): float
    {
        return $this->toFloat($value) / 100000000;
    }

    private function normalizeRayRate(mixed $value): float
    {
        return ($this->toFloat($value) / 10000000000000000000000000);
    }

    private function normalizeWalletChainId(string|int|null $chainId): string
    {
        $normalized = Conf::normalizeWeb3ChainIdToHex($chainId);

        return $normalized ?: '0x1';
    }

    private function normalizeWalletAddress(string $address, string $chainId): string
    {
        $address = trim($address);

        return $chainId === 'solana'
            ? $address
            : strtolower($address);
    }

    private function isValidSolanaAddress(string $address): bool
    {
        return (bool) preg_match('/^[1-9A-HJ-NP-Za-km-z]{32,44}$/', $address);
    }

    private function normalizeTokenAmount(mixed $value, int $decimals): float
    {
        $amount = $this->toFloat($value);
        if ($decimals <= 0) {
            return $amount;
        }

        return $amount / (10 ** min($decimals, 18));
    }

    private function normalizeHexTokenAmount(mixed $value, int $decimals): float
    {
        if (!is_string($value) || $value === '' || $value === '0x') {
            return 0.0;
        }

        $normalized = strtolower(trim($value));
        if (!str_starts_with($normalized, '0x')) {
            return 0.0;
        }

        $hex = substr($normalized, 2);
        if ($hex === '' || !ctype_xdigit($hex)) {
            return 0.0;
        }

        $numeric = (float) hexdec($hex);

        return $decimals > 0 ? $numeric / (10 ** min($decimals, 18)) : $numeric;
    }

    private function toFloat(mixed $value): float
    {
        if (is_numeric($value)) {
            return (float) $value;
        }

        if (is_string($value)) {
            $trimmed = trim($value);
            if ($trimmed === '' || !is_numeric($trimmed)) {
                return 0.0;
            }

            return (float) $trimmed;
        }

        return 0.0;
    }

    private function extractList(array $payload, array $keys): array
    {
        foreach ($keys as $key) {
            $value = $payload[$key] ?? null;
            if (is_array($value)) {
                return array_is_list($value) ? $value : [];
            }
        }

        return array_is_list($payload) ? $payload : [];
    }
}
