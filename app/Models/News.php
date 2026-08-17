<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use App\Support\MediaUrl;
use Illuminate\Support\Facades\Schema;

class News extends Model
{
    protected $table = 'news';
    public $timestamps = false;
    protected $guarded = [];

    public static function getLatest(string $fid, int $limit = 5, ?string $locale = 'ru')
    {
        return DB::table('news')
            ->where(function ($query) use ($fid) {
                $query->where('firma', (int) $fid)
                    ->orWhere('firma', 0);
            })
            ->orderByDesc('hot')
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->map(fn ($item) => self::decorateItem($item, $locale));
    }

    public static function init(string $fid, int $pos = 0, int $perPage = 20, ?string $locale = 'ru', ?string $htmlkeys = null): array
    {
        $baseQuery = DB::table('news')
            ->where(function ($query) use ($fid) {
                $query->where('firma', (int) $fid)
                    ->orWhere('firma', 0);
            });

        $htmlkeys = trim((string) $htmlkeys);
        if ($htmlkeys !== '') {
            $baseQuery->where('htmlkeys', 'like', '%' . $htmlkeys . '%');
        }

        $total = (clone $baseQuery)->count();

        $items = (clone $baseQuery)
            ->orderByDesc('hot')
            ->orderByDesc('id')
            ->offset($pos)
            ->limit($perPage)
            ->get()
            ->map(function ($item) use ($locale) {
                $item = self::decorateItem($item, $locale);
                return $item;
            });

        return [
            'items' => $items,
            'total' => $total,
        ];
    }

    public static function findForView(int|string $identifier, string $fid, ?string $locale = 'ru'): ?object
    {
        $query = DB::table('news')
            ->where(function ($query) use ($fid) {
                $query->where('firma', (int) $fid)
                    ->orWhere('firma', 0);
            });

        if (is_numeric($identifier)) {
            $query->where('id', (int) $identifier);
        } elseif (Schema::hasColumn('news', 'url')) {
            $query->where('url', self::normalizeSlug((string) $identifier));
        } else {
            return null;
        }

        $item = $query->first();

        return $item ? self::decorateItem($item, $locale) : null;
    }

    public static function findOwned(int $id, string $fid): ?object
    {
        return DB::table('news')
            ->where('id', $id)
            ->where('firma', (int) $fid)
            ->first();
    }

    public static function emptyNews(string $fid): object
    {
        return (object) [
            'id' => 0,
            'title' => '',
            'title_ua' => '',
            'title_en' => '',
            'kratko' => '',
            'kratko_ua' => '',
            'kratko_en' => '',
            'txt' => '',
            'txt_ua' => '',
            'txt_en' => '',
            'foto' => '',
            'dt' => date('d-m-Y'),
            'time' => date('H:i:s'),
            'firma' => (int) $fid,
            'view' => 1,
            'hot' => 0,
            'always' => 0,
            'article' => 0,
            'tags' => '',
            'htmlkeys' => '',
            'url' => '',
        ];
    }

    public static function saveNews(int $id, string $fid, array $data): int
    {
        $columns = Schema::getColumnListing('news');
        $payload = array_intersect_key($data, array_flip($columns));

        foreach (['title', 'title_ua', 'title_en', 'kratko', 'kratko_ua', 'kratko_en', 'txt', 'txt_ua', 'txt_en', 'foto', 'tags', 'htmlkeys', 'codesocnet', 'top'] as $field) {
            if (array_key_exists($field, $payload) && $payload[$field] === null) {
                $payload[$field] = '';
            }
        }

        if (in_array('url', $columns, true) && ($id <= 0 || array_key_exists('url', $payload))) {
            $rawUrl = trim((string) ($payload['url'] ?? ''));
            $sourceTitle = (string) ($payload['title'] ?? '');
            $payload['url'] = self::uniqueUrl($rawUrl, $sourceTitle, (int) $fid, $id);
        }

        try {
            if ($id > 0) {
                DB::table('news')
                    ->where('id', $id)
                    ->where('firma', (int) $fid)
                    ->update($payload);

                return $id;
            }

            // Retry insert to handle race condition on manual ID generation
            $attempts = 3;
            for ($i = 0; $i < $attempts; $i++) {
                try {
                    $maxId = (int) DB::table('news')->max('id');
                    $payload['id'] = $maxId + 1;
                    DB::table('news')->insert($payload);

                    return (int) $payload['id'];
                } catch (\Illuminate\Database\QueryException $e) {
                    if ($i === $attempts - 1 || !str_contains($e->getMessage(), 'Duplicate')) {
                        throw $e;
                    }
                }
            }

            throw new \RuntimeException('Failed to insert news after ' . $attempts . ' attempts.');
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('News::saveNews failed', [
                'id' => $id,
                'fid' => $fid,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public static function deleteNews(int $id, string $fid): void
    {
        DB::table('news')
            ->where('id', $id)
            ->where('firma', (int) $fid)
            ->delete();
    }

    public static function normalizeSlug(string $value): string
    {
        $value = self::transliterate($value);
        $value = mb_strtolower($value, 'UTF-8');
        $value = preg_replace('/[^a-z0-9]+/u', '-', $value) ?? '';
        $value = trim($value, '-');

        return $value;
    }

    private static function uniqueUrl(string $url, string $title, int $fid, int $currentId = 0): string
    {
        $base = self::normalizeSlug($url !== '' ? $url : $title);
        if ($base === '') {
            $base = 'article';
        }

        $candidate = $base;
        $suffix = 2;

        while (DB::table('news')
            ->where('firma', $fid)
            ->where('url', $candidate)
            ->when($currentId > 0, fn ($query) => $query->where('id', '!=', $currentId))
            ->exists()) {
            $candidate = $base . '-' . $suffix;
            $suffix++;
        }

        return $candidate;
    }

    private static function transliterate(string $value): string
    {
        $map = [
            'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e', 'ё' => 'e',
            'ж' => 'zh', 'з' => 'z', 'и' => 'i', 'й' => 'i', 'к' => 'k', 'л' => 'l', 'м' => 'm',
            'н' => 'n', 'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't', 'у' => 'u',
            'ф' => 'f', 'х' => 'h', 'ц' => 'c', 'ч' => 'ch', 'ш' => 'sh', 'щ' => 'sch',
            'ъ' => '', 'ы' => 'y', 'ь' => '', 'э' => 'e', 'ю' => 'yu', 'я' => 'ya',
            'є' => 'ye', 'і' => 'i', 'ї' => 'yi', 'ґ' => 'g',
        ];

        return strtr($value, $map + array_combine(
            array_map('mb_strtoupper', array_keys($map)),
            array_map('ucfirst', array_values($map)),
        ));
    }

    public static function decorateTitle(object $item): string
    {
        return trim((string) ($item->title ?? ''))
            ?: trim((string) ($item->title_ua ?? ''))
            ?: trim((string) ($item->title_en ?? ''));
    }

    private static function decorateItem(object $item, ?string $locale = 'ru'): object
    {
        $item->title_view = Field::localizedValue($locale, $item->title ?? '', $item->title_ua ?? '', $item->title_en ?? '') ?: 'Новина';
        $item->excerpt_view = Field::localizedValue($locale, $item->kratko ?? '', $item->kratko_ua ?? '', $item->kratko_en ?? '');
        $item->body_view = Field::localizedValue($locale, $item->txt ?? '', $item->txt_ua ?? '', $item->txt_en ?? '');
        $item->photo_view = self::resolvePhoto((string) ($item->foto ?? ''));

        return $item;
    }

    public static function resolvePhoto(string $photo): ?string
    {
        $photo = str_replace('\\', '/', trim($photo));
        if ($photo === '') {
            return null;
        }

        if (str_starts_with($photo, 'http://') || str_starts_with($photo, 'https://')) {
            return $photo;
        }

        if (str_starts_with($photo, '../files') || str_starts_with($photo, '..\\files')) {
            return MediaUrl::image($photo);
        }

        // New uploads are stored on the public disk as `files/news/...`.
        // Do not prepend `files` again or the URL becomes
        // `/storage/files/files/news/...`.
        if (str_starts_with(ltrim($photo, '/'), 'files/')) {
            return MediaUrl::storage(ltrim($photo, '/'), 'storage');
        }

        if (str_starts_with(ltrim($photo, '/'), 'storage/')) {
            return MediaUrl::storage('/' . ltrim($photo, '/'), 'storage');
        }

        // Keep compatibility with legacy values containing only a filename or
        // a path relative to the old `storage/files` directory.
        return MediaUrl::storage(ltrim($photo, '/'), 'storage/files');
    }
}
