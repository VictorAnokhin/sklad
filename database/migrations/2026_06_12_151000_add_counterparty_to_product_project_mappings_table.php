<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('product_project_mappings')) {
            return;
        }

        Schema::table('product_project_mappings', function (Blueprint $table) {
            if (! Schema::hasColumn('product_project_mappings', 'counterparty_user_id')) {
                $table->unsignedBigInteger('counterparty_user_id')->default(0)->after('source_company_id');
            }
        });

        $this->dropIndexIfExists('product_project_mappings', 'product_project_mappings_source_unique');
        $this->addIndexIfMissing(
            'product_project_mappings',
            'product_project_mappings_counterparty_index',
            'INDEX',
            '(`source_company_id`, `counterparty_user_id`)'
        );
        $this->addIndexIfMissing(
            'product_project_mappings',
            'product_project_mappings_source_unique',
            'UNIQUE',
            '(`source_company_id`, `counterparty_user_id`, `source_product_id`, `target_company_id`)'
        );
    }

    public function down(): void
    {
        if (! Schema::hasTable('product_project_mappings')) {
            return;
        }

        $this->dropIndexIfExists('product_project_mappings', 'product_project_mappings_source_unique');
        $this->dropIndexIfExists('product_project_mappings', 'product_project_mappings_counterparty_index');
        $this->addIndexIfMissing(
            'product_project_mappings',
            'product_project_mappings_source_unique',
            'UNIQUE',
            '(`source_company_id`, `source_product_id`, `target_company_id`)'
        );

        Schema::table('product_project_mappings', function (Blueprint $table) {
            if (Schema::hasColumn('product_project_mappings', 'counterparty_user_id')) {
                $table->dropColumn('counterparty_user_id');
            }
        });
    }

    private function addIndexIfMissing(string $table, string $name, string $type, string $columns): void
    {
        if ($this->indexExists($table, $name)) {
            return;
        }

        DB::statement("ALTER TABLE `{$table}` ADD {$type} `{$name}` {$columns}");
    }

    private function dropIndexIfExists(string $table, string $name): void
    {
        if (! $this->indexExists($table, $name)) {
            return;
        }

        DB::statement("ALTER TABLE `{$table}` DROP INDEX `{$name}`");
    }

    private function indexExists(string $table, string $name): bool
    {
        return collect(DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$name]))->isNotEmpty();
    }
};
