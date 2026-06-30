<?php

namespace App\Http\Controllers;

use App\Models\TrafficRule;
use App\Models\TrafficSign;
use App\Models\TrafficTestQuestion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TrafficLearningController extends Controller
{
    public function publicIndex(Request $request): JsonResponse
    {
        $fid = (int) $request->input('fid', 2);

        return response()->json([
            'rules' => TrafficRule::query()->where('fid', $fid)->where('is_published', true)->orderBy('sort_order')->orderBy('id')->get(),
            'signs' => TrafficSign::query()->where('fid', $fid)->where('is_published', true)->orderBy('sort_order')->orderBy('id')->get(),
            'tests' => TrafficTestQuestion::query()->where('fid', $fid)->where('is_published', true)->orderBy('sort_order')->orderBy('id')->get(),
        ]);
    }

    public function settingsIndex(Request $request): JsonResponse
    {
        $fid = (int) $request->input('fid', session('fid', 2));

        return response()->json([
            'rules' => TrafficRule::query()->where('fid', $fid)->orderBy('sort_order')->orderBy('id')->get(),
            'signs' => TrafficSign::query()->where('fid', $fid)->orderBy('sort_order')->orderBy('id')->get(),
            'tests' => TrafficTestQuestion::query()->where('fid', $fid)->orderBy('sort_order')->orderBy('id')->get(),
        ]);
    }

    public function store(Request $request, string $type): JsonResponse
    {
        $model = $this->modelFor($type);
        $payload = $this->validated($request, $type);
        $payload = $this->storeSignImage($request, $type, $payload);
        $item = $model::query()->create($payload);

        return response()->json(['item' => $item], 201);
    }

    public function update(Request $request, string $type, int $id): JsonResponse
    {
        $model = $this->modelFor($type);
        $item = $model::query()->findOrFail($id);
        $payload = $this->validated($request, $type);
        $payload = $this->storeSignImage($request, $type, $payload);
        $item->update($payload);

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
        abort_unless(in_array($type, ['rules', 'signs', 'tests'], true), 404);

        return match ($type) {
            'rules' => TrafficRule::class,
            'signs' => TrafficSign::class,
            'tests' => TrafficTestQuestion::class,
        };
    }

    private function validated(Request $request, string $type): array
    {
        $common = [
            'fid' => ['required', 'integer', 'min:1'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_published' => ['required', 'boolean'],
        ];
        $specific = match ($type) {
            'rules' => [
                'section_number' => ['required', 'string', 'max:20'],
                'title' => ['required', 'string', 'max:255'],
                'summary' => ['required', 'string', 'max:2000'],
                'content' => ['nullable', 'string'],
            ],
            'signs' => [
                'code' => ['required', 'string', 'max:30'],
                'title' => ['required', 'string', 'max:255'],
                'description' => ['nullable', 'string'],
                'image_url' => ['nullable', 'string', 'max:500', 'required_without:image_upload'],
                'image_upload' => ['nullable', 'file', 'mimes:svg,png,jpg,jpeg,webp', 'mimetypes:image/svg+xml,image/png,image/jpeg,image/webp', 'max:4096'],
                'category' => ['nullable', 'string', 'max:100'],
            ],
            'tests' => [
                'source_external_id' => ['nullable', 'integer', 'min:1'],
                'topic_external_id' => ['nullable', 'integer', 'min:1'],
                'question' => ['required', 'string', 'max:5000'],
                'answers' => ['required', 'array', 'min:2'],
                'answers.*' => ['required', 'string', 'max:2000'],
                'correct_answer' => ['nullable', 'integer', 'min:0'],
                'explanation' => ['nullable', 'string'],
                'image_url' => ['nullable', 'string', 'max:500'],
                'source_url' => ['nullable', 'url', 'max:500'],
            ],
        };

        return $request->validate(array_merge($common, $specific));
    }

    private function storeSignImage(Request $request, string $type, array $payload): array
    {
        if ($type !== 'signs') {
            return $payload;
        }

        unset($payload['image_upload']);

        if (! $request->hasFile('image_upload')) {
            return $payload;
        }

        $path = $request->file('image_upload')->store('traffic-signs', 'public');
        $payload['image_url'] = Storage::disk('public')->url($path);

        return $payload;
    }
}
