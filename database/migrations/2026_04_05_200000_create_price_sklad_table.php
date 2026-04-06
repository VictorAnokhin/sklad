<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('price_sklad')) {
            return;
        }

        Schema::create('price_sklad', function (Blueprint $table) {
            $table->id();
            $table->string('pnum', 15)->default('0');
            $table->unsignedInteger('firma')->default(0);
            $table->char('garant', 2)->default('');
            $table->unsignedSmallInteger('sklad')->default(0);
            $table->decimal('count', 12, 3)->default(0);

            $table->index(['pnum', 'firma']);
            $table->index('sklad');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_sklad');
    }
};
