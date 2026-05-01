<?php

namespace App\Services;

use App\Models\Conf;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

class ZerionWalletService
{
    private const BASE_URL = 'https://api.zerion.io/v1';
    private const CACHE_TTL_SECONDS = 300;
    private const DEFAULT_HOLDINGS_LIMIT = 8;

    private const CHAIN_MAP = [
        '0x1' => 'ethereum',
        '0x38' => 'binance-smart-chain',
        '0x89' => 'polygon',
        '0xa' => 'optimism',
        '0x2105' => 'base',
        '0xa4b1' => 'arbitrum',
        '0xa86a' => 'avalanche',
        'solana' => 'solana',
    ];

    private const REVERSE_CHAIN_MAP = [
        'ethereum' => '0x1',
        'binance-smart-chain' => '0x38',
        'polygon' => '0x89',
        'optimism' => '0xa',
        'base' => '0x2105',
        'arbitrum' => '0xa4b1',
        'avalanche' => '0xa86a',
        'solana' => 'solana',
    ];

    public function searchFungibles(string $query, string|int|null $chainId = null, int $limit = 10): array
    {
        $normalizedChainId = $this->normalizeWalletChainId($chainId);
        $chainSlug = $this->resolveChainSlug($normalizedChainId);

        if (! $this->isConfigured()) {
            return [];
        }

        if ($normalizedChainId !== null && $chainSlug === null) {
            return [];
        }

        try {
            $response = $this->http()->get('/fungibles/', array_filter([
                'currency' => 'usd',
                'filter[search_query]' => trim($query),
                'filter[implementation_chain_id]' => $chainSlug,
                'sort' => '-market_data.market_cap',
            ], fn ($value) => $value !== null && $value !== ''));

            $payload = $response->throw()->json();

            return collect((array) data_get($payload, 'data', []))
                ->map(fn (array $item) => $this->mapSearchFungible($item, $normalizedChainId))
                ->filter()
                ->take(max(1, $limit))
                ->values()
                ->all();
        } catch (Throwable $exception) {
            report($exception);

            return [];
        }
    }

    public function loadAssets(string $address, string|int|null $chainId, array $configuredTokens = []): array
    {
        $normalizedChainId = $this->normalizeWalletChainId($chainId);
        $chainSlug = $this->resolveChainSlug($normalizedChainId);
        $normalizedAddress = $this->normalizeWalletAddress($address, $normalizedChainId);
        $configuredAssets = $this->configuredAssetsForChain($configuredTokens, $chainSlug);

        if (! $this->isConfigured()) {
            return $this->emptyAssets(
                $normalizedAddress,
                $normalizedChainId,
                'Zerion API key is not configured.',
                $this->fallbackAssetsFromConfiguredTokens($configuredAssets)
            );
        }

        if ($normalizedChainId !== null && $chainSlug === null) {
            return $this->emptyAssets(
                $normalizedAddress,
                $normalizedChainId,
                'Unsupported network',
                $this->fallbackAssetsFromConfiguredTokens($configuredAssets)
            );
        }

        $cacheKey = sprintf('zerion:assets:%s:%s:%s', $normalizedAddress, $chainSlug ?: 'all', md5(json_encode($this->configuredTokenKeys($configuredTokens))));

        try {
            return Cache::remember($cacheKey, self::CACHE_TTL_SECONDS, function () use ($normalizedAddress, $normalizedChainId, $chainSlug, $configuredTokens) {
                $positions = $this->fetchPositions($normalizedAddress, [
                    'currency' => 'usd',
                    'filter[positions]' => 'no_filter',
                    'sort' => '-value',
                    'filter[chain_ids]' => $chainSlug,
                ]);

                $assets = collect($positions)
                    ->filter(fn (array $position) => $this->isWalletPosition($position))
                    ->map(fn (array $position) => $this->mapAssetPosition($position))
                    ->filter(fn (?array $asset) => $asset !== null)
                    ->values();

                $allowedAddresses = $configuredAssets
                    ->pluck('address')
                    ->filter()
                    ->values()
                    ->all();

                if ($configuredTokens === []) {
                    $assets = collect();
                } else {
                    $assets = $assets
                        ->filter(function (array $asset) use ($allowedAddresses, $configuredAssets) {
                            if (! empty($asset['is_native'])) {
                                return true;
                            }

                            $address = strtolower((string) ($asset['address'] ?? ''));
                            if ($address !== '' && in_array($address, $allowedAddresses, true)) {
                                return true;
                            }

                            $symbol = strtoupper(trim((string) ($asset['symbol'] ?? '')));
                            $name = trim((string) ($asset['name'] ?? ''));

                            return $configuredAssets->contains(function (array $configured) use ($symbol, $name) {
                                return ($symbol !== '' && $configured['symbol'] !== '' && $configured['symbol'] === $symbol)
                                    || ($name !== '' && $configured['name'] !== '' && strcasecmp($configured['name'], $name) === 0);
                            });
                        })
                        ->values();
                }

                $assets = $this->mergeConfiguredFallbackAssets($assets, $configuredAssets);

                return [
                    'available' => $assets->isNotEmpty(),
                    'address' => $normalizedAddress,
                    'chain_id' => $normalizedChainId,
                    'assets' => $assets->all(),
                    'error' => null,
                ];
            });
        } catch (Throwable $exception) {
            report($exception);

            return $this->emptyAssets(
                $normalizedAddress,
                $normalizedChainId,
                $this->humanizeApiError($exception),
                $this->fallbackAssetsFromConfiguredTokens($configuredAssets)
            );
        }
    }

    private function mapSearchFungible(array $item, ?string $preferredChainId = null): ?array
    {
        $implementations = collect((array) data_get($item, 'attributes.implementations', []))
            ->map(function (array $implementation) {
                $hexChainId = $this->resolveHexChainId((string) data_get($implementation, 'chain_id', ''));
                $address = trim((string) data_get($implementation, 'address', ''));

                if ($hexChainId === null || $address === '') {
                    return null;
                }

                return [
                    'chain_id' => $hexChainId,
                    'address' => $address,
                    'decimals' => (int) data_get($implementation, 'decimals', $hexChainId === 'solana' ? 9 : 18),
                ];
            })
            ->filter();

        if ($preferredChainId !== null) {
            $implementations = $implementations->where('chain_id', $preferredChainId)->values();
        } else {
            $implementations = $implementations->values();
        }

        if ($implementations->isEmpty()) {
            return null;
        }

        return [
            'id' => (string) data_get($item, 'id', ''),
            'symbol' => (string) data_get($item, 'attributes.symbol', ''),
            'name' => (string) data_get($item, 'attributes.name', ''),
            'icon_url' => data_get($item, 'attributes.icon.url'),
            'price' => (float) (
                data_get($item, 'attributes.market_data.price')
                ?? data_get($item, 'attributes.market_data.price.last')
                ?? 0
            ),
            'implementations' => $implementations->all(),
        ];
    }

    private function resolveHexChainId(string $chainSlug): ?string
    {
        $normalized = strtolower(trim($chainSlug));

        return self::REVERSE_CHAIN_MAP[$normalized] ?? null;
    }

    public function loadProtocols(string $address, string|int|null $chainId): array
    {
        $normalizedChainId = $this->normalizeWalletChainId($chainId);
        $chainSlug = $this->resolveChainSlug($normalizedChainId);
        $normalizedAddress = $this->normalizeWalletAddress($address, $normalizedChainId);

        if (! $this->isConfigured()) {
            return [];
        }

        if ($normalizedChainId !== null && $chainSlug === null) {
            return [];
        }

        if ($chainSlug === 'solana') {
            return [];
        }

        $cacheKey = sprintf('zerion:protocols:%s:%s', $normalizedAddress, $chainSlug ?: 'all');

        try {
            return Cache::remember($cacheKey, self::CACHE_TTL_SECONDS, function () use ($normalizedAddress, $chainSlug) {
                $positions = $this->fetchPositions($normalizedAddress, [
                    'currency' => 'usd',
                    'filter[positions]' => 'only_complex',
                    'sort' => '-value',
                    'filter[chain_ids]' => $chainSlug,
                ]);

                $protocols = [];

                foreach ($positions as $position) {
                    $protocolName = trim((string) data_get($position, 'attributes.application_metadata.name', data_get($position, 'attributes.protocol', 'Unknown Protocol')));
                    $protocolKey = Str::snake(Str::slug($protocolName ?: 'protocol', ' '));

                    if (! isset($protocols[$protocolKey])) {
                        $protocols[$protocolKey] = [
                            'name' => $protocolName ?: 'Protocol',
                            'url' => data_get($position, 'attributes.application_metadata.url'),
                            'icon' => data_get($position, 'attributes.application_metadata.icon.url'),
                            'available' => true,
                            'error' => null,
                            'tokens' => [],
                            'loans' => [],
                            'pools' => [],
                        ];
                    }

                    $mapped = $this->mapProtocolPosition($position);
                    $bucket = $mapped['bucket'];
                    $protocols[$protocolKey][$bucket][] = $mapped['item'];
                }

                foreach ($protocols as $key => $protocol) {
                    $protocols[$key]['tokens'] = array_values($protocol['tokens']);
                    $protocols[$key]['loans'] = array_values($protocol['loans']);
                    $protocols[$key]['pools'] = $this->collapseProtocolPools($protocol['pools']);
                }

                return $protocols;
            });
        } catch (Throwable $exception) {
            report($exception);

            return [
                'zerion' => [
                    'name' => 'Zerion',
                    'url' => null,
                    'icon' => null,
                    'available' => false,
                    'error' => $this->humanizeApiError($exception),
                    'tokens' => [],
                    'loans' => [],
                    'pools' => [],
                ],
            ];
        }
    }

    public function transparencyOverview(?string $address = null, array $chainIds = []): array
    {
        $walletAddress = $this->normalizeTransparencyWalletAddress($address ?: config('services.zerion.wallet_address'));
        $configuredChains = $chainIds !== [] ? $chainIds : $this->parseConfiguredChainIds((string) config('services.zerion.chain_ids', ''));
        $normalizedChainSlugs = $this->normalizeChainFilters($configuredChains);

        if (! $this->isConfigured()) {
            return $this->emptyTransparencyOverview($walletAddress, $normalizedChainSlugs, 'Zerion API key is not configured.');
        }

        if ($walletAddress === '') {
            return $this->emptyTransparencyOverview('', $normalizedChainSlugs, 'Zerion wallet address is not configured.');
        }

        $cacheKey = sprintf('zerion:transparency:%s:%s', $walletAddress, md5(json_encode($normalizedChainSlugs)));

        return Cache::remember($cacheKey, self::CACHE_TTL_SECONDS, function () use ($walletAddress, $normalizedChainSlugs) {
            $positions = $this->fetchPositions($walletAddress, [
                'currency' => 'usd',
                'filter[positions]' => 'no_filter',
                'sort' => '-value',
                'filter[chain_ids]' => $normalizedChainSlugs === [] ? null : implode(',', $normalizedChainSlugs),
            ]);

            $tokenHoldings = [];
            $protocolHoldings = [];

            foreach ($positions as $position) {
                if ($this->isWalletPosition($position)) {
                    $holding = $this->mapTransparencyTokenHolding($position);
                    if ($holding !== null) {
                        $tokenHoldings[] = $holding;
                    }
                    continue;
                }

                $holding = $this->mapTransparencyProtocolHolding($position);
                if ($holding !== null) {
                    $protocolHoldings[] = $holding;
                }
            }

            $allHoldings = collect([...$tokenHoldings, ...$protocolHoldings])
                ->sortByDesc('usd_value')
                ->values();

            $totalUsdValue = round((float) $allHoldings->sum('usd_value'), 2);

            $tokens = $this->attachShares(collect($tokenHoldings)->sortByDesc('usd_value')->values(), $totalUsdValue)->all();
            $protocols = $this->attachShares(collect($protocolHoldings)->sortByDesc('usd_value')->values(), $totalUsdValue)->all();
            $holdings = $this->attachShares($allHoldings->take(self::DEFAULT_HOLDINGS_LIMIT), $totalUsdValue)->all();

            return [
                'available' => $totalUsdValue > 0 && $holdings !== [],
                'wallet' => [
                    'address' => $walletAddress,
                    'chain_ids' => $normalizedChainSlugs,
                ],
                'total_usd_value' => $totalUsdValue,
                'tokens' => $tokens,
                'protocols' => $protocols,
                'holdings' => $holdings,
                'error' => null,
                'updated_at' => now()->toIso8601String(),
            ];
        });
    }

    private function fetchPositions(string $address, array $params = []): array
    {
        $positions = [];
        $nextUrl = '/wallets/' . $address . '/positions/';
        $queryParams = array_filter($params, fn ($value) => $value !== null && $value !== '');

        do {
            $response = str_starts_with($nextUrl, 'http')
                ? $this->http()->get($nextUrl)
                : $this->http()->get($nextUrl, $queryParams);

            $payload = $response->throw()->json();
            $positions = [...$positions, ...(array) data_get($payload, 'data', [])];
            $nextUrl = data_get($payload, 'links.next');
            $queryParams = [];
        } while ($nextUrl);

        return $positions;
    }

    private function mapAssetPosition(array $position): ?array
    {
        $symbol = (string) data_get($position, 'attributes.fungible_info.symbol', data_get($position, 'attributes.name', 'TOKEN'));
        $name = (string) data_get($position, 'attributes.fungible_info.name', data_get($position, 'attributes.name', $symbol));
        $decimals = (int) data_get($position, 'attributes.quantity.decimals', 18);
        $chain = (string) data_get($position, 'relationships.chain.data.id', '');
        $amount = data_get($position, 'attributes.quantity.float');
        $price = data_get($position, 'attributes.price');
        $address = $this->resolvePositionTokenAddress($position, $chain);
        $isNative = $address === null;

        return [
            'symbol' => $symbol,
            'name' => $name,
            'address' => $address,
            'decimals' => $decimals,
            'balance' => is_numeric($amount) ? (float) $amount : 0.0,
            'price' => is_numeric($price) ? (float) $price : 0.0,
            'is_native' => $isNative,
            'coingecko_id' => null,
        ];
    }

    private function mapProtocolPosition(array $position): array
    {
        $attributes = (array) data_get($position, 'attributes', []);
        $symbol = (string) data_get($position, 'attributes.fungible_info.symbol', data_get($position, 'attributes.name', 'POSITION'));
        $name = (string) data_get($position, 'attributes.name', $symbol);
        $quantity = is_numeric(data_get($attributes, 'quantity.float')) ? (float) data_get($attributes, 'quantity.float') : 0.0;
        $value = is_numeric(data_get($attributes, 'value')) ? (float) data_get($attributes, 'value') : 0.0;
        $positionType = (string) data_get($attributes, 'position_type', '');
        $protocolModule = (string) data_get($attributes, 'protocol_module', '');
        $groupId = (string) data_get($attributes, 'group_id', data_get($position, 'id', Str::uuid()->toString()));
        $chain = (string) data_get($position, 'relationships.chain.data.id', '');
        $externalUrl = data_get($position, 'attributes.application_metadata.url');
        $baseMeta = [
            'chain' => $chain,
            'position_type' => $positionType,
            'protocol_module' => $protocolModule,
            'link' => is_string($externalUrl) ? $externalUrl : null,
        ];

        if (
            $positionType === 'loan'
            || $value < 0
            || str_contains(strtolower($name), 'borrow')
            || str_contains(strtolower($name), 'debt')
        ) {
            return [
                'bucket' => 'loans',
                'item' => [
                    'name' => $name,
                    'symbol' => $symbol,
                    'balance' => abs($quantity),
                    'usd_value' => abs($value),
                    'side' => $positionType !== '' ? $positionType : 'borrowed',
                    'apy' => null,
                    'pnl_usd' => null,
                    ...$baseMeta,
                ],
            ];
        }

        if ($protocolModule === 'liquidity_pool' || $this->looksLikeLiquidityPosition($attributes)) {
            return [
                'bucket' => 'pools',
                'item' => [
                    'group_id' => $groupId,
                    'name' => $name,
                    'symbol' => $symbol,
                    'usd_value' => $value,
                    'apy' => null,
                    'tvl_usd' => $value,
                    'total_liquidity' => $quantity,
                    'total_borrowed' => null,
                    'long_token' => $symbol,
                    'short_token' => null,
                    ...$baseMeta,
                ],
            ];
        }

        return [
            'bucket' => 'tokens',
            'item' => [
                'name' => $name,
                'symbol' => $symbol,
                'balance' => $quantity,
                'usd_value' => $value,
                'apy' => null,
                'collateral' => in_array($positionType, ['deposit', 'locked'], true),
                ...$baseMeta,
            ],
        ];
    }

    private function collapseProtocolPools(array $items): array
    {
        $grouped = [];

        foreach ($items as $item) {
            $key = (string) ($item['group_id'] ?? Str::uuid()->toString());

            if (! isset($grouped[$key])) {
                $grouped[$key] = $item;
                continue;
            }

            $grouped[$key]['usd_value'] += (float) ($item['usd_value'] ?? 0);
            $grouped[$key]['tvl_usd'] += (float) ($item['tvl_usd'] ?? 0);
            $grouped[$key]['total_liquidity'] += (float) ($item['total_liquidity'] ?? 0);
            $existingSymbol = (string) ($grouped[$key]['symbol'] ?? '');
            $nextSymbol = (string) ($item['symbol'] ?? '');

            if ($nextSymbol !== '' && ! str_contains($existingSymbol, $nextSymbol)) {
                $grouped[$key]['symbol'] = trim($existingSymbol . ' / ' . $nextSymbol, ' /');
            }
        }

        return array_values(array_map(function (array $item) {
            unset($item['group_id']);

            return $item;
        }, $grouped));
    }

    private function mapTransparencyTokenHolding(array $position): ?array
    {
        $usdValue = data_get($position, 'attributes.value');
        if (! is_numeric($usdValue) || (float) $usdValue <= 0) {
            return null;
        }

        $chain = (string) data_get($position, 'relationships.chain.data.id', '');
        $symbol = data_get($position, 'attributes.fungible_info.symbol');

        return [
            'type' => 'token',
            'id' => (string) data_get($position, 'id', Str::uuid()->toString()),
            'name' => (string) data_get($position, 'attributes.fungible_info.name', data_get($position, 'attributes.name', $symbol ?: 'Token')),
            'symbol' => $symbol ? (string) $symbol : null,
            'chain' => $chain,
            'usd_value' => round((float) $usdValue, 2),
            'share' => 0.0,
            'amount' => is_numeric(data_get($position, 'attributes.quantity.float')) ? (float) data_get($position, 'attributes.quantity.float') : null,
            'price' => is_numeric(data_get($position, 'attributes.price')) ? (float) data_get($position, 'attributes.price') : null,
            'asset_usd_value' => round((float) $usdValue, 2),
            'debt_usd_value' => null,
            'logo_url' => data_get($position, 'attributes.fungible_info.icon.url'),
            'link' => data_get($position, 'attributes.application_metadata.url'),
        ];
    }

    private function mapTransparencyProtocolHolding(array $position): ?array
    {
        $usdValue = data_get($position, 'attributes.value');
        if (! is_numeric($usdValue) || (float) $usdValue <= 0) {
            return null;
        }

        return [
            'type' => 'defi',
            'id' => (string) data_get($position, 'id', Str::uuid()->toString()),
            'name' => (string) data_get($position, 'attributes.name', data_get($position, 'attributes.protocol', 'DeFi Position')),
            'symbol' => data_get($position, 'attributes.fungible_info.symbol')
                ? (string) data_get($position, 'attributes.fungible_info.symbol')
                : null,
            'chain' => (string) data_get($position, 'relationships.chain.data.id', ''),
            'usd_value' => round((float) $usdValue, 2),
            'share' => 0.0,
            'amount' => is_numeric(data_get($position, 'attributes.quantity.float')) ? (float) data_get($position, 'attributes.quantity.float') : null,
            'price' => is_numeric(data_get($position, 'attributes.price')) ? (float) data_get($position, 'attributes.price') : null,
            'asset_usd_value' => round((float) $usdValue, 2),
            'debt_usd_value' => null,
            'logo_url' => data_get($position, 'attributes.application_metadata.icon.url', data_get($position, 'attributes.fungible_info.icon.url')),
            'link' => data_get($position, 'attributes.application_metadata.url'),
        ];
    }

    private function attachShares($holdings, float $totalUsdValue)
    {
        return $holdings->map(function (array $holding) use ($totalUsdValue) {
            $holding['share'] = $totalUsdValue > 0
                ? round(((float) $holding['usd_value'] / $totalUsdValue) * 100, 1)
                : 0.0;

            return $holding;
        });
    }

    private function normalizeWalletChainId(string|int|null $chainId): ?string
    {
        return Conf::normalizeWeb3ChainIdToHex($chainId);
    }

    private function resolveChainSlug(?string $chainId): ?string
    {
        if ($chainId === null) {
            return null;
        }

        return self::CHAIN_MAP[$chainId] ?? null;
    }

    private function normalizeChainFilters(array $chainIds): array
    {
        return collect($chainIds)
            ->map(function ($chainId) {
                $hexChainId = Conf::normalizeWeb3ChainIdToHex($chainId);

                if ($hexChainId !== null) {
                    return $this->resolveChainSlug($hexChainId);
                }

                $raw = strtolower(trim((string) $chainId));

                return in_array($raw, self::CHAIN_MAP, true) ? $raw : null;
            })
            ->filter()
            ->values()
            ->all();
    }

    private function parseConfiguredChainIds(string $raw): array
    {
        return array_values(array_filter(array_map('trim', explode(',', $raw))));
    }

    private function normalizeWalletAddress(string $address, ?string $chainId): string
    {
        return $this->normalizeAddressValue($address, $chainId);
    }

    private function normalizeTransparencyWalletAddress(?string $address): string
    {
        return $this->normalizeAddressValue((string) $address);
    }

    private function normalizeAddressValue(string $address, ?string $chainId = null): string
    {
        $normalized = trim($address);

        if ($chainId === 'solana') {
            return $normalized;
        }

        return str_starts_with(strtolower($normalized), '0x') ? strtolower($normalized) : $normalized;
    }

    private function resolvePositionTokenAddress(array $position, string $chain): ?string
    {
        $implementations = (array) data_get($position, 'attributes.fungible_info.implementations', []);

        foreach ($implementations as $implementation) {
            if ((string) data_get($implementation, 'chain_id', '') !== $chain) {
                continue;
            }

            $address = strtolower(trim((string) data_get($implementation, 'address', '')));

            return $address !== '' ? $address : null;
        }

        return null;
    }

    private function isWalletPosition(array $position): bool
    {
        return (string) data_get($position, 'attributes.position_type', '') === 'wallet';
    }

    private function looksLikeLiquidityPosition(array $attributes): bool
    {
        $name = strtolower((string) ($attributes['name'] ?? ''));

        return str_contains($name, ' lp')
            || str_contains($name, 'pool')
            || str_contains($name, '/');
    }

    private function configuredTokenKeys(array $configuredTokens): array
    {
        return collect($configuredTokens)
            ->map(fn ($token) => [
                'chain' => Conf::normalizeWeb3ChainIdToHex($token->vision ?? null),
                'address' => strtolower(trim((string) ($token->color ?? ''))),
            ])
            ->values()
            ->all();
    }

    private function http(): PendingRequest
    {
        $apiKey = (string) config('services.zerion.api_key', '');

        return Http::baseUrl(self::BASE_URL)
            ->acceptJson()
            ->timeout(20)
            ->connectTimeout(10)
            ->withHeaders([
                'Authorization' => 'Basic ' . base64_encode($apiKey . ':'),
            ]);
    }

    private function isConfigured(): bool
    {
        return trim((string) config('services.zerion.api_key', '')) !== '';
    }

    private function emptyAssets(string $address, ?string $chainId, string $error, array $assets = []): array
    {
        return [
            'available' => $assets !== [],
            'address' => $address,
            'chain_id' => $chainId,
            'assets' => $assets,
            'error' => $error,
        ];
    }

    private function configuredAssetsForChain(array $configuredTokens, ?string $chainSlug)
    {
        return collect($configuredTokens)
            ->map(function ($token) use ($chainSlug) {
                $tokenChainSlug = $this->resolveChainSlug(Conf::normalizeWeb3ChainIdToHex($token->vision ?? null));
                if ($tokenChainSlug !== $chainSlug) {
                    return null;
                }

                return [
                    'address' => strtolower(trim((string) ($token->color ?? ''))),
                    'symbol' => strtoupper(trim((string) ($token->name ?? ''))),
                    'name' => trim((string) ($token->doc ?? '')),
                    'decimals' => (int) ($token->status ?? 18),
                    'balance' => is_numeric($token->last_balance ?? null) ? (float) $token->last_balance : 0.0,
                    'price' => is_numeric($token->last_price ?? null) ? (float) $token->last_price : 0.0,
                ];
            })
            ->filter()
            ->values();
    }

    private function fallbackAssetsFromConfiguredTokens($configuredAssets): array
    {
        return $configuredAssets
            ->filter(fn (array $asset) => (float) ($asset['balance'] ?? 0) > 0)
            ->map(fn (array $asset) => [
                'symbol' => $asset['symbol'] ?: 'TOKEN',
                'name' => $asset['name'] ?: ($asset['symbol'] ?: 'Token'),
                'address' => $asset['address'] !== '' ? $asset['address'] : null,
                'decimals' => $asset['decimals'] ?? 18,
                'balance' => (float) ($asset['balance'] ?? 0),
                'price' => (float) ($asset['price'] ?? 0),
                'is_native' => false,
                'coingecko_id' => null,
            ])
            ->values()
            ->all();
    }

    private function mergeConfiguredFallbackAssets($assets, $configuredAssets)
    {
        $existingAddresses = $assets
            ->map(fn (array $asset) => strtolower((string) ($asset['address'] ?? '')))
            ->filter()
            ->values()
            ->all();

        $fallbackAssets = $this->fallbackAssetsFromConfiguredTokens($configuredAssets);

        foreach ($fallbackAssets as $fallbackAsset) {
            $fallbackAddress = strtolower((string) ($fallbackAsset['address'] ?? ''));
            if ($fallbackAddress !== '' && in_array($fallbackAddress, $existingAddresses, true)) {
                continue;
            }

            $assets->push($fallbackAsset);
        }

        return $assets
            ->sortByDesc(fn (array $asset) => ((float) ($asset['balance'] ?? 0)) * ((float) ($asset['price'] ?? 0)))
            ->values();
    }

    private function emptyTransparencyOverview(string $address, array $chainIds, string $error): array
    {
        return [
            'available' => false,
            'wallet' => [
                'address' => $address,
                'chain_ids' => $chainIds,
            ],
            'total_usd_value' => 0,
            'tokens' => [],
            'protocols' => [],
            'holdings' => [],
            'error' => $error,
            'updated_at' => now()->toIso8601String(),
        ];
    }

    private function humanizeApiError(Throwable $exception): string
    {
        $message = $exception->getMessage();

        if (str_contains($message, '429') || str_contains(strtolower($message), 'too many requests')) {
            return 'Zerion API rate limit reached. Please retry in a minute.';
        }

        return 'Zerion data is temporarily unavailable.';
    }
}
