<?php

namespace App\Http\Controllers;

use App\Models\EducationCategory;
use App\Models\EducationProgress;
use App\Models\EducationTopic;
use App\Models\EducationalMaterial;
use App\Models\QuestTest;
use App\Models\QuestTestAttempt;
use App\Models\QuestTestResult;
use App\Models\Project;
use App\Support\MediaUrl;
use App\Services\AcademyCoursePaymentService;
use App\Services\EducationMaterialResolver;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class EducationController extends Controller
{
    private const EDUCATION_LANGUAGES = ['ru', 'ua', 'en', 'es', 'fr'];
    private const MATERIAL_IMAGES_DIRECTORY = 'files/education/materials';
    private const MATERIAL_IMAGES_METADATA = 'files/education/materials/.metadata.json';
    private const MATERIAL_IMAGES_PUBLIC_BASE_URL = 'https://av8capital.space';

    public function ensureCourseOrder(Request $request, AcademyCoursePaymentService $payments): JsonResponse
    {
        $validated = $request->validate([
            'course_id' => ['required', 'integer', 'min:1'],
        ]);

        $course = EducationTopic::query()
            ->where('project_id', 36)
            ->where('is_active', true)
            ->findOrFail((int) $validated['course_id']);

        return response()->json([
            'order' => $payments->ensureOrder($request->user(), $course),
        ]);
    }

    public function recordCoursePayment(Request $request, AcademyCoursePaymentService $payments): JsonResponse
    {
        $validated = $request->validate([
            'course_id' => ['required', 'integer', 'min:1'],
            'digest' => ['required', 'string', 'max:64', 'regex:/^[1-9A-HJ-NP-Za-km-z]+$/'],
            'wallet_address' => ['required', 'string', 'max:80', 'regex:/^0x[a-fA-F0-9]{1,64}$/'],
        ]);

        $course = EducationTopic::query()
            ->where('project_id', 36)
            ->where('is_active', true)
            ->findOrFail((int) $validated['course_id']);

        return response()->json([
            'payment' => $payments->record(
                $request->user(),
                $course,
                $validated['digest'],
                $validated['wallet_address']
            ),
        ]);
    }

    public function profile(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'fid' => ['nullable', 'integer', 'min:1'],
            'lang' => ['nullable', 'in:ua,ru,en,es,fr'],
        ]);

        $user = $request->user();
        abort_unless($user, 401);

        $projectId = (int) ($validated['fid'] ?? 36);
        $lang = $this->language($request);
        $educationRating = Schema::hasColumn('users', 'education_rating')
            ? (int) DB::table('users')->where('id', $user->id)->value('education_rating')
            : 0;

        $testAttempts = collect();
        if (Schema::hasTable('quest_test_attempts') && Schema::hasTable('quests_tests')) {
            $testAttempts = QuestTestAttempt::query()
                ->with('test.category')
                ->where('user_id', $user->id)
                ->latest()
                ->limit(100)
                ->get()
                ->map(function (QuestTestAttempt $attempt) use ($lang): array {
                    $test = $attempt->test;
                    $profile = data_get($attempt->result_data, 'profile');

                    return [
                        'id' => (int) $attempt->id,
                        'test_id' => (int) ($attempt->quest_test_id ?? 0),
                        'title' => $test
                            ? $this->localizedText($test->title_translations, $lang, (string) $test->title)
                            : 'Тест',
                        'category' => $test?->category
                            ? $this->localizedText($test->category->title_translations, $lang, (string) $test->category->title)
                            : null,
                        'score' => (int) ($attempt->score ?? 0),
                        'total_score' => $attempt->total_score !== null ? (int) $attempt->total_score : null,
                        'max_score' => $attempt->max_score !== null ? (int) $attempt->max_score : null,
                        'passed' => (bool) $attempt->passed,
                        'profile' => is_array($profile) ? $profile : null,
                        'rating_awarded' => (int) data_get($attempt->result_data, 'rating_awarded', 0),
                        'created_at' => optional($attempt->created_at)->toDateTimeString(),
                    ];
                });
        }

        $courseOrders = collect();
        if (Schema::hasTable('document')) {
            $courseOrders = DB::table('document')
                ->where('firma', (string) $projectId)
                ->where('type', 'ZOUT')
                ->where('client1', (string) $user->id)
                ->orderByDesc('dt')
                ->orderByDesc('id')
                ->limit(100)
                ->get()
                ->map(function ($order): array {
                    return [
                        'id' => (int) $order->id,
                        'num' => (string) ($order->num ?? ''),
                        'summa' => (string) ($order->summa ?? '0'),
                        'status' => (string) ($order->status ?? ''),
                        'data' => (string) ($order->data ?? ''),
                        'dt' => (int) ($order->dt ?? 0),
                        'close' => (bool) ($order->close ?? false),
                        'typeproduct' => (string) ($order->typeproduct ?? ''),
                    ];
                });
        }

        $coursePayments = collect();
        if (Schema::hasTable('z_document')) {
            $coursePayments = DB::table('z_document')
                ->where('firma', (string) $projectId)
                ->where('type', 'PO')
                ->where('client1', (string) $user->id)
                ->orderByDesc('dt')
                ->orderByDesc('id')
                ->limit(100)
                ->get()
                ->map(function ($payment): array {
                    return [
                        'id' => (int) $payment->id,
                        'num' => (string) ($payment->num ?? ''),
                        'docid' => (int) ($payment->docid ?? 0),
                        'summa' => (string) ($payment->summa ?? '0'),
                        'status' => (string) ($payment->status ?? ''),
                        'data' => (string) ($payment->data ?? ''),
                        'dt' => (int) ($payment->dt ?? 0),
                        'provodka' => (bool) ($payment->provodka ?? false),
                        'typeproduct' => (string) ($payment->typeproduct ?? ''),
                    ];
                });
        }

        $latestProfileAttempt = $testAttempts->first(fn (array $attempt): bool => is_array($attempt['profile']));

        return response()->json([
            'profile' => [
                'education_rating' => $educationRating,
                'latest_profile' => $latestProfileAttempt['profile'] ?? null,
                'tests' => $testAttempts->values(),
                'course_orders' => $courseOrders->values(),
                'course_payments' => $coursePayments->values(),
            ],
        ]);
    }

    public function publicFirstTest(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'fid' => ['required', 'integer', 'min:1'],
            'lang' => ['nullable', 'in:ua,ru,en,es,fr'],
        ]);
        abort_unless($this->educationSchemaReady(), 503, 'Таблицы образовательного модуля ещё не созданы.');
        $lang = $this->language($request);

        $test = $this->firstPublicTest((int) $validated['fid']);
        $questData = $this->localizedQuestData($test, $lang);

        return response()->json([
            'test' => [
                'id' => $test->id,
                'category_id' => $test->category_id,
                'category_title' => $test->category
                    ? $this->localizedText($test->category->title_translations, $lang, (string) $test->category->title)
                    : null,
                'category_position' => (int) ($test->category?->position ?? 2147483647),
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
                        'image_url' => $this->publicQuestionImage($test, $question, $index),
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
            'lang' => ['nullable', 'in:ua,ru,en,es,fr'],
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
            'answers' => ['required', 'array'],
            'answers.*' => ['required', 'integer', 'min:0'],
        ]);
        abort_unless($this->educationSchemaReady(), 503, 'Таблицы образовательного модуля ещё не созданы.');

        $test = $this->knowYourselfTestsQuery((int) $validated['fid'])
            ->findOrFail((int) $validated['test_id']);
        $questions = array_values($test->quest_data['questions'] ?? []);
        abort_if(count($questions) === 0, 422, 'В тесте нет вопросов.');
        $answers = array_values($validated['answers']);
        abort_if(count($answers) !== count($questions), 422, 'Необходимо ответить на все вопросы теста.');

        $result = $this->evaluateTest($test, $answers, (int) $test->passing_score, $this->language($request));
        $ratingAward = max(0, (int) ($test->quest_data['rating'] ?? 0));
        $user = $request->user();
        abort_unless($user, 401);
        $ratingAwarded = 0;
        $ratingAlreadyAwarded = false;
        $educationRating = 0;

        DB::transaction(function () use ($test, $user, $answers, $result, $ratingAward, &$ratingAwarded, &$ratingAlreadyAwarded, &$educationRating) {
            $lockedUser = DB::table('users')
                ->where('id', $user->id)
                ->lockForUpdate()
                ->first();
            abort_unless($lockedUser, 401);

            $ratingAlreadyAwarded = QuestTestAttempt::query()
                ->where('user_id', $user->id)
                ->where('quest_test_id', $test->id)
                ->where('passed', true)
                ->get()
                ->contains(fn (QuestTestAttempt $attempt) => (int) data_get($attempt->result_data, 'rating_awarded', 0) > 0);

            if ($result['passed'] && $ratingAward > 0 && !$ratingAlreadyAwarded) {
                DB::table('users')
                    ->where('id', $user->id)
                    ->increment('education_rating', $ratingAward);
                $ratingAwarded = $ratingAward;
            }

            QuestTestAttempt::create([
                'user_id' => $user->id,
                'quest_test_id' => $test->id,
                'material_id' => null,
                'score' => $result['score'],
                'total_score' => $result['total_score'],
                'max_score' => $result['max_score'],
                'passed' => $result['passed'],
                'answers' => $answers,
                'result_data' => [
                    'scoring_type' => $result['scoring_type'],
                    'profile' => $result['profile'],
                    'rating_award' => $ratingAward,
                    'rating_awarded' => $ratingAwarded,
                    'rating_already_awarded' => $ratingAlreadyAwarded,
                ],
                'next_material_id' => null,
            ]);

            $educationRating = (int) DB::table('users')
                ->where('id', $user->id)
                ->value('education_rating');
        });

        return response()->json([
            'passed' => $result['passed'],
            'rating_award' => $ratingAward,
            'rating_awarded' => $ratingAwarded,
            'rating_already_awarded' => $ratingAlreadyAwarded,
            'education_rating' => $educationRating,
        ]);
    }

    public function publicCourse(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'fid' => ['required', 'integer', 'min:1'],
            'lang' => ['nullable', 'in:ua,ru,en,es,fr'],
        ]);
        abort_unless($this->educationSchemaReady(), 503, 'Таблицы образовательного модуля ещё не созданы.');
        $lang = $this->language($request);
        $user = Auth::guard('sanctum')->user();
        $progressByTopic = $user
            ? DB::table('user_course_progress')
                ->where('user_id', $user->id)
                ->get()
                ->keyBy('education_topic_id')
            : collect();
        $paidCourseIds = $user
            ? $this->paidEducationCourseIds((int) $validated['fid'], (int) $user->id)
            : collect();

        $topics = EducationTopic::query()
            ->where('project_id', (int) $validated['fid'])
            ->where('is_active', true)
            ->with(['category', 'materials' => fn ($query) => $query
                ->where('is_active', true)
                ->with(['tests' => fn ($testQuery) => $testQuery
                    ->where('is_active', true)
                    ->with('results')
                    ->orderBy('id')])
                ->orderBy('rating')
                ->orderBy('level')
                ->orderByRaw('CAST(version AS DECIMAL(10,2))')])
            ->orderBy('position')
            ->orderBy('id')
            ->get()
            ->map(function (EducationTopic $topic) use ($lang, $progressByTopic, $paidCourseIds) {
                $progress = $progressByTopic->get($topic->id);
                $topicRating = (int) $topic->position;
                $requiresPayment = (float) ($topic->cost_av8 ?? 0) > 0;
                $isPaid = !$requiresPayment || $paidCourseIds->contains((int) $topic->id);

                return [
                    'id' => $topic->id,
                    'category_id' => $topic->category_id,
                    'category_title' => $topic->category
                        ? $this->localizedText($topic->category->title_translations, $lang, (string) $topic->category->title)
                        : null,
                    'category_position' => (int) ($topic->category?->position ?? 2147483647),
                    'title' => $this->localizedText($topic->title_translations, $lang, (string) $topic->title),
                    'description' => $this->localizedText($topic->description_translations, $lang, (string) ($topic->description ?? '')),
                    'rating' => $topicRating,
                    'local_rating' => $progress ? max($topicRating, (int) $progress->local_rating) : null,
                    'completed_at' => $progress?->completed_at,
                    'cost_av8' => (string) ($topic->cost_av8 ?? '0'),
                    'is_paid' => $isPaid,
                    'materials' => $topic->materials
                        ->map(fn (EducationalMaterial $material) => [
                            'id' => $material->id,
                            'title' => $this->localizedText(
                                $material->title_translations,
                                $lang,
                                $material->title ?: $this->localizedText($topic->title_translations, $lang, (string) $topic->title)
                            ),
                            'rating' => (int) ($material->rating ?? $topic->position ?? 0),
                            'level' => $material->level,
                            'content_type' => $material->content_type,
                            'version' => $material->version,
                            'body' => $isPaid
                                ? $this->localizedText($material->body_translations, $lang, (string) $material->body)
                                : '',
                            'tests' => $isPaid ? $material->tests
                                ->map(fn (QuestTest $test) => $this->publicTestPayload($test, $lang))
                                ->values() : collect(),
                        ])
                        ->values(),
                ];
            })
            ->values();

        return response()->json(['topics' => $topics]);
    }

    private function paidEducationCourseIds(int $projectId, int $userId)
    {
        if (!Schema::hasTable('z_document')) {
            return collect();
        }

        $payments = DB::table('z_document as po')
            ->join('document as course_order', function ($join) {
                $join->on('course_order.id', '=', 'po.docid')
                    ->on('course_order.firma', '=', 'po.firma');
            })
            ->where('po.firma', (string) $projectId)
            ->where('po.type', 'PO')
            ->where('po.client1', (string) $userId)
            ->where('po.provodka', 1)
            ->where('course_order.type', 'ZOUT')
            ->where('course_order.client1', (string) $userId)
            ->get(['po.docid', 'po.numdoc', 'po.typeproduct']);

        $directCourseIds = $payments
            ->filter(fn ($payment) => strtolower(trim((string) ($payment->typeproduct ?? ''))) === 'course')
            ->pluck('numdoc')
            ->map(fn ($id) => (int) $id)
            ->filter();

        if (!Schema::hasTable('z_body')) {
            return $directCourseIds->unique()->values();
        }

        $orderIds = $payments->pluck('docid')->map(fn ($id) => (string) $id)->filter()->unique();
        $orderedCourseIds = $orderIds->isEmpty()
            ? collect()
            : DB::table('z_body')
                ->where('firma', (string) $projectId)
                ->whereIn('docid', $orderIds->all())
                ->pluck('pnum')
                ->map(fn ($id) => (int) $id)
                ->filter();

        return $directCourseIds
            ->merge($orderedCourseIds)
            ->unique()
            ->values();
    }

    private function assertCoursePaymentAccess(EducationTopic $course): void
    {
        if ((float) ($course->cost_av8 ?? 0) <= 0) {
            return;
        }

        $user = Auth::guard('sanctum')->user();
        abort_unless(
            $user && $this->paidEducationCourseIds((int) $course->project_id, (int) $user->id)->contains((int) $course->id),
            403,
            'Курс не оплачен.'
        );
    }

    public function publicCourseMaterialTests(Request $request, EducationalMaterial $material): JsonResponse
    {
        $validated = $request->validate([
            'fid' => ['required', 'integer', 'min:1'],
            'lang' => ['nullable', 'in:ua,ru,en,es,fr'],
        ]);
        abort_unless($this->educationSchemaReady(), 503, 'Таблицы образовательного модуля ещё не созданы.');

        $material->loadMissing('topic');
        abort_unless(
            $material->is_active
            && $material->topic
            && $material->topic->is_active
            && (int) $material->topic->project_id === (int) $validated['fid'],
            404
        );
        $this->assertCoursePaymentAccess($material->topic);

        $lang = $this->language($request);
        $tests = $material->tests()
            ->where('is_active', true)
            ->with('results')
            ->orderBy('id')
            ->get()
            ->map(fn (QuestTest $test) => $this->publicTestPayload($test, $lang))
            ->values();

        return response()->json(['tests' => $tests]);
    }

    public function publicSubmitCourseTest(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'fid' => ['required', 'integer', 'min:1'],
            'test_id' => ['required', 'integer', 'min:1'],
            'lang' => ['nullable', 'in:ua,ru,en,es,fr'],
            'answers' => ['required', 'array'],
            'answers.*' => ['required', 'integer', 'min:0'],
        ]);
        abort_unless($this->educationSchemaReady(), 503, 'Таблицы образовательного модуля ещё не созданы.');

        $test = $this->courseTestsQuery((int) $validated['fid'])
            ->findOrFail((int) $validated['test_id']);
        $this->assertCoursePaymentAccess($test->material->topic);

        $questions = array_values($test->quest_data['questions'] ?? []);
        abort_if(count($questions) === 0, 422, 'В тесте нет вопросов.');
        $result = $this->evaluateTest($test, $validated['answers'], (int) $test->passing_score, $this->language($request));
        $ratingAward = max(0, (int) ($test->quest_data['rating'] ?? 0));
        $ratingAwarded = 0;
        $educationRating = null;
        $courseLocalRating = null;
        $courseCompletedAt = null;
        $user = Auth::guard('sanctum')->user();

        if ($user) {
            DB::transaction(function () use ($test, $user, $validated, $result, $ratingAward, &$ratingAwarded, &$educationRating, &$courseLocalRating, &$courseCompletedAt) {
                $priorPassedAttempts = QuestTestAttempt::query()
                    ->where('user_id', $user->id)
                    ->where('quest_test_id', $test->id)
                    ->where('passed', true)
                    ->get();
                $ratingAlreadyAwarded = $priorPassedAttempts
                    ->contains(fn (QuestTestAttempt $attempt) => (int) data_get($attempt->result_data, 'rating_awarded', 0) > 0);
                $localRatingAlreadyAwarded = $priorPassedAttempts
                    ->contains(fn (QuestTestAttempt $attempt) => (int) data_get($attempt->result_data, 'local_rating_awarded', 0) > 0);

                if ($result['passed'] && $ratingAward > 0 && !$ratingAlreadyAwarded) {
                    DB::table('users')
                        ->where('id', $user->id)
                        ->increment('education_rating', $ratingAward);
                    $ratingAwarded = $ratingAward;
                }

                $topic = $test->material?->topic;
                $localRatingAwarded = 0;
                if ($topic) {
                    $progress = DB::table('user_course_progress')
                        ->where('user_id', $user->id)
                        ->where('education_topic_id', $topic->id)
                        ->lockForUpdate()
                        ->first();
                    $baseRating = (int) $topic->position;
                    $currentLocalRating = max($baseRating, (int) ($progress->local_rating ?? $baseRating));
                    $localRatingAwarded = 0;

                    if ($result['passed'] && $ratingAward > 0 && !$localRatingAlreadyAwarded) {
                        $currentLocalRating += $ratingAward;
                        $localRatingAwarded = $ratingAward;
                    }

                    $hasLockedLessons = EducationalMaterial::query()
                        ->where('topic_id', $topic->id)
                        ->where('is_active', true)
                        ->where('rating', '>', $currentLocalRating)
                        ->exists();
                    $completedAt = !$hasLockedLessons
                        ? ($progress?->completed_at ?? now())
                        : null;

                    DB::table('user_course_progress')->updateOrInsert(
                        ['user_id' => $user->id, 'education_topic_id' => $topic->id],
                        [
                            'local_rating' => $currentLocalRating,
                            'completed_at' => $completedAt,
                            'created_at' => $progress?->created_at ?? now(),
                            'updated_at' => now(),
                        ]
                    );

                    $courseLocalRating = $currentLocalRating;
                    $courseCompletedAt = $completedAt;
                }

                QuestTestAttempt::create([
                    'user_id' => $user->id,
                    'quest_test_id' => $test->id,
                    'material_id' => $test->material_id,
                    'score' => $result['score'],
                    'total_score' => $result['total_score'],
                    'max_score' => $result['max_score'],
                    'passed' => $result['passed'],
                    'answers' => $validated['answers'],
                    'result_data' => [
                        'scoring_type' => $result['scoring_type'],
                        'profile' => $result['profile'],
                        'rating_award' => $ratingAward,
                        'rating_awarded' => $ratingAwarded,
                        'local_rating_awarded' => $localRatingAwarded ?? 0,
                        'rating_already_awarded' => $ratingAlreadyAwarded,
                        'local_rating_already_awarded' => $localRatingAlreadyAwarded,
                    ],
                    'next_material_id' => null,
                ]);

                $educationRating = (int) DB::table('users')->where('id', $user->id)->value('education_rating');
            });
        }

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
            'rating_award' => $ratingAward,
            'rating_awarded' => $ratingAwarded,
            'education_rating' => $educationRating,
            'course_local_rating' => $courseLocalRating,
            'course_completed_at' => $courseCompletedAt,
        ]);
    }

    public function publicKnowYourselfTests(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'fid' => ['required', 'integer', 'min:1'],
            'lang' => ['nullable', 'in:ua,ru,en,es,fr'],
        ]);
        abort_unless($this->educationSchemaReady(), 503, 'Таблицы образовательного модуля ещё не созданы.');
        $lang = $this->language($request);

        $tests = $this->knowYourselfTestsQuery((int) $validated['fid'])
            ->with('category')
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
            'lang' => ['nullable', 'in:ua,ru,en,es,fr'],
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

    public function course(Request $request, EducationMaterialResolver $resolver)
    {
        $project = $this->educationProject();
        if (!$this->educationSchemaReady()) {
            return view('education.course', [
                'project' => $project,
                'topics' => collect(),
                'courseList' => collect(),
                'materialEditorItems' => [],
                'topicEditorItems' => [],
                'migrationRequired' => true,
                'categories' => collect(),
                'categoryGroups' => collect(),
                'categoryEditorItems' => [],
            ]);
        }

        $payload = $this->educationCoursePayload($project, $resolver);
        $selectedCategoryId = trim((string) $request->query('category', ''));
        $courseListQuery = EducationTopic::query()
            ->where('project_id', $project->id)
            ->where('is_active', true)
            ->with(['category', 'materials' => fn ($query) => $query
                ->where('is_active', true)
                ->orderBy('rating')
                ->orderBy('level')
                ->orderByRaw('CAST(version AS DECIMAL(10,2))')]);

        if ($selectedCategoryId !== '') {
            if ($selectedCategoryId === 'none') {
                $courseListQuery->whereNull('category_id');
            } elseif (ctype_digit($selectedCategoryId)) {
                $courseListQuery->where('category_id', (int) $selectedCategoryId);
            }

            $courseListQuery->orderBy('position')->orderBy('id');
        } else {
            $courseListQuery->orderByDesc('created_at')->orderByDesc('id');
        }

        return view('education.course', $payload + [
            'project' => $project,
            'courseList' => $courseListQuery->paginate(5)->withQueryString(),
            'selectedCategoryId' => $selectedCategoryId,
            'courseDetailTopic' => null,
            'migrationRequired' => false,
        ]);
    }

    public function courseShow(int $topic, EducationMaterialResolver $resolver)
    {
        $project = $this->educationProject();
        abort_unless($this->educationSchemaReady(), 503, 'Таблицы образовательного модуля ещё не созданы.');

        $payload = $this->educationCoursePayload($project, $resolver);
        $courseDetailTopic = $payload['topics']->firstWhere('id', $topic);
        abort_unless($courseDetailTopic, 404);

        return view('education.course', $payload + [
            'project' => $project,
            'courseList' => collect(),
            'selectedCategoryId' => $courseDetailTopic->category_id ? (string) $courseDetailTopic->category_id : 'none',
            'courseDetailTopic' => $courseDetailTopic,
            'migrationRequired' => false,
        ]);
    }

    private function educationCoursePayload(Project $project, EducationMaterialResolver $resolver): array
    {
        $userId = (int) Auth::id();

        $topics = EducationTopic::query()
            ->where('project_id', $project->id)
            ->where('is_active', true)
            ->with(['category', 'materials' => fn ($query) => $query
                ->where('is_active', true)
                ->orderBy('rating')
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
                'rating' => (int) ($material->rating ?? $topic->position ?? 0),
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
                    'cost_av8' => (string) ($topic->cost_av8 ?? '0'),
                    'category_id' => $topic->category_id,
                ],
            ])
            ->all();

        $categories = $this->educationCategories((int) $project->id, EducationCategory::CONTEXT_COURSE);

        return [
            'topics' => $topics,
            'materialEditorItems' => $materialEditorItems,
            'topicEditorItems' => $topicEditorItems,
            'categories' => $categories,
            'categoryGroups' => $this->educationCategoryGroups($categories, $topics, 'topics'),
            'categoryEditorItems' => $this->categoryEditorItems($categories),
        ];
    }

    public function materials()
    {
        $this->educationProject();

        return view('education.materials', [
            'materials' => $this->educationMaterialImages(),
            'storageDirectory' => self::MATERIAL_IMAGES_DIRECTORY,
        ]);
    }

    public function storeMaterialImage(Request $request)
    {
        $this->educationProject();

        $validated = $request->validate([
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'],
            'alt' => ['nullable', 'string', 'max:255'],
        ]);

        $uploadedFile = $validated['image'];
        $originalName = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
        $safeName = Str::slug($originalName) ?: 'material';
        $extension = strtolower($uploadedFile->getClientOriginalExtension() ?: $uploadedFile->extension() ?: 'jpg');
        $filename = $safeName . '_' . now()->format('YmdHis') . '_' . Str::random(8) . '.' . $extension;

        $uploadedFile->storeAs(self::MATERIAL_IMAGES_DIRECTORY, $filename, 'public');

        $alt = trim((string) ($validated['alt'] ?? ''));
        if ($alt !== '') {
            $metadata = $this->educationMaterialImagesMetadata();
            $metadata[$filename] = ['alt' => $alt];
            $this->saveEducationMaterialImagesMetadata($metadata);
        }

        return redirect()->route('education.material-files.index')->with('success', 'Фото загружено.');
    }

    public function destroyMaterialImage(Request $request)
    {
        $this->educationProject();

        $validated = $request->validate([
            'file' => ['required', 'string', 'max:255'],
        ]);

        $filename = basename($validated['file']);
        $path = self::MATERIAL_IMAGES_DIRECTORY . '/' . $filename;
        abort_if($filename === '' || !Storage::disk('public')->exists($path), 404);

        Storage::disk('public')->delete($path);

        $metadata = $this->educationMaterialImagesMetadata();
        if (array_key_exists($filename, $metadata)) {
            unset($metadata[$filename]);
            $this->saveEducationMaterialImagesMetadata($metadata);
        }

        return redirect()->route('education.material-files.index')->with('success', 'Фото удалено.');
    }

    public function storeMaterial(Request $request)
    {
        $project = $this->educationProject();
        $validated = $this->validateMaterial($request);

        $categoryId = DB::transaction(function () use ($validated, $project) {
            $topic = EducationTopic::query()
                ->where('project_id', $project->id)
                ->whereKey($validated['topic_id'])
                ->firstOrFail();

            EducationalMaterial::create([
                'topic_id' => $topic->id,
                'title' => $validated['title'],
                'title_translations' => $validated['title_translations'],
                'level' => $validated['level'],
                'rating' => $validated['rating'],
                'content_type' => $validated['content_type'],
                'body' => $validated['body'],
                'body_translations' => $validated['body_translations'],
                'version' => $validated['version'],
                'is_active' => true,
            ]);

            return $topic->category_id;
        });

        return redirect()->route('education.course')
            ->with('success', 'Материал курса создан.')
            ->with('open_category_id', $this->educationOpenCategoryId($categoryId));
    }

    public function storeTopic(Request $request)
    {
        $project = $this->educationProject();
        $validated = $this->validateTopic($request);

        $topic = EducationTopic::create([
            'project_id' => $project->id,
            'category_id' => $validated['category_id'],
            'title' => $validated['title'],
            'title_translations' => $validated['title_translations'],
            'description' => $validated['description'] ?? null,
            'description_translations' => $validated['description_translations'],
            'position' => $validated['position'] ?? 0,
            'cost_av8' => $validated['cost_av8'],
            'is_active' => true,
        ]);

        return redirect()->route('education.course')
            ->with('success', 'Курс создан.')
            ->with('open_category_id', $this->educationOpenCategoryId($topic->category_id));
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
            'cost_av8' => $validated['cost_av8'],
            'category_id' => $validated['category_id'],
        ]);

        return redirect()->route('education.course')
            ->with('success', 'Курс изменён.')
            ->with('open_category_id', $this->educationOpenCategoryId($topic->category_id));
    }

    public function destroyTopic(EducationTopic $topic)
    {
        $project = $this->educationProject();
        $this->assertTopicProject($topic, $project);
        $categoryId = $topic->category_id;
        $topic->delete();

        return redirect()->route('education.course')
            ->with('success', 'Курс удалён.')
            ->with('open_category_id', $this->educationOpenCategoryId($categoryId));
    }

    public function updateMaterial(Request $request, EducationalMaterial $material)
    {
        $project = $this->educationProject();
        $this->assertMaterialProject($material, $project);
        $validated = $this->validateMaterial($request, $material);

        $categoryId = DB::transaction(function () use ($validated, $material, $project) {
            $topic = EducationTopic::query()
                ->where('project_id', $project->id)
                ->whereKey($validated['topic_id'])
                ->firstOrFail();

            $material->update([
                'topic_id' => $validated['topic_id'],
                'title' => $validated['title'],
                'title_translations' => $validated['title_translations'],
                'level' => $validated['level'],
                'rating' => $validated['rating'],
                'content_type' => $validated['content_type'],
                'body' => $validated['body'],
                'body_translations' => $validated['body_translations'],
                'version' => $validated['version'],
            ]);

            return $topic->category_id;
        });

        return redirect()->route('education.course')
            ->with('success', 'Материал курса изменён.')
            ->with('open_category_id', $this->educationOpenCategoryId($categoryId));
    }

    public function destroyMaterial(EducationalMaterial $material)
    {
        $project = $this->educationProject();
        $this->assertMaterialProject($material, $project);
        $categoryId = $material->topic?->category_id;
        DB::transaction(function () use ($material) {
            $topic = $material->topic;
            $material->delete();

            if (!$topic->materials()->exists()) {
                $topic->delete();
            }
        });

        return redirect()->route('education.course')
            ->with('success', 'Материал курса удалён.')
            ->with('open_category_id', $this->educationOpenCategoryId($categoryId));
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
                'materialSearchItems' => [],
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

        $materialSearchItems = $materials
            ->map(fn (EducationalMaterial $material) => [
                'id' => (string) $material->id,
                'title' => $material->title ?: ('Урок #' . $material->id),
                'topic' => $material->topic?->title ?? '',
                'level' => $material->level,
                'version' => $material->version,
            ])
            ->values()
            ->all();

        return view('education.tests', [
            'project' => $project,
            'tests' => $tests,
            'attempts' => $attempts,
            'materials' => $materials,
            'materialSearchItems' => $materialSearchItems,
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
                'categories' => collect(),
                'categoryGroups' => collect(),
                'categoryEditorItems' => [],
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
                    'category_id' => $test->category_id,
                ],
            ])
            ->all();

        $categories = $this->educationCategories((int) $project->id, EducationCategory::CONTEXT_KNOW_YOURSELF);

        return view('education.know-yourself', [
            'project' => $project,
            'tests' => $tests,
            'attempts' => $attempts,
            'testEditorItems' => $testEditorItems,
            'categories' => $categories,
            'categoryGroups' => $this->educationCategoryGroups($categories, $tests, 'tests'),
            'categoryEditorItems' => $this->categoryEditorItems($categories),
            'migrationRequired' => false,
        ]);
    }

    public function storeCategory(Request $request)
    {
        $project = $this->educationProject();
        $validated = $this->validateCategory($request);

        $category = EducationCategory::create($validated + [
            'project_id' => $project->id,
            'is_active' => true,
        ]);

        return redirect()->route($this->categoryRedirectRoute($validated['context']))
            ->with('success', 'Категория создана.')
            ->with('open_category_id', $this->educationOpenCategoryId($category->id));
    }

    public function updateCategory(Request $request, EducationCategory $category)
    {
        $project = $this->educationProject();
        abort_unless((int) $category->project_id === (int) $project->id, 404);
        $validated = $this->validateCategory($request);
        abort_unless($category->context === $validated['context'], 422, 'Нельзя изменить раздел категории.');
        $category->update($validated);

        return redirect()->route($this->categoryRedirectRoute($category->context))
            ->with('success', 'Категория изменена.')
            ->with('open_category_id', $this->educationOpenCategoryId($category->id));
    }

    public function destroyCategory(EducationCategory $category)
    {
        $project = $this->educationProject();
        abort_unless((int) $category->project_id === (int) $project->id, 404);
        $context = $category->context;
        $category->delete();

        return redirect()->route($this->categoryRedirectRoute($context))
            ->with('success', 'Категория удалена. Элементы перемещены в «Без категории».')
            ->with('open_category_id', 'none');
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

        return redirect()->route('education.know-yourself')
            ->with('success', 'Тест создан.')
            ->with('open_category_id', $this->educationOpenCategoryId($test->category_id));
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

        return redirect()->route('education.know-yourself')
            ->with('success', 'Тест изменён.')
            ->with('open_category_id', $this->educationOpenCategoryId($test->category_id));
    }

    public function destroyKnowYourself(QuestTest $test)
    {
        $project = $this->educationProject();
        $this->assertTestProject($test, $project);
        abort_unless(($test->test_type ?? '') === 'profile_assessment', 404);
        $categoryId = $test->category_id;
        $test->delete();

        return redirect()->route('education.know-yourself')
            ->with('success', 'Тест удалён.')
            ->with('open_category_id', $this->educationOpenCategoryId($categoryId));
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
        $ratingAward = max(0, (int) ($test->quest_data['rating'] ?? 0));
        $ratingAwarded = 0;
        $ratingAlreadyAwarded = false;

        DB::transaction(function () use ($test, $resolver, $userId, $validated, $score, $passed, $result, $ratingAward, &$ratingAwarded, &$ratingAlreadyAwarded) {
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

            $ratingAlreadyAwarded = QuestTestAttempt::query()
                ->where('user_id', $userId)
                ->where('quest_test_id', $test->id)
                ->where('passed', true)
                ->get()
                ->contains(fn (QuestTestAttempt $attempt) => (int) data_get($attempt->result_data, 'rating_awarded', 0) > 0);

            if ($passed && $ratingAward > 0 && !$ratingAlreadyAwarded) {
                DB::table('users')
                    ->where('id', $userId)
                    ->increment('education_rating', $ratingAward);
                $ratingAwarded = $ratingAward;
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
                    'rating_award' => $ratingAward,
                    'rating_awarded' => $ratingAwarded,
                    'rating_already_awarded' => $ratingAlreadyAwarded,
                ],
                'next_material_id' => $nextMaterial?->id,
            ]);
        });

        $ratingMessage = $ratingAwarded > 0
            ? " Рейтинг увеличен на {$ratingAwarded}."
            : ($passed && $ratingAward > 0 && $ratingAlreadyAwarded
                ? ' Рейтинг за этот тест уже был начислен ранее.'
                : '');

        return back()->with(
            $passed ? 'success' : 'warning',
            $result['scoring_type'] === 'points'
                ? "Результат: {$result['total_score']} из {$result['max_score']} баллов. Профиль: {$result['profile']['title']}.{$ratingMessage}"
                : ($passed
                    ? "Тест пройден: {$score}%. Открыт следующий уровень.{$ratingMessage}"
                    : "Результат {$score}%. Материал автоматически заменён на более подходящую версию.")
        );
    }

    private function educationProject(): Project
    {
        $project = Project::query()->find((int) session('fid'));
        abort_unless($project && strtolower(trim((string) $project->project_type)) === 'education', 403);

        return $project;
    }

    private function educationMaterialImages(): array
    {
        if (!Storage::disk('public')->exists(self::MATERIAL_IMAGES_DIRECTORY)) {
            Storage::disk('public')->makeDirectory(self::MATERIAL_IMAGES_DIRECTORY);
        }

        $metadata = $this->educationMaterialImagesMetadata();

        return collect(Storage::disk('public')->files(self::MATERIAL_IMAGES_DIRECTORY))
            ->filter(function (string $path): bool {
                return in_array(strtolower(pathinfo($path, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'webp', 'gif'], true);
            })
            ->sortDesc()
            ->map(function (string $path) use ($metadata): array {
                $filename = basename($path);
                $name = pathinfo($filename, PATHINFO_FILENAME);
                $alt = trim((string) data_get($metadata, $filename . '.alt', $name));
                $url = rtrim(self::MATERIAL_IMAGES_PUBLIC_BASE_URL, '/') . '/storage/' . ltrim($path, '/');

                return [
                    'file' => $filename,
                    'name' => $name,
                    'alt' => $alt,
                    'path' => $path,
                    'url' => $url,
                    'hint' => '<figure>' . PHP_EOL
                        . '  <img src="' . e($url) . '" alt="' . e($alt) . '">' . PHP_EOL
                        . '  <figcaption>' . e($alt) . '</figcaption>' . PHP_EOL
                        . '</figure>',
                ];
            })
            ->values()
            ->all();
    }

    private function educationMaterialImagesMetadata(): array
    {
        if (!Storage::disk('public')->exists(self::MATERIAL_IMAGES_METADATA)) {
            return [];
        }

        $payload = json_decode((string) Storage::disk('public')->get(self::MATERIAL_IMAGES_METADATA), true);

        return is_array($payload) ? $payload : [];
    }

    private function saveEducationMaterialImagesMetadata(array $metadata): void
    {
        Storage::disk('public')->put(
            self::MATERIAL_IMAGES_METADATA,
            json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );
    }

    private function educationSchemaReady(): bool
    {
        return Schema::hasTable('education_topics')
            && Schema::hasTable('education_categories')
            && Schema::hasColumn('education_topics', 'category_id')
            && Schema::hasTable('educational_materials')
            && Schema::hasColumn('educational_materials', 'title')
            && Schema::hasColumn('educational_materials', 'rating')
            && Schema::hasColumn('education_topics', 'title_translations')
            && Schema::hasColumn('education_topics', 'description_translations')
            && Schema::hasColumn('education_topics', 'cost_av8')
            && Schema::hasColumn('educational_materials', 'title_translations')
            && Schema::hasColumn('educational_materials', 'body_translations')
            && Schema::hasColumn('users', 'education_rating')
            && Schema::hasTable('quests_tests')
            && Schema::hasTable('quest_test_results')
            && Schema::hasColumn('quests_tests', 'project_id')
            && Schema::hasColumn('quests_tests', 'category_id')
            && Schema::hasColumn('quests_tests', 'test_type')
            && Schema::hasColumn('quests_tests', 'title_translations')
            && Schema::hasColumn('quests_tests', 'quest_data_translations')
            && Schema::hasTable('education_progress')
            && Schema::hasTable('user_course_progress')
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
            'level' => ['nullable', 'in:beginner,intermediate,advanced'],
            'rating' => ['nullable', 'integer', 'min:0'],
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
        $validated['level'] = $validated['level'] ?? $material?->level ?? 'beginner';
        $validated['rating'] = (int) ($validated['rating'] ?? 0);

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
            'cost_av8' => ['nullable', 'numeric', 'min:0'],
            'category_id' => ['nullable', 'integer', 'min:1'],
        ]);
        $validated['title_translations'] = $this->translationMap($validated['title_translations'] ?? null);
        $validated['description_translations'] = $this->translationMap($validated['description_translations'] ?? null);
        $validated['title'] = $this->fallbackTranslationValue($validated['title_translations'], $validated['title'] ?? '');
        $validated['description'] = $this->fallbackTranslationValue($validated['description_translations'], $validated['description'] ?? '');
        abort_if($validated['title'] === '', 422, 'Заполните название курса хотя бы на одном языке.');
        if ($validated['title_translations'] === []) {
            $validated['title_translations'] = ['ru' => $validated['title']];
        }
        $validated['cost_av8'] = number_format((float) ($validated['cost_av8'] ?? 0), 6, '.', '');
        $validated['category_id'] = $this->validatedCategoryId(
            $validated['category_id'] ?? null,
            EducationCategory::CONTEXT_COURSE
        );

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
            'rating' => ['nullable', 'integer', 'min:0'],
            'quest_data' => ['required', 'json'],
            'quest_data_translations' => ['nullable', 'json'],
        ]);
        $validated['quest_data'] = json_decode($validated['quest_data'], true, 512, JSON_THROW_ON_ERROR);
        $validated['quest_data']['rating'] = max(0, (int) ($validated['rating'] ?? 0));
        unset($validated['rating']);
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
            'question_images' => ['nullable', 'array'],
            'question_images.*' => ['nullable', 'image', 'max:5120'],
            'category_id' => ['nullable', 'integer', 'min:1'],
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
        $validated = $this->storeKnowYourselfQuestionImages($request, $validated);
        $validated['category_id'] = $this->validatedCategoryId(
            $validated['category_id'] ?? null,
            EducationCategory::CONTEXT_KNOW_YOURSELF
        );
        if ($validated['title_translations'] === []) {
            $validated['title_translations'] = ['ru' => $validated['title']];
        }

        unset($validated['question_images']);

        return $validated;
    }

    private function validateCategory(Request $request): array
    {
        $validated = $request->validate([
            'context' => ['required', Rule::in([
                EducationCategory::CONTEXT_KNOW_YOURSELF,
                EducationCategory::CONTEXT_COURSE,
            ])],
            'title' => ['nullable', 'string', 'max:255'],
            'title_translations' => ['nullable'],
            'position' => ['nullable', 'integer', 'min:0'],
        ]);
        $validated['title_translations'] = $this->translationMap($validated['title_translations'] ?? null);
        $validated['title'] = $this->fallbackTranslationValue($validated['title_translations'], $validated['title'] ?? '');
        abort_if($validated['title'] === '', 422, 'Заполните название категории хотя бы на одном языке.');
        if ($validated['title_translations'] === []) {
            $validated['title_translations'] = ['ru' => $validated['title']];
        }
        $validated['position'] = (int) ($validated['position'] ?? 0);

        return $validated;
    }

    private function validatedCategoryId(mixed $categoryId, string $context): ?int
    {
        if (!$categoryId) {
            return null;
        }

        return (int) EducationCategory::query()
            ->where('project_id', (int) session('fid'))
            ->where('context', $context)
            ->where('is_active', true)
            ->findOrFail((int) $categoryId)
            ->id;
    }

    private function educationCategories(int $projectId, string $context)
    {
        return EducationCategory::query()
            ->where('project_id', $projectId)
            ->where('context', $context)
            ->where('is_active', true)
            ->orderBy('position')
            ->orderBy('id')
            ->get();
    }

    private function categoryEditorItems($categories): array
    {
        return $categories->mapWithKeys(fn (EducationCategory $category) => [
            $category->id => [
                'id' => $category->id,
                'title' => $category->title,
                'title_translations' => $category->title_translations ?? [],
                'position' => $category->position,
                'context' => $category->context,
            ],
        ])->all();
    }

    private function educationCategoryGroups($categories, $items, string $itemsKey)
    {
        $itemsByCategory = $items->groupBy(fn ($item) => $item->category_id ? (string) $item->category_id : 'none');
        $knownCategoryKeys = $categories->pluck('id')->map(fn ($id) => (string) $id)->all();
        $groups = $categories->map(fn (EducationCategory $category) => [
            'key' => (string) $category->id,
            'title' => $category->title,
            $itemsKey => $itemsByCategory->get((string) $category->id, collect()),
        ]);

        foreach ($itemsByCategory as $categoryKey => $groupedItems) {
            if ($categoryKey === 'none' || in_array((string) $categoryKey, $knownCategoryKeys, true)) {
                continue;
            }

            $groups->push([
                'key' => (string) $categoryKey,
                'title' => $groupedItems->first()?->category?->title ?? 'Без категории',
                $itemsKey => $groupedItems,
            ]);
        }

        if ($itemsByCategory->has('none')) {
            $groups->push([
                'key' => 'none',
                'title' => 'Без категории',
                $itemsKey => $itemsByCategory->get('none'),
            ]);
        }

        return $groups;
    }

    private function categoryRedirectRoute(string $context): string
    {
        return $context === EducationCategory::CONTEXT_COURSE
            ? 'education.course'
            : 'education.know-yourself';
    }

    private function educationOpenCategoryId(mixed $categoryId): string
    {
        return $categoryId ? (string) $categoryId : 'none';
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
            ->with(['material.topic', 'results', 'category'])
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
            ->with(['results', 'category'])
            ->where('is_active', true)
            ->where('project_id', $projectId)
            ->where('test_type', 'profile_assessment');
    }

    private function courseTestsQuery(int $projectId)
    {
        return QuestTest::query()
            ->with(['material.topic', 'results'])
            ->where('is_active', true)
            ->whereNotNull('material_id')
            ->whereHas('material.topic', fn ($topicQuery) => $topicQuery
                ->where('project_id', $projectId)
                ->where('is_active', true))
            ->whereHas('material', fn ($materialQuery) => $materialQuery
                ->where('is_active', true));
    }

    private function publicTestPayload(QuestTest $test, string $lang = 'ru'): array
    {
        $questData = $this->localizedQuestData($test, $lang);

        return [
            'id' => $test->id,
            'category_id' => $test->category_id,
            'category_title' => $test->category
                ? $this->localizedText($test->category->title_translations, $lang, (string) $test->category->title)
                : null,
            'category_position' => (int) ($test->category?->position ?? 2147483647),
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
                    'image_url' => $this->publicQuestionImage($test, $question, $index),
                    'options' => $this->publicQuestionOptions($question['options'] ?? []),
                ]),
        ];
    }

    private function publicQuestionImage(QuestTest $test, mixed $question, int $index): ?string
    {
        $localizedQuestion = is_array($question) ? $question : [];
        $baseQuestions = array_values($test->quest_data['questions'] ?? []);
        $baseQuestion = is_array($baseQuestions[$index] ?? null) ? $baseQuestions[$index] : [];
        $path = trim((string) ($localizedQuestion['image'] ?? $baseQuestion['image'] ?? ''));

        return $path === '' ? null : MediaUrl::image($path);
    }

    private function storeKnowYourselfQuestionImages(Request $request, array $validated): array
    {
        $baseQuestions = array_values($validated['quest_data']['questions'] ?? []);
        $sharedImages = array_map(
            fn ($question) => is_array($question) ? trim((string) ($question['image'] ?? '')) : '',
            $baseQuestions
        );

        foreach ((array) $request->file('question_images', []) as $index => $uploadedFile) {
            $index = (int) $index;
            if (!$uploadedFile || !$uploadedFile->isValid() || !array_key_exists($index, $baseQuestions)) {
                continue;
            }

            $extension = strtolower($uploadedFile->getClientOriginalExtension() ?: $uploadedFile->extension() ?: 'jpg');
            $filename = 'question_' . date('Ymd_His') . '_' . $index . '_' . uniqid() . '.' . $extension;
            $path = $uploadedFile->storeAs('files/education/questions', $filename, 'public');
            if ($path) {
                $sharedImages[$index] = $path;
            }
        }

        foreach ($baseQuestions as $index => &$question) {
            if (is_array($question)) {
                $question['image'] = $sharedImages[$index] ?? '';
            }
        }
        unset($question);
        $validated['quest_data']['questions'] = $baseQuestions;

        foreach ($validated['quest_data_translations'] as $lang => $questData) {
            $questions = array_values($questData['questions'] ?? []);
            foreach ($questions as $index => &$question) {
                if (is_array($question)) {
                    $question['image'] = $sharedImages[$index] ?? '';
                }
            }
            unset($question);
            $validated['quest_data_translations'][$lang]['questions'] = $questions;
        }

        return $validated;
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

        return in_array($lang, self::EDUCATION_LANGUAGES, true) ? $lang : 'ru';
    }

    private function localizedText(?array $translations, string $lang, string $fallback = ''): string
    {
        $translations = $translations ?? [];
        $value = trim((string) ($translations[$lang] ?? ''));
        if ($value !== '') {
            return $value;
        }

        foreach (self::EDUCATION_LANGUAGES as $fallbackLang) {
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
        foreach (self::EDUCATION_LANGUAGES as $lang) {
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
        foreach (self::EDUCATION_LANGUAGES as $lang) {
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

        foreach (self::EDUCATION_LANGUAGES as $lang) {
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
