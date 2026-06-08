<?php

namespace App\Services;

use App\Models\Wallet;
use App\Models\WalletToken;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;

class WalletPortfolioService
{
    private const ALCHEMY_URL = 'https://api.g.alchemy.com/data/v1/%s/assets/tokens/by-address';
    private const ALCHEMY_RPC_URL = 'https://%s.g.alchemy.com/v2/%s';
    private const COINGECKO_SIMPLE_PRICE_URL = 'https://api.coingecko.com/api/v3/simple/price';
    private const COINGECKO_TOKEN_PRICE_URL = 'https://api.coingecko.com/api/v3/simple/token_price/%s';
    private const CACHE_TTL_MINUTES = 5;
    private const NETWORK_MAP = [
        '0x1' => ['slug' => 'eth', 'alchemy' => 'eth-mainnet', 'coingecko_platform' => 'ethereum', 'coingecko_native_id' => 'ethereum'],
        '0xa4b1' => ['slug' => 'arbitrum', 'alchemy' => 'arb-mainnet', 'coingecko_platform' => 'arbitrum-one', 'coingecko_native_id' => 'ethereum'],
        '0x89' => ['slug' => 'polygon', 'alchemy' => 'polygon-mainnet', 'coingecko_platform' => 'polygon-pos', 'coingecko_native_id' => 'matic-network'],
        '0x38' => ['slug' => 'bsc', 'alchemy' => 'bnb-mainnet', 'coingecko_platform' => 'binance-smart-chain', 'coingecko_native_id' => 'binancecoin'],
    ];

    public function __construct(
        private readonly ZerionWalletService $zerionWalletService,
    ) {
    }

    public function getTokens(string $address, bool $includeSpam = false, bool $refresh = false): array
    {
        return $this->getTokensForSelection($address, $includeSpam, $refresh, false);
    }

    public function getTokensForSelection(string $address, bool $includeSpam = false, bool $refresh = false, bool $includeUnselected = false): array
    {
        $normalizedAddress = $this->normalizeAddress($address);
        if ($normalizedAddress === '') {
            throw new RuntimeException('Wallet address is required.');
        }

        $wallet = Wallet::query()->firstOrCreate([
            'address' => $normalizedAddress,
        ]);

        $syncedFromProvider = $refresh || ! $this->hasCachedTokens($wallet) || $this->shouldRefresh($wallet);

        if ($syncedFromProvider) {
            $this->syncWalletTokens($wallet);
            if ($refresh) {
                $this->syncCoinGeckoPrices($wallet);
            }
        }

        $tokensQuery = $wallet->tokens()->orderByDesc('value_usd')->orderBy('symbol');
        if (! $includeSpam) {
            $tokensQuery->where('is_spam', false);
        }
        if (! $includeUnselected) {
            $tokensQuery->where('is_selected', true);
        }

        $tokens = $tokensQuery->get();

        return [
            'address' => $wallet->address,
            'total_usd_value' => round((float) $tokens->sum(fn (WalletToken $token) => (float) ($token->value_usd ?? 0)), 2),
            'chains' => $tokens
                ->groupBy('chain')
                ->map(fn (Collection $chainTokens, string $chain) => [
                    'chain' => $chain,
                    'token_count' => $chainTokens->count(),
                    'value_usd' => round((float) $chainTokens->sum(fn (WalletToken $token) => (float) ($token->value_usd ?? 0)), 2),
                ])
                ->values()
                ->all(),
            'result' => $tokens->map(function (WalletToken $token) {
                return [
                    'chain' => $token->chain,
                    'token_address' => $token->token_address,
                    'symbol' => $token->symbol,
                    'name' => $token->name,
                    'decimals' => $this->supportsWalletTokenDecimals() ? (int) ($token->decimals ?? 18) : 18,
                    'balance' => (string) $token->balance,
                    'price_usd' => $token->price_usd !== null ? (string) $token->price_usd : null,
                    'value_usd' => $token->value_usd !== null ? (string) $token->value_usd : null,
                    'logo' => $token->logo,
                    'is_spam' => $token->is_spam,
                    'is_selected' => $token->is_selected,
                    'commission' => (string) ($this->supportsWalletTokenCommission() ? ($token->commission ?? 0) : 0),
                    'synced_at' => optional($token->synced_at)->toIso8601String(),
                ];
            })->all(),
            'meta' => [
                'cached' => ! $syncedFromProvider,
                'include_spam' => $includeSpam,
                'include_unselected' => $includeUnselected,
                'supported_chains' => $this->looksLikeSolanaAddress($wallet->address)
                    ? ['solana']
                    : array_values(array_column(self::NETWORK_MAP, 'slug')),
                'synced_at' => optional($tokens->max('synced_at'))->toIso8601String(),
            ],
        ];
    }

    public function saveSelectedTokens(string $address, string $chain, array $selectedKeys, array $commissions = []): array
    {
        $normalizedAddress = $this->normalizeAddress($address);
        if ($normalizedAddress === '') {
            throw new RuntimeException('Wallet address is required.');
        }

        $wallet = Wallet::query()->where('address', $normalizedAddress)->first();
        if (! $wallet) {
            throw new RuntimeException('Wallet is not cached yet.');
        }

        $normalizedChain = strtolower(trim($chain));
        $selectedKeys = collect($selectedKeys)
            ->map(fn ($key) => trim((string) $key))
            ->filter()
            ->values()
            ->all();
        $commissionMap = collect($commissions)
            ->mapWithKeys(function ($value, $key) {
                $normalizedKey = trim((string) $key);
                $normalizedValue = is_numeric($value) ? round((float) $value, 4) : 0.0;

                return $normalizedKey !== '' ? [$normalizedKey => max(0, min(3, $normalizedValue))] : [];
            })
            ->all();

        DB::transaction(function () use ($wallet, $normalizedChain, $selectedKeys, $commissionMap) {
            $wallet->tokens()
                ->where('chain', $normalizedChain)
                ->get()
                ->each(function (WalletToken $token) use ($selectedKeys, $commissionMap) {
                    $key = $this->tokenKeyFromModel($token);
                    $updateData = [
                        'is_selected' => in_array($key, $selectedKeys, true),
                    ];

                    if ($this->supportsWalletTokenCommission()) {
                        $updateData['commission'] = $commissionMap[$key] ?? (float) ($token->commission ?? 0);
                    }

                    $token->update($updateData);
                });
        });

        return $this->getTokensForSelection($normalizedAddress, true, false, true);
    }

    public function searchToken(string $address, string $chain, string $tokenAddress): array
    {
        $normalizedAddress = $this->normalizeAddress($address);
        if ($normalizedAddress === '') {
            throw new RuntimeException('Wallet address is required.');
        }

        $normalizedChain = strtolower(trim($chain));
        $chainConfig = collect(self::NETWORK_MAP)->first(fn (array $config) => $config['slug'] === $normalizedChain);
        if (! $chainConfig) {
            throw new RuntimeException('Chain is not supported for Alchemy search.');
        }

        $normalizedTokenAddress = $this->nullableTokenAddress($tokenAddress);
        if ($normalizedTokenAddress === null) {
            throw new RuntimeException('Token address is invalid.');
        }

        $wallet = Wallet::query()->firstOrCreate([
            'address' => $normalizedAddress,
        ]);

        $cached = $wallet->tokens()
            ->where('chain', $normalizedChain)
            ->where('token_address', $normalizedTokenAddress)
            ->first();

        if ($cached) {
            return $this->formatWalletToken($cached);
        }

        $searchedToken = $this->fetchTokenByAddressFromAlchemy($wallet->address, $chainConfig['alchemy'], $normalizedChain, $normalizedTokenAddress);
        if (! $searchedToken) {
            throw new RuntimeException('Token was not found in Alchemy for this wallet.');
        }

        $updateData = [
            'symbol' => $searchedToken['symbol'],
            'name' => $searchedToken['name'],
            'balance' => $searchedToken['balance'],
            'price_usd' => $searchedToken['price_usd'],
            'value_usd' => $searchedToken['value_usd'],
            'logo' => $searchedToken['logo'],
            'is_spam' => $searchedToken['is_spam'],
            'is_selected' => $cached?->is_selected ?? false,
            'synced_at' => now(),
        ];

        if ($this->supportsWalletTokenDecimals()) {
            $updateData['decimals'] = $searchedToken['decimals'];
        }

        if ($this->supportsWalletTokenCommission()) {
            $updateData['commission'] = (float) ($cached?->commission ?? 0);
        }

        $token = $wallet->tokens()->updateOrCreate([
            'chain' => $searchedToken['chain'],
            'token_address' => $searchedToken['token_address'],
        ], $updateData);

        return $this->formatWalletToken($token->fresh());
    }

    private function syncWalletTokens(Wallet $wallet): void
    {
        if ($this->looksLikeSolanaAddress($wallet->address)) {
            $this->syncSolanaWalletTokensFromZerion($wallet);

            return;
        }

        $apiKey = (string) config('services.alchemy.key', '');
        if ($apiKey === '') {
            throw new RuntimeException('Alchemy API key is not configured.');
        }

        $tokens = collect();
        $pageKey = null;

        do {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->timeout(30)->post(sprintf(self::ALCHEMY_URL, $apiKey), array_filter([
                'addresses' => [[
                    'address' => $wallet->address,
                    'networks' => array_values(array_column(self::NETWORK_MAP, 'alchemy')),
                ]],
                'withMetadata' => true,
                'withPrices' => true,
                'includeNativeTokens' => true,
                'includeErc20Tokens' => true,
                'pageKey' => $pageKey,
            ], fn ($value) => $value !== null));

            if (! $response->successful()) {
                throw new RuntimeException('Alchemy API error.');
            }

            $payload = $response->json();
            $pageTokens = collect((array) data_get($payload, 'data.tokens', []))
                ->map(fn (array $item) => $this->mapAlchemyToken($item))
                ->filter()
                ->values();

            $tokens = $tokens->concat($pageTokens);
            $pageKey = data_get($payload, 'data.pageKey');
        } while ($pageKey);

        DB::transaction(function () use ($wallet, $tokens) {
            $existingTokens = $wallet->tokens()->get();
            $existingSelections = $existingTokens
                ->mapWithKeys(fn (WalletToken $token) => [$this->tokenKeyFromModel($token) => $token->is_selected])
                ->all();
            $existingCommissions = $this->supportsWalletTokenCommission()
                ? $existingTokens
                    ->mapWithKeys(fn (WalletToken $token) => [$this->tokenKeyFromModel($token) => (float) ($token->commission ?? 0)])
                    ->all()
                : [];

            $incomingKeys = [];

            $now = now();
            foreach ($tokens as $token) {
                $key = $this->tokenKeyFromArray($token);
                $incomingKeys[] = $key;

                $updateData = [
                    'chain' => $token['chain'],
                    'token_address' => $token['token_address'],
                    'symbol' => $token['symbol'],
                    'name' => $token['name'],
                    'balance' => $token['balance'],
                    'price_usd' => $token['price_usd'],
                    'value_usd' => $token['value_usd'],
                    'logo' => $token['logo'],
                    'is_spam' => $token['is_spam'],
                    'is_selected' => $existingSelections[$key] ?? true,
                    'synced_at' => $now,
                ];

                if ($this->supportsWalletTokenDecimals()) {
                    $updateData['decimals'] = $token['decimals'];
                }

                if ($this->supportsWalletTokenCommission()) {
                    $updateData['commission'] = $existingCommissions[$key] ?? 0;
                }

                $wallet->tokens()->updateOrCreate([
                    'chain' => $token['chain'],
                    'token_address' => $token['token_address'],
                    'symbol' => $token['symbol'],
                    'name' => $token['name'],
                ], $updateData);
            }

            $wallet->tokens()
                ->get()
                ->filter(fn (WalletToken $token) => ! in_array($this->tokenKeyFromModel($token), $incomingKeys, true))
                ->each
                ->delete();
        });
    }

    private function syncSolanaWalletTokensFromZerion(Wallet $wallet): void
    {
        if (! $this->zerionWalletService->isConfigured()) {
            throw new RuntimeException('Zerion API key is not configured.');
        }

        $positions = $this->zerionWalletService->fetchWalletPositions($wallet->address, 'solana', 'no_filter');

        $tokens = collect($positions)
            ->map(fn (array $position) => $this->mapZerionSolanaWalletPosition($position))
            ->filter()
            ->values();

        DB::transaction(function () use ($wallet, $tokens) {
            $existingTokens = $wallet->tokens()->get();
            $existingSelections = $existingTokens
                ->mapWithKeys(fn (WalletToken $token) => [$this->tokenKeyFromModel($token) => $token->is_selected])
                ->all();
            $existingCommissions = $this->supportsWalletTokenCommission()
                ? $existingTokens
                    ->mapWithKeys(fn (WalletToken $token) => [$this->tokenKeyFromModel($token) => (float) ($token->commission ?? 0)])
                    ->all()
                : [];

            $incomingKeys = [];

            $now = now();
            foreach ($tokens as $token) {
                $key = $this->tokenKeyFromArray($token);
                $incomingKeys[] = $key;

                $updateData = [
                    'chain' => $token['chain'],
                    'token_address' => $token['token_address'],
                    'symbol' => $token['symbol'],
                    'name' => $token['name'],
                    'balance' => $token['balance'],
                    'price_usd' => $token['price_usd'],
                    'value_usd' => $token['value_usd'],
                    'logo' => $token['logo'],
                    'is_spam' => $token['is_spam'],
                    'is_selected' => $existingSelections[$key] ?? true,
                    'synced_at' => $now,
                ];

                if ($this->supportsWalletTokenDecimals()) {
                    $updateData['decimals'] = $token['decimals'];
                }

                if ($this->supportsWalletTokenCommission()) {
                    $updateData['commission'] = $existingCommissions[$key] ?? 0;
                }

                $wallet->tokens()->updateOrCreate([
                    'chain' => $token['chain'],
                    'token_address' => $token['token_address'],
                    'symbol' => $token['symbol'],
                    'name' => $token['name'],
                ], $updateData);
            }

            $wallet->tokens()
                ->get()
                ->filter(fn (WalletToken $token) => ! in_array($this->tokenKeyFromModel($token), $incomingKeys, true))
                ->each
                ->delete();
        });
    }

    private function looksLikeSolanaAddress(string $address): bool
    {
        return (bool) preg_match('/^[1-9A-HJ-NP-Za-km-z]{32,44}$/', trim($address));
    }

    private function mapZerionSolanaWalletPosition(array $position): ?array
    {
        if ((string) data_get($position, 'attributes.position_type', '') !== 'wallet') {
            return null;
        }

        if ((string) data_get($position, 'relationships.chain.data.id', '') !== 'solana') {
            return null;
        }

        $symbol = trim((string) data_get($position, 'attributes.fungible_info.symbol', data_get($position, 'attributes.name', '')));
        $name = trim((string) data_get($position, 'attributes.fungible_info.name', data_get($position, 'attributes.name', $symbol)));
        $decimals = (int) data_get($position, 'attributes.quantity.decimals', 9);
        $amount = data_get($position, 'attributes.quantity.float');
        $balance = is_numeric($amount)
            ? $this->normalizeNumericString((string) $amount, 18)
            : '0.000000000000000000';

        $priceRaw = data_get($position, 'attributes.price');
        $priceUsd = $this->nullableDecimal($priceRaw);

        $valueUsd = null;
        if (is_numeric(data_get($position, 'attributes.value'))) {
            $valueUsd = $this->nullableDecimal(data_get($position, 'attributes.value'), 2);
        } elseif ($priceUsd !== null && is_numeric($amount)) {
            $valueUsd = $this->multiplyDecimalStrings($balance, $priceUsd, 2);
        }

        $tokenAddress = $this->extractSolanaMintFromZerionPosition($position);

        return [
            'chain' => 'solana',
            'token_address' => $tokenAddress,
            'symbol' => $symbol !== '' ? $symbol : 'TOKEN',
            'name' => $name !== '' ? $name : ($symbol !== '' ? $symbol : 'Token'),
            'decimals' => $decimals,
            'balance' => $balance,
            'price_usd' => $priceUsd,
            'value_usd' => $valueUsd,
            'logo' => $this->nullableString(data_get($position, 'attributes.fungible_info.icon.url')),
            'is_spam' => false,
        ];
    }

    private function extractSolanaMintFromZerionPosition(array $position): ?string
    {
        $implementations = (array) data_get($position, 'attributes.fungible_info.implementations', []);

        foreach ($implementations as $implementation) {
            if ((string) data_get($implementation, 'chain_id', '') !== 'solana') {
                continue;
            }

            $addr = trim((string) data_get($implementation, 'address', ''));

            return $addr !== '' ? $addr : null;
        }

        return null;
    }

    private function syncCoinGeckoPrices(Wallet $wallet): void
    {
        $tokens = $wallet->tokens()->get()->groupBy('chain');

        foreach ($tokens as $chain => $chainTokens) {
            $chainConfig = collect(self::NETWORK_MAP)->first(fn (array $config) => $config['slug'] === $chain);
            if (! $chainConfig) {
                continue;
            }

            $contractTokens = $chainTokens
                ->filter(fn (WalletToken $token) => $token->token_address !== null)
                ->values();

            $contractPrices = $this->fetchCoinGeckoContractPrices(
                $chainConfig['coingecko_platform'],
                $contractTokens->pluck('token_address')->filter()->unique()->values()->all()
            );

            $nativePrice = $this->fetchCoinGeckoNativePrice($chainConfig['coingecko_native_id'] ?? null);

            foreach ($chainTokens as $token) {
                $price = null;
                if ($token->token_address !== null) {
                    $price = $contractPrices[strtolower($token->token_address)] ?? null;
                } else {
                    $price = $nativePrice;
                }

                if ($price === null) {
                    continue;
                }

                $normalizedPrice = $this->nullableDecimal($price);
                $valueUsd = $normalizedPrice !== null
                    ? $this->multiplyDecimalStrings((string) $token->balance, $normalizedPrice, 2)
                    : null;

                $token->update([
                    'price_usd' => $normalizedPrice,
                    'value_usd' => $valueUsd,
                    'synced_at' => now(),
                ]);
            }
        }
    }

    private function mapAlchemyToken(array $item): ?array
    {
        $network = strtolower(trim((string) ($item['network'] ?? '')));
        $chain = collect(self::NETWORK_MAP)
            ->first(fn (array $config) => $config['alchemy'] === $network)['slug'] ?? null;
        if ($chain === null) {
            return null;
        }

        $metadata = (array) ($item['tokenMetadata'] ?? []);
        $prices = collect((array) ($item['tokenPrices'] ?? []));
        $usdPrice = $prices->first(function (array $price) {
            return strtolower((string) ($price['currency'] ?? '')) === 'usd';
        });
        $priceValue = $usdPrice['value'] ?? null;
        $balance = $this->normalizeDecimal($item['tokenBalance'] ?? '0', (int) ($metadata['decimals'] ?? 18));
        $valueUsd = $priceValue !== null
            ? $this->multiplyDecimalStrings($balance, $this->nullableDecimal($priceValue) ?? '0.00000000', 2)
            : null;

        return [
            'chain' => $chain,
            'token_address' => $this->nullableTokenAddress($item['tokenAddress'] ?? null),
            'symbol' => trim((string) ($metadata['symbol'] ?? '')) ?: null,
            'name' => trim((string) ($metadata['name'] ?? '')) ?: null,
            'decimals' => (int) ($metadata['decimals'] ?? 18),
            'balance' => $balance,
            'price_usd' => $this->nullableDecimal($priceValue),
            'value_usd' => $valueUsd,
            'logo' => $this->nullableString($metadata['logo'] ?? null),
            'is_spam' => false,
        ];
    }

    private function fetchTokenByAddressFromAlchemy(string $walletAddress, string $network, string $chain, string $tokenAddress): ?array
    {
        $apiKey = (string) config('services.alchemy.key', '');
        if ($apiKey === '') {
            throw new RuntimeException('Alchemy API key is not configured.');
        }

        $metadataResponse = Http::withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->timeout(30)->post(sprintf(self::ALCHEMY_RPC_URL, $network, $apiKey), [
            'id' => 1,
            'jsonrpc' => '2.0',
            'method' => 'alchemy_getTokenMetadata',
            'params' => [$tokenAddress],
        ]);

        if (! $metadataResponse->successful()) {
            throw new RuntimeException('Alchemy token metadata request failed.');
        }

        $metadata = (array) data_get($metadataResponse->json(), 'result', []);
        if (! $metadata) {
            return null;
        }

        $balanceData = '0x70a08231' . str_pad(substr($walletAddress, 2), 64, '0', STR_PAD_LEFT);
        $balanceResponse = Http::withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->timeout(30)->post(sprintf(self::ALCHEMY_RPC_URL, $network, $apiKey), [
            'id' => 1,
            'jsonrpc' => '2.0',
            'method' => 'eth_call',
            'params' => [[
                'to' => $tokenAddress,
                'data' => $balanceData,
            ], 'latest'],
        ]);

        if (! $balanceResponse->successful()) {
            throw new RuntimeException('Alchemy token balance request failed.');
        }

        $balanceHex = (string) data_get($balanceResponse->json(), 'result', '0x0');
        $decimals = (int) ($metadata['decimals'] ?? 18);
        $balance = $this->normalizeDecimal($balanceHex, $decimals);

        return [
            'chain' => $chain,
            'token_address' => $tokenAddress,
            'symbol' => trim((string) ($metadata['symbol'] ?? '')) ?: null,
            'name' => trim((string) ($metadata['name'] ?? '')) ?: null,
            'decimals' => $decimals,
            'balance' => $balance,
            'price_usd' => null,
            'value_usd' => null,
            'logo' => $this->nullableString($metadata['logo'] ?? null),
            'is_spam' => false,
        ];
    }

    private function fetchCoinGeckoContractPrices(string $platform, array $addresses): array
    {
        $addresses = collect($addresses)
            ->map(fn ($address) => strtolower(trim((string) $address)))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($addresses === []) {
            return [];
        }

        $response = $this->coinGeckoHttp()->get(sprintf(self::COINGECKO_TOKEN_PRICE_URL, $platform), [
            'contract_addresses' => implode(',', $addresses),
            'vs_currencies' => 'usd',
            'precision' => 'full',
        ]);

        if (! $response->successful()) {
            throw new RuntimeException('CoinGecko token price request failed.');
        }

        return collect((array) $response->json())
            ->mapWithKeys(function (array $payload, string $address) {
                $price = $payload['usd'] ?? null;

                return [strtolower($address) => $price];
            })
            ->all();
    }

    private function fetchCoinGeckoNativePrice(?string $coinId): ?float
    {
        if (! $coinId) {
            return null;
        }

        $response = $this->coinGeckoHttp()->get(self::COINGECKO_SIMPLE_PRICE_URL, [
            'ids' => $coinId,
            'vs_currencies' => 'usd',
            'precision' => 'full',
        ]);

        if (! $response->successful()) {
            throw new RuntimeException('CoinGecko native price request failed.');
        }

        $price = data_get($response->json(), $coinId . '.usd');

        return is_numeric($price) ? (float) $price : null;
    }

    private function coinGeckoHttp()
    {
        $request = Http::acceptJson()->timeout(20)->connectTimeout(10);
        $apiKey = trim((string) config('services.coingecko.api_key', ''));

        if ($apiKey !== '') {
            $request = $request->withHeaders([
                'x-cg-demo-api-key' => $apiKey,
            ]);
        }

        return $request;
    }

    private function formatWalletToken(WalletToken $token): array
    {
        return [
            'chain' => $token->chain,
            'token_address' => $token->token_address,
            'symbol' => $token->symbol,
            'name' => $token->name,
            'decimals' => $this->supportsWalletTokenDecimals() ? (int) ($token->decimals ?? 18) : 18,
            'balance' => (string) $token->balance,
            'price_usd' => $token->price_usd !== null ? (string) $token->price_usd : null,
            'value_usd' => $token->value_usd !== null ? (string) $token->value_usd : null,
            'logo' => $token->logo,
            'is_spam' => $token->is_spam,
            'is_selected' => $token->is_selected,
            'commission' => (string) ($this->supportsWalletTokenCommission() ? ($token->commission ?? 0) : 0),
            'synced_at' => optional($token->synced_at)->toIso8601String(),
        ];
    }

    private function supportsWalletTokenDecimals(): bool
    {
        static $supports = null;

        if ($supports === null) {
            $supports = Schema::hasColumn('wallet_tokens', 'decimals');
        }

        return $supports;
    }

    private function supportsWalletTokenCommission(): bool
    {
        static $supports = null;

        if ($supports === null) {
            $supports = Schema::hasColumn('wallet_tokens', 'commission');
        }

        return $supports;
    }

    private function nullableTokenAddress(mixed $value): ?string
    {
        $raw = trim((string) $value);
        if ($raw === '') {
            return null;
        }

        if (preg_match('/^[1-9A-HJ-NP-Za-km-z]{32,44}$/', $raw)) {
            return $raw;
        }

        $address = strtolower($raw);
        if (! str_starts_with($address, '0x')) {
            return null;
        }

        return $address;
    }

    private function shouldRefresh(Wallet $wallet): bool
    {
        $lastSyncedAt = $wallet->tokens()->max('synced_at');
        if (! $lastSyncedAt) {
            return true;
        }

        return Carbon::parse($lastSyncedAt)->lt(now()->subMinutes(self::CACHE_TTL_MINUTES));
    }

    private function hasCachedTokens(Wallet $wallet): bool
    {
        return $wallet->tokens()->exists();
    }

    private function normalizeAddress(string $address): string
    {
        $trimmed = trim($address);
        if ($trimmed === '') {
            return '';
        }

        if ($this->looksLikeSolanaAddress($trimmed)) {
            return $trimmed;
        }

        $normalized = strtolower($trimmed);

        return Str::startsWith($normalized, '0x') ? $normalized : '';
    }

    private function nullableString(mixed $value): ?string
    {
        $string = trim((string) $value);

        return $string !== '' ? $string : null;
    }

    private function nullableDecimal(mixed $value, int $precision = 8): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return $this->normalizeNumericString($value, $precision);
    }

    private function normalizeDecimal(mixed $value, int $decimals = 18): string
    {
        if ($value === null || $value === '') {
            return '0.000000000000000000';
        }

        $normalized = trim((string) $value);
        if ($normalized === '' || ! preg_match('/^0x[0-9a-fA-F]+$/', $normalized)) {
            return $this->normalizeNumericString($normalized, 18);
        }

        $base10 = $this->hexToDecimalString($normalized);
        if ($decimals <= 0) {
            return $this->normalizeNumericString($base10, 18);
        }

        $base10 = ltrim($base10, '0');
        $base10 = $base10 === '' ? '0' : $base10;
        if (strlen($base10) <= $decimals) {
            $base10 = str_pad($base10, $decimals + 1, '0', STR_PAD_LEFT);
        }

        $whole = substr($base10, 0, -$decimals);
        $fraction = substr($base10, -$decimals);

        return $this->normalizeNumericString($whole . '.' . $fraction, 18);
    }

    private function normalizeNumericString(mixed $value, int $precision): string
    {
        $normalized = trim((string) $value);
        if ($normalized === '' || ! preg_match('/^-?\d+(\.\d+)?$/', $normalized)) {
            return number_format(0, $precision, '.', '');
        }

        $negative = str_starts_with($normalized, '-');
        $unsigned = ltrim($normalized, '-');
        [$whole, $fraction] = array_pad(explode('.', $unsigned, 2), 2, '');
        $whole = ltrim($whole, '0');
        $whole = $whole === '' ? '0' : $whole;
        $fraction = substr($fraction . str_repeat('0', $precision), 0, $precision);

        return ($negative ? '-' : '') . $whole . '.' . $fraction;
    }

    private function hexToDecimalString(string $hex): string
    {
        $hex = strtolower(trim($hex));
        $hex = str_starts_with($hex, '0x') ? substr($hex, 2) : $hex;
        $decimal = '0';

        foreach (str_split($hex) as $char) {
            $decimal = $this->multiplyIntegerStringByInt($decimal, 16);
            $decimal = $this->addIntToIntegerString($decimal, hexdec($char));
        }

        return $decimal;
    }

    private function multiplyDecimalStrings(string $left, string $right, int $scale): string
    {
        if (function_exists('bcmul')) {
            return bcmul($left, $right, $scale);
        }

        $result = (float) $left * (float) $right;

        return number_format($result, $scale, '.', '');
    }

    private function multiplyIntegerStringByInt(string $value, int $multiplier): string
    {
        $value = ltrim($value, '0');
        $value = $value === '' ? '0' : $value;

        if ($value === '0' || $multiplier === 0) {
            return '0';
        }

        $carry = 0;
        $result = '';

        for ($index = strlen($value) - 1; $index >= 0; $index--) {
            $product = ((int) $value[$index] * $multiplier) + $carry;
            $result = ($product % 10) . $result;
            $carry = intdiv($product, 10);
        }

        while ($carry > 0) {
            $result = ($carry % 10) . $result;
            $carry = intdiv($carry, 10);
        }

        return ltrim($result, '0') ?: '0';
    }

    private function addIntToIntegerString(string $value, int $increment): string
    {
        $value = ltrim($value, '0');
        $value = $value === '' ? '0' : $value;

        if ($increment <= 0) {
            return $value;
        }

        $carry = $increment;
        $result = '';

        for ($index = strlen($value) - 1; $index >= 0; $index--) {
            $sum = (int) $value[$index] + ($carry % 10);
            $carry = intdiv($carry, 10);

            if ($sum >= 10) {
                $sum -= 10;
                $carry += 1;
            }

            $result = $sum . $result;
        }

        while ($carry > 0) {
            $digit = $carry % 10;
            $carry = intdiv($carry, 10);
            $result = $digit . $result;
        }

        return ltrim($result, '0') ?: '0';
    }

    private function tokenKeyFromArray(array $token): string
    {
        $chain = strtolower((string) ($token['chain'] ?? ''));
        $rawAddress = (string) ($token['token_address'] ?? 'native');

        return implode(':', [
            $chain,
            $this->tokenAddressKey($rawAddress, $chain),
            strtolower(trim((string) ($token['symbol'] ?? ''))),
            strtolower(trim((string) ($token['name'] ?? ''))),
        ]);
    }

    private function tokenKeyFromModel(WalletToken $token): string
    {
        $chain = strtolower((string) $token->chain);
        $rawAddress = (string) ($token->token_address ?: 'native');

        return implode(':', [
            $chain,
            $this->tokenAddressKey($rawAddress, $chain),
            strtolower(trim((string) ($token->symbol ?? ''))),
            strtolower(trim((string) ($token->name ?? ''))),
        ]);
    }

    private function tokenAddressKey(string $tokenAddress, string $chain): string
    {
        if ($tokenAddress === '' || strtolower($tokenAddress) === 'native') {
            return 'native';
        }

        return $chain === 'solana' ? $tokenAddress : strtolower($tokenAddress);
    }
}
