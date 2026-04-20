<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conf', function (Blueprint $table) {
            $table->decimal('last_balance', 36, 18)->nullable()->after('constanta');
            $table->decimal('last_price', 24, 8)->nullable()->after('last_balance');
            $table->timestamp('last_updated_at')->nullable()->after('last_price');
        });
    }

    public function down(): void
    {
        Schema::table('conf', function (Blueprint $table) {
            $table->dropColumn(['last_balance', 'last_price', 'last_updated_at']);
        });
    }
};