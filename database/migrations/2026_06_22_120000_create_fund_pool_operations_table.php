<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('fund_pool_operations')) {
            return;
        }

        Schema::create('fund_pool_operations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('company_id')->default(0)->index();
            $table->unsignedBigInteger('pool_id')->index();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('type', 32);
            $table->decimal('amount', 28, 8)->default(0);
            $table->string('currency', 20)->default('USDC');
            $table->decimal('shares_delta', 38, 18)->default(0);
            $table->decimal('nav_price', 28, 12)->default(1);
            $table->string('source', 32)->default('internal')->index();
            $table->unsignedBigInteger('ledger_transaction_id')->nullable()->index();
            $table->string('status', 32)->default('draft')->index();
            $table->string('reference_type', 120)->nullable();
            $table->string('reference_id', 120)->nullable();
            $table->string('blockchain_tx_digest', 120)->nullable();
            $table->string('external_event_id', 160)->nullable();
            $table->timestamp('operation_at')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->timestamp('reversed_at')->nullable();
            $table->unsignedBigInteger('reversed_by_operation_id')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['pool_id', 'user_id', 'status'], 'fund_pool_ops_pool_user_status_idx');
            $table->index(['reference_type', 'reference_id'], 'fund_pool_ops_reference_idx');
            $table->index(['pool_id', 'operation_at'], 'fund_pool_ops_pool_date_idx');
            $table->unique(['source', 'external_event_id'], 'fund_pool_ops_source_event_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fund_pool_operations');
    }
};
