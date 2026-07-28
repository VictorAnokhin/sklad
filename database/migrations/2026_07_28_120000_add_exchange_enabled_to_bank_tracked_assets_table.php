<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('bank_tracked_assets') || Schema::hasColumn('bank_tracked_assets', 'exchange_enabled')) {
            return;
        }

        Schema::table('bank_tracked_assets', function (Blueprint $table): void {
            $table->boolean('exchange_enabled')->default(false)->after('hidden')->index();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('bank_tracked_assets') || ! Schema::hasColumn('bank_tracked_assets', 'exchange_enabled')) {
            return;
        }

        Schema::table('bank_tracked_assets', function (Blueprint $table): void {
            $table->dropColumn('exchange_enabled');
        });
    }
};
