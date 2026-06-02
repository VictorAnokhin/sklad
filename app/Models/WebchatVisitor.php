<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WebchatVisitor extends Model
{
    protected $table = 'webchat_visitors';

    protected $fillable = [
        'fid',
        'visitor_uid',
        'site_domain',
        'last_session_token',
        'identified_user_id',
        'language',
        'timezone',
        'last_seen_url',
        'last_seen_path',
        'last_referrer',
        'ip_hash',
        'user_agent_hash',
        'interests',
        'traits',
        'counters',
        'journey',
        'needs_summary',
        'total_time_ms',
        'last_ip_hash',
        'consent_analytics',
        'identification_confidence',
        'first_seen_at',
        'last_seen_at',
    ];

    protected $casts = [
        'fid' => 'integer',
        'identified_user_id' => 'integer',
        'interests' => 'array',
        'traits' => 'array',
        'counters' => 'array',
        'journey' => 'array',
        'needs_summary' => 'array',
        'total_time_ms' => 'integer',
        'consent_analytics' => 'boolean',
        'identification_confidence' => 'decimal:2',
        'first_seen_at' => 'datetime',
        'last_seen_at' => 'datetime',
    ];

    public function events(): HasMany
    {
        return $this->hasMany(WebchatEvent::class);
    }

    public function scopeForFid($query, int $fid)
    {
        return $query->where('fid', $fid);
    }
}
