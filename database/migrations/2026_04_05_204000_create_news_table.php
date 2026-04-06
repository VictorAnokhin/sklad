<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('news')) {
            return;
        }

        Schema::create('news', function (Blueprint $table) {
            $table->id();
            $table->text('title')->nullable();
            $table->text('title_ua')->nullable();
            $table->text('title_en')->nullable();
            $table->text('kratko')->nullable();
            $table->text('kratko_ua')->nullable();
            $table->text('kratko_en')->nullable();
            $table->longText('txt')->nullable();
            $table->longText('txt_ua')->nullable();
            $table->longText('txt_en')->nullable();
            $table->text('htmlkeys')->nullable();
            $table->text('tags')->nullable();
            $table->text('codesocnet')->nullable();
            $table->string('dt', 12)->default('');
            $table->time('time')->nullable();
            $table->unsignedInteger('firma')->default(0);
            $table->string('foto', 250)->default('');
            $table->string('foto2', 250)->default('');
            $table->string('foto3', 250)->default('');
            $table->string('foto4', 250)->default('');
            $table->unsignedInteger('field')->default(0);
            $table->unsignedInteger('field1')->default(0);
            $table->boolean('hot')->default(false);
            $table->boolean('view')->default(false);
            $table->boolean('always')->default(false);
            $table->boolean('article')->default(false);
            $table->unsignedBigInteger('author')->default(0);
            $table->string('top', 5)->default('');

            $table->index('firma');
            $table->index('author');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('news');
    }
};
