<?php

namespace App\Http\Controllers;

use App\Jobs\UpdateTokenDataJob;
use App\Models\Conf;
use App\Services\DefiLlamaTransparencyService;
use App\Services\WalletProtocolService;
use App\Services\ZeroExSwapService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Auth;

class WalletController extends Controller
{
    protected WalletProtocolService $protocolService;
    protected DefiLlamaTransparencyService $transparencyService;
    protected ZeroExSwapService $zeroExSwapService;

    public function __construct(
        WalletProtocolService $protocolService,
        DefiLlamaTransparencyService $transparencyService,
        ZeroExSwapService $zeroExSwapService
    )
    {
        $this->protocolService = $protocolService;
        $this->transparencyService = $transparencyService;
        $this->zeroExSwapService = $zeroExSwapService;
    }

    public function page()
    {
        $query = $this->web3TokensQuery();
        $web3Tokens = $query->get();
        $profileWallets = $this->resolveProfileWallets();
        $profileWallet = $profileWallets[0] ?? null;

        return view('pages.wallet', compact('web3Tokens', 'profileWallet', 'profileWallets'));
    }

    public function protocols(Request $request)
    {
        $validated = $request->validate([
            'address' => ['nullable', 'string', 'max:80'],
            'chain_id' => ['nullable', 'string', 'max:20'],
        ]);

        $profileWallet = $this->resolveProfileWallet();
        $address = $validated['address'] ?? ($profileWallet['address'] ?? null);
        $chainId = $validated['chain_id'] ?? ($profileWallet['chain_id'] ?? null);

        abort_unless($address, 422, 'Wallet address is required.');

        return response()->json(
            $this->protocolService->load(
                $address,
                $chainId
            )
        );
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
                'address' => strtolower($address),
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
            return response()->json($this->transparencyService->overview());
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
                    'connected_at' => optional($user->wallet_connected_at)->toIso8601String(),
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
                    'connected_at' => optional($wallet->connected_at)->toIso8601String(),
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
            'taker' => ['required', 'string', 'max:80'],
            'slippage_bps' => ['nullable', 'integer', 'min:10', 'max:1000'],
        ]);

        try {
            return response()->json($this->zeroExSwapService->price([
                'chainId' => $validated['chain_id'],
                'sellToken' => $validated['sell_token'],
                'buyToken' => $validated['buy_token'],
                'sellAmount' => $validated['sell_amount'],
                'taker' => $validated['taker'],
                'slippageBps' => (string) ($validated['slippage_bps'] ?? 100),
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
            return response()->json($this->zeroExSwapService->quote([
                'chainId' => $validated['chain_id'],
                'sellToken' => $validated['sell_token'],
                'buyToken' => $validated['buy_token'],
                'sellAmount' => $validated['sell_amount'],
                'taker' => $validated['taker'],
                'slippageBps' => (string) ($validated['slippage_bps'] ?? 100),
            ]));
        } catch (\Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => $exception->getMessage() ?: 'Failed to fetch swap quote.',
            ], 422);
        }
    }
}
