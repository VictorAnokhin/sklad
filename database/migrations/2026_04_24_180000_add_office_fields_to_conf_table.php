<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('conf')) {
            return;
        }

        Schema::table('conf', function (Blueprint $table) {
            if (!Schema::hasColumn('conf', 'phone')) {
                $table->string('phone', 50)->default('')->after('doc');
            }

            if (!Schema::hasColumn('conf', 'address')) {
                $table->string('address', 255)->default('')->after('phone');
            }

            if (!Schema::hasColumn('conf', 'google_map')) {
                $table->text('google_map')->nullable()->after('address');
            }

            if (!Schema::hasColumn('conf', 'foto')) {
                $table->string('foto', 255)->default('')->after('google_map');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('conf')) {
            return;
        }

        Schema::table('conf', function (Blueprint $table) {
            foreach (['foto', 'google_map', 'address', 'phone'] as $column) {
                if (Schema::hasColumn('conf', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
