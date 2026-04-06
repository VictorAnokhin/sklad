<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('firma')) {
            return;
        }

        Schema::create('firma', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->default('');
            $table->string('regnum', 12)->default('');
            $table->string('inn', 15)->default('');
            $table->string('schet', 30)->default('');
            $table->string('bank', 50)->default('');
            $table->string('mfo', 6)->default('');
            $table->string('town', 25)->default('');
            $table->string('address', 50)->default('');
            $table->string('map', 200)->default('');
            $table->string('view', 15)->default('');
            $table->string('phone', 50)->default('');
            $table->unsignedTinyInteger('dwn')->default(0);
            $table->date('data')->nullable();
            $table->unsignedBigInteger('userid')->default(0);
            $table->unsignedBigInteger('firma')->default(0);
            $table->string('direktor', 30)->default('');
            $table->string('pidpys', 30)->default('');
            $table->string('pechat', 30)->default('');

            $table->index('userid');
            $table->index('firma');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('firma');
    }
};
