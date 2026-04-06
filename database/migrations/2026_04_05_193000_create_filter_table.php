<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('filter')) {
            return;
        }

        Schema::create('filter', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('idkeyfield')->default(0);
            $table->unsignedInteger('idfilter')->default(0);
            $table->string('keyfield', 30)->default('');
            $table->string('val', 60)->default('');
            $table->string('valru', 60)->default('');
            $table->string('valen', 60)->default('');
            $table->text('description')->nullable();
            $table->text('descriptionen')->nullable();
            $table->text('descriptionru')->nullable();
            $table->unsignedInteger('count')->default(0);
            $table->unsignedInteger('top')->default(0);
            $table->unsignedSmallInteger('num')->default(0);

            $table->index(['keyfield', 'idkeyfield']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('filter');
    }
};
