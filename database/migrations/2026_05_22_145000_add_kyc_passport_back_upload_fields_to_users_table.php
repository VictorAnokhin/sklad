<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $after = 'kyc_passport_uploaded_at';

            if (! Schema::hasColumn('users', 'kyc_passport_back_file_path')) {
                $table->string('kyc_passport_back_file_path', 255)->default('')->after($after);
                $after = 'kyc_passport_back_file_path';
            } else {
                $after = 'kyc_passport_back_file_path';
            }

            if (! Schema::hasColumn('users', 'kyc_passport_back_file_name')) {
                $table->string('kyc_passport_back_file_name', 255)->default('')->after($after);
                $after = 'kyc_passport_back_file_name';
            } else {
                $after = 'kyc_passport_back_file_name';
            }

            if (! Schema::hasColumn('users', 'kyc_passport_back_file_mime')) {
                $table->string('kyc_passport_back_file_mime', 80)->default('')->after($after);
                $after = 'kyc_passport_back_file_mime';
            } else {
                $after = 'kyc_passport_back_file_mime';
            }

            if (! Schema::hasColumn('users', 'kyc_passport_back_file_size')) {
                $table->unsignedBigInteger('kyc_passport_back_file_size')->default(0)->after($after);
                $after = 'kyc_passport_back_file_size';
            } else {
                $after = 'kyc_passport_back_file_size';
            }

            if (! Schema::hasColumn('users', 'kyc_passport_back_uploaded_at')) {
                $table->timestamp('kyc_passport_back_uploaded_at')->nullable()->after($after);
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            foreach ([
                'kyc_passport_back_uploaded_at',
                'kyc_passport_back_file_size',
                'kyc_passport_back_file_mime',
                'kyc_passport_back_file_name',
                'kyc_passport_back_file_path',
            ] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
