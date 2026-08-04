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
            if (!Schema::hasColumn('education_utilities', 'schema_json')) {
                $table->json('schema_json')->nullable()->after('icon_path');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('education_utilities')) {
            return;
        }

        Schema::table('education_utilities', function (Blueprint $table) {
            if (Schema::hasColumn('education_utilities', 'schema_json')) {
                $table->dropColumn('schema_json');
            }
        });
    }
};
