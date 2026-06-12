<?php

namespace Tests\Unit;

use App\Services\OpenDataBotTransportService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use RuntimeException;
use Tests\TestCase;

class OpenDataBotTransportServiceTest extends TestCase
{
    public function test_it_sends_normalized_plate_and_api_key(): void
    {
        config()->set('services.opendatabot.transport_url', 'https://opendatabot.com/api/v4/transport');
        config()->set('services.opendatabot.api_token', 'test-token');

        Http::fake([
            'https://opendatabot.com/api/v4/transport*' => Http::response([
                'requestStatus' => 'ok',
                'brand' => 'VOLKSWAGEN',
                'model' => 'CADDY',
                'number' => 'АА0000АА',
            ]),
        ]);

        $result = (new OpenDataBotTransportService())->lookup(' аа 0000 аа ');

        $this->assertTrue($result['success']);
        $this->assertSame('АА0000АА', $result['plate']);
        $this->assertSame('VOLKSWAGEN', $result['data']['brand']);

        Http::assertSent(function (Request $request) {
            return $request->method() === 'GET'
                && $request->url() === 'https://opendatabot.com/api/v4/transport?number=%D0%90%D0%900000%D0%90%D0%90&apiKey=test-token';
        });
    }

    public function test_it_rejects_an_empty_plate(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new OpenDataBotTransportService())->lookup('---');
    }

    public function test_it_requires_an_api_token(): void
    {
        config()->set('services.opendatabot.transport_url', 'https://opendatabot.com/api/v4/transport');
        config()->set('services.opendatabot.api_token', '');
        Http::fake();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('OpenDataBot API token is not configured.');

        try {
            (new OpenDataBotTransportService())->lookup('AB2628IH');
        } finally {
            Http::assertNothingSent();
        }
    }
}
