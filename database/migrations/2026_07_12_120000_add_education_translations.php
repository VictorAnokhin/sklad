<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('education_topics')) {
            Schema::table('education_topics', function (Blueprint $table) {
                if (!Schema::hasColumn('education_topics', 'title_translations')) {
                    $table->json('title_translations')->nullable()->after('title');
                }
                if (!Schema::hasColumn('education_topics', 'description_translations')) {
                    $table->json('description_translations')->nullable()->after('description');
                }
            });
        }

        if (Schema::hasTable('educational_materials')) {
            Schema::table('educational_materials', function (Blueprint $table) {
                if (!Schema::hasColumn('educational_materials', 'title_translations')) {
                    $table->json('title_translations')->nullable()->after('title');
                }
                if (!Schema::hasColumn('educational_materials', 'body_translations')) {
                    $table->json('body_translations')->nullable()->after('body');
                }
            });
        }

        if (Schema::hasTable('quests_tests')) {
            Schema::table('quests_tests', function (Blueprint $table) {
                if (!Schema::hasColumn('quests_tests', 'title_translations')) {
                    $table->json('title_translations')->nullable()->after('title');
                }
                if (!Schema::hasColumn('quests_tests', 'quest_data_translations')) {
                    $table->json('quest_data_translations')->nullable()->after('quest_data');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('quests_tests')) {
            Schema::table('quests_tests', function (Blueprint $table) {
                if (Schema::hasColumn('quests_tests', 'quest_data_translations')) {
                    $table->dropColumn('quest_data_translations');
                }
                if (Schema::hasColumn('quests_tests', 'title_translations')) {
                    $table->dropColumn('title_translations');
                }
            });
        }

        if (Schema::hasTable('educational_materials')) {
            Schema::table('educational_materials', function (Blueprint $table) {
                if (Schema::hasColumn('educational_materials', 'body_translations')) {
                    $table->dropColumn('body_translations');
                }
                if (Schema::hasColumn('educational_materials', 'title_translations')) {
                    $table->dropColumn('title_translations');
                }
            });
        }

        if (Schema::hasTable('education_topics')) {
            Schema::table('education_topics', function (Blueprint $table) {
                if (Schema::hasColumn('education_topics', 'description_translations')) {
                    $table->dropColumn('description_translations');
                }
                if (Schema::hasColumn('education_topics', 'title_translations')) {
                    $table->dropColumn('title_translations');
                }
            });
        }
    }
};
