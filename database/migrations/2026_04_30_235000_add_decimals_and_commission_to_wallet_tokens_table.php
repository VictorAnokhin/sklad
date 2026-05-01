<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('wallet_tokens')) {
            return;
        }

        Schema::table('wallet_tokens', function (Blueprint $table) {
            if (! Schema::hasColumn('wallet_tokens', 'decimals')) {
                $table->unsignedInteger('decimals')->default(18)->after('name');
            }

            if (! Schema::hasColumn('wallet_tokens', 'commission')) {
                $table->decimal('commission', 8, 4)->default(0)->after('is_selected');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('wallet_tokens')) {
            return;
        }

        Schema::table('wallet_tokens', function (Blueprint $table) {
            if (Schema::hasColumn('wallet_tokens', 'commission')) {
                $table->dropColumn('commission');
            }

            if (Schema::hasColumn('wallet_tokens', 'decimals')) {
                $table->dropColumn('decimals');
            }
        });
    }
};
