<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('fund_share_settings')) {
            Schema::table('fund_share_settings', function (Blueprint $table) {
                if (! Schema::hasColumn('fund_share_settings', 'share_fee_config_id')) {
                    $table->string('share_fee_config_id', 80)->default('')->after('share_config_id');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('fund_share_settings')) {
            Schema::table('fund_share_settings', function (Blueprint $table) {
                if (Schema::hasColumn('fund_share_settings', 'share_fee_config_id')) {
                    $table->dropColumn('share_fee_config_id');
                }
            });
        }
    }
};
