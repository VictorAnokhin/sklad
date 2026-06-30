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
            ['code' => '37'],
            [
                'name' => 'Расчеты с разными дебиторами',
                'type' => 'asset',
                'parent_id' => null,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        $parentId = DB::table('accounts')->where('code', '37')->value('id');

        DB::table('accounts')->updateOrInsert(
            ['code' => '377'],
            [
                'name' => 'Кредитование (выданные кредиты)',
                'type' => 'asset',
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

        DB::table('accounts')->where('code', 'like', '377.%')->delete();
        DB::table('accounts')->where('code', '377')->delete();
        DB::table('accounts')->where('code', '37')->delete();
    }
};
