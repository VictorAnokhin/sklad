<?php

namespace App\Services;

use App\Models\Conf;
use App\Models\Wallet;
use RuntimeException;

class WalletProtocolService
{
    public function __construct(
        private readonly ZerionWalletService $zerionWalletService
    ) {
    }

    public function load(string $address, string|int|null $chainId, bool $refresh = false): array
    {
        $normalizedAddress = $this->normalizeAddress($address);
        if ($normalizedAddress === '') {
            throw new RuntimeException('Wallet address is required.');
        }

        $normalizedChainId = Conf::normalizeWeb3ChainIdToHex($chainId);
        $snapshotChainId = $normalizedChainId ?? 'all';

        $wallet = Wallet::query()->firstOrCreate([
            'address' => $normalizedAddress,
        ]);

        $snapshot = $wallet->protocolSnapshots()
            ->where('chain_id', $snapshotChainId)
            ->first();

        if (! $refresh && $snapshot) {
            return is_array($snapshot->payload) ? $snapshot->payload : [];
        }

        $payload = $this->zerionWalletService->loadProtocols($normalizedAddress, $normalizedChainId);

        $wallet->protocolSnapshots()->updateOrCreate(
            ['chain_id' => $snapshotChainId],
            [
                'payload' => $payload,
                'synced_at' => now(),
            ]
        );

        return $payload;
    }

    public function loadAssets(string $address, string|int|null $chainId, array $configuredTokens = []): array
    {
        return $this->zerionWalletService->loadAssets($address, $chainId, $configuredTokens);
    }

    private function normalizeAddress(string $address): string
    {
        $normalized = strtolower(trim($address));

        return str_starts_with($normalized, '0x') ? $normalized : '';
    }
}
