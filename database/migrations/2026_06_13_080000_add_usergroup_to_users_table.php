<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users') || Schema::hasColumn('users', 'usergroup')) {
            return;
        }

        Schema::table('users', function (Blueprint $table): void {
            $table->integer('usergroup')->default(0)->after('tgroup');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'usergroup')) {
            return;
        }

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('usergroup');
        });
    }
};
