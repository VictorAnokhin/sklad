<?php

namespace App\Http\Controllers;

use App\Models\TrafficRule;
use App\Models\TrafficSign;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TrafficLearningController extends Controller
{
    public function publicIndex(Request $request): JsonResponse
    {
        $fid = (int) $request->input('fid', 2);

        return response()->json([
            'rules' => TrafficRule::query()->where('fid', $fid)->where('is_published', true)->orderBy('sort_order')->orderBy('id')->get(),
            'signs' => TrafficSign::query()->where('fid', $fid)->where('is_published', true)->orderBy('sort_order')->orderBy('id')->get(),
        ]);
    }

    public function settingsIndex(Request $request): JsonResponse
    {
        $fid = (int) $request->input('fid', session('fid', 2));

        return response()->json([
            'rules' => TrafficRule::query()->where('fid', $fid)->orderBy('sort_order')->orderBy('id')->get(),
            'signs' => TrafficSign::query()->where('fid', $fid)->orderBy('sort_order')->orderBy('id')->get(),
        ]);
    }

    public function store(Request $request, string $type): JsonResponse
    {
        $model = $this->modelFor($type);
        $item = $model::query()->create($this->validated($request, $type));

        return response()->json(['item' => $item], 201);
    }

    public function update(Request $request, string $type, int $id): JsonResponse
    {
        $model = $this->modelFor($type);
        $item = $model::query()->findOrFail($id);
        $item->update($this->validated($request, $type));

        return response()->json(['item' => $item->fresh()]);
    }

    public function destroy(string $type, int $id): JsonResponse
    {
        $model = $this->modelFor($type);
        $model::query()->findOrFail($id)->delete();

        return response()->json(['message' => 'Видалено']);
    }

    /**
     * @return class-string<Model>
     */
    private function modelFor(string $type): string
    {
        abort_unless(in_array($type, ['rules', 'signs'], true), 404);

        return $type === 'rules' ? TrafficRule::class : TrafficSign::class;
    }

    private function validated(Request $request, string $type): array
    {
        $common = [
            'fid' => ['required', 'integer', 'min:1'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_published' => ['required', 'boolean'],
        ];
        $specific = $type === 'rules'
            ? [
                'section_number' => ['required', 'string', 'max:20'],
                'title' => ['required', 'string', 'max:255'],
                'summary' => ['required', 'string', 'max:2000'],
                'content' => ['nullable', 'string'],
            ]
            : [
                'code' => ['required', 'string', 'max:30'],
                'title' => ['required', 'string', 'max:255'],
                'description' => ['nullable', 'string', 'max:2000'],
                'image_url' => ['required', 'string', 'max:500'],
                'category' => ['nullable', 'string', 'max:100'],
            ];

        return $request->validate(array_merge($common, $specific));
    }
}
