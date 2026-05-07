<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('rwa_admin_caps')) {
            return;
        }

        Schema::create('rwa_admin_caps', function (Blueprint $table) {
            $table->id();
            $table->string('network', 40)->default('testnet');
            $table->string('package_id', 80);
            $table->string('admin_cap_id', 80)->unique();
            $table->string('owner_address', 80);
            $table->string('label', 120)->default('');
            $table->string('tx_digest', 120)->default('');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['network', 'package_id']);
            $table->index('owner_address');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rwa_admin_caps');
    }
};
