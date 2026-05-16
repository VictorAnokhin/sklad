<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AnalystResearch extends Model
{
    protected $table = 'analyst_researches';

    protected $fillable = [
        'fid',
        'topic',
        'summary',
        'status',
        'metadata',
    ];

    protected $casts = [
        'fid' => 'integer',
        'metadata' => 'array',
    ];

    public function sources(): HasMany
    {
        return $this->hasMany(AnalystSource::class, 'research_id');
    }

    /**
     * Завершить исследование с summary.
     */
    public function complete(string $summary): void
    {
        $this->update([
            'status' => 'completed',
            'summary' => $summary,
        ]);
    }

    /**
     * Отметить исследование как неудачное.
     */
    public function fail(string $reason): void
    {
        $this->update([
            'status' => 'failed',
            'summary' => $reason,
        ]);
    }

    /**
     * Все активные исследования по fid.
     */
    public function scopeForFid($query, int $fid)
    {
        return $query->where('fid', $fid);
    }

    /**
     * Активные (в работе).
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'in_progress');
    }
}
