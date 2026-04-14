<?php

namespace App\Support;

class MediaUrl
{
    public static function image(?string $path): ?string
    {
        $path = trim((string) $path);
        if ($path === '') {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        // If path starts with ../files => use MEDIA_IMAGE_URL
        if (str_starts_with($path, '../files') || str_starts_with($path, '..\\files')) {
            $base = (string) config('media.image_base_url', '');
            $cleanPath = ltrim(substr($path, 2), '/\\');
            return self::join($base, $cleanPath);
        }

        // Default => use MEDIA_ASSET_URL
        return self::join((string) config('media.asset_base_url', config('app.url', 'http://localhost')), $path);
    }

    public static function storage(?string $path, string $prefix = 'storage'): ?string
    {
        $path = trim((string) $path);
        if ($path === '') {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        $base = (string) config('media.asset_base_url', config('app.url', 'http://localhost'));

        if (str_starts_with($path, '/')) {
            return self::join($base, $path);
        }

        $prefix = trim($prefix, '/');

        return self::join($base, $prefix . '/' . ltrim($path, '/'));
    }

    private static function join(string $base, string $path): string
    {
        return rtrim($base, '/') . '/' . ltrim($path, '/');
    }
}
