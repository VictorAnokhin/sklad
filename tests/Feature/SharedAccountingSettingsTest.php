<?php

namespace Tests\Feature;

use App\Http\Controllers\SettingsController;
use App\Models\Account;
use App\Models\Conf;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SharedAccountingSettingsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::beginTransaction();
    }

    protected function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    public function test_accounts_from_all_projects_are_returned_for_any_selected_project(): void
    {
        $suffix = bin2hex(random_bytes(4));
        $first = Account::query()->create([
            'code' => "301.101.{$suffix}",
            'name' => 'Shared account one',
            'type' => 'asset',
            'currency' => 'UAH',
        ]);
        $second = Account::query()->create([
            'code' => "301.202.{$suffix}",
            'name' => 'Shared account two',
            'type' => 'asset',
            'currency' => 'UAH',
        ]);

        session(['fid' => '101']);

        $items = collect(app(SettingsController::class)->accountsIndex()->getData(true));

        $this->assertTrue($items->contains('id', $first->id));
        $this->assertTrue($items->contains('id', $second->id));
    }

    public function test_payment_types_are_shared_by_settings_and_document_forms(): void
    {
        $firstId = DB::table('conf')->insertGetId([
            'type' => 'reestr',
            'name' => 'Shared payment one ' . bin2hex(random_bytes(3)),
            'firma' => '101',
            'status' => '1',
            'doc' => 'PO',
        ]);
        $secondId = DB::table('conf')->insertGetId([
            'type' => 'reestr',
            'name' => 'Shared payment two ' . bin2hex(random_bytes(3)),
            'firma' => '202',
            'status' => '1',
            'doc' => 'PO',
        ]);

        session(['fid' => '101']);

        $settingsItems = collect(app(SettingsController::class)->apiIndex('reestr')->getData(true));
        $documentItems = Conf::paymentTypesForDocument('101', 'PO');

        $this->assertTrue($settingsItems->contains('id', $firstId));
        $this->assertTrue($settingsItems->contains('id', $secondId));
        $this->assertTrue($documentItems->contains('id', $firstId));
        $this->assertTrue($documentItems->contains('id', $secondId));
    }
}
