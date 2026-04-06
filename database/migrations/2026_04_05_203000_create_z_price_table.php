<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('z_price')) {
            return;
        }

        Schema::create('z_price', function (Blueprint $table) {
            $table->id();
            $table->string('cod', 15)->default('');
            $table->unsignedInteger('idagent')->default(0);
            $table->string('code', 10)->default('');
            $table->text('name')->nullable();
            $table->text('description')->nullable();
            $table->decimal('dilpay', 12, 2)->default(0);
            $table->decimal('pay', 12, 2)->default(0);
            $table->char('garant', 2)->default('');
            $table->unsignedTinyInteger('sklad')->default(0);
            $table->string('dt', 12)->default('');
            $table->unsignedTinyInteger('upd')->default(0);

            $table->index('cod');
            $table->index('idagent');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('z_price');
    }
};
