<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebchatUiConfig extends Model
{
    protected $table = 'webchat_ui_configs';

    protected $fillable = [
        'fid',
        'variant_key',
        'site_domain',
        'status',
        'config',
        'recommendation',
        'source',
        'published_at',
    ];

    protected $casts = [
        'fid' => 'integer',
        'config' => 'array',
        'recommendation' => 'array',
        'published_at' => 'datetime',
    ];

    public function scopeForFid($query, int $fid)
    {
        return $query->where('fid', $fid);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
