<?php

namespace App\Services;

use App\Models\UnmetNeed;
use App\Models\WidgetUserProfile;
use App\Support\MediaUrl;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WidgetIntelligenceService
{
    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function handshake(array $payload): array
    {
        $profile = $this->upsertProfile($payload);
        $actions = $this->triggerActionsForProfile($profile);

        return [
            'ok' => true,
            'profile' => $this->serializeProfile($profile),
            'trigger_actions' => $actions,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function upsertProfile(array $payload): WidgetUserProfile
    {
        $fid = max(1, (int) ($payload['fid'] ?? 0));
        $fingerprintHash = $this->limit((string) ($payload['fingerprint_hash'] ?? ''), 128);
        $now = now();

        $profile = WidgetUserProfile::firstOrNew([
            'fid' => $fid,
            'fingerprint_hash' => $fingerprintHash,
        ]);

        $profile->fill([
            'visitor_uid' => $this->limit((string) ($payload['visitor_uid'] ?? ''), 100) ?: $profile->visitor_uid,
            'user_id' => isset($payload['user_id']) ? (int) $payload['user_id'] : $profile->user_id,
            'google_id' => $this->limit((string) ($payload['google_id'] ?? ''), 191) ?: $profile->google_id,
            'email' => $this->limit((string) ($payload['email'] ?? ''), 191) ?: $profile->email,
            'last_session_token' => $this->limit((string) ($payload['session_token'] ?? $payload['last_session_token'] ?? ''), 64) ?: $profile->last_session_token,
            'site_domain' => $this->limit((string) ($payload['site_domain'] ?? ''), 120) ?: $profile->site_domain,
            'traits' => $this->mergeTraits($profile->traits, is_array($payload['traits'] ?? null) ? $payload['traits'] : []),
            'first_seen_at' => $profile->exists ? $profile->first_seen_at : $now,
            'last_seen_at' => $now,
        ]);
        $profile->save();

        $this->attachUnmetNeedsToProfile($profile);

        return $profile->refresh();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function linkGoogleProfile(array $payload): void
    {
        if (trim((string) ($payload['fingerprint_hash'] ?? '')) === '') {
            return;
        }

        $profile = $this->upsertProfile($payload);

        UnmetNeed::forFid((int) $profile->fid)
            ->where(function ($query) use ($profile): void {
                $query->where('fingerprint_hash', $profile->fingerprint_hash);
                if ($profile->visitor_uid) {
                    $query->orWhere('visitor_uid', $profile->visitor_uid);
                }
            })
            ->update([
                'widget_user_profile_id' => $profile->id,
                'user_id' => $profile->user_id,
                'google_id' => $profile->google_id,
                'email' => $profile->email,
                'updated_at' => now(),
            ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function storeUnmetNeed(array $payload): array
    {
        $profile = null;
        if (! empty($payload['fingerprint_hash'])) {
            $profile = $this->upsertProfile($payload);
        }

        $query = $this->limit((string) ($payload['search_query'] ?? ''), 500);
        $need = UnmetNeed::create([
            'fid' => max(1, (int) ($payload['fid'] ?? 0)),
            'widget_user_profile_id' => $profile?->id,
            'fingerprint_hash' => $this->limit((string) ($payload['fingerprint_hash'] ?? ''), 128) ?: null,
            'visitor_uid' => $this->limit((string) ($payload['visitor_uid'] ?? ''), 100) ?: null,
            'user_id' => isset($payload['user_id']) ? (int) $payload['user_id'] : $profile?->user_id,
            'google_id' => $this->limit((string) ($payload['google_id'] ?? ''), 191) ?: $profile?->google_id,
            'email' => $this->limit((string) ($payload['email'] ?? ''), 191) ?: $profile?->email,
            'status' => $this->limit((string) ($payload['status'] ?? 'pending'), 40) ?: 'pending',
            'search_query' => $query,
            'normalized_query' => $this->normalizeQuery($query),
            'context' => is_array($payload['context'] ?? null) ? $payload['context'] : null,
            'detected_at' => now(),
        ]);

        return $this->serializeNeed($need->refresh());
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function triggerActionsForProfile(WidgetUserProfile $profile): array
    {
        $needs = UnmetNeed::forFid((int) $profile->fid)
            ->whereIn('status', ['pending', 'ready'])
            ->where(function ($query) use ($profile): void {
                $query->where('widget_user_profile_id', $profile->id)
                    ->orWhere('fingerprint_hash', $profile->fingerprint_hash);
                if ($profile->visitor_uid) {
                    $query->orWhere('visitor_uid', $profile->visitor_uid);
                }
                if ($profile->google_id) {
                    $query->orWhere('google_id', $profile->google_id);
                }
                if ($profile->email) {
                    $query->orWhere('email', $profile->email);
                }
            })
            ->orderByDesc('updated_at')
            ->limit(5)
            ->get();

        $actions = [];
        foreach ($needs as $need) {
            $product = is_array($need->product_snapshot) ? $need->product_snapshot : null;
            if ($product === null || ($need->status !== 'ready')) {
                $product = $this->findProductForNeed((int) $need->fid, (string) $need->search_query);
            }

            if ($product === null) {
                continue;
            }

            if ($need->status !== 'ready') {
                $need->update([
                    'status' => 'ready',
                    'product_snapshot' => $product,
                    'ready_at' => now(),
                ]);
            }

            $actions[] = [
                'type' => 'product_available',
                'need_id' => $need->id,
                'message' => "Здравствуйте! Вы недавно искали «{$need->search_query}». Похожий товар уже есть в каталоге. Хотите посмотреть характеристики?",
                'ui' => [
                    'component' => 'product_card',
                    'product' => $product,
                    'actions' => [
                        ['type' => 'navigate', 'label' => 'Посмотреть', 'url' => $product['url']],
                        ['type' => 'google_pay', 'label' => 'Купить через Google Pay', 'product_id' => $product['id']],
                    ],
                ],
            ];
        }

        return array_slice($actions, 0, 3);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function findProductForNeed(int $fid, string $searchQuery): ?array
    {
        $query = $this->normalizeQuery($searchQuery);
        if (mb_strlen($query) < 2) {
            return null;
        }

        $item = DB::table('comp')
            ->leftJoin('descript as d', function ($join): void {
                $join->on('d.pnum', '=', 'comp.id')
                    ->whereColumn('d.firma', 'comp.firma');
            })
            ->select([
                'comp.id',
                'comp.nickname',
                'comp.nfoto as image',
                'comp.nfoto1 as image_thumb',
                'comp.pay as price',
                DB::raw("COALESCE(NULLIF(d.name, ''), NULLIF(d.name_ua, ''), NULLIF(d.name_en, ''), NULLIF(comp.nickname, ''), NULLIF(comp.namedoc, ''), NULLIF(comp.name, ''), CONCAT('Товар #', comp.id)) as name"),
            ])
            ->where('comp.firma', (string) $fid)
            ->where(function ($search) use ($query): void {
                $search->where('comp.nickname', 'LIKE', "%{$query}%")
                    ->orWhere('comp.namedoc', 'LIKE', "%{$query}%")
                    ->orWhere('comp.name', 'LIKE', "%{$query}%")
                    ->orWhere('comp.htmlkeys', 'LIKE', "%{$query}%")
                    ->orWhere('comp.htmlkeyspop', 'LIKE', "%{$query}%")
                    ->orWhere('d.name', 'LIKE', "%{$query}%")
                    ->orWhere('d.name_ua', 'LIKE', "%{$query}%")
                    ->orWhere('d.description', 'LIKE', "%{$query}%");
            })
            ->orderByDesc('comp.top')
            ->orderByDesc('comp.id')
            ->first();

        if ($item === null) {
            return null;
        }

        $identifier = trim((string) ($item->nickname ?? '')) ?: (string) $item->id;

        return [
            'id' => (int) $item->id,
            'code' => (string) ($item->nickname ?? ''),
            'name' => (string) $item->name,
            'price' => (float) ($item->price ?? 0),
            'currency' => 'UAH',
            'image' => MediaUrl::image($item->image ?? ''),
            'image_thumb' => MediaUrl::image($item->image_thumb ?? ''),
            'url' => '/goods/'.rawurlencode($identifier),
        ];
    }

    private function attachUnmetNeedsToProfile(WidgetUserProfile $profile): void
    {
        UnmetNeed::forFid((int) $profile->fid)
            ->whereNull('widget_user_profile_id')
            ->where(function ($query) use ($profile): void {
                $query->where('fingerprint_hash', $profile->fingerprint_hash);
                if ($profile->visitor_uid) {
                    $query->orWhere('visitor_uid', $profile->visitor_uid);
                }
            })
            ->update([
                'widget_user_profile_id' => $profile->id,
                'updated_at' => now(),
            ]);
    }

    /**
     * @param  array<string, mixed>|null  $current
     * @param  array<string, mixed>  $incoming
     * @return array<string, mixed>
     */
    private function mergeTraits(?array $current, array $incoming): array
    {
        return array_filter(array_merge($current ?? [], $incoming), static fn ($value) => $value !== null && $value !== '');
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeProfile(WidgetUserProfile $profile): array
    {
        return [
            'id' => $profile->id,
            'fid' => $profile->fid,
            'fingerprint_hash' => $profile->fingerprint_hash,
            'visitor_uid' => $profile->visitor_uid,
            'google_linked' => $profile->google_id !== null || $profile->user_id !== null,
            'email' => $profile->email,
            'last_seen_at' => $profile->last_seen_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeNeed(UnmetNeed $need): array
    {
        return [
            'id' => $need->id,
            'fid' => $need->fid,
            'status' => $need->status,
            'search_query' => $need->search_query,
            'fingerprint_hash' => $need->fingerprint_hash,
            'visitor_uid' => $need->visitor_uid,
            'google_id' => $need->google_id,
            'email' => $need->email,
            'product_snapshot' => $need->product_snapshot,
            'detected_at' => $need->detected_at?->toIso8601String(),
            'ready_at' => $need->ready_at?->toIso8601String(),
        ];
    }

    private function normalizeQuery(string $query): string
    {
        return Str::limit(trim(preg_replace('/\s+/u', ' ', mb_strtolower($query)) ?? ''), 500, '');
    }

    private function limit(string $value, int $limit): string
    {
        return Str::limit(trim($value), $limit, '');
    }
}
