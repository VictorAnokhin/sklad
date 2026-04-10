<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Support\MediaUrl;

class Firma extends Model
{
    protected $table = 'firma';
    public $timestamps = false;
    protected $guarded = [];


    public function firmaObj()
    {
        return $this->belongsTo(Firma::class, 'firma');
    }

    public function legacyUser()
    {
        return $this->belongsTo(LegacyUser::class, 'userid');
    }

    public static function decorateMedia(object $company): object
    {
        $company->pidpys_preview = self::resolveMediaUrl((string) ($company->pidpys ?? ''));
        $company->pechat_preview = self::resolveMediaUrl((string) ($company->pechat ?? ''));

        return $company;
    }

    public static function resolveMediaUrl(string $value): ?string
    {
        $value = trim($value);
        if (!self::isImagePath($value)) {
            return null;
        }

        return MediaUrl::storage($value, 'storage');
    }

    public static function isImagePath(string $value): bool
    {
        $value = trim($value);
        if ($value === '') {
            return false;
        }

        $path = parse_url($value, PHP_URL_PATH) ?: $value;
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'], true);
    }
}
