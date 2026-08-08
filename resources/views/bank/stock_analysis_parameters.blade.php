@extends('home')

@section('title')
Параметры акций
@endsection

@section('content')
@php
    $parameters = ($parameters ?? collect())->sortBy([
        ['sort_order', 'asc'],
        ['id', 'asc'],
    ])->values();
    $parameterGroups = ($parameterGroups ?? collect())->sortBy([
        ['sort_order', 'asc'],
        ['name', 'asc'],
    ])->values();
    if ($parameterGroups->isEmpty()) {
        $parameterGroups = collect([(object) ['name' => 'Основные', 'sort_order' => 0]]);
    }
    $parametersByGroup = $parameters->groupBy(fn ($parameter) => trim((string) ($parameter->group_name ?? '')) ?: 'Основные');
    $groups = $parameterGroups->mapWithKeys(fn ($group) => [
        (string) $group->name => $parametersByGroup->get((string) $group->name, collect())->values(),
    ]);
    $groupNames = $parameterGroups->pluck('name')->map(fn ($name) => (string) $name)->values();
    $parameterData = $parameters->map(fn ($parameter) => [
        'id' => (int) $parameter->id,
        'label' => (string) $parameter->label,
        'field_key' => (string) $parameter->field_key,
        'group_name' => (string) ($parameter->group_name ?? 'Основные'),
        'description' => (string) ($parameter->description ?? ''),
        'settings' => (string) ($parameter->settings ?? ''),
        'update_url' => route('bank.stock-analysis.parameters.update', $parameter->id),
    ])->values();
@endphp

<div class="bank-page bank-stock-parameters-page" data-stock-parameters-page>
    @include('bank.partials.invest_nav')

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">Проверьте поля формы и попробуйте снова.</div>
    @endif

    <section class="bank-stock-parameters-header">
        <div>
            <div class="bank-label">Stock Analysis</div>
            <h1>Параметры акций</h1>
            <div class="bank-meta">Snapshot-параметры группируются по полю «Группа». Мультипликаторы настраиваются во вкладке «Анализ».</div>
        </div>
        <a class="btn btn-sm btn-outline-light" href="{{ route('bank.stock-analysis') }}">Назад к акциям</a>
    </section>

    @forelse($groups as $groupName => $groupParameters)
        <section class="bank-stock-parameters-panel">
            <div class="bank-stock-parameters-panel__head">
                <div>
                    <div class="bank-label">Группа</div>
                    <h2>{{ $groupName }}</h2>
                </div>
                <span>{{ $groupParameters->count() }}</span>
            </div>

            <div class="bank-stock-parameter-grid">
                @foreach($groupParameters as $parameter)
                    <article class="bank-stock-parameter-card">
                        <form method="POST"
                              action="{{ route('bank.stock-analysis.parameters.destroy', $parameter->id) }}"
                              class="bank-stock-parameter-delete"
                              onsubmit="return confirm('Удалить параметр {{ $parameter->label }}?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" aria-label="Удалить параметр {{ $parameter->label }}">×</button>
                        </form>
                        <button type="button"
                                class="bank-stock-parameter-menu"
                                data-stock-parameter-open
                                data-stock-parameter-id="{{ $parameter->id }}"
                                aria-label="Редактировать параметр {{ $parameter->label }}">⋮</button>
                        <strong>{{ $parameter->label }}</strong>
                        <code>{{ $parameter->field_key }}</code>
                        @if(trim((string) ($parameter->description ?? '')) !== '')
                            <p>{{ Str::limit($parameter->description, 80) }}</p>
                        @endif
                    </article>
                @endforeach
                <article class="bank-stock-parameter-card bank-stock-parameter-add-card">
                    <button type="button"
                            data-stock-parameter-create
                            data-stock-parameter-group="{{ $groupName }}"
                            aria-label="Добавить параметр в группу {{ $groupName }}">+</button>
                </article>
            </div>
        </section>
    @empty
        <section class="bank-stock-parameters-panel">
            <div class="bank-stock-parameter-empty">Параметры пока не созданы.</div>
        </section>
    @endforelse

    <div class="bank-modal" data-stock-parameter-modal hidden>
        <div class="bank-modal__backdrop" data-stock-parameter-close></div>
        <div class="bank-modal__dialog bank-stock-parameter-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="stockParameterModalTitle">
            <div class="bank-modal__header">
                <div>
                    <div class="bank-label">Параметр</div>
                    <h2 id="stockParameterModalTitle">Редактировать параметр</h2>
                </div>
                <button type="button" class="bank-modal__close" data-stock-parameter-close aria-label="Закрыть">×</button>
            </div>
            <form method="POST" class="bank-stock-parameter-modal-form" data-stock-parameter-form>
                @csrf
                <input type="hidden" name="_method" value="PUT" data-stock-parameter-method>
                <label>
                    <span>Название</span>
                    <input type="text" name="label" data-stock-parameter-label required>
                </label>
                <label>
                    <span>Значение</span>
                    <input type="text" name="field_key" data-stock-parameter-field required>
                </label>
                <label class="bank-stock-parameter-group-field" data-stock-group-combobox>
                    <span>Группа</span>
                    <input type="text" name="group_name" data-stock-parameter-group autocomplete="off" required>
                    <div class="bank-stock-parameter-group-options" data-stock-group-options hidden>
                        @foreach($groupNames as $groupName)
                            @php($groupDeleteFormId = 'stock-parameter-group-delete-' . $loop->index)
                            <div class="bank-stock-parameter-group-option">
                                <button type="button" data-stock-group-option data-value="{{ $groupName }}">{{ $groupName }}</button>
                                @if($groupName !== 'Основные')
                                    <button type="submit"
                                            form="{{ $groupDeleteFormId }}"
                                            aria-label="Удалить группу {{ $groupName }}">×</button>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </label>
                <label>
                    <span>Описание</span>
                    <textarea name="description" rows="4" data-stock-parameter-description></textarea>
                </label>
                <label>
                    <span>Настройка</span>
                    <textarea name="settings" rows="4" data-stock-parameter-settings></textarea>
                </label>
                <div class="bank-modal__actions">
                    <button type="button" class="btn btn-secondary" data-stock-parameter-close>Отмена</button>
                    <button type="submit" class="btn btn-primary">Сохранить</button>
                </div>
            </form>
        </div>
    </div>

    @foreach($groupNames as $groupName)
        @if($groupName !== 'Основные')
            <form id="stock-parameter-group-delete-{{ $loop->index }}"
                  method="POST"
                  action="{{ route('bank.stock-analysis.parameter-groups.destroy') }}"
                  onsubmit="return confirm('Удалить группу {{ $groupName }}? Параметры будут перенесены в Основные.')"
                  hidden>
                @csrf
                @method('DELETE')
                <input type="hidden" name="group_name" value="{{ $groupName }}">
            </form>
        @endif
    @endforeach
</div>

@include('bank.partials.styles')

<style>
    .bank-stock-parameters-page {
        max-width: 1180px;
    }

    .bank-stock-parameters-header,
    .bank-stock-parameters-panel {
        border: 1px solid rgba(148, 163, 184, 0.2);
        border-radius: 8px;
        background: rgba(15, 23, 42, 0.76);
        box-shadow: 0 12px 30px rgba(0, 0, 0, 0.22);
    }

    .bank-stock-parameters-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 14px;
        padding: 16px;
        margin-bottom: 12px;
    }

    .bank-stock-parameters-header h1,
    .bank-stock-parameters-panel h2 {
        margin: 4px 0;
        color: #f8fafc;
        font-weight: 800;
        line-height: 1.15;
    }

    .bank-stock-parameters-header h1 {
        font-size: 1.55rem;
    }

    .bank-stock-parameters-panel {
        padding: 14px;
        margin-bottom: 12px;
    }

    .bank-stock-parameters-panel__head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 12px;
    }

    .bank-stock-parameters-panel__head h2 {
        font-size: 1.05rem;
    }

    .bank-stock-parameters-panel__head > span {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 32px;
        height: 32px;
        border-radius: 8px;
        background: rgba(251, 191, 36, 0.14);
        color: #fbbf24;
        font-weight: 800;
    }

    .bank-stock-parameter-grid {
        display: grid;
        grid-template-columns: repeat(5, minmax(0, 1fr));
        gap: 10px;
    }

    .bank-stock-parameter-card {
        position: relative;
        min-height: 118px;
        padding: 34px 10px 10px;
        border: 1px solid rgba(148, 163, 184, 0.16);
        border-radius: 8px;
        background: rgba(2, 6, 23, 0.28);
        overflow: hidden;
    }

    .bank-stock-parameter-card strong {
        display: block;
        color: #f8fafc;
        font-size: 0.9rem;
        line-height: 1.25;
        overflow-wrap: anywhere;
    }

    .bank-stock-parameter-card code {
        display: inline-block;
        max-width: 100%;
        margin-top: 7px;
        padding: 3px 6px;
        border-radius: 6px;
        background: rgba(2, 6, 23, 0.5);
        color: #fbbf24;
        font-size: 0.76rem;
        overflow-wrap: anywhere;
    }

    .bank-stock-parameter-card p {
        margin: 7px 0 0;
        color: rgba(203, 213, 225, 0.74);
        font-size: 0.76rem;
        line-height: 1.35;
    }

    .bank-stock-parameter-add-card {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 118px;
        padding: 10px;
        border-style: dashed;
    }

    .bank-stock-parameter-add-card button {
        width: 58px;
        height: 58px;
        border: 1px solid rgba(251, 191, 36, 0.42);
        border-radius: 50%;
        background: rgba(251, 191, 36, 0.1);
        color: #fbbf24;
        font-size: 2.2rem;
        line-height: 1;
        cursor: pointer;
    }

    .bank-stock-parameter-add-card button:hover {
        background: rgba(251, 191, 36, 0.18);
    }

    .bank-stock-parameter-delete,
    .bank-stock-parameter-menu {
        position: absolute;
        top: 7px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 26px;
        height: 26px;
        margin: 0;
        border-radius: 7px;
    }

    .bank-stock-parameter-delete {
        left: 7px;
        border: 0;
        background: transparent;
        color: #fbbf24;
    }

    .bank-stock-parameter-delete button,
    .bank-stock-parameter-menu {
        border: 0;
        background: transparent;
        color: inherit;
        font: inherit;
        line-height: 1;
        cursor: pointer;
    }

    .bank-stock-parameter-menu {
        right: 7px;
        border: 1px solid rgba(148, 163, 184, 0.22);
        background: rgba(15, 23, 42, 0.82);
        color: #e5e7eb;
        font-size: 19px;
    }

    .bank-stock-parameter-modal__dialog {
        width: min(620px, calc(100vw - 24px));
        max-height: min(720px, calc(100vh - 24px));
        overflow: visible;
    }

    .bank-stock-parameter-modal-form {
        display: grid;
        gap: 10px;
    }

    .bank-stock-parameter-modal-form label {
        display: grid;
        gap: 5px;
        margin: 0;
        color: rgba(226, 232, 240, 0.86);
        font-size: 0.84rem;
        font-weight: 700;
    }

    .bank-stock-parameter-modal-form label span {
        color: rgba(148, 163, 184, 0.92);
        font-size: 0.72rem;
        text-transform: uppercase;
    }

    .bank-stock-parameter-modal-form input,
    .bank-stock-parameter-modal-form textarea {
        width: 100%;
        border: 1px solid rgba(148, 163, 184, 0.22);
        border-radius: 8px;
        background: rgba(15, 23, 42, 0.82);
        color: #f8fafc;
        font: inherit;
        font-size: 0.88rem;
        padding: 8px 10px;
    }

    .bank-stock-parameter-modal-form textarea {
        resize: vertical;
    }

    .bank-stock-parameter-group-field {
        position: relative;
    }

    .bank-stock-parameter-group-options {
        position: absolute;
        top: calc(100% + 4px);
        left: 0;
        right: 0;
        z-index: 10020;
        max-height: 190px;
        overflow-y: auto;
        padding: 4px;
        border: 1px solid rgba(255, 255, 255, 0.16);
        border-radius: 8px;
        background: #101827;
        box-shadow: 0 16px 32px rgba(0, 0, 0, 0.35);
    }

    .bank-stock-parameter-group-options[hidden] {
        display: none;
    }

    .bank-stock-parameter-group-option {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 30px;
        gap: 4px;
        align-items: center;
    }

    .bank-stock-parameter-group-option > button {
        min-height: 32px;
        border: 0;
        border-radius: 6px;
        background: transparent;
        color: var(--foreground);
        font: inherit;
        font-size: 0.86rem;
        text-align: left;
        cursor: pointer;
    }

    .bank-stock-parameter-group-option > button:first-child {
        padding: 6px 8px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .bank-stock-parameter-group-option > button:last-child:not(:first-child) {
        width: 30px;
        text-align: center;
        color: #f43f5e;
    }

    .bank-stock-parameter-group-option > button:hover {
        background: rgba(251, 191, 36, 0.14);
    }

    .bank-stock-parameter-empty {
        padding: 18px;
        color: rgba(203, 213, 225, 0.74);
        text-align: center;
    }

    @media (max-width: 1100px) {
        .bank-stock-parameter-grid {
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }
    }

    @media (max-width: 860px) {
        .bank-stock-parameters-header {
            flex-direction: column;
        }

        .bank-stock-parameter-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 520px) {
        .bank-stock-parameter-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const root = document.querySelector('[data-stock-parameters-page]');
        if (!root) return;

        const parameters = @json($parameterData);
        const parameterStoreUrl = @json(route('bank.stock-analysis.parameters.store'));
        const modal = root.querySelector('[data-stock-parameter-modal]');
        const form = root.querySelector('[data-stock-parameter-form]');
        const methodInput = root.querySelector('[data-stock-parameter-method]');
        const closeButtons = root.querySelectorAll('[data-stock-parameter-close]');
        const labelInput = root.querySelector('[data-stock-parameter-label]');
        const fieldInput = root.querySelector('[data-stock-parameter-field]');
        const groupInput = root.querySelector('[data-stock-parameter-group]');
        const descriptionInput = root.querySelector('[data-stock-parameter-description]');
        const settingsInput = root.querySelector('[data-stock-parameter-settings]');
        const groupOptions = root.querySelector('[data-stock-group-options]');

        const closeModal = () => {
            if (!modal) return;
            modal.hidden = true;
            document.body.style.overflow = '';
            if (groupOptions) groupOptions.hidden = true;
        };

        const openModal = (mode = 'edit', parameter = null, groupName = 'Основные') => {
            if (!modal || !form) return;
            const isEdit = mode === 'edit' && parameter;

            form.action = isEdit ? parameter.update_url : parameterStoreUrl;
            if (methodInput) {
                methodInput.value = isEdit ? 'PUT' : 'POST';
                methodInput.disabled = !isEdit;
            }
            if (labelInput) labelInput.value = isEdit ? (parameter.label || '') : '';
            if (fieldInput) fieldInput.value = isEdit ? (parameter.field_key || '') : '';
            if (groupInput) groupInput.value = isEdit ? (parameter.group_name || 'Основные') : (groupName || 'Основные');
            if (descriptionInput) descriptionInput.value = isEdit ? (parameter.description || '') : '';
            if (settingsInput) settingsInput.value = isEdit ? (parameter.settings || '') : '';
            modal.hidden = false;
            document.body.style.overflow = 'hidden';
            setTimeout(() => labelInput?.focus(), 0);
        };

        root.querySelectorAll('[data-stock-parameter-open]').forEach((button) => {
            button.addEventListener('click', () => {
                const parameter = parameters.find((item) => String(item.id) === String(button.dataset.stockParameterId));
                openModal('edit', parameter);
            });
        });
        root.querySelectorAll('[data-stock-parameter-create]').forEach((button) => {
            button.addEventListener('click', () => {
                openModal('create', null, button.dataset.stockParameterGroup || 'Основные');
            });
        });

        closeButtons.forEach((button) => button.addEventListener('click', closeModal));
        modal?.addEventListener('click', (event) => {
            if (event.target === modal) closeModal();
        });

        const showGroupOptions = () => {
            if (!groupOptions) return;
            const search = (groupInput?.value || '').trim().toLowerCase();
            let visible = 0;

            groupOptions.querySelectorAll('[data-stock-group-option]').forEach((option) => {
                const value = (option.dataset.value || option.textContent || '').toLowerCase();
                const shouldShow = !search || value.includes(search);
                option.closest('.bank-stock-parameter-group-option').hidden = !shouldShow;
                if (shouldShow) visible += 1;
            });
            groupOptions.hidden = visible === 0;
        };

        groupInput?.addEventListener('focus', showGroupOptions);
        groupInput?.addEventListener('click', showGroupOptions);
        groupInput?.addEventListener('input', showGroupOptions);
        root.querySelectorAll('[data-stock-group-option]').forEach((option) => {
            option.addEventListener('click', () => {
                if (groupInput) groupInput.value = option.dataset.value || option.textContent.trim();
                if (groupOptions) groupOptions.hidden = true;
            });
        });

        document.addEventListener('click', (event) => {
            if (!event.target.closest('[data-stock-group-combobox]') && groupOptions) {
                groupOptions.hidden = true;
            }
        });
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                if (groupOptions) groupOptions.hidden = true;
                if (modal && !modal.hidden) closeModal();
            }
        });
    });
</script>
@endpush
@endsection
