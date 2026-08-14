<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('employee_roles')) {
            Schema::create('employee_roles', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('project_id')->index();
                $table->string('name', 120);
                $table->text('description')->nullable();
                $table->unsignedInteger('sort')->default(100);
                $table->timestamps();

                $table->unique(['project_id', 'name'], 'employee_roles_project_name_unique');
            });
        }

        if (! Schema::hasTable('employee_role_permissions')) {
            Schema::create('employee_role_permissions', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('role_id')->index();
                $table->string('permission', 120);
                $table->timestamps();

                $table->unique(['role_id', 'permission'], 'employee_role_permissions_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_role_permissions');
        Schema::dropIfExists('employee_roles');
    }
};
