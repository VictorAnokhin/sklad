<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BannerCarousel extends Model
{
    protected $table = 'banner_carousels';
    protected $guarded = [];

    public static function decorate(object $banner): object
    {
        $banner->image_url = self::resolveMediaUrl((string) ($banner->image_path ?? ''));

        return $banner;
    }

    public static function resolveMediaUrl(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://') || str_starts_with($value, '/')) {
            return $value;
        }

        return asset('storage/' . ltrim($value, '/'));
    }
}
