@extends('home')

@section('title', __('nav.employee_roles'))

@section('content')
@php
    $activeRole = $roles->firstWhere('id', $activeRoleId) ?? $roles->first();
    $isCreating = request()->query('mode') === 'create' || ! $activeRole;
    $roleFormAction = $isCreating
        ? route('settings.employeeRoles.store')
        : route('settings.employeeRoles.update', ['role' => $activeRole->id]);
@endphp

<div class="container mt-4 employee-roles-page" data-bs-theme="dark">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h3 text-light mb-1">{{ __('nav.employee_roles') }}</h1>
            <p class="text-muted mb-0">Роли видны всем компаниям холдинга, а назначение роли сохраняется отдельно для каждой компании сотрудника.</p>
        </div>
        <a href="{{ route('team') }}" class="btn btn-outline-secondary">Команда</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">
            @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <div class="employee-roles-layout">
        <aside class="glass-card employee-roles-sidebar">
            <a href="{{ route('settings.employeeRoles.index', ['mode' => 'create']) }}" class="btn btn-warning w-100 mb-3">Новая роль</a>

            <div class="employee-roles-list" aria-label="Список ролей">
                @forelse($roles as $role)
                    <a
                        href="{{ route('settings.employeeRoles.index', ['role_id' => $role->id]) }}"
                        class="employee-roles-list__item {{ ! $isCreating && (int) $activeRole->id === (int) $role->id ? 'is-active' : '' }}"
                    >
                        <span class="employee-roles-list__name">{{ $role->name }}</span>
                        <span class="employee-roles-list__meta">{{ (int) $role->members_count }} сотрудников</span>
                    </a>
                @empty
                    <div class="employee-roles-empty">Роли еще не созданы.</div>
                @endforelse
            </div>
        </aside>

        <main class="employee-roles-workspace">
            <section class="glass-card employee-role-editor">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
                    <div>
                        <h2 class="h5 text-light mb-1">{{ $isCreating ? 'Новая роль' : 'Редактирование роли' }}</h2>
                        @if(! $isCreating)
                            <div class="text-muted small">Назначено сотрудникам: <strong>{{ (int) $activeRole->members_count }}</strong></div>
                        @endif
                    </div>

                    @if(! $isCreating)
                        <form method="POST" action="{{ route('settings.employeeRoles.destroy', ['role' => $activeRole->id]) }}">
                            @csrf
                            @method('DELETE')
                            <button
                                type="submit"
                                class="btn btn-outline-danger"
                                {{ ((int) $activeRole->members_count) > 0 ? 'disabled' : '' }}
                                onclick="return confirm('Удалить роль?');"
                            >Удалить</button>
                        </form>
                    @endif
                </div>

                <form method="POST" action="{{ $roleFormAction }}">
                    @csrf
                    @if(! $isCreating)
                        @method('PUT')
                    @endif

                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label">Название</label>
                            <input type="text" name="name" class="form-control" value="{{ old('name', $isCreating ? '' : $activeRole->name) }}" maxlength="120" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Сортировка</label>
                            <input type="number" name="sort" class="form-control" value="{{ old('sort', $isCreating ? 100 : (int) $activeRole->sort) }}" min="0" max="999999">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Описание</label>
                            <textarea name="description" class="form-control" rows="4" maxlength="1000">{{ old('description', $isCreating ? '' : $activeRole->description) }}</textarea>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-success mt-3">{{ $isCreating ? 'Создать роль' : 'Сохранить роль' }}</button>
                </form>
            </section>

            <section class="glass-card employee-role-permissions">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
                    <div>
                        <h2 class="h5 text-light mb-1">Разрешения</h2>
                        <div class="text-muted small">
                            @if($isCreating)
                                Сначала создайте роль, затем настройте разрешения.
                            @else
                                {{ $activeRole->name }}
                            @endif
                        </div>
                    </div>
                </div>

                @if(! $isCreating && $activeRole)
                    <form method="POST" action="{{ route('settings.employeeRoles.permissions.update', ['role' => $activeRole->id]) }}">
                        @csrf
                        @method('PUT')
                        <div class="employee-permissions-grid">
                            @foreach($permissionGroups as $group)
                                <section class="employee-permission-group">
                                    <h3>{{ $group['label'] }}</h3>
                                    @foreach($group['permissions'] as $permission => $label)
                                        <label class="employee-permission-check">
                                            <input
                                                type="checkbox"
                                                name="permissions[]"
                                                value="{{ $permission }}"
                                                {{ in_array($permission, $activeRole->permissions ?? [], true) ? 'checked' : '' }}
                                            >
                                            <span>{{ $label }}</span>
                                        </label>
                                    @endforeach
                                </section>
                            @endforeach
                        </div>
                        <button type="submit" class="btn btn-success mt-4">Сохранить разрешения</button>
                    </form>
                @else
                    <div class="alert alert-secondary mb-0">Форма разрешений появится после создания роли.</div>
                @endif
            </section>
        </main>
    </div>
</div>

<style>
    .employee-roles-page .glass-card {
        border: 1px solid rgba(255, 255, 255, .12);
        border-radius: 16px;
        background: rgba(12, 16, 24, .82);
        box-shadow: 0 18px 50px rgba(0, 0, 0, .25);
        padding: 1.25rem;
    }

    .employee-roles-layout {
        display: grid;
        grid-template-columns: minmax(230px, 280px) minmax(0, 1fr);
        gap: 1rem;
        align-items: start;
    }

    .employee-roles-sidebar {
        position: sticky;
        top: 1rem;
        max-height: calc(100vh - 7rem);
        overflow: auto;
    }

    .employee-roles-list {
        display: grid;
        gap: .6rem;
    }

    .employee-roles-list__item {
        display: grid;
        gap: .2rem;
        padding: .8rem .9rem;
        border: 1px solid rgba(255, 255, 255, .1);
        border-radius: 12px;
        color: rgba(255, 255, 255, .82);
        text-decoration: none;
        background: rgba(255, 255, 255, .03);
    }

    .employee-roles-list__item:hover,
    .employee-roles-list__item.is-active {
        border-color: rgba(250, 204, 21, .72);
        color: #fff;
        background: rgba(250, 204, 21, .12);
    }

    .employee-roles-list__name {
        font-weight: 700;
    }

    .employee-roles-list__meta,
    .employee-roles-empty {
        color: rgba(255, 255, 255, .58);
        font-size: .86rem;
    }

    .employee-roles-workspace {
        display: grid;
        gap: 1rem;
    }

    .employee-permissions-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 1rem;
    }

    .employee-permission-group {
        border: 1px solid rgba(255, 255, 255, .1);
        border-radius: 12px;
        padding: 1rem;
        background: rgba(255, 255, 255, .03);
    }

    .employee-permission-group h3 {
        color: #fff;
        font-size: 1rem;
        margin-bottom: .85rem;
    }

    .employee-permission-check {
        display: flex;
        align-items: flex-start;
        gap: .65rem;
        color: rgba(255, 255, 255, .78);
        margin-bottom: .7rem;
    }

    .employee-permission-check:last-child {
        margin-bottom: 0;
    }

    @media (max-width: 900px) {
        .employee-roles-layout {
            grid-template-columns: 1fr;
        }

        .employee-roles-sidebar {
            position: static;
            max-height: none;
        }
    }

    @media (max-width: 768px) {
        .employee-permissions-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
@endsection
