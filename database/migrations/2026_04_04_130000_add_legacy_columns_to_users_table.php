<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add legacy columns that existed in the original workspace users table
 * but are NOT part of the default Laravel users table.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $columns = [
                ['fid', fn(Blueprint $t) => $t->string('fid', 20)->default('')->after('email_verified_at')],
                ['phone', fn(Blueprint $t) => $t->string('phone', 20)->default('')->after('fid')],
                ['phone1', fn(Blueprint $t) => $t->string('phone1', 20)->default('')->after('phone')],
                ['name', fn(Blueprint $t) => $t->string('name', 255)->default('')->after('phone1')],
                ['secondname', fn(Blueprint $t) => $t->string('secondname', 255)->default('')->after('name')],
                ['fathername', fn(Blueprint $t) => $t->string('fathername', 255)->default('')->after('secondname')],
                ['orgname', fn(Blueprint $t) => $t->string('orgname', 255)->default('')->after('fathername')],
                ['kod1', fn(Blueprint $t) => $t->string('kod1', 20)->default('')->after('orgname')],
                ['name2', fn(Blueprint $t) => $t->string('name2', 255)->default('')->after('kod1')],
                ['city', fn(Blueprint $t) => $t->string('city', 100)->default('')->after('name2')],
                ['region', fn(Blueprint $t) => $t->string('region', 100)->default('')->after('city')],
                ['poshta', fn(Blueprint $t) => $t->string('poshta', 50)->default('')->after('region')],
                ['idstatus', fn(Blueprint $t) => $t->tinyInteger('idstatus')->default(1)->after('poshta')],
                ['idkassa', fn(Blueprint $t) => $t->string('idkassa', 20)->default('')->after('fid')],
                ['idsklad', fn(Blueprint $t) => $t->string('idsklad', 20)->default('')->after('idkassa')],
                ['idreestr', fn(Blueprint $t) => $t->string('idreestr', 20)->default('')->after('idsklad')],
                ['domen', fn(Blueprint $t) => $t->string('domen', 100)->default('')->after('idreestr')],
                ['bonus', fn(Blueprint $t) => $t->decimal('bonus', 10, 2)->default(0)->after('domen')],
                ['balans', fn(Blueprint $t) => $t->decimal('balans', 12, 2)->default(0)->after('bonus')],
                ['top', fn(Blueprint $t) => $t->tinyInteger('top')->default(0)->after('balans')],
                ['hbd', fn(Blueprint $t) => $t->text('hbd')->nullable()->after('top')],
            ];

            foreach ($columns as [$colName, $fn]) {
                if (!Schema::hasColumn('users', $colName)) {
                    $fn($table);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $dropColumns = [
                'fid', 'phone', 'phone1', 'name', 'secondname', 'fathername',
                'orgname', 'kod1', 'name2', 'city', 'region', 'poshta',
                'idstatus', 'idkassa', 'idsklad', 'idreestr', 'domen',
                'bonus', 'balans', 'top', 'hbd',
            ];

            foreach ($dropColumns as $col) {
                if (Schema::hasColumn('users', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
