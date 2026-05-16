<?php

namespace App\Http\Controllers;

use App\Models\ChatSession;
use App\Services\ChatService;
use App\Services\DeepSeekClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class AiChatController extends Controller
{
    public function __construct(
        private readonly ChatService $chatService,
    ) {}

    /**
     * Отправить сообщение в чат и получить ответ от AI.
     *
     * Поддерживает как новый формат (с session_token), так и старый (без него).
     */
    public function chat(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'message' => ['required', 'string', 'min:2', 'max:2000'],
            'session_token' => ['nullable', 'string', 'size:36', 'uuid'],
            'language' => ['nullable', 'string', 'in:ru,ua,en'],
            'page' => ['nullable', 'string', 'max:80'],
            'wallet' => ['nullable', 'string', 'max:100'],
            'fid' => ['nullable', 'integer', 'min:1', 'max:999999'],
            'firma' => ['nullable', 'integer', 'min:1', 'max:999999'],
            'user_id' => ['nullable', 'integer', 'min:1'],
            'history' => ['nullable', 'array', 'max:8'],
            'history.*.role' => ['required_with:history', 'string', 'in:user,assistant'],
            'history.*.content' => ['required_with:history', 'string', 'max:1600'],
        ]);

        try {
            $result = $this->chatService->sendMessage($payload);
        } catch (Throwable $e) {
            $message = $e->getMessage();
            Log::warning('Chat send failed.', [
                'message' => $message,
                'page' => $payload['page'] ?? null,
                'wallet' => $payload['wallet'] ?? null,
                'fid' => $payload['fid'] ?? null,
            ]);

            if (str_contains($message, 'DEEPSEEK_API_KEY')) {
                return response()->json([
                    'message' => 'AI assistant is not configured. Add DEEPSEEK_API_KEY on the Laravel backend.',
                    'error' => config('app.debug') ? $message : null,
                ], 503);
            }

            return response()->json([
                'message' => 'AI assistant is temporarily unavailable.',
                'error' => config('app.debug') ? $message : null,
            ], 503);
        }

        return response()->json($result);
    }

    /**
     * Получить список сессий чата пользователя.
     */
    public function sessions(Request $request): JsonResponse
    {
        $user = $request->user();
        $userId = $user !== null ? $user->id : null;
        $sessionToken = trim((string) ($request->query('session_token', '')));
        $fidParam = $request->query('fid');
        $fid = $fidParam !== null ? (int) $fidParam : ((int) session('fid', 0)) ?: null;

        $sessions = $this->chatService->getUserSessions($userId, $sessionToken, $fid);

        return response()->json([
            'sessions' => $sessions->map(fn (ChatSession $s) => [
                'id' => $s->id,
                'session_token' => $s->session_token,
                'title' => $s->title,
                'language' => $s->language,
                'page' => $s->page,
                'fid' => $s->fid,
                'firma' => $s->firma,
                'wallet' => $s->wallet,
                'status' => $s->status,
                'messages_count' => $s->messages()->count(),
                'created_at' => $s->created_at?->toIso8601String(),
                'updated_at' => $s->updated_at?->toIso8601String(),
            ])->values(),
        ]);
    }

    /**
     * Получить историю сообщений сессии.
     */
    public function history(Request $request, string $sessionToken): JsonResponse
    {
        $session = $this->chatService->findSession($sessionToken);

        if ($session === null) {
            return response()->json(['message' => 'Session not found.'], 404);
        }

        $messages = $this->chatService->getSessionHistory($sessionToken);

        return response()->json([
            'session' => [
                'session_token' => $session->session_token,
                'title' => $session->title,
                'language' => $session->language,
                'fid' => $session->fid,
                'firma' => $session->firma,
                'status' => $session->status,
            ],
            'messages' => $messages->map(fn ($msg) => [
                'id' => $msg->id,
                'role' => $msg->role,
                'content' => $msg->content,
                'fid' => $msg->fid,
                'firma' => $msg->firma,
                'metadata' => $msg->metadata,
                'created_at' => $msg->created_at?->toIso8601String(),
            ]),
        ]);
    }

    /**
     * Архивировать сессию чата.
     */
    public function archive(Request $request, string $sessionToken): JsonResponse
    {
        $archived = $this->chatService->archiveSession($sessionToken);

        if (! $archived) {
            return response()->json(['message' => 'Session not found.'], 404);
        }

        return response()->json(['message' => 'Session archived.']);
    }

    /**
     * Удалить сессию чата (безвозвратно).
     */
    public function destroy(Request $request, string $sessionToken): JsonResponse
    {
        $deleted = $this->chatService->deleteSession($sessionToken);

        if (! $deleted) {
            return response()->json(['message' => 'Session not found.'], 404);
        }

        return response()->json(['message' => 'Session deleted.']);
    }
}
