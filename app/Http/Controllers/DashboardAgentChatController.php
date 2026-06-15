<?php

namespace App\Http\Controllers;

use App\Models\ChatMessage;
use App\Models\ChatSession;
use App\Services\ManagerAiBridgeClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class DashboardAgentChatController extends Controller
{
    private const PAGE = 'dashboard_agents';

    public function __construct(
        private readonly ManagerAiBridgeClient $managerAiBridge,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $session = $this->resolveDashboardSession($request, true);

        return response()->json([
            'session' => $this->serializeSession($session),
            'messages' => $this->serializeMessages($session),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'message' => ['required', 'string', 'min:2', 'max:4000'],
            'session_token' => ['nullable', 'string', 'size:36', 'uuid'],
        ]);

        $session = $this->resolveDashboardSession($request, true, $payload['session_token'] ?? null);
        $fid = (int) ($session->fid ?: session('fid'));
        $firma = (int) ($session->firma ?: session('fid')) ?: null;
        $message = trim($payload['message']);

        $this->saveMessage($session, 'user', $message, [
            'source' => 'dashboard',
            'agent_flow' => 'dashboard_to_webchatagent_to_financialanalyst',
        ]);
        $session->updateTitle($message);

        $assistantMessage = null;

        if (! $this->managerAiBridge->enabled()) {
            $assistantMessage = $this->saveMessage($session, 'assistant', 'ManagerAI bridge не настроен. Сообщение сохранено в контексте dashboard-чата, но агентам не отправлено.', [
                'provider' => 'manager-ai',
                'source_agent' => 'WebChatAgent',
                'target_agent' => 'FinancialAnalyst',
                'manager_ai_disabled' => true,
            ]);

            return response()->json([
                'session' => $this->serializeSession($session->refresh()),
                'message' => $this->serializeMessage($assistantMessage),
                'messages' => $this->serializeMessages($session),
            ], 201);
        }

        try {
            $managerResult = $this->managerAiBridge->sendChatMessage([
                'message' => $this->dashboardAgentPrompt($request, $session, $message),
                'session_token' => $session->session_token,
                'language' => app()->getLocale() ?: 'ru',
                'page' => self::PAGE,
                'fid' => $fid,
                'firma' => $firma,
                'user_id' => $request->user()?->id ?? session('id'),
                'site_domain' => $request->getHost(),
                'manager_ai_mode' => 'execute',
                'target_agent' => 'WebChatAgent',
            ]);

            $assistantMessage = $this->saveMessage($session, 'assistant', (string) ($managerResult['answer'] ?? 'Запрос передан WebChatAgent.'), [
                'provider' => 'manager-ai',
                'source_agent' => 'WebChatAgent',
                'target_agent' => 'FinancialAnalyst',
                'agent_flow' => 'dashboard_to_webchatagent_to_financialanalyst',
                'manager_ai' => $managerResult['manager_ai'] ?? null,
                'pending_agent_response' => true,
            ]);
        } catch (Throwable $e) {
            Log::warning('Dashboard agent chat delegation failed.', [
                'fid' => $fid,
                'session_token' => $session->session_token,
                'error' => $e->getMessage(),
            ]);

            $assistantMessage = $this->saveMessage($session, 'assistant', 'Не удалось передать запрос в manager-ai. Проверьте подключение manager-ai и повторите запрос.', [
                'provider' => 'manager-ai',
                'source_agent' => 'WebChatAgent',
                'target_agent' => 'FinancialAnalyst',
                'delegation_failed' => true,
                'error' => $e->getMessage(),
            ]);
        }

        return response()->json([
            'session' => $this->serializeSession($session->refresh()),
            'message' => $this->serializeMessage($assistantMessage),
            'messages' => $this->serializeMessages($session),
        ], 201);
    }

    public function agentContext(Request $request): JsonResponse
    {
        if (! $this->canUseAgentApi($request)) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $payload = $request->validate([
            'fid' => ['required', 'integer', 'min:1', 'max:999999'],
            'session_token' => ['nullable', 'string', 'size:36', 'uuid'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $session = $this->resolveAgentSession((int) $payload['fid'], $payload['session_token'] ?? null, false);

        if ($session === null) {
            return response()->json(['message' => 'Dashboard chat session not found.'], 404);
        }

        return response()->json([
            'session' => $this->serializeSession($session),
            'messages' => $this->serializeMessages($session, (int) ($payload['limit'] ?? 40)),
        ]);
    }

    public function agentStore(Request $request): JsonResponse
    {
        if (! $this->canUseAgentApi($request)) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $payload = $request->validate([
            'fid' => ['required', 'integer', 'min:1', 'max:999999'],
            'firma' => ['nullable', 'integer', 'min:1', 'max:999999'],
            'session_token' => ['nullable', 'string', 'size:36', 'uuid'],
            'message' => ['required', 'string', 'min:1', 'max:12000'],
            'source_agent' => ['nullable', 'string', 'max:80'],
            'target_agent' => ['nullable', 'string', 'max:80'],
            'metadata' => ['nullable', 'array'],
        ]);

        $session = $this->resolveAgentSession((int) $payload['fid'], $payload['session_token'] ?? null, true, (int) ($payload['firma'] ?? 0) ?: null);
        $message = $this->saveMessage($session, 'assistant', trim($payload['message']), [
            'provider' => 'manager-ai',
            'source' => 'agent_api',
            'source_agent' => $payload['source_agent'] ?? 'WebChatAgent',
            'target_agent' => $payload['target_agent'] ?? 'dashboard',
            'agent_flow' => 'financialanalyst_to_webchatagent_to_dashboard',
            'agent_metadata' => $payload['metadata'] ?? [],
        ]);

        return response()->json([
            'ok' => true,
            'session' => $this->serializeSession($session->refresh()),
            'message' => $this->serializeMessage($message),
        ], 201);
    }

    private function resolveDashboardSession(Request $request, bool $create, ?string $sessionToken = null): ?ChatSession
    {
        $fid = (int) session('fid');
        $firma = $fid > 0 ? $fid : null;
        $userId = $request->user()?->id ?? ((int) session('id') ?: null);
        $sessionToken = trim((string) $sessionToken);

        $query = ChatSession::query()
            ->active()
            ->where('page', self::PAGE);

        if ($sessionToken !== '') {
            $found = (clone $query)->where('session_token', $sessionToken)->first();
            if ($found !== null) {
                return $found;
            }
        }

        $found = (clone $query)
            ->when($fid > 0, fn ($q) => $q->where('fid', $fid))
            ->when($userId !== null, fn ($q) => $q->where('user_id', $userId))
            ->latest('updated_at')
            ->first();

        if ($found !== null || ! $create) {
            return $found;
        }

        return ChatSession::createSession([
            'user_id' => $userId,
            'session_token' => $sessionToken !== '' ? $sessionToken : (string) Str::uuid(),
            'fid' => $fid > 0 ? $fid : null,
            'firma' => $firma,
            'language' => app()->getLocale() ?: 'ru',
            'page' => self::PAGE,
            'title' => 'Dashboard agents',
            'status' => 'active',
        ]);
    }

    private function resolveAgentSession(int $fid, ?string $sessionToken, bool $create, ?int $firma = null): ?ChatSession
    {
        $sessionToken = trim((string) $sessionToken);
        $query = ChatSession::query()
            ->active()
            ->where('page', self::PAGE)
            ->where('fid', $fid);

        if ($sessionToken !== '') {
            $session = (clone $query)->where('session_token', $sessionToken)->first();
            if ($session !== null || ! $create) {
                return $session;
            }
        } else {
            $session = (clone $query)->latest('updated_at')->first();
            if ($session !== null || ! $create) {
                return $session;
            }
        }

        return ChatSession::createSession([
            'session_token' => $sessionToken !== '' ? $sessionToken : (string) Str::uuid(),
            'fid' => $fid,
            'firma' => $firma ?: $fid,
            'language' => 'ru',
            'page' => self::PAGE,
            'title' => 'Dashboard agents',
            'status' => 'active',
        ]);
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    private function saveMessage(ChatSession $session, string $role, string $content, array $metadata = []): ChatMessage
    {
        return ChatMessage::create([
            'chat_session_id' => $session->id,
            'fid' => $session->fid,
            'firma' => $session->firma,
            'role' => $role,
            'content' => $content,
            'metadata' => $metadata,
        ]);
    }

    private function dashboardAgentPrompt(Request $request, ChatSession $session, string $message): string
    {
        $callbackBase = rtrim((string) config('services.manager_ai.laravel_api_url', ''), '/');
        if ($callbackBase === '') {
            $callbackBase = rtrim((string) config('app.url', ''), '/');
        }

        $history = $session->messages()
            ->whereIn('role', ['user', 'assistant'])
            ->orderByDesc('created_at')
            ->limit(12)
            ->get()
            ->reverse()
            ->map(fn (ChatMessage $item) => [
                'role' => $item->role,
                'content' => Str::limit($item->content, 1200, ''),
            ])
            ->values()
            ->all();

        $context = [
            'fid' => $session->fid,
            'firma' => $session->firma,
            'session_token' => $session->session_token,
            'page' => self::PAGE,
            'dashboard_url' => $request->fullUrl(),
            'context_api' => $callbackBase.'/api/external/dashboard-agent-chat/context',
            'publish_api' => $callbackBase.'/api/external/dashboard-agent-chat/messages',
            'auth_header' => 'X-ManagerAI-Bridge-Secret',
            'history' => $history,
        ];

        return "Запрос из dashboard laravel-api для WebChatAgent.\n\n".
            "Маршрут агентов:\n".
            "1. WebChatAgent принимает сообщение пользователя dashboard.\n".
            "2. WebChatAgent переадресовывает задачу FinancialAnalyst для анализа данных проекта.\n".
            "3. FinancialAnalyst возвращает вывод WebChatAgent.\n".
            "4. WebChatAgent публикует итоговый ответ в dashboard chat через publish_api.\n\n".
            "Важно: каждый проект ведется отдельно. Работай только с fid={$session->fid} и session_token={$session->session_token}.\n".
            "Для чтения контекста используй GET/POST context_api с fid и session_token. Для публикации ответа отправь POST publish_api JSON: ".
            "{\"fid\":{$session->fid},\"session_token\":\"{$session->session_token}\",\"message\":\"ответ\",\"source_agent\":\"WebChatAgent\",\"target_agent\":\"dashboard\"}.\n\n".
            "Контекст:\n".
            json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT).
            "\n\nСообщение пользователя:\n".$message;
    }

    private function canUseAgentApi(Request $request): bool
    {
        $secret = trim((string) config('services.manager_ai.bridge_secret', ''));
        if ($secret === '') {
            return false;
        }

        return hash_equals($secret, trim((string) $request->header('X-ManagerAI-Bridge-Secret', '')));
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeSession(ChatSession $session): array
    {
        return [
            'id' => $session->id,
            'session_token' => $session->session_token,
            'fid' => $session->fid,
            'firma' => $session->firma,
            'page' => $session->page,
            'title' => $session->title,
            'status' => $session->status,
            'updated_at' => $session->updated_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function serializeMessages(ChatSession $session, int $limit = 60): array
    {
        return $session->messages()
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->reverse()
            ->map(fn (ChatMessage $message) => $this->serializeMessage($message))
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeMessage(ChatMessage $message): array
    {
        return [
            'id' => $message->id,
            'role' => $message->role,
            'content' => $message->content,
            'metadata' => $message->metadata,
            'created_at' => $message->created_at?->toIso8601String(),
        ];
    }
}
