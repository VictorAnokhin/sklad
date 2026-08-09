<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('conf') || ! Schema::hasColumn('conf', 'firma')) {
            return;
        }

        DB::table('conf')
            ->whereIn('type', ['tgroup', 'tclient', 'currency'])
            ->update(['firma' => 0]);
    }

    public function down(): void
    {
        // These settings are intentionally shared globally after migration.
    }
};
