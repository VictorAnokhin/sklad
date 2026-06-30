<?php

namespace App\Console\Commands;

use App\Models\TrafficSign;
use App\Models\TrafficTestQuestion;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class SyncTrafficLearningCatalog extends Command
{
    protected $signature = 'traffic-learning:sync-catalog {--fid=2 : Project identifier}';

    protected $description = 'Synchronize road signs and free demo questions from pdr.infotech.gov.ua';

    private const API_URL = 'https://api.testpdr.com/v1';
    private const SOURCE_URL = 'https://pdr.infotech.gov.ua/';

    public function handle(): int
    {
        $fid = max(1, (int) $this->option('fid'));

        try {
            $token = md5(now()->format('d.m.Y').'pdr2021');
            $signPayload = $this->get('/road-signs', $token);
            $settings = $this->get('/web-settings', $token);
            $demoToken = data_get($settings, 'data.user_demo_token');

            if (! is_string($demoToken) || $demoToken === '') {
                throw new RuntimeException('Публічний demo token не знайдено.');
            }

            $examPayload = $this->get('/exam-questions?is_training=true', $token, $demoToken);
            $signCount = $this->syncSigns($fid, data_get($signPayload, 'data', []));
            $questionCount = $this->syncQuestions($fid, data_get($examPayload, 'data.questions', []));
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info("Синхронізовано знаків: {$signCount}; тестових питань: {$questionCount}.");
        $this->line('Джерело: '.self::SOURCE_URL);

        return self::SUCCESS;
    }

    private function get(string $path, string $token, ?string $bearerToken = null): array
    {
        $request = Http::acceptJson()
            ->withHeaders(['Token' => $token])
            ->withUserAgent('AutoAgent-Traffic-Learning-Sync/1.0')
            ->timeout(60)
            ->retry(3, 500);

        if ($bearerToken) {
            $request = $request->withToken($bearerToken);
        }

        $response = $request->get(self::API_URL.$path);

        if (! $response->successful()) {
            throw new RuntimeException("API {$path} повернуло HTTP {$response->status()}.");
        }

        $payload = $response->json();
        if (! is_array($payload)) {
            throw new RuntimeException("API {$path} повернуло некоректні дані.");
        }

        return $payload;
    }

    private function syncSigns(int $fid, mixed $categories): int
    {
        if (! is_array($categories)) {
            throw new RuntimeException('Список дорожніх знаків відсутній.');
        }

        $directory = public_path('img/traffic-signs/catalog');
        File::ensureDirectoryExists($directory);
        $codes = [];
        $count = 0;

        DB::transaction(function () use ($fid, $categories, $directory, &$codes, &$count): void {
            foreach ($categories as $categoryIndex => $category) {
                $categoryName = trim((string) data_get($category, 'name.uk', 'Дорожні знаки'));

                foreach ((array) data_get($category, 'signs', []) as $signIndex => $sign) {
                    $code = trim((string) data_get($sign, 'number'));
                    $sourceImage = trim((string) data_get($sign, 'image.original'));
                    if ($code === '' || $sourceImage === '') {
                        continue;
                    }

                    $filename = preg_replace('/[^0-9A-Za-z._-]+/', '_', $code).'.svg';
                    $relativePath = '/img/traffic-signs/catalog/'.$filename;
                    $svg = sprintf(
                        '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" role="img"><title>%s</title><image href="%s" width="512" height="512" preserveAspectRatio="xMidYMid meet"/></svg>',
                        htmlspecialchars((string) data_get($sign, 'name.uk', $code), ENT_XML1 | ENT_QUOTES, 'UTF-8'),
                        htmlspecialchars($sourceImage, ENT_XML1 | ENT_QUOTES, 'UTF-8'),
                    );
                    File::put($directory.'/'.$filename, $svg);

                    $codes[] = $code;
                    TrafficSign::query()->updateOrCreate(
                        ['fid' => $fid, 'code' => $code],
                        [
                            'title' => (string) data_get($sign, 'name.uk', $code),
                            'description' => $this->plainText((string) (
                                data_get($sign, 'content.uk')
                                ?: data_get($sign, 'description.uk', '')
                            )),
                            'image_url' => $relativePath,
                            'category' => $categoryName,
                            'sort_order' => (($categoryIndex + 1) * 10000) + (($signIndex + 1) * 10),
                            'is_published' => true,
                        ],
                    );
                    $count++;
                }
            }

            TrafficSign::query()->where('fid', $fid)->whereNotIn('code', $codes)->delete();
        });

        return $count;
    }

    private function syncQuestions(int $fid, mixed $questions): int
    {
        if (! is_array($questions) || count($questions) === 0) {
            throw new RuntimeException('Демонстраційні тестові питання відсутні.');
        }

        $externalIds = [];

        DB::transaction(function () use ($fid, $questions, &$externalIds): void {
            foreach ($questions as $index => $question) {
                $externalId = (int) data_get($question, 'id');
                if ($externalId < 1) {
                    continue;
                }

                $externalIds[] = $externalId;
                $answers = collect((array) data_get($question, 'questions_answers', []))
                    ->sortBy(fn (array $answer): int => (int) ($answer['order'] ?? 0))
                    ->map(fn (array $answer): string => (string) data_get($answer, 'name.uk', ''))
                    ->filter()
                    ->values()
                    ->all();

                TrafficTestQuestion::query()->updateOrCreate(
                    ['fid' => $fid, 'source_external_id' => $externalId],
                    [
                        'topic_external_id' => data_get($question, 'topic_traffic_rule_id'),
                        'question' => (string) data_get($question, 'name.uk', ''),
                        'answers' => $answers,
                        'explanation' => $this->plainText((string) data_get($question, 'explanation.uk', '')),
                        'image_url' => data_get($question, 'picture'),
                        'source_url' => self::SOURCE_URL.'tests/service-center',
                        'sort_order' => ($index + 1) * 10,
                        'is_published' => true,
                    ],
                );
            }

            TrafficTestQuestion::query()
                ->where('fid', $fid)
                ->whereNotIn('source_external_id', $externalIds)
                ->delete();
        });

        return count($externalIds);
    }

    private function plainText(string $value): string
    {
        $value = preg_replace('/#\([^)]+\)/u', '', $value) ?: $value;
        $value = preg_replace('/\{[^|}]+\|[^|}]+\|([^}]+)\}/u', '$1', $value) ?: $value;
        $value = str_replace(['**', "\r"], ['', ''], $value);

        return trim($value);
    }
}
