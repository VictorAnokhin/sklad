<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('garage_vehicles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->unsignedBigInteger('fid')->nullable()->index();
            $table->string('email')->index();
            $table->string('vehicle_number', 32)->nullable()->index();
            $table->string('vin', 32)->nullable()->index();
            $table->string('input_value', 64);
            $table->string('input_type', 20);
            $table->string('title')->nullable();
            $table->string('photo_url', 500)->nullable();
            $table->string('adv_link', 500)->nullable();
            $table->json('characteristics')->nullable();
            $table->json('autoria_payload')->nullable();
            $table->unsignedSmallInteger('autoria_status')->nullable();
            $table->timestamp('checked_at')->nullable()->index();
            $table->timestamps();

            $table->unique(['email', 'input_value']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('garage_vehicles');
    }
};
