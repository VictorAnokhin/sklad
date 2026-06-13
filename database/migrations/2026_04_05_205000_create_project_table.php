<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('project')) {
            return;
        }

        Schema::create('project', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('num')->default(0);
            $table->string('name', 50)->default('');
            $table->string('project_type', 40)->nullable();
            $table->text('phone')->nullable();
            $table->text('telegram')->nullable();
            $table->text('instagram')->nullable();
            $table->text('twitter')->nullable();
            $table->text('facebook')->nullable();
            $table->unsignedBigInteger('userid')->default(0);
            $table->string('foto', 50)->default('');
            $table->string('foto_header', 50)->default('');
            $table->string('foto_footer', 50)->default('');
            $table->text('description')->nullable();
            $table->boolean('web')->default(false);
            $table->boolean('hit')->default(false);
            $table->text('htmlkeys')->nullable();

            $table->index('userid');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project');
    }
};
