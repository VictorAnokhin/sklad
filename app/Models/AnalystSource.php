<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnalystSource extends Model
{
    protected $table = 'analyst_sources';

    protected $fillable = [
        'research_id',
        'fid',
        'url',
        'title',
        'content',
        'content_type',
        'summary',
        'metadata',
    ];

    protected $casts = [
        'fid' => 'integer',
        'research_id' => 'integer',
        'metadata' => 'array',
    ];

    public function research(): BelongsTo
    {
        return $this->belongsTo(AnalystResearch::class, 'research_id');
    }

    /**
     * Источники по fid.
     */
    public function scopeForFid($query, int $fid)
    {
        return $query->where('fid', $fid);
    }

    /**
     * Источники определённого типа.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('content_type', $type);
    }

    /**
     * Поиск по содержимому источника.
     */
    public function scopeSearch($query, string $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('title', 'like', "%{$term}%")
              ->orWhere('content', 'like', "%{$term}%")
              ->orWhere('summary', 'like', "%{$term}%");
        });
    }
}
