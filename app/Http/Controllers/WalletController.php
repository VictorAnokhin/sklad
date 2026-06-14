<?php

namespace App\Http\Controllers;

use App\Jobs\UpdateTokenDataJob;
use App\Models\Conf;
use App\Models\Wallet;
use App\Services\DefiLlamaTransparencyService;
use App\Services\OneInchSwapService;
use App\Services\WalletPerformanceService;
use App\Services\WalletPortfolioService;
use App\Services\WalletProtocolService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Auth;

class WalletController extends Controller
{
    private const NETWORK_DEFAULTS = [
        '0x1' => [
            'name' => 'Ethereum',
            'native_symbol' => 'ETH',
            'native_name' => 'Ethereum',
            'icon_url' => 'https://cryptologos.cc/logos/ethereum-eth-logo.svg',
            'supports_swap' => true,
            'supports_protocols' => true,
        ],
        '0x38' => [
            'name' => 'BNB Chain',
            'native_symbol' => 'BNB',
            'native_name' => 'BNB',
            'icon_url' => 'https://cryptologos.cc/logos/bnb-bnb-logo.svg',
            'supports_swap' => true,
            'supports_protocols' => true,
        ],
        '0x89' => [
            'name' => 'Polygon',
            'native_symbol' => 'POL',
            'native_name' => 'Polygon',
            'icon_url' => 'https://cryptologos.cc/logos/polygon-matic-logo.svg',
            'supports_swap' => true,
            'supports_protocols' => true,
        ],
        '0xa' => [
            'name' => 'Optimism',
            'native_symbol' => 'ETH',
            'native_name' => 'Ethereum',
            'icon_url' => 'https://cryptologos.cc/logos/ethereum-eth-logo.svg',
            'supports_swap' => true,
            'supports_protocols' => true,
        ],
        '0x2105' => [
            'name' => 'Base',
            'native_symbol' => 'ETH',
            'native_name' => 'Ethereum',
            'icon_url' => 'https://cryptologos.cc/logos/ethereum-eth-logo.svg',
            'supports_swap' => true,
            'supports_protocols' => true,
        ],
        '0xa4b1' => [
            'name' => 'Arbitrum',
            'native_symbol' => 'ETH',
            'native_name' => 'Ethereum',
            'icon_url' => 'https://cryptologos.cc/logos/ethereum-eth-logo.svg',
            'supports_swap' => true,
            'supports_protocols' => true,
        ],
        '0xa86a' => [
            'name' => 'Avalanche',
            'native_symbol' => 'AVAX',
            'native_name' => 'Avalanche',
            'icon_url' => 'https://cryptologos.cc/logos/avalanche-avax-logo.svg',
            'supports_swap' => true,
            'supports_protocols' => true,
        ],
        'solana' => [
            'name' => 'Solana',
            'native_symbol' => 'SOL',
            'native_name' => 'Solana',
            'icon_url' => 'https://cryptologos.cc/logos/solana-sol-logo.svg',
            'supports_swap' => false,
            'supports_protocols' => true,
        ],
        'sui' => [
            'name' => 'Sui',
            'native_symbol' => 'SUI',
            'native_name' => 'Sui',
            'icon_url' => 'https://cryptologos.cc/logos/sui-sui-logo.svg',
            'supports_swap' => false,
            'supports_protocols' => true,
        ],
    ];

    protected WalletProtocolService $protocolService;
    protected DefiLlamaTransparencyService $transparencyService;
    protected OneInchSwapService $oneInchSwapService;

    public function __construct(
        WalletProtocolService $protocolService,
        DefiLlamaTransparencyService $transparencyService,
        OneInchSwapService $oneInchSwapService
    )
    {
        $this->protocolService = $protocolService;
        $this->transparencyService = $transparencyService;
        $this->oneInchSwapService = $oneInchSwapService;
    }

    public function page()
    {
        $query = $this->web3TokensQuery();
        $web3Tokens = $query->get();
        $web3Catalog = $this->buildWeb3CatalogFromCollection($web3Tokens);
        $profileWallets = $this->resolveProfileWallets();
        $profileWallet = $profileWallets[0] ?? null;

        return view('pages.wallet', compact('web3Tokens', 'web3Catalog', 'profileWallet', 'profileWallets'));
    }

    public function protocols(Request $request)
    {
        $validated = $request->validate([
            'address' => ['nullable', 'string', 'max:80'],
            'chain_id' => ['nullable', 'string', 'max:20'],
            'refresh' => ['nullable', 'boolean'],
        ]);

        $profileWallet = $this->resolveProfileWallet();
        $address = $validated['address'] ?? ($profileWallet['address'] ?? null);
        $chainId = $validated['chain_id'] ?? ($profileWallet['chain_id'] ?? null);

        abort_unless($address, 422, 'Wallet address is required.');

        return response()->json(
            $this->protocolService->load(
                $address,
                $chainId,
                $request->boolean('refresh', false)
            )
        );
    }

    public function manualDefiPositions(Request $request)
    {
        $validated = $request->validate([
            'address' => ['required', 'string', 'max:120'],
            'chain_id' => ['nullable', 'string', 'max:40'],
            'fid' => ['nullable', 'integer', 'min:0'],
        ]);

        if (! Schema::hasTable('manual_defi_positions')) {
            return response()->json([]);
        }

        $walletAddress = $this->normalizeManualDefiAddress((string) $validated['address']);
        $chainId = strtolower(trim((string) ($validated['chain_id'] ?? '')));
        $fid = (int) ($validated['fid'] ?? 0);

        return response()->json(
            DB::table('manual_defi_positions')
                ->where('wallet_address', $walletAddress)
                ->where('fid', $fid)
                ->when($chainId !== '', fn ($query) => $query->where('chain_id', $chainId))
                ->orderByDesc('id')
                ->get()
                ->map(fn ($row) => $this->manualDefiPositionPayload($row))
                ->values()
        );
    }

    public function storeManualDefiPosition(Request $request)
    {
        if (! Schema::hasTable('manual_defi_positions')) {
            return response()->json(['message' => 'Manual DeFi positions storage is not configured.'], 503);
        }

        $validated = $request->validate([
            'address' => ['required', 'string', 'max:120'],
            'chain_id' => ['required', 'string', 'max:40'],
            'fid' => ['nullable', 'integer', 'min:0'],
            'protocol_key' => ['required', 'string', 'max:80'],
            'protocol_name' => ['required', 'string', 'max:120'],
            'position_address' => ['required', 'string', 'max:180'],
        ]);

        $walletAddress = $this->normalizeManualDefiAddress((string) $validated['address']);
        $chainId = strtolower(trim((string) $validated['chain_id']));
        $protocolKey = strtolower(trim((string) $validated['protocol_key']));
        $protocolName = trim((string) $validated['protocol_name']);
        $positionAddress = trim((string) $validated['position_address']);
        $fid = (int) ($validated['fid'] ?? 0);
        $now = now();
        $key = [
            'fid' => $fid,
            'wallet_address' => $walletAddress,
            'chain_id' => $chainId,
            'protocol_key' => $protocolKey,
            'position_address' => $positionAddress,
        ];
        $values = [
            'protocol_name' => $protocolName,
            'updated_at' => $now,
        ];

        $existing = DB::table('manual_defi_positions')->where($key)->first();
        if ($existing) {
            DB::table('manual_defi_positions')->where('id', $existing->id)->update($values);
            $row = DB::table('manual_defi_positions')->where('id', $existing->id)->first();
        } else {
            $id = DB::table('manual_defi_positions')->insertGetId($key + $values + ['created_at' => $now]);
            $row = DB::table('manual_defi_positions')->where('id', $id)->first();
        }

        return response()->json($this->manualDefiPositionPayload($row), $existing ? 200 : 201);
    }

    public function overview(Request $request)
    {
        $validated = $request->validate([
            'address' => ['nullable', 'string', 'max:80'],
            'chain_id' => ['nullable', 'string', 'max:20'],
        ]);

        $profileWallet = $this->resolveProfileWallet();
        $address = $validated['address'] ?? ($profileWallet['address'] ?? null);
        $chainId = $validated['chain_id'] ?? ($profileWallet['chain_id'] ?? null);

        abort_unless($address, 422, 'Wallet address is required.');

        $configuredTokens = $this->web3TokensQuery()->get()->all();

        return response()->json([
            'wallet' => [
                'address' => $this->normalizeOverviewWalletAddress((string) $address),
                'chain_id' => $chainId,
                'source' => $profileWallet ? 'profile' : 'request',
            ],
            'assets' => $this->protocolService->loadAssets($address, $chainId, $configuredTokens),
            'protocols' => $this->protocolService->load($address, $chainId),
        ]);
    }

    public function transparencyOverview(Request $request)
    {
        $validated = $request->validate([
            'address' => ['nullable', 'string', 'max:80'],
            'chain_ids' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            return response()->json(
                $this->transparencyService->overview($validated['address'] ?? null)
            );
        } catch (\Throwable $exception) {
            report($exception);

            return response()->json([
                'available' => false,
                'wallet' => [
                    'address' => strtolower((string) config('services.zerion.wallet_address', '')),
                    'chain_ids' => [],
                ],
                'total_usd_value' => 0,
                'tokens' => [],
                'protocols' => [],
                'holdings' => [],
                'error' => 'Failed to load Zerion wallet overview.',
                'updated_at' => now()->toIso8601String(),
            ]);
        }
    }

    public function tokens(Request $request)
    {
        $validated = $request->validate([
            'chain_id' => ['nullable', 'string', 'max:20'],
        ]);

        $chainId = Conf::normalizeWeb3ChainIdToHex($validated['chain_id'] ?? null);
        $tokens = $this->web3TokensQuery()->get();

        return response()->json($this->buildWeb3CatalogFromCollection($tokens, $chainId));
    }

    public function walletTokens(string $address, Request $request, WalletPortfolioService $service)
    {
        try {
            return response()->json(
                $service->getTokensForSelection(
                    $address,
                    $request->boolean('include_spam', false),
                    $request->boolean('refresh', false),
                    $request->boolean('include_unselected', false)
                )
            );
        } catch (\Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => $exception->getMessage() ?: 'Failed to load wallet tokens.',
            ], 422);
        }
    }

    public function performance(string $address, Request $request, WalletPerformanceService $service)
    {
        $validated = $request->validate([
            'chain_id' => ['nullable', 'string', 'max:20'],
            'timeframe' => ['nullable', 'string', 'max:8'],
            'refresh' => ['nullable', 'boolean'],
        ]);

        try {
            return response()->json(
                $service->getPerformance(
                    $address,
                    $validated['chain_id'] ?? null,
                    $validated['timeframe'] ?? '1M',
                    $request->boolean('refresh', false)
                )
            );
        } catch (\Throwable $exception) {
            report($exception);

            return response()->json([
                'status' => 'analyzing',
                'message' => $exception->getMessage() ?: 'Wallet performance analysis is still running.',
                'wallet' => [
                    'address' => $address,
                    'chain_id' => $validated['chain_id'] ?? null,
                ],
                'timeframe' => strtoupper((string) ($validated['timeframe'] ?? '1M')),
                'cached' => false,
                'points' => [],
            ], 202);
        }
    }

    public function walletTokenSettings(string $address, Request $request, WalletPortfolioService $service)
    {
        if ($request->isMethod('get')) {
            return response()->json(
                $service->getTokensForSelection(
                    $address,
                    true,
                    $request->boolean('refresh', false),
                    true
                )
            );
        }

        $validated = $request->validate([
            'chain' => ['required', 'string', 'max:40'],
            'selected_keys' => ['array'],
            'selected_keys.*' => ['string', 'max:255'],
            'commissions' => ['array'],
            'commissions.*' => ['nullable', 'numeric', 'min:0', 'max:3'],
        ]);

        return response()->json(
            $service->saveSelectedTokens(
                $address,
                $validated['chain'],
                $validated['selected_keys'] ?? [],
                $validated['commissions'] ?? []
            )
        );
    }

    public function walletTokenSearch(string $address, Request $request, WalletPortfolioService $service)
    {
        $validated = $request->validate([
            'chain' => ['required', 'string', 'max:40'],
            'token_address' => ['required', 'string', 'max:80'],
        ]);

        return response()->json([
            'result' => $service->searchToken(
                $address,
                $validated['chain'],
                $validated['token_address']
            ),
        ]);
    }

    private function web3TokensQuery()
    {
        $query = DB::table('conf')->where('type', 'web3_token')->orderBy('name');

        $fid = session('fid');
        if ($fid !== null && $fid !== '') {
            $query->where('firma', $fid);
        } elseif (Auth::check()) {
            $query->where('firma', (string) (session('fid', '')));
        }

        return $query;
    }

    private function resolveProfileWallets(): array
    {
        $user = Auth::user();
        if (!$user) {
            return [];
        }

        if (!Schema::hasTable('user_wallets')) {
            $fallback = [];

            if ($user->wallet_address) {
                $fallback[] = [
                    'address' => $user->wallet_address,
                    'network' => $user->wallet_network,
                    'chain_id' => Conf::normalizeWeb3ChainIdToHex($user->wallet_network),
                    'connected_at' => optional($user->wallet_connected_at)->toIso8601String(),
                    'web3auth' => 0,
                ];
            }

            return $fallback;
        }

        return DB::table('user_wallets')
            ->where('user_id', $user->id)
            ->orderByDesc('connected_at')
            ->orderByDesc('id')
            ->get()
            ->map(function ($wallet) {
                return [
                    'address' => $wallet->address,
                    'network' => $wallet->network,
                    'chain_id' => Conf::normalizeWeb3ChainIdToHex($wallet->network),
                    'connected_at' => optional($wallet->connected_at)->toIso8601String(),
                    'web3auth' => property_exists($wallet, 'web3auth') ? (int) $wallet->web3auth : 0,
                ];
            })
            ->toArray();
    }

    private function resolveProfileWallet(): ?array
    {
        return $this->resolveProfileWallets()[0] ?? null;
    }

    public function updateTokenData(Request $request)
    {
        $validated = $request->validate([
            'wallet_address' => ['required', 'string', 'max:80'],
            'chain_id' => ['nullable', 'string', 'max:20'],
        ]);

        $address = $validated['wallet_address'];
        $chainId = $validated['chain_id'] ?? '0x1';

        $configuredTokens = $this->web3TokensQuery()->get();

        UpdateTokenDataJob::dispatchForWallet($configuredTokens, $address, $chainId);

        return response()->json(['message' => 'Token data update job dispatched.']);
    }

    public function swapWindow(Request $request)
    {
        return view('pages.swap_popup');
    }

    public function swapPrice(Request $request)
    {
        $validated = $request->validate([
            'chain_id' => ['required', 'string', 'max:20'],
            'sell_token' => ['required', 'string', 'max:80'],
            'buy_token' => ['required', 'string', 'max:80'],
            'sell_amount' => ['required', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:80'],
        ]);

        try {
            $feePercent = $this->resolveSwapCommission(
                $validated['chain_id'],
                $validated['sell_token'],
                $validated['address'] ?? null
            );

            $payload = $this->oneInchSwapService->quote([
                'chain_id' => $validated['chain_id'],
                'src' => $validated['sell_token'],
                'dst' => $validated['buy_token'],
                'amount' => $validated['sell_amount'],
                'fee' => $feePercent,
            ]);

            return response()->json(array_merge($payload, [
                'meta' => [
                    'provider' => '1inch',
                    'commission_percent' => $feePercent,
                ],
            ]));
        } catch (\Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => $exception->getMessage() ?: 'Failed to fetch swap price.',
            ], 422);
        }
    }

    public function swapQuote(Request $request)
    {
        $validated = $request->validate([
            'chain_id' => ['required', 'string', 'max:20'],
            'sell_token' => ['required', 'string', 'max:80'],
            'buy_token' => ['required', 'string', 'max:80'],
            'sell_amount' => ['required', 'string', 'max:100'],
            'taker' => ['required', 'string', 'max:80'],
            'slippage_bps' => ['nullable', 'integer', 'min:10', 'max:1000'],
        ]);

        try {
            $feePercent = $this->resolveSwapCommission(
                $validated['chain_id'],
                $validated['sell_token'],
                $validated['taker']
            );
            $slippagePercent = round((($validated['slippage_bps'] ?? 100) / 100), 2);

            $approvalRequired = false;
            $approveTx = null;

            if (! $this->isNativeTokenAddress($validated['sell_token'])) {
                $allowancePayload = $this->oneInchSwapService->allowance([
                    'chain_id' => $validated['chain_id'],
                    'src' => $validated['sell_token'],
                    'wallet_address' => $validated['taker'],
                ]);

                $allowance = trim((string) ($allowancePayload['allowance'] ?? '0'));
                $approvalRequired = $this->compareNumericStrings($allowance, trim((string) $validated['sell_amount'])) < 0;

                if ($approvalRequired) {
                    $approveTx = $this->oneInchSwapService->approveTransaction([
                        'chain_id' => $validated['chain_id'],
                        'src' => $validated['sell_token'],
                        'amount' => $validated['sell_amount'],
                    ]);

                    return response()->json([
                        'tx' => null,
                        'toTokenAmount' => null,
                        'meta' => [
                            'provider' => '1inch',
                            'commission_percent' => $feePercent,
                            'slippage_percent' => $slippagePercent,
                        ],
                        'approval_required' => true,
                        'approve_tx' => $approveTx,
                    ]);
                }
            }

            $payload = $this->oneInchSwapService->swap([
                'chain_id' => $validated['chain_id'],
                'src' => $validated['sell_token'],
                'dst' => $validated['buy_token'],
                'amount' => $validated['sell_amount'],
                'wallet_address' => $validated['taker'],
                'slippage' => $slippagePercent,
                'fee' => $feePercent,
            ]);

            return response()->json(array_merge($payload, [
                'meta' => [
                    'provider' => '1inch',
                    'commission_percent' => $feePercent,
                    'slippage_percent' => $slippagePercent,
                ],
                'approval_required' => $approvalRequired,
                'approve_tx' => $approveTx,
            ]));
        } catch (\Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => $exception->getMessage() ?: 'Failed to fetch swap quote.',
            ], 422);
        }
    }

    private function findConfiguredToken(string $chainId, string $tokenAddress): ?object
    {
        $normalizedChainId = Conf::normalizeWeb3ChainIdToHex($chainId);
        $normalizedAddress = strtolower(trim($tokenAddress));

        return $this->web3TokensQuery()
            ->get()
            ->first(function ($token) use ($normalizedChainId, $normalizedAddress) {
                return Conf::normalizeWeb3ChainIdToHex($token->vision ?? null) === $normalizedChainId
                    && strtolower(trim((string) ($token->color ?? ''))) === $normalizedAddress;
            });
    }

    private function resolveSwapCommission(string $chainId, string $tokenAddress, ?string $walletAddress = null): float
    {
        $normalizedAddress = strtolower(trim($tokenAddress));
        $chainSlug = collect([
            '0x1' => 'eth',
            '0x38' => 'bsc',
            '0x89' => 'polygon',
            '0xa4b1' => 'arbitrum',
        ])->get(Conf::normalizeWeb3ChainIdToHex($chainId) ?? '');

        if ($walletAddress && $chainSlug) {
            $wallet = Wallet::query()->where('address', strtolower(trim($walletAddress)))->first();
            $walletToken = $wallet?->tokens()
                ->where('chain', $chainSlug)
                ->where('token_address', $normalizedAddress)
                ->where('is_selected', true)
                ->first();

            if ($walletToken && $walletToken->commission !== null) {
                return (float) $walletToken->commission;
            }
        }

        $sellToken = $this->findConfiguredToken($chainId, $tokenAddress);

        return ($sellToken && property_exists($sellToken, 'commission') && $sellToken->commission !== null)
            ? (float) $sellToken->commission
            : 0.0;
    }

    private function isNativeTokenAddress(string $tokenAddress): bool
    {
        return strtolower(trim($tokenAddress)) === '0xeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeee';
    }

    private function compareNumericStrings(string $left, string $right): int
    {
        $left = ltrim($left, '0');
        $right = ltrim($right, '0');
        $left = $left === '' ? '0' : $left;
        $right = $right === '' ? '0' : $right;

        if (strlen($left) !== strlen($right)) {
            return strlen($left) <=> strlen($right);
        }

        return strcmp($left, $right);
    }

    private function buildWeb3CatalogFromCollection($tokens, ?string $filterChainId = null): array
    {
        $items = collect($tokens)
            ->map(fn ($token) => $this->formatConfiguredToken($token))
            ->filter(function (?array $token) use ($filterChainId) {
                if ($token === null) {
                    return false;
                }

                if ($filterChainId === null) {
                    return true;
                }

                return $token['chain_id'] === $filterChainId;
            })
            ->values();

        $networks = $items
            ->groupBy('chain_id')
            ->map(function ($chainTokens, $chainId) {
                $default = self::NETWORK_DEFAULTS[$chainId] ?? [
                    'name' => strtoupper((string) $chainId),
                    'native_symbol' => 'TOKEN',
                    'native_name' => 'Token',
                    'icon_url' => 'https://cryptologos.cc/logos/ethereum-eth-logo.svg',
                    'supports_swap' => $chainId !== 'solana',
                    'supports_protocols' => true,
                ];

                return [
                    'chain_id' => $chainId,
                    'chain_id_decimal' => $chainTokens->first()['chain_id_decimal'] ?? '',
                    'name' => $default['name'],
                    'native_symbol' => $default['native_symbol'],
                    'native_name' => $default['native_name'],
                    'icon_url' => $default['icon_url'],
                    'supports_swap' => (bool) $default['supports_swap'],
                    'supports_protocols' => (bool) $default['supports_protocols'],
                    'token_count' => $chainTokens->count(),
                ];
            })
            ->sortBy(function (array $network) {
                $order = array_keys(self::NETWORK_DEFAULTS);
                $index = array_search($network['chain_id'], $order, true);

                return $index === false ? 999 : $index;
            })
            ->values();

        return [
            'items' => $items->all(),
            'networks' => $networks->all(),
        ];
    }

    private function formatConfiguredToken(object $token): ?array
    {
        $chainIdHex = Conf::normalizeWeb3ChainIdToHex($token->vision ?? null) ?? (string) ($token->vision ?? '');
        $chainIdDecimal = Conf::normalizeWeb3ChainIdToDecimalString($token->vision ?? null) ?? '';
        $rawColor = trim((string) ($token->color ?? ''));
        $address = $chainIdHex === 'solana' ? $rawColor : strtolower($rawColor);

        if ($chainIdHex === '') {
            return null;
        }

        return [
            'id' => (int) $token->id,
            'symbol' => trim((string) ($token->name ?? '')),
            'name' => trim((string) ($token->doc ?? '')) ?: trim((string) ($token->name ?? '')),
            'address' => $address,
            'decimals' => (int) ($token->status ?? 18),
            'chain_id' => $chainIdHex,
            'chain_id_decimal' => $chainIdDecimal,
            'coingecko_id' => trim((string) ($token->constanta ?? '')),
            'commission' => property_exists($token, 'commission') && $token->commission !== null ? (float) $token->commission : 0.0,
        ];
    }

    private function normalizeOverviewWalletAddress(string $address): string
    {
        $trimmed = trim($address);

        if ((bool) preg_match('/^[1-9A-HJ-NP-Za-km-z]{32,44}$/', $trimmed)) {
            return $trimmed;
        }

        return strtolower($trimmed);
    }

    private function normalizeManualDefiAddress(string $address): string
    {
        return $this->normalizeOverviewWalletAddress($address);
    }

    private function manualDefiPositionPayload(?object $row): array
    {
        if (! $row) {
            return [];
        }

        return [
            'id' => (int) $row->id,
            'fid' => (int) ($row->fid ?? 0),
            'wallet_address' => (string) ($row->wallet_address ?? ''),
            'chain_id' => (string) ($row->chain_id ?? ''),
            'protocol_key' => (string) ($row->protocol_key ?? ''),
            'protocol_name' => (string) ($row->protocol_name ?? ''),
            'position_address' => (string) ($row->position_address ?? ''),
            'created_at' => (string) ($row->created_at ?? ''),
            'updated_at' => (string) ($row->updated_at ?? ''),
        ];
    }
}
