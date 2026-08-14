<?php

namespace App\Http\Controllers;

use App\Support\HoldingScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class EmployeeRoleController extends Controller
{
    public function index()
    {
        $fid = $this->activeFid();
        $this->ensureDefaultRoles($fid);

        $roles = $this->rolesQuery($fid)->get()->map(function (object $role): object {
            $role->permissions = $this->rolePermissions((int) $role->id);
            $role->members_count = $this->roleMembersCount((int) $role->id);

            return $role;
        });

        return view('settings.employee_roles', [
            'roles' => $roles,
            'permissionGroups' => $this->permissionGroups(),
            'activeRoleId' => (int) request()->query('role_id', $roles->first()->id ?? 0),
            'activeTab' => (string) request()->query('tab', 'roles'),
        ]);
    }

    public function store(Request $request)
    {
        $fid = $this->activeFid();
        $this->ensureTables();

        $validated = $this->validateRole($request, $fid);

        DB::table('employee_roles')->insert([
            'project_id' => (int) $fid,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'sort' => (int) ($validated['sort'] ?? 100),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('settings.employeeRoles.index')->with('success', 'Роль добавлена');
    }

    public function update(Request $request, int $role)
    {
        $fid = $this->activeFid();
        $this->ensureTables();
        $existing = $this->roleForProject($role, $fid);
        abort_unless($existing, 404);

        $validated = $this->validateRole($request, $fid, $role);

        DB::table('employee_roles')->where('id', $role)->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'sort' => (int) ($validated['sort'] ?? 100),
            'updated_at' => now(),
        ]);

        return redirect()
            ->route('settings.employeeRoles.index', ['role_id' => $role])
            ->with('success', 'Роль обновлена');
    }

    public function destroy(int $role)
    {
        $fid = $this->activeFid();
        $this->ensureTables();
        $existing = $this->roleForProject($role, $fid);
        abort_unless($existing, 404);

        if ($this->roleMembersCount($role) > 0) {
            return redirect()
                ->route('settings.employeeRoles.index', ['role_id' => $role])
                ->with('error', 'Роль нельзя удалить, пока она назначена сотрудникам.');
        }

        DB::transaction(function () use ($role): void {
            DB::table('employee_role_permissions')->where('role_id', $role)->delete();
            DB::table('employee_roles')->where('id', $role)->delete();
        });

        return redirect()->route('settings.employeeRoles.index')->with('success', 'Роль удалена');
    }

    public function updatePermissions(Request $request, int $role)
    {
        $fid = $this->activeFid();
        $this->ensureTables();
        $existing = $this->roleForProject($role, $fid);
        abort_unless($existing, 404);

        $allowedPermissions = collect($this->permissionGroups())
            ->flatMap(fn (array $group): array => array_keys($group['permissions']))
            ->all();

        $validated = $request->validate([
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::in($allowedPermissions)],
        ]);

        $permissions = collect($validated['permissions'] ?? [])->unique()->values();

        DB::transaction(function () use ($role, $permissions): void {
            DB::table('employee_role_permissions')->where('role_id', $role)->delete();

            if ($permissions->isEmpty()) {
                return;
            }

            $now = now();
            DB::table('employee_role_permissions')->insert($permissions->map(fn (string $permission): array => [
                'role_id' => $role,
                'permission' => $permission,
                'created_at' => $now,
                'updated_at' => $now,
            ])->all());
        });

        return redirect()
            ->route('settings.employeeRoles.index', ['role_id' => $role, 'tab' => 'permissions'])
            ->with('success', 'Разрешения сохранены');
    }

    private function validateRole(Request $request, string $fid, ?int $ignoreRoleId = null): array
    {
        $projectScope = collect(HoldingScope::projectIdsFor($fid))->map(fn ($id): int => (int) $id)->all();

        return $request->validate([
            'name' => [
                'required',
                'string',
                'max:120',
                Rule::unique('employee_roles', 'name')
                    ->where(fn ($query) => $query->whereIn('project_id', $projectScope))
                    ->ignore($ignoreRoleId),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'sort' => ['nullable', 'integer', 'min:0', 'max:999999'],
        ]);
    }

    private function activeFid(): string
    {
        $sessionFid = trim((string) session('fid', ''));
        if ($sessionFid !== '') {
            return $sessionFid;
        }

        return (string) (Auth::user()->firma ?? '');
    }

    private function ensureTables(): void
    {
        abort_unless(
            Schema::hasTable('employee_roles') && Schema::hasTable('employee_role_permissions'),
            503,
            'Сначала выполните миграции базы данных.'
        );
    }

    private function rolesQuery(string $fid)
    {
        $this->ensureTables();

        return DB::table('employee_roles')
            ->whereIn('project_id', collect(HoldingScope::projectIdsFor($fid))->map(fn ($id): int => (int) $id)->all())
            ->orderBy('sort')
            ->orderBy('name')
            ->orderBy('id');
    }

    private function roleForProject(int $roleId, string $fid): ?object
    {
        return $this->rolesQuery($fid)->where('id', $roleId)->first();
    }

    private function rolePermissions(int $roleId): array
    {
        if (! Schema::hasTable('employee_role_permissions')) {
            return [];
        }

        return DB::table('employee_role_permissions')
            ->where('role_id', $roleId)
            ->pluck('permission')
            ->map(fn ($permission): string => (string) $permission)
            ->all();
    }

    private function roleMembersCount(int $roleId): int
    {
        if (! Schema::hasTable('team_memberships') || ! Schema::hasColumn('team_memberships', 'role_id')) {
            return 0;
        }

        return (int) DB::table('team_memberships')->where('role_id', $roleId)->count();
    }

    private function ensureDefaultRoles(string $fid): void
    {
        if (! Schema::hasTable('employee_roles')) {
            return;
        }

        $projectScope = collect(HoldingScope::projectIdsFor($fid))->map(fn ($id): int => (int) $id)->all();

        if (DB::table('employee_roles')->whereIn('project_id', $projectScope)->exists()) {
            return;
        }

        $now = now();
        DB::table('employee_roles')->insert([
            ['project_id' => (int) $fid, 'name' => 'Владелец', 'description' => 'Полный доступ к проекту.', 'sort' => 10, 'created_at' => $now, 'updated_at' => $now],
            ['project_id' => (int) $fid, 'name' => 'Администратор', 'description' => 'Управление операционными разделами и настройками.', 'sort' => 20, 'created_at' => $now, 'updated_at' => $now],
            ['project_id' => (int) $fid, 'name' => 'Менеджер', 'description' => 'Работа с документами, клиентами и товарами.', 'sort' => 30, 'created_at' => $now, 'updated_at' => $now],
            ['project_id' => (int) $fid, 'name' => 'Наблюдатель', 'description' => 'Просмотр отчетов и основных данных.', 'sort' => 40, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    private function permissionGroups(): array
    {
        return [
            'business' => [
                'label' => 'Бизнес',
                'permissions' => [
                    'orders.view' => 'Заказы: просмотр',
                    'orders.manage' => 'Заказы: создание и изменение',
                    'purchases.view' => 'Закупки: просмотр',
                    'purchases.manage' => 'Закупки: создание и изменение',
                    'goods.view' => 'Товары: просмотр',
                    'goods.manage' => 'Товары: управление',
                ],
            ],
            'finance' => [
                'label' => 'Финансы',
                'permissions' => [
                    'money.view' => 'Деньги: просмотр',
                    'money.manage' => 'Деньги: управление',
                    'reports.view' => 'Отчеты: просмотр',
                    'bank.view' => 'Банк: просмотр',
                    'bank.manage' => 'Банк: управление',
                ],
            ],
            'investing' => [
                'label' => 'Инвестирование',
                'permissions' => [
                    'assets.view' => 'Активы: просмотр',
                    'assets.manage' => 'Активы: управление',
                    'financing.view' => 'Финансирование: просмотр',
                    'financing.manage' => 'Финансирование: управление',
                ],
            ],
            'management' => [
                'label' => 'Менеджмент',
                'permissions' => [
                    'team.view' => 'Команда: просмотр',
                    'team.manage' => 'Команда: управление',
                    'settings.view' => 'Настройки: просмотр',
                    'settings.manage' => 'Настройки: управление',
                    'roles.manage' => 'Роли сотрудников: управление',
                ],
            ],
        ];
    }
}
