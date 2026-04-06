<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('field')) {
            return;
        }

        Schema::table('field', function (Blueprint $table) {
            if (!Schema::hasColumn('field', 'valua')) {
                $table->text('valua')->nullable()->after('val');
            }
            if (!Schema::hasColumn('field', 'valen')) {
                $table->text('valen')->nullable()->after('valua');
            }
            if (!Schema::hasColumn('field', 'description')) {
                $table->text('description')->nullable()->after('valen');
            }
            if (!Schema::hasColumn('field', 'descriptionua')) {
                $table->text('descriptionua')->nullable()->after('description');
            }
            if (!Schema::hasColumn('field', 'descriptionen')) {
                $table->text('descriptionen')->nullable()->after('descriptionua');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('field')) {
            return;
        }

        Schema::table('field', function (Blueprint $table) {
            $dropColumns = [];
            foreach (['valua', 'valen', 'description', 'descriptionua', 'descriptionen'] as $column) {
                if (Schema::hasColumn('field', $column)) {
                    $dropColumns[] = $column;
                }
            }

            if (!empty($dropColumns)) {
                $table->dropColumn($dropColumns);
            }
        });
    }
};
