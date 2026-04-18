<?php

namespace App\Http\Controllers;

use App\Services\WalletProtocolService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class WalletController extends Controller
{
    public function __construct(
        private readonly WalletProtocolService $protocolService
    ) {
    }

    public function page()
    {
        $query = $this->web3TokensQuery();
        $web3Tokens = $query->get();
        $profileWallet = $this->resolveProfileWallet();

        return view('pages.wallet', compact('web3Tokens', 'profileWallet'));
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

    private function resolveProfileWallet(): ?array
    {
        $user = Auth::user();
        if (!$user) {
            return null;
        }

        if (Schema::hasTable('user_wallets')) {
            $wallet = DB::table('user_wallets')
                ->where('user_id', $user->id)
                ->orderByDesc('connected_at')
                ->orderByDesc('id')
                ->first(['address', 'network', 'connected_at']);

            if ($wallet && !empty($wallet->address)) {
                return [
                    'address' => (string) $wallet->address,
                    'chain_id' => $wallet->network,
                    'connected_at' => $wallet->connected_at,
                ];
            }
        }

        if (!empty($user->wallet_address)) {
            return [
                'address' => (string) $user->wallet_address,
                'chain_id' => $user->wallet_network,
                'connected_at' => $user->wallet_connected_at,
            ];
        }

        return null;
    }
}
