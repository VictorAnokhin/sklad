<?php

namespace Tests\Unit;

use App\Models\Wallet;
use App\Services\WalletPortfolioService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WalletPortfolioServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_syncs_multichain_wallet_tokens_from_alchemy(): void
    {
        config()->set('services.alchemy.key', 'test-key');

        Http::fake([
            'https://api.g.alchemy.com/data/v1/*/assets/tokens/by-address' => Http::response([
                'data' => [
                    'tokens' => [
                        [
                            'network' => 'arb-mainnet',
                            'tokenAddress' => '0xFd086bC7CD5C481DCC9C85ebE478A1C0b69FCbb9',
                            'tokenBalance' => '0x000000000000000000000000000000000000000000000000000000004fb6fe70',
                            'tokenMetadata' => [
                                'decimals' => 6,
                                'logo' => 'https://example.com/usdt0.webp',
                                'name' => 'Tether USD',
                                'symbol' => 'USDT0',
                            ],
                            'tokenPrices' => [
                                ['currency' => 'usd', 'value' => '1.00'],
                            ],
                        ],
                        [
                            'network' => 'eth-mainnet',
                            'tokenAddress' => null,
                            'tokenBalance' => '0x1158e460913d0000',
                            'tokenMetadata' => [
                                'decimals' => 18,
                                'logo' => 'https://example.com/eth.webp',
                                'name' => 'Ethereum',
                                'symbol' => 'ETH',
                            ],
                            'tokenPrices' => [
                                ['currency' => 'usd', 'value' => '3200.12'],
                            ],
                        ],
                    ],
                    'pageKey' => null,
                ],
            ]),
        ]);

        $service = new WalletPortfolioService();
        $payload = $service->getTokens('0xa79798c0637daea4ac7fccbd61371dbb08d1d002');

        $this->assertSame('0xa79798c0637daea4ac7fccbd61371dbb08d1d002', $payload['address']);
        $this->assertCount(2, $payload['result']);
        $this->assertSame('eth', $payload['chains'][0]['chain']);
        $this->assertDatabaseHas('wallets', [
            'address' => '0xa79798c0637daea4ac7fccbd61371dbb08d1d002',
        ]);
        $this->assertDatabaseHas('wallet_tokens', [
            'chain' => 'arbitrum',
            'symbol' => 'USDT0',
            'token_address' => '0xfd086bc7cd5c481dcc9c85ebe478a1c0b69fcbb9',
            'is_spam' => false,
        ]);
    }

    public function test_it_returns_cached_tokens_without_spam_by_default(): void
    {
        $wallet = Wallet::query()->create([
            'address' => '0xa79798c0637daea4ac7fccbd61371dbb08d1d002',
        ]);

        $wallet->tokens()->createMany([
            [
                'chain' => 'arbitrum',
                'token_address' => '0xfd086bc7cd5c481dcc9c85ebe478a1c0b69fcbb9',
                'symbol' => 'USDT0',
                'name' => 'Tether USD',
                'balance' => '500.000000000000000000',
                'price_usd' => '1.00000000',
                'value_usd' => '500.00',
                'is_spam' => false,
                'synced_at' => now(),
            ],
            [
                'chain' => 'bsc',
                'token_address' => '0xspam',
                'symbol' => 'SPAM',
                'name' => 'Spam Token',
                'balance' => '1.000000000000000000',
                'price_usd' => '0.00000000',
                'value_usd' => '0.00',
                'is_spam' => true,
                'synced_at' => now(),
            ],
        ]);

        $service = new WalletPortfolioService();
        $payload = $service->getTokens('0xa79798c0637daea4ac7fccbd61371dbb08d1d002');

        $this->assertCount(1, $payload['result']);
        $this->assertSame('USDT0', $payload['result'][0]['symbol']);
    }
}
