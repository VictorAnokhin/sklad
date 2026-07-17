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

    public static function findForView(int $id, string $fid, ?string $locale = 'ru'): ?object
    {
        $item = DB::table('news')
            ->where('id', $id)
            ->where(function ($query) use ($fid) {
                $query->where('firma', (int) $fid)
                    ->orWhere('firma', 0);
            })
            ->first();

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

        if ($id > 0) {
            DB::table('news')
                ->where('id', $id)
                ->where('firma', (int) $fid)
                ->update($payload);

            return $id;
        }

        $maxId = (int) DB::table('news')->max('id');
        $payload['id'] = $maxId + 1;

        DB::table('news')->insert($payload);

        return (int) $payload['id'];
    }

    public static function deleteNews(int $id, string $fid): void
    {
        DB::table('news')
            ->where('id', $id)
            ->where('firma', (int) $fid)
            ->delete();
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
