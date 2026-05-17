<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AgentCommunication extends Model
{
    const UPDATED_AT = null;

    protected $fillable = [
        'source_agent',
        'target_agent',
        'fid',
        'task_id',
        'message_type',
        'content',
        'metadata',
        'status',
    ];

    protected $casts = [
        'metadata' => 'array',
        'fid' => 'integer',
        'task_id' => 'integer',
    ];

    public function task()
    {
        return $this->belongsTo(AgentTask::class, 'task_id');
    }

    public function scopeForAgent($query, string $agentName)
    {
        return $query->where('target_agent', $agentName);
    }

    public function scopeForFid($query, int $fid)
    {
        return $query->where('fid', $fid);
    }
}
