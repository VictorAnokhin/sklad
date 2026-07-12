<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('education_topics')) {
            Schema::table('education_topics', function (Blueprint $table) {
                if (!Schema::hasColumn('education_topics', 'cost_av8')) {
                    $table->decimal('cost_av8', 18, 6)->default(0)->after('position');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('education_topics')) {
            Schema::table('education_topics', function (Blueprint $table) {
                if (Schema::hasColumn('education_topics', 'cost_av8')) {
                    $table->dropColumn('cost_av8');
                }
            });
        }
    }
};
