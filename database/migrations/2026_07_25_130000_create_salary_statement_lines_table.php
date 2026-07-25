<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('salary_statement_lines')) {
            return;
        }

        Schema::create('salary_statement_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('statement_document_id');
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('project_id');
            $table->decimal('salary_amount', 15, 2)->default(0);
            $table->unsignedBigInteger('zp_document_id')->nullable();
            $table->timestamps();

            $table->unique(
                ['statement_document_id', 'employee_id'],
                'salary_statement_employee_unique'
            );
            $table->unique('zp_document_id', 'salary_statement_zp_unique');
            $table->index(['project_id', 'statement_document_id'], 'salary_statement_project_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_statement_lines');
    }
};
