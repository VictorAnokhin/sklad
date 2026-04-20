<?php

namespace App\Http\Controllers;

use App\Jobs\UpdateTokenDataJob;
use App\Models\Conf;
use App\Services\WalletProtocolService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Auth;

class WalletController extends Controller
{
    protected WalletProtocolService $protocolService;

    public function __construct(WalletProtocolService $protocolService)
    {
        $this->protocolService = $protocolService;
    }

    public function page()
    {
        $query = $this->web3TokensQuery();
        $web3Tokens = $query->get();
        $profileWallets = $this->resolveProfileWallets();
        $profileWallet = $profileWallets[0] ?? null;

        // Dispatch jobs to update token data for the first wallet
        if ($profileWallet) {
            foreach ($web3Tokens as $token) {
                UpdateTokenDataJob::dispatch($token->id, $profileWallet['address'], $profileWallet['chain_id'] ?? '0x1');
            }
        }

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

        // Dispatch jobs to update token data asynchronously
        foreach ($configuredTokens as $token) {
            UpdateTokenDataJob::dispatch($token->id, $address, $chainId ?? '0x1');
        }

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

        foreach ($configuredTokens as $token) {
            UpdateTokenDataJob::dispatch($token->id, $address, $chainId);
        }

        return response()->json(['message' => 'Token data update jobs dispatched.']);
    }
}