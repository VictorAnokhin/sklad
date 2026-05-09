<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('user_wallets')) {
            return;
        }

        Schema::table('user_wallets', function (Blueprint $table) {
            $table->dropUnique(['address']);
        });

        DB::table('user_wallets')->whereNull('network')->update(['network' => 'eth']);

        Schema::table('user_wallets', function (Blueprint $table) {
            $table->unique(['user_id', 'address', 'network'], 'user_wallets_user_address_network_unique');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('user_wallets')) {
            return;
        }

        Schema::table('user_wallets', function (Blueprint $table) {
            $table->dropUnique('user_wallets_user_address_network_unique');
        });

        Schema::table('user_wallets', function (Blueprint $table) {
            $table->unique('address');
        });
    }
};
