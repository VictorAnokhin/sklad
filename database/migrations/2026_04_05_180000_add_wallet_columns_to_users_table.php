<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'wallet_address')) {
                $table->string('wallet_address', 80)->nullable()->after('email');
            }

            if (!Schema::hasColumn('users', 'wallet_network')) {
                $table->string('wallet_network', 80)->nullable()->after('wallet_address');
            }

            if (!Schema::hasColumn('users', 'wallet_connected_at')) {
                $table->timestamp('wallet_connected_at')->nullable()->after('wallet_network');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'wallet_connected_at')) {
                $table->dropColumn('wallet_connected_at');
            }

            if (Schema::hasColumn('users', 'wallet_network')) {
                $table->dropColumn('wallet_network');
            }

            if (Schema::hasColumn('users', 'wallet_address')) {
                $table->dropColumn('wallet_address');
            }
        });
    }
};
