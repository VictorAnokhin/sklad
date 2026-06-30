<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->renameLoanDocuments([
            'ZOUT' => 'CRDT',
            'RO' => 'CRO',
            'PO' => 'CPO',
            'RN' => 'CPLAN',
            'RA' => 'CDOC',
        ]);
    }

    public function down(): void
    {
        $this->renameLoanDocuments([
            'CRDT' => 'ZOUT',
            'CRO' => 'RO',
            'CPO' => 'PO',
            'CPLAN' => 'RN',
            'CDOC' => 'RA',
        ]);
    }

    private function renameLoanDocuments(array $types): void
    {
        if (!Schema::hasTable('document')) {
            return;
        }

        $rootFrom = array_key_first($types);
        $rootTo = $types[$rootFrom];
        $loanIds = DB::table('document')
            ->where('type', $rootFrom)
            ->where(function ($query) {
                $query->where('typeproduct', 'credit_request')
                    ->orWhere('numorder', 'AV8-LOAN')
                    ->orWhere('content', 'like', '%[AV8_LOAN_REQUEST]%');
            })
            ->pluck('id');

        if ($loanIds->isEmpty()) {
            return;
        }

        DB::table('document')
            ->whereIn('id', $loanIds)
            ->where('type', $rootFrom)
            ->update([
                'type' => $rootTo,
                'typez' => $rootTo,
            ]);

        if (!Schema::hasTable('z_document')) {
            return;
        }

        foreach (array_slice($types, 1, null, true) as $from => $to) {
            DB::table('z_document')
                ->whereIn('docid', $loanIds)
                ->where('type', $from)
                ->update([
                    'type' => $to,
                    'typez' => $rootTo,
                ]);
        }
    }
};
