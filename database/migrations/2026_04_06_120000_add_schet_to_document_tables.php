<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('document') && !Schema::hasColumn('document', 'schet')) {
            Schema::table('document', function (Blueprint $table) {
                $table->string('schet', 20)->default('')->after('typeproduct');
            });
        }

        if (Schema::hasTable('z_document') && !Schema::hasColumn('z_document', 'schet')) {
            Schema::table('z_document', function (Blueprint $table) {
                $table->string('schet', 20)->default('')->after('typeproduct');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('document') && Schema::hasColumn('document', 'schet')) {
            Schema::table('document', function (Blueprint $table) {
                $table->dropColumn('schet');
            });
        }

        if (Schema::hasTable('z_document') && Schema::hasColumn('z_document', 'schet')) {
            Schema::table('z_document', function (Blueprint $table) {
                $table->dropColumn('schet');
            });
        }
    }
};
