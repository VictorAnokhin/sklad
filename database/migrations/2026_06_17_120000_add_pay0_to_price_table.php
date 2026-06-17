<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('price')) {
            return;
        }

        Schema::table('price', function (Blueprint $table) {
            if (! Schema::hasColumn('price', 'pay0')) {
                $table->decimal('pay0', 14, 6)->default(0)->after('pay');
            }
        });

        if (Schema::hasColumn('price', 'pay') && Schema::hasColumn('price', 'pay0')) {
            DB::table('price')
                ->where(function ($query): void {
                    $query->whereNull('pay0')->orWhere('pay0', 0);
                })
                ->update(['pay0' => DB::raw('COALESCE(pay, 0)')]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('price') || ! Schema::hasColumn('price', 'pay0')) {
            return;
        }

        Schema::table('price', function (Blueprint $table) {
            $table->dropColumn('pay0');
        });
    }
};
