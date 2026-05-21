<?php

namespace Tests\Unit;

use App\Services\WalletProtocolService;
use App\Services\SuiDefiProtocolService;
use App\Services\ZerionWalletService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WalletProtocolServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_it_loads_wallet_assets_from_zerion_and_filters_to_configured_tokens(): void
    {
        config()->set('services.zerion.api_key', 'test-key');

        Http::fake([
            '*' => Http::response([
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
            '*' => Http::response([
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
                    [
                        'id' => 'gmx-glp',
                        'attributes' => [
                            'name' => 'GLP Position',
                            'position_type' => 'deposit',
                            'protocol_module' => 'vault',
                            'value' => 900,
                            'quantity' => ['float' => 15, 'decimals' => 18],
                            'fungible_info' => ['symbol' => 'GLP'],
                            'application_metadata' => ['name' => 'GMX'],
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
        $this->assertArrayHasKey('gmx', $payload);
        $this->assertSame('Aave V3', $payload['aave_v3']['name']);
        $this->assertCount(1, $payload['aave_v3']['tokens']);
        $this->assertSame('USDC', $payload['aave_v3']['tokens'][0]['symbol']);
        $this->assertCount(1, $payload['uniswap_v3']['pools']);
        $this->assertSame('WETH / USDC', $payload['uniswap_v3']['pools'][0]['symbol']);
        $this->assertEquals(2000.0, $payload['uniswap_v3']['pools'][0]['usd_value']);
        $this->assertSame('GMX', $payload['gmx']['name']);
        $this->assertCount(1, $payload['gmx']['tokens']);
        $this->assertSame('GLP', $payload['gmx']['tokens'][0]['symbol']);
    }

    public function test_it_keeps_configured_arbitrum_stablecoin_when_address_or_name_matches(): void
    {
        config()->set('services.zerion.api_key', 'test-key');

        Http::fake([
            '*' => Http::response([
                'links' => ['self' => 'https://api.zerion.io/v1/wallets/test/positions/'],
                'data' => [
                    [
                        'id' => 'eth-wallet',
                        'attributes' => [
                            'name' => 'Ether',
                            'position_type' => 'wallet',
                            'value' => 100,
                            'price' => 100,
                            'quantity' => ['float' => 0.05, 'decimals' => 18],
                            'fungible_info' => [
                                'name' => 'Ether',
                                'symbol' => 'ETH',
                                'implementations' => [],
                            ],
                        ],
                        'relationships' => [
                            'chain' => ['data' => ['id' => 'arbitrum']],
                        ],
                    ],
                    [
                        'id' => 'usdt0-wallet',
                        'attributes' => [
                            'name' => 'Tether USD',
                            'position_type' => 'wallet',
                            'value' => 750,
                            'price' => 1,
                            'quantity' => ['float' => 750, 'decimals' => 6],
                            'fungible_info' => [
                                'name' => 'Tether USD',
                                'symbol' => 'USDT0',
                                'implementations' => [
                                    [
                                        'chain_id' => 'arbitrum',
                                        'address' => '0xFd086bC7CD5C481DCC9C85ebE478A1C0b69FCbb9',
                                    ],
                                ],
                            ],
                        ],
                        'relationships' => [
                            'chain' => ['data' => ['id' => 'arbitrum']],
                        ],
                    ],
                ],
            ]),
        ]);

        $token = (object) [
            'name' => 'USDT',
            'doc' => 'Tether USD',
            'color' => '0xFd086bC7CD5C481DCC9C85ebE478A1C0b69FCbb9',
            'status' => '6',
            'vision' => '42161',
            'constanta' => 'tether',
        ];

        $service = new WalletProtocolService(new ZerionWalletService());
        $payload = $service->loadAssets('0xa79798c0637daea4ac7fccbd61371dbb08d1d002', '0xa4b1', [$token]);

        $this->assertTrue($payload['available']);
        $this->assertCount(2, $payload['assets']);
        $usdt = collect($payload['assets'])->firstWhere('symbol', 'USDT0');
        $this->assertSame('0xfd086bc7cd5c481dcc9c85ebe478a1c0b69fcbb9', $usdt['address']);
        $this->assertEquals(750.0, $usdt['balance']);
    }

    public function test_it_falls_back_to_saved_token_data_when_zerion_assets_are_unavailable(): void
    {
        config()->set('services.zerion.api_key', '');

        $token = (object) [
            'name' => 'USDT0',
            'doc' => 'Tether USD',
            'color' => '0xFd086bC7CD5C481DCC9C85ebE478A1C0b69FCbb9',
            'status' => '6',
            'vision' => '42161',
            'constanta' => 'tether',
            'last_balance' => '1337.500000',
            'last_price' => '1.00000000',
        ];

        $service = new WalletProtocolService(new ZerionWalletService());
        $payload = $service->loadAssets('0xa79798c0637daea4ac7fccbd61371dbb08d1d002', '0xa4b1', [$token]);

        $this->assertTrue($payload['available']);
        $this->assertSame('Zerion API key is not configured.', $payload['error']);
        $this->assertCount(1, $payload['assets']);
        $this->assertSame('USDT0', $payload['assets'][0]['symbol']);
        $this->assertSame('0xfd086bc7cd5c481dcc9c85ebe478a1c0b69fcbb9', $payload['assets'][0]['address']);
        $this->assertEquals(1337.5, $payload['assets'][0]['balance']);
        $this->assertEquals(1.0, $payload['assets'][0]['price']);
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

    public function test_it_does_not_call_zerion_for_protocol_positions_on_solana(): void
    {
        config()->set('services.zerion.api_key', 'test-key');

        Http::fake();

        $service = new WalletProtocolService(new ZerionWalletService());
        $payload = $service->load('9xQeWvG816bUx9EPjHmaT23yvVM2ZWbrrpZb9PusVFin', 'solana');

        $this->assertSame([], $payload);
        $this->assertCount(0, Http::recorded());
    }

    public function test_it_loads_suilend_and_navi_positions_from_sui_rpc(): void
    {
        config()->set('services.sui.rpc_url', 'https://sui.test');

        Http::fake([
            'https://sui.test' => Http::response([
                'jsonrpc' => '2.0',
                'result' => [
                    'data' => [
                        [
                            'data' => [
                                'objectId' => '0x111',
                                'type' => '0xabc::lending_market::ObligationOwnerCap',
                                'content' => ['fields' => ['id' => ['id' => '0x111']]],
                            ],
                        ],
                        [
                            'data' => [
                                'objectId' => '0x222',
                                'type' => '0xdef::incentive_v3::AccountCap',
                                'content' => ['fields' => ['id' => ['id' => '0x222']]],
                            ],
                        ],
                        [
                            'data' => [
                                'objectId' => '0xf0c2fcaad83946c09e40d04f597e3727ae8177546319a30c4810c2a598fbe578',
                                'type' => '0xabc::suilend::StrategyOwnerCap',
                                'content' => ['fields' => ['id' => ['id' => '0xf0c2fcaad83946c09e40d04f597e3727ae8177546319a30c4810c2a598fbe578']]],
                            ],
                        ],
                    ],
                    'hasNextPage' => false,
                    'nextCursor' => null,
                ],
            ]),
        ]);

        $service = new WalletProtocolService(new ZerionWalletService(), new SuiDefiProtocolService());
        $payload = $service->load('0x1234567890abcdef1234567890abcdef1234567890abcdef1234567890abcdef', 'sui', true);

        $this->assertArrayHasKey('suilend', $payload);
        $this->assertArrayHasKey('navi', $payload);
        $this->assertSame('Suilend', $payload['suilend']['name']);
        $this->assertSame('NAVI Protocol', $payload['navi']['name']);
        $this->assertSame('0x111', $payload['suilend']['pools'][0]['object_id']);
        $this->assertSame('0xf0c2fcaad83946c09e40d04f597e3727ae8177546319a30c4810c2a598fbe578', $payload['suilend']['pools'][1]['object_id']);
        $this->assertSame('Suilend StrategyOwnerCap', $payload['suilend']['pools'][1]['name']);
        $this->assertSame('0x222', $payload['navi']['pools'][0]['object_id']);
    }
}
