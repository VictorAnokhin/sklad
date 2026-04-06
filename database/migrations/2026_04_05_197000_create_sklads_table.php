<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sklads')) {
            return;
        }

        Schema::create('sklads', function (Blueprint $table) {
            $table->id();
            $table->string('name', 200)->default('');
            $table->string('address', 100)->default('');
            $table->unsignedInteger('firma')->default(0);
            $table->unsignedBigInteger('userid')->default(0);

            $table->index('firma');
            $table->index('userid');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sklads');
    }
};
