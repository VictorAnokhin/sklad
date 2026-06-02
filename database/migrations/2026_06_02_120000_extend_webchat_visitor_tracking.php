<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('webchat_visitors', function (Blueprint $table) {
            if (! Schema::hasColumn('webchat_visitors', 'journey')) {
                $table->json('journey')->nullable()->after('counters');
            }
            if (! Schema::hasColumn('webchat_visitors', 'needs_summary')) {
                $table->json('needs_summary')->nullable()->after('journey');
            }
            if (! Schema::hasColumn('webchat_visitors', 'total_time_ms')) {
                $table->unsignedBigInteger('total_time_ms')->default(0)->after('needs_summary');
            }
            if (! Schema::hasColumn('webchat_visitors', 'last_ip_hash')) {
                $table->string('last_ip_hash', 64)->nullable()->index()->after('ip_hash');
            }
        });

        Schema::table('webchat_events', function (Blueprint $table) {
            if (! Schema::hasColumn('webchat_events', 'duration_ms')) {
                $table->unsignedInteger('duration_ms')->nullable()->after('language');
            }
            if (! Schema::hasColumn('webchat_events', 'ip_hash')) {
                $table->string('ip_hash', 64)->nullable()->index()->after('duration_ms');
            }
            if (! Schema::hasColumn('webchat_events', 'user_agent_hash')) {
                $table->string('user_agent_hash', 64)->nullable()->index()->after('ip_hash');
            }
        });
    }

    public function down(): void
    {
        Schema::table('webchat_events', function (Blueprint $table) {
            if (Schema::hasColumn('webchat_events', 'user_agent_hash')) {
                $table->dropIndex(['user_agent_hash']);
            }
            if (Schema::hasColumn('webchat_events', 'ip_hash')) {
                $table->dropIndex(['ip_hash']);
            }

            foreach (['user_agent_hash', 'ip_hash', 'duration_ms'] as $column) {
                if (Schema::hasColumn('webchat_events', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('webchat_visitors', function (Blueprint $table) {
            if (Schema::hasColumn('webchat_visitors', 'last_ip_hash')) {
                $table->dropIndex(['last_ip_hash']);
            }

            foreach (['last_ip_hash', 'total_time_ms', 'needs_summary', 'journey'] as $column) {
                if (Schema::hasColumn('webchat_visitors', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
