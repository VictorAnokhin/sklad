<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('z_document') || !Schema::hasColumn('z_document', 'docum')) {
            return;
        }

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE `z_document` MODIFY `docum` TEXT NOT NULL');
        }
    }

    public function down(): void
    {
        // One-way: reverting to varchar(220) can truncate or fail if rows exceed the limit.
    }
};
