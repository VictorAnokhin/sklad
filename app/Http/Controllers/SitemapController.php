<?php

namespace App\Http\Controllers;

use App\Services\SitemapService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;

class SitemapController extends Controller
{
    public function short(Request $request): Response
    {
        $response = Http::timeout(15)
            ->withHeaders([
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
                'Pragma' => 'no-cache',
                'Expires' => '0',
            ])
            ->get('https://av8capital.space/sitemap.xml', [
                'fid' => 2,
                '_t' => now()->timestamp,
            ]);

        abort_unless($response->successful(), 502, 'Unable to fetch upstream sitemap.');

        return response($response->body(), $response->status())
            ->header('Content-Type', $response->header('Content-Type', 'text/xml; charset=UTF-8'))
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    public function index(Request $request, SitemapService $sitemapService): Response
    {
        $fid = $request->integer('fid');
        $host = $request->getHost();
        $sitemap = $sitemapService->read($fid > 0 ? $fid : null, $host);

        if ($sitemap === null) {
            $resolvedProject = $sitemapService->resolveProject($fid > 0 ? $fid : null, $host);
            $sitemap = $sitemapService->buildXml($resolvedProject?->id);
        }

        return response($sitemap)->header('Content-Type', 'text/xml');
    }
}
