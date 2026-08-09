<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('education_topics')) {
            Schema::table('education_topics', function (Blueprint $table) {
                if (! Schema::hasColumn('education_topics', 'slug')) {
                    $table->string('slug', 160)->nullable()->after('title');
                    $table->unique(['project_id', 'slug'], 'education_topics_project_slug_unique');
                }
            });

            $used = [];
            DB::table('education_topics')
                ->select('id', 'project_id', 'title', 'slug')
                ->orderBy('id')
                ->chunkById(100, function ($topics) use (&$used) {
                    foreach ($topics as $topic) {
                        $projectId = (int) $topic->project_id;
                        $current = trim((string) ($topic->slug ?? ''));
                        $base = Str::slug($current !== '' ? $current : (string) $topic->title) ?: 'course-' . $topic->id;
                        $candidate = $base;
                        $index = 2;
                        while (isset($used[$projectId][$candidate])) {
                            $candidate = $base . '-' . $index;
                            $index++;
                        }
                        $used[$projectId][$candidate] = true;

                        if ($current !== $candidate) {
                            DB::table('education_topics')->where('id', $topic->id)->update(['slug' => $candidate]);
                        }
                    }
                });
        }

        if (Schema::hasTable('quests_tests')) {
            Schema::table('quests_tests', function (Blueprint $table) {
                if (! Schema::hasColumn('quests_tests', 'slug')) {
                    $table->string('slug', 160)->nullable()->after('title');
                    $table->unique(['project_id', 'test_type', 'slug'], 'quests_tests_project_type_slug_unique');
                }
            });

            $used = [];
            DB::table('quests_tests')
                ->select('id', 'project_id', 'test_type', 'title', 'slug')
                ->orderBy('id')
                ->chunkById(100, function ($tests) use (&$used) {
                    foreach ($tests as $test) {
                        $projectId = (int) ($test->project_id ?? 0);
                        $testType = (string) ($test->test_type ?? '');
                        $current = trim((string) ($test->slug ?? ''));
                        $base = Str::slug($current !== '' ? $current : (string) $test->title) ?: 'test-' . $test->id;
                        $candidate = $base;
                        $index = 2;
                        while (isset($used[$projectId][$testType][$candidate])) {
                            $candidate = $base . '-' . $index;
                            $index++;
                        }
                        $used[$projectId][$testType][$candidate] = true;

                        if ($current !== $candidate) {
                            DB::table('quests_tests')->where('id', $test->id)->update(['slug' => $candidate]);
                        }
                    }
                });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('quests_tests') && Schema::hasColumn('quests_tests', 'slug')) {
            Schema::table('quests_tests', function (Blueprint $table) {
                $table->dropUnique('quests_tests_project_type_slug_unique');
                $table->dropColumn('slug');
            });
        }

        if (Schema::hasTable('education_topics') && Schema::hasColumn('education_topics', 'slug')) {
            Schema::table('education_topics', function (Blueprint $table) {
                $table->dropUnique('education_topics_project_slug_unique');
                $table->dropColumn('slug');
            });
        }
    }
};
