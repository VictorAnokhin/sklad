<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'kyc_provider')) {
                $table->string('kyc_provider', 40)->default('')->after('wallet_connected_at');
            }
            if (! Schema::hasColumn('users', 'kyc_status')) {
                $table->string('kyc_status', 40)->default('not_started')->after('kyc_provider');
            }
            if (! Schema::hasColumn('users', 'kyc_applicant_id')) {
                $table->string('kyc_applicant_id', 120)->default('')->after('kyc_status');
            }
            if (! Schema::hasColumn('users', 'kyc_level_name')) {
                $table->string('kyc_level_name', 120)->default('')->after('kyc_applicant_id');
            }
            if (! Schema::hasColumn('users', 'kyc_verified_at')) {
                $table->timestamp('kyc_verified_at')->nullable()->after('kyc_level_name');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            foreach (['kyc_verified_at', 'kyc_level_name', 'kyc_applicant_id', 'kyc_status', 'kyc_provider'] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
