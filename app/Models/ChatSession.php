<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ChatSession extends Model
{
    protected $table = 'chat_sessions';

    protected $fillable = [
        'user_id',
        'session_token',
        'fid',
        'firma',
        'wallet',
        'language',
        'page',
        'title',
        'status',
        'reply_mode',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'fid' => 'integer',
        'firma' => 'integer',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $session): void {
            if ($session->session_token === null || $session->session_token === '') {
                $session->session_token = Str::uuid()->toString();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class);
    }

    /**
     * Получить историю сообщений для отправки в AI.
     *
     * @return array<int, array{role: string, content: string}>
     */
    public function getHistoryForAi(int $limit = 20): array
    {
        return $this->messages()
            ->whereIn('role', ['user', 'assistant'])
            ->orderBy('created_at')
            ->limit($limit)
            ->get()
            ->map(fn (ChatMessage $msg) => [
                'role' => $msg->role,
                'content' => $msg->content,
            ])
            ->toArray();
    }

    /**
     * Обновить заголовок сессии первым сообщением пользователя.
     */
    public function updateTitle(string $message): void
    {
        if ($this->title === null || $this->title === '') {
            $this->update([
                'title' => mb_substr(trim($message), 0, 250),
            ]);
        }
    }

    /**
     * Создать или получить сессию по токену.
     */
    public static function resolveByToken(string $token): ?self
    {
        return static::where('session_token', $token)
            ->where('status', 'active')
            ->first();
    }

    /**
     * Создать новую сессию.
     *
     * @param  array<string, mixed>  $attributes
     */
    public static function createSession(array $attributes = []): self
    {
        return static::create($attributes);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeForUser($query, ?int $userId)
    {
        if ($userId !== null) {
            return $query->where('user_id', $userId);
        }

        return $query;
    }
}
