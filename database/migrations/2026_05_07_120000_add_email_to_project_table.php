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

        if (!Schema::hasColumn('project', 'email')) {
            $afterColumn = null;
            if (Schema::hasColumn('project', 'url')) {
                $afterColumn = 'url';
            } elseif (Schema::hasColumn('project', 'phone')) {
                $afterColumn = 'phone';
            }

            Schema::table('project', function (Blueprint $table) use ($afterColumn): void {
                if ($afterColumn !== null) {
                    $table->string('email', 255)->nullable()->after($afterColumn);
                } else {
                    $table->string('email', 255)->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('project') || !Schema::hasColumn('project', 'email')) {
            return;
        }

        Schema::table('project', function (Blueprint $table) {
            $table->dropColumn('email');
        });
    }
};
