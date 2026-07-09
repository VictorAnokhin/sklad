<?php

namespace App\Http\Controllers;

use App\Models\EducationProgress;
use App\Models\EducationTopic;
use App\Models\EducationalMaterial;
use App\Models\QuestTest;
use App\Models\QuestTestAttempt;
use App\Models\Project;
use App\Services\EducationMaterialResolver;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class EducationController extends Controller
{
    public function publicFirstTest(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'fid' => ['required', 'integer', 'min:1'],
        ]);
        $test = $this->firstPublicTest((int) $validated['fid']);

        return response()->json([
            'test' => [
                'id' => $test->id,
                'title' => $test->title,
                'passing_score' => $test->passing_score,
                'topic' => $test->material->topic->title,
                'level' => $test->material->level,
                'questions' => collect($test->quest_data['questions'] ?? [])
                    ->values()
                    ->map(fn ($question, $index) => [
                        'id' => $index,
                        'text' => (string) ($question['text'] ?? ''),
                        'options' => array_values($question['options'] ?? []),
                    ]),
            ],
        ]);
    }

    public function publicSubmitFirstTest(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'fid' => ['required', 'integer', 'min:1'],
            'test_id' => ['required', 'integer', 'min:1'],
            'answers' => ['required', 'array'],
            'answers.*' => ['required', 'integer', 'min:0'],
        ]);
        $test = $this->firstPublicTest((int) $validated['fid']);
        abort_unless((int) $test->id === (int) $validated['test_id'], 404);

        $questions = array_values($test->quest_data['questions'] ?? []);
        abort_if(count($questions) === 0, 422, 'В тесте нет вопросов.');
        $correct = 0;

        foreach ($questions as $index => $question) {
            if ((int) ($validated['answers'][$index] ?? -1) === (int) ($question['correct_index'] ?? -2)) {
                $correct++;
            }
        }

        $score = (int) round($correct * 100 / count($questions));

        return response()->json([
            'score' => $score,
            'passed' => $score >= (int) $test->passing_score,
            'passing_score' => (int) $test->passing_score,
            'correct_answers' => $correct,
            'questions_count' => count($questions),
        ]);
    }

    public function course(EducationMaterialResolver $resolver)
    {
        $project = $this->educationProject();
        $userId = (int) Auth::id();

        $topics = EducationTopic::query()
            ->where('project_id', $project->id)
            ->where('is_active', true)
            ->with(['materials' => fn ($query) => $query
                ->where('is_active', true)
                ->orderBy('level')
                ->orderByRaw('CAST(version AS DECIMAL(10,2))')])
            ->orderBy('position')
            ->orderBy('id')
            ->get();

        $progressByTopic = EducationProgress::query()
            ->where('user_id', $userId)
            ->whereIn('topic_id', $topics->pluck('id'))
            ->get()
            ->keyBy('topic_id');

        $topics->each(function (EducationTopic $topic) use ($progressByTopic, $resolver) {
            $progress = $progressByTopic->get($topic->id);
            $material = $progress?->current_material_id
                ? $topic->materials()->whereKey($progress->current_material_id)->where('is_active', true)->first()
                : null;

            $topic->setRelation('selectedMaterial', $material
                ?? $resolver->initial((int) $topic->id, (string) ($progress?->current_level ?? 'beginner')));
            $topic->setRelation('studentProgress', $progress);
        });

        return view('education.course', compact('project', 'topics'));
    }

    public function storeMaterial(Request $request)
    {
        $project = $this->educationProject();
        $validated = $this->validateMaterial($request);

        DB::transaction(function () use ($validated, $project) {
            $topic = empty($validated['topic_id'])
                ? EducationTopic::create([
                    'project_id' => $project->id,
                    'title' => $validated['topic_title'],
                    'description' => $validated['topic_description'] ?? null,
                    'position' => $validated['position'] ?? 0,
                    'is_active' => true,
                ])
                : EducationTopic::query()
                    ->where('project_id', $project->id)
                    ->whereKey($validated['topic_id'])
                    ->firstOrFail();

            EducationalMaterial::create([
                'topic_id' => $topic->id,
                'level' => $validated['level'],
                'content_type' => $validated['content_type'],
                'body' => $validated['body'],
                'version' => $validated['version'],
                'is_active' => true,
            ]);
        });

        return redirect()->route('education.course')->with('success', 'Материал курса создан.');
    }

    public function updateMaterial(Request $request, EducationalMaterial $material)
    {
        $project = $this->educationProject();
        $this->assertMaterialProject($material, $project);
        $validated = $this->validateMaterial($request, $material);

        DB::transaction(function () use ($validated, $material) {
            $material->topic->update([
                'title' => $validated['topic_title'],
                'description' => $validated['topic_description'] ?? null,
                'position' => $validated['position'] ?? 0,
            ]);
            $material->update([
                'level' => $validated['level'],
                'content_type' => $validated['content_type'],
                'body' => $validated['body'],
                'version' => $validated['version'],
            ]);
        });

        return redirect()->route('education.course')->with('success', 'Материал курса изменён.');
    }

    public function destroyMaterial(EducationalMaterial $material)
    {
        $project = $this->educationProject();
        $this->assertMaterialProject($material, $project);
        DB::transaction(function () use ($material) {
            $topic = $material->topic;
            $material->delete();

            if (!$topic->materials()->exists()) {
                $topic->delete();
            }
        });

        return redirect()->route('education.course')->with('success', 'Материал курса удалён.');
    }

    public function tests()
    {
        $project = $this->educationProject();
        $tests = QuestTest::query()
            ->with(['material.topic'])
            ->where('is_active', true)
            ->whereHas('material.topic', fn ($query) => $query
                ->where('project_id', $project->id)
                ->where('is_active', true))
            ->whereHas('material', fn ($query) => $query->where('is_active', true))
            ->orderBy('id')
            ->get();

        $materials = EducationalMaterial::query()
            ->with('topic')
            ->where('is_active', true)
            ->whereHas('topic', fn ($query) => $query
                ->where('project_id', $project->id)
                ->where('is_active', true))
            ->orderBy('topic_id')
            ->orderBy('level')
            ->get();

        $userId = (int) Auth::id();

        $attempts = QuestTestAttempt::query()
            ->where('user_id', $userId)
            ->whereIn('quest_test_id', $tests->pluck('id'))
            ->latest()
            ->get()
            ->groupBy('quest_test_id');

        return view('education.tests', compact('project', 'tests', 'attempts', 'materials'));
    }

    public function storeTest(Request $request)
    {
        $project = $this->educationProject();
        $validated = $this->validateTest($request);
        $material = EducationalMaterial::query()->findOrFail($validated['material_id']);
        $this->assertMaterialProject($material, $project);

        QuestTest::create($validated + ['is_active' => true]);

        return redirect()->route('education.tests')->with('success', 'Тест создан.');
    }

    public function updateTest(Request $request, QuestTest $test)
    {
        $project = $this->educationProject();
        $this->assertTestProject($test, $project);
        $validated = $this->validateTest($request);
        $material = EducationalMaterial::query()->findOrFail($validated['material_id']);
        $this->assertMaterialProject($material, $project);
        $test->update($validated);

        return redirect()->route('education.tests')->with('success', 'Тест изменён.');
    }

    public function destroyTest(QuestTest $test)
    {
        $project = $this->educationProject();
        $this->assertTestProject($test, $project);
        $test->delete();

        return redirect()->route('education.tests')->with('success', 'Тест удалён.');
    }

    public function submit(Request $request, QuestTest $test, EducationMaterialResolver $resolver)
    {
        $project = $this->educationProject();
        $test->load('material.topic');
        abort_unless((int) $test->material->topic->project_id === (int) $project->id && $test->is_active, 404);

        $questions = $test->quest_data['questions'] ?? [];
        abort_if(!is_array($questions) || count($questions) === 0, 422, 'В тесте нет вопросов.');

        $validated = $request->validate([
            'answers' => ['required', 'array'],
            'answers.*' => ['nullable', 'integer', 'min:0'],
        ]);

        $correct = 0;
        foreach ($questions as $index => $question) {
            if ((int) ($validated['answers'][$index] ?? -1) === (int) ($question['correct_index'] ?? -2)) {
                $correct++;
            }
        }

        $score = (int) round($correct * 100 / count($questions));
        $passed = $score >= $test->passing_score;
        $userId = (int) Auth::id();

        DB::transaction(function () use ($test, $resolver, $userId, $validated, $score, $passed) {
            $progress = EducationProgress::query()->firstOrCreate(
                ['user_id' => $userId, 'topic_id' => $test->material->topic_id],
                ['current_level' => $test->material->level, 'current_material_id' => $test->material_id]
            );

            $nextMaterial = $passed
                ? $resolver->nextLevel($test->material)
                : $resolver->afterFailure($test->material);

            $progress->current_level = $nextMaterial?->level ?? $test->material->level;
            $progress->current_material_id = $nextMaterial?->id ?? $test->material_id;
            $progress->failed_attempts += $passed ? 0 : 1;
            $progress->passed_attempts += $passed ? 1 : 0;
            $progress->completed_at = $passed && $nextMaterial?->id === $test->material_id ? now() : null;
            $progress->save();

            QuestTestAttempt::create([
                'user_id' => $userId,
                'quest_test_id' => $test->id,
                'material_id' => $test->material_id,
                'score' => $score,
                'passed' => $passed,
                'answers' => $validated['answers'],
                'next_material_id' => $nextMaterial?->id,
            ]);
        });

        return redirect()->route('education.tests')->with(
            $passed ? 'success' : 'warning',
            $passed
                ? "Тест пройден: {$score}%. Открыт следующий уровень."
                : "Результат {$score}%. Материал автоматически заменён на более подходящую версию."
        );
    }

    private function educationProject(): Project
    {
        $project = Project::query()->find((int) session('fid'));
        abort_unless($project && strtolower(trim((string) $project->project_type)) === 'education', 403);

        return $project;
    }

    private function validateMaterial(Request $request, ?EducationalMaterial $material = null): array
    {
        $topicId = $material?->topic_id ?? $request->integer('topic_id');
        $versionUnique = Rule::unique('educational_materials', 'version')
            ->where(fn ($query) => $query
                ->where('topic_id', $topicId)
                ->where('level', $request->input('level')))
            ->ignore($material?->id);

        return $request->validate([
            'topic_id' => ['nullable', 'integer'],
            'topic_title' => ['required', 'string', 'max:255'],
            'topic_description' => ['nullable', 'string'],
            'position' => ['nullable', 'integer', 'min:0'],
            'level' => ['required', 'in:beginner,intermediate,advanced'],
            'content_type' => ['required', 'in:markdown,video_link,interactive_scenario'],
            'body' => ['required', 'string'],
            'version' => ['required', 'string', 'max:32', $versionUnique],
        ]);
    }

    private function validateTest(Request $request): array
    {
        $validated = $request->validate([
            'material_id' => ['required', 'integer'],
            'title' => ['required', 'string', 'max:255'],
            'passing_score' => ['required', 'integer', 'min:1', 'max:100'],
            'quest_data' => ['required', 'json'],
        ]);
        $validated['quest_data'] = json_decode($validated['quest_data'], true, 512, JSON_THROW_ON_ERROR);

        if (!isset($validated['quest_data']['questions']) || !is_array($validated['quest_data']['questions'])) {
            abort(422, 'quest_data должен содержать массив questions.');
        }

        return $validated;
    }

    private function assertMaterialProject(EducationalMaterial $material, Project $project): void
    {
        $material->loadMissing('topic');
        abort_unless((int) $material->topic?->project_id === (int) $project->id, 404);
    }

    private function assertTestProject(QuestTest $test, Project $project): void
    {
        $test->loadMissing('material.topic');
        abort_unless((int) $test->material?->topic?->project_id === (int) $project->id, 404);
    }

    private function firstPublicTest(int $projectId): QuestTest
    {
        return QuestTest::query()
            ->with('material.topic')
            ->where('is_active', true)
            ->whereHas('material', fn ($query) => $query->where('is_active', true))
            ->whereHas('material.topic', fn ($query) => $query
                ->where('project_id', $projectId)
                ->where('is_active', true))
            ->orderBy('id')
            ->firstOrFail();
    }
}
