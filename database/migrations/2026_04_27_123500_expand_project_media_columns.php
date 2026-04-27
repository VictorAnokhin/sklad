<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project', function (Blueprint $table) {
            $table->string('foto', 255)->default('')->change();
            $table->string('foto_header', 255)->default('')->change();
            $table->string('foto_footer', 255)->default('')->change();
        });
    }

    public function down(): void
    {
        Schema::table('project', function (Blueprint $table) {
            $table->string('foto', 50)->default('')->change();
            $table->string('foto_header', 50)->default('')->change();
            $table->string('foto_footer', 50)->default('')->change();
        });
    }
};
