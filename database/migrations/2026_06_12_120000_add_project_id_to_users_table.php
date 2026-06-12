<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasTable('project')) {
            return;
        }

        if (! Schema::hasColumn('users', 'project_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreignId('project_id')
                    ->nullable()
                    ->after('firma')
                    ->constrained('project')
                    ->nullOnDelete();
            });
        }

        DB::table('users')
            ->join('project', 'project.id', '=', 'users.firma')
            ->whereNull('users.project_id')
            ->update(['users.project_id' => DB::raw('project.id')]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'project_id')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('project_id');
        });
    }
};
