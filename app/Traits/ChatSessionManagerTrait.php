<?php

namespace App\Traits;

use App\Models\ChatSession;
use App\Models\ChatMessage;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Trait ChatSessionManagerTrait
 *
 * Управление сессиями чата: создание/получение сессии, сохранение сообщений.
 * Используется в TelegramChatService и TelegramAgent для устранения дублирования кода.
 *
 * @property string $telegramTokenPrefix  Префикс токена сессии (напр. 'tg_' или 'tg_agent_')
 * @property int    $defaultAnalystFid    FID аналитика по умолчанию
 */
trait ChatSessionManagerTrait
{
    /**
     * Префикс для session_token (должен быть установлен в конструкторе класса).
     */
    protected string $telegramTokenPrefix = 'tg_';

    /**
     * FID по умолчанию для аналитика.
     */
    protected int $defaultAnalystFid = 12;

    /**
     * Получить или создать сессию чата для Telegram chat_id.
     *
     * @param  int|string  $chatId  Telegram chat_id
     * @param  bool        $forceNew  Принудительно создать новую сессию
     * @param  int|null    $fid       ID проекта (fid) для привязки сессии
     * @return ChatSession
     */
    protected function resolveSession(int|string $chatId, bool $forceNew = false, ?int $fid = null): ChatSession
    {
        if (! $forceNew) {
            $session = ChatSession::where('session_token', $this->telegramTokenPrefix . $chatId)
                ->where('status', 'active')
                ->first();

            if ($session !== null) {
                return $session;
            }
        }

        // Создаём новую сессию
        $attributes = [
            'session_token' => $this->telegramTokenPrefix . $chatId,
            'status' => 'active',
        ];

        if ($fid !== null && $fid > 0) {
            $attributes['fid'] = $fid;
        }

        $session = ChatSession::createSession($attributes);

        Log::info('ChatSessionManager: created new session.', [
            'session_token' => $session->session_token,
            'fid' => $fid,
        ]);

        return $session;
    }

    /**
     * Сохранить сообщение пользователя в историю сессии.
     */
    protected function saveUserMessage(ChatSession $session, string $text): void
    {
        try {
            ChatMessage::create([
                'chat_session_id' => $session->id,
                'fid' => $session->fid,
                'firma' => $session->firma,
                'role' => 'user',
                'content' => $text,
            ]);
        } catch (Throwable $e) {
            Log::warning('ChatSessionManager: failed to save user message.', [
                'session_token' => $session->session_token,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Сохранить ответ ассистента в историю сессии.
     *
     * @param  array<string, mixed>  $result  Результат от AI (содержит answer, model, usage)
     */
    protected function saveAssistantMessage(ChatSession $session, string $answer, array $result): void
    {
        try {
            $metadata = [
                'model' => $result['model'] ?? null,
                'usage' => $result['usage'] ?? null,
                'provider' => $this->getProviderNameForMetadata(),
            ];

            ChatMessage::create([
                'chat_session_id' => $session->id,
                'fid' => $session->fid,
                'firma' => $session->firma,
                'role' => 'assistant',
                'content' => $answer,
                'metadata' => $metadata,
            ]);
        } catch (Throwable $e) {
            Log::warning('ChatSessionManager: failed to save assistant message.', [
                'session_token' => $session->session_token,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Получить имя провайдера для метаданных сообщения.
     * Должен быть реализован в классе, использующем трейт.
     */
    abstract protected function getProviderNameForMetadata(): string;
}
