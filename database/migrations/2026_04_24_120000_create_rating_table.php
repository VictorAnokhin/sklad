<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('rating')) {
            return;
        }

        Schema::create('rating', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedBigInteger('comp_id');
            $table->unsignedTinyInteger('rating');
            $table->timestamps();

            $table->unique(['user_id', 'comp_id']);
            $table->index('comp_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rating');
    }
};
