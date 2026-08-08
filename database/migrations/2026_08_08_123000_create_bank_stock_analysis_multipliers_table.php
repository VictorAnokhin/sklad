<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('bank_stock_analysis_multipliers')) {
            return;
        }

        Schema::create('bank_stock_analysis_multipliers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_id')->default(0)->index();
            $table->string('name', 120);
            $table->string('formula', 500);
            $table->text('description')->nullable();
            $table->string('block', 40)->default('cheapness')->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_stock_analysis_multipliers');
    }
};
