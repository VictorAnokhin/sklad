<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('fund_pools')) {
            return;
        }

        Schema::table('fund_pools', function (Blueprint $table) {
            if (! Schema::hasColumn('fund_pools', 'source_type')) {
                $table->string('source_type', 40)->default('manual')->after('notes');
            }
            if (! Schema::hasColumn('fund_pools', 'credit_request_status')) {
                $table->string('credit_request_status', 40)->default('')->after('source_type');
            }
            if (! Schema::hasColumn('fund_pools', 'borrower_address')) {
                $table->string('borrower_address', 80)->default('')->after('credit_request_status');
            }
            if (! Schema::hasColumn('fund_pools', 'collateral_kind')) {
                $table->string('collateral_kind', 20)->default('')->after('borrower_address');
            }
            if (! Schema::hasColumn('fund_pools', 'collateral_object_id')) {
                $table->string('collateral_object_id', 80)->default('')->after('collateral_kind');
            }
            if (! Schema::hasColumn('fund_pools', 'collateral_type')) {
                $table->string('collateral_type', 500)->default('')->after('collateral_object_id');
            }
            if (! Schema::hasColumn('fund_pools', 'collateral_label')) {
                $table->string('collateral_label', 255)->default('')->after('collateral_type');
            }
            if (! Schema::hasColumn('fund_pools', 'collateral_protocol')) {
                $table->string('collateral_protocol', 120)->default('')->after('collateral_label');
            }
            if (! Schema::hasColumn('fund_pools', 'collateral_image_url')) {
                $table->string('collateral_image_url', 500)->default('')->after('collateral_protocol');
            }
            if (! Schema::hasColumn('fund_pools', 'collateral_valuation')) {
                $table->string('collateral_valuation', 80)->default('')->after('collateral_image_url');
            }
            if (! Schema::hasColumn('fund_pools', 'collateral_status')) {
                $table->string('collateral_status', 80)->default('')->after('collateral_valuation');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('fund_pools')) {
            return;
        }

        Schema::table('fund_pools', function (Blueprint $table) {
            foreach ([
                'collateral_status',
                'collateral_valuation',
                'collateral_image_url',
                'collateral_protocol',
                'collateral_label',
                'collateral_type',
                'collateral_object_id',
                'collateral_kind',
                'borrower_address',
                'credit_request_status',
                'source_type',
            ] as $column) {
                if (Schema::hasColumn('fund_pools', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
