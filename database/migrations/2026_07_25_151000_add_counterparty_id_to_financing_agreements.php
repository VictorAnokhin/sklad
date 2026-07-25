<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('financing_agreements') && ! Schema::hasColumn('financing_agreements', 'counterparty_id')) {
            Schema::table('financing_agreements', function (Blueprint $table) {
                $table->unsignedBigInteger('counterparty_id')->nullable()->index()->after('fid');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('financing_agreements') && Schema::hasColumn('financing_agreements', 'counterparty_id')) {
            Schema::table('financing_agreements', function (Blueprint $table) {
                $table->dropColumn('counterparty_id');
            });
        }
    }
};
