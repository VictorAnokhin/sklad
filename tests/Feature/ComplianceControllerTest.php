<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class ComplianceControllerTest extends TestCase
{
    public function test_screen_incoming_crypto_requires_payload(): void
    {
        $response = $this->postJson('/api/compliance/screen-incoming-crypto', []);

        $response->assertStatus(422);
    }

    public function test_screen_incoming_usdt_returns_mock_result(): void
    {
        Config::set('services.chainalysis.enabled', true);
        Config::set('services.chainalysis.mock_mode', true);
        Config::set('services.chainalysis.api_key', '');

        $response = $this->postJson('/api/compliance/screen-incoming-crypto', [
            'address' => '0xCleanWallet123',
            'asset' => 'USDT',
            'network' => 'ethereum',
            'amount' => '100',
            'direction' => 'incoming',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('allowed', true);
        $response->assertJsonPath('asset', 'USDT');
        $response->assertJsonPath('provider', 'chainalysis');
    }

    public function test_screen_incoming_usdt_blocks_high_risk_wallet(): void
    {
        Config::set('services.chainalysis.enabled', true);
        Config::set('services.chainalysis.mock_mode', true);
        Config::set('services.chainalysis.api_key', '');

        $response = $this->postJson('/api/compliance/screen-incoming-crypto', [
            'address' => 'wallet-highrisk-demo',
            'asset' => 'USDT',
            'network' => 'solana',
        ]);

        $response->assertStatus(403);
        $response->assertJsonPath('allowed', false);
        $response->assertJsonPath('riskLevel', 'SEVERE');
    }
}
