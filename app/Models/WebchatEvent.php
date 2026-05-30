<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebchatEvent extends Model
{
    protected $table = 'webchat_events';

    protected $fillable = [
        'fid',
        'webchat_visitor_id',
        'visitor_uid',
        'session_token',
        'event_type',
        'funnel_step',
        'ui_variant_key',
        'site_domain',
        'page_url',
        'page_path',
        'page_title',
        'referrer',
        'language',
        'metadata',
        'occurred_at',
    ];

    protected $casts = [
        'fid' => 'integer',
        'webchat_visitor_id' => 'integer',
        'metadata' => 'array',
        'occurred_at' => 'datetime',
    ];

    public function visitor(): BelongsTo
    {
        return $this->belongsTo(WebchatVisitor::class, 'webchat_visitor_id');
    }

    public function scopeForFid($query, int $fid)
    {
        return $query->where('fid', $fid);
    }
}
