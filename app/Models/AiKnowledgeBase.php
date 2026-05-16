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
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
        'fid' => 'integer',
    ];

    public function scopeForFid($query, int $fid)
    {
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
