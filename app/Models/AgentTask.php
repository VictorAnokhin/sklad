<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AgentTask extends Model
{
    const UPDATED_AT = null;

    protected $fillable = [
        'uuid',
        'source_agent',
        'target_agent',
        'fid',
        'session_token',
        'task_type',
        'input_data',
        'output_data',
        'status',
        'priority',
        'error_message',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'input_data' => 'array',
        'output_data' => 'array',
        'fid' => 'integer',
        'priority' => 'integer',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (AgentTask $task) {
            if (!$task->uuid) {
                $task->uuid = (string) Str::uuid();
            }
        });
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeForAgent($query, string $agentName)
    {
        return $query->where('target_agent', $agentName);
    }

    public function scopeForFid($query, int $fid)
    {
        return $query->where('fid', $fid);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function communications()
    {
        return $this->hasMany(AgentCommunication::class, 'task_id');
    }
}
