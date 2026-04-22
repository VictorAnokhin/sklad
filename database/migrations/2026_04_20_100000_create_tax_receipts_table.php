<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tax_receipts')) {
            return;
        }

        Schema::create('tax_receipts', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('firma')->default(0);
            $table->string('receipt_number', 100)->unique();
            $table->string('document_id', 100)->nullable(); // ID документа PO/RO
            $table->enum('document_type', ['PO', 'RO', 'OTHER'])->default('PO');
            $table->string('taxpayer_id', 50)->nullable(); // ІПН платника
            $table->string('cashier_name', 255)->nullable();
            $table->decimal('amount', 12, 2)->default(0);
            $table->text('goods_description')->nullable();
            $table->string('registration_status', 50)->default('pending'); // pending, registered, failed
            $table->string('tax_office_receipt_id', 100)->nullable(); // ID чека у налоговой
            $table->text('tax_office_response')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('registered_at')->nullable();
            $table->timestamps();

            $table->index('firma');
            $table->index('document_id');
            $table->index('registration_status');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_receipts');
    }
};
