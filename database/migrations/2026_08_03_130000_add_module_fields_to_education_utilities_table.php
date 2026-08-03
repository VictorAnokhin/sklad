<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('education_utilities')) {
            return;
        }

        Schema::table('education_utilities', function (Blueprint $table) {
            if (!Schema::hasColumn('education_utilities', 'module_key')) {
                $table->string('module_key', 120)->default('investment_simulation')->after('slug');
            }
            if (!Schema::hasColumn('education_utilities', 'icon')) {
                $table->string('icon', 80)->default('calculator')->after('module_key');
            }
            if (!Schema::hasColumn('education_utilities', 'icon_path')) {
                $table->string('icon_path')->nullable()->after('icon');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('education_utilities')) {
            return;
        }

        Schema::table('education_utilities', function (Blueprint $table) {
            if (Schema::hasColumn('education_utilities', 'icon_path')) {
                $table->dropColumn('icon_path');
            }
            if (Schema::hasColumn('education_utilities', 'icon')) {
                $table->dropColumn('icon');
            }
            if (Schema::hasColumn('education_utilities', 'module_key')) {
                $table->dropColumn('module_key');
            }
        });
    }
};
