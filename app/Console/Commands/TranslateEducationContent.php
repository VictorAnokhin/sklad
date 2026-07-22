<?php

namespace App\Console\Commands;

use App\Contracts\AiClientInterface;
use App\Models\EducationCategory;
use App\Models\EducationTopic;
use App\Models\EducationalMaterial;
use App\Models\QuestTest;
use App\Services\AiClientFactory;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class TranslateEducationContent extends Command
{
    protected $signature = 'education:translate-content
        {--provider= : AI provider key from config/ai.php}
        {--model= : Optional model override}
        {--project= : Limit by education project id}
        {--scope=all : all, course, tests, know-yourself}
        {--target=* : Target language, can be repeated; defaults to ua,en,es,fr}
        {--force : Overwrite existing non-empty translations}
        {--dry-run : Show what would be translated without saving}
        {--max-tokens=6000 : Max output tokens per AI request}';

    protected $description = 'Translate saved education courses, know-yourself tests, and course tests into all supported languages.';

    private const SOURCE_LANGUAGE = 'ru';
    private const TARGET_LANGUAGES = ['ua', 'en', 'es', 'fr'];
    private const MAX_TRANSLATION_CHARS = 6000;
    private const LANGUAGE_NAMES = [
        'ua' => 'Ukrainian',
        'en' => 'English',
        'es' => 'Spanish',
        'fr' => 'French',
    ];
    private const QUEST_TEXT_KEYS = [
        'intro',
        'text',
        'label',
        'title',
        'subtitle',
        'description',
        'recommendation',
    ];

    private AiClientInterface $client;

    /** @var array<string, string> */
    private array $cache = [];

    private bool $force = false;
    private bool $dryRun = false;
    private int $maxTokens = 6000;

    public function handle(AiClientFactory $factory): int
    {
        if (! $this->educationSchemaReady()) {
            $this->error('Education tables or translation columns are missing. Run migrations first.');
            return self::FAILURE;
        }

        $scope = $this->scope();
        $targets = $this->targetLanguages();
        $projectId = $this->projectId();
        $this->force = (bool) $this->option('force');
        $this->dryRun = (bool) $this->option('dry-run');
        $this->maxTokens = max(1000, (int) $this->option('max-tokens'));
        $this->client = $this->makeClient($factory);

        $this->line('Scope: '.$scope);
        $this->line('Targets: '.implode(', ', $targets));
        $this->line('Provider: '.$this->client->getProviderName().' / '.$this->client->getModel());
        if ($projectId !== null) {
            $this->line('Project: '.$projectId);
        }
        if ($this->dryRun) {
            $this->warn('Dry run: no database rows will be updated.');
        }

        $updated = 0;

        if ($scope === 'all' || $scope === 'course' || $scope === 'know-yourself') {
            $updated += $this->translateCategories($targets, $scope, $projectId);
        }

        if ($scope === 'all' || $scope === 'course') {
            $updated += $this->translateCourses($targets, $projectId);
            $updated += $this->translateMaterials($targets, $projectId);
        }

        if ($scope === 'all' || $scope === 'tests') {
            $updated += $this->translateTests($targets, $projectId, null);
        } elseif ($scope === 'course') {
            $updated += $this->translateTests($targets, $projectId, 'course');
        } elseif ($scope === 'know-yourself') {
            $updated += $this->translateTests($targets, $projectId, 'know-yourself');
        }

        $this->info("Done. {$updated} record(s) ".($this->dryRun ? 'would be updated.' : 'updated.'));
        return self::SUCCESS;
    }

    private function makeClient(AiClientFactory $factory): AiClientInterface
    {
        $provider = trim((string) $this->option('provider'));
        $model = trim((string) $this->option('model')) ?: null;

        $client = $provider !== ''
            ? $factory->makeForProvider($provider, $model)
            : $factory->make('agent');

        if ($model !== null && $provider === '') {
            $client->setModel($model);
        }

        $client->setTemperature(0.1);
        $client->setMaxTokens($this->maxTokens);

        return $client;
    }

    /**
     * @param  array<int, string>  $targets
     */
    private function translateCategories(array $targets, string $scope, ?int $projectId): int
    {
        $query = EducationCategory::query()->orderBy('id');
        if ($projectId !== null) {
            $query->where('project_id', $projectId);
        }
        if ($scope === 'course') {
            $query->where('context', EducationCategory::CONTEXT_COURSE);
        }
        if ($scope === 'know-yourself') {
            $query->where('context', EducationCategory::CONTEXT_KNOW_YOURSELF);
        }

        return $this->chunkTranslate($query, function (EducationCategory $category) use ($targets): bool {
            return $this->translateTextMap($category, 'title_translations', 'title', $targets, "education category #{$category->id}");
        });
    }

    /**
     * @param  array<int, string>  $targets
     */
    private function translateCourses(array $targets, ?int $projectId): int
    {
        $query = EducationTopic::query()->orderBy('id');
        if ($projectId !== null) {
            $query->where('project_id', $projectId);
        }

        return $this->chunkTranslate($query, function (EducationTopic $topic) use ($targets): bool {
            $changed = $this->translateTextMap($topic, 'title_translations', 'title', $targets, "course title #{$topic->id}");
            $changed = $this->translateTextMap($topic, 'description_translations', 'description', $targets, "course description #{$topic->id}") || $changed;
            return $changed;
        });
    }

    /**
     * @param  array<int, string>  $targets
     */
    private function translateMaterials(array $targets, ?int $projectId): int
    {
        $query = EducationalMaterial::query()
            ->whereHas('topic', function (Builder $topicQuery) use ($projectId) {
                if ($projectId !== null) {
                    $topicQuery->where('project_id', $projectId);
                }
            })
            ->orderBy('id');

        return $this->chunkTranslate($query, function (EducationalMaterial $material) use ($targets): bool {
            $changed = $this->translateTextMap($material, 'title_translations', 'title', $targets, "lesson title #{$material->id}");
            $changed = $this->translateTextMap($material, 'body_translations', 'body', $targets, "lesson body #{$material->id}") || $changed;
            return $changed;
        });
    }

    /**
     * @param  array<int, string>  $targets
     */
    private function translateTests(array $targets, ?int $projectId, ?string $kind): int
    {
        $query = QuestTest::query()->orderBy('id');

        if ($kind === 'course') {
            $query->whereNotNull('material_id')
                ->whereHas('material.topic', function (Builder $topicQuery) use ($projectId) {
                    if ($projectId !== null) {
                        $topicQuery->where('project_id', $projectId);
                    }
                });
        } elseif ($kind === 'know-yourself') {
            $query->where('test_type', 'profile_assessment');
            if ($projectId !== null) {
                $query->where('project_id', $projectId);
            }
        } elseif ($projectId !== null) {
            $query->where(function (Builder $testQuery) use ($projectId) {
                $testQuery->where('project_id', $projectId)
                    ->orWhereHas('material.topic', fn (Builder $topicQuery) => $topicQuery->where('project_id', $projectId));
            });
        }

        return $this->chunkTranslate($query, function (QuestTest $test) use ($targets): bool {
            $changed = $this->translateTextMap($test, 'title_translations', 'title', $targets, "test title #{$test->id}");
            $changed = $this->translateQuestDataMap($test, $targets) || $changed;
            return $changed;
        });
    }

    /**
     * @param  Builder<Model>  $query
     */
    private function chunkTranslate(Builder $query, callable $callback): int
    {
        $updated = 0;

        $query->chunkById(25, function ($records) use ($callback, &$updated) {
            foreach ($records as $record) {
                if ($callback($record)) {
                    $updated++;
                    if (! $this->dryRun) {
                        $record->save();
                    }
                    $this->line(class_basename($record).' #'.$record->getKey().($this->dryRun ? ' would update' : ' updated'));
                }
            }
        });

        return $updated;
    }

    /**
     * @param  array<int, string>  $targets
     */
    private function translateTextMap(Model $record, string $translationField, string $fallbackField, array $targets, string $context): bool
    {
        $translations = is_array($record->{$translationField}) ? $record->{$translationField} : [];
        $source = $this->sourceText($translations, (string) ($record->{$fallbackField} ?? ''));
        if ($source === '') {
            return false;
        }

        $changed = false;
        if (trim((string) ($translations[self::SOURCE_LANGUAGE] ?? '')) === '') {
            $translations[self::SOURCE_LANGUAGE] = $source;
            $changed = true;
        }

        foreach ($targets as $target) {
            if (! $this->shouldTranslate($translations[$target] ?? null)) {
                continue;
            }

            $translations[$target] = $this->translateString($source, $target, $context);
            $changed = true;
        }

        if ($changed) {
            $record->{$translationField} = $translations;
        }

        return $changed;
    }

    /**
     * @param  array<int, string>  $targets
     */
    private function translateQuestDataMap(QuestTest $test, array $targets): bool
    {
        $translations = is_array($test->quest_data_translations) ? $test->quest_data_translations : [];
        $source = is_array($translations[self::SOURCE_LANGUAGE] ?? null)
            ? $translations[self::SOURCE_LANGUAGE]
            : (is_array($test->quest_data) ? $test->quest_data : []);

        if ($source === []) {
            return false;
        }

        $changed = false;
        if (! is_array($translations[self::SOURCE_LANGUAGE] ?? null)) {
            $translations[self::SOURCE_LANGUAGE] = $source;
            $changed = true;
        }

        foreach ($targets as $target) {
            if (! $this->shouldTranslate($translations[$target] ?? null)) {
                continue;
            }

            $translations[$target] = $this->translateQuestValue($source, $target, "test data #{$test->id}");
            $changed = true;
        }

        if ($changed) {
            $test->quest_data_translations = $translations;
        }

        return $changed;
    }

    private function translateQuestValue(mixed $value, string $target, string $context, ?string $key = null): mixed
    {
        if (is_array($value)) {
            $translated = [];
            foreach ($value as $childKey => $childValue) {
                $translated[$childKey] = $this->translateQuestValue($childValue, $target, $context, (string) $childKey);
            }
            return $translated;
        }

        if (is_string($value) && in_array($key, self::QUEST_TEXT_KEYS, true) && trim($value) !== '') {
            return $this->translateString($value, $target, "{$context}: {$key}");
        }

        return $value;
    }

    private function translateString(string $source, string $target, string $context): string
    {
        $source = trim($source);
        if ($source === '') {
            return '';
        }

        $cacheKey = $target.':'.sha1($source);
        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        if ($this->dryRun) {
            return "[{$target}] {$source}";
        }

        if (mb_strlen($source) > self::MAX_TRANSLATION_CHARS) {
            $chunks = $this->splitText($source);
            $translated = [];
            $total = count($chunks);

            foreach ($chunks as $index => $chunk) {
                $translated[] = $this->translateString($chunk, $target, "{$context} part ".($index + 1)."/{$total}");
            }

            $this->cache[$cacheKey] = implode("\n\n", $translated);
            return $this->cache[$cacheKey];
        }

        $languageName = self::LANGUAGE_NAMES[$target] ?? $target;
        $instructions = implode("\n", [
            'You are a professional translator for financial education content.',
            "Translate Russian source text into {$languageName}.",
            'Preserve Markdown, HTML tags, line breaks, lists, placeholders, product names, URLs, wallet addresses, hashes, and numbers.',
            'Do not add explanations, comments, quotes, or code fences.',
            'Return only the translated text.',
        ]);

        $response = $this->client->chat($instructions, [[
            'role' => 'user',
            'content' => "Context: {$context}\n\nRussian source:\n{$source}",
        ]], [
            'temperature' => 0.1,
            'max_tokens' => $this->maxTokens,
        ]);

        $translated = trim((string) ($response['answer'] ?? ''));
        if ($translated === '') {
            throw new RuntimeException("Empty translation for {$context} ({$target}).");
        }

        $this->cache[$cacheKey] = $translated;
        return $translated;
    }

    /**
     * @return array<int, string>
     */
    private function splitText(string $source): array
    {
        $paragraphs = preg_split("/\n{2,}/u", $source) ?: [$source];
        $chunks = [];
        $current = '';

        foreach ($paragraphs as $paragraph) {
            $paragraph = trim($paragraph);
            if ($paragraph === '') {
                continue;
            }

            if (mb_strlen($paragraph) > self::MAX_TRANSLATION_CHARS) {
                if ($current !== '') {
                    $chunks[] = $current;
                    $current = '';
                }

                $chunks = array_merge($chunks, $this->splitLongParagraph($paragraph));
                continue;
            }

            $candidate = $current === '' ? $paragraph : $current."\n\n".$paragraph;
            if (mb_strlen($candidate) > self::MAX_TRANSLATION_CHARS && $current !== '') {
                $chunks[] = $current;
                $current = $paragraph;
            } else {
                $current = $candidate;
            }
        }

        if ($current !== '') {
            $chunks[] = $current;
        }

        return $chunks === [] ? [$source] : $chunks;
    }

    /**
     * @return array<int, string>
     */
    private function splitLongParagraph(string $paragraph): array
    {
        $chunks = [];
        $offset = 0;
        $length = mb_strlen($paragraph);

        while ($offset < $length) {
            $chunks[] = mb_substr($paragraph, $offset, self::MAX_TRANSLATION_CHARS);
            $offset += self::MAX_TRANSLATION_CHARS;
        }

        return $chunks;
    }

    /**
     * @param  array<string, mixed>  $translations
     */
    private function sourceText(array $translations, string $fallback): string
    {
        $source = trim((string) ($translations[self::SOURCE_LANGUAGE] ?? ''));
        return $source !== '' ? $source : trim($fallback);
    }

    private function shouldTranslate(mixed $value): bool
    {
        if ($this->force) {
            return true;
        }

        if (is_array($value)) {
            return $value === [];
        }

        return trim((string) $value) === '';
    }

    /**
     * @return array<int, string>
     */
    private function targetLanguages(): array
    {
        $targets = $this->option('target') ?: self::TARGET_LANGUAGES;
        $targets = array_values(array_unique(array_map(
            static fn ($target) => strtolower(trim((string) $target)),
            is_array($targets) ? $targets : [$targets],
        )));

        $invalid = array_diff($targets, self::TARGET_LANGUAGES);
        if ($invalid !== []) {
            throw new RuntimeException('Unsupported target language(s): '.implode(', ', $invalid));
        }

        return $targets;
    }

    private function scope(): string
    {
        $scope = strtolower(trim((string) $this->option('scope'))) ?: 'all';
        $allowed = ['all', 'course', 'tests', 'know-yourself'];
        if (! in_array($scope, $allowed, true)) {
            throw new RuntimeException('Unsupported scope. Use one of: '.implode(', ', $allowed));
        }

        return $scope;
    }

    private function projectId(): ?int
    {
        $value = trim((string) $this->option('project'));
        return $value === '' ? null : max(1, (int) $value);
    }

    private function educationSchemaReady(): bool
    {
        return Schema::hasTable('education_categories')
            && Schema::hasTable('education_topics')
            && Schema::hasTable('educational_materials')
            && Schema::hasTable('quests_tests')
            && Schema::hasColumn('education_categories', 'title_translations')
            && Schema::hasColumn('education_topics', 'title_translations')
            && Schema::hasColumn('education_topics', 'description_translations')
            && Schema::hasColumn('educational_materials', 'title_translations')
            && Schema::hasColumn('educational_materials', 'body_translations')
            && Schema::hasColumn('quests_tests', 'title_translations')
            && Schema::hasColumn('quests_tests', 'quest_data_translations');
    }
}
