<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('quests_tests')) {
            Schema::table('quests_tests', function (Blueprint $table) {
                if (!Schema::hasColumn('quests_tests', 'project_id')) {
                    $table->unsignedBigInteger('project_id')->nullable()->after('id')->index();
                }
                if (!Schema::hasColumn('quests_tests', 'test_type')) {
                    $table->string('test_type', 40)->default('knowledge_check')->after('material_id')->index();
                }
            });

            $this->makeForeignKeyNullable('quests_tests', 'material_id', 'quests_tests_material_id_foreign', 'educational_materials');
        }

        if (Schema::hasTable('quest_test_attempts')) {
            Schema::table('quest_test_attempts', function (Blueprint $table) {
                if (!Schema::hasColumn('quest_test_attempts', 'total_score')) {
                    $table->unsignedInteger('total_score')->nullable()->after('score');
                }
                if (!Schema::hasColumn('quest_test_attempts', 'max_score')) {
                    $table->unsignedInteger('max_score')->nullable()->after('total_score');
                }
                if (!Schema::hasColumn('quest_test_attempts', 'result_data')) {
                    $table->json('result_data')->nullable()->after('answers');
                }
            });

            $this->makeForeignKeyNullable('quest_test_attempts', 'material_id', 'quest_test_attempts_material_id_foreign', 'educational_materials');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('quests_tests')) {
            Schema::table('quests_tests', function (Blueprint $table) {
                if (Schema::hasColumn('quests_tests', 'project_id')) {
                    $table->dropColumn('project_id');
                }
                if (Schema::hasColumn('quests_tests', 'test_type')) {
                    $table->dropColumn('test_type');
                }
            });
        }
    }

    private function makeForeignKeyNullable(string $table, string $column, string $foreignKey, string $relatedTable): void
    {
        if (!Schema::hasColumn($table, $column)) {
            return;
        }

        try {
            DB::statement("ALTER TABLE {$table} DROP FOREIGN KEY {$foreignKey}");
        } catch (\Throwable) {
            // Foreign key may already be absent or renamed on a legacy database.
        }

        DB::statement("ALTER TABLE {$table} MODIFY {$column} BIGINT UNSIGNED NULL");

        try {
            DB::statement("ALTER TABLE {$table} ADD CONSTRAINT {$foreignKey} FOREIGN KEY ({$column}) REFERENCES {$relatedTable}(id) ON DELETE SET NULL");
        } catch (\Throwable) {
            // Keep migration idempotent if the constraint already exists.
        }
    }
};
