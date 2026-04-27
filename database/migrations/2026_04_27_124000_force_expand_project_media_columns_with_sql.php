<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            ALTER TABLE `project`
                MODIFY `foto` varchar(255) NOT NULL DEFAULT '',
                MODIFY `foto_header` varchar(255) NOT NULL DEFAULT '',
                MODIFY `foto_footer` varchar(255) NOT NULL DEFAULT ''
        ");
    }

    public function down(): void
    {
        DB::statement("
            ALTER TABLE `project`
                MODIFY `foto` varchar(50) NOT NULL DEFAULT '',
                MODIFY `foto_header` varchar(50) NOT NULL DEFAULT '',
                MODIFY `foto_footer` varchar(50) NOT NULL DEFAULT ''
        ");
    }
};
