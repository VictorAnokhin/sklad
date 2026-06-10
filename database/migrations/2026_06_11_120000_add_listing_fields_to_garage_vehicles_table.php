<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('garage_vehicles', function (Blueprint $table) {
            if (!Schema::hasColumn('garage_vehicles', 'garage_photo_1')) {
                $table->string('garage_photo_1', 500)->nullable()->after('photo_url');
            }

            if (!Schema::hasColumn('garage_vehicles', 'garage_photo_2')) {
                $table->string('garage_photo_2', 500)->nullable();
            }

            if (!Schema::hasColumn('garage_vehicles', 'garage_photo_3')) {
                $table->string('garage_photo_3', 500)->nullable();
            }

            if (!Schema::hasColumn('garage_vehicles', 'garage_photo_4')) {
                $table->string('garage_photo_4', 500)->nullable();
            }

            if (!Schema::hasColumn('garage_vehicles', 'garage_photo_5')) {
                $table->string('garage_photo_5', 500)->nullable();
            }

            if (!Schema::hasColumn('garage_vehicles', 'vehicle_price')) {
                $table->decimal('vehicle_price', 12, 2)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('garage_vehicles', function (Blueprint $table) {
            foreach (['vehicle_price', 'garage_photo_5', 'garage_photo_4', 'garage_photo_3', 'garage_photo_2', 'garage_photo_1'] as $column) {
                if (Schema::hasColumn('garage_vehicles', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
