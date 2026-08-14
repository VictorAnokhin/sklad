<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('subscription_plans')) {
            Schema::create('subscription_plans', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('project_id')->index();
                $table->string('name', 160);
                $table->text('description')->nullable();
                $table->string('billing_period', 20)->default('month');
                $table->unsignedInteger('interval_count')->default(1);
                $table->decimal('price', 14, 2)->default(0);
                $table->string('currency', 10)->default('UAH');
                $table->unsignedInteger('payment_due_days')->default(5);
                $table->unsignedInteger('grace_days')->default(3);
                $table->boolean('block_on_overdue')->default(true);
                $table->json('blocked_features')->nullable();
                $table->boolean('active')->default(true);
                $table->timestamps();

                $table->index(['project_id', 'active']);
            });
        }

        if (! Schema::hasTable('subscription_plan_items')) {
            Schema::create('subscription_plan_items', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('plan_id')->index();
                $table->unsignedBigInteger('product_id');
                $table->string('item_type', 20)->default('goods');
                $table->decimal('quantity', 14, 3)->default(1);
                $table->decimal('price', 14, 2)->default(0);
                $table->unsignedInteger('sort')->default(100);
                $table->timestamps();

                $table->index(['plan_id', 'product_id']);
            });
        }

        if (! Schema::hasTable('customer_subscriptions')) {
            Schema::create('customer_subscriptions', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('project_id')->index();
                $table->unsignedBigInteger('client_id')->index();
                $table->unsignedBigInteger('plan_id')->index();
                $table->string('status', 20)->default('active');
                $table->string('payment_status', 20)->default('paid');
                $table->date('starts_at')->nullable();
                $table->date('next_billing_at')->nullable();
                $table->date('last_paid_until')->nullable();
                $table->date('ends_at')->nullable();
                $table->timestamp('blocked_at')->nullable();
                $table->date('grace_until')->nullable();
                $table->string('block_reason', 120)->default('');
                $table->string('payment_method', 60)->default('');
                $table->boolean('auto_create_invoice')->default(true);
                $table->boolean('auto_close_if_paid')->default(false);
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['project_id', 'status', 'next_billing_at'], 'customer_subscriptions_due_index');
            });
        }

        if (! Schema::hasTable('subscription_invoices')) {
            Schema::create('subscription_invoices', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('subscription_id')->index();
                $table->unsignedBigInteger('document_id')->nullable()->index();
                $table->date('period_from');
                $table->date('period_to');
                $table->date('due_at')->nullable();
                $table->timestamp('paid_at')->nullable();
                $table->string('status', 20)->default('pending');
                $table->decimal('amount', 14, 2)->default(0);
                $table->timestamps();

                $table->unique(['subscription_id', 'period_from', 'period_to'], 'subscription_invoices_period_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_invoices');
        Schema::dropIfExists('customer_subscriptions');
        Schema::dropIfExists('subscription_plan_items');
        Schema::dropIfExists('subscription_plans');
    }
};
