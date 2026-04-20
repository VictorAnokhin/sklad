<?php

namespace App\Jobs;

use App\Models\Conf;
use App\Services\WalletProtocolService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

class UpdateTokenDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $tokenId,
        public string $walletAddress,
        public string $chainId
    ) {}

    public function handle(WalletProtocolService $protocolService): void
    {
        $token = Conf::find($this->tokenId);
        if (!$token || $token->type !== 'web3_token') {
            return;
        }

        $normalizedChainId = $this->normalizeChainId($this->chainId);
        $normalizedAddress = $this->normalizeAddress($this->walletAddress);

        // Get balance
        $assets = $protocolService->loadAssets($normalizedAddress, $normalizedChainId, [$token]);
        $balance = 0.0;
        if (isset($assets['assets']) && is_array($assets['assets'])) {
            foreach ($assets['assets'] as $asset) {
                if (strtolower($asset['address'] ?? '') === strtolower($token->color ?? '')) {
                    $balance = (float) ($asset['balance'] ?? 0);
                    break;
                }
            }
        }

        // Get price from Coingecko
        $price = 0.0;
        $cgId = $token->constanta;
        if ($cgId && $cgId !== '0') {
            try {
                $response = Http::get("https://api.coingecko.com/api/v3/simple/price?ids={$cgId}&vs_currencies=usd");
                if ($response->successful()) {
                    $data = $response->json();
                    $price = (float) ($data[$cgId]['usd'] ?? 0);
                }
            } catch (\Exception $e) {
                // Log error if needed
            }
        }

        // Update token data
        $token->update([
            'last_balance' => $balance,
            'last_price' => $price,
            'last_updated_at' => now(),
        ]);
    }

    private function normalizeChainId(string $chainId): string
    {
        if (preg_match('/^0x[0-9a-f]+$/i', $chainId)) {
            return $chainId;
        }
        if (is_numeric($chainId)) {
            return '0x' . dechex((int) $chainId);
        }
        return $chainId;
    }

    private function normalizeAddress(string $address): string
    {
        return strtolower(trim($address));
    }
}