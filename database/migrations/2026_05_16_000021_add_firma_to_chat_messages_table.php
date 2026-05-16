<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Добавляет поле firma (ID компании) в таблицу chat_messages.
     */
    public function up(): void
    {
        if (!Schema::hasTable('chat_messages')) {
            return;
        }

        if (Schema::hasColumn('chat_messages', 'firma')) {
            return;
        }

        Schema::table('chat_messages', function (Blueprint $table) {
            $table->integer('firma')->nullable()->index()->after('fid')
                ->comment('ID компании (фирмы), контекст сообщения');
        });
    }

    public function down(): void
    {
        Schema::table('chat_messages', function (Blueprint $table) {
            $table->dropColumn('firma');
        });
    }
};
