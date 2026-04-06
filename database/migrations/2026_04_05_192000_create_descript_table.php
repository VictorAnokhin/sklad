<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('descript')) {
            return;
        }

        Schema::create('descript', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pnum')->default(0);
            $table->unsignedBigInteger('firma')->default(0);
            $table->unsignedBigInteger('descript')->default(0);
            $table->unsignedBigInteger('descript2')->default(0);
            $table->unsignedBigInteger('descript3')->default(0);
            $table->unsignedBigInteger('descript4')->default(0);
            $table->unsignedBigInteger('descript5')->default(0);
            $table->string('name', 150)->default('');
            $table->string('name_ua', 150)->default('');
            $table->string('name_en', 150)->default('');
            $table->text('description')->nullable();
            $table->text('description_ua')->nullable();
            $table->text('description_en')->nullable();
            $table->char('web', 1)->default('1');

            $table->index(['pnum', 'firma']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('descript');
    }
};
