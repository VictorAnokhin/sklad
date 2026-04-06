<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('field')) {
            return;
        }

        Schema::create('field', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('idkeyfield')->default(0);
            $table->string('keyfield', 50)->default('');
            $table->string('val', 50)->default('');
            $table->string('valua', 50)->default('');
            $table->string('valen', 50)->default('');
            $table->string('description', 200)->default('');
            $table->string('descriptionua', 200)->default('');
            $table->string('descriptionen', 200)->default('');
            $table->string('link', 35)->default('');
            $table->text('links')->nullable();
            $table->unsignedInteger('nw')->default(0);
            $table->unsignedInteger('upd')->default(0);
            $table->unsignedSmallInteger('num')->default(0);
            $table->decimal('pers', 12, 2)->default(0);
            $table->decimal('pers1', 12, 2)->default(0);
            $table->decimal('pers2', 12, 2)->default(0);
            $table->unsignedInteger('firma')->default(0);
            $table->text('comment')->nullable();
            $table->text('hkeys')->nullable();
            $table->text('hdescr')->nullable();
            $table->boolean('visible')->default(true);
            $table->boolean('firstpage')->default(false);
            $table->string('foto1', 60)->default('');

            $table->index(['keyfield', 'firma']);
            $table->index(['idkeyfield', 'firma']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('field');
    }
};
