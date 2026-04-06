<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('user_wallets')) {
            Schema::create('user_wallets', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->string('address', 80)->unique();
                $table->string('network', 80)->nullable();
                $table->timestamp('connected_at')->nullable();
                $table->timestamps();

                $table->index('user_id');
            });
        }

        if (
            Schema::hasTable('users')
            && Schema::hasColumn('users', 'wallet_address')
            && Schema::hasColumn('users', 'wallet_network')
            && Schema::hasColumn('users', 'wallet_connected_at')
        ) {
            DB::table('users')
                ->select('id', 'wallet_address', 'wallet_network', 'wallet_connected_at')
                ->whereNotNull('wallet_address')
                ->where('wallet_address', '!=', '')
                ->orderBy('id')
                ->chunkById(200, function ($users) {
                    foreach ($users as $user) {
                        DB::table('user_wallets')->updateOrInsert(
                            ['address' => strtolower((string) $user->wallet_address)],
                            [
                                'user_id' => $user->id,
                                'network' => $user->wallet_network,
                                'connected_at' => $user->wallet_connected_at,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]
                        );
                    }
                });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('user_wallets');
    }
};
