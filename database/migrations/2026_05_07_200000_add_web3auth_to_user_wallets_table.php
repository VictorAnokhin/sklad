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

        if (! Schema::hasColumn('user_wallets', 'web3auth')) {
            Schema::table('user_wallets', function (Blueprint $table) {
                $table->unsignedTinyInteger('web3auth')->default(0)->after('network');
            });
        }

        if (! Schema::hasTable('zklogin_identities')) {
            return;
        }

        $rows = DB::table('zklogin_identities')
            ->whereNotNull('wallet_address')
            ->where('wallet_address', '!=', '')
            ->get(['user_id', 'wallet_address']);

        foreach ($rows as $row) {
            $norm = strtolower(trim((string) $row->wallet_address));
            if ($norm === '') {
                continue;
            }

            DB::table('user_wallets')
                ->where('user_id', $row->user_id)
                ->whereRaw('LOWER(TRIM(address)) = ?', [$norm])
                ->update(['web3auth' => 1]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('user_wallets')) {
            return;
        }

        if (Schema::hasColumn('user_wallets', 'web3auth')) {
            Schema::table('user_wallets', function (Blueprint $table) {
                $table->dropColumn('web3auth');
            });
        }
    }
};
