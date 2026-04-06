<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('reklama')) {
            return;
        }

        Schema::create('reklama', function (Blueprint $table) {
            $table->id();
            $table->string('name', 30)->default('');
            $table->unsignedInteger('firma')->default(0);

            $table->index('firma');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reklama');
    }
};
