<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('bank_stock_analyses')) {
            Schema::table('bank_stock_analyses', function (Blueprint $table) {
                if (! Schema::hasColumn('bank_stock_analyses', 'adapter')) {
                    $table->string('adapter', 80)->default('manual')->after('ticker');
                }
                if (! Schema::hasColumn('bank_stock_analyses', 'adapter_config')) {
                    $table->json('adapter_config')->nullable()->after('adapter');
                }
                if (! Schema::hasColumn('bank_stock_analyses', 'last_payload')) {
                    $table->json('last_payload')->nullable()->after('adapter_config');
                }
                if (! Schema::hasColumn('bank_stock_analyses', 'sync_status')) {
                    $table->string('sync_status', 80)->nullable()->after('last_payload');
                }
                if (! Schema::hasColumn('bank_stock_analyses', 'sync_error')) {
                    $table->text('sync_error')->nullable()->after('sync_status');
                }
                if (! Schema::hasColumn('bank_stock_analyses', 'last_synced_at')) {
                    $table->timestamp('last_synced_at')->nullable()->after('sync_error');
                }
            });
        }

        if (! Schema::hasTable('bank_stock_analysis_snapshots')) {
            Schema::create('bank_stock_analysis_snapshots', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('stock_analysis_id')->index();
                $table->unsignedBigInteger('project_id')->default(0)->index();
                $table->string('ticker', 20)->index();
                $table->date('snapshot_date')->index();
                $table->string('adapter', 80)->default('manual');
                $table->string('price', 80)->nullable();
                $table->string('change_percent', 80)->nullable();
                $table->string('volume', 80)->nullable();
                $table->json('payload');
                $table->json('changed_fields')->nullable();
                $table->timestamps();

                $table->unique(['stock_analysis_id', 'snapshot_date'], 'bank_stock_snapshots_stock_date_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_stock_analysis_snapshots');

        if (Schema::hasTable('bank_stock_analyses')) {
            Schema::table('bank_stock_analyses', function (Blueprint $table) {
                foreach (['last_synced_at', 'sync_error', 'sync_status', 'last_payload', 'adapter_config', 'adapter'] as $column) {
                    if (Schema::hasColumn('bank_stock_analyses', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
