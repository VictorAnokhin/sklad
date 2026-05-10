<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->alignUsers();
        $this->alignDocument();
        $this->alignZDocument();
        $this->alignZBody();
        $this->alignComp();
        $this->alignPrice();
        $this->alignConf();
        $this->alignKurs();
    }

    public function down(): void
    {
        // Legacy schema alignment is intentionally one-way.
    }

    private function alignUsers(): void
    {
        if (!Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $stringColumns = [
                'firmuser' => 1,
                'firmuserall' => 1,
                'login' => 100,
                'pass' => 255,
                'ustype' => 20,
                'region' => 25,
                'city' => 50,
                'poshta' => 7,
                'direktor' => 40,
                'kod2' => 20,
                'pp' => 35,
                'bank' => 35,
                'mfo' => 6,
                'address' => 250,
                'phone2' => 15,
                'website' => 25,
            ];

            foreach ($stringColumns as $column => $length) {
                if (!Schema::hasColumn('users', $column)) {
                    $table->string($column, $length)->default('');
                }
            }

            $textColumns = ['user', 'description'];
            foreach ($textColumns as $column) {
                if (!Schema::hasColumn('users', $column)) {
                    $table->text($column)->nullable();
                }
            }

            $photoColumns = ['foto1', 'foto2', 'foto3', 'foto4', 'foto5'];
            foreach ($photoColumns as $column) {
                if (!Schema::hasColumn('users', $column)) {
                    $table->string($column, 200)->default('');
                }
            }

            $integerColumns = [
                'tgroup' => 0,
                'kassa' => 0,
                'sklad' => 0,
                'reestr' => 0,
                'firm' => 0,
                'msg' => 0,
                'docs' => 0,
                'userid' => 0,
            ];

            foreach ($integerColumns as $column => $default) {
                if (!Schema::hasColumn('users', $column)) {
                    $table->integer($column)->default($default);
                }
            }

            $decimalColumns = [
                'summa' => [12, 2],
                'balance' => [12, 2],
            ];

            foreach ($decimalColumns as $column => [$precision, $scale]) {
                if (!Schema::hasColumn('users', $column)) {
                    $table->decimal($column, $precision, $scale)->default(0);
                }
            }

            if (!Schema::hasColumn('users', 'date')) {
                $table->date('date')->nullable();
            }

            if (!Schema::hasColumn('users', 'time')) {
                $table->time('time')->nullable();
            }
        });
    }

    private function alignDocument(): void
    {
        if (!Schema::hasTable('document')) {
            return;
        }

        Schema::table('document', function (Blueprint $table) {
            if (!Schema::hasColumn('document', 'time2')) {
                $table->string('time2', 10)->default('');
            }

            if (!Schema::hasColumn('document', 'dt2')) {
                $table->unsignedBigInteger('dt2')->default(0);
            }
        });

        $this->runMysqlStatement("ALTER TABLE `document` MODIFY `docum` varchar(250) NOT NULL DEFAULT ''");
        $this->runMysqlStatement("ALTER TABLE `document` MODIFY `money` varchar(50) NOT NULL DEFAULT ''");
        $this->runMysqlStatement("ALTER TABLE `document` MODIFY `oplata` varchar(50) NOT NULL DEFAULT ''");
        $this->runMysqlStatement("ALTER TABLE `document` MODIFY `oplata2` varchar(50) NOT NULL DEFAULT ''");
        $this->runMysqlStatement("ALTER TABLE `document` MODIFY `sklads` varchar(50) NOT NULL DEFAULT ''");
        $this->runMysqlStatement("ALTER TABLE `document` MODIFY `reteil` varchar(50) NOT NULL DEFAULT ''");
        $this->runMysqlStatement("ALTER TABLE `document` MODIFY `reestr` varchar(50) NOT NULL DEFAULT ''");
        $this->runMysqlStatement("ALTER TABLE `document` MODIFY `typeproduct` varchar(50) NOT NULL DEFAULT ''");
        $this->runMysqlStatement("ALTER TABLE `document` MODIFY `status` varchar(50) NOT NULL DEFAULT ''");
    }

    private function alignZDocument(): void
    {
        if (!Schema::hasTable('z_document')) {
            return;
        }

        Schema::table('z_document', function (Blueprint $table) {
            if (!Schema::hasColumn('z_document', 'dt2')) {
                $table->unsignedBigInteger('dt2')->default(0);
            }
        });

        $this->runMysqlStatement('ALTER TABLE `z_document` MODIFY `docum` TEXT NOT NULL');
        $this->runMysqlStatement("ALTER TABLE `z_document` MODIFY `money` varchar(50) NOT NULL DEFAULT ''");
        $this->runMysqlStatement("ALTER TABLE `z_document` MODIFY `oplata` varchar(50) NOT NULL DEFAULT ''");
        $this->runMysqlStatement("ALTER TABLE `z_document` MODIFY `oplata2` varchar(50) NOT NULL DEFAULT ''");
        $this->runMysqlStatement("ALTER TABLE `z_document` MODIFY `sklads` varchar(50) NOT NULL DEFAULT ''");
        $this->runMysqlStatement("ALTER TABLE `z_document` MODIFY `reteil` varchar(50) NOT NULL DEFAULT ''");
        $this->runMysqlStatement("ALTER TABLE `z_document` MODIFY `reestr` varchar(50) NOT NULL DEFAULT ''");
        $this->runMysqlStatement("ALTER TABLE `z_document` MODIFY `typeproduct` varchar(50) NOT NULL DEFAULT ''");
        $this->runMysqlStatement("ALTER TABLE `z_document` MODIFY `status` varchar(50) NOT NULL DEFAULT ''");
    }

    private function alignZBody(): void
    {
        if (!Schema::hasTable('z_body')) {
            return;
        }

        Schema::table('z_body', function (Blueprint $table) {
            if (!Schema::hasColumn('z_body', 'pcod')) {
                $table->string('pcod', 25)->default('');
            }

            if (!Schema::hasColumn('z_body', 'pgarant')) {
                $table->smallInteger('pgarant')->default(0);
            }

            if (!Schema::hasColumn('z_body', 'pname')) {
                $table->string('pname', 250)->default('');
            }

            if (!Schema::hasColumn('z_body', 'zvalue')) {
                $table->string('zvalue', 200)->default('');
            }
        });

        $this->runMysqlStatement("ALTER TABLE `z_body` MODIFY `type` varchar(50) NOT NULL DEFAULT ''");
    }

    private function alignComp(): void
    {
        if (!Schema::hasTable('comp')) {
            return;
        }

        Schema::table('comp', function (Blueprint $table) {
            $stringColumns = [
                'artikul' => 10,
                'firstpage' => 2,
                'action' => 2,
                'web' => 1,
                'user' => 30,
            ];

            foreach ($stringColumns as $column => $length) {
                if (!Schema::hasColumn('comp', $column)) {
                    $table->string($column, $length)->default('');
                }
            }

            $textColumns = [
                'slogan',
                'description',
                'description_ua',
                'description_en',
                'full_description',
                'htmlname',
            ];

            foreach ($textColumns as $column) {
                if (!Schema::hasColumn('comp', $column)) {
                    $table->text($column)->nullable();
                }
            }

            $integerColumns = [
                'top5' => 0,
                'upd' => 1,
                'idsklad' => 0,
            ];

            foreach ($integerColumns as $column => $default) {
                if (!Schema::hasColumn('comp', $column)) {
                    $table->integer($column)->default($default);
                }
            }
        });

        $this->runMysqlStatement("ALTER TABLE `comp` MODIFY `dt` varchar(12) NOT NULL DEFAULT ''");
        $this->runMysqlStatement("ALTER TABLE `comp` MODIFY `top` int NOT NULL DEFAULT 0");
        $this->runMysqlStatement("ALTER TABLE `comp` MODIFY `nickname` varchar(50) NOT NULL DEFAULT ''");
        $this->runMysqlStatement("ALTER TABLE `comp` MODIFY `name_ua` varchar(150) NOT NULL DEFAULT ''");
        $this->runMysqlStatement("ALTER TABLE `comp` MODIFY `name_en` varchar(150) NOT NULL DEFAULT ''");
    }

    private function alignPrice(): void
    {
        if (!Schema::hasTable('price')) {
            return;
        }

        Schema::table('price', function (Blueprint $table) {
            if (!Schema::hasColumn('price', 'name')) {
                $table->text('name')->nullable();
            }

            if (!Schema::hasColumn('price', 'description')) {
                $table->text('description')->nullable();
            }

            if (!Schema::hasColumn('price', 'garant')) {
                $table->string('garant', 2)->default('');
            }

            if (!Schema::hasColumn('price', 'data')) {
                $table->string('data', 12)->default('');
            }
        });

        $this->runMysqlStatement("ALTER TABLE `price` MODIFY `count` decimal(12,3) NOT NULL DEFAULT 0.000");
    }

    private function alignConf(): void
    {
        if (!Schema::hasTable('conf')) {
            return;
        }

        Schema::table('conf', function (Blueprint $table) {
            $decimalColumns = ['value', 'value1', 'value2'];
            foreach ($decimalColumns as $column) {
                if (!Schema::hasColumn('conf', $column)) {
                    $table->decimal($column, 12, 2)->default(0);
                }
            }

            $integerColumns = [
                'users' => 0,
                'first' => 0,
                'userid' => 0,
                'registr' => 0,
            ];

            foreach ($integerColumns as $column => $default) {
                if (!Schema::hasColumn('conf', $column)) {
                    $table->integer($column)->default($default);
                }
            }

            $stringColumns = [
                'doc' => 50,
                'work' => 1,
            ];

            foreach ($stringColumns as $column => $length) {
                if (!Schema::hasColumn('conf', $column)) {
                    $table->string($column, $length)->default('');
                }
            }

            $textColumns = ['descript', 'descript2', 'descript3', 'descript4', 'descript5', 'htmlkeys'];
            foreach ($textColumns as $column) {
                if (!Schema::hasColumn('conf', $column)) {
                    $table->text($column)->nullable();
                }
            }
        });

        $this->runMysqlStatement("ALTER TABLE `conf` MODIFY `type` varchar(50) NOT NULL DEFAULT ''");
        $this->runMysqlStatement("ALTER TABLE `conf` MODIFY `name` varchar(200) NOT NULL DEFAULT ''");
        $this->runMysqlStatement("ALTER TABLE `conf` MODIFY `color` varchar(9) NOT NULL DEFAULT ''");
        $this->runMysqlStatement("ALTER TABLE `conf` MODIFY `status` int NOT NULL DEFAULT 0");
        $this->runMysqlStatement("ALTER TABLE `conf` MODIFY `hide` smallint NOT NULL DEFAULT 0");
        $this->runMysqlStatement("ALTER TABLE `conf` MODIFY `vision` char(1) NOT NULL DEFAULT '1'");
        $this->runMysqlStatement("ALTER TABLE `conf` MODIFY `constanta` char(1) NOT NULL DEFAULT ''");
    }

    private function alignKurs(): void
    {
        if (!Schema::hasTable('kurs')) {
            return;
        }

        Schema::table('kurs', function (Blueprint $table) {
            if (!Schema::hasColumn('kurs', 'kurs')) {
                $table->decimal('kurs', 6, 2)->default(0);
            }
        });

        $this->runMysqlStatement("ALTER TABLE `kurs` MODIFY `data` date NULL DEFAULT NULL");
    }

    private function runMysqlStatement(string $sql): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement($sql);
        }
    }
};
