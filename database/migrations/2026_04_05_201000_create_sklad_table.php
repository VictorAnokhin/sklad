<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sklad')) {
            return;
        }

        Schema::create('sklad', function (Blueprint $table) {
            $table->id();
            $table->string('name', 200)->default('');
            $table->string('color', 25)->default('');
            $table->unsignedInteger('firma')->default(0);

            $table->index('firma');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sklad');
    }
};
