<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('holding')) {
            Schema::create('holding', function (Blueprint $table): void {
                $table->id();
                $table->string('name')->unique();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('project') && ! Schema::hasColumn('project', 'holding_id')) {
            Schema::table('project', function (Blueprint $table): void {
                $table->foreignId('holding_id')
                    ->nullable()
                    ->after('project_type')
                    ->constrained('holding')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('project') && Schema::hasColumn('project', 'holding_id')) {
            Schema::table('project', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('holding_id');
            });
        }

        Schema::dropIfExists('holding');
    }
};
