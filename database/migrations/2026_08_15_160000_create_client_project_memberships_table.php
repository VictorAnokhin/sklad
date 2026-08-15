<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('client_project_memberships')) {
            Schema::create('client_project_memberships', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('project_id');
                $table->timestamps();

                $table->unique(['user_id', 'project_id'], 'client_project_user_project_unique');
                $table->index('project_id', 'client_project_project_index');
                $table->index('user_id', 'client_project_user_index');
            });
        }

        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'firma')) {
            return;
        }

        DB::table('users')
            ->whereNotNull('firma')
            ->where('firma', '<>', '')
            ->where('firma', '<>', '0')
            ->orderBy('id')
            ->chunkById(500, function ($users): void {
                $now = now();
                $rows = $users->map(fn (object $user): array => [
                    'user_id' => (int) $user->id,
                    'project_id' => (int) $user->firma,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->filter(fn (array $row): bool => $row['user_id'] > 0 && $row['project_id'] > 0)->all();

                if ($rows !== []) {
                    DB::table('client_project_memberships')->insertOrIgnore($rows);
                }
            }, 'id');
    }

    public function down(): void
    {
        Schema::dropIfExists('client_project_memberships');
    }
};
