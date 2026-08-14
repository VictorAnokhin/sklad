<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('team_memberships') || Schema::hasColumn('team_memberships', 'role_id')) {
            return;
        }

        Schema::table('team_memberships', function (Blueprint $table): void {
            $table->unsignedBigInteger('role_id')->nullable()->after('project_id')->index();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('team_memberships') || ! Schema::hasColumn('team_memberships', 'role_id')) {
            return;
        }

        Schema::table('team_memberships', function (Blueprint $table): void {
            $table->dropIndex('team_memberships_role_id_index');
            $table->dropColumn('role_id');
        });
    }
};
