<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('field')) {
            return;
        }

        Schema::table('field', function (Blueprint $table) {
            if (!Schema::hasColumn('field', 'visible')) {
                $table->string('visible', 5)->default('1')->after('idkeyfield');
            }
            if (!Schema::hasColumn('field', 'firstpage')) {
                $table->string('firstpage', 5)->default('0')->after('visible');
            }
            if (!Schema::hasColumn('field', 'num')) {
                $table->integer('num')->default(0)->after('firstpage');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('field')) {
            return;
        }

        Schema::table('field', function (Blueprint $table) {
            $dropColumns = [];
            foreach (['visible', 'firstpage', 'num'] as $column) {
                if (Schema::hasColumn('field', $column)) {
                    $dropColumns[] = $column;
                }
            }

            if (!empty($dropColumns)) {
                $table->dropColumn($dropColumns);
            }
        });
    }
};
