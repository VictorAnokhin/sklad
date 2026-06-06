<?php

namespace Tests\Feature;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SitemapRouteTest extends TestCase
{
    public function test_sitemap_short_route_proxies_upstream_project_12_xml(): void
    {
        Http::fake([
            'https://av8.fund/sitemap.xml?fid=12*' => Http::response(
                '<urlset><url><loc>https://av8.fund/</loc></url></urlset>',
                200,
                ['Content-Type' => 'text/xml; charset=UTF-8']
            ),
        ]);

        $response = $this->get('/sitemap');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/xml; charset=UTF-8');
        $response->assertHeader('Cache-Control', 'no-cache, no-store, must-revalidate');
        $response->assertSee('https://av8.fund/', false);

        Http::assertSent(function (Request $request) {
            return str_starts_with($request->url(), 'https://av8.fund/sitemap.xml?fid=12&_t=')
                && $request->method() === 'GET'
                && $request->hasHeader('Cache-Control', 'no-cache, no-store, must-revalidate')
                && $request->hasHeader('Pragma', 'no-cache');
        });
    }
}
