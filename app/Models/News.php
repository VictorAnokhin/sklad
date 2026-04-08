<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class News extends Model
{
    protected $table = 'news';
    public $timestamps = false;
    protected $guarded = [];

    public static function init(string $fid, int $pos = 0, int $perPage = 20): array
    {
        $baseQuery = DB::table('news')
            ->where(function ($query) use ($fid) {
                $query->where('firma', (int) $fid)
                    ->orWhere('firma', 0);
            });

        $total = (clone $baseQuery)->count();

        $items = (clone $baseQuery)
            ->orderByDesc('hot')
            ->orderByDesc('id')
            ->offset($pos)
            ->limit($perPage)
            ->get()
            ->map(function ($item) {
                $item = self::decorateItem($item);
                return $item;
            });

        return [
            'items' => $items,
            'total' => $total,
        ];
    }

    public static function findForView(int $id, string $fid): ?object
    {
        $item = DB::table('news')
            ->where('id', $id)
            ->where(function ($query) use ($fid) {
                $query->where('firma', (int) $fid)
                    ->orWhere('firma', 0);
            })
            ->first();

        return $item ? self::decorateItem($item) : null;
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

    private static function decorateItem(object $item): object
    {
        $item->title_view = trim((string) ($item->title_ua ?: $item->title ?: $item->title_en ?: 'Новина'));
        $item->excerpt_view = trim((string) ($item->kratko_ua ?: $item->kratko ?: $item->kratko_en ?: ''));
        $item->body_view = (string) ($item->txt_ua ?: $item->txt ?: $item->txt_en ?: '');
        $item->photo_view = self::resolvePhoto((string) ($item->foto ?? ''));

        return $item;
    }

    private static function resolvePhoto(string $photo): ?string
    {
        $photo = trim($photo);
        if ($photo === '') {
            return null;
        }

        if (str_starts_with($photo, 'http://') || str_starts_with($photo, 'https://') || str_starts_with($photo, '/')) {
            return $photo;
        }

        return asset('storage/files/' . ltrim($photo, '/'));
    }
}
