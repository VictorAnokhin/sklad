<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WidgetUserProfile extends Model
{
    protected $table = 'widget_user_profiles';

    protected $fillable = [
        'fid',
        'fingerprint_hash',
        'visitor_uid',
        'user_id',
        'google_id',
        'email',
        'last_session_token',
        'site_domain',
        'traits',
        'first_seen_at',
        'last_seen_at',
    ];

    protected $casts = [
        'fid' => 'integer',
        'user_id' => 'integer',
        'traits' => 'array',
        'first_seen_at' => 'datetime',
        'last_seen_at' => 'datetime',
    ];

    public function unmetNeeds(): HasMany
    {
        return $this->hasMany(UnmetNeed::class, 'widget_user_profile_id');
    }

    public function scopeForFid($query, int $fid)
    {
        return $query->where('fid', $fid);
    }
}
