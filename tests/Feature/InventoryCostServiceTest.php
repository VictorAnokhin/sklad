<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\Money;
use App\Models\Report;
use App\Services\InventoryCostService;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class InventoryCostServiceTest extends TestCase
{
    private int $companyId;
    private int $warehouseId;
    private string $productId;

    protected function setUp(): void
    {
        parent::setUp();

        DB::beginTransaction();
        $this->companyId = random_int(800000, 899999);
        $this->warehouseId = random_int(8000, 8999);
        $this->productId = 'w' . bin2hex(random_bytes(4));

        DB::table('price')->insert([
            'pnum' => $this->productId,
            'firma' => $this->companyId,
            'pay' => 10,
        ]);
        DB::table('price_sklad')->insert([
            'pnum' => $this->productId,
            'firma' => $this->companyId,
            'sklad' => $this->warehouseId,
            'count' => 10,
        ]);
        DB::table('inventory_cost_balances')->insert([
            'company_id' => $this->companyId,
            'warehouse_id' => $this->warehouseId,
            'product_id' => $this->productId,
            'quantity' => 10,
            'total_value' => 100,
            'average_cost' => 10,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    public function test_weighted_average_receipt_issue_and_reversal(): void
    {
        $service = app(InventoryCostService::class);
        $pnLine = $this->createLine('PN', 5, 16);
        $pn = $this->document('PN', 910001);

        $service->post($pn, collect([$pnLine]), (string) $this->companyId);

        $balance = $this->balance();
        $this->assertEqualsWithDelta(15, (float) $balance->quantity, 0.0001);
        $this->assertEqualsWithDelta(180, (float) $balance->total_value, 0.0001);
        $this->assertEqualsWithDelta(12, (float) $balance->average_cost, 0.000001);
        $this->assertEqualsWithDelta(12, $this->referenceCost(), 0.000001);

        $rnLine = $this->createLine('RN', 4, 25);
        $rn = $this->document('RN', 910002);
        $service->post($rn, collect([$rnLine]), (string) $this->companyId);

        $balance = $this->balance();
        $this->assertEqualsWithDelta(11, (float) $balance->quantity, 0.0001);
        $this->assertEqualsWithDelta(132, (float) $balance->total_value, 0.0001);
        $this->assertEqualsWithDelta(12, (float) $balance->average_cost, 0.000001);
        $this->assertEqualsWithDelta(12, $this->referenceCost(), 0.000001);
        $this->assertEqualsWithDelta(
            12,
            (float) DB::table('z_body')->where('id', $rnLine->id)->value('zvalue'),
            0.000001
        );

        $service->reverse($rn, (string) $this->companyId);
        $balance = $this->balance();
        $this->assertEqualsWithDelta(15, (float) $balance->quantity, 0.0001);
        $this->assertEqualsWithDelta(180, (float) $balance->total_value, 0.0001);

        $service->reverse($pn, (string) $this->companyId);
        $balance = $this->balance();
        $this->assertEqualsWithDelta(10, (float) $balance->quantity, 0.0001);
        $this->assertEqualsWithDelta(100, (float) $balance->total_value, 0.0001);
        $this->assertEqualsWithDelta(10, (float) $balance->average_cost, 0.000001);
        $this->assertEqualsWithDelta(10, $this->referenceCost(), 0.000001);
    }

    public function test_issue_cannot_create_negative_stock(): void
    {
        $service = app(InventoryCostService::class);
        $rnLine = $this->createLine('RN', 11, 25);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Недостатньо товару');

        $service->post(
            $this->document('RN', 920001),
            collect([$rnLine]),
            (string) $this->companyId
        );
    }

    public function test_earlier_movement_is_blocked_after_later_movement(): void
    {
        $service = app(InventoryCostService::class);
        $service->post(
            $this->document('PN', 930001, '2026-06-12'),
            collect([$this->createLine('PN', 1, 10)]),
            (string) $this->companyId
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('заднім числом');

        $service->post(
            $this->document('PN', 930002, '2026-06-11'),
            collect([$this->createLine('PN', 1, 10)]),
            (string) $this->companyId
        );
    }

    public function test_pn_and_rn_create_balanced_ledger_entries_with_weighted_cost(): void
    {
        $pnId = $this->createDocument('PN', 5, 16);
        $pnResult = Document::provodka((string) $pnId, 'PN', (string) $this->companyId);

        $this->assertTrue($pnResult['isPosted']);
        $pnTransaction = DB::table('transactions')
            ->where('reference_type', 'z_document:PN')
            ->where('reference_id', (string) $pnId)
            ->first();
        $this->assertNotNull($pnTransaction);
        $this->assertLedgerIsBalanced((int) $pnTransaction->id, 80);
        $this->assertAccountEntry((int) $pnTransaction->id, "281.{$this->companyId}", 80, 0);
        $this->assertAccountEntry((int) $pnTransaction->id, "631.{$this->companyId}.generic", 0, 80);

        $rnId = $this->createDocument('RN', 4, 25);
        $rnResult = Document::provodka((string) $rnId, 'RN', (string) $this->companyId);

        $this->assertTrue($rnResult['isPosted']);
        $rnTransaction = DB::table('transactions')
            ->where('reference_type', 'z_document:RN')
            ->where('reference_id', (string) $rnId)
            ->first();
        $this->assertNotNull($rnTransaction);
        $this->assertLedgerIsBalanced((int) $rnTransaction->id, 148);
        $this->assertAccountEntry((int) $rnTransaction->id, "361.{$this->companyId}.generic", 100, 0);
        $this->assertAccountEntry((int) $rnTransaction->id, "701.{$this->companyId}", 0, 100);
        $this->assertAccountEntry((int) $rnTransaction->id, "902.{$this->companyId}", 48, 0);
        $this->assertAccountEntry((int) $rnTransaction->id, "281.{$this->companyId}", 0, 48);

        $reverseResult = Document::provodka((string) $rnId, 'RN', (string) $this->companyId);
        $this->assertFalse($reverseResult['isPosted']);
        $this->assertEqualsWithDelta(15, (float) $this->balance()->quantity, 0.0001);
        $this->assertEqualsWithDelta(180, (float) $this->balance()->total_value, 0.0001);
    }

    public function test_document_cannot_be_reversed_after_later_product_movement(): void
    {
        $service = app(InventoryCostService::class);
        $firstDocument = $this->document('PN', 940001, '2026-06-11');
        $service->post(
            $firstDocument,
            collect([$this->createLine('PN', 1, 10)]),
            (string) $this->companyId
        );
        $service->post(
            $this->document('PN', 940002, '2026-06-12'),
            collect([$this->createLine('PN', 1, 10)]),
            (string) $this->companyId
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('після нього вже є рух');

        $service->reverse($firstDocument, (string) $this->companyId);
    }

    public function test_pn_and_rn_create_and_reverse_project_mirror_transactions(): void
    {
        $projectId = DB::table('project')->insertGetId([
            'name' => 'Mirror project',
        ]);
        $counterpartyId = DB::table('users')->insertGetId([
            'name' => 'Mirror counterparty',
            'email' => "mirror-{$this->companyId}@example.test",
            'password' => password_hash('test-password', PASSWORD_BCRYPT),
            'firma' => (string) $this->companyId,
            'project_id' => $projectId,
        ]);
        [$targetProductId, $targetWarehouseId] = $this->createProductProjectMapping($projectId, $counterpartyId);

        $rnId = $this->createDocument('RN', 4, 25, $counterpartyId);
        Document::provodka((string) $rnId, 'RN', (string) $this->companyId);

        $rnMirror = $this->projectMirrorTransaction($rnId, 'RN', $projectId);
        $this->assertNotNull($rnMirror);
        $this->assertLedgerIsBalanced((int) $rnMirror->id, 100);
        $this->assertAccountEntry((int) $rnMirror->id, "281.{$projectId}", 100, 0);
        $this->assertAccountEntry(
            (int) $rnMirror->id,
            "631.{$projectId}.company-{$this->companyId}",
            0,
            100
        );
        $this->assertMirrorStock($projectId, $targetWarehouseId, $targetProductId, 14, 200, 14.285714);

        Document::provodka((string) $rnId, 'RN', (string) $this->companyId);
        $rnReversal = $this->projectMirrorReversal($rnId, 'RN', $projectId);
        $this->assertNotNull($rnReversal);
        $this->assertAccountEntry((int) $rnReversal->id, "281.{$projectId}", 0, 100);
        $this->assertAccountEntry(
            (int) $rnReversal->id,
            "631.{$projectId}.company-{$this->companyId}",
            100,
            0
        );
        $this->assertMirrorStock($projectId, $targetWarehouseId, $targetProductId, 10, 100, 10);

        $pnId = $this->createDocument('PN', 5, 16, $counterpartyId);
        Document::provodka((string) $pnId, 'PN', (string) $this->companyId);

        $pnMirror = $this->projectMirrorTransaction($pnId, 'PN', $projectId);
        $this->assertNotNull($pnMirror);
        $this->assertLedgerIsBalanced((int) $pnMirror->id, 80);
        $this->assertAccountEntry(
            (int) $pnMirror->id,
            "361.{$projectId}.company-{$this->companyId}",
            80,
            0
        );
        $this->assertAccountEntry((int) $pnMirror->id, "701.{$projectId}", 0, 80);
        $this->assertMirrorStock($projectId, $targetWarehouseId, $targetProductId, 5, 50, 10);

        Document::provodka((string) $pnId, 'PN', (string) $this->companyId);
        $pnReversal = $this->projectMirrorReversal($pnId, 'PN', $projectId);
        $this->assertNotNull($pnReversal);
        $this->assertAccountEntry(
            (int) $pnReversal->id,
            "361.{$projectId}.company-{$this->companyId}",
            0,
            80
        );
        $this->assertAccountEntry((int) $pnReversal->id, "701.{$projectId}", 80, 0);
        $this->assertMirrorStock($projectId, $targetWarehouseId, $targetProductId, 10, 100, 10);
    }

    public function test_pn_mirror_uses_warehouse_that_has_counterparty_stock(): void
    {
        $projectId = DB::table('project')->insertGetId([
            'name' => 'Mirror stock warehouse project',
        ]);
        $counterpartyId = DB::table('users')->insertGetId([
            'name' => 'Mirror stock warehouse counterparty',
            'email' => "mirror-stock-{$this->companyId}@example.test",
            'password' => password_hash('test-password', PASSWORD_BCRYPT),
            'firma' => (string) $this->companyId,
            'project_id' => $projectId,
        ]);
        [$targetProductId, $defaultWarehouseId] = $this->createProductProjectMapping($projectId, $counterpartyId);
        $stockedWarehouseId = (int) DB::table('conf')
            ->where('firma', $projectId)
            ->where('type', 'sklads')
            ->where('is_default', 0)
            ->value('id');

        DB::table('price_sklad')
            ->where('firma', $projectId)
            ->where('sklad', $defaultWarehouseId)
            ->where('pnum', $targetProductId)
            ->update(['count' => 0]);
        DB::table('inventory_cost_balances')
            ->where('company_id', $projectId)
            ->where('warehouse_id', $defaultWarehouseId)
            ->where('product_id', $targetProductId)
            ->update(['quantity' => 0, 'total_value' => 0, 'average_cost' => 0]);
        DB::table('price_sklad')->insert([
            'pnum' => $targetProductId,
            'firma' => $projectId,
            'sklad' => $stockedWarehouseId,
            'count' => 1,
        ]);
        DB::table('inventory_cost_balances')->insert([
            'company_id' => $projectId,
            'warehouse_id' => $stockedWarehouseId,
            'product_id' => $targetProductId,
            'quantity' => 0,
            'total_value' => 0,
            'average_cost' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('conf')->where('id', $stockedWarehouseId)->delete();

        $pnId = $this->createDocument('PN', 1, 16, $counterpartyId);
        $result = Document::provodka((string) $pnId, 'PN', (string) $this->companyId);

        $this->assertTrue($result['isPosted']);
        $this->assertMirrorStock($projectId, $stockedWarehouseId, $targetProductId, 0, 0, 0);
    }

    public function test_project_mirror_is_not_created_for_current_company_project(): void
    {
        DB::table('project')->insert([
            'id' => $this->companyId,
            'name' => 'Current company project',
        ]);
        $counterpartyId = DB::table('users')->insertGetId([
            'name' => 'Internal counterparty',
            'email' => "internal-{$this->companyId}@example.test",
            'password' => password_hash('test-password', PASSWORD_BCRYPT),
            'firma' => (string) $this->companyId,
            'project_id' => $this->companyId,
        ]);

        $rnId = $this->createDocument('RN', 1, 25, $counterpartyId);
        Document::provodka((string) $rnId, 'RN', (string) $this->companyId);

        $this->assertNull($this->projectMirrorTransaction($rnId, 'RN', $this->companyId));
    }

    public function test_purchase_from_counterparty_without_project_posts_only_current_company(): void
    {
        $counterpartyId = DB::table('users')->insertGetId([
            'name' => 'Counterparty without project',
            'email' => "without-project-{$this->companyId}@example.test",
            'password' => password_hash('test-password', PASSWORD_BCRYPT),
            'firma' => (string) $this->companyId,
            'project_id' => null,
        ]);

        $pnId = $this->createDocument('PN', 5, 16, $counterpartyId);
        $result = Document::provodka((string) $pnId, 'PN', (string) $this->companyId);

        $this->assertTrue($result['isPosted']);
        $this->assertEqualsWithDelta(15, (float) $this->balance()->quantity, 0.0001);
        $this->assertNull(
            DB::table('transactions')
                ->where('reference_type', 'z_document:PN:project-mirror')
                ->where('reference_id', (string) $pnId)
                ->first()
        );
        $this->assertFalse(
            DB::table('inventory_cost_movements')
                ->where('source_type', 'PN:project-mirror')
                ->where('source_id', (string) $pnId)
                ->exists()
        );
    }

    public function test_po_and_ro_use_double_entry_and_exact_reversal(): void
    {
        $counterpartyId = DB::table('users')->insertGetId([
            'name' => 'Money counterparty',
            'email' => "money-{$this->companyId}@example.test",
            'password' => password_hash('test-password', PASSWORD_BCRYPT),
            'firma' => (string) $this->companyId,
        ]);
        $cashboxId = DB::table('conf')->insertGetId([
            'type' => 'oplata',
            'firma' => (string) $this->companyId,
            'name' => 'Test cashbox',
            'value' => 100,
        ]);

        $poId = $this->createMoneyDocument('PO', 70, $counterpartyId, $cashboxId);
        Document::provodka((string) $poId, 'PO', (string) $this->companyId);

        $poTransaction = $this->documentTransaction($poId, 'PO');
        $this->assertNotNull($poTransaction);
        $this->assertLedgerIsBalanced((int) $poTransaction->id, 70);
        $this->assertAccountEntry((int) $poTransaction->id, "301.{$this->companyId}.{$cashboxId}", 70, 0);
        $this->assertAccountEntry((int) $poTransaction->id, "361.{$this->companyId}.{$counterpartyId}", 0, 70);
        $this->assertEqualsWithDelta(170, $this->cashboxValue($cashboxId), 0.001);

        Document::provodka((string) $poId, 'PO', (string) $this->companyId);
        $poReversal = $this->documentReversal($poId, 'PO');
        $this->assertNotNull($poReversal);
        $this->assertAccountEntry((int) $poReversal->id, "301.{$this->companyId}.{$cashboxId}", 0, 70);
        $this->assertAccountEntry((int) $poReversal->id, "361.{$this->companyId}.{$counterpartyId}", 70, 0);
        $this->assertEqualsWithDelta(100, $this->cashboxValue($cashboxId), 0.001);

        $roId = $this->createMoneyDocument('RO', 40, $counterpartyId, $cashboxId);
        Document::provodka((string) $roId, 'RO', (string) $this->companyId);

        $roTransaction = $this->documentTransaction($roId, 'RO');
        $this->assertNotNull($roTransaction);
        $this->assertLedgerIsBalanced((int) $roTransaction->id, 40);
        $this->assertAccountEntry((int) $roTransaction->id, "631.{$this->companyId}.{$counterpartyId}", 40, 0);
        $this->assertAccountEntry((int) $roTransaction->id, "301.{$this->companyId}.{$cashboxId}", 0, 40);
        $this->assertEqualsWithDelta(60, $this->cashboxValue($cashboxId), 0.001);

        Document::provodka((string) $roId, 'RO', (string) $this->companyId);
        $roReversal = $this->documentReversal($roId, 'RO');
        $this->assertNotNull($roReversal);
        $this->assertAccountEntry((int) $roReversal->id, "631.{$this->companyId}.{$counterpartyId}", 0, 40);
        $this->assertAccountEntry((int) $roReversal->id, "301.{$this->companyId}.{$cashboxId}", 40, 0);
        $this->assertEqualsWithDelta(100, $this->cashboxValue($cashboxId), 0.001);
    }

    public function test_profile_balance_exchange_uses_double_entry_and_exact_reversal(): void
    {
        $ownerId = DB::table('users')->insertGetId([
            'name' => 'Exchange owner',
            'email' => "exchange-owner-{$this->companyId}@example.test",
            'password' => password_hash('test-password', PASSWORD_BCRYPT),
            'firma' => (string) $this->companyId,
        ]);

        DB::table('users_cashe')->insert([
            [
                'userid' => (string) $ownerId,
                'firma' => $this->companyId,
                'user_id' => $ownerId,
                'balance' => 500,
                'valuta' => 'UAH',
            ],
            [
                'userid' => (string) $ownerId,
                'firma' => $this->companyId,
                'user_id' => $ownerId,
                'balance' => 10,
                'valuta' => 'USD',
            ],
        ]);

        $docId = DB::table('z_document')->insertGetId([
            'num' => (string) random_int(900000, 999999),
            'type' => 'PPP',
            'firma' => (string) $this->companyId,
            'client1' => '0',
            'client2' => (string) $ownerId,
            'summa' => 100,
            'summa2' => 25,
            'currency_from' => 'UAH',
            'currency_to' => 'USD',
            'exchange_rate' => 0.25,
            'data' => '12-06-2026',
            'docum' => 'exchange',
            'provodka' => 0,
        ]);

        Money::provodka($docId, (string) $this->companyId);

        $transaction = $this->documentTransaction($docId, 'balance_exchange');
        $this->assertNotNull($transaction);
        $this->assertLedgerIsBalanced((int) $transaction->id, 100);
        $this->assertAccountEntry((int) $transaction->id, "333.{$this->companyId}.{$ownerId}.USD", 100, 0);
        $this->assertAccountEntry((int) $transaction->id, "333.{$this->companyId}.{$ownerId}.UAH", 0, 100);
        $this->assertUserCacheBalance($ownerId, 'UAH', 400);
        $this->assertUserCacheBalance($ownerId, 'USD', 35);

        Money::provodka($docId, (string) $this->companyId);

        $reversal = $this->documentReversal($docId, 'balance_exchange');
        $this->assertNotNull($reversal);
        $this->assertAccountEntry((int) $reversal->id, "333.{$this->companyId}.{$ownerId}.USD", 0, 100);
        $this->assertAccountEntry((int) $reversal->id, "333.{$this->companyId}.{$ownerId}.UAH", 100, 0);
        $this->assertUserCacheBalance($ownerId, 'UAH', 500);
        $this->assertUserCacheBalance($ownerId, 'USD', 10);
    }

    public function test_profile_balance_exchange_cannot_overdraw_or_create_missing_source_balance(): void
    {
        $ownerId = DB::table('users')->insertGetId([
            'name' => 'Exchange limited owner',
            'email' => "exchange-limited-owner-{$this->companyId}@example.test",
            'password' => password_hash('test-password', PASSWORD_BCRYPT),
            'firma' => (string) $this->companyId,
        ]);

        DB::table('users_cashe')->insert([
            'userid' => (string) $ownerId,
            'firma' => $this->companyId,
            'user_id' => $ownerId,
            'balance' => 50,
            'valuta' => 'UAH',
        ]);

        $overdraftDocId = DB::table('z_document')->insertGetId([
            'num' => (string) random_int(900000, 999999),
            'type' => 'PPP',
            'firma' => (string) $this->companyId,
            'client1' => '0',
            'client2' => (string) $ownerId,
            'summa' => 100,
            'summa2' => 25,
            'currency_from' => 'UAH',
            'currency_to' => 'USD',
            'exchange_rate' => 0.25,
            'data' => '12-06-2026',
            'docum' => 'exchange',
            'provodka' => 0,
        ]);

        $overdraftResult = Money::provodka($overdraftDocId, (string) $this->companyId);

        $this->assertFalse($overdraftResult['isPosted']);
        $this->assertStringContainsString('Недостатньо коштів', $overdraftResult['error'] ?? '');
        $this->assertEquals(0, (int) DB::table('z_document')->where('id', $overdraftDocId)->value('provodka'));
        $this->assertUserCacheBalance($ownerId, 'UAH', 50);
        $this->assertFalse(DB::table('users_cashe')
            ->where('userid', (string) $ownerId)
            ->where('firma', $this->companyId)
            ->where('valuta', 'USD')
            ->exists());

        $missingSourceDocId = DB::table('z_document')->insertGetId([
            'num' => (string) random_int(900000, 999999),
            'type' => 'PPP',
            'firma' => (string) $this->companyId,
            'client1' => '0',
            'client2' => (string) $ownerId,
            'summa' => 10,
            'summa2' => 5,
            'currency_from' => 'EUR',
            'currency_to' => 'USD',
            'exchange_rate' => 0.5,
            'data' => '12-06-2026',
            'docum' => 'exchange',
            'provodka' => 0,
        ]);

        $missingSourceResult = Money::provodka($missingSourceDocId, (string) $this->companyId);

        $this->assertFalse($missingSourceResult['isPosted']);
        $this->assertStringContainsString('Недостатньо коштів', $missingSourceResult['error'] ?? '');
        $this->assertEquals(0, (int) DB::table('z_document')->where('id', $missingSourceDocId)->value('provodka'));
        $this->assertFalse(DB::table('users_cashe')
            ->where('userid', (string) $ownerId)
            ->where('firma', $this->companyId)
            ->where('valuta', 'EUR')
            ->exists());
    }

    public function test_profile_balance_exchange_uses_legacy_user_id_only_cache_rows(): void
    {
        $ownerId = DB::table('users')->insertGetId([
            'name' => 'Exchange legacy owner',
            'email' => "exchange-legacy-owner-{$this->companyId}@example.test",
            'password' => password_hash('test-password', PASSWORD_BCRYPT),
            'firma' => (string) $this->companyId,
        ]);

        DB::table('users_cashe')->insert([
            'userid' => '',
            'firma' => $this->companyId,
            'user_id' => $ownerId,
            'balance' => 70,
            'valuta' => 'EUR',
        ]);

        $balances = Money::cachedUserBalances((string) $ownerId, (string) $this->companyId);
        $this->assertContains('EUR', array_column($balances, 'currency'));

        $docId = DB::table('z_document')->insertGetId([
            'num' => (string) random_int(900000, 999999),
            'type' => 'PPP',
            'firma' => (string) $this->companyId,
            'client1' => '0',
            'client2' => (string) $ownerId,
            'summa' => 20,
            'summa2' => 10,
            'currency_from' => 'EUR',
            'currency_to' => 'USD',
            'exchange_rate' => 0.5,
            'data' => '12-06-2026',
            'docum' => 'exchange',
            'provodka' => 0,
        ]);

        $result = Money::provodka($docId, (string) $this->companyId);

        $this->assertTrue($result['isPosted']);
        $legacyRow = DB::table('users_cashe')
            ->where('user_id', $ownerId)
            ->where('firma', $this->companyId)
            ->where('valuta', 'EUR')
            ->first(['userid', 'balance']);
        $this->assertSame((string) $ownerId, (string) $legacyRow->userid);
        $this->assertEqualsWithDelta(50, (float) $legacyRow->balance, 0.001);
        $this->assertUserCacheBalance($ownerId, 'USD', 10);
    }

    public function test_profile_balance_exchange_uses_holding_scoped_cache_rows(): void
    {
        $accountFirma = $this->companyId + 100000;
        $holdingId = DB::table('holding')->insertGetId([
            'name' => "Exchange holding {$this->companyId}",
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('project')->insert([
            [
                'id' => $this->companyId,
                'name' => 'Exchange user project',
                'holding_id' => $holdingId,
            ],
            [
                'id' => $accountFirma,
                'name' => 'Exchange account project',
                'holding_id' => $holdingId,
            ],
        ]);

        $ownerId = DB::table('users')->insertGetId([
            'name' => 'Exchange holding owner',
            'email' => "exchange-holding-owner-{$this->companyId}@example.test",
            'password' => password_hash('test-password', PASSWORD_BCRYPT),
            'firma' => (string) $this->companyId,
        ]);

        DB::table('users_cashe')->insert([
            'userid' => (string) $ownerId,
            'firma' => $accountFirma,
            'user_id' => $ownerId,
            'balance' => 600,
            'valuta' => 'UAH',
        ]);

        $balances = Money::cachedUserBalances((string) $ownerId, (string) $this->companyId);
        $this->assertContains('UAH', array_column($balances, 'currency'));

        $docId = DB::table('z_document')->insertGetId([
            'num' => (string) random_int(900000, 999999),
            'type' => 'PPP',
            'firma' => (string) $this->companyId,
            'client1' => '0',
            'client2' => (string) $ownerId,
            'summa' => 549,
            'summa2' => 100,
            'currency_from' => 'UAH',
            'currency_to' => 'USD',
            'exchange_rate' => 0.18214936,
            'data' => '12-06-2026',
            'docum' => 'exchange',
            'provodka' => 0,
        ]);

        $result = Money::provodka($docId, (string) $this->companyId);

        $this->assertTrue($result['isPosted'], $result['error'] ?? '');
        $this->assertEqualsWithDelta(
            51,
            (float) DB::table('users_cashe')
                ->where('userid', (string) $ownerId)
                ->where('firma', $accountFirma)
                ->where('valuta', 'UAH')
                ->value('balance'),
            0.001
        );
    }

    public function test_money_order_posting_creates_missing_user_cache_in_order_currency(): void
    {
        $ownerId = DB::table('users')->insertGetId([
            'name' => 'Order owner',
            'email' => "order-owner-{$this->companyId}@example.test",
            'password' => password_hash('test-password', PASSWORD_BCRYPT),
            'firma' => (string) $this->companyId,
        ]);
        $clientId = DB::table('users')->insertGetId([
            'name' => 'Order client',
            'email' => "order-client-{$this->companyId}@example.test",
            'password' => password_hash('test-password', PASSWORD_BCRYPT),
            'firma' => (string) $this->companyId,
        ]);

        $docId = DB::table('z_document')->insertGetId([
            'num' => (string) random_int(900000, 999999),
            'type' => 'PPO',
            'firma' => (string) $this->companyId,
            'client1' => (string) $clientId,
            'client2' => (string) $ownerId,
            'summa' => 80,
            'currency_from' => 'USD',
            'data' => '12-06-2026',
            'docum' => '',
            'provodka' => 0,
        ]);

        $this->assertFalse(
            DB::table('users_cashe')->whereIn('userid', [(string) $ownerId, (string) $clientId])->exists()
        );

        Money::provodka($docId, (string) $this->companyId);

        $this->assertUserCacheBalance($ownerId, 'USD', 80);
        $this->assertUserCacheBalance($clientId, 'USD', -80);

        Money::provodka($docId, (string) $this->companyId);

        $this->assertUserCacheBalance($ownerId, 'USD', 0);
        $this->assertUserCacheBalance($clientId, 'USD', 0);
    }

    public function test_po_and_ro_create_and_reverse_project_mirror_transactions(): void
    {
        $projectId = DB::table('project')->insertGetId([
            'name' => 'Money mirror project',
        ]);
        $counterpartyId = DB::table('users')->insertGetId([
            'name' => 'Project money counterparty',
            'email' => "project-money-{$this->companyId}@example.test",
            'password' => password_hash('test-password', PASSWORD_BCRYPT),
            'firma' => (string) $this->companyId,
            'project_id' => $projectId,
        ]);
        $cashboxId = DB::table('conf')->insertGetId([
            'type' => 'oplata',
            'firma' => (string) $this->companyId,
            'name' => 'Project mirror source cashbox',
            'value' => 100,
        ]);
        DB::table('conf')->insertGetId([
            'type' => 'oplata',
            'firma' => (string) $projectId,
            'name' => 'Project mirror fallback cashbox',
            'value' => 0,
            'is_default' => 0,
        ]);
        $projectCashboxId = DB::table('conf')->insertGetId([
            'type' => 'oplata',
            'firma' => (string) $projectId,
            'name' => 'Project mirror default cashbox',
            'value' => 0,
            'is_default' => 1,
        ]);
        $projectCashCode = "301.{$projectId}.{$projectCashboxId}";

        $poId = $this->createMoneyDocument('PO', 70, $counterpartyId, $cashboxId);
        Document::provodka((string) $poId, 'PO', (string) $this->companyId);

        $poMirror = $this->projectMirrorTransaction($poId, 'PO', $projectId);
        $this->assertNotNull($poMirror);
        $this->assertLedgerIsBalanced((int) $poMirror->id, 70);
        $this->assertAccountEntry(
            (int) $poMirror->id,
            "631.{$projectId}.company-{$this->companyId}",
            70,
            0
        );
        $this->assertAccountEntry((int) $poMirror->id, $projectCashCode, 0, 70);

        Document::provodka((string) $poId, 'PO', (string) $this->companyId);
        $poReversal = $this->projectMirrorReversal($poId, 'PO', $projectId);
        $this->assertNotNull($poReversal);
        $this->assertAccountEntry(
            (int) $poReversal->id,
            "631.{$projectId}.company-{$this->companyId}",
            0,
            70
        );
        $this->assertAccountEntry((int) $poReversal->id, $projectCashCode, 70, 0);

        $roId = $this->createMoneyDocument('RO', 40, $counterpartyId, $cashboxId);
        Document::provodka((string) $roId, 'RO', (string) $this->companyId);

        $roMirror = $this->projectMirrorTransaction($roId, 'RO', $projectId);
        $this->assertNotNull($roMirror);
        $this->assertLedgerIsBalanced((int) $roMirror->id, 40);
        $this->assertAccountEntry((int) $roMirror->id, $projectCashCode, 40, 0);
        $this->assertAccountEntry(
            (int) $roMirror->id,
            "361.{$projectId}.company-{$this->companyId}",
            0,
            40
        );

        Document::provodka((string) $roId, 'RO', (string) $this->companyId);
        $roReversal = $this->projectMirrorReversal($roId, 'RO', $projectId);
        $this->assertNotNull($roReversal);
        $this->assertAccountEntry((int) $roReversal->id, $projectCashCode, 0, 40);
        $this->assertAccountEntry(
            (int) $roReversal->id,
            "361.{$projectId}.company-{$this->companyId}",
            40,
            0
        );
    }

    public function test_accounting_reports_use_double_entry_balances_and_turnovers(): void
    {
        $pnId = $this->createDocument('PN', 5, 16);
        Document::provodka((string) $pnId, 'PN', (string) $this->companyId);

        $trialBalance = Report::trialBalance(
            (string) $this->companyId,
            '2026-06-01',
            '2026-06-30'
        );
        $inventoryRow = $trialBalance['rows']->firstWhere('code', "281.{$this->companyId}");
        $payableRow = $trialBalance['rows']->firstWhere('code', "631.{$this->companyId}.generic");

        $this->assertNotNull($inventoryRow);
        $this->assertNotNull($payableRow);
        $this->assertEqualsWithDelta(80, (float) $inventoryRow->closing_balance_debit, 0.001);
        $this->assertEqualsWithDelta(0, (float) $inventoryRow->closing_balance_credit, 0.001);
        $this->assertEqualsWithDelta(0, (float) $payableRow->closing_balance_debit, 0.001);
        $this->assertEqualsWithDelta(80, (float) $payableRow->closing_balance_credit, 0.001);
        $this->assertEqualsWithDelta(
            (float) $trialBalance['totals']['period_debit'],
            (float) $trialBalance['totals']['period_credit'],
            0.001
        );

        $rnId = $this->createDocument('RN', 4, 25);
        Document::provodka((string) $rnId, 'RN', (string) $this->companyId);
        $pnl = Report::financialPnl((string) $this->companyId, '2026-06-01', '2026-06-30');

        $this->assertEqualsWithDelta(100, (float) $pnl['revenueTotal'], 0.001);
        $this->assertEqualsWithDelta(48, (float) $pnl['cogsTotal'], 0.001);
        $this->assertEqualsWithDelta(52, (float) $pnl['grossProfitTotal'], 0.001);
        $this->assertEqualsWithDelta(52, (float) $pnl['netProfit'], 0.001);

        $balance = Report::balanceSheet((string) $this->companyId, '2026-06-01', '2026-06-30');
        $this->assertEqualsWithDelta(
            (float) $balance['totalAssets'] - (float) $balance['totalLiabilities'] - (float) $balance['equity'],
            (float) $balance['balanceDifference'],
            0.001
        );
    }

    public function test_cash_reports_follow_cash_account_entries_and_reversals(): void
    {
        $counterpartyId = DB::table('users')->insertGetId([
            'name' => 'Report counterparty',
            'email' => "report-{$this->companyId}@example.test",
            'password' => password_hash('test-password', PASSWORD_BCRYPT),
            'firma' => (string) $this->companyId,
        ]);
        $cashboxId = DB::table('conf')->insertGetId([
            'type' => 'oplata',
            'firma' => (string) $this->companyId,
            'name' => 'Report cashbox',
            'value' => 0,
        ]);

        $poId = $this->createMoneyDocument('PO', 70, $counterpartyId, $cashboxId);
        $roId = $this->createMoneyDocument('RO', 40, $counterpartyId, $cashboxId);
        Document::provodka((string) $poId, 'PO', (string) $this->companyId);
        Document::provodka((string) $roId, 'RO', (string) $this->companyId);

        $cashFlow = Report::cashFlowStatement(
            (string) $this->companyId,
            '2026-06-01',
            '2026-06-30'
        );
        $this->assertEqualsWithDelta(70, (float) $cashFlow['operatingInflows'], 0.001);
        $this->assertEqualsWithDelta(40, (float) $cashFlow['operatingOutflows'], 0.001);
        $this->assertEqualsWithDelta(30, (float) $cashFlow['netCashFlow'], 0.001);

        Document::provodka((string) $poId, 'PO', (string) $this->companyId);
        $cashFlowAfterReversal = Report::cashFlowStatement(
            (string) $this->companyId,
            '2026-06-01',
            '2026-06-30'
        );
        $this->assertEqualsWithDelta(-40, (float) $cashFlowAfterReversal['netCashFlow'], 0.001);

        $finance = Report::finance(
            (string) $this->companyId,
            '2026-06-01',
            '2026-06-30',
            (string) $cashboxId
        );
        $this->assertEqualsWithDelta(-40, (float) $finance['operatingCashFlow'], 0.001);
        $this->assertEqualsWithDelta(-40, (float) $finance['cashBalanceTotal'], 0.001);

        $journal = Report::journal((string) $this->companyId, '2026-06-01', '2026-06-30');
        $this->assertTrue($journal['rows']->every(
            fn ($row) => str_contains((string) $row->account_code, ".{$this->companyId}")
        ));
    }

    public function test_partial_payment_binding_keeps_dynamic_cash_account(): void
    {
        $counterpartyId = DB::table('users')->insertGetId([
            'name' => 'Binding counterparty',
            'email' => "binding-{$this->companyId}@example.test",
            'password' => password_hash('test-password', PASSWORD_BCRYPT),
            'firma' => (string) $this->companyId,
        ]);
        $cashboxId = DB::table('conf')->insertGetId([
            'type' => 'oplata',
            'firma' => (string) $this->companyId,
            'name' => 'Binding cashbox',
            'value' => 100,
        ]);
        $expenseAccountId = DB::table('accounts')->insertGetId([
            'code' => "949.{$this->companyId}.test",
            'name' => 'Binding expense',
            'type' => 'expense',
            'parent_id' => DB::table('accounts')->where('code', '949')->value('id'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $paymentTypeId = DB::table('conf')->insertGetId([
            'type' => 'reestr',
            'firma' => (string) $this->companyId,
            'name' => 'Binding expense type',
            'doc' => 'RO',
            'debit_account_id' => $expenseAccountId,
            'credit_account_id' => null,
        ]);

        $roId = $this->createMoneyDocument('RO', 35, $counterpartyId, $cashboxId);
        DB::table('z_document')->where('id', $roId)->update(['reestr' => (string) $paymentTypeId]);
        Document::provodka((string) $roId, 'RO', (string) $this->companyId);

        $transaction = $this->documentTransaction($roId, 'RO');
        $this->assertNotNull($transaction);
        $this->assertAccountEntry((int) $transaction->id, "949.{$this->companyId}.test", 35, 0);
        $this->assertAccountEntry(
            (int) $transaction->id,
            "301.{$this->companyId}.{$cashboxId}",
            0,
            35
        );
    }

    public function test_loan_ro_posts_to_lending_receivable_and_bank_cash(): void
    {
        $borrowerId = DB::table('users')->insertGetId([
            'name' => 'Loan borrower',
            'email' => "loan-borrower-{$this->companyId}@example.test",
            'password' => password_hash('test-password', PASSWORD_BCRYPT),
            'firma' => (string) $this->companyId,
        ]);
        $cashboxId = DB::table('conf')->insertGetId([
            'type' => 'oplata',
            'firma' => (string) $this->companyId,
            'name' => 'Loan bank account',
            'doc' => 'bank',
            'value' => 1000,
        ]);
        $loanId = DB::table('document')->insertGetId([
            'num' => (string) random_int(900000, 999999),
            'type' => 'ZOUT',
            'firma' => (string) $this->companyId,
            'client1' => (string) $borrowerId,
            'summa' => 250,
            'data' => '12-06-2026',
            'typeproduct' => 'credit_request',
            'numorder' => 'AV8-LOAN',
            'content' => '[AV8_LOAN_REQUEST]',
            'provodka' => 0,
        ]);
        $roId = $this->createMoneyDocument('RO', 250, $borrowerId, $cashboxId);
        DB::table('z_document')->where('id', $roId)->update([
            'docid' => (string) $loanId,
            'typez' => 'ZOUT',
        ]);

        Document::provodka((string) $roId, 'RO', (string) $this->companyId);

        $transaction = $this->documentTransaction($roId, 'RO');
        $this->assertNotNull($transaction);
        $this->assertAccountEntry(
            (int) $transaction->id,
            "377.{$this->companyId}.{$borrowerId}",
            250,
            0
        );
        $this->assertAccountEntry(
            (int) $transaction->id,
            "301.{$this->companyId}.{$cashboxId}",
            0,
            250
        );
        $this->assertEqualsWithDelta(750, $this->cashboxValue($cashboxId), 0.001);
    }

    public function test_loan_po_splits_repayment_between_principal_and_interest(): void
    {
        $borrowerId = DB::table('users')->insertGetId([
            'name' => 'Repaying borrower',
            'email' => "repaying-borrower-{$this->companyId}@example.test",
            'password' => password_hash('test-password', PASSWORD_BCRYPT),
            'firma' => (string) $this->companyId,
        ]);
        $cashboxId = DB::table('conf')->insertGetId([
            'type' => 'oplata',
            'firma' => (string) $this->companyId,
            'name' => 'Loan repayment bank account',
            'doc' => 'bank',
            'value' => 0,
        ]);
        $loanId = DB::table('document')->insertGetId([
            'num' => (string) random_int(900000, 999999),
            'type' => 'ZOUT',
            'firma' => (string) $this->companyId,
            'client1' => (string) $borrowerId,
            'summa' => 250,
            'data' => '12-06-2026',
            'typeproduct' => 'credit_request',
            'numorder' => 'AV8-LOAN',
            'content' => implode("\n", [
                '[AV8_LOAN_REQUEST]',
                'Процентная ставка заемщика: 12.00%',
                'Срок кредита: 1 год',
            ]),
            'provodka' => 0,
        ]);
        $poId = $this->createMoneyDocument('PO', 28, $borrowerId, $cashboxId);
        DB::table('z_document')->where('id', $poId)->update([
            'docid' => (string) $loanId,
            'typez' => 'ZOUT',
            'typeproduct' => 'credit_payment',
            'numorder' => 'AV8-LOAN-PAYMENT',
        ]);

        Document::provodka((string) $poId, 'PO', (string) $this->companyId);

        $transaction = $this->documentTransaction($poId, 'PO');
        $this->assertNotNull($transaction);
        $this->assertAccountEntry(
            (int) $transaction->id,
            "301.{$this->companyId}.{$cashboxId}",
            28,
            0
        );
        $this->assertAccountEntry(
            (int) $transaction->id,
            "377.{$this->companyId}.{$borrowerId}",
            0,
            25
        );
        $this->assertAccountEntry(
            (int) $transaction->id,
            "732.{$this->companyId}",
            0,
            3
        );
        $this->assertEqualsWithDelta(28, $this->cashboxValue($cashboxId), 0.001);
    }

    private function createLine(string $type, float $quantity, float $price): object
    {
        $id = DB::table('z_body')->insertGetId([
            'docnum' => '1',
            'pid' => '1',
            'pnum' => $this->productId,
            'pcount' => $quantity,
            'pprice' => $price,
            'psumma' => $quantity * $price,
            'type' => $type,
            'firma' => $this->companyId,
            'docid' => random_int(900000, 999999),
            'zvalue' => '',
        ]);

        return DB::table('z_body')->where('id', $id)->first();
    }

    private function document(string $type, int $id, string $date = '2026-06-12'): object
    {
        return (object) [
            'id' => $id,
            'type' => $type,
            'sklads' => $this->warehouseId,
            'data' => $date,
        ];
    }

    private function createDocument(string $type, float $quantity, float $price, int|string $clientId = 0): int
    {
        $id = DB::table('z_document')->insertGetId([
            'num' => (string) random_int(900000, 999999),
            'type' => $type,
            'firma' => (string) $this->companyId,
            'client1' => (string) $clientId,
            'summa' => $quantity * $price,
            'data' => '12-06-2026',
            'sklads' => (string) $this->warehouseId,
            'docum' => '',
            'provodka' => 0,
        ]);

        DB::table('z_body')->insert([
            'docnum' => (string) $id,
            'pid' => '1',
            'pnum' => $this->productId,
            'pcount' => $quantity,
            'pprice' => $price,
            'psumma' => $quantity * $price,
            'type' => $type,
            'firma' => (string) $this->companyId,
            'docid' => (string) $id,
            'zvalue' => '',
        ]);

        return $id;
    }

    private function projectMirrorTransaction(int $documentId, string $type, int $projectId): ?object
    {
        return DB::table('transactions')
            ->where('company_id', $projectId)
            ->where('reference_type', "z_document:{$type}:project-mirror")
            ->where('reference_id', (string) $documentId)
            ->latest('id')
            ->first();
    }

    private function createProductProjectMapping(int $projectId, int $counterpartyId): array
    {
        $targetProductId = (string) DB::table('comp')->insertGetId([
            'cod' => "mirror-{$this->productId}",
            'firma' => (string) $projectId,
            'name' => 'Mirror product',
        ]);
        DB::table('conf')->insertGetId([
            'type' => 'sklads',
            'firma' => (string) $projectId,
            'name' => 'Mirror fallback warehouse',
            'is_default' => 0,
        ]);
        $targetWarehouseId = DB::table('conf')->insertGetId([
            'type' => 'sklads',
            'firma' => (string) $projectId,
            'name' => 'Mirror warehouse',
            'is_default' => 1,
        ]);

        DB::table('price')->insert([
            'pnum' => $targetProductId,
            'firma' => $projectId,
            'pay' => 10,
        ]);
        DB::table('price_sklad')->insert([
            'pnum' => $targetProductId,
            'firma' => $projectId,
            'sklad' => $targetWarehouseId,
            'count' => 10,
        ]);
        DB::table('inventory_cost_balances')->insert([
            'company_id' => $projectId,
            'warehouse_id' => $targetWarehouseId,
            'product_id' => $targetProductId,
            'quantity' => 10,
            'total_value' => 100,
            'average_cost' => 10,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('product_project_mappings')->insert([
            'source_company_id' => $this->companyId,
            'counterparty_user_id' => $counterpartyId,
            'source_product_id' => $this->productId,
            'target_company_id' => $projectId,
            'target_product_id' => $targetProductId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$targetProductId, (int) $targetWarehouseId];
    }

    private function assertMirrorStock(
        int $projectId,
        int $warehouseId,
        string $productId,
        float $quantity,
        float $totalValue,
        float $averageCost
    ): void {
        $balance = DB::table('inventory_cost_balances')
            ->where('company_id', $projectId)
            ->where('warehouse_id', $warehouseId)
            ->where('product_id', $productId)
            ->first();

        $this->assertNotNull($balance);
        $this->assertEqualsWithDelta($quantity, (float) $balance->quantity, 0.0001);
        $this->assertEqualsWithDelta($totalValue, (float) $balance->total_value, 0.0001);
        $this->assertEqualsWithDelta($averageCost, (float) $balance->average_cost, 0.000001);
        $this->assertEqualsWithDelta(
            $quantity,
            (float) DB::table('price_sklad')
                ->where('firma', $projectId)
                ->where('sklad', $warehouseId)
                ->where('pnum', $productId)
                ->value('count'),
            0.0001
        );
    }

    private function createMoneyDocument(
        string $type,
        float $amount,
        int $clientId,
        int $cashboxId
    ): int {
        return DB::table('z_document')->insertGetId([
            'num' => (string) random_int(900000, 999999),
            'type' => $type,
            'firma' => (string) $this->companyId,
            'client1' => (string) $clientId,
            'summa' => $amount,
            'data' => '12-06-2026',
            'oplata' => (string) $cashboxId,
            'reestr' => '',
            'docum' => '',
            'provodka' => 0,
        ]);
    }

    private function documentTransaction(int $documentId, string $type): ?object
    {
        return DB::table('transactions')
            ->where('company_id', $this->companyId)
            ->where('reference_type', "z_document:{$type}")
            ->where('reference_id', (string) $documentId)
            ->latest('id')
            ->first();
    }

    private function documentReversal(int $documentId, string $type): ?object
    {
        return DB::table('transactions')
            ->where('company_id', $this->companyId)
            ->where('reference_type', "z_document:{$type}:reversal")
            ->where('reference_id', (string) $documentId)
            ->latest('id')
            ->first();
    }

    private function cashboxValue(int $cashboxId): float
    {
        return (float) DB::table('conf')->where('id', $cashboxId)->value('value');
    }

    private function projectMirrorReversal(int $documentId, string $type, int $projectId): ?object
    {
        return DB::table('transactions')
            ->where('company_id', $projectId)
            ->where('reference_type', "z_document:{$type}:project-mirror:reversal")
            ->where('reference_id', (string) $documentId)
            ->latest('id')
            ->first();
    }

    private function assertLedgerIsBalanced(int $transactionId, float $expectedTurnover): void
    {
        $totals = DB::table('entries')
            ->where('transaction_id', $transactionId)
            ->selectRaw('SUM(debit) debit, SUM(credit) credit')
            ->first();

        $this->assertEqualsWithDelta($expectedTurnover, (float) $totals->debit, 0.001);
        $this->assertEqualsWithDelta($expectedTurnover, (float) $totals->credit, 0.001);
    }

    private function assertAccountEntry(
        int $transactionId,
        string $accountCode,
        float $debit,
        float $credit
    ): void {
        $entry = DB::table('entries as e')
            ->join('accounts as a', 'a.id', '=', 'e.account_id')
            ->where('e.transaction_id', $transactionId)
            ->where('a.code', $accountCode)
            ->first(['e.debit', 'e.credit']);

        $this->assertNotNull($entry, "Missing ledger entry for account {$accountCode}");
        $this->assertEqualsWithDelta($debit, (float) $entry->debit, 0.001);
        $this->assertEqualsWithDelta($credit, (float) $entry->credit, 0.001);
    }

    private function assertUserCacheBalance(int $userId, string $currency, float $expected): void
    {
        $actual = DB::table('users_cashe')
            ->where('userid', (string) $userId)
            ->where('firma', $this->companyId)
            ->where('valuta', $currency)
            ->value('balance');

        $this->assertEqualsWithDelta($expected, (float) $actual, 0.001);
    }

    private function balance(): object
    {
        return DB::table('inventory_cost_balances')
            ->where('company_id', $this->companyId)
            ->where('warehouse_id', $this->warehouseId)
            ->where('product_id', $this->productId)
            ->first();
    }

    private function referenceCost(): float
    {
        return (float) DB::table('price')
            ->where('firma', $this->companyId)
            ->where('pnum', $this->productId)
            ->value('pay');
    }
}
