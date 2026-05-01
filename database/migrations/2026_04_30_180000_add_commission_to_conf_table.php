<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('conf') || Schema::hasColumn('conf', 'commission')) {
            return;
        }

        Schema::table('conf', function (Blueprint $table) {
            $table->decimal('commission', 8, 4)->nullable()->after('last_updated_at');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('conf') || ! Schema::hasColumn('conf', 'commission')) {
            return;
        }

        Schema::table('conf', function (Blueprint $table) {
            $table->dropColumn('commission');
        });
    }
};
