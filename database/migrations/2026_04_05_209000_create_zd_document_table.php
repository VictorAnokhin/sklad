<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('zd_document')) {
            return;
        }

        Schema::create('zd_document', function (Blueprint $table) {
            $table->id();
            $table->string('data', 14)->default('0');
            $table->string('data2', 14)->default('');
            $table->time('time')->nullable();
            $table->text('time2')->nullable();
            $table->unsignedBigInteger('num')->default(0);
            $table->unsignedBigInteger('client1')->default(0);
            $table->unsignedBigInteger('client2')->default(0);
            $table->text('content')->nullable();
            $table->string('type', 5)->default('');
            $table->decimal('summa', 12, 2)->default(0);
            $table->float('summa2')->default(0);
            $table->decimal('discount', 4, 2)->default(0);
            $table->unsignedInteger('firma')->default(0);
            $table->string('user', 20)->default('0');
            $table->unsignedInteger('schet')->default(0);
            $table->string('provodka', 5)->default('0');
            $table->unsignedBigInteger('close')->default(0);
            $table->unsignedBigInteger('dt')->default(0);
            $table->unsignedBigInteger('dt2')->default(0);
            $table->unsignedBigInteger('numz')->default(0);
            $table->string('typez', 10)->default('');
            $table->string('status', 50)->default('');
            $table->string('money', 50)->default('');
            $table->string('reteil', 50)->default('');
            $table->string('docum', 220)->default('');
            $table->char('sms_flag', 1)->default('');
            $table->string('oplata', 50)->default('');
            $table->string('oplata2', 50)->default('');
            $table->string('sklads', 50)->default('');
            $table->string('typeproduct', 50)->default('');
            $table->string('reestr', 50)->default('');
            $table->string('manager', 50)->default('');
            $table->unsignedTinyInteger('dostup')->default(0);
            $table->unsignedBigInteger('docid')->default(0);
            $table->float('bonus')->default(0);
            $table->char('work', 1)->default('');

            $table->index(['firma', 'type', 'dt']);
            $table->index('docid');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('zd_document');
    }
};
