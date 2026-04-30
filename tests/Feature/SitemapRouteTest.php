<?php

namespace Tests\Feature;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SitemapRouteTest extends TestCase
{
    public function test_sitemap_short_route_proxies_upstream_project_2_xml(): void
    {
        Http::fake([
            'https://av8capital.space/sitemap.xml?fid=2*' => Http::response(
                '<urlset><url><loc>https://av8capital.space/</loc></url></urlset>',
                200,
                ['Content-Type' => 'text/xml; charset=UTF-8']
            ),
        ]);

        $response = $this->get('/sitemap');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/xml; charset=UTF-8');
        $response->assertHeader('Cache-Control', 'no-cache, no-store, must-revalidate');
        $response->assertSee('https://av8capital.space/', false);

        Http::assertSent(function (Request $request) {
            return str_starts_with($request->url(), 'https://av8capital.space/sitemap.xml?fid=2&_t=')
                && $request->method() === 'GET'
                && $request->hasHeader('Cache-Control', 'no-cache, no-store, must-revalidate')
                && $request->hasHeader('Pragma', 'no-cache');
        });
    }
}
