<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('bank_stock_analysis_parameters')) {
            return;
        }

        Schema::create('bank_stock_analysis_parameters', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_id')->default(0)->index();
            $table->string('field_key', 120);
            $table->string('label', 160);
            $table->text('description')->nullable();
            $table->text('settings')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['project_id', 'field_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_stock_analysis_parameters');
    }
};
