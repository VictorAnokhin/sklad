<?php

namespace App\Http\Controllers;

use App\Models\AiTool;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class AiToolController extends Controller
{
    /**
     * GET /api/ai/tools?fid=12
     *
     * Получить активные инструменты для проекта.
     */
    public function index(Request $request): JsonResponse
    {
        $fid = $request->has('fid') ? (int) $request->input('fid') : null;
        $tools = AiTool::getActive($fid);

        return response()->json([
            'data' => $tools->values(),
        ]);
    }

    /**
     * GET /api/ai/tools/all?fid=12
     *
     * Получить все инструменты для проекта (включая неактивные).
     */
    public function all(Request $request): JsonResponse
    {
        $fid = $request->has('fid') ? (int) $request->input('fid') : null;
        $tools = AiTool::getAllForFid($fid);

        return response()->json([
            'data' => $tools->values(),
        ]);
    }

    /**
     * POST /api/ai/tools
     *
     * Создать инструмент.
     */
    public function store(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'fid' => ['nullable', 'integer', 'min:1'],
            'key' => ['required', 'string', 'max:80'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'schema' => ['required', 'array'],
            'active' => ['nullable', 'boolean'],
        ]);

        // Проверка уникальности fid + key
        $fid = isset($payload['fid']) ? (int) $payload['fid'] : null;
        $existing = AiTool::forFid($fid)->where('key', $payload['key'])->first();
        if ($existing) {
            return response()->json([
                'message' => 'Tool with this key already exists for this project.',
            ], 422);
        }

        try {
            $tool = AiTool::create([
                'fid' => $fid,
                'key' => $payload['key'],
                'name' => $payload['name'],
                'description' => $payload['description'] ?? null,
                'schema' => $payload['schema'],
                'active' => (bool) ($payload['active'] ?? true),
            ]);
        } catch (Throwable $e) {
            Log::error('Failed to create AI tool.', [
                'fid' => $fid,
                'key' => $payload['key'],
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Failed to create tool.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }

        return response()->json([
            'data' => $tool,
            'message' => 'Tool created.',
        ], 201);
    }

    /**
     * GET /api/ai/tools/{id}
     *
     * Получить один инструмент.
     */
    public function show(int $id): JsonResponse
    {
        $tool = AiTool::find($id);

        if (! $tool) {
            return response()->json(['message' => 'Tool not found.'], 404);
        }

        return response()->json(['data' => $tool]);
    }

    /**
     * PUT /api/ai/tools/{id}
     *
     * Обновить инструмент.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $tool = AiTool::find($id);

        if (! $tool) {
            return response()->json(['message' => 'Tool not found.'], 404);
        }

        $payload = $request->validate([
            'fid' => ['nullable', 'integer', 'min:1'],
            'key' => ['nullable', 'string', 'max:80'],
            'name' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'schema' => ['nullable', 'array'],
            'active' => ['nullable', 'boolean'],
        ]);

        // Проверка уникальности при смене key или fid
        if (array_key_exists('key', $payload) || array_key_exists('fid', $payload)) {
            $newFid = array_key_exists('fid', $payload)
                ? ($payload['fid'] !== null ? (int) $payload['fid'] : null)
                : $tool->fid;
            $newKey = $payload['key'] ?? $tool->key;

            $duplicate = AiTool::forFid($newFid)
                ->where('key', $newKey)
                ->where('id', '!=', $id)
                ->first();

            if ($duplicate) {
                return response()->json([
                    'message' => 'Another tool with this key already exists for this project.',
                ], 422);
            }
        }

        try {
            $data = [];
            foreach (['fid', 'key', 'name', 'description', 'schema', 'active'] as $field) {
                if (array_key_exists($field, $payload)) {
                    $value = $payload[$field];
                    if ($field === 'fid') {
                        $value = $value !== null ? (int) $value : null;
                    } elseif ($field === 'active') {
                        $value = (bool) $value;
                    }
                    $data[$field] = $value;
                }
            }

            $tool->update($data);
        } catch (Throwable $e) {
            Log::error('Failed to update AI tool.', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Failed to update tool.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }

        return response()->json([
            'data' => $tool->fresh(),
            'message' => 'Tool updated.',
        ]);
    }

    /**
     * DELETE /api/ai/tools/{id}
     *
     * Удалить инструмент.
     */
    public function destroy(int $id): JsonResponse
    {
        $tool = AiTool::find($id);

        if (! $tool) {
            return response()->json(['message' => 'Tool not found.'], 404);
        }

        try {
            $tool->delete();
        } catch (Throwable $e) {
            Log::error('Failed to delete AI tool.', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Failed to delete tool.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }

        return response()->json(['message' => 'Tool deleted.']);
    }
}
