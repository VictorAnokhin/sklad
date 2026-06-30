<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('accounts')) {
            return;
        }

        DB::table('accounts')->updateOrInsert(
            ['code' => '73'],
            [
                'name' => 'Прочие финансовые доходы',
                'type' => 'income',
                'parent_id' => null,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        $parentId = DB::table('accounts')->where('code', '73')->value('id');

        DB::table('accounts')->updateOrInsert(
            ['code' => '732'],
            [
                'name' => 'Процентный доход по кредитованию',
                'type' => 'income',
                'parent_id' => $parentId,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }

    public function down(): void
    {
        if (! Schema::hasTable('accounts')) {
            return;
        }

        DB::table('accounts')->where('code', 'like', '732.%')->delete();
        DB::table('accounts')->where('code', '732')->delete();
        DB::table('accounts')->where('code', '73')->delete();
    }
};
