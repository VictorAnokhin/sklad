<?php

namespace App\Services;

class DefiLlamaTransparencyService
{
    public function __construct(
        private readonly ZerionWalletService $zerionWalletService
    ) {
    }

    public function overview(?string $walletAddress = null): array
    {
        return $this->zerionWalletService->transparencyOverview($walletAddress);
    }
}
