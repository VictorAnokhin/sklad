<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('comp')) {
            return;
        }

        Schema::table('comp', function (Blueprint $table): void {
            if (! Schema::hasColumn('comp', 'manager_ai_external_id')) {
                $table->string('manager_ai_external_id', 255)->nullable()->after('cod');
            }

            if (! Schema::hasColumn('comp', 'manager_ai_source_url')) {
                $table->text('manager_ai_source_url')->nullable()->after('manager_ai_external_id');
            }

            if (! Schema::hasColumn('comp', 'manager_ai_source_hash')) {
                $table->string('manager_ai_source_hash', 64)->nullable()->after('manager_ai_source_url');
            }

            if (! Schema::hasColumn('comp', 'manager_ai_last_seen_at')) {
                $table->timestamp('manager_ai_last_seen_at')->nullable()->after('manager_ai_source_hash');
            }
        });

        Schema::table('comp', function (Blueprint $table): void {
            $table->index(['firma', 'manager_ai_external_id'], 'comp_firma_manager_ai_external_idx');
            $table->index(['firma', 'manager_ai_source_hash'], 'comp_firma_manager_ai_source_hash_idx');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('comp')) {
            return;
        }

        Schema::table('comp', function (Blueprint $table): void {
            $table->dropIndex('comp_firma_manager_ai_external_idx');
            $table->dropIndex('comp_firma_manager_ai_source_hash_idx');
        });

        Schema::table('comp', function (Blueprint $table): void {
            foreach ([
                'manager_ai_external_id',
                'manager_ai_source_url',
                'manager_ai_source_hash',
                'manager_ai_last_seen_at',
            ] as $column) {
                if (Schema::hasColumn('comp', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
