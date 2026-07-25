<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SalaryStatementWorkflowTest extends TestCase
{
    private int $projectId;
    private int $userId;
    private int $secondUserId;

    protected function setUp(): void
    {
        parent::setUp();
        DB::beginTransaction();

        $this->projectId = random_int(700000, 799999);
        $this->userId = DB::table('users')->insertGetId([
            'name' => 'Salary employee',
            'email' => "salary-{$this->projectId}@example.test",
            'password' => password_hash('test-password', PASSWORD_BCRYPT),
            'firma' => (string) $this->projectId,
        ]);
        DB::table('team_memberships')->insert([
            'user_id' => $this->userId,
            'project_id' => $this->projectId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->secondUserId = DB::table('users')->insertGetId([
            'name' => 'Second salary employee',
            'email' => "salary-second-{$this->projectId}@example.test",
            'password' => password_hash('test-password', PASSWORD_BCRYPT),
            'firma' => (string) $this->projectId,
        ]);
        DB::table('team_memberships')->insert([
            'user_id' => $this->secondUserId,
            'project_id' => $this->projectId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    public function test_statement_creates_and_posts_linked_salary_document(): void
    {
        $cashboxId = DB::table('conf')->insertGetId([
            'type' => 'oplata',
            'firma' => (string) $this->projectId,
            'name' => 'Salary bank account',
            'value' => 1000,
            'vision' => '1',
            'currency' => 'UAH',
        ]);
        $paymentTypeId = DB::table('conf')->insertGetId([
            'type' => 'reestr',
            'firma' => '0',
            'name' => 'Salary payout',
            'doc' => 'ZP',
            'status' => '1',
            'vision' => 'operating',
        ]);

        $user = User::query()->findOrFail($this->userId);
        $this->actingAs($user)->withSession([
            'fid' => (string) $this->projectId,
            'login' => (string) ($user->login ?? ''),
            'work' => '1',
        ]);

        $createResponse = $this->postJson(route('document.salaryStatements.store'), [
            'data' => '2026-07-25',
            'content' => 'July payroll',
            'employees' => [[
                'employee_id' => $this->userId,
                'salary_amount' => 385,
            ], [
                'employee_id' => $this->secondUserId,
                'salary_amount' => 100,
            ]],
        ]);

        $createResponse->assertCreated();
        $statementId = (int) $createResponse->json('statement.id');
        $lines = collect($createResponse->json('statement.lines'));
        $lineId = (int) $lines->firstWhere('employee_id', $this->userId)['id'];
        $secondLineId = (int) $lines->firstWhere('employee_id', $this->secondUserId)['id'];

        $this->deleteJson(route('document.salaryStatements.employees.destroy', [
            'id' => $statementId,
            'lineId' => $secondLineId,
        ]))->assertOk();
        $this->assertDatabaseMissing('salary_statement_lines', ['id' => $secondLineId]);

        $payoutResponse = $this->postJson(
            route('document.salaryStatements.payout', ['id' => $statementId, 'lineId' => $lineId]),
            [
                'salary_amount' => 385,
                'data' => '2026-07-25',
                'oplata' => $cashboxId,
                'reestr' => $paymentTypeId,
                'content' => 'Salary payout from ZV',
            ]
        );

        $payoutResponse->assertOk();
        $paidLine = collect($payoutResponse->json('statement.lines'))->firstWhere('employee_id', $this->userId);
        $this->assertSame(1, (int) $paidLine['zp_posted']);
        $zpId = (int) $paidLine['zp_document_id'];
        $this->assertDatabaseHas('z_document', [
            'id' => $statementId,
            'type' => 'ZV',
            'firma' => (string) $this->projectId,
            'summa' => 385,
        ]);
        $this->assertDatabaseHas('z_document', [
            'id' => $zpId,
            'type' => 'ZP',
            'docid' => (string) $statementId,
            'typez' => 'ZV',
            'client1' => (string) $this->userId,
            'provodka' => 1,
        ]);
        $this->assertDatabaseHas('salary_statement_lines', [
            'id' => $lineId,
            'statement_document_id' => $statementId,
            'employee_id' => $this->userId,
            'zp_document_id' => $zpId,
        ]);
        $this->assertEqualsWithDelta(
            615,
            (float) DB::table('conf')->where('id', $cashboxId)->value('value'),
            0.001
        );
        $this->assertSame(
            2,
            DB::table('entries')
                ->where('reference_type', 'z_document:ZP')
                ->where('reference_id', (string) $zpId)
                ->count()
        );
    }
}
