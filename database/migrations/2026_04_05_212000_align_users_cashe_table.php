<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users_cashe', function (Blueprint $table) {
            $columns = [
                'firma' => fn(Blueprint $t) => $t->integer('firma')->default(0),
                'user_id' => fn(Blueprint $t) => $t->integer('user_id')->default(0),
                'top' => fn(Blueprint $t) => $t->integer('top')->default(0),
                'doc' => fn(Blueprint $t) => $t->string('doc', 20)->default(''),
                'data' => fn(Blueprint $t) => $t->string('data', 30)->default(''),
                'num' => fn(Blueprint $t) => $t->integer('num')->default(0),
            ];

            foreach ($columns as $name => $definition) {
                if (!Schema::hasColumn('users_cashe', $name)) {
                    $definition($table);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('users_cashe', function (Blueprint $table) {
            foreach (['firma', 'user_id', 'top', 'doc', 'data', 'num'] as $column) {
                if (Schema::hasColumn('users_cashe', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
