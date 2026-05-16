<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('chat_messages')) {
            return;
        }

        if (Schema::hasColumn('chat_messages', 'fid')) {
            return;
        }

        Schema::table('chat_messages', function (Blueprint $table) {
            $table->integer('fid')->nullable()->index()->comment('ID проекта')->after('chat_session_id');
        });
    }

    public function down(): void
    {
        Schema::table('chat_messages', function (Blueprint $table) {
            $table->dropColumn('fid');
        });
    }
};
