@extends('home')

@section('title', 'Финансирование')

@section('content')
@php
    $agreementTypes = \App\Models\FinancingAgreement::typeOptions();
    $operationTypes = \App\Models\FinancingOperation::operationOptions();
@endphp

<div class="container py-4 financing-page" data-bs-theme="dark">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
        <div>
            <h1 class="h3 mb-1 text-light">Финансирование</h1>
            <div class="text-muted">Кредиты, инвесторы, проценты и дивиденды с двойной записью.</div>
        </div>
        <a href="{{ route('settings.index') }}" class="btn btn-outline-light btn-sm">Виды платежей</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <div class="row g-3 mb-4">
        <div class="col-md-3"><div class="fin-stat"><div class="fin-stat__label">Тело кредитов</div><div class="fin-stat__value">{{ number_format((float) $summary['principal_balance'], 2, '.', ' ') }}</div></div></div>
        <div class="col-md-3"><div class="fin-stat"><div class="fin-stat__label">Проценты к оплате</div><div class="fin-stat__value">{{ number_format((float) $summary['accrued_interest'], 2, '.', ' ') }}</div></div></div>
        <div class="col-md-3"><div class="fin-stat"><div class="fin-stat__label">Капитал инвесторов</div><div class="fin-stat__value">{{ number_format((float) $summary['equity_amount'], 2, '.', ' ') }}</div></div></div>
        <div class="col-md-3"><div class="fin-stat"><div class="fin-stat__label">Дивиденды к выплате</div><div class="fin-stat__value">{{ number_format((float) $summary['dividends_payable'], 2, '.', ' ') }}</div></div></div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-xl-5">
            <div class="fin-panel h-100">
                <h2 class="h5 mb-3 text-light">Новый договор</h2>
                <form method="POST" action="{{ route('document.financing.agreements.store') }}" class="row g-3">
                    @csrf
                    <div class="col-md-6">
                        <label class="form-label">Тип</label>
                        <select name="agreement_type" class="form-select">
                            @foreach($agreementTypes as $value => $label)
                                <option value="{{ $value }}" @selected(old('agreement_type') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Контрагент</label>
                        <div class="fin-client-search">
                            <div class="d-flex gap-1">
                                <input type="text" name="counterparty_name" class="form-control flex-grow-1" value="{{ old('counterparty_name') }}" placeholder="Поиск в users..." autocomplete="off" data-fin-client-search>
                                <button type="button" class="btn btn-outline-secondary" data-fin-client-edit style="display:none;">Изменить</button>
                                <button type="button" class="btn btn-outline-primary" data-fin-client-new>Новый</button>
                            </div>
                            <div class="list-group fin-client-search__results" data-fin-client-results></div>
                            <input type="hidden" name="counterparty_id" data-fin-client-id value="{{ old('counterparty_id') }}">
                            <div class="alert alert-warning py-1 mt-1 mb-0 fin-client-search__details" data-fin-client-details>Контрагент не выбран</div>
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Название договора</label>
                        <input type="text" name="name" class="form-control" value="{{ old('name') }}" required placeholder="Кредит Ощадбанк №..., Инвестор Иванов">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Номер</label>
                        <input type="text" name="agreement_number" class="form-control" value="{{ old('agreement_number') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Дата</label>
                        <input type="date" name="agreement_date" class="form-control" value="{{ old('agreement_date', now()->toDateString()) }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Погашение</label>
                        <input type="date" name="maturity_date" class="form-control" value="{{ old('maturity_date') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Лимит/сумма</label>
                        <input type="number" step="0.01" min="0" name="principal_amount" class="form-control" value="{{ old('principal_amount') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Ставка, %</label>
                        <input type="number" step="0.0001" min="0" max="100" name="interest_rate" class="form-control" value="{{ old('interest_rate') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Доля, %</label>
                        <input type="number" step="0.0001" min="0" max="100" name="equity_percent" class="form-control" value="{{ old('equity_percent') }}">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Примечание</label>
                        <input type="text" name="description" class="form-control" value="{{ old('description') }}">
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-warning px-4">Создать договор</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-xl-7">
            <div class="fin-panel h-100">
                <h2 class="h5 mb-3 text-light">Новая операция</h2>
                <form method="POST" action="{{ route('document.financing.operations.store') }}" class="row g-3">
                    @csrf
                    <div class="col-md-4">
                        <label class="form-label">Договор</label>
                        <select name="financing_agreement_id" class="form-select" required>
                            <option value="">Выберите договор</option>
                            @foreach($agreements as $agreement)
                                <option value="{{ $agreement->id }}" @selected((string) old('financing_agreement_id') === (string) $agreement->id)>
                                    {{ $agreement->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Тип операции</label>
                        <select name="operation_type" class="form-select">
                            @foreach($operationTypes as $value => $label)
                                <option value="{{ $value }}" @selected(old('operation_type') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Дата</label>
                        <input type="date" name="operation_date" class="form-control" value="{{ old('operation_date', now()->toDateString()) }}" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Сумма</label>
                        <input type="number" step="0.01" min="0.01" name="amount" class="form-control" value="{{ old('amount') }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Денежный счет</label>
                        <select name="cash_account_id" class="form-select">
                            <option value="">Без денежного потока</option>
                            @foreach($cashAccounts as $cash)
                                <option value="{{ $cash->id }}" @selected((string) old('cash_account_id') === (string) $cash->id)>{{ $cash->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Вид платежа</label>
                        <select name="payment_type_id" class="form-select">
                            <option value="">Без вида платежа</option>
                            @foreach($paymentTypes as $paymentType)
                                <option value="{{ $paymentType->id }}" @selected((string) old('payment_type_id') === (string) $paymentType->id)>{{ $paymentType->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Провести сразу</label>
                        <div class="form-check form-switch mt-2">
                            <input class="form-check-input" type="checkbox" name="post_after_save" value="1" id="fin-post-after-save" @checked(old('post_after_save'))>
                            <label class="form-check-label" for="fin-post-after-save">Да</label>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <button type="submit" class="btn btn-warning w-100">Сохранить</button>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Примечание</label>
                        <input type="text" name="description" class="form-control" value="{{ old('description') }}">
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-xl-5">
            <div class="fin-panel h-100">
                <h2 class="h5 mb-3 text-light">Договоры</h2>
                <div class="table-responsive">
                    <table class="table table-sm table-dark table-hover align-middle fin-table">
                        <thead>
                            <tr>
                                <th>Договор</th>
                                <th class="text-end">Кредит</th>
                                <th class="text-end">Капитал</th>
                                <th>Статус</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($agreements as $agreement)
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $agreement->name }}</div>
                                        <div class="text-muted small">{{ \App\Models\FinancingAgreement::typeLabel($agreement->agreement_type) }} · {{ $agreement->counterparty_name ?: 'контрагент не указан' }}</div>
                                    </td>
                                    <td class="text-end">
                                        <div>{{ number_format((float) $agreement->principal_balance, 2, '.', ' ') }}</div>
                                        <div class="text-muted small">проц. {{ number_format((float) $agreement->accrued_interest, 2, '.', ' ') }}</div>
                                    </td>
                                    <td class="text-end">
                                        <div>{{ number_format((float) $agreement->equity_amount, 2, '.', ' ') }}</div>
                                        <div class="text-muted small">див. {{ number_format((float) $agreement->dividends_payable, 2, '.', ' ') }}</div>
                                    </td>
                                    <td><span class="badge bg-secondary">{{ $agreement->status }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-muted text-center py-4">Договоров пока нет.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-xl-7">
            <div class="fin-panel h-100">
                <h2 class="h5 mb-3 text-light">Журнал операций</h2>
                <div class="table-responsive">
                    <table class="table table-sm table-dark table-hover align-middle fin-table">
                        <thead>
                            <tr>
                                <th>Дата</th>
                                <th>Операция</th>
                                <th>Договор</th>
                                <th class="text-end">Сумма</th>
                                <th>Платеж</th>
                                <th class="text-end">Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($operations as $operation)
                                <tr>
                                    <td>{{ $operation->operation_date ? \Carbon\Carbon::parse($operation->operation_date)->format('d.m.Y') : '' }}</td>
                                    <td>
                                        <div class="fw-semibold">{{ \App\Models\FinancingOperation::operationLabel($operation->operation_type) }}</div>
                                        <div class="small {{ $operation->provodka ? 'text-success' : 'text-muted' }}">{{ $operation->provodka ? 'Проведено' : 'Черновик' }}</div>
                                    </td>
                                    <td>{{ $operation->agreement_name ?: 'Без договора' }}</td>
                                    <td class="text-end fw-semibold">{{ number_format((float) $operation->amount, 2, '.', ' ') }}</td>
                                    <td>
                                        <div>{{ $operation->payment_type_name ?: 'Без вида' }}</div>
                                        <div class="text-muted small">{{ $operation->cash_account_name ?: 'Без счета' }}</div>
                                    </td>
                                    <td class="text-end">
                                        @if($operation->provodka)
                                            <form method="POST" action="{{ route('document.financing.operations.reverse', $operation->id) }}" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-outline-warning btn-sm">Снять</button>
                                            </form>
                                        @else
                                            <form method="POST" action="{{ route('document.financing.operations.post', $operation->id) }}" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-outline-success btn-sm">Провести</button>
                                            </form>
                                            <form method="POST" action="{{ route('document.financing.operations.destroy', $operation->id) }}" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-outline-danger btn-sm">Удалить</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-muted text-center py-4">Операций пока нет.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="finClientModal" tabindex="-1" aria-labelledby="finClientModalLabel" aria-hidden="true" data-bs-theme="dark">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-dark text-light border-secondary">
            <div class="modal-header">
                <h5 class="modal-title" id="finClientModalLabel">Контрагент</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="finClientModalId" value="0">
                <div class="row g-2">
                    <div class="col-12">
                        <label class="form-label small mb-1">Группа</label>
                        <input type="text" class="form-control form-control-sm" id="finClientGroupName" list="finClientGroupList" autocomplete="off">
                        <input type="hidden" id="finClientGroupId" value="">
                        <datalist id="finClientGroupList">
                            @foreach(($clientGroups ?? collect()) as $group)
                                <option value="{{ $group->name }}" data-id="{{ $group->id }}"></option>
                            @endforeach
                        </datalist>
                    </div>
                    <div class="col-12">
                        <label class="form-label small mb-1">Организация</label>
                        <input type="text" class="form-control form-control-sm" id="finClientOrgname">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small mb-1">Фамилия</label>
                        <input type="text" class="form-control form-control-sm" id="finClientSecondname">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small mb-1">Имя</label>
                        <input type="text" class="form-control form-control-sm" id="finClientName">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small mb-1">Телефон</label>
                        <input type="text" class="form-control form-control-sm" id="finClientPhone" placeholder="+38 (000) 00-00-000" maxlength="19" inputmode="tel">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small mb-1">Статус</label>
                        <select class="form-select form-select-sm" id="finClientStatus">
                            <option value="">Выберите статус</option>
                            @foreach(($clientStatuses ?? collect()) as $status)
                                <option value="{{ $status->id }}">{{ $status->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small mb-1">Город</label>
                        <input type="text" class="form-control form-control-sm" id="finClientCity">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small mb-1">Область</label>
                        <input type="text" class="form-control form-control-sm" id="finClientRegion">
                    </div>
                    <div class="col-12">
                        <label class="form-label small mb-1">Отделение НП</label>
                        <input type="text" class="form-control form-control-sm" id="finClientPoshta">
                    </div>
                </div>
                <div class="alert alert-danger mt-3 mb-0 py-2" id="finClientError" style="display:none;"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Отмена</button>
                <button type="button" class="btn btn-warning" id="finClientSaveBtn">Сохранить</button>
            </div>
        </div>
    </div>
</div>

<style>
    .financing-page { color: #f6efe6; }
    .fin-panel,
    .fin-stat {
        background: rgba(30, 22, 16, 0.82);
        border: 1px solid rgba(229, 177, 84, 0.22);
        border-radius: 12px;
        padding: 18px;
        box-shadow: 0 18px 50px rgba(0, 0, 0, 0.22);
    }
    .fin-stat__label {
        color: rgba(246, 239, 230, 0.68);
        font-size: 13px;
        margin-bottom: 6px;
    }
    .fin-stat__value {
        color: #e5b154;
        font-size: 22px;
        font-weight: 700;
    }
    .fin-table th {
        color: #e5b154;
        border-color: rgba(229, 177, 84, 0.24);
    }
    .fin-table td {
        border-color: rgba(255, 255, 255, 0.1);
    }
    .financing-page .form-control,
    .financing-page .form-select {
        background-color: rgba(255, 255, 255, 0.06);
        border-color: rgba(255, 255, 255, 0.18);
        color: #fff;
    }
    .fin-client-search {
        position: relative;
    }
    .fin-client-search__results {
        display: none;
        left: 0;
        position: absolute;
        right: 0;
        top: 40px;
        z-index: 1050;
    }
    .fin-client-search__details {
        font-size: 0.85rem;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const searchInput = document.querySelector('[data-fin-client-search]');
        const results = document.querySelector('[data-fin-client-results]');
        const clientId = document.querySelector('[data-fin-client-id]');
        const details = document.querySelector('[data-fin-client-details]');
        const editBtn = document.querySelector('[data-fin-client-edit]');
        const newBtn = document.querySelector('[data-fin-client-new]');
        const modalEl = document.getElementById('finClientModal');
        const modal = modalEl ? new bootstrap.Modal(modalEl) : null;
        const saveBtn = document.getElementById('finClientSaveBtn');
        const errorBox = document.getElementById('finClientError');
        const fields = {
            id: document.getElementById('finClientModalId'),
            orgname: document.getElementById('finClientOrgname'),
            secondname: document.getElementById('finClientSecondname'),
            name: document.getElementById('finClientName'),
            phone: document.getElementById('finClientPhone'),
            status: document.getElementById('finClientStatus'),
            city: document.getElementById('finClientCity'),
            region: document.getElementById('finClientRegion'),
            poshta: document.getElementById('finClientPoshta'),
            groupName: document.getElementById('finClientGroupName'),
            groupId: document.getElementById('finClientGroupId'),
        };
        const groupOptions = Array.from(document.querySelectorAll('#finClientGroupList option'));
        let searchTimer = null;

        if (!searchInput || !results || !clientId || !details || !modal) {
            return;
        }

        const formatName = (user) => [user.secondname || '', user.name || ''].filter(Boolean).join(' ').trim();
        const displayName = (user) => [user.orgname || '', formatName(user)].filter(Boolean).join(' ').trim() || `User #${user.id}`;
        const detailsHtml = (user) => {
            const region = user.region ? `${user.region} | ` : '';
            const poshta = user.poshta ? ` | ${user.poshta}` : '';
            const org = user.orgname ? `<strong>${user.orgname}</strong> | ` : '';
            return `${org}${formatName(user)}<br><small>${user.phone || ''} | ${region}${user.city || ''}${poshta}</small>`;
        };
        const syncGroupId = () => {
            const value = String(fields.groupName?.value || '').trim().toLowerCase();
            const match = groupOptions.find((option) => String(option.value || '').trim().toLowerCase() === value);
            if (fields.groupId) {
                fields.groupId.value = match?.dataset.id || '';
            }
        };
        const clearModal = () => {
            Object.values(fields).forEach((field) => {
                if (field) field.value = '';
            });
            if (fields.id) fields.id.value = '0';
            if (errorBox) errorBox.style.display = 'none';
        };
        const fillModalFromSelected = () => {
            fields.id.value = clientId.value || '0';
            fields.orgname.value = clientId.dataset.orgname || '';
            fields.secondname.value = clientId.dataset.secondname || '';
            fields.name.value = clientId.dataset.name || '';
            fields.phone.value = clientId.dataset.phone || '';
            fields.status.value = clientId.dataset.status || '';
            fields.city.value = clientId.dataset.city || '';
            fields.region.value = clientId.dataset.region || '';
            fields.poshta.value = clientId.dataset.poshta || '';
            fields.groupName.value = clientId.dataset.usergroupName || '';
            fields.groupId.value = clientId.dataset.usergroup || '';
            if (errorBox) errorBox.style.display = 'none';
        };
        const selectUser = (user) => {
            clientId.value = user.id || '';
            clientId.dataset.orgname = user.orgname || '';
            clientId.dataset.name = user.name || '';
            clientId.dataset.secondname = user.secondname || '';
            clientId.dataset.phone = user.phone || '';
            clientId.dataset.city = user.city || '';
            clientId.dataset.region = user.region || '';
            clientId.dataset.poshta = user.poshta || '';
            clientId.dataset.status = user.idstatus || '';
            clientId.dataset.usergroup = user.usergroup || '';
            clientId.dataset.usergroupName = user.usergroup_name || '';
            searchInput.value = displayName(user);
            details.className = 'alert alert-secondary py-1 mt-1 mb-0 fin-client-search__details';
            details.innerHTML = detailsHtml(user);
            if (editBtn) editBtn.style.display = 'inline-block';
            results.style.display = 'none';
        };
        const performSearch = () => {
            const q = searchInput.value.trim();
            if (q.length < 2) {
                results.style.display = 'none';
                return;
            }

            fetch("{{ route('client.search') }}?" + new URLSearchParams({ q }).toString())
                .then((response) => response.json())
                .then((items) => {
                    results.innerHTML = '';
                    if (!Array.isArray(items) || items.length === 0) {
                        results.innerHTML = '<div class="list-group-item text-dark bg-white">Ничего не найдено</div>';
                    } else {
                        items.forEach((user) => {
                            const item = document.createElement('a');
                            item.href = '#';
                            item.className = 'list-group-item list-group-item-action bg-white text-dark';
                            item.innerHTML = detailsHtml(user);
                            item.addEventListener('click', (event) => {
                                event.preventDefault();
                                selectUser(user);
                            });
                            results.appendChild(item);
                        });
                    }
                    results.style.display = 'block';
                })
                .catch(() => {
                    results.style.display = 'none';
                });
        };

        searchInput.addEventListener('input', () => {
            clientId.value = '';
            if (editBtn) editBtn.style.display = 'none';
            details.className = 'alert alert-warning py-1 mt-1 mb-0 fin-client-search__details';
            details.textContent = 'Контрагент не выбран';
            clearTimeout(searchTimer);
            searchTimer = setTimeout(performSearch, 300);
        });
        searchInput.addEventListener('keydown', (event) => {
            if (event.key === 'Enter') {
                event.preventDefault();
                performSearch();
            }
        });
        document.addEventListener('click', (event) => {
            if (!searchInput.contains(event.target) && !results.contains(event.target)) {
                results.style.display = 'none';
            }
        });
        fields.groupName?.addEventListener('change', syncGroupId);
        fields.phone?.addEventListener('input', () => {
            const digits = fields.phone.value.replace(/\D/g, '').slice(0, 12);
            fields.phone.value = digits ? `+${digits}` : '';
        });
        newBtn?.addEventListener('click', () => {
            clearModal();
            modal.show();
        });
        editBtn?.addEventListener('click', () => {
            if (!clientId.value) {
                return;
            }
            fillModalFromSelected();
            modal.show();
        });
        saveBtn?.addEventListener('click', () => {
            syncGroupId();
            const payload = {
                id: fields.id.value || '0',
                orgname: fields.orgname.value.trim(),
                secondname: fields.secondname.value.trim(),
                name: fields.name.value.trim(),
                phone: fields.phone.value.trim(),
                city: fields.city.value.trim(),
                region: fields.region.value.trim(),
                poshta: fields.poshta.value.trim(),
                idstatus: fields.status.value,
                usergroup: fields.groupId.value,
                usergroup_name: fields.groupName.value.trim(),
            };

            if (!payload.name && !payload.secondname && !payload.phone) {
                errorBox.textContent = 'Заполните хотя бы одно поле: имя, фамилия или телефон';
                errorBox.style.display = 'block';
                return;
            }
            if (!payload.idstatus) {
                errorBox.textContent = 'Выберите статус контрагента';
                errorBox.style.display = 'block';
                return;
            }
            errorBox.style.display = 'none';
            saveBtn.disabled = true;
            saveBtn.textContent = 'Сохраняем...';

            fetch("{{ route('client.quickStore') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                },
                body: JSON.stringify(payload),
            })
                .then(async (response) => {
                    const data = await response.json().catch(() => ({}));
                    if (!response.ok) {
                        throw new Error(data.message || 'Не удалось сохранить контрагента');
                    }
                    return data;
                })
                .then((user) => {
                    selectUser(user);
                    modal.hide();
                    clearModal();
                })
                .catch((error) => {
                    errorBox.textContent = error.message;
                    errorBox.style.display = 'block';
                })
                .finally(() => {
                    saveBtn.disabled = false;
                    saveBtn.textContent = 'Сохранить';
                });
        });
    });
</script>
@endsection
