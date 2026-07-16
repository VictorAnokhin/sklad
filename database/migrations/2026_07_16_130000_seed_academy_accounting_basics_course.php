<?php

use Database\Seeders\AcademyAccountingBasicsCourseSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (
            ! Schema::hasTable('education_topics')
            || ! Schema::hasTable('educational_materials')
            || ! Schema::hasTable('quests_tests')
        ) {
            return;
        }

        (new AcademyAccountingBasicsCourseSeeder())->run();
    }

    public function down(): void
    {
        if (! Schema::hasTable('education_topics')) {
            return;
        }

        DB::table('education_topics')
            ->where('project_id', AcademyAccountingBasicsCourseSeeder::PROJECT_ID)
            ->where('title', AcademyAccountingBasicsCourseSeeder::COURSE_TITLE)
            ->delete();
    }
};
