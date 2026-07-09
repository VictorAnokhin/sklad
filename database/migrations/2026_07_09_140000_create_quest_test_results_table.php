<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('quest_test_results')) {
            return;
        }

        Schema::create('quest_test_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quest_test_id')->constrained('quests_tests')->cascadeOnDelete();
            $table->unsignedInteger('min_score');
            $table->unsignedInteger('max_score');
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->text('description')->nullable();
            $table->text('recommendation')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index(['quest_test_id', 'min_score', 'max_score'], 'quest_test_results_test_score_idx');
        });

        $this->backfillResultsFromQuestData();
    }

    public function down(): void
    {
        Schema::dropIfExists('quest_test_results');
    }

    private function backfillResultsFromQuestData(): void
    {
        if (!Schema::hasTable('quests_tests')) {
            return;
        }

        DB::table('quests_tests')
            ->select(['id', 'quest_data'])
            ->orderBy('id')
            ->chunkById(100, function ($tests) {
                foreach ($tests as $test) {
                    $questData = json_decode((string) $test->quest_data, true);
                    $results = is_array($questData) ? ($questData['results'] ?? []) : [];

                    if (!is_array($results)) {
                        continue;
                    }

                    foreach (array_values($results) as $index => $result) {
                        if (!is_array($result)) {
                            continue;
                        }

                        DB::table('quest_test_results')->insert([
                            'quest_test_id' => $test->id,
                            'min_score' => max(0, (int) ($result['min'] ?? 0)),
                            'max_score' => max(0, (int) ($result['max'] ?? 0)),
                            'title' => (string) ($result['title'] ?? 'Профиль определён'),
                            'subtitle' => $result['subtitle'] ?? null,
                            'description' => $result['description'] ?? null,
                            'recommendation' => $result['recommendation'] ?? null,
                            'sort_order' => $index,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            });
    }
};
