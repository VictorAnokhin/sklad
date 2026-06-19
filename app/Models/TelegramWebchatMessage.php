<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TelegramWebchatMessage extends Model
{
    protected $table = 'telegram_webchat_messages';

    protected $fillable = [
        'chat_session_id',
        'chat_message_id',
        'fid',
        'firma',
        'site_key',
        'site_domain',
        'telegram_chat_id',
        'telegram_thread_id',
        'telegram_message_id',
        'telegram_reply_to_message_id',
        'direction',
        'payload',
    ];

    protected $casts = [
        'chat_session_id' => 'integer',
        'chat_message_id' => 'integer',
        'fid' => 'integer',
        'firma' => 'integer',
        'telegram_message_id' => 'integer',
        'telegram_reply_to_message_id' => 'integer',
        'payload' => 'array',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(ChatSession::class, 'chat_session_id');
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(ChatMessage::class, 'chat_message_id');
    }
}
