<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Добавляет поле firma (ID компании) в таблицу chat_sessions.
     */
    public function up(): void
    {
        if (!Schema::hasTable('chat_sessions')) {
            return;
        }

        if (Schema::hasColumn('chat_sessions', 'firma')) {
            return;
        }

        Schema::table('chat_sessions', function (Blueprint $table) {
            $table->integer('firma')->nullable()->index()->after('fid')
                ->comment('ID компании (фирмы), к которой относится сессия');
        });
    }

    public function down(): void
    {
        Schema::table('chat_sessions', function (Blueprint $table) {
            $table->dropColumn('firma');
        });
    }
};
