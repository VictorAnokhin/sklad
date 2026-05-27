<?php

namespace Tests\Unit;

use App\Services\ChainalysisService;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class ChainalysisServiceTest extends TestCase
{
    public function test_non_usdt_assets_are_skipped(): void
    {
        Config::set('services.chainalysis.enabled', true);
        Config::set('services.chainalysis.mock_mode', true);

        $service = new ChainalysisService();
        $result = $service->screenIncomingCrypto('0xabc', 'USDC', 'ethereum');

        $this->assertTrue($result['allowed']);
        $this->assertSame('non_usdt_skipped', $result['reason']);
    }

    public function test_mock_mode_blocks_high_risk_pattern(): void
    {
        Config::set('services.chainalysis.enabled', true);
        Config::set('services.chainalysis.mock_mode', true);
        Config::set('services.chainalysis.api_key', '');

        $service = new ChainalysisService();
        $result = $service->screenIncomingCrypto('wallet-highrisk-demo', 'USDT', 'ethereum');

        $this->assertFalse($result['allowed']);
        $this->assertSame('SEVERE', $result['risk_level']);
        $this->assertSame('mock_high_risk_pattern', $result['reason']);
    }

    public function test_mock_mode_allows_clean_wallet(): void
    {
        Config::set('services.chainalysis.enabled', true);
        Config::set('services.chainalysis.mock_mode', true);
        Config::set('services.chainalysis.api_key', '');

        $service = new ChainalysisService();
        $result = $service->screenIncomingCrypto('0xCleanWallet123', 'USDT', 'sui');

        $this->assertTrue($result['allowed']);
        $this->assertSame('LOW', $result['risk_level']);
    }
}
