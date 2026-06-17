<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: all tables that exist in the legacy project.
 * Run: php artisan migrate
 *
 * NOTE: If migrating FROM existing legacy DB → do NOT run this migration.
 * Instead set DB_DATABASE= to existing database and run only seeders.
 * This file is for fresh installs only.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── document (ZIN / ZOUT) ─────────────────────────────────────────────
        Schema::create('document', function (Blueprint $t) {
            $t->id();
            $t->string('num', 20)->default('');
            $t->string('type', 10)->default('');
            $t->string('firma', 20)->default('');
            $t->string('client1', 20)->default('0');
            $t->string('client2', 20)->default('0');
            $t->decimal('summa', 12, 2)->default(0);
            $t->decimal('summa2', 12, 2)->default(0);
            $t->decimal('summa3', 12, 2)->default(0);
            $t->decimal('discount', 5, 2)->default(0);
            $t->string('status', 20)->default('0');
            $t->string('data', 20)->default('');
            $t->string('data2', 20)->default('');
            $t->string('time', 10)->default('');
            $t->unsignedBigInteger('dt')->default(0);
            $t->text('manager')->nullable();
            $t->text('user')->nullable();
            $t->text('content')->nullable();
            $t->string('ttn', 50)->default('');
            $t->string('oplata', 20)->default('');
            $t->string('oplata2', 20)->default('');
            $t->string('sklads', 20)->default('');
            $t->string('reteil', 20)->default('');
            $t->string('reestr', 20)->default('');
            $t->string('numz', 20)->default('0');
            $t->string('typez', 10)->default('');
            $t->string('docid', 20)->default('0');
            $t->string('docum', 20)->default('');
            $t->tinyInteger('provodka')->default(0);
            $t->tinyInteger('dostup')->default(1);
            $t->string('work', 10)->default('');
            $t->string('money', 20)->default('');
            $t->decimal('bonus', 10, 2)->default(0);
            $t->string('numdoc', 20)->default('');
            $t->string('numorder', 50)->default('');
            $t->tinyInteger('close')->default(0);
            $t->string('sms_flag', 5)->default('0');
            $t->string('typeproduct', 20)->default('');
            $t->string('schet', 20)->default('');
            $t->index(['firma', 'type', 'dt']);
            $t->index(['client1', 'firma']);
        });

        // ── z_document (all other doc types) ─────────────────────────────────
        Schema::create('z_document', function (Blueprint $t) {
            $t->id();
            $t->string('num', 20)->default('');
            $t->string('type', 10)->default('');
            $t->string('firma', 20)->default('');
            $t->string('client1', 20)->default('0');
            $t->string('client2', 20)->default('0');
            $t->decimal('summa', 12, 2)->default(0);
            $t->decimal('summa2', 12, 2)->default(0);
            $t->decimal('summa3', 12, 2)->default(0);
            $t->decimal('discount', 5, 2)->default(0);
            $t->string('status', 20)->default('0');
            $t->string('data', 20)->default('');
            $t->string('data2', 20)->default('');
            $t->string('time', 10)->default('');
            $t->unsignedBigInteger('dt')->default(0);
            $t->text('manager')->nullable();
            $t->text('user')->nullable();
            $t->text('content')->nullable();
            $t->string('ttn', 50)->default('');
            $t->string('oplata', 20)->default('');
            $t->string('oplata2', 20)->default('');
            $t->string('sklads', 20)->default('');
            $t->string('reteil', 20)->default('');
            $t->string('reestr', 20)->default('');
            $t->string('numz', 20)->default('0');
            $t->string('typez', 10)->default('');
            $t->string('docid', 20)->default('0');
            $t->text('docum');
            $t->tinyInteger('provodka')->default(0);
            $t->tinyInteger('dostup')->default(1);
            $t->string('work', 10)->default('');
            $t->string('money', 20)->default('');
            $t->decimal('bonus', 10, 2)->default(0);
            $t->string('numdoc', 20)->default('');
            $t->string('numorder', 50)->default('');
            $t->tinyInteger('close')->default(0);
            $t->string('sms_flag', 5)->default('0');
            $t->string('typeproduct', 20)->default('');
            $t->string('schet', 20)->default('');
            $t->index(['firma', 'type', 'dt']);
            $t->index(['docid', 'firma']);
        });

        // ── z_body (line items) ───────────────────────────────────────────────
        Schema::create('z_body', function (Blueprint $t) {
            $t->id();
            $t->string('docnum', 20)->default('');
            $t->string('pid', 20)->default('');
            $t->string('pnum', 20)->default('');
            $t->decimal('pcount', 10, 3)->default(1);
            $t->decimal('pprice', 12, 2)->default(0);
            $t->decimal('psumma', 12, 2)->default(0);
            $t->string('type', 10)->default('');
            $t->string('firma', 20)->default('');
            $t->string('docid', 20)->default('0');
            $t->index(['docid', 'firma']);
            $t->index(['pnum', 'firma']);
        });

        // ── comp (product catalog) ────────────────────────────────────────────
        Schema::create('comp', function (Blueprint $t) {
            $t->id();
            $t->string('cod', 30)->default('');
            $t->tinyInteger('hit')->default(0);
            $t->tinyInteger('constanta')->default(0);
            $t->string('firma', 20)->default('');
            $t->string('firma_share', 20)->default('');
            $t->tinyInteger('top')->default(0);
            $t->text('nickname')->nullable();
            $t->string('idtype', 20)->default('');
            $t->string('idcaption', 20)->default('');
            $t->string('idglava', 20)->default('');
            $t->text('namedoc')->nullable();
            $t->text('name')->nullable();
            $t->text('name_ua')->nullable();
            $t->text('name_en')->nullable();
            $t->text('htmldescr')->nullable();
            $t->text('htmlkeys')->nullable();
            $t->text('htmlkeyspop')->nullable();
            $t->decimal('pay1', 10, 2)->default(0);
            $t->decimal('pay', 10, 2)->default(0);
            $t->decimal('profitpay', 10, 2)->default(0);
            $t->integer('count')->default(0);
            $t->tinyInteger('hand')->default(0);
            $t->string('param1')->default('');
            $t->string('param2')->default('');
            $t->string('param3')->default('');
            $t->string('param4')->default('');
            $t->string('param5')->default('');
            $t->string('param6')->default('');
            $t->string('paramfix1')->default('');
            $t->string('paramfix2')->default('');
            $t->string('paramfix3')->default('');
            $t->string('paramfix4')->default('');
            $t->string('garant', 50)->default('');
            $t->string('flag', 10)->default('');
            $t->tinyInteger('sklad')->default(0);
            $t->string('nfoto')->default('');
            $t->string('nfoto1')->default('');
            $t->string('nfoto2')->default('');
            $t->string('nfoto3')->default('');
            $t->string('nfoto4')->default('');
            $t->string('nfoto5')->default('');
            $t->string('nfoto6')->default('');
            $t->string('nfoto7')->default('');
            $t->string('nfoto8')->default('');
            $t->string('nfoto9')->default('');
            $t->string('nfile')->default('');
            $t->string('nvideo1')->default('');
            $t->string('nvideo2')->default('');
            $t->timestamp('dt')->nullable();
            $t->index(['firma', 'idcaption', 'sklad']);
        });

        // ── price ─────────────────────────────────────────────────────────────
        Schema::create('price', function (Blueprint $t) {
            $t->id();
            $t->string('pnum', 20)->default('');
            $t->string('cod', 30)->default('');
            $t->string('firma', 20)->default('');
            $t->string('tgroup', 20)->default('');
            $t->string('idagent', 20)->default('');
            $t->decimal('pay', 10, 2)->default(0);
            $t->decimal('pay0', 14, 6)->default(0);
            $t->decimal('pay1', 10, 2)->default(0);
            $t->decimal('oldpay', 10, 2)->default(0);
            $t->integer('count')->default(0);
            $t->tinyInteger('sklad')->default(0);
            $t->index(['pnum', 'firma']);
            $t->index(['cod', 'idagent']);
        });

        // ── conf (classifiers) ────────────────────────────────────────────────
        Schema::create('conf', function (Blueprint $t) {
            $t->id();
            $t->string('type', 30)->default('');
            $t->text('name')->nullable();
            $t->string('color', 20)->default('');
            $t->string('status', 5)->default('1');
            $t->string('firma', 20)->default('');
            $t->string('constanta', 2)->default('0');
            $t->string('vision', 2)->default('1');
            $t->string('hide', 2)->default('0');
            $t->index(['type', 'firma']);
        });

        // ── kassa (cash registers) ────────────────────────────────────────────
        Schema::create('kassa', function (Blueprint $t) {
            $t->id();
            $t->text('name')->nullable();
            $t->decimal('balance', 12, 2)->default(0);
            $t->string('firma', 20)->default('');
        });

        // ── kurs (currency rates) ─────────────────────────────────────────────
        Schema::create('kurs', function (Blueprint $t) {
            $t->id();
            $t->decimal('usd', 8, 4)->default(0);
            $t->decimal('eur', 8, 4)->default(0);
            $t->date('data');
            $t->string('firma', 20)->default('');
        });

        // ── users_cashe (balance cache) ───────────────────────────────────────
        Schema::create('users_cashe', function (Blueprint $t) {
            $t->id();
            $t->string('userid', 20);
            $t->decimal('balance', 12, 2)->default(0);
            $t->unique('userid');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users_cashe');
        Schema::dropIfExists('kurs');
        Schema::dropIfExists('kassa');
        Schema::dropIfExists('conf');
        Schema::dropIfExists('price');
        Schema::dropIfExists('comp');
        Schema::dropIfExists('z_body');
        Schema::dropIfExists('z_document');
        Schema::dropIfExists('document');
    }
};
