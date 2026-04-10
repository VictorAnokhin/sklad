<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('project')) {
            return;
        }

        if (Schema::hasColumn('project', 'url') && !Schema::hasColumn('project', 'phone')) {
            Schema::table('project', function (Blueprint $table) {
                $table->renameColumn('url', 'phone');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('project')) {
            return;
        }

        if (Schema::hasColumn('project', 'phone') && !Schema::hasColumn('project', 'url')) {
            Schema::table('project', function (Blueprint $table) {
                $table->renameColumn('phone', 'url');
            });
        }
    }
};
