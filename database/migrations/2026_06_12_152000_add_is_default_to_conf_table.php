<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('conf') || Schema::hasColumn('conf', 'is_default')) {
            return;
        }

        Schema::table('conf', function (Blueprint $table) {
            $table->boolean('is_default')->default(false)->after('vision')->index();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('conf') || ! Schema::hasColumn('conf', 'is_default')) {
            return;
        }

        Schema::table('conf', function (Blueprint $table) {
            $table->dropColumn('is_default');
        });
    }
};
