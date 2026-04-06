<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('docs')) {
            return;
        }

        Schema::create('docs', function (Blueprint $table) {
            $table->id();
            $table->string('name', 30)->default('');
            $table->decimal('summa', 12, 2)->default(0);
            $table->unsignedInteger('firma')->default(0);

            $table->index('firma');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('docs');
    }
};
