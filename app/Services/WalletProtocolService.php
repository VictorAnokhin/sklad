<?php

namespace App\Services;

use App\Models\Conf;
use App\Models\Wallet;
use RuntimeException;

class WalletProtocolService
{
    private SuiDefiProtocolService $suiDefiProtocolService;

    public function __construct(
        private readonly ZerionWalletService $zerionWalletService,
        ?SuiDefiProtocolService $suiDefiProtocolService = null
    ) {
        $this->suiDefiProtocolService = $suiDefiProtocolService ?: app(SuiDefiProtocolService::class);
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

        $payload = $this->shouldLoadSuiProtocols($normalizedAddress, $normalizedChainId)
            ? $this->suiDefiProtocolService->loadProtocols($normalizedAddress, $refresh)
            : $this->zerionWalletService->loadProtocols($normalizedAddress, $normalizedChainId);

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
        $trimmed = trim($address);
        if ($trimmed === '') {
            return '';
        }

        if ((bool) preg_match('/^[1-9A-HJ-NP-Za-km-z]{32,44}$/', $trimmed)) {
            return $trimmed;
        }

        $normalized = strtolower($trimmed);

        return str_starts_with($normalized, '0x') ? $normalized : '';
    }

    private function shouldLoadSuiProtocols(string $address, ?string $chainId): bool
    {
        if ($chainId === 'sui') {
            return true;
        }

        return $chainId === null && (bool) preg_match('/^0x[a-f0-9]{41,64}$/', $address);
    }
}
