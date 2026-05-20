<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class WalrusProxyController extends Controller
{
    public function store(Request $request, string $network): Response
    {
        $network = strtolower(trim($network));
        if (! in_array($network, ['mainnet', 'testnet'], true)) {
            throw ValidationException::withMessages([
                'network' => 'Unsupported Walrus network.',
            ]);
        }

        $publisherUrl = (string) config("walrus.publishers.{$network}", '');
        if ($publisherUrl === '') {
            throw ValidationException::withMessages([
                'publisher' => "Walrus {$network} publisher is not configured.",
            ]);
        }

        $body = $request->getContent();
        $maxUploadBytes = (int) config('walrus.max_upload_bytes', 26214400);
        if ($maxUploadBytes > 0 && strlen($body) > $maxUploadBytes) {
            throw ValidationException::withMessages([
                'file' => 'Walrus upload exceeds configured size limit.',
            ]);
        }

        $query = $request->query();
        $allowedQuery = array_intersect_key($query, array_flip([
            'epochs',
            'deletable',
            'permanent',
            'send_object_to',
        ]));
        $queryString = http_build_query($allowedQuery, '', '&', PHP_QUERY_RFC3986);
        $targetUrl = rtrim($publisherUrl, '/').'/v1/blobs'.($queryString ? '?'.$queryString : '');
        $contentType = $request->headers->get('content-type', 'application/octet-stream');

        $upstream = Http::timeout((int) config('walrus.timeout', 120))
            ->withBody($body, $contentType)
            ->put($targetUrl);

        return response($upstream->body(), $upstream->status())
            ->header('Content-Type', $upstream->header('Content-Type') ?: 'application/json');
    }
}
