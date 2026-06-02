<?php

namespace App\Http\Controllers;

use App\Services\WidgetIntelligenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WidgetIntelligenceController extends Controller
{
    public function __construct(
        private readonly WidgetIntelligenceService $widgetIntelligence,
    ) {}

    public function handshake(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'fid' => ['required', 'integer', 'min:1', 'max:999999'],
            'fingerprint_hash' => ['required', 'string', 'min:16', 'max:128'],
            'visitor_uid' => ['nullable', 'string', 'max:100'],
            'session_token' => ['nullable', 'string', 'max:64'],
            'site_domain' => ['nullable', 'string', 'max:120'],
            'traits' => ['nullable', 'array'],
        ]);

        return response()->json($this->widgetIntelligence->handshake($payload));
    }

    public function storeUnmetNeed(Request $request): JsonResponse
    {
        if (! $this->canUseAgentApi($request)) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $payload = $request->validate([
            'fid' => ['required', 'integer', 'min:1', 'max:999999'],
            'fingerprint_hash' => ['nullable', 'string', 'max:128'],
            'visitor_uid' => ['nullable', 'string', 'max:100'],
            'user_id' => ['nullable', 'integer', 'min:1'],
            'google_id' => ['nullable', 'string', 'max:191'],
            'email' => ['nullable', 'email', 'max:191'],
            'status' => ['nullable', 'string', 'in:pending,ready,resolved,ignored'],
            'search_query' => ['required', 'string', 'min:2', 'max:500'],
            'context' => ['nullable', 'array'],
            'traits' => ['nullable', 'array'],
            'site_domain' => ['nullable', 'string', 'max:120'],
        ]);

        return response()->json([
            'ok' => true,
            'unmet_need' => $this->widgetIntelligence->storeUnmetNeed($payload),
        ], 201);
    }

    private function canUseAgentApi(Request $request): bool
    {
        $secret = trim((string) config('services.manager_ai.bridge_secret', ''));
        if ($secret === '') {
            return false;
        }

        return hash_equals($secret, trim((string) $request->header('X-ManagerAI-Bridge-Secret', '')));
    }
}
