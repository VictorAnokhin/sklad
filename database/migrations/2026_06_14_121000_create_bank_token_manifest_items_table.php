<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('bank_token_manifest_items')) {
            return;
        }

        Schema::create('bank_token_manifest_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_id');
            $table->unsignedBigInteger('wallet_token_id');
            $table->boolean('hidden')->default(false);
            $table->timestamps();

            $table->unique(['project_id', 'wallet_token_id'], 'bank_token_manifest_unique');
            $table->index(['project_id', 'hidden'], 'bank_token_manifest_display_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_token_manifest_items');
    }
};
