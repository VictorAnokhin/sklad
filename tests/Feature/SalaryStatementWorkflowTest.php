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
            'reestr' => $paymentTypeId,
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
            'reestr' => (string) $paymentTypeId,
        ]);
        $this->assertDatabaseHas('z_document', [
            'id' => $zpId,
            'type' => 'ZP',
            'docid' => (string) $statementId,
            'typez' => 'ZV',
            'client1' => (string) $this->userId,
            'provodka' => 1,
        ]);
        $statementResponse = $this->getJson(route('document.salaryStatements.show', ['id' => $statementId]));
        $statementResponse->assertOk();
        $statementResponse->assertJsonPath('zp_documents.0.id', $zpId);
        $statementResponse->assertJsonPath('zp_documents.0.num', (string) DB::table('z_document')->where('id', $zpId)->value('num'));
        $this->assertStringContainsString(
            '/document/show',
            (string) $statementResponse->json('zp_documents.0.url')
        );
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

    public function test_unposted_salary_document_can_move_to_another_statement(): void
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
        $statementIds = collect([1, 2])->map(function (int $number) use ($paymentTypeId) {
            return DB::table('z_document')->insertGetId([
                'num' => (string) $number,
                'client1' => 0,
                'client2' => 0,
                'type' => 'ZV',
                'summa' => 385,
                'status' => 0,
                'data' => '25-07-2026',
                'data2' => '25-07-2026',
                'time' => '12:00:00',
                'firma' => $this->projectId,
                'dt' => now()->timestamp + $number,
                'numz' => (string) $number,
                'typez' => 'ZV',
                'docid' => 0,
                'manager' => '',
                'user' => '',
                'reestr' => (string) $paymentTypeId,
                'content' => '',
                'docum' => '',
                'dostup' => 1,
                'work' => '1',
            ]);
        });
        [$firstStatementId, $secondStatementId] = $statementIds->all();

        $zpId = DB::table('z_document')->insertGetId([
            'num' => '10',
            'client1' => $this->userId,
            'client2' => 0,
            'type' => 'ZP',
            'summa' => 385,
            'status' => 0,
            'data' => '25-07-2026',
            'data2' => '25-07-2026',
            'time' => '12:30:00',
            'firma' => $this->projectId,
            'dt' => now()->timestamp,
            'numz' => '1',
            'typez' => 'ZV',
            'docid' => $firstStatementId,
            'manager' => '',
            'user' => '',
            'oplata' => (string) $cashboxId,
            'reestr' => (string) $paymentTypeId,
            'content' => '',
            'docum' => '',
            'dostup' => 1,
            'work' => '1',
        ]);
        $firstLineId = DB::table('salary_statement_lines')->insertGetId([
            'statement_document_id' => $firstStatementId,
            'employee_id' => $this->userId,
            'project_id' => $this->projectId,
            'salary_amount' => 385,
            'zp_document_id' => $zpId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user = User::query()->findOrFail($this->userId);
        $this->actingAs($user)->withSession([
            'fid' => (string) $this->projectId,
            'login' => (string) ($user->login ?? ''),
            'work' => '1',
            'doc' => 'ZP',
            'doc_id' => (string) $zpId,
            'year' => '2026',
        ]);

        $response = $this->post(route('document.save'), [
            'run' => 'Сохранить',
            'doc' => 'ZP',
            'doc_id' => $zpId,
            'num' => '10',
            'client1' => $this->userId,
            'summa' => 385,
            'data' => '2026-07-25',
            'data2' => '2026-07-25',
            'time' => '12:30',
            'oplata' => $cashboxId,
            'reestr' => $paymentTypeId,
            'salary_statement_id' => $secondStatementId,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseMissing('salary_statement_lines', ['id' => $firstLineId]);
        $this->assertDatabaseHas('salary_statement_lines', [
            'statement_document_id' => $secondStatementId,
            'employee_id' => $this->userId,
            'zp_document_id' => $zpId,
            'salary_amount' => 385,
        ]);
        $this->assertDatabaseHas('z_document', [
            'id' => $zpId,
            'docid' => (string) $secondStatementId,
            'typez' => 'ZV',
            'numz' => '2',
        ]);
    }

    public function test_zv_list_shows_unassigned_salary_documents(): void
    {
        $zpId = DB::table('z_document')->insertGetId([
            'num' => '99',
            'client1' => $this->userId,
            'client2' => 0,
            'type' => 'ZP',
            'summa' => 500,
            'status' => 0,
            'data' => '25-07-2026',
            'data2' => '25-07-2026',
            'time' => '12:00:00',
            'firma' => $this->projectId,
            'dt' => now()->timestamp,
            'numz' => '0',
            'typez' => '',
            'docid' => 0,
            'manager' => '',
            'user' => '',
            'content' => '',
            'docum' => '',
            'dostup' => 1,
            'work' => '1',
        ]);

        $user = User::query()->findOrFail($this->userId);
        $response = $this->actingAs($user)->withSession([
            'fid' => (string) $this->projectId,
            'login' => (string) ($user->login ?? ''),
            'work' => '1',
            'year' => '2026',
        ])->get(route('document.index', ['doc' => 'ZV']));

        $response->assertOk();
        $response->assertSee('Документы ZP без платежной ведомости');
        $response->assertSee('ZP №99');
        $response->assertSee(route('document.show', [
            'doc' => 'ZP',
            'doc_id' => $zpId,
            'num' => '99',
            'year' => '2026',
        ]), false);
    }

    public function test_zv_list_shows_salary_document_with_inconsistent_statement_link(): void
    {
        $statementId = DB::table('z_document')->insertGetId([
            'num' => '7',
            'client1' => 0,
            'client2' => 0,
            'type' => 'ZV',
            'summa' => 700,
            'status' => 0,
            'data' => '25-07-2026',
            'data2' => '25-07-2026',
            'time' => '12:00:00',
            'firma' => $this->projectId,
            'dt' => now()->timestamp,
            'numz' => '7',
            'typez' => 'ZV',
            'docid' => 0,
            'manager' => '',
            'user' => '',
            'content' => '',
            'docum' => '',
            'dostup' => 1,
            'work' => '1',
        ]);
        $zpId = DB::table('z_document')->insertGetId([
            'num' => '100',
            'client1' => $this->userId,
            'client2' => 0,
            'type' => 'ZP',
            'summa' => 700,
            'status' => 0,
            'data' => '25-07-2026',
            'data2' => '25-07-2026',
            'time' => '12:30:00',
            'firma' => $this->projectId,
            'dt' => now()->timestamp,
            'numz' => '0',
            'typez' => '',
            'docid' => 0,
            'manager' => '',
            'user' => '',
            'content' => '',
            'docum' => '',
            'dostup' => 1,
            'work' => '1',
        ]);
        DB::table('salary_statement_lines')->insert([
            'statement_document_id' => $statementId,
            'employee_id' => $this->userId,
            'project_id' => $this->projectId,
            'salary_amount' => 700,
            'zp_document_id' => $zpId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user = User::query()->findOrFail($this->userId);
        $response = $this->actingAs($user)->withSession([
            'fid' => (string) $this->projectId,
            'login' => (string) ($user->login ?? ''),
            'work' => '1',
            'year' => '2026',
        ])->get(route('document.index', ['doc' => 'ZV', 'num' => 0]));

        $response->assertOk();
        $response->assertSee('ZP №100');
    }
}
