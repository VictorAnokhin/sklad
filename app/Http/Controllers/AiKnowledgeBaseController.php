<?php

namespace App\Http\Controllers;

use App\Events\AgentCommunicationSent;
use App\Models\AgentCommunication;
use App\Models\AiKnowledgeBase;
use App\Services\AiKnowledgeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class AiKnowledgeBaseController extends Controller
{
    public function __construct(
        private readonly AiKnowledgeService $knowledgeService,
    ) {}

    /**
     * GET /api/ai/knowledge-base?fid=12
     *
     * Получить все записи базы знаний для проекта.
     */
    public function index(Request $request): JsonResponse
    {
        $fid = (int) ($request->input('fid', 0));

        if ($fid <= 0) {
            return response()->json(['message' => 'Parameter "fid" is required.'], 422);
        }

        $query = AiKnowledgeBase::forFid($fid)
            ->orderBy('created_at', 'desc');

        if ($request->has('category')) {
            $query->byCategory((string) $request->input('category'));
        }

        if ($request->has('active')) {
            $query->where('active', (bool) $request->input('active'));
        }

        $perPage = min((int) ($request->input('per_page', 50)), 200);
        $records = $query->paginate($perPage);

        return response()->json([
            'data' => $records->items(),
            'meta' => [
                'current_page' => $records->currentPage(),
                'last_page' => $records->lastPage(),
                'per_page' => $records->perPage(),
                'total' => $records->total(),
            ],
        ]);
    }

    /**
     * POST /api/ai/knowledge-base
     *
     * Создать запись в базе знаний.
     */
    public function store(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'fid' => ['required', 'integer', 'min:1'],
            'title' => ['nullable', 'string', 'max:255'],
            'content' => ['required', 'string', 'min:10', 'max:10000'],
            'category' => ['nullable', 'string', 'max:80'],
            'source' => ['nullable', 'string', 'max:80'],
            'tool_keys' => ['nullable', 'array'],
            'tool_keys.*' => ['string', 'max:80'],
            'active' => ['nullable', 'boolean'],
        ]);

        try {
            $record = $this->knowledgeService->create((int) $payload['fid'], $payload);
        } catch (Throwable $e) {
            Log::error('Failed to create knowledge base record.', [
                'fid' => $payload['fid'],
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Failed to create knowledge base record.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }

        return response()->json([
            'data' => $record,
            'message' => 'Knowledge base record created.',
        ], 201);
    }

    /**
     * GET /api/ai/knowledge-base/{id}
     *
     * Получить одну запись.
     */
    public function show(int $id): JsonResponse
    {
        $record = AiKnowledgeBase::find($id);

        if (! $record) {
            return response()->json(['message' => 'Record not found.'], 404);
        }

        return response()->json(['data' => $record]);
    }

    /**
     * PUT /api/ai/knowledge-base/{id}
     *
     * Обновить запись.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $record = AiKnowledgeBase::find($id);

        if (! $record) {
            return response()->json(['message' => 'Record not found.'], 404);
        }

        $payload = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'content' => ['nullable', 'string', 'min:10', 'max:10000'],
            'category' => ['nullable', 'string', 'max:80'],
            'tool_keys' => ['nullable', 'array'],
            'tool_keys.*' => ['string', 'max:80'],
            'active' => ['nullable', 'boolean'],
        ]);

        try {
            $record->update($payload);
        } catch (Throwable $e) {
            Log::error('Failed to update knowledge base record.', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Failed to update knowledge base record.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }

        return response()->json([
            'data' => $record->fresh(),
            'message' => 'Knowledge base record updated.',
        ]);
    }

    /**
     * DELETE /api/ai/knowledge-base/{id}
     *
     * Удалить запись.
     */
    public function destroy(int $id): JsonResponse
    {
        $record = AiKnowledgeBase::find($id);

        if (! $record) {
            return response()->json(['message' => 'Record not found.'], 404);
        }

        try {
            $record->delete();
        } catch (Throwable $e) {
            Log::error('Failed to delete knowledge base record.', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Failed to delete knowledge base record.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }

        return response()->json(['message' => 'Knowledge base record deleted.']);
    }

    /**
     * POST /api/ai/knowledge-base/fetch
     *
     * Загрузить веб-страницу по URL, извлечь текст и сохранить в базу знаний.
     *
     * Параметры:
     * - fid (required) — ID проекта
     * - url (required) — URL страницы для парсинга
     * - category (optional) — категория знания (по умолчанию 'web_page')
     */
    public function fetchAndSave(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'fid' => ['required', 'integer', 'min:1'],
            'url' => ['required', 'string', 'max:2000'],
            'category' => ['nullable', 'string', 'max:80'],
        ]);

        $fid = (int) $payload['fid'];
        $url = (string) $payload['url'];
        $category = (string) ($payload['category'] ?? 'web_page');

        try {
            $result = $this->knowledgeService->fetchAndSavePage($fid, $url, $category);

            if (! $result['success']) {
                return response()->json([
                    'message' => $result['error'] ?? 'Failed to fetch and save page.',
                ], 422);
            }

            return response()->json([
                'data' => $result['record'],
                'message' => 'Страница успешно сохранена в базу знаний.',
            ], 201);
        } catch (Throwable $e) {
            Log::error('Failed to fetch and save page.', [
                'fid' => $fid,
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Failed to fetch and save page.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * POST /api/ai/knowledge-base/save
     *
     * Сохранить произвольную информацию в базу знаний.
     *
     * Параметры:
     * - fid (required) — ID проекта
     * - title (required) — заголовок информации
     * - content (required) — содержание
     * - category (optional) — категория (по умолчанию 'manual')
     */
    public function saveInformation(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'fid' => ['required', 'integer', 'min:1'],
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string', 'min:10', 'max:50000'],
            'category' => ['nullable', 'string', 'max:80'],
        ]);

        $fid = (int) $payload['fid'];
        $title = (string) $payload['title'];
        $content = (string) $payload['content'];
        $category = (string) ($payload['category'] ?? 'manual');

        try {
            $result = $this->knowledgeService->saveInformation($fid, $title, $content, $category);

            if (! $result['success']) {
                return response()->json([
                    'message' => $result['error'] ?? 'Failed to save information.',
                ], 422);
            }

            return response()->json([
                'data' => $result['record'],
                'message' => 'Информация успешно сохранена в базу знаний.',
            ], 201);
        } catch (Throwable $e) {
            Log::error('Failed to save information.', [
                'fid' => $fid,
                'title' => $title,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Failed to save information.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * POST /api/ai/knowledge-base/manager-ai-ingest
     *
     * Secure callback for manager-ai. Saves researched knowledge and emits a
     * frontend command telling web chats to reread the project KB.
     */
    public function managerAiIngest(Request $request): JsonResponse
    {
        $expectedSecret = trim((string) config('services.manager_ai.bridge_secret', ''));
        $providedSecret = trim((string) (
            $request->header('X-ManagerAI-Bridge-Secret')
            ?: $request->header('X-Manager-AI-Bridge-Secret')
            ?: ''
        ));

        if ($expectedSecret === '' || $providedSecret === '' || ! hash_equals($expectedSecret, $providedSecret)) {
            return response()->json(['message' => 'Invalid ManagerAI bridge secret.'], 403);
        }

        $payload = $request->validate([
            'fid' => ['required', 'integer', 'min:1'],
            'session_token' => ['nullable', 'string', 'max:80'],
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string', 'min:10', 'max:50000'],
            'category' => ['nullable', 'string', 'max:80'],
            'answer' => ['nullable', 'string', 'max:5000'],
        ]);

        $fid = (int) $payload['fid'];
        $category = (string) ($payload['category'] ?? 'manager_ai_research');

        try {
            $result = $this->knowledgeService->saveInformation(
                $fid,
                (string) $payload['title'],
                (string) $payload['content'],
                $category,
                'manager_ai',
            );

            if (! ($result['success'] ?? false)) {
                return response()->json([
                    'message' => $result['error'] ?? 'Failed to save ManagerAI knowledge.',
                ], 422);
            }

            $record = $result['record'];
            $command = [
                'type' => 'read_knowledge_base',
                'command' => 'read_again',
                'fid' => $fid,
                'record_id' => $record?->id,
                'session_token' => (string) ($payload['session_token'] ?? ''),
                'answer' => (string) ($payload['answer'] ?? ''),
                'source' => 'manager-ai',
            ];

            $communication = AgentCommunication::create([
                'source_agent' => 'manager-ai',
                'target_agent' => 'frontend',
                'fid' => $fid,
                'message_type' => 'knowledge_updated',
                'content' => 'ManagerAI пополнил базу знаний. Вебчат должен перечитать KB.',
                'metadata' => [
                    'command' => $command,
                    'knowledge_record_id' => $record?->id,
                    'session_token' => (string) ($payload['session_token'] ?? ''),
                ],
                'status' => 'sent',
            ]);

            broadcast(new AgentCommunicationSent($communication));

            return response()->json([
                'data' => $record,
                'message' => 'ManagerAI knowledge saved.',
                'command' => $command,
            ], 201);
        } catch (Throwable $e) {
            Log::error('ManagerAI knowledge ingest failed.', [
                'fid' => $fid,
                'title' => $payload['title'] ?? null,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Failed to ingest ManagerAI knowledge.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * POST /api/ai/knowledge-base/search
     *
     * Поиск по базе знаний.
     */
    public function search(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'fid' => ['required', 'integer', 'min:1'],
            'query' => ['required', 'string', 'min:2', 'max:200'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $results = $this->knowledgeService->search(
            (int) $payload['fid'],
            (string) $payload['query'],
            (int) ($payload['limit'] ?? 5),
        );

        return response()->json([
            'data' => $results,
        ]);
    }

    /**
     * POST /api/ai/chat/export
     *
     * Экспортировать диалог чата в базу знаний.
     */
    public function exportChat(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'fid' => ['required', 'integer', 'min:1'],
            'question' => ['required', 'string', 'min:2', 'max:2000'],
            'answer' => ['required', 'string', 'min:2', 'max:10000'],
            'category' => ['nullable', 'string', 'max:80'],
        ]);

        try {
            $record = $this->knowledgeService->exportToKnowledgeBase(
                (int) $payload['fid'],
                (string) $payload['question'],
                (string) $payload['answer'],
                (string) ($payload['category'] ?? 'chat_export'),
            );
        } catch (Throwable $e) {
            Log::error('Failed to export chat to knowledge base.', [
                'fid' => $payload['fid'],
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Failed to export chat to knowledge base.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }

        return response()->json([
            'data' => $record,
            'message' => 'Chat exported to knowledge base.',
        ], 201);
    }
}
