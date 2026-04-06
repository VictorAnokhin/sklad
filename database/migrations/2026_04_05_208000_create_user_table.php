<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('user')) {
            return;
        }

        Schema::create('user', function (Blueprint $table) {
            $table->id();
            $table->string('name', 25)->default('');
            $table->unsignedBigInteger('iduser')->default(0);
            $table->unsignedBigInteger('idfirma')->default(0);
            $table->string('address', 30)->default('');
            $table->string('phone', 50)->default('');
            $table->string('login', 30)->default('');
            $table->string('pass', 50)->default('');
            $table->unsignedTinyInteger('status')->default(0);
            $table->unsignedInteger('kassa')->default(0);
            $table->unsignedInteger('sklad')->default(0);
            $table->string('website', 250)->default('');
            $table->float('bonus')->default(0);
            $table->float('summa')->default(0);
            $table->unsignedBigInteger('userid')->default(0);

            $table->index('userid');
            $table->index('idfirma');
            $table->index('login');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user');
    }
};
