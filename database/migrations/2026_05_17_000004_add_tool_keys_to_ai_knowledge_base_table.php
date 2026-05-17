<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_knowledge_base', function (Blueprint $table) {
            $table->json('tool_keys')->nullable()->after('source');
        });
    }

    public function down(): void
    {
        Schema::table('ai_knowledge_base', function (Blueprint $table) {
            $table->dropColumn('tool_keys');
        });
    }
};
