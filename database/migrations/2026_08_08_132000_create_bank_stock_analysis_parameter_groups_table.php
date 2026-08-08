<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('bank_stock_analysis_parameter_groups')) {
            return;
        }

        Schema::create('bank_stock_analysis_parameter_groups', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_id')->default(0)->index();
            $table->string('name', 160);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['project_id', 'name']);
        });

        if (Schema::hasTable('bank_stock_analysis_parameters') && Schema::hasColumn('bank_stock_analysis_parameters', 'group_name')) {
            $now = now();
            DB::table('bank_stock_analysis_parameters')
                ->select('project_id', 'group_name')
                ->whereNotNull('group_name')
                ->where('group_name', '<>', '')
                ->distinct()
                ->orderBy('project_id')
                ->orderBy('group_name')
                ->get()
                ->each(function ($row, int $index) use ($now): void {
                    DB::table('bank_stock_analysis_parameter_groups')->insertOrIgnore([
                        'project_id' => (int) $row->project_id,
                        'name' => (string) $row->group_name,
                        'sort_order' => $index * 10,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_stock_analysis_parameter_groups');
    }
};
