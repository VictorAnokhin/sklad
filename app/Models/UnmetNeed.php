<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UnmetNeed extends Model
{
    protected $table = 'unmet_needs';

    protected $fillable = [
        'fid',
        'widget_user_profile_id',
        'fingerprint_hash',
        'visitor_uid',
        'user_id',
        'google_id',
        'email',
        'status',
        'search_query',
        'normalized_query',
        'context',
        'product_snapshot',
        'detected_at',
        'ready_at',
        'resolved_at',
    ];

    protected $casts = [
        'fid' => 'integer',
        'widget_user_profile_id' => 'integer',
        'user_id' => 'integer',
        'context' => 'array',
        'product_snapshot' => 'array',
        'detected_at' => 'datetime',
        'ready_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function profile(): BelongsTo
    {
        return $this->belongsTo(WidgetUserProfile::class, 'widget_user_profile_id');
    }

    public function scopeForFid($query, int $fid)
    {
        return $query->where('fid', $fid);
    }
}
