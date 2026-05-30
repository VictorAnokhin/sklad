<?php

namespace App\Services;

use App\Models\AiKnowledgeBase;
use App\Models\WebchatEvent;
use App\Models\WebchatUiConfig;
use App\Models\WebchatVisitor;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WebchatIntelligenceService
{
    /**
     * @param  array<string, mixed>  $payload
     * @return array{visitor: WebchatVisitor, event: WebchatEvent}
     */
    public function recordEvent(array $payload, Request $request): array
    {
        $fid = max(1, (int) ($payload['fid'] ?? 0));
        $visitorUid = $this->normalizeVisitorUid((string) ($payload['visitor_uid'] ?? ''));
        $pageUrl = $this->limitString((string) ($payload['page_url'] ?? $payload['url'] ?? ''), 500);
        $referrer = $this->limitString((string) ($payload['referrer'] ?? ''), 500);
        $siteDomain = $this->resolveDomain(
            (string) ($payload['site_domain'] ?? ''),
            $pageUrl,
            $request->headers->get('origin') ?: $request->headers->get('referer')
        );
        $now = now();

        $visitor = WebchatVisitor::firstOrNew([
            'fid' => $fid,
            'visitor_uid' => $visitorUid,
        ]);

        $counters = is_array($visitor->counters) ? $visitor->counters : [];
        $eventType = $this->limitString((string) ($payload['event_type'] ?? 'unknown'), 80);
        $counters['events_total'] = (int) ($counters['events_total'] ?? 0) + 1;
        $counters[$eventType] = (int) ($counters[$eventType] ?? 0) + 1;

        if ($eventType === 'page_view') {
            $counters['page_views'] = (int) ($counters['page_views'] ?? 0) + 1;
        }
        if (in_array($eventType, ['message_sent', 'assistant_answered'], true)) {
            $counters['chat_messages'] = (int) ($counters['chat_messages'] ?? 0) + 1;
        }
        if (in_array($eventType, ['cta_clicked', 'quick_reply_clicked', 'form_completed', 'order_created'], true)) {
            $counters['conversion_events'] = (int) ($counters['conversion_events'] ?? 0) + 1;
        }

        $visitor->fill([
            'site_domain' => $siteDomain ?: $visitor->site_domain,
            'last_session_token' => $this->limitString((string) ($payload['session_token'] ?? ''), 64) ?: $visitor->last_session_token,
            'identified_user_id' => isset($payload['user_id']) ? (int) $payload['user_id'] : $visitor->identified_user_id,
            'language' => $this->limitString((string) ($payload['language'] ?? ''), 10) ?: $visitor->language,
            'timezone' => $this->limitString((string) ($payload['timezone'] ?? ''), 80) ?: $visitor->timezone,
            'last_seen_url' => $pageUrl ?: $visitor->last_seen_url,
            'last_seen_path' => $this->limitString((string) ($payload['page_path'] ?? $this->pathFromUrl($pageUrl)), 255) ?: $visitor->last_seen_path,
            'last_referrer' => $referrer ?: $visitor->last_referrer,
            'ip_hash' => $this->hashNullable($request->ip()),
            'user_agent_hash' => $this->hashNullable($request->userAgent()),
            'interests' => $this->mergeInterests($visitor->interests, $payload),
            'traits' => $this->mergeTraits($visitor->traits, $payload),
            'counters' => $counters,
            'consent_analytics' => (bool) ($payload['consent_analytics'] ?? $visitor->consent_analytics ?? false),
            'identification_confidence' => $this->confidenceScore($payload, $counters),
            'first_seen_at' => $visitor->exists ? $visitor->first_seen_at : $now,
            'last_seen_at' => $now,
        ]);
        $visitor->save();

        $event = WebchatEvent::create([
            'fid' => $fid,
            'webchat_visitor_id' => $visitor->id,
            'visitor_uid' => $visitorUid,
            'session_token' => $this->limitString((string) ($payload['session_token'] ?? ''), 64) ?: null,
            'event_type' => $eventType,
            'funnel_step' => $this->limitString((string) ($payload['funnel_step'] ?? ''), 120) ?: null,
            'ui_variant_key' => $this->limitString((string) ($payload['ui_variant_key'] ?? ''), 120) ?: null,
            'site_domain' => $siteDomain ?: null,
            'page_url' => $pageUrl ?: null,
            'page_path' => $this->limitString((string) ($payload['page_path'] ?? $this->pathFromUrl($pageUrl)), 255) ?: null,
            'page_title' => $this->limitString((string) ($payload['page_title'] ?? ''), 255) ?: null,
            'referrer' => $referrer ?: null,
            'language' => $this->limitString((string) ($payload['language'] ?? ''), 10) ?: null,
            'metadata' => is_array($payload['metadata'] ?? null) ? $payload['metadata'] : null,
            'occurred_at' => $this->occurredAt($payload['occurred_at'] ?? null),
        ]);

        return ['visitor' => $visitor, 'event' => $event];
    }

    /**
     * @return array<string, mixed>
     */
    public function configFor(int $fid, ?string $siteDomain = null): array
    {
        $query = WebchatUiConfig::forFid($fid)->active()->orderByDesc('published_at')->orderByDesc('id');

        if ($siteDomain !== null && $siteDomain !== '') {
            $query->where(function ($q) use ($siteDomain): void {
                $q->where('site_domain', $siteDomain)->orWhereNull('site_domain');
            })->orderByRaw('site_domain IS NULL');
        }

        $config = $query->first();

        if ($config !== null) {
            return [
                'fid' => $fid,
                'variant_key' => $config->variant_key,
                'site_domain' => $config->site_domain,
                'config' => $config->config,
            ];
        }

        return [
            'fid' => $fid,
            'variant_key' => 'default',
            'site_domain' => $siteDomain,
            'config' => $this->defaultConfig($fid),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function analyticsSummary(int $fid, int $days = 7): array
    {
        $since = now()->subDays(max(1, min($days, 90)));
        $events = WebchatEvent::forFid($fid)->where('occurred_at', '>=', $since);
        $eventCounts = (clone $events)
            ->select('event_type', DB::raw('COUNT(*) as total'))
            ->groupBy('event_type')
            ->orderByDesc('total')
            ->get()
            ->pluck('total', 'event_type')
            ->map(fn ($value) => (int) $value)
            ->toArray();

        $dropOffs = (clone $events)
            ->whereIn('event_type', ['chat_closed', 'session_dropped'])
            ->select('funnel_step', DB::raw('COUNT(*) as total'))
            ->groupBy('funnel_step')
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->map(fn ($row) => [
                'funnel_step' => $row->funnel_step ?: 'unknown',
                'total' => (int) $row->total,
            ])
            ->values()
            ->toArray();

        $ctaClicks = (clone $events)
            ->whereIn('event_type', ['cta_clicked', 'quick_reply_clicked'])
            ->select('funnel_step', DB::raw('COUNT(*) as total'))
            ->groupBy('funnel_step')
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->map(fn ($row) => [
                'target' => $row->funnel_step ?: 'unknown',
                'total' => (int) $row->total,
            ])
            ->values()
            ->toArray();

        $topPages = (clone $events)
            ->whereNotNull('page_path')
            ->select('page_path', DB::raw('COUNT(*) as total'))
            ->groupBy('page_path')
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->map(fn ($row) => [
                'page_path' => $row->page_path,
                'total' => (int) $row->total,
            ])
            ->values()
            ->toArray();

        $visitors = WebchatVisitor::forFid($fid)->where('last_seen_at', '>=', $since);

        return [
            'fid' => $fid,
            'period' => [
                'days' => max(1, min($days, 90)),
                'since' => $since->toIso8601String(),
                'until' => now()->toIso8601String(),
            ],
            'totals' => [
                'events' => array_sum($eventCounts),
                'visitors' => (clone $visitors)->count(),
                'repeat_visitors' => (clone $visitors)->where('identification_confidence', '>=', 40)->count(),
                'chat_sessions' => (clone $events)->whereNotNull('session_token')->distinct('session_token')->count('session_token'),
            ],
            'events_by_type' => $eventCounts,
            'drop_offs' => $dropOffs,
            'cta_clicks' => $ctaClicks,
            'top_pages' => $topPages,
            'recommendation_prompt' => $this->recommendationPrompt($fid, $eventCounts, $dropOffs, $ctaClicks, $topPages),
        ];
    }

    /**
     * @param  array<string, mixed>  $summary
     */
    public function cacheSummaryToKnowledgeBase(array $summary): AiKnowledgeBase
    {
        $fid = (int) ($summary['fid'] ?? 0);

        return AiKnowledgeBase::create([
            'fid' => $fid,
            'title' => 'Webchat intelligence summary '.now()->format('Y-m-d H:i'),
            'content' => json_encode($summary, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT),
            'category' => 'webchat_intelligence',
            'source' => 'webchat_intelligence',
            'active' => true,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function defaultConfig(int $fid): array
    {
        return [
            'title' => null,
            'subtitle' => null,
            'welcome' => null,
            'placeholder' => null,
            'quick_replies' => [
                ['label' => 'Что вы ищете?', 'value' => 'Подскажите, что здесь можно сделать?', 'funnel_step' => 'intent_discovery'],
                ['label' => 'Узнать цену', 'value' => 'Подскажите цену и условия заказа.', 'funnel_step' => 'price_interest'],
                ['label' => 'Оформить заявку', 'value' => 'Хочу оформить заявку. Что нужно сделать?', 'funnel_step' => 'lead_intent'],
            ],
            'theme' => [
                'variant' => 'default',
            ],
            'tracking' => [
                'enabled' => true,
                'privacy' => 'first_party_cookie_and_hashed_network_signals',
            ],
            'fid' => $fid,
        ];
    }

    /**
     * @param  array<string, int>  $eventCounts
     * @param  array<int, array<string, mixed>>  $dropOffs
     * @param  array<int, array<string, mixed>>  $ctaClicks
     * @param  array<int, array<string, mixed>>  $topPages
     */
    private function recommendationPrompt(int $fid, array $eventCounts, array $dropOffs, array $ctaClicks, array $topPages): string
    {
        return "Проанализируй webchat intelligence для fid={$fid}. ".
            "Найди слабые места в конверсии, предложи новый JSON UI webchat, CTA, вопросы для UX-исследования и задачи для контента/дизайна/разработки. ".
            json_encode([
                'events_by_type' => $eventCounts,
                'drop_offs' => $dropOffs,
                'cta_clicks' => $ctaClicks,
                'top_pages' => $topPages,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function normalizeVisitorUid(string $visitorUid): string
    {
        $visitorUid = trim($visitorUid);
        if ($visitorUid === '') {
            return (string) Str::uuid();
        }

        return $this->limitString($visitorUid, 100);
    }

    private function resolveDomain(string $explicit, string $pageUrl, ?string $fallbackUrl): string
    {
        foreach ([$explicit, $pageUrl, (string) $fallbackUrl] as $candidate) {
            $candidate = trim($candidate);
            if ($candidate === '') {
                continue;
            }

            $host = parse_url($candidate, PHP_URL_HOST);
            if (is_string($host) && $host !== '') {
                return $this->limitString($host, 120);
            }

            if (preg_match('/^[a-z0-9.-]+\.[a-z]{2,}$/i', $candidate) === 1) {
                return $this->limitString($candidate, 120);
            }
        }

        return '';
    }

    private function pathFromUrl(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH);

        return is_string($path) ? $path : '';
    }

    private function hashNullable(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        return hash('sha256', $value.'|'.config('app.key'));
    }

    /**
     * @param  array<int, string>|null  $current
     * @param  array<string, mixed>  $payload
     * @return array<int, string>
     */
    private function mergeInterests(?array $current, array $payload): array
    {
        $interests = collect($current ?? []);
        $metadata = is_array($payload['metadata'] ?? null) ? $payload['metadata'] : [];

        foreach (['intent', 'topic', 'product', 'cta_label'] as $key) {
            $value = trim((string) ($metadata[$key] ?? $payload[$key] ?? ''));
            if ($value !== '') {
                $interests->push(Str::limit($value, 80, ''));
            }
        }

        return $interests->filter()->unique()->take(20)->values()->toArray();
    }

    /**
     * @param  array<string, mixed>|null  $current
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function mergeTraits(?array $current, array $payload): array
    {
        $traits = $current ?? [];
        $metadata = is_array($payload['metadata'] ?? null) ? $payload['metadata'] : [];

        foreach (['device', 'viewport', 'source', 'campaign'] as $key) {
            if (isset($metadata[$key])) {
                $traits[$key] = $metadata[$key];
            }
        }

        return $traits;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, int>  $counters
     */
    private function confidenceScore(array $payload, array $counters): float
    {
        if (!empty($payload['user_id']) || !empty($payload['wallet']) || !empty($payload['email']) || !empty($payload['phone'])) {
            return 95.0;
        }

        $score = 10.0;
        $score += min(35, ((int) ($counters['events_total'] ?? 0)) * 2);
        $score += min(25, ((int) ($counters['page_views'] ?? 0)) * 3);
        $score += min(20, ((int) ($counters['chat_messages'] ?? 0)) * 5);

        return min(90.0, $score);
    }

    private function occurredAt(mixed $value): Carbon
    {
        try {
            return $value ? Carbon::parse((string) $value) : now();
        } catch (\Throwable) {
            return now();
        }
    }

    private function limitString(string $value, int $limit): string
    {
        return Str::limit(trim($value), $limit, '');
    }
}
