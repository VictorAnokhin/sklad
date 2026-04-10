<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('banner_carousels')) {
            return;
        }

        Schema::create('banner_carousels', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('firma')->default(0);
            $table->string('title', 255)->default('');
            $table->text('subtitle')->nullable();
            $table->string('button_text', 120)->default('');
            $table->string('link_url', 500)->default('');
            $table->string('image_path', 255)->default('');
            $table->unsignedInteger('sort_order')->default(0);
            $table->unsignedTinyInteger('vision')->default(1);
            $table->timestamps();

            $table->index(['firma', 'vision']);
            $table->index(['firma', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('banner_carousels');
    }
};
