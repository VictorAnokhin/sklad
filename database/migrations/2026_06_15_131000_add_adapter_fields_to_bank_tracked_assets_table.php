<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('bank_tracked_assets')) {
            return;
        }

        Schema::table('bank_tracked_assets', function (Blueprint $table) {
            if (! Schema::hasColumn('bank_tracked_assets', 'adapter')) {
                $table->string('adapter', 80)->default('manual')->after('asset_type');
            }
            if (! Schema::hasColumn('bank_tracked_assets', 'available_fields')) {
                $table->json('available_fields')->nullable()->after('last_payload');
            }
            if (! Schema::hasColumn('bank_tracked_assets', 'selected_fields')) {
                $table->json('selected_fields')->nullable()->after('available_fields');
            }
            if (! Schema::hasColumn('bank_tracked_assets', 'image_url')) {
                $table->text('image_url')->nullable()->after('selected_fields');
            }
            if (! Schema::hasColumn('bank_tracked_assets', 'external_url')) {
                $table->text('external_url')->nullable()->after('image_url');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('bank_tracked_assets')) {
            return;
        }

        Schema::table('bank_tracked_assets', function (Blueprint $table) {
            foreach (['external_url', 'image_url', 'selected_fields', 'available_fields', 'adapter'] as $column) {
                if (Schema::hasColumn('bank_tracked_assets', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
