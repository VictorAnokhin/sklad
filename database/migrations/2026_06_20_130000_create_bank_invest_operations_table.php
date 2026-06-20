<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('bank_invest_operations')) {
            return;
        }

        Schema::create('bank_invest_operations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('project_id')->index();
            $table->unsignedBigInteger('account_id')->index();
            $table->string('direction', 32)->default('account_to_asset');
            $table->string('asset_type', 32);
            $table->string('asset_key', 120);
            $table->string('asset_label', 255);
            $table->string('currency', 20)->default('USD');
            $table->decimal('quantity', 28, 8)->default(0);
            $table->decimal('amount', 28, 8)->default(0);
            $table->decimal('price_usd', 28, 8)->nullable();
            $table->decimal('value_usd', 28, 8)->default(0);
            $table->text('note')->nullable();
            $table->timestamp('operated_at')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'asset_type', 'asset_key'], 'bank_invest_ops_project_asset_idx');
            $table->index(['project_id', 'operated_at'], 'bank_invest_ops_project_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_invest_operations');
    }
};
