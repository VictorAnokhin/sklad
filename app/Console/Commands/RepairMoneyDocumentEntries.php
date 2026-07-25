<?php

namespace App\Console\Commands;

use App\Services\AccountingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RepairMoneyDocumentEntries extends Command
{
    private const MONEY_TYPES = ['PO', 'CPO', 'PPO', 'RO', 'CRO', 'PRO', 'ZP'];

    protected $signature = 'accounting:repair-money-entries {--fid=} {--type=*} {--dry-run}';

    protected $description = 'Create missing accounting entries for posted money documents.';

    public function handle(AccountingService $accounting): int
    {
        if (! Schema::hasTable('z_document') || ! Schema::hasTable('transactions') || ! Schema::hasTable('entries')) {
            $this->error('Required tables z_document, transactions or entries are missing.');

            return self::FAILURE;
        }

        $fid = trim((string) $this->option('fid'));
        $dryRun = (bool) $this->option('dry-run');
        $created = 0;
        $missing = 0;
        $failed = 0;

        $types = collect((array) $this->option('type'))
            ->flatMap(fn ($value) => explode(',', (string) $value))
            ->map(fn ($value) => strtoupper(trim($value)))
            ->filter()
            ->values()
            ->all();
        $types = $types === [] ? self::MONEY_TYPES : array_values(array_intersect($types, self::MONEY_TYPES));

        if ($types === []) {
            $this->error('No supported document types selected.');

            return self::FAILURE;
        }

        $query = DB::table('z_document')
            ->whereIn('type', $types)
            ->where('provodka', 1)
            ->orderBy('firma')
            ->orderBy('id');

        if ($fid !== '') {
            $query->where('firma', $fid);
        }

        $query->chunkById(200, function ($documents) use ($accounting, $dryRun, &$created, &$missing, &$failed): void {
            foreach ($documents as $document) {
                $referenceType = $this->primaryReferenceType((string) $document->type);
                $referenceId = (string) $document->id;
                $companyId = (int) $document->firma;

                if ($this->hasLedgerEntries((string) $document->type, $referenceId, $companyId)) {
                    continue;
                }

                $missing++;
                if ($dryRun) {
                    $sum = number_format((float) ($document->summa ?? 0), 2, '.', ' ');
                    $this->line("Missing entries: {$document->firma} {$document->type} #{$document->id} {$sum}");
                    continue;
                }

                $transaction = $accounting->createDocumentTransaction(
                    $referenceType,
                    $referenceId,
                    (string) $document->type,
                    $document,
                    [],
                    (string) $document->firma,
                    false
                );

                if ($transaction === null) {
                    $failed++;
                    $this->warn("Failed: {$document->firma} {$document->type} #{$document->id}");
                    continue;
                }

                $created++;
                $this->line("Created entries: {$document->firma} {$document->type} #{$document->id}");
            }
        }, 'id');

        $this->info("Missing: {$missing}. Created: {$created}. Failed: {$failed}.");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function primaryReferenceType(string $documentType): string
    {
        return in_array($documentType, ['PPO', 'PRO'], true)
            ? 'z_document:money_order'
            : "z_document:{$documentType}";
    }

    private function referenceTypes(string $documentType): array
    {
        $primary = $this->primaryReferenceType($documentType);

        return array_values(array_unique([
            $primary,
            "z_document:{$documentType}",
            'z_document:money_order',
        ]));
    }

    private function hasLedgerEntries(string $documentType, string $referenceId, int $companyId): bool
    {
        return DB::table('transactions as t')
            ->join('entries as e', 'e.transaction_id', '=', 't.id')
            ->where('t.company_id', $companyId)
            ->whereIn('t.reference_type', $this->referenceTypes($documentType))
            ->where('t.reference_id', $referenceId)
            ->exists();
    }
}
