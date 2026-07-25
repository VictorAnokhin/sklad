<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('accounts') || Schema::hasColumn('accounts', 'project_id')) {
            return;
        }

        Schema::table('accounts', function (Blueprint $table) {
            $table->unsignedBigInteger('project_id')->nullable()->after('parent_id')->index();
        });

        if (! Schema::hasTable('project')) {
            return;
        }

        $projectIds = DB::table('project')
            ->pluck('id')
            ->mapWithKeys(fn ($id) => [(string) $id => (int) $id])
            ->all();

        DB::table('accounts')
            ->select(['id', 'code'])
            ->orderBy('id')
            ->chunkById(500, function ($accounts) use ($projectIds) {
                foreach ($accounts as $account) {
                    if (! preg_match('/^\d+\.(\d+)(?:\.|$)/', (string) $account->code, $matches)) {
                        continue;
                    }

                    $projectId = $projectIds[$matches[1]] ?? null;
                    if ($projectId !== null) {
                        DB::table('accounts')->where('id', $account->id)->update(['project_id' => $projectId]);
                    }
                }
            });
    }

    public function down(): void
    {
        if (! Schema::hasTable('accounts') || ! Schema::hasColumn('accounts', 'project_id')) {
            return;
        }

        Schema::table('accounts', function (Blueprint $table) {
            $table->dropColumn('project_id');
        });
    }
};
