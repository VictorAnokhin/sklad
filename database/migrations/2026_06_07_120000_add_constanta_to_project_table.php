<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('project') || Schema::hasColumn('project', 'constanta')) {
            return;
        }

        Schema::table('project', function (Blueprint $table) {
            $table->boolean('constanta')->default(false)->after('hit')->index();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('project') || ! Schema::hasColumn('project', 'constanta')) {
            return;
        }

        Schema::table('project', function (Blueprint $table) {
            $table->dropIndex(['constanta']);
            $table->dropColumn('constanta');
        });
    }
};
