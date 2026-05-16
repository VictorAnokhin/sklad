<?php

namespace App\Http\Controllers;

use App\Models\AiKnowledgeCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class AiKnowledgeCategoryController extends Controller
{
    /**
     * GET /api/ai/knowledge-base/categories?fid=12
     *
     * Получить активные категории для проекта.
     */
    public function index(Request $request): JsonResponse
    {
        $fid = $request->has('fid') ? (int) $request->input('fid') : null;
        $categories = AiKnowledgeCategory::getActive($fid);

        return response()->json([
            'data' => $categories->values(),
        ]);
    }

    /**
     * GET /api/ai/knowledge-base/categories/all?fid=12
     *
     * Получить все категории для проекта (включая неактивные).
     */
    public function all(Request $request): JsonResponse
    {
        $fid = $request->has('fid') ? (int) $request->input('fid') : null;
        $categories = AiKnowledgeCategory::getAllForFid($fid);

        return response()->json([
            'data' => $categories->values(),
        ]);
    }

    /**
     * POST /api/ai/knowledge-base/categories
     *
     * Создать категорию для проекта.
     */
    public function store(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'fid' => ['nullable', 'integer', 'min:1'],
            'key' => ['required', 'string', 'max:80', 'unique:ai_knowledge_categories,key'],
            'name' => ['required', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'active' => ['nullable', 'boolean'],
        ]);

        try {
            $category = AiKnowledgeCategory::create([
                'fid' => isset($payload['fid']) ? (int) $payload['fid'] : null,
                'key' => $payload['key'],
                'name' => $payload['name'],
                'sort_order' => (int) ($payload['sort_order'] ?? 0),
                'active' => (bool) ($payload['active'] ?? true),
            ]);
        } catch (Throwable $e) {
            Log::error('Failed to create knowledge category.', [
                'fid' => $payload['fid'] ?? null,
                'key' => $payload['key'],
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Failed to create category.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }

        return response()->json([
            'data' => $category,
            'message' => 'Category created.',
        ], 201);
    }

    /**
     * GET /api/ai/knowledge-base/categories/{id}
     *
     * Получить одну категорию.
     */
    public function show(int $id): JsonResponse
    {
        $category = AiKnowledgeCategory::find($id);

        if (! $category) {
            return response()->json(['message' => 'Category not found.'], 404);
        }

        return response()->json(['data' => $category]);
    }

    /**
     * PUT /api/ai/knowledge-base/categories/{id}
     *
     * Обновить категорию.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $category = AiKnowledgeCategory::find($id);

        if (! $category) {
            return response()->json(['message' => 'Category not found.'], 404);
        }

        $payload = $request->validate([
            'fid' => ['nullable', 'integer', 'min:1'],
            'key' => ['nullable', 'string', 'max:80', 'unique:ai_knowledge_categories,key,' . $id],
            'name' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'active' => ['nullable', 'boolean'],
        ]);

        try {
            $data = $payload;
            if (array_key_exists('fid', $payload)) {
                $data['fid'] = $payload['fid'] !== null ? (int) $payload['fid'] : null;
            }
            $category->update($data);
        } catch (Throwable $e) {
            Log::error('Failed to update knowledge category.', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Failed to update category.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }

        return response()->json([
            'data' => $category->fresh(),
            'message' => 'Category updated.',
        ]);
    }

    /**
     * DELETE /api/ai/knowledge-base/categories/{id}
     *
     * Удалить категорию.
     */
    public function destroy(int $id): JsonResponse
    {
        $category = AiKnowledgeCategory::find($id);

        if (! $category) {
            return response()->json(['message' => 'Category not found.'], 404);
        }

        try {
            $category->delete();
        } catch (Throwable $e) {
            Log::error('Failed to delete knowledge category.', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Failed to delete category.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }

        return response()->json(['message' => 'Category deleted.']);
    }
}
