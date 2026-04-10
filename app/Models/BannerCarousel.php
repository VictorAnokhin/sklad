<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Support\MediaUrl;

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

        return MediaUrl::storage($value, 'storage');
    }
}
