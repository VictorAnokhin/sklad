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
        $durationMs = $this->durationMs($payload['duration_ms'] ?? null, $payload);
        $ipHash = $this->hashNullable($request->ip());
        $userAgentHash = $this->hashNullable($request->userAgent());
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
        if ($durationMs > 0) {
            $counters['total_time_ms'] = (int) ($counters['total_time_ms'] ?? 0) + $durationMs;
        }

        $journey = $this->mergeJourney($visitor->journey, $payload, $eventType, $pageUrl, $durationMs, $now);
        $needsSummary = $this->inferNeedsSummary($payload, $eventType, $counters, $journey);

        $visitor->fill([
            'site_domain' => $siteDomain ?: $visitor->site_domain,
            'last_session_token' => $this->limitString((string) ($payload['session_token'] ?? ''), 64) ?: $visitor->last_session_token,
            'identified_user_id' => isset($payload['user_id']) ? (int) $payload['user_id'] : $visitor->identified_user_id,
            'language' => $this->limitString((string) ($payload['language'] ?? ''), 10) ?: $visitor->language,
            'timezone' => $this->limitString((string) ($payload['timezone'] ?? ''), 80) ?: $visitor->timezone,
            'last_seen_url' => $pageUrl ?: $visitor->last_seen_url,
            'last_seen_path' => $this->limitString((string) ($payload['page_path'] ?? $this->pathFromUrl($pageUrl)), 255) ?: $visitor->last_seen_path,
            'last_referrer' => $referrer ?: $visitor->last_referrer,
            'ip_hash' => $ipHash,
            'last_ip_hash' => $ipHash,
            'user_agent_hash' => $userAgentHash,
            'interests' => $this->mergeInterests($visitor->interests, $payload),
            'traits' => $this->mergeTraits($visitor->traits, $payload),
            'counters' => $counters,
            'journey' => $journey,
            'needs_summary' => $needsSummary,
            'total_time_ms' => (int) ($visitor->total_time_ms ?? 0) + $durationMs,
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
            'duration_ms' => $durationMs > 0 ? $durationMs : null,
            'ip_hash' => $ipHash,
            'user_agent_hash' => $userAgentHash,
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
            ->select('page_path', DB::raw('COUNT(*) as total'), DB::raw('SUM(duration_ms) as total_time_ms'))
            ->groupBy('page_path')
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->map(fn ($row) => [
                'page_path' => $row->page_path,
                'total' => (int) $row->total,
                'total_time_ms' => (int) $row->total_time_ms,
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
     * @return array<string, mixed>
     */
    public function visitorContext(int $fid, string $visitorUid): array
    {
        $visitorUid = $this->normalizeVisitorUid($visitorUid);
        $visitor = WebchatVisitor::forFid($fid)->where('visitor_uid', $visitorUid)->first();

        if ($visitor === null) {
            return [
                'known' => false,
                'proactive_message' => null,
                'analysis' => null,
            ];
        }

        $journey = array_slice(is_array($visitor->journey) ? $visitor->journey : [], -10);
        $needs = is_array($visitor->needs_summary) ? $visitor->needs_summary : [];
        $counters = is_array($visitor->counters) ? $visitor->counters : [];

        return [
            'known' => true,
            'visitor_uid' => $visitor->visitor_uid,
            'last_seen_path' => $visitor->last_seen_path,
            'first_seen_at' => $visitor->first_seen_at?->toIso8601String(),
            'last_seen_at' => $visitor->last_seen_at?->toIso8601String(),
            'total_time_ms' => (int) ($visitor->total_time_ms ?? $counters['total_time_ms'] ?? 0),
            'page_views' => (int) ($counters['page_views'] ?? 0),
            'chat_messages' => (int) ($counters['chat_messages'] ?? 0),
            'interests' => $visitor->interests ?? [],
            'journey' => $journey,
            'analysis' => $needs,
            'proactive_message' => $needs['proactive_message'] ?? null,
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function agentVisitors(array $filters): array
    {
        $fid = max(1, (int) ($filters['fid'] ?? 0));
        $limit = max(1, min((int) ($filters['limit'] ?? 50), 200));

        $query = WebchatVisitor::forFid($fid)->orderByDesc('last_seen_at')->orderByDesc('id');

        foreach (['visitor_uid', 'last_session_token', 'ip_hash', 'last_ip_hash', 'user_agent_hash'] as $key) {
            $value = trim((string) ($filters[$key] ?? ''));
            if ($value !== '') {
                $query->where($key, $value);
            }
        }

        if (! empty($filters['seen_since'])) {
            $query->where('last_seen_at', '>=', $this->occurredAt($filters['seen_since']));
        }

        $visitors = $query->limit($limit)->get();

        return [
            'fid' => $fid,
            'total_returned' => $visitors->count(),
            'visitors' => $visitors->map(fn (WebchatVisitor $visitor) => $this->serializeVisitor($visitor))->values()->toArray(),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function agentVisitor(int $fid, string $visitorUid): ?array
    {
        $visitor = WebchatVisitor::forFid($fid)
            ->where('visitor_uid', $this->normalizeVisitorUid($visitorUid))
            ->first();

        return $visitor === null ? null : $this->serializeVisitor($visitor, includeContext: true);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function agentUpsertVisitor(array $payload): array
    {
        $fid = max(1, (int) ($payload['fid'] ?? 0));
        $visitorUid = $this->normalizeVisitorUid((string) ($payload['visitor_uid'] ?? ''));
        $now = now();

        $visitor = WebchatVisitor::firstOrNew([
            'fid' => $fid,
            'visitor_uid' => $visitorUid,
        ]);

        $fillable = [
            'site_domain' => [120, 'string'],
            'last_session_token' => [64, 'string'],
            'identified_user_id' => [null, 'integer'],
            'language' => [10, 'string'],
            'timezone' => [80, 'string'],
            'last_seen_url' => [500, 'string'],
            'last_seen_path' => [255, 'string'],
            'last_referrer' => [500, 'string'],
            'ip_hash' => [64, 'hash'],
            'last_ip_hash' => [64, 'hash'],
            'user_agent_hash' => [64, 'hash'],
            'identification_confidence' => [null, 'float'],
        ];

        $updates = [];
        foreach ($fillable as $field => [$limit, $type]) {
            if (! array_key_exists($field, $payload)) {
                continue;
            }

            $updates[$field] = match ($type) {
                'integer' => $payload[$field] === null ? null : (int) $payload[$field],
                'float' => max(0, min(100, (float) $payload[$field])),
                'hash' => $this->limitString((string) $payload[$field], (int) $limit) ?: null,
                default => $this->limitString((string) $payload[$field], (int) $limit) ?: null,
            };
        }

        foreach (['interests', 'traits', 'counters', 'journey', 'needs_summary'] as $jsonField) {
            if (array_key_exists($jsonField, $payload)) {
                $updates[$jsonField] = is_array($payload[$jsonField]) ? $payload[$jsonField] : null;
            }
        }

        if (array_key_exists('total_time_ms', $payload)) {
            $updates['total_time_ms'] = max(0, (int) $payload['total_time_ms']);
        }
        if (array_key_exists('consent_analytics', $payload)) {
            $updates['consent_analytics'] = (bool) $payload['consent_analytics'];
        }
        if (! empty($payload['first_seen_at'])) {
            $updates['first_seen_at'] = $this->occurredAt($payload['first_seen_at']);
        } elseif (! $visitor->exists) {
            $updates['first_seen_at'] = $now;
        }
        if (! empty($payload['last_seen_at'])) {
            $updates['last_seen_at'] = $this->occurredAt($payload['last_seen_at']);
        } else {
            $updates['last_seen_at'] = $now;
        }

        $visitor->fill($updates);
        $visitor->save();

        return $this->serializeVisitor($visitor->refresh(), includeContext: true);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function agentEvents(array $filters): array
    {
        $fid = max(1, (int) ($filters['fid'] ?? 0));
        $limit = max(1, min((int) ($filters['limit'] ?? 100), 500));

        $query = WebchatEvent::forFid($fid)->orderByDesc('occurred_at')->orderByDesc('id');

        foreach (['visitor_uid', 'session_token', 'event_type', 'ip_hash', 'user_agent_hash'] as $key) {
            $value = trim((string) ($filters[$key] ?? ''));
            if ($value !== '') {
                $query->where($key, $value);
            }
        }

        if (! empty($filters['occurred_since'])) {
            $query->where('occurred_at', '>=', $this->occurredAt($filters['occurred_since']));
        }

        $events = $query->limit($limit)->get();

        return [
            'fid' => $fid,
            'total_returned' => $events->count(),
            'events' => $events->map(fn (WebchatEvent $event) => $this->serializeEvent($event))->values()->toArray(),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function agentStoreEvent(array $payload): array
    {
        $fid = max(1, (int) ($payload['fid'] ?? 0));
        $visitorUid = $this->normalizeVisitorUid((string) ($payload['visitor_uid'] ?? ''));
        $visitor = WebchatVisitor::firstOrNew([
            'fid' => $fid,
            'visitor_uid' => $visitorUid,
        ]);

        if (! $visitor->exists) {
            $visitor->fill([
                'site_domain' => $this->limitString((string) ($payload['site_domain'] ?? ''), 120) ?: null,
                'last_session_token' => $this->limitString((string) ($payload['session_token'] ?? ''), 64) ?: null,
                'last_seen_url' => $this->limitString((string) ($payload['page_url'] ?? ''), 500) ?: null,
                'last_seen_path' => $this->limitString((string) ($payload['page_path'] ?? ''), 255) ?: null,
                'ip_hash' => $this->limitString((string) ($payload['ip_hash'] ?? ''), 64) ?: null,
                'last_ip_hash' => $this->limitString((string) ($payload['ip_hash'] ?? ''), 64) ?: null,
                'user_agent_hash' => $this->limitString((string) ($payload['user_agent_hash'] ?? ''), 64) ?: null,
                'first_seen_at' => $this->occurredAt($payload['occurred_at'] ?? null),
                'last_seen_at' => $this->occurredAt($payload['occurred_at'] ?? null),
            ]);
            $visitor->save();
        }

        $event = WebchatEvent::create([
            'fid' => $fid,
            'webchat_visitor_id' => $visitor->id,
            'visitor_uid' => $visitorUid,
            'session_token' => $this->limitString((string) ($payload['session_token'] ?? ''), 64) ?: null,
            'event_type' => $this->limitString((string) ($payload['event_type'] ?? 'agent_event'), 80),
            'funnel_step' => $this->limitString((string) ($payload['funnel_step'] ?? ''), 120) ?: null,
            'ui_variant_key' => $this->limitString((string) ($payload['ui_variant_key'] ?? ''), 120) ?: null,
            'site_domain' => $this->limitString((string) ($payload['site_domain'] ?? ''), 120) ?: null,
            'page_url' => $this->limitString((string) ($payload['page_url'] ?? ''), 500) ?: null,
            'page_path' => $this->limitString((string) ($payload['page_path'] ?? ''), 255) ?: null,
            'page_title' => $this->limitString((string) ($payload['page_title'] ?? ''), 255) ?: null,
            'referrer' => $this->limitString((string) ($payload['referrer'] ?? ''), 500) ?: null,
            'language' => $this->limitString((string) ($payload['language'] ?? ''), 10) ?: null,
            'duration_ms' => $this->durationMs($payload['duration_ms'] ?? null, $payload) ?: null,
            'ip_hash' => $this->limitString((string) ($payload['ip_hash'] ?? ''), 64) ?: null,
            'user_agent_hash' => $this->limitString((string) ($payload['user_agent_hash'] ?? ''), 64) ?: null,
            'metadata' => is_array($payload['metadata'] ?? null) ? $payload['metadata'] : null,
            'occurred_at' => $this->occurredAt($payload['occurred_at'] ?? null),
        ]);

        return $this->serializeEvent($event);
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

    /**
     * @return array<string, mixed>
     */
    private function serializeVisitor(WebchatVisitor $visitor, bool $includeContext = false): array
    {
        $data = [
            'id' => $visitor->id,
            'fid' => $visitor->fid,
            'visitor_uid' => $visitor->visitor_uid,
            'site_domain' => $visitor->site_domain,
            'last_session_token' => $visitor->last_session_token,
            'identified_user_id' => $visitor->identified_user_id,
            'language' => $visitor->language,
            'timezone' => $visitor->timezone,
            'last_seen_url' => $visitor->last_seen_url,
            'last_seen_path' => $visitor->last_seen_path,
            'last_referrer' => $visitor->last_referrer,
            'ip_hash' => $visitor->ip_hash,
            'last_ip_hash' => $visitor->last_ip_hash,
            'user_agent_hash' => $visitor->user_agent_hash,
            'interests' => $visitor->interests ?? [],
            'traits' => $visitor->traits ?? [],
            'counters' => $visitor->counters ?? [],
            'journey' => $visitor->journey ?? [],
            'needs_summary' => $visitor->needs_summary ?? [],
            'total_time_ms' => (int) ($visitor->total_time_ms ?? 0),
            'consent_analytics' => (bool) $visitor->consent_analytics,
            'identification_confidence' => (float) $visitor->identification_confidence,
            'first_seen_at' => $visitor->first_seen_at?->toIso8601String(),
            'last_seen_at' => $visitor->last_seen_at?->toIso8601String(),
            'created_at' => $visitor->created_at?->toIso8601String(),
            'updated_at' => $visitor->updated_at?->toIso8601String(),
        ];

        if ($includeContext) {
            $data['visitor_context'] = $this->visitorContext((int) $visitor->fid, (string) $visitor->visitor_uid);
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeEvent(WebchatEvent $event): array
    {
        return [
            'id' => $event->id,
            'fid' => $event->fid,
            'webchat_visitor_id' => $event->webchat_visitor_id,
            'visitor_uid' => $event->visitor_uid,
            'session_token' => $event->session_token,
            'event_type' => $event->event_type,
            'funnel_step' => $event->funnel_step,
            'ui_variant_key' => $event->ui_variant_key,
            'site_domain' => $event->site_domain,
            'page_url' => $event->page_url,
            'page_path' => $event->page_path,
            'page_title' => $event->page_title,
            'referrer' => $event->referrer,
            'language' => $event->language,
            'duration_ms' => $event->duration_ms,
            'ip_hash' => $event->ip_hash,
            'user_agent_hash' => $event->user_agent_hash,
            'metadata' => $event->metadata ?? [],
            'occurred_at' => $event->occurred_at?->toIso8601String(),
            'created_at' => $event->created_at?->toIso8601String(),
            'updated_at' => $event->updated_at?->toIso8601String(),
        ];
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
     * @param  array<int, array<string, mixed>>|null  $current
     * @param  array<string, mixed>  $payload
     * @return array<int, array<string, mixed>>
     */
    private function mergeJourney(?array $current, array $payload, string $eventType, string $pageUrl, int $durationMs, Carbon $now): array
    {
        $journey = collect($current ?? [])
            ->filter(fn ($item) => is_array($item))
            ->values()
            ->toArray();

        $path = $this->limitString((string) ($payload['page_path'] ?? $this->pathFromUrl($pageUrl)), 255);
        if ($path === '') {
            return array_slice($journey, -30);
        }

        $title = $this->limitString((string) ($payload['page_title'] ?? ''), 255);
        $lastIndex = count($journey) - 1;
        $last = $lastIndex >= 0 ? $journey[$lastIndex] : null;
        $sameLastPage = is_array($last) && ($last['page_path'] ?? '') === $path;

        if ($eventType === 'page_view' || ! $sameLastPage) {
            $journey[] = [
                'page_path' => $path,
                'page_url' => $pageUrl ?: null,
                'page_title' => $title ?: null,
                'referrer' => $this->limitString((string) ($payload['referrer'] ?? ''), 500) ?: null,
                'first_seen_at' => $now->toIso8601String(),
                'last_seen_at' => $now->toIso8601String(),
                'views' => 1,
                'duration_ms' => max(0, $durationMs),
                'events' => [$eventType],
            ];
        } else {
            $journey[$lastIndex]['last_seen_at'] = $now->toIso8601String();
            $journey[$lastIndex]['duration_ms'] = (int) ($journey[$lastIndex]['duration_ms'] ?? 0) + max(0, $durationMs);
            $journey[$lastIndex]['views'] = (int) ($journey[$lastIndex]['views'] ?? 1) + ($eventType === 'page_view' ? 1 : 0);
            $events = is_array($journey[$lastIndex]['events'] ?? null) ? $journey[$lastIndex]['events'] : [];
            $events[] = $eventType;
            $journey[$lastIndex]['events'] = array_values(array_unique(array_slice($events, -12)));
        }

        return array_slice($journey, -30);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, int>  $counters
     * @param  array<int, array<string, mixed>>  $journey
     * @return array<string, mixed>
     */
    private function inferNeedsSummary(array $payload, string $eventType, array $counters, array $journey): array
    {
        $metadata = is_array($payload['metadata'] ?? null) ? $payload['metadata'] : [];
        $signals = [];
        $score = 0;

        $pathsText = mb_strtolower(implode(' ', array_map(
            fn ($item) => (string) ($item['page_path'] ?? '').' '.(string) ($item['page_title'] ?? ''),
            $journey
        )));

        $topics = [
            'pricing' => ['цена', 'price', 'стоим', 'оплат', 'checkout', 'cart', 'order'],
            'delivery' => ['достав', 'delivery', 'shipping'],
            'catalog' => ['goods', 'catalog', 'товар', 'каталог', 'product', 'search'],
            'support' => ['сто', 'service', 'support', 'help', 'ремонт', 'contact'],
            'lead' => ['заявк', 'request', 'contact', 'call', 'lead'],
        ];

        $matchedTopics = [];
        foreach ($topics as $topic => $needles) {
            foreach ($needles as $needle) {
                if (mb_stripos($pathsText, $needle) !== false) {
                    $matchedTopics[] = $topic;
                    $signals[] = "visited_{$topic}_content";
                    $score += 15;
                    break;
                }
            }
        }

        if ((int) ($counters['page_views'] ?? 0) >= 3) {
            $signals[] = 'multi_page_session';
            $score += 15;
        }
        if ((int) ($counters['total_time_ms'] ?? 0) >= 45000) {
            $signals[] = 'high_time_on_site';
            $score += 15;
        }
        if (in_array($eventType, ['quick_reply_clicked', 'cta_clicked', 'message_sent'], true)) {
            $signals[] = $eventType;
            $score += 20;
        }
        foreach (['intent', 'topic', 'product', 'cta_label', 'quick_reply_value'] as $key) {
            $value = trim((string) ($metadata[$key] ?? ''));
            if ($value !== '') {
                $signals[] = "{$key}:{$this->limitString($value, 50)}";
                $score += 10;
            }
        }

        $intent = $matchedTopics[0] ?? 'general_help';
        $message = $this->proactiveMessage($intent, array_slice($journey, -3));

        return [
            'likely_intent' => $intent,
            'confidence' => min(95, $score),
            'signals' => array_values(array_unique(array_slice($signals, -12))),
            'last_pages' => array_values(array_map(
                fn ($item) => [
                    'page_path' => $item['page_path'] ?? null,
                    'page_title' => $item['page_title'] ?? null,
                    'duration_ms' => (int) ($item['duration_ms'] ?? 0),
                ],
                array_slice($journey, -5)
            )),
            'proactive_message' => $message,
            'updated_at' => now()->toIso8601String(),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $recentJourney
     */
    private function proactiveMessage(string $intent, array $recentJourney): string
    {
        $lastKey = array_key_last($recentJourney);
        $lastPage = $lastKey === null ? '' : trim((string) ($recentJourney[$lastKey]['page_title'] ?? ''));

        return match ($intent) {
            'pricing' => 'Вижу, вы смотрите условия и стоимость. Могу помочь подобрать вариант и объяснить оформление заказа.',
            'delivery' => 'Похоже, вас интересует доставка. Могу подсказать сроки, условия и что нужно для оформления.',
            'catalog' => 'Вы просматриваете каталог. Могу быстро найти подходящий товар или открыть нужную категорию.',
            'support' => 'Похоже, нужен сервис или консультация. Опишите задачу, и я подскажу ближайший следующий шаг.',
            'lead' => 'Могу помочь оформить заявку и собрать нужные данные без лишних шагов.',
            default => $lastPage !== ''
                ? "Если нужна помощь по странице «{$lastPage}», я подскажу, что здесь можно сделать дальше."
                : 'Если нужна помощь, я могу подсказать следующий шаг по сайту.',
        };
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function durationMs(mixed $value, array $payload): int
    {
        $duration = is_numeric($value) ? (int) $value : 0;
        if ($duration <= 0) {
            $metadata = is_array($payload['metadata'] ?? null) ? $payload['metadata'] : [];
            $duration = is_numeric($metadata['duration_ms'] ?? null) ? (int) $metadata['duration_ms'] : 0;
        }

        return max(0, min($duration, 30 * 60 * 1000));
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
