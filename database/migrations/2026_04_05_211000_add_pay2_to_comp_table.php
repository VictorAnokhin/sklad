<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('comp', function (Blueprint $table) {
            if (!Schema::hasColumn('comp', 'pay2')) {
                $table->decimal('pay2', 12, 2)->default(0);
            }
        });
    }

    public function down(): void
    {
        Schema::table('comp', function (Blueprint $table) {
            if (Schema::hasColumn('comp', 'pay2')) {
                $table->dropColumn('pay2');
            }
        });
    }
};
