<?php

namespace App\Services;

class WalletProtocolService
{
    public function __construct(
        private readonly ZerionWalletService $zerionWalletService
    ) {
    }

    public function load(string $address, string|int|null $chainId): array
    {
        return $this->zerionWalletService->loadProtocols($address, $chainId);
    }

    public function loadAssets(string $address, string|int|null $chainId, array $configuredTokens = []): array
    {
        return $this->zerionWalletService->loadAssets($address, $chainId, $configuredTokens);
    }
}
