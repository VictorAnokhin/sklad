<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class AiKnowledgeCategory extends Model
{
    protected $table = 'ai_knowledge_categories';

    protected $fillable = [
        'fid',
        'key',
        'name',
        'sort_order',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
        'sort_order' => 'integer',
        'fid' => 'integer',
    ];

    /**
     * Получить активные категории для проекта (fid) или глобальные (fid = null).
     *
     * @param  int|null  $fid  ID проекта. Если null — возвращаются только глобальные категории.
     * @return Collection<int, AiKnowledgeCategory>
     */
    public static function getActive(?int $fid = null): Collection
    {
        return self::forFid($fid)
            ->where('active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    /**
     * Получить все категории для проекта (fid) или глобальные (fid = null).
     *
     * @param  int|null  $fid  ID проекта. Если null — возвращаются только глобальные категории.
     * @return Collection<int, AiKnowledgeCategory>
     */
    public static function getAllForFid(?int $fid = null): Collection
    {
        return self::forFid($fid)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    /**
     * Скоуп для фильтрации по fid.
     * Если fid = null — только глобальные категории (fid IS NULL).
     * Если fid > 0 — категории проекта + глобальные.
     */
    public function scopeForFid($query, ?int $fid)
    {
        if ($fid === null) {
            return $query->whereNull('fid');
        }

        return $query->where(function ($q) use ($fid) {
            $q->where('fid', $fid)
              ->orWhereNull('fid');
        });
    }
}
