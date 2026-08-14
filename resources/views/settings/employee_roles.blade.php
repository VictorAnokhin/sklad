@extends('home')

@section('title', __('nav.employee_roles'))

@section('content')
@php
    $activeRole = $roles->firstWhere('id', $activeRoleId) ?? $roles->first();
    $showPermissionsTab = ($activeTab ?? 'roles') === 'permissions';
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

    <ul class="nav nav-tabs employee-roles-tabs mb-4" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link {{ $showPermissionsTab ? '' : 'active' }}" id="roles-tab" data-bs-toggle="tab" data-bs-target="#roles-pane" type="button" role="tab">Роли</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link {{ $showPermissionsTab ? 'active' : '' }}" id="permissions-tab" data-bs-toggle="tab" data-bs-target="#permissions-pane" type="button" role="tab">Разрешения</button>
        </li>
    </ul>

    <div class="tab-content">
        <div class="tab-pane fade {{ $showPermissionsTab ? '' : 'show active' }}" id="roles-pane" role="tabpanel" aria-labelledby="roles-tab">
            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="glass-card employee-role-panel">
                        <h2 class="h5 text-light mb-3">Новая роль</h2>
                        <form method="POST" action="{{ route('settings.employeeRoles.store') }}">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">Название</label>
                                <input type="text" name="name" class="form-control" maxlength="120" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Описание</label>
                                <textarea name="description" class="form-control" rows="4" maxlength="1000"></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Сортировка</label>
                                <input type="number" name="sort" class="form-control" value="100" min="0" max="999999">
                            </div>
                            <button type="submit" class="btn btn-success w-100">Добавить роль</button>
                        </form>
                    </div>
                </div>

                <div class="col-lg-8">
                    <div class="employee-role-list">
                        @forelse($roles as $role)
                            <div class="glass-card employee-role-card">
                                <form method="POST" action="{{ route('settings.employeeRoles.update', ['role' => $role->id]) }}">
                                    @csrf
                                    @method('PUT')
                                    <div class="row g-3 align-items-end">
                                        <div class="col-md-5">
                                            <label class="form-label">Название</label>
                                            <input type="text" name="name" class="form-control" value="{{ $role->name }}" maxlength="120" required>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label">Сортировка</label>
                                            <input type="number" name="sort" class="form-control" value="{{ (int) $role->sort }}" min="0" max="999999">
                                        </div>
                                        <div class="col-md-5">
                                            <div class="employee-role-card__meta">
                                                Назначено сотрудникам: <strong>{{ (int) $role->members_count }}</strong>
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label">Описание</label>
                                            <textarea name="description" class="form-control" rows="2" maxlength="1000">{{ $role->description }}</textarea>
                                        </div>
                                    </div>
                                    <div class="d-flex flex-wrap gap-2 mt-3">
                                        <button type="submit" class="btn btn-primary">Сохранить</button>
                                        <a href="{{ route('settings.employeeRoles.index', ['role_id' => $role->id, 'tab' => 'permissions']) }}" class="btn btn-outline-warning">Разрешения</a>
                                    </div>
                                </form>

                                <form method="POST" action="{{ route('settings.employeeRoles.destroy', ['role' => $role->id]) }}" class="employee-role-card__delete">
                                    @csrf
                                    @method('DELETE')
                                    <button
                                        type="submit"
                                        class="btn btn-outline-danger"
                                        {{ ((int) $role->members_count) > 0 ? 'disabled' : '' }}
                                        onclick="return confirm('Удалить роль?');"
                                    >Удалить</button>
                                </form>
                            </div>
                        @empty
                            <div class="alert alert-secondary">Роли еще не созданы.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-pane fade {{ $showPermissionsTab ? 'show active' : '' }}" id="permissions-pane" role="tabpanel" aria-labelledby="permissions-tab">
            <div class="glass-card employee-role-panel">
                @if($activeRole)
                    <form method="GET" action="{{ route('settings.employeeRoles.index') }}" class="row g-3 align-items-end mb-4">
                        <input type="hidden" name="tab" value="permissions">
                        <div class="col-md-6">
                            <label class="form-label">Роль</label>
                            <select name="role_id" class="form-select" onchange="this.form.submit()">
                                @foreach($roles as $role)
                                    <option value="{{ $role->id }}" {{ (int) $activeRole->id === (int) $role->id ? 'selected' : '' }}>{{ $role->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </form>

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
                    <div class="alert alert-secondary mb-0">Создайте роль, чтобы настроить разрешения.</div>
                @endif
            </div>
        </div>
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

    .employee-roles-tabs .nav-link {
        color: rgba(255, 255, 255, .68);
    }

    .employee-roles-tabs .nav-link.active {
        color: #111827;
        background: #facc15;
        border-color: #facc15;
    }

    .employee-role-list {
        display: grid;
        gap: 1rem;
    }

    .employee-role-card {
        position: relative;
        padding-right: 8.5rem !important;
    }

    .employee-role-card__meta {
        color: rgba(255, 255, 255, .62);
        font-size: .92rem;
    }

    .employee-role-card__delete {
        position: absolute;
        right: 1.25rem;
        bottom: 1.25rem;
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

    @media (max-width: 768px) {
        .employee-role-card {
            padding-right: 1.25rem !important;
        }

        .employee-role-card__delete {
            position: static;
            margin-top: .75rem;
        }

        .employee-permissions-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (window.location.hash === '#permissions-pane') {
            const trigger = document.getElementById('permissions-tab');
            if (trigger && window.bootstrap) {
                window.bootstrap.Tab.getOrCreateInstance(trigger).show();
            }
        }
    });
</script>
@endpush
@endsection
