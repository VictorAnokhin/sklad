<?php

namespace App\Http\Controllers;

use App\Models\EducationProgress;
use App\Models\EducationTopic;
use App\Models\EducationalMaterial;
use App\Models\QuestTest;
use App\Models\QuestTestAttempt;
use App\Models\QuestTestResult;
use App\Models\Project;
use App\Services\EducationMaterialResolver;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class EducationController extends Controller
{
    public function publicFirstTest(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'fid' => ['required', 'integer', 'min:1'],
            'lang' => ['nullable', 'in:ua,ru,en'],
        ]);
        abort_unless($this->educationSchemaReady(), 503, 'Таблицы образовательного модуля ещё не созданы.');
        $lang = $this->language($request);

        $test = $this->firstPublicTest((int) $validated['fid']);
        $questData = $this->localizedQuestData($test, $lang);

        return response()->json([
            'test' => [
                'id' => $test->id,
                'title' => $this->localizedText($test->title_translations, $lang, (string) $test->title),
                'passing_score' => $test->passing_score,
                'intro' => (string) ($questData['intro'] ?? ''),
                'topic' => $test->material?->topic
                    ? $this->localizedText($test->material->topic->title_translations, $lang, (string) $test->material->topic->title)
                    : 'Диагностика',
                'level' => $test->material?->level ?? (string) ($test->test_type ?? 'profile_assessment'),
                'questions' => collect($questData['questions'] ?? [])
                    ->values()
                    ->map(fn ($question, $index) => [
                        'id' => $index,
                        'text' => (string) ($question['text'] ?? ''),
                        'options' => $this->publicQuestionOptions($question['options'] ?? []),
                    ]),
            ],
        ]);
    }

    public function publicSubmitFirstTest(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'fid' => ['required', 'integer', 'min:1'],
            'test_id' => ['required', 'integer', 'min:1'],
            'lang' => ['nullable', 'in:ua,ru,en'],
            'answers' => ['required', 'array'],
            'answers.*' => ['required', 'integer', 'min:0'],
        ]);
        abort_unless($this->educationSchemaReady(), 503, 'Таблицы образовательного модуля ещё не созданы.');

        $test = $this->firstPublicTest((int) $validated['fid']);
        abort_unless((int) $test->id === (int) $validated['test_id'], 404);

        $questions = array_values($test->quest_data['questions'] ?? []);
        abort_if(count($questions) === 0, 422, 'В тесте нет вопросов.');
        $result = $this->evaluateTest($test, $validated['answers'], (int) $test->passing_score, $this->language($request));

        return response()->json([
            'score' => $result['score'],
            'passed' => $result['passed'],
            'passing_score' => (int) $test->passing_score,
            'correct_answers' => $result['correct_answers'],
            'questions_count' => count($questions),
            'scoring_type' => $result['scoring_type'],
            'total_score' => $result['total_score'],
            'max_score' => $result['max_score'],
            'profile' => $result['profile'],
            'rating_award' => (int) ($test->quest_data['rating'] ?? 0),
        ]);
    }

    public function applyKnowYourselfRating(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'fid' => ['required', 'integer', 'min:1'],
            'test_id' => ['required', 'integer', 'min:1'],
        ]);
        abort_unless($this->educationSchemaReady(), 503, 'Таблицы образовательного модуля ещё не созданы.');

        $test = $this->knowYourselfTestsQuery((int) $validated['fid'])
            ->findOrFail((int) $validated['test_id']);
        $rating = max(0, (int) ($test->quest_data['rating'] ?? 0));
        $user = $request->user();
        abort_unless($user, 401);

        $current = (int) ($user->education_rating ?? 0);
        if ($rating > $current) {
            $user->forceFill(['education_rating' => $rating])->save();
        }

        return response()->json([
            'rating_award' => $rating,
            'education_rating' => max($current, $rating),
        ]);
    }

    public function publicCourse(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'fid' => ['required', 'integer', 'min:1'],
            'lang' => ['nullable', 'in:ua,ru,en'],
        ]);
        abort_unless($this->educationSchemaReady(), 503, 'Таблицы образовательного модуля ещё не созданы.');
        $lang = $this->language($request);

        $topics = EducationTopic::query()
            ->where('project_id', (int) $validated['fid'])
            ->where('is_active', true)
            ->with(['materials' => fn ($query) => $query
                ->where('is_active', true)
                ->orderBy('level')
                ->orderByRaw('CAST(version AS DECIMAL(10,2))')])
            ->orderBy('position')
            ->orderBy('id')
            ->get()
            ->map(fn (EducationTopic $topic) => [
                'id' => $topic->id,
                'title' => $this->localizedText($topic->title_translations, $lang, (string) $topic->title),
                'description' => $this->localizedText($topic->description_translations, $lang, (string) ($topic->description ?? '')),
                'rating' => (int) $topic->position,
                'materials' => $topic->materials
                    ->map(fn (EducationalMaterial $material) => [
                        'id' => $material->id,
                        'title' => $this->localizedText(
                            $material->title_translations,
                            $lang,
                            $material->title ?: $this->localizedText($topic->title_translations, $lang, (string) $topic->title)
                        ),
                        'level' => $material->level,
                        'content_type' => $material->content_type,
                        'version' => $material->version,
                        'body' => $this->localizedText($material->body_translations, $lang, (string) $material->body),
                    ])
                    ->values(),
            ])
            ->values();

        return response()->json(['topics' => $topics]);
    }

    public function publicKnowYourselfTests(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'fid' => ['required', 'integer', 'min:1'],
            'lang' => ['nullable', 'in:ua,ru,en'],
        ]);
        abort_unless($this->educationSchemaReady(), 503, 'Таблицы образовательного модуля ещё не созданы.');
        $lang = $this->language($request);

        $tests = $this->knowYourselfTestsQuery((int) $validated['fid'])
            ->orderBy('id')
            ->get()
            ->map(fn (QuestTest $test) => $this->publicTestPayload($test, $lang))
            ->values();

        return response()->json(['tests' => $tests]);
    }

    public function publicSubmitKnowYourselfTest(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'fid' => ['required', 'integer', 'min:1'],
            'test_id' => ['required', 'integer', 'min:1'],
            'lang' => ['nullable', 'in:ua,ru,en'],
            'answers' => ['required', 'array'],
            'answers.*' => ['required', 'integer', 'min:0'],
        ]);
        abort_unless($this->educationSchemaReady(), 503, 'Таблицы образовательного модуля ещё не созданы.');

        $test = $this->knowYourselfTestsQuery((int) $validated['fid'])
            ->findOrFail((int) $validated['test_id']);

        $questions = array_values($test->quest_data['questions'] ?? []);
        abort_if(count($questions) === 0, 422, 'В тесте нет вопросов.');
        $result = $this->evaluateTest($test, $validated['answers'], (int) $test->passing_score, $this->language($request));

        return response()->json([
            'score' => $result['score'],
            'passed' => $result['passed'],
            'passing_score' => (int) $test->passing_score,
            'correct_answers' => $result['correct_answers'],
            'questions_count' => count($questions),
            'scoring_type' => $result['scoring_type'],
            'total_score' => $result['total_score'],
            'max_score' => $result['max_score'],
            'profile' => $result['profile'],
            'rating_award' => (int) ($test->quest_data['rating'] ?? 0),
        ]);
    }

    public function course(EducationMaterialResolver $resolver)
    {
        $project = $this->educationProject();
        if (!$this->educationSchemaReady()) {
            return view('education.course', [
                'project' => $project,
                'topics' => collect(),
                'materialEditorItems' => [],
                'topicEditorItems' => [],
                'migrationRequired' => true,
            ]);
        }

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

        $materialEditorItems = $topics
            ->flatMap(fn (EducationTopic $topic) => $topic->materials->map(fn (EducationalMaterial $material) => [
                'id' => $material->id,
                'topic_id' => $topic->id,
                'topic_title' => $topic->title,
                'topic_description' => $topic->description,
                'topic_title_translations' => $topic->title_translations ?? [],
                'topic_description_translations' => $topic->description_translations ?? [],
                'position' => $topic->position,
                'title' => $material->title,
                'title_translations' => $material->title_translations ?? [],
                'level' => $material->level,
                'content_type' => $material->content_type,
                'version' => $material->version,
                'body' => $material->body,
                'body_translations' => $material->body_translations ?? [],
            ]))
            ->keyBy('id')
            ->all();

        $topicEditorItems = $topics
            ->mapWithKeys(fn (EducationTopic $topic) => [
                $topic->id => [
                    'title' => $topic->title,
                    'description' => $topic->description,
                    'title_translations' => $topic->title_translations ?? [],
                    'description_translations' => $topic->description_translations ?? [],
                    'position' => $topic->position,
                    'rating' => $topic->position,
                ],
            ])
            ->all();

        return view('education.course', [
            'project' => $project,
            'topics' => $topics,
            'materialEditorItems' => $materialEditorItems,
            'topicEditorItems' => $topicEditorItems,
            'migrationRequired' => false,
        ]);
    }

    public function storeMaterial(Request $request)
    {
        $project = $this->educationProject();
        $validated = $this->validateMaterial($request);

        DB::transaction(function () use ($validated, $project) {
            $topic = EducationTopic::query()
                ->where('project_id', $project->id)
                ->whereKey($validated['topic_id'])
                ->firstOrFail();

            EducationalMaterial::create([
                'topic_id' => $topic->id,
                'title' => $validated['title'],
                'title_translations' => $validated['title_translations'],
                'level' => $validated['level'],
                'content_type' => $validated['content_type'],
                'body' => $validated['body'],
                'body_translations' => $validated['body_translations'],
                'version' => $validated['version'],
                'is_active' => true,
            ]);
        });

        return redirect()->route('education.course')->with('success', 'Материал курса создан.');
    }

    public function storeTopic(Request $request)
    {
        $project = $this->educationProject();
        $validated = $this->validateTopic($request);

        EducationTopic::create([
            'project_id' => $project->id,
            'title' => $validated['title'],
            'title_translations' => $validated['title_translations'],
            'description' => $validated['description'] ?? null,
            'description_translations' => $validated['description_translations'],
            'position' => $validated['position'] ?? 0,
            'is_active' => true,
        ]);

        return redirect()->route('education.course')->with('success', 'Курс создан.');
    }

    public function updateTopic(Request $request, EducationTopic $topic)
    {
        $project = $this->educationProject();
        $this->assertTopicProject($topic, $project);
        $validated = $this->validateTopic($request);

        $topic->update([
            'title' => $validated['title'],
            'title_translations' => $validated['title_translations'],
            'description' => $validated['description'] ?? null,
            'description_translations' => $validated['description_translations'],
            'position' => $validated['position'] ?? 0,
        ]);

        return redirect()->route('education.course')->with('success', 'Курс изменён.');
    }

    public function destroyTopic(EducationTopic $topic)
    {
        $project = $this->educationProject();
        $this->assertTopicProject($topic, $project);
        $topic->delete();

        return redirect()->route('education.course')->with('success', 'Курс удалён.');
    }

    public function updateMaterial(Request $request, EducationalMaterial $material)
    {
        $project = $this->educationProject();
        $this->assertMaterialProject($material, $project);
        $validated = $this->validateMaterial($request, $material);

        DB::transaction(function () use ($validated, $material, $project) {
            EducationTopic::query()
                ->where('project_id', $project->id)
                ->whereKey($validated['topic_id'])
                ->firstOrFail();

            $material->update([
                'topic_id' => $validated['topic_id'],
                'title' => $validated['title'],
                'title_translations' => $validated['title_translations'],
                'level' => $validated['level'],
                'content_type' => $validated['content_type'],
                'body' => $validated['body'],
                'body_translations' => $validated['body_translations'],
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
        if (!$this->educationSchemaReady()) {
            return view('education.tests', [
                'project' => $project,
                'tests' => collect(),
                'attempts' => collect(),
                'materials' => collect(),
                'testEditorItems' => [],
                'migrationRequired' => true,
            ]);
        }

        $tests = QuestTest::query()
            ->with(['material.topic', 'results'])
            ->where('is_active', true)
            ->where(fn ($query) => $query
                ->where('project_id', $project->id)
                ->orWhereHas('material.topic', fn ($topicQuery) => $topicQuery
                    ->where('project_id', $project->id)
                    ->where('is_active', true)))
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

        $testEditorItems = $tests
            ->mapWithKeys(fn (QuestTest $test) => [
                $test->id => [
                    'id' => $test->id,
                    'title' => $test->title,
                    'title_translations' => $test->title_translations ?? [],
                    'material_id' => $test->material_id,
                    'test_type' => $test->test_type ?? 'knowledge_check',
                    'passing_score' => $test->passing_score,
                    'quest_data' => $test->quest_data,
                    'quest_data_translations' => $test->quest_data_translations ?? [],
                ],
            ])
            ->all();

        return view('education.tests', [
            'project' => $project,
            'tests' => $tests,
            'attempts' => $attempts,
            'materials' => $materials,
            'testEditorItems' => $testEditorItems,
            'migrationRequired' => false,
        ]);
    }

    public function storeTest(Request $request)
    {
        $project = $this->educationProject();
        $validated = $this->validateTest($request);
        $materialId = $validated['material_id'] ?? null;
        if ($materialId) {
            $material = EducationalMaterial::query()->findOrFail($materialId);
            $this->assertMaterialProject($material, $project);
        }

        $test = QuestTest::create($validated + [
            'project_id' => $project->id,
            'is_active' => true,
        ]);
        $this->syncTestResults($test, $validated['quest_data']);

        return redirect()->route('education.tests')->with('success', 'Тест создан.');
    }

    public function updateTest(Request $request, QuestTest $test)
    {
        $project = $this->educationProject();
        $this->assertTestProject($test, $project);
        $validated = $this->validateTest($request);
        $materialId = $validated['material_id'] ?? null;
        if ($materialId) {
            $material = EducationalMaterial::query()->findOrFail($materialId);
            $this->assertMaterialProject($material, $project);
        }
        $test->update($validated + ['project_id' => $project->id]);
        $this->syncTestResults($test, $validated['quest_data']);

        return redirect()->route('education.tests')->with('success', 'Тест изменён.');
    }

    public function destroyTest(QuestTest $test)
    {
        $project = $this->educationProject();
        $this->assertTestProject($test, $project);
        $test->delete();

        return redirect()->route('education.tests')->with('success', 'Тест удалён.');
    }

    public function knowYourself()
    {
        $project = $this->educationProject();
        if (!$this->educationSchemaReady()) {
            return view('education.know-yourself', [
                'project' => $project,
                'tests' => collect(),
                'attempts' => collect(),
                'testEditorItems' => [],
                'migrationRequired' => true,
            ]);
        }

        $tests = $this->knowYourselfTestsQuery((int) $project->id)
            ->orderBy('id')
            ->get();

        $userId = (int) Auth::id();

        $attempts = QuestTestAttempt::query()
            ->where('user_id', $userId)
            ->whereIn('quest_test_id', $tests->pluck('id'))
            ->latest()
            ->get()
            ->groupBy('quest_test_id');

        $testEditorItems = $tests
            ->mapWithKeys(fn (QuestTest $test) => [
                $test->id => [
                    'id' => $test->id,
                    'title' => $test->title,
                    'title_translations' => $test->title_translations ?? [],
                    'quest_data' => $test->quest_data,
                    'quest_data_translations' => $test->quest_data_translations ?? [],
                ],
            ])
            ->all();

        return view('education.know-yourself', [
            'project' => $project,
            'tests' => $tests,
            'attempts' => $attempts,
            'testEditorItems' => $testEditorItems,
            'migrationRequired' => false,
        ]);
    }

    public function storeKnowYourself(Request $request)
    {
        $project = $this->educationProject();
        $validated = $this->validateKnowYourselfTest($request);

        $test = QuestTest::create($validated + [
            'project_id' => $project->id,
            'material_id' => null,
            'test_type' => 'profile_assessment',
            'passing_score' => 1,
            'is_active' => true,
        ]);
        $this->syncTestResults($test, $validated['quest_data']);

        return redirect()->route('education.know-yourself')->with('success', 'Тест создан.');
    }

    public function updateKnowYourself(Request $request, QuestTest $test)
    {
        $project = $this->educationProject();
        $this->assertTestProject($test, $project);
        abort_unless(($test->test_type ?? '') === 'profile_assessment', 404);

        $validated = $this->validateKnowYourselfTest($request);
        $test->update($validated + [
            'project_id' => $project->id,
            'material_id' => null,
            'test_type' => 'profile_assessment',
            'passing_score' => 1,
        ]);
        $this->syncTestResults($test, $validated['quest_data']);

        return redirect()->route('education.know-yourself')->with('success', 'Тест изменён.');
    }

    public function destroyKnowYourself(QuestTest $test)
    {
        $project = $this->educationProject();
        $this->assertTestProject($test, $project);
        abort_unless(($test->test_type ?? '') === 'profile_assessment', 404);
        $test->delete();

        return redirect()->route('education.know-yourself')->with('success', 'Тест удалён.');
    }

    public function submit(Request $request, QuestTest $test, EducationMaterialResolver $resolver)
    {
        $project = $this->educationProject();
        $test->load('material.topic');
        $this->assertTestProject($test, $project);
        abort_unless($test->is_active, 404);

        $questions = $test->quest_data['questions'] ?? [];
        abort_if(!is_array($questions) || count($questions) === 0, 422, 'В тесте нет вопросов.');

        $validated = $request->validate([
            'answers' => ['required', 'array'],
            'answers.*' => ['nullable', 'integer', 'min:0'],
        ]);

        $result = $this->evaluateTest($test, $validated['answers'], (int) $test->passing_score);
        $score = $result['score'];
        $passed = $result['passed'];
        $userId = (int) Auth::id();

        DB::transaction(function () use ($test, $resolver, $userId, $validated, $score, $passed, $result) {
            $nextMaterial = null;

            if ($test->material) {
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
            }

            QuestTestAttempt::create([
                'user_id' => $userId,
                'quest_test_id' => $test->id,
                'material_id' => $test->material_id,
                'score' => $score,
                'total_score' => $result['total_score'],
                'max_score' => $result['max_score'],
                'passed' => $passed,
                'answers' => $validated['answers'],
                'result_data' => [
                    'scoring_type' => $result['scoring_type'],
                    'profile' => $result['profile'],
                ],
                'next_material_id' => $nextMaterial?->id,
            ]);
        });

        return back()->with(
            $passed ? 'success' : 'warning',
            $result['scoring_type'] === 'points'
                ? "Результат: {$result['total_score']} из {$result['max_score']} баллов. Профиль: {$result['profile']['title']}."
                : ($passed
                    ? "Тест пройден: {$score}%. Открыт следующий уровень."
                    : "Результат {$score}%. Материал автоматически заменён на более подходящую версию.")
        );
    }

    private function educationProject(): Project
    {
        $project = Project::query()->find((int) session('fid'));
        abort_unless($project && strtolower(trim((string) $project->project_type)) === 'education', 403);

        return $project;
    }

    private function educationSchemaReady(): bool
    {
        return Schema::hasTable('education_topics')
            && Schema::hasTable('educational_materials')
            && Schema::hasColumn('educational_materials', 'title')
            && Schema::hasColumn('education_topics', 'title_translations')
            && Schema::hasColumn('education_topics', 'description_translations')
            && Schema::hasColumn('educational_materials', 'title_translations')
            && Schema::hasColumn('educational_materials', 'body_translations')
            && Schema::hasColumn('users', 'education_rating')
            && Schema::hasTable('quests_tests')
            && Schema::hasTable('quest_test_results')
            && Schema::hasColumn('quests_tests', 'project_id')
            && Schema::hasColumn('quests_tests', 'test_type')
            && Schema::hasColumn('quests_tests', 'title_translations')
            && Schema::hasColumn('quests_tests', 'quest_data_translations')
            && Schema::hasTable('education_progress')
            && Schema::hasTable('quest_test_attempts')
            && Schema::hasColumn('quest_test_attempts', 'total_score')
            && Schema::hasColumn('quest_test_attempts', 'max_score')
            && Schema::hasColumn('quest_test_attempts', 'result_data');
    }

    private function validateMaterial(Request $request, ?EducationalMaterial $material = null): array
    {
        $validated = $request->validate([
            'topic_id' => ['required', 'integer'],
            'title' => ['nullable', 'string', 'max:255'],
            'title_translations' => ['nullable'],
            'level' => ['required', 'in:beginner,intermediate,advanced'],
            'content_type' => ['required', 'in:markdown,video_link,interactive_scenario'],
            'body' => ['nullable', 'string'],
            'body_translations' => ['nullable'],
            'version' => ['required', 'string', 'max:32'],
        ]);
        $validated['title_translations'] = $this->translationMap($validated['title_translations'] ?? null);
        $validated['body_translations'] = $this->translationMap($validated['body_translations'] ?? null);
        $validated['title'] = $this->fallbackTranslationValue($validated['title_translations'], $validated['title'] ?? '');
        $validated['body'] = $this->fallbackTranslationValue($validated['body_translations'], $validated['body'] ?? '');
        abort_if($validated['title'] === '', 422, 'Заполните название урока хотя бы на одном языке.');
        abort_if($validated['body'] === '', 422, 'Заполните содержание урока хотя бы на одном языке.');

        if ($validated['title_translations'] === []) {
            $validated['title_translations'] = ['ru' => $validated['title']];
        }
        if ($validated['body_translations'] === []) {
            $validated['body_translations'] = ['ru' => $validated['body']];
        }

        return $validated;
    }

    private function validateTopic(Request $request): array
    {
        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'title_translations' => ['nullable'],
            'description' => ['nullable', 'string'],
            'description_translations' => ['nullable'],
            'position' => ['nullable', 'integer', 'min:0'],
        ]);
        $validated['title_translations'] = $this->translationMap($validated['title_translations'] ?? null);
        $validated['description_translations'] = $this->translationMap($validated['description_translations'] ?? null);
        $validated['title'] = $this->fallbackTranslationValue($validated['title_translations'], $validated['title'] ?? '');
        $validated['description'] = $this->fallbackTranslationValue($validated['description_translations'], $validated['description'] ?? '');
        abort_if($validated['title'] === '', 422, 'Заполните название курса хотя бы на одном языке.');
        if ($validated['title_translations'] === []) {
            $validated['title_translations'] = ['ru' => $validated['title']];
        }

        return $validated;
    }

    private function validateTest(Request $request): array
    {
        $validated = $request->validate([
            'material_id' => ['nullable', 'integer'],
            'test_type' => ['required', 'in:knowledge_check,profile_assessment'],
            'title' => ['nullable', 'string', 'max:255'],
            'title_translations' => ['nullable'],
            'passing_score' => ['required', 'integer', 'min:1', 'max:100'],
            'quest_data' => ['required', 'json'],
            'quest_data_translations' => ['nullable', 'json'],
        ]);
        $validated['quest_data'] = json_decode($validated['quest_data'], true, 512, JSON_THROW_ON_ERROR);
        $validated['title_translations'] = $this->translationMap($validated['title_translations'] ?? null);
        $validated['quest_data_translations'] = $this->questDataTranslationMap($validated['quest_data_translations'] ?? null);
        $validated['title'] = $this->fallbackTranslationValue($validated['title_translations'], $validated['title'] ?? '');
        abort_if($validated['title'] === '', 422, 'Заполните название теста хотя бы на одном языке.');

        if (!isset($validated['quest_data']['questions']) || !is_array($validated['quest_data']['questions'])) {
            abort(422, 'quest_data должен содержать массив questions.');
        }
        foreach ($validated['quest_data_translations'] as $questData) {
            if (!isset($questData['questions']) || !is_array($questData['questions'])) {
                abort(422, 'Переводы quest_data должны содержать массив questions.');
            }
        }
        if ($validated['title_translations'] === []) {
            $validated['title_translations'] = ['ru' => $validated['title']];
        }

        return $validated;
    }

    private function validateKnowYourselfTest(Request $request): array
    {
        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'title_translations' => ['nullable'],
            'quest_data' => ['required', 'json'],
            'quest_data_translations' => ['nullable', 'json'],
        ]);
        $validated['quest_data'] = json_decode($validated['quest_data'], true, 512, JSON_THROW_ON_ERROR);
        $validated['title_translations'] = $this->translationMap($validated['title_translations'] ?? null);
        $validated['quest_data_translations'] = $this->questDataTranslationMap($validated['quest_data_translations'] ?? null);
        $validated['title'] = $this->fallbackTranslationValue($validated['title_translations'], $validated['title'] ?? '');
        abort_if($validated['title'] === '', 422, 'Заполните название теста хотя бы на одном языке.');

        if (!isset($validated['quest_data']['questions']) || !is_array($validated['quest_data']['questions'])) {
            abort(422, 'quest_data должен содержать массив questions.');
        }
        foreach ($validated['quest_data_translations'] as $questData) {
            if (!isset($questData['questions']) || !is_array($questData['questions'])) {
                abort(422, 'Переводы quest_data должны содержать массив questions.');
            }
        }

        $validated['quest_data']['scoring'] = 'points';
        foreach ($validated['quest_data_translations'] as $lang => $questData) {
            $validated['quest_data_translations'][$lang]['scoring'] = 'points';
        }
        if ($validated['title_translations'] === []) {
            $validated['title_translations'] = ['ru' => $validated['title']];
        }

        return $validated;
    }

    private function assertMaterialProject(EducationalMaterial $material, Project $project): void
    {
        $material->loadMissing('topic');
        abort_unless((int) $material->topic?->project_id === (int) $project->id, 404);
    }

    private function assertTopicProject(EducationTopic $topic, Project $project): void
    {
        abort_unless((int) $topic->project_id === (int) $project->id, 404);
    }

    private function assertTestProject(QuestTest $test, Project $project): void
    {
        $test->loadMissing('material.topic');
        $belongsToProject = (int) ($test->project_id ?? 0) === (int) $project->id
            || (int) ($test->material?->topic?->project_id ?? 0) === (int) $project->id;

        abort_unless($belongsToProject, 404);
    }

    private function firstPublicTest(int $projectId): QuestTest
    {
        $query = QuestTest::query()
            ->with(['material.topic', 'results'])
            ->where('is_active', true)
            ->where(fn ($query) => $query
                ->where('project_id', $projectId)
                ->orWhereHas('material.topic', fn ($topicQuery) => $topicQuery
                    ->where('project_id', $projectId)
                    ->where('is_active', true)));

        $featured = (clone $query)
            ->where('quest_data->public_featured', true)
            ->orderBy('id')
            ->first();

        return $featured ?? $query->orderBy('id')->firstOrFail();
    }

    private function knowYourselfTestsQuery(int $projectId)
    {
        return QuestTest::query()
            ->with(['results'])
            ->where('is_active', true)
            ->where('project_id', $projectId)
            ->where('test_type', 'profile_assessment');
    }

    private function publicTestPayload(QuestTest $test, string $lang = 'ru'): array
    {
        $questData = $this->localizedQuestData($test, $lang);

        return [
            'id' => $test->id,
            'title' => $this->localizedText($test->title_translations, $lang, (string) $test->title),
            'passing_score' => $test->passing_score,
            'intro' => (string) ($questData['intro'] ?? ''),
            'auth_required' => ($questData['auth_required'] ?? '') === 'google' ? 'google' : 'none',
            'rating_award' => (int) ($test->quest_data['rating'] ?? 0),
            'topic' => $test->material?->topic
                ? $this->localizedText($test->material->topic->title_translations, $lang, (string) $test->material->topic->title)
                : 'Диагностика',
            'level' => $test->material?->level ?? (string) ($test->test_type ?? 'profile_assessment'),
            'questions' => collect($questData['questions'] ?? [])
                ->values()
                ->map(fn ($question, $index) => [
                    'id' => $index,
                    'text' => (string) ($question['text'] ?? ''),
                    'options' => $this->publicQuestionOptions($question['options'] ?? []),
                ]),
        ];
    }

    private function publicQuestionOptions(array $options): array
    {
        return collect($options)
            ->values()
            ->map(fn ($option) => is_array($option)
                ? (string) ($option['text'] ?? $option['label'] ?? '')
                : (string) $option)
            ->all();
    }

    private function evaluateTest(QuestTest $test, array $answers, int $passingScore, string $lang = 'ru'): array
    {
        $questData = $test->quest_data ?? [];
        $questions = array_values($questData['questions'] ?? []);
        $usesPointScoring = collect($questions)->contains(fn ($question) => collect($question['options'] ?? [])
            ->contains(fn ($option) => is_array($option) && array_key_exists('score', $option)));

        if ($usesPointScoring) {
            $totalScore = 0;
            $maxScore = 0;

            foreach ($questions as $index => $question) {
                $options = array_values($question['options'] ?? []);
                $answerIndex = (int) ($answers[$index] ?? -1);

                $maxScore += (collect($options)
                    ->map(fn ($option) => is_array($option) ? (int) ($option['score'] ?? 0) : 0)
                    ->max() ?? 0);

                $selected = $options[$answerIndex] ?? null;
                $totalScore += is_array($selected) ? (int) ($selected['score'] ?? 0) : 0;
            }

            $profile = $this->profileForScore($test, $totalScore, $lang);

            return [
                'scoring_type' => 'points',
                'score' => $maxScore > 0 ? (int) round($totalScore * 100 / $maxScore) : 0,
                'passed' => true,
                'correct_answers' => null,
                'total_score' => $totalScore,
                'max_score' => $maxScore,
                'profile' => $profile,
            ];
        }

        $correct = 0;
        foreach ($questions as $index => $question) {
            if ((int) ($answers[$index] ?? -1) === (int) ($question['correct_index'] ?? -2)) {
                $correct++;
            }
        }

        $score = count($questions) > 0 ? (int) round($correct * 100 / count($questions)) : 0;

        return [
            'scoring_type' => 'correct_answers',
            'score' => $score,
            'passed' => $score >= $passingScore,
            'correct_answers' => $correct,
            'total_score' => $correct,
            'max_score' => count($questions),
            'profile' => null,
        ];
    }

    private function profileForScore(QuestTest $test, int $score, string $lang = 'ru'): array
    {
        $localizedResult = collect($this->localizedQuestData($test, $lang)['results'] ?? [])
            ->first(fn ($result) => is_array($result)
                && (int) ($result['min'] ?? 0) <= $score
                && (int) ($result['max'] ?? 0) >= $score);

        if (is_array($localizedResult)) {
            return [
                'title' => (string) ($localizedResult['title'] ?? 'Профиль определён'),
                'subtitle' => (string) ($localizedResult['subtitle'] ?? ''),
                'description' => (string) ($localizedResult['description'] ?? ''),
                'recommendation' => (string) ($localizedResult['recommendation'] ?? ''),
            ];
        }

        $result = QuestTestResult::query()
            ->where('quest_test_id', $test->id)
            ->where('min_score', '<=', $score)
            ->where('max_score', '>=', $score)
            ->orderBy('sort_order')
            ->orderBy('min_score')
            ->first();

        if ($result) {
            return [
                'title' => (string) $result->title,
                'subtitle' => (string) ($result->subtitle ?? ''),
                'description' => (string) ($result->description ?? ''),
                'recommendation' => (string) ($result->recommendation ?? ''),
            ];
        }

        return [
            'title' => 'Профиль определён',
            'subtitle' => '',
            'description' => '',
            'recommendation' => '',
        ];
    }

    private function language(Request $request): string
    {
        $lang = strtolower((string) $request->input('lang', 'ru'));

        return in_array($lang, ['ua', 'ru', 'en'], true) ? $lang : 'ru';
    }

    private function localizedText(?array $translations, string $lang, string $fallback = ''): string
    {
        $translations = $translations ?? [];
        $value = trim((string) ($translations[$lang] ?? ''));
        if ($value !== '') {
            return $value;
        }

        foreach (['ru', 'ua', 'en'] as $fallbackLang) {
            $value = trim((string) ($translations[$fallbackLang] ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        return $fallback;
    }

    private function localizedQuestData(QuestTest $test, string $lang): array
    {
        $translations = $test->quest_data_translations ?? [];
        $localized = is_array($translations[$lang] ?? null) ? $translations[$lang] : null;

        return $localized ?: ($test->quest_data ?? []);
    }

    private function translationMap(mixed $value): array
    {
        if (!$value) {
            return [];
        }

        $decoded = is_array($value) ? $value : json_decode((string) $value, true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($decoded)) {
            return [];
        }

        $translations = [];
        foreach (['ua', 'ru', 'en'] as $lang) {
            $value = trim((string) ($decoded[$lang] ?? ''));
            if ($value !== '') {
                $translations[$lang] = $value;
            }
        }

        return $translations;
    }

    private function questDataTranslationMap(?string $json): array
    {
        if (!$json) {
            return [];
        }

        $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($decoded)) {
            return [];
        }

        $translations = [];
        foreach (['ua', 'ru', 'en'] as $lang) {
            if (isset($decoded[$lang]) && is_array($decoded[$lang])) {
                $translations[$lang] = $decoded[$lang];
            }
        }

        return $translations;
    }

    private function fallbackTranslationValue(array $translations, string $fallback = ''): string
    {
        $fallback = trim($fallback);
        if ($fallback !== '') {
            return $fallback;
        }

        foreach (['ru', 'ua', 'en'] as $lang) {
            $value = trim((string) ($translations[$lang] ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }

    private function syncTestResults(QuestTest $test, array $questData): void
    {
        $results = $questData['results'] ?? [];
        if (!is_array($results)) {
            $results = [];
        }

        DB::transaction(function () use ($test, $results) {
            QuestTestResult::query()->where('quest_test_id', $test->id)->delete();

            foreach (array_values($results) as $index => $result) {
                if (!is_array($result)) {
                    continue;
                }

                QuestTestResult::query()->create([
                    'quest_test_id' => $test->id,
                    'min_score' => max(0, (int) ($result['min'] ?? 0)),
                    'max_score' => max(0, (int) ($result['max'] ?? 0)),
                    'title' => (string) ($result['title'] ?? 'Профиль определён'),
                    'subtitle' => $result['subtitle'] ?? null,
                    'description' => $result['description'] ?? null,
                    'recommendation' => $result['recommendation'] ?? null,
                    'sort_order' => $index,
                ]);
            }
        });
    }
}
