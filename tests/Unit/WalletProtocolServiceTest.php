<?php

namespace Tests\Unit;

use App\Services\WalletProtocolService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WalletProtocolServiceTest extends TestCase
{
    public function test_it_maps_aave_payload_and_skips_gmx_requests(): void
    {
        config()->set('services.thegraph.gateway_url', 'https://gateway.thegraph.com/api');
        config()->set('services.thegraph.api_key', 'test-key');

        Http::fake([
            'https://arb1.arbitrum.io/rpc' => Http::sequence()
                ->push(['result' => '0x0'])
                ->push(['result' => '0x0']),
            'https://gateway.thegraph.com/api/test-key/subgraphs/id/DLuE98kEb5pQNXAcKFQGQgfSQ57Xdou4jnVbAEqMfy3B' => Http::response([
                'data' => [
                    'userReserves' => [
                        [
                            'currentATokenBalance' => '2500000',
                            'currentVariableDebt' => '500000',
                            'currentStableDebt' => '0',
                            'usageAsCollateralEnabledOnUser' => true,
                            'reserve' => [
                                'symbol' => 'USDC',
                                'name' => 'USD Coin',
                                'underlyingAsset' => '0xaf88d065e77c8cC2239327C5EDb3A432268e5831',
                                'decimals' => 6,
                                'liquidityRate' => '30000000000000000000000000',
                                'variableBorrowRate' => '50000000000000000000000000',
                                'availableLiquidity' => '400000000000',
                                'totalLiquidity' => '1000000000000',
                                'totalCurrentVariableDebt' => '250000000000',
                                'price' => [
                                    'priceInEth' => '100000000',
                                ],
                            ],
                        ],
                    ],
                ],
            ]),
        ]);

        $service = new WalletProtocolService();
        $payload = $service->load('0x1234567890abcdef1234567890abcdef12345678', '0xa4b1');

        $this->assertTrue($payload['aave']['available']);
        $this->assertCount(1, $payload['aave']['tokens']);
        $this->assertSame('USDC', $payload['aave']['tokens'][0]['symbol']);
        $this->assertEquals(2.5, $payload['aave']['tokens'][0]['balance']);
        $this->assertCount(1, $payload['aave']['loans']);
        $this->assertEquals(0.5, $payload['aave']['loans'][0]['balance']);
        $this->assertCount(1, $payload['aave']['pools']);

        $this->assertFalse($payload['gmx']['available']);
        $this->assertSame([], $payload['gmx']['tokens']);
        $this->assertSame([], $payload['gmx']['loans']);
        $this->assertSame([], $payload['gmx']['pools']);

        Http::assertSentCount(1);
    }

    public function test_it_loads_assets_from_rpc_without_wallet_provider(): void
    {
        Http::fake([
            'https://mainnet.base.org' => Http::sequence()
                ->push(['jsonrpc' => '2.0', 'id' => 1, 'result' => '0x16345785d8a0000'])
                ->push(['jsonrpc' => '2.0', 'id' => 1, 'result' => '0x5f5e100']),
        ]);

        $token = (object) [
            'name' => 'USDC',
            'doc' => 'USD Coin',
            'color' => '0x833589fCD6eDb6E08f4c7C32D4f71b54bdA02913',
            'status' => '6',
            'vision' => '8453',
            'constanta' => 'usd-coin',
        ];

        $service = new WalletProtocolService();
        $payload = $service->loadAssets('0x1234567890abcdef1234567890abcdef12345678', '0x2105', [$token]);

        $this->assertTrue($payload['available']);
        $this->assertSame('0x2105', $payload['chain_id']);
        $this->assertCount(2, $payload['assets']);
        $this->assertSame('ETH', $payload['assets'][0]['symbol']);
        $this->assertEquals(0.1, $payload['assets'][0]['balance']);
        $this->assertSame('USDC', $payload['assets'][1]['symbol']);
        $this->assertEquals(100, $payload['assets'][1]['balance']);
    }

    public function test_it_marks_unsupported_networks_as_unavailable(): void
    {
        $service = new WalletProtocolService();
        $payload = $service->load('0x1234567890abcdef1234567890abcdef12345678', '0x539');

        $this->assertFalse($payload['aave']['available']);
        $this->assertFalse($payload['gmx']['available']);
        $this->assertSame([], $payload['aave']['tokens']);
        $this->assertSame([], $payload['gmx']['pools']);
    }
}
