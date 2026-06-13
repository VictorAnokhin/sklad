<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('project') || Schema::hasColumn('project', 'project_type')) {
            return;
        }

        Schema::table('project', function (Blueprint $table): void {
            $table->string('project_type', 40)->nullable()->after('name');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('project') || ! Schema::hasColumn('project', 'project_type')) {
            return;
        }

        Schema::table('project', function (Blueprint $table): void {
            $table->dropColumn('project_type');
        });
    }
};
