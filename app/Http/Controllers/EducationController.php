<?php

namespace App\Http\Controllers;

use App\Models\EducationProgress;
use App\Models\EducationTopic;
use App\Models\QuestTest;
use App\Models\QuestTestAttempt;
use App\Models\Project;
use App\Services\EducationMaterialResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EducationController extends Controller
{
    public function course(EducationMaterialResolver $resolver)
    {
        $project = $this->educationProject();
        $userId = (int) Auth::id();

        $topics = EducationTopic::query()
            ->where('project_id', $project->id)
            ->where('is_active', true)
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

    public function tests()
    {
        $project = $this->educationProject();
        $userId = (int) Auth::id();

        $tests = QuestTest::query()
            ->with(['material.topic'])
            ->where('is_active', true)
            ->whereHas('material.topic', fn ($query) => $query
                ->where('project_id', $project->id)
                ->where('is_active', true))
            ->whereHas('material', function ($query) use ($userId) {
                $query->where('is_active', true)
                    ->where(function ($scope) use ($userId) {
                        $scope->whereIn('id', EducationProgress::query()
                            ->select('current_material_id')
                            ->where('user_id', $userId)
                            ->whereNotNull('current_material_id'))
                            ->orWhere('level', 'beginner');
                    });
            })
            ->orderBy('id')
            ->get();

        $attempts = QuestTestAttempt::query()
            ->where('user_id', $userId)
            ->whereIn('quest_test_id', $tests->pluck('id'))
            ->latest()
            ->get()
            ->groupBy('quest_test_id');

        return view('education.tests', compact('project', 'tests', 'attempts'));
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
}
