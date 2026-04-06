<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('protokol')) {
            return;
        }

        Schema::create('protokol', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('conf')->default(0);
            $table->string('lang', 5)->default('');
            $table->string('value', 250)->default('');

            $table->index(['conf', 'lang']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('protokol');
    }
};
