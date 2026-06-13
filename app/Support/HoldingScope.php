<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class HoldingScope
{
    /**
     * @return list<string>
     */
    public static function projectIdsFor(mixed $fid): array
    {
        $current = trim((string) $fid);
        if ($current === '') {
            return [];
        }

        if (! Schema::hasTable('project') || ! Schema::hasColumn('project', 'holding_id')) {
            return [$current];
        }

        $holdingId = DB::table('project')
            ->where('id', $current)
            ->value('holding_id');

        if (! $holdingId) {
            return [$current];
        }

        $projectIds = DB::table('project')
            ->where('holding_id', $holdingId)
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();

        return $projectIds !== [] ? $projectIds : [$current];
    }
}
