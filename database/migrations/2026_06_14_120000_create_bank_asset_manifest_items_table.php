<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('bank_asset_manifest_items')) {
            return;
        }

        Schema::create('bank_asset_manifest_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_id');
            $table->string('asset_type', 32);
            $table->unsignedBigInteger('asset_id');
            $table->unsignedInteger('position')->default(0);
            $table->boolean('hidden')->default(false);
            $table->timestamps();

            $table->unique(['project_id', 'asset_type', 'asset_id'], 'bank_asset_manifest_unique');
            $table->index(['project_id', 'hidden', 'position'], 'bank_asset_manifest_display_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_asset_manifest_items');
    }
};
