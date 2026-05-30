<?php

namespace App\Http\Controllers;

use App\Services\ManagerAiBridgeClient;
use App\Services\WebchatIntelligenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class WebchatIntelligenceController extends Controller
{
    public function __construct(
        private readonly WebchatIntelligenceService $webchat,
        private readonly ManagerAiBridgeClient $managerAiBridge,
    ) {}

    public function config(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'fid' => ['required', 'integer', 'min:1', 'max:999999'],
            'site_domain' => ['nullable', 'string', 'max:120'],
        ]);

        return response()->json($this->webchat->configFor(
            (int) $payload['fid'],
            isset($payload['site_domain']) ? (string) $payload['site_domain'] : null,
        ));
    }

    public function event(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'fid' => ['required', 'integer', 'min:1', 'max:999999'],
            'visitor_uid' => ['required', 'string', 'max:100'],
            'session_token' => ['nullable', 'string', 'max:64'],
            'event_type' => ['required', 'string', 'max:80'],
            'funnel_step' => ['nullable', 'string', 'max:120'],
            'ui_variant_key' => ['nullable', 'string', 'max:120'],
            'site_domain' => ['nullable', 'string', 'max:120'],
            'page_url' => ['nullable', 'string', 'max:500'],
            'url' => ['nullable', 'string', 'max:500'],
            'page_path' => ['nullable', 'string', 'max:255'],
            'page_title' => ['nullable', 'string', 'max:255'],
            'referrer' => ['nullable', 'string', 'max:500'],
            'language' => ['nullable', 'string', 'max:10'],
            'timezone' => ['nullable', 'string', 'max:80'],
            'user_id' => ['nullable', 'integer', 'min:1'],
            'consent_analytics' => ['nullable', 'boolean'],
            'metadata' => ['nullable', 'array'],
            'occurred_at' => ['nullable', 'date'],
        ]);

        $result = $this->webchat->recordEvent($payload, $request);

        return response()->json([
            'ok' => true,
            'visitor_id' => $result['visitor']->id,
            'event_id' => $result['event']->id,
        ]);
    }

    public function summary(Request $request): JsonResponse
    {
        if (! $this->canReadAnalytics($request)) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $payload = $request->validate([
            'fid' => ['required', 'integer', 'min:1', 'max:999999'],
            'days' => ['nullable', 'integer', 'min:1', 'max:90'],
        ]);

        return response()->json($this->webchat->analyticsSummary(
            (int) $payload['fid'],
            (int) ($payload['days'] ?? 7),
        ));
    }

    public function syncManagerAi(Request $request): JsonResponse
    {
        if (! $this->canReadAnalytics($request)) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $payload = $request->validate([
            'fid' => ['required', 'integer', 'min:1', 'max:999999'],
            'days' => ['nullable', 'integer', 'min:1', 'max:90'],
            'mode' => ['nullable', 'string', 'in:discuss,execute'],
        ]);

        $summary = $this->webchat->analyticsSummary((int) $payload['fid'], (int) ($payload['days'] ?? 7));
        $knowledgeEntry = $this->webchat->cacheSummaryToKnowledgeBase($summary);

        if (! $this->managerAiBridge->enabled()) {
            return response()->json([
                'ok' => false,
                'message' => 'ManagerAI bridge is disabled.',
                'knowledge_entry_id' => $knowledgeEntry->id,
                'summary' => $summary,
            ], 503);
        }

        try {
            $result = $this->managerAiBridge->sendChatMessage([
                'fid' => (int) $payload['fid'],
                'manager_ai_mode' => $payload['mode'] ?? 'discuss',
                'message' => $summary['recommendation_prompt'],
                'page' => 'webchat-intelligence-summary',
                'language' => 'ru',
            ]);
        } catch (Throwable $e) {
            Log::warning('Webchat ManagerAI sync failed.', [
                'fid' => (int) $payload['fid'],
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'ok' => false,
                'message' => 'ManagerAI sync failed.',
                'error' => config('app.debug') ? $e->getMessage() : null,
                'knowledge_entry_id' => $knowledgeEntry->id,
                'summary' => $summary,
            ], 503);
        }

        return response()->json([
            'ok' => true,
            'manager_ai' => $result['manager_ai'] ?? null,
            'answer' => $result['answer'] ?? null,
            'knowledge_entry_id' => $knowledgeEntry->id,
            'summary' => $summary,
        ]);
    }

    private function canReadAnalytics(Request $request): bool
    {
        if ($request->user() !== null) {
            return true;
        }

        $secret = trim((string) config('services.manager_ai.bridge_secret', ''));
        if ($secret === '') {
            return false;
        }

        return hash_equals($secret, trim((string) $request->header('X-ManagerAI-Bridge-Secret', '')));
    }
}
