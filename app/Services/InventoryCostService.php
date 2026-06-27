<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class InventoryCostService
{
    private const EPSILON = 0.0005;

    public function post(object $document, Collection $lineItems, string $fid): Collection
    {
        $docType = strtoupper((string) ($document->type ?? ''));
        if (! in_array($docType, ['PN', 'RN'], true)) {
            return collect();
        }

        $warehouseId = (int) ($document->sklads ?? 0);
        if ($warehouseId <= 0) {
            throw new RuntimeException('Для проведення PN/RN потрібно вибрати склад.');
        }

        $lineItems = $this->validatedLines($lineItems);
        $sourceId = (string) ($document->id ?? '');
        $movementDate = $this->normalizeDate((string) ($document->data ?? ''));

        if ($this->activeMovements((int) $fid, $docType, $sourceId)->exists()) {
            throw new RuntimeException("Документ {$docType} вже має активні складські рухи.");
        }

        $movements = collect();
        foreach ($lineItems as $line) {
            $productId = trim((string) $line->pnum);
            $quantity = round((float) $line->pcount, 3);
            $balance = $this->lockBalance((int) $fid, $warehouseId, $productId);

            $latestDate = DB::table('inventory_cost_movements')
                ->where('company_id', (int) $fid)
                ->where('warehouse_id', $warehouseId)
                ->where('product_id', $productId)
                ->whereNull('reversed_at')
                ->max('movement_date');

            if ($latestDate !== null && $latestDate > $movementDate) {
                throw new RuntimeException(
                    "Не можна провести {$docType} заднім числом: для товару {$productId} є пізніший рух {$latestDate}."
                );
            }

            $beforeQuantity = round((float) $balance->quantity, 3);
            $beforeValue = round((float) $balance->total_value, 4);
            $beforeAverage = round((float) $balance->average_cost, 6);

            if ($docType === 'PN') {
                $unitCost = $this->purchaseUnitCost($line);
                $afterQuantity = round($beforeQuantity + $quantity, 3);

                if ($beforeQuantity <= self::EPSILON && $afterQuantity > self::EPSILON) {
                    $afterValue = round($afterQuantity * $unitCost, 4);
                } else {
                    $afterValue = round($beforeValue + ($quantity * $unitCost), 4);
                }
                $afterAverage = $afterQuantity > self::EPSILON
                    ? round($afterValue / $afterQuantity, 6)
                    : $unitCost;
                $direction = 'in';
            } else {
                if ($beforeQuantity + self::EPSILON < $quantity) {
                    throw new RuntimeException(
                        "Недостатньо товару {$productId} на складі: доступно {$beforeQuantity}, потрібно {$quantity}."
                    );
                }
                if ($beforeAverage <= 0) {
                    throw new RuntimeException("Для товару {$productId} не визначена середня собівартість.");
                }

                $unitCost = $beforeAverage;
                $afterQuantity = round($beforeQuantity - $quantity, 3);
                $afterValue = $afterQuantity <= self::EPSILON
                    ? 0.0
                    : round($beforeValue - ($quantity * $unitCost), 4);
                $afterAverage = $afterQuantity <= self::EPSILON
                    ? 0.0
                    : round($afterValue / $afterQuantity, 6);
                $direction = 'out';

                DB::table('z_body')
                    ->where('id', $line->id)
                    ->update(['zvalue' => number_format($unitCost, 6, '.', '')]);
                $line->zvalue = number_format($unitCost, 6, '.', '');
            }

            DB::table('inventory_cost_balances')
                ->where('id', $balance->id)
                ->update([
                    'quantity' => $afterQuantity,
                    'total_value' => $afterValue,
                    'average_cost' => $afterAverage,
                    'last_movement_date' => $movementDate,
                    'updated_at' => now(),
                ]);

            $movementId = DB::table('inventory_cost_movements')->insertGetId([
                'company_id' => (int) $fid,
                'warehouse_id' => $warehouseId,
                'product_id' => $productId,
                'source_type' => $docType,
                'source_id' => $sourceId,
                'line_id' => $line->id ?? null,
                'movement_date' => $movementDate,
                'direction' => $direction,
                'quantity' => $quantity,
                'unit_cost' => $unitCost,
                'total_cost' => round($quantity * $unitCost, 4),
                'quantity_before' => $beforeQuantity,
                'value_before' => $beforeValue,
                'average_cost_before' => $beforeAverage,
                'quantity_after' => $afterQuantity,
                'value_after' => $afterValue,
                'average_cost_after' => $afterAverage,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->setPhysicalStock((int) $fid, $warehouseId, $productId, $afterQuantity);
            $this->setReferenceCost((int) $fid, $productId, $afterAverage);
            $movements->push(DB::table('inventory_cost_movements')->where('id', $movementId)->first());
        }

        return $movements;
    }

    public function reverse(object $document, string $fid): Collection
    {
        $docType = strtoupper((string) ($document->type ?? ''));
        $sourceId = (string) ($document->id ?? '');
        $movements = $this->activeMovements((int) $fid, $docType, $sourceId)
            ->orderByDesc('id')
            ->lockForUpdate()
            ->get();

        if ($movements->isEmpty()) {
            throw new RuntimeException(
                "Для документа {$docType} немає активних складських рухів. "
                .'Документ міг бути проведений до запуску регістру собівартості та вже включений у початковий залишок.'
            );
        }

        foreach ($movements as $movement) {
            $hasLaterExternalMovement = DB::table('inventory_cost_movements')
                ->where('company_id', $movement->company_id)
                ->where('warehouse_id', $movement->warehouse_id)
                ->where('product_id', $movement->product_id)
                ->whereNull('reversed_at')
                ->where('id', '>', $movement->id)
                ->where(function ($query) use ($docType, $sourceId) {
                    $query->where('source_type', '<>', $docType)
                        ->orWhere('source_id', '<>', $sourceId);
                })
                ->exists();

            if ($hasLaterExternalMovement) {
                throw new RuntimeException(
                    "Неможливо скасувати {$docType}: після нього вже є рух товару {$movement->product_id}."
                );
            }
        }

        foreach ($movements as $movement) {
            $balance = $this->lockBalance(
                (int) $movement->company_id,
                (int) $movement->warehouse_id,
                (string) $movement->product_id
            );

            DB::table('inventory_cost_balances')
                ->where('id', $balance->id)
                ->update([
                    'quantity' => $movement->quantity_before,
                    'total_value' => $movement->value_before,
                    'average_cost' => $movement->average_cost_before,
                    'last_movement_date' => $this->previousMovementDate($movement),
                    'updated_at' => now(),
                ]);

            DB::table('inventory_cost_movements')
                ->where('id', $movement->id)
                ->update(['reversed_at' => now(), 'updated_at' => now()]);

            $this->setPhysicalStock(
                (int) $movement->company_id,
                (int) $movement->warehouse_id,
                (string) $movement->product_id,
                (float) $movement->quantity_before
            );
            $this->setReferenceCost(
                (int) $movement->company_id,
                (string) $movement->product_id,
                (float) $movement->average_cost_before
            );
        }

        return $movements;
    }

    public function postProjectMirror(object $document, Collection $lineItems, string $sourceCompanyId): Collection
    {
        $sourceDocType = strtoupper((string) ($document->type ?? ''));
        if (! in_array($sourceDocType, ['PN', 'RN'], true)) {
            return collect();
        }

        $targetCompanyId = $this->counterpartyProjectId($document, $sourceCompanyId);
        if ($targetCompanyId === null) {
            return collect();
        }

        $targetDocType = $sourceDocType === 'PN' ? 'RN' : 'PN';
        $sourceId = (string) ($document->id ?? '');
        $sourceType = "{$sourceDocType}:project-mirror";
        $movementDate = $this->normalizeDate((string) ($document->data ?? ''));
        $validatedLines = $this->validatedLines($lineItems);

        if ($this->activeMovements($targetCompanyId, $sourceType, $sourceId)->exists()) {
            throw new RuntimeException("Документ {$sourceDocType} вже має активні дзеркальні складські рухи.");
        }

        $movements = collect();
        foreach ($validatedLines as $line) {
            $sourceProductId = trim((string) $line->pnum);
            $targetProductId = $this->mappedProductId(
                (int) $sourceCompanyId,
                $sourceProductId,
                $targetCompanyId,
                (int) ($document->client1 ?? 0)
            );
            $quantity = round((float) $line->pcount, 3);
            $warehouseId = $this->resolveMirrorWarehouse(
                $targetCompanyId,
                $document,
                $targetDocType === 'RN' ? $targetProductId : null,
                $targetDocType === 'RN' ? $quantity : null
            );
            $balance = $this->lockBalance($targetCompanyId, $warehouseId, $targetProductId);

            $latestDate = DB::table('inventory_cost_movements')
                ->where('company_id', $targetCompanyId)
                ->where('warehouse_id', $warehouseId)
                ->where('product_id', $targetProductId)
                ->whereNull('reversed_at')
                ->max('movement_date');

            if ($latestDate !== null && $latestDate > $movementDate) {
                throw new RuntimeException(
                    "Не можна провести дзеркальний {$targetDocType} заднім числом: для товару {$targetProductId} є пізніший рух {$latestDate}."
                );
            }

            $beforeQuantity = round((float) $balance->quantity, 3);
            $beforeValue = round((float) $balance->total_value, 4);
            $beforeAverage = round((float) $balance->average_cost, 6);

            if ($targetDocType === 'PN') {
                $unitCost = $this->purchaseUnitCost($line);
                $afterQuantity = round($beforeQuantity + $quantity, 3);
                $afterValue = $beforeQuantity <= self::EPSILON && $afterQuantity > self::EPSILON
                    ? round($afterQuantity * $unitCost, 4)
                    : round($beforeValue + ($quantity * $unitCost), 4);
                $afterAverage = $afterQuantity > self::EPSILON
                    ? round($afterValue / $afterQuantity, 6)
                    : $unitCost;
                $direction = 'in';
            } else {
                if ($beforeQuantity + self::EPSILON < $quantity) {
                    throw new RuntimeException(
                        "Недостатньо товару {$targetProductId} у проекті {$targetCompanyId}: доступно {$beforeQuantity}, потрібно {$quantity}. "
                        ."Перевірте маппінг товарів для {$sourceProductId}."
                    );
                }
                if ($beforeAverage <= 0) {
                    throw new RuntimeException("Для товару {$targetProductId} у проекті {$targetCompanyId} не визначена середня собівартість.");
                }

                $unitCost = $beforeAverage;
                $afterQuantity = round($beforeQuantity - $quantity, 3);
                $afterValue = $afterQuantity <= self::EPSILON
                    ? 0.0
                    : round($beforeValue - ($quantity * $unitCost), 4);
                $afterAverage = $afterQuantity <= self::EPSILON
                    ? 0.0
                    : round($afterValue / $afterQuantity, 6);
                $direction = 'out';
            }

            DB::table('inventory_cost_balances')
                ->where('id', $balance->id)
                ->update([
                    'quantity' => $afterQuantity,
                    'total_value' => $afterValue,
                    'average_cost' => $afterAverage,
                    'last_movement_date' => $movementDate,
                    'updated_at' => now(),
                ]);

            $movementId = DB::table('inventory_cost_movements')->insertGetId([
                'company_id' => $targetCompanyId,
                'warehouse_id' => $warehouseId,
                'product_id' => $targetProductId,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'line_id' => $line->id ?? null,
                'movement_date' => $movementDate,
                'direction' => $direction,
                'quantity' => $quantity,
                'unit_cost' => $unitCost,
                'total_cost' => round($quantity * $unitCost, 4),
                'quantity_before' => $beforeQuantity,
                'value_before' => $beforeValue,
                'average_cost_before' => $beforeAverage,
                'quantity_after' => $afterQuantity,
                'value_after' => $afterValue,
                'average_cost_after' => $afterAverage,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->setPhysicalStock($targetCompanyId, $warehouseId, $targetProductId, $afterQuantity);
            $this->setReferenceCost($targetCompanyId, $targetProductId, $afterAverage);
            $movements->push(DB::table('inventory_cost_movements')->where('id', $movementId)->first());
        }

        return $movements;
    }

    public function reverseProjectMirror(object $document, string $sourceCompanyId): Collection
    {
        $sourceDocType = strtoupper((string) ($document->type ?? ''));
        if (! in_array($sourceDocType, ['PN', 'RN'], true)) {
            return collect();
        }

        $targetCompanyId = $this->counterpartyProjectId($document, $sourceCompanyId);
        if ($targetCompanyId === null) {
            return collect();
        }

        $sourceType = "{$sourceDocType}:project-mirror";
        $sourceId = (string) ($document->id ?? '');
        $movements = $this->activeMovements($targetCompanyId, $sourceType, $sourceId)
            ->orderByDesc('id')
            ->lockForUpdate()
            ->get();

        if ($movements->isEmpty()) {
            return collect();
        }

        foreach ($movements as $movement) {
            $hasLaterExternalMovement = DB::table('inventory_cost_movements')
                ->where('company_id', $movement->company_id)
                ->where('warehouse_id', $movement->warehouse_id)
                ->where('product_id', $movement->product_id)
                ->whereNull('reversed_at')
                ->where('id', '>', $movement->id)
                ->where(function ($query) use ($sourceType, $sourceId) {
                    $query->where('source_type', '<>', $sourceType)
                        ->orWhere('source_id', '<>', $sourceId);
                })
                ->exists();

            if ($hasLaterExternalMovement) {
                throw new RuntimeException(
                    "Неможливо скасувати дзеркальний рух: після нього вже є рух товару {$movement->product_id}."
                );
            }
        }

        foreach ($movements as $movement) {
            $balance = $this->lockBalance(
                (int) $movement->company_id,
                (int) $movement->warehouse_id,
                (string) $movement->product_id
            );

            DB::table('inventory_cost_balances')
                ->where('id', $balance->id)
                ->update([
                    'quantity' => $movement->quantity_before,
                    'total_value' => $movement->value_before,
                    'average_cost' => $movement->average_cost_before,
                    'last_movement_date' => $this->previousMovementDate($movement),
                    'updated_at' => now(),
                ]);

            DB::table('inventory_cost_movements')
                ->where('id', $movement->id)
                ->update(['reversed_at' => now(), 'updated_at' => now()]);

            $this->setPhysicalStock(
                (int) $movement->company_id,
                (int) $movement->warehouse_id,
                (string) $movement->product_id,
                (float) $movement->quantity_before
            );
            $this->setReferenceCost(
                (int) $movement->company_id,
                (string) $movement->product_id,
                (float) $movement->average_cost_before
            );
        }

        return $movements;
    }

    public function attachLedgerTransaction(Collection $movements, ?int $transactionId): void
    {
        if ($transactionId === null || $movements->isEmpty()) {
            return;
        }

        DB::table('inventory_cost_movements')
            ->whereIn('id', $movements->pluck('id')->all())
            ->update(['ledger_transaction_id' => $transactionId, 'updated_at' => now()]);
    }

    private function validatedLines(Collection $lineItems): Collection
    {
        $lines = $lineItems
            ->filter(fn ($line) => trim((string) ($line->pnum ?? '')) !== '')
            ->values();

        if ($lines->isEmpty()) {
            throw new RuntimeException('Документ не містить товарних позицій.');
        }

        foreach ($lines as $line) {
            if ((float) ($line->pcount ?? 0) <= 0) {
                throw new RuntimeException("Кількість товару {$line->pnum} повинна бути більшою за нуль.");
            }
        }

        return $lines;
    }

    private function purchaseUnitCost(object $line): float
    {
        $quantity = (float) ($line->pcount ?? 0);
        $lineTotal = (float) ($line->psumma ?? 0);
        $unitCost = $lineTotal > 0 && $quantity > 0
            ? $lineTotal / $quantity
            : (float) ($line->pprice ?? 0);

        $unitCost = round($unitCost, 6);
        if ($unitCost <= 0) {
            throw new RuntimeException("Для товару {$line->pnum} потрібно вказати закупівельну ціну.");
        }

        return $unitCost;
    }

    private function mappedProductId(
        int $sourceCompanyId,
        string $sourceProductId,
        int $targetCompanyId,
        int $counterpartyUserId = 0
    ): string
    {
        if (! Schema::hasTable('product_project_mappings')) {
            throw new RuntimeException('Таблиця маппінгу товарів product_project_mappings не створена.');
        }

        $query = DB::table('product_project_mappings')
            ->where('source_company_id', $sourceCompanyId)
            ->where('source_product_id', $sourceProductId)
            ->where('target_company_id', $targetCompanyId);

        if (Schema::hasColumn('product_project_mappings', 'counterparty_user_id')) {
            $query->whereIn('counterparty_user_id', [$counterpartyUserId, 0])
                ->orderByRaw('CASE WHEN counterparty_user_id = ? THEN 0 ELSE 1 END', [$counterpartyUserId]);
        }

        $targetProductId = $query
            ->value('target_product_id');

        if ($targetProductId === null || trim((string) $targetProductId) === '') {
            throw new RuntimeException(
                "Не знайдено маппінг товару {$sourceProductId}: проект {$sourceCompanyId}, контрагент {$counterpartyUserId} -> проект {$targetCompanyId}."
            );
        }

        $exists = DB::table('comp')
            ->where('firma', $targetCompanyId)
            ->where('id', $targetProductId)
            ->exists();

        if (! $exists) {
            throw new RuntimeException(
                "Маппінг товару {$sourceProductId} вказує на неіснуючий товар {$targetProductId} проекту {$targetCompanyId}."
            );
        }

        return (string) $targetProductId;
    }

    private function counterpartyProjectId(object $document, string $sourceCompanyId): ?int
    {
        if (! Schema::hasColumn('users', 'project_id')) {
            return null;
        }

        $counterpartyId = trim((string) ($document->client1 ?? ''));
        if ($counterpartyId === '' || $counterpartyId === '0') {
            return null;
        }

        $projectId = DB::table('users')
            ->where('id', $counterpartyId)
            ->where('firma', $sourceCompanyId)
            ->value('project_id');

        if ($projectId === null || (string) $projectId === (string) $sourceCompanyId) {
            return null;
        }

        return DB::table('project')->where('id', $projectId)->exists()
            ? (int) $projectId
            : null;
    }

    private function resolveMirrorWarehouse(
        int $targetCompanyId,
        object $document,
        ?string $outgoingProductId = null,
        ?float $outgoingQuantity = null
    ): int
    {
        $candidateWarehouseIds = collect();

        if (Schema::hasColumn('conf', 'is_default')) {
            $warehouseId = DB::table('conf')
                ->where('type', 'sklads')
                ->where('firma', $targetCompanyId)
                ->where('is_default', 1)
                ->orderBy('id')
                ->value('id');

            if ($warehouseId !== null && (int) $warehouseId > 0) {
                $candidateWarehouseIds->push((int) $warehouseId);
            }
        }

        $sourceWarehouseId = (int) ($document->sklads ?? 0);
        if ($sourceWarehouseId > 0) {
            $sameWarehouseExists = DB::table('conf')
                ->where('id', $sourceWarehouseId)
                ->where('type', 'sklads')
                ->where('firma', $targetCompanyId)
                ->exists();

            if ($sameWarehouseExists) {
                $candidateWarehouseIds->push($sourceWarehouseId);
            }
        }

        $candidateWarehouseIds = $candidateWarehouseIds->merge(DB::table('conf')
            ->where('type', 'sklads')
            ->where('firma', $targetCompanyId)
            ->orderBy('id')
            ->pluck('id')
            ->map(fn ($id) => (int) $id));
        $candidateWarehouseIds = $candidateWarehouseIds->merge(DB::table('price_sklad')
            ->where('firma', $targetCompanyId)
            ->orderBy('sklad')
            ->pluck('sklad')
            ->map(fn ($id) => (int) $id))
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();

        if ($outgoingProductId !== null && $outgoingQuantity !== null) {
            foreach ($candidateWarehouseIds as $candidateWarehouseId) {
                $available = DB::table('inventory_cost_balances')
                    ->where('company_id', $targetCompanyId)
                    ->where('warehouse_id', $candidateWarehouseId)
                    ->where('product_id', $outgoingProductId)
                    ->value('quantity');

                if ($available === null) {
                    $available = DB::table('price_sklad')
                        ->where('firma', $targetCompanyId)
                        ->where('sklad', $candidateWarehouseId)
                        ->where('pnum', $outgoingProductId)
                        ->sum('count');
                }

                if ((float) $available + self::EPSILON >= $outgoingQuantity) {
                    return $candidateWarehouseId;
                }
            }

            throw new RuntimeException(
                "Недостатньо товару {$outgoingProductId} у проекті {$targetCompanyId}: "
                ."на жодному складі немає {$outgoingQuantity}."
            );
        }

        if ($candidateWarehouseIds->isNotEmpty()) {
            return (int) $candidateWarehouseIds->first();
        }

        throw new RuntimeException("Для проекту {$targetCompanyId} не знайдено склад для дзеркальної проводки.");
    }

    private function lockBalance(int $companyId, int $warehouseId, string $productId): object
    {
        $balance = DB::table('inventory_cost_balances')
            ->where('company_id', $companyId)
            ->where('warehouse_id', $warehouseId)
            ->where('product_id', $productId)
            ->lockForUpdate()
            ->first();

        if ($balance) {
            return $balance;
        }

        $physicalQuantity = (float) (DB::table('price_sklad')
            ->where('firma', $companyId)
            ->where('sklad', $warehouseId)
            ->where('pnum', $productId)
            ->sum('count') ?? 0);
        $costColumn = Schema::hasColumn('price', 'pay0') ? 'pay0' : 'pay';
        $openingCost = (float) (DB::table('price')
            ->where('firma', $companyId)
            ->where('pnum', $productId)
            ->value($costColumn) ?? 0);

        DB::table('inventory_cost_balances')->insertOrIgnore([
            'company_id' => $companyId,
            'warehouse_id' => $warehouseId,
            'product_id' => $productId,
            'quantity' => round($physicalQuantity, 3),
            'total_value' => round($physicalQuantity * $openingCost, 4),
            'average_cost' => round($openingCost, 6),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return DB::table('inventory_cost_balances')
            ->where('company_id', $companyId)
            ->where('warehouse_id', $warehouseId)
            ->where('product_id', $productId)
            ->lockForUpdate()
            ->first();
    }

    private function setPhysicalStock(int $companyId, int $warehouseId, string $productId, float $quantity): void
    {
        DB::table('price_sklad')->updateOrInsert(
            [
                'firma' => $companyId,
                'sklad' => $warehouseId,
                'pnum' => $productId,
            ],
            ['count' => round($quantity, 3)]
        );
    }

    private function setReferenceCost(int $companyId, string $productId, float $averageCost): void
    {
        $costColumn = Schema::hasColumn('price', 'pay0') ? 'pay0' : 'pay';

        DB::table('price')
            ->where('firma', $companyId)
            ->where('pnum', $productId)
            ->update([$costColumn => max(0, round($averageCost, 6))]);
    }

    private function activeMovements(int $companyId, string $sourceType, string $sourceId)
    {
        return DB::table('inventory_cost_movements')
            ->where('company_id', $companyId)
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->whereNull('reversed_at');
    }

    private function previousMovementDate(object $movement): ?string
    {
        return DB::table('inventory_cost_movements')
            ->where('company_id', $movement->company_id)
            ->where('warehouse_id', $movement->warehouse_id)
            ->where('product_id', $movement->product_id)
            ->whereNull('reversed_at')
            ->where('id', '<', $movement->id)
            ->max('movement_date');
    }

    private function normalizeDate(string $date): string
    {
        if (preg_match('/^\d{2}-\d{2}-\d{4}$/', $date) === 1) {
            [$day, $month, $year] = explode('-', $date);
            return "{$year}-{$month}-{$day}";
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) === 1) {
            return $date;
        }

        return now()->toDateString();
    }
}
