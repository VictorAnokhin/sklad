<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('product_project_mappings')) {
            return;
        }

        Schema::create('product_project_mappings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('source_company_id');
            $table->unsignedBigInteger('counterparty_user_id')->default(0);
            $table->string('source_product_id', 80);
            $table->unsignedBigInteger('target_company_id');
            $table->string('target_product_id', 80);
            $table->timestamps();

            $table->unique(
                ['source_company_id', 'counterparty_user_id', 'source_product_id', 'target_company_id'],
                'product_project_mappings_source_unique'
            );
            $table->index(
                ['source_company_id', 'counterparty_user_id'],
                'product_project_mappings_counterparty_index'
            );
            $table->index(
                ['target_company_id', 'target_product_id'],
                'product_project_mappings_target_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_project_mappings');
    }
};
