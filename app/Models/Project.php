<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    protected $table = 'project';
    public $timestamps = false;
    protected $guarded = [];


    public function legacyUser()
    {
        return $this->belongsTo(LegacyUser::class, 'userid');
    }

    public static function decorateMedia(object $project): object
    {
        $project->foto_preview = self::resolveMediaUrl((string) ($project->foto ?? ''));
        $project->foto_header_preview = self::resolveMediaUrl((string) ($project->foto_header ?? ''));
        $project->foto_footer_preview = self::resolveMediaUrl((string) ($project->foto_footer ?? ''));

        return $project;
    }

    public static function resolveMediaUrl(string $value): ?string
    {
        $value = trim($value);
        if (!self::isImagePath($value)) {
            return null;
        }

        if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://') || str_starts_with($value, '/')) {
            return $value;
        }

        return asset('storage/' . ltrim($value, '/'));
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
