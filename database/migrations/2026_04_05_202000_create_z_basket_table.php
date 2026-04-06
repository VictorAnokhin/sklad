<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('z_basket')) {
            return;
        }

        Schema::create('z_basket', function (Blueprint $table) {
            $table->id();
            $table->string('login', 25)->default('');
            $table->string('cod', 17)->default('');
            $table->string('ch', 10)->default('');
            $table->unsignedInteger('count')->nullable();

            $table->index('login');
            $table->index('cod');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('z_basket');
    }
};
