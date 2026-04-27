<?php

namespace App\Jobs;

use App\Models\Conf;
use App\Services\WalletProtocolService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class UpdateTokenDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public array $tokenIds,
        public string $walletAddress,
        public string $chainId
    ) {
    }

    public static function dispatchForWallet(Collection|array $tokens, string $walletAddress, string $chainId): void
    {
        $tokenIds = collect($tokens)
            ->map(fn ($token) => (int) data_get($token, 'id'))
            ->filter(fn (int $id) => $id > 0)
            ->values()
            ->all();

        if ($tokenIds === []) {
            return;
        }

        self::dispatch($tokenIds, $walletAddress, $chainId);
    }

    public function handle(WalletProtocolService $protocolService): void
    {
        $tokens = Conf::query()
            ->whereIn('id', $this->tokenIds)
            ->where('type', 'web3_token')
            ->get();

        if ($tokens->isEmpty()) {
            return;
        }

        $normalizedChainId = $this->normalizeChainId($this->chainId);
        $normalizedAddress = $this->normalizeAddress($this->walletAddress);
        $assetsPayload = $protocolService->loadAssets($normalizedAddress, $normalizedChainId, $tokens->all());
        $assets = collect((array) ($assetsPayload['assets'] ?? []));

        foreach ($tokens as $token) {
            $tokenAddress = strtolower(trim((string) ($token->color ?? '')));
            $asset = $assets->first(function (array $item) use ($tokenAddress) {
                return strtolower((string) ($item['address'] ?? '')) === $tokenAddress;
            });

            $balance = (float) data_get($asset, 'balance', 0);
            $price = (float) data_get($asset, 'price', 0);

            $token->update([
                'last_balance' => $balance,
                'last_price' => $price,
                'last_updated_at' => now(),
            ]);
        }
    }

    private function normalizeChainId(string $chainId): string
    {
        if (preg_match('/^0x[0-9a-f]+$/i', $chainId)) {
            return strtolower($chainId);
        }
        if (is_numeric($chainId)) {
            return '0x' . dechex((int) $chainId);
        }

        return strtolower($chainId);
    }

    private function normalizeAddress(string $address): string
    {
        return strtolower(trim($address));
    }
}
