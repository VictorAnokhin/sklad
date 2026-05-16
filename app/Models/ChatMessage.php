<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatMessage extends Model
{
    protected $table = 'chat_messages';

    protected $fillable = [
        'chat_session_id',
        'fid',
        'firma',
        'role',
        'content',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'chat_session_id' => 'integer',
        'fid' => 'integer',
        'firma' => 'integer',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(ChatSession::class, 'chat_session_id');
    }
}
