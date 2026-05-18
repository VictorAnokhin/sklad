<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiKnowledgeBase extends Model
{
    protected $table = 'ai_knowledge_base';

    protected $fillable = [
        'fid',
        'title',
        'content',
        'category',
        'source',
        'tool_keys',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
        'fid' => 'integer',
        'tool_keys' => 'array',
    ];

    /**
     * Фильтр по проекту (fid). Если $fid === null — не фильтровать.
     */
    public function scopeForFid($query, ?int $fid)
    {
        if ($fid === null) {
            return $query;
        }
        return $query->where('fid', $fid);
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }
}
