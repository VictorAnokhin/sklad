<?php

namespace App\Http\Controllers;

use App\Services\SitemapService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
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
