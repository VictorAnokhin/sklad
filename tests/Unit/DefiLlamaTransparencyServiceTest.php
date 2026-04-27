<?php

namespace Tests\Unit;

use App\Services\DefiLlamaTransparencyService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DefiLlamaTransparencyServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_it_returns_unavailable_when_slug_is_missing(): void
    {
        config()->set('services.defillama.protocol_slug', '');
        Http::fake();

        $service = new DefiLlamaTransparencyService();
        $payload = $service->overview();

        $this->assertFalse($payload['available']);
        $this->assertSame('DefiLlama protocol slug is not configured.', $payload['error']);
        Http::assertNothingSent();
    }

    public function test_it_maps_protocol_tvl_and_chain_breakdown(): void
    {
        config()->set('services.defillama.protocol_slug', 'mev-capital');

        Http::fake([
            'https://api.llama.fi/protocol/mev-capital' => Http::response([
                'name' => 'MEV Capital',
                'slug' => 'mev-capital',
                'url' => 'https://mevcapital.com',
                'logo' => 'https://icons.llama.fi/mev-capital.jpg',
                'tvl' => 1500000.12,
                'chainTvls' => [
                    'Ethereum' => 1000000.12,
                    'Base' => 500000,
                    'borrowed' => 999999,
                    'Ethereum-borrowed' => 123,
                ],
            ]),
        ]);

        $service = new DefiLlamaTransparencyService();
        $payload = $service->overview();

        $this->assertTrue($payload['available']);
        $this->assertSame('mev-capital', $payload['wallet']['address']);
        $this->assertSame(['ethereum', 'base'], $payload['wallet']['chain_ids']);
        $this->assertEquals(1500000.12, $payload['total_usd_value']);
        $this->assertSame([], $payload['tokens']);
        $this->assertCount(2, $payload['holdings']);
        $this->assertSame('Ethereum', $payload['holdings'][0]['name']);
        $this->assertEquals(1000000.12, $payload['holdings'][0]['usd_value']);
        $this->assertSame('https://mevcapital.com', $payload['holdings'][0]['link']);
    }

    public function test_it_returns_fallback_when_upstream_fails(): void
    {
        config()->set('services.defillama.protocol_slug', 'mev-capital');

        Http::fake([
            'https://api.llama.fi/protocol/mev-capital' => Http::response(['error' => 'upstream'], 500),
        ]);

        $service = new DefiLlamaTransparencyService();
        $payload = $service->overview();

        $this->assertFalse($payload['available']);
        $this->assertSame('DefiLlama protocol data is temporarily unavailable.', $payload['error']);

        Http::assertSent(function (Request $request) {
            return (string) $request->url() === 'https://api.llama.fi/protocol/mev-capital';
        });
    }
}
