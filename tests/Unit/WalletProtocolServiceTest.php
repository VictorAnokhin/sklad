<?php

namespace Tests\Unit;

use App\Services\WalletProtocolService;
use App\Services\ZerionWalletService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WalletProtocolServiceTest extends TestCase
{
    public function test_it_loads_wallet_assets_from_zerion_and_filters_to_configured_tokens(): void
    {
        config()->set('services.zerion.api_key', 'test-key');

        Http::fake([
            'https://api.zerion.io/v1/wallets/*/positions/*' => Http::response([
                'links' => ['self' => 'https://api.zerion.io/v1/wallets/test/positions/'],
                'data' => [
                    [
                        'id' => 'eth-wallet',
                        'attributes' => [
                            'name' => 'Ether',
                            'position_type' => 'wallet',
                            'value' => 1800,
                            'price' => 1800,
                            'quantity' => ['float' => 1, 'decimals' => 18],
                            'fungible_info' => [
                                'name' => 'Ether',
                                'symbol' => 'ETH',
                                'implementations' => [],
                            ],
                        ],
                        'relationships' => [
                            'chain' => ['data' => ['id' => 'base']],
                        ],
                    ],
                    [
                        'id' => 'usdc-wallet',
                        'attributes' => [
                            'name' => 'USD Coin',
                            'position_type' => 'wallet',
                            'value' => 250,
                            'price' => 1,
                            'quantity' => ['float' => 250, 'decimals' => 6],
                            'fungible_info' => [
                                'name' => 'USD Coin',
                                'symbol' => 'USDC',
                                'implementations' => [
                                    [
                                        'chain_id' => 'base',
                                        'address' => '0x833589fCD6eDb6E08f4c7C32D4f71b54bdA02913',
                                    ],
                                ],
                            ],
                        ],
                        'relationships' => [
                            'chain' => ['data' => ['id' => 'base']],
                        ],
                    ],
                    [
                        'id' => 'dai-wallet',
                        'attributes' => [
                            'name' => 'DAI',
                            'position_type' => 'wallet',
                            'value' => 90,
                            'price' => 1,
                            'quantity' => ['float' => 90, 'decimals' => 18],
                            'fungible_info' => [
                                'name' => 'Dai Stablecoin',
                                'symbol' => 'DAI',
                                'implementations' => [
                                    [
                                        'chain_id' => 'base',
                                        'address' => '0x50c5725949A6F0c72E6C4a641F24049A917DB0Cb',
                                    ],
                                ],
                            ],
                        ],
                        'relationships' => [
                            'chain' => ['data' => ['id' => 'base']],
                        ],
                    ],
                ],
            ]),
        ]);

        $token = (object) [
            'name' => 'USDC',
            'doc' => 'USD Coin',
            'color' => '0x833589fCD6eDb6E08f4c7C32D4f71b54bdA02913',
            'status' => '6',
            'vision' => '8453',
            'constanta' => 'usd-coin',
        ];

        $service = new WalletProtocolService(new ZerionWalletService());
        $payload = $service->loadAssets('0x1234567890abcdef1234567890abcdef12345678', '0x2105', [$token]);

        $this->assertTrue($payload['available']);
        $this->assertSame('0x2105', $payload['chain_id']);
        $this->assertCount(2, $payload['assets']);
        $this->assertSame('ETH', $payload['assets'][0]['symbol']);
        $this->assertTrue($payload['assets'][0]['is_native']);
        $this->assertSame('USDC', $payload['assets'][1]['symbol']);
        $this->assertSame('0x833589fcd6edb6e08f4c7c32d4f71b54bda02913', $payload['assets'][1]['address']);
        $this->assertEquals(250.0, $payload['assets'][1]['balance']);
        $this->assertEquals(1.0, $payload['assets'][1]['price']);
    }

    public function test_it_groups_complex_positions_into_dynamic_protocol_buckets(): void
    {
        config()->set('services.zerion.api_key', 'test-key');

        Http::fake([
            'https://api.zerion.io/v1/wallets/*/positions/*' => Http::response([
                'links' => ['self' => 'https://api.zerion.io/v1/wallets/test/positions/'],
                'data' => [
                    [
                        'id' => 'aave-usdc',
                        'attributes' => [
                            'name' => 'Supplied USDC',
                            'position_type' => 'deposit',
                            'protocol_module' => 'lending',
                            'value' => 5000,
                            'quantity' => ['float' => 5000, 'decimals' => 6],
                            'fungible_info' => ['symbol' => 'USDC'],
                            'application_metadata' => ['name' => 'Aave V3'],
                        ],
                        'relationships' => [
                            'chain' => ['data' => ['id' => 'arbitrum']],
                        ],
                    ],
                    [
                        'id' => 'uni-eth',
                        'attributes' => [
                            'name' => 'ETH/USDC LP',
                            'position_type' => 'deposit',
                            'protocol_module' => 'liquidity_pool',
                            'group_id' => 'pool-1',
                            'value' => 1800,
                            'quantity' => ['float' => 0.8, 'decimals' => 18],
                            'fungible_info' => ['symbol' => 'WETH'],
                            'application_metadata' => ['name' => 'Uniswap V3'],
                        ],
                        'relationships' => [
                            'chain' => ['data' => ['id' => 'arbitrum']],
                        ],
                    ],
                    [
                        'id' => 'uni-usdc',
                        'attributes' => [
                            'name' => 'ETH/USDC LP',
                            'position_type' => 'deposit',
                            'protocol_module' => 'liquidity_pool',
                            'group_id' => 'pool-1',
                            'value' => 200,
                            'quantity' => ['float' => 200, 'decimals' => 6],
                            'fungible_info' => ['symbol' => 'USDC'],
                            'application_metadata' => ['name' => 'Uniswap V3'],
                        ],
                        'relationships' => [
                            'chain' => ['data' => ['id' => 'arbitrum']],
                        ],
                    ],
                ],
            ]),
        ]);

        $service = new WalletProtocolService(new ZerionWalletService());
        $payload = $service->load('0x1234567890abcdef1234567890abcdef12345678', '0xa4b1');

        $this->assertArrayHasKey('aave_v3', $payload);
        $this->assertArrayHasKey('uniswap_v3', $payload);
        $this->assertSame('Aave V3', $payload['aave_v3']['name']);
        $this->assertCount(1, $payload['aave_v3']['tokens']);
        $this->assertSame('USDC', $payload['aave_v3']['tokens'][0]['symbol']);
        $this->assertCount(1, $payload['uniswap_v3']['pools']);
        $this->assertSame('WETH / USDC', $payload['uniswap_v3']['pools'][0]['symbol']);
        $this->assertEquals(2000.0, $payload['uniswap_v3']['pools'][0]['usd_value']);
    }

    public function test_it_marks_unsupported_networks_as_unavailable_for_assets(): void
    {
        config()->set('services.zerion.api_key', 'test-key');

        $service = new WalletProtocolService(new ZerionWalletService());
        $payload = $service->loadAssets('0x1234567890abcdef1234567890abcdef12345678', '0x539');

        $this->assertFalse($payload['available']);
        $this->assertSame([], $payload['assets']);
        $this->assertSame('Unsupported network', $payload['error']);
    }
}
