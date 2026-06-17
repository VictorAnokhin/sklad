@extends('home')

@section('title')
Депозиты
@endsection

@section('content')
<div class="bank-page">
    @include('bank.partials.nav')

    <section class="bank-hero">
        <div>
            <div class="bank-label">Bank Deposits</div>
            <h1>Депозиты</h1>
            <p>Депозитные счета проектов, текущие остатки, лимиты и история пополнений и выводов.</p>
        </div>
        <div class="bank-hero__metrics">
            <div>
                <span>Активных депозитов</span>
                <strong>{{ number_format((int) $summary['active'], 0, '.', ' ') }}</strong>
            </div>
            <div>
                <span>Ожидают проводку</span>
                <strong>{{ number_format((int) $summary['pending'], 0, '.', ' ') }}</strong>
            </div>
        </div>
    </section>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <section class="bank-grid bank-grid--summary">
        <div class="bank-panel bank-panel--accent">
            <div class="bank-label">Депозитов</div>
            <div class="bank-value">{{ $deposits->count() }}</div>
            <div class="bank-meta">Во всех проектах холдинга</div>
        </div>
        <div class="bank-panel">
            <div class="bank-label">Пополнения</div>
            <div class="bank-value">{{ number_format((float) $summary['topups'], 2, '.', ' ') }}</div>
            <div class="bank-meta">Последние {{ $operations->count() }} операций</div>
        </div>
        <div class="bank-panel">
            <div class="bank-label">Выводы</div>
            <div class="bank-value">{{ number_format((float) $summary['withdrawals'], 2, '.', ' ') }}</div>
            <div class="bank-meta">Последние {{ $operations->count() }} операций</div>
        </div>
        <div class="bank-panel">
            <div class="bank-label">Управление</div>
            <div class="bank-value">PP</div>
            <div class="bank-meta"><a href="{{ route('deposit.index') }}" class="bank-account-link">Открыть операции депозитов</a></div>
        </div>
    </section>

    <section class="bank-panel bank-currency-strip">
        <div class="bank-currency-list">
            @forelse($totalByCurrency as $currency => $total)
                <div class="bank-currency-item">
                    <span>{{ $currency }}</span>
                    <strong>
                        {{ number_format((float) $total, 2, '.', ' ') }}
                        <small> / лимит {{ number_format((float) ($limitByCurrency[$currency] ?? 0), 2, '.', ' ') }}</small>
                    </strong>
                </div>
            @empty
                <div class="bank-empty">Депозитные счета пока не созданы.</div>
            @endforelse
        </div>
    </section>

    <ul class="nav nav-tabs bank-modal-tabs mb-3" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="bankDepositsPortfolioTab" data-bs-toggle="tab" data-bs-target="#bankDepositsPortfolioPane" type="button" role="tab">
                Депозиты
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="bankDepositsTransferTab" data-bs-toggle="tab" data-bs-target="#bankDepositsTransferPane" type="button" role="tab">
                Трансфер
            </button>
        </li>
    </ul>

    <div class="tab-content">
        <div class="tab-pane fade show active" id="bankDepositsPortfolioPane" role="tabpanel" aria-labelledby="bankDepositsPortfolioTab">
    <section class="bank-panel bank-table-panel">
        <div class="bank-table-header">
            <div>
                <div class="bank-label">Портфель депозитов</div>
                <div class="bank-meta">Текущие остатки и установленные лимиты депозитных счетов.</div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <div class="bank-meta">{{ $deposits->count() }} счетов</div>
                <button type="button"
                    class="btn btn-sm btn-primary"
                    data-bs-toggle="modal"
                    data-bs-target="#depositMovementModal"
                    data-deposit-create="1"
                    data-store-url="{{ route('bank.deposit.store') }}">
                    Создать депозит
                </button>
            </div>
        </div>
        <div class="table-responsive bank-table-scroll bank-table-scroll--compact">
            <table class="table table-dark table-hover table-sm align-middle bank-table bank-table--deposits">
                <thead>
                    <tr>
                        <th class="bank-table__num">№</th>
                        <th>Депозит</th>
                        <th>Проект</th>
                        <th>Валюта</th>
                        <th class="text-end">Остаток</th>
                        <th class="text-end">Лимит</th>
                        <th class="text-end">Использовано</th>
                        <th>Статус</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($deposits as $deposit)
                        @php
                            $usage = $deposit->limit > 0 ? min(100, max(0, $deposit->balance / $deposit->limit * 100)) : null;
                        @endphp
                        <tr
                            class="bank-deposit-row"
                            role="button"
                            tabindex="0"
                            data-bs-toggle="modal"
                            data-bs-target="#depositMovementModal"
                            data-deposit-id="{{ $deposit->id }}"
                            data-deposit-name="{{ $deposit->name }}"
                            data-deposit-currency="{{ $deposit->currency }}"
                            data-deposit-balance="{{ number_format((float) $deposit->balance, 2, '.', ' ') }}"
                            data-deposit-type="{{ $deposit->deposit_type }}"
                            data-deposit-type-label="{{ $deposit->deposit_type_label }}"
                            data-update-url="{{ route('bank.deposit.update', ['deposit' => $deposit->id]) }}"
                        >
                            <td class="bank-table__num bank-mono">{{ $loop->iteration }}</td>
                            <td>
                                <strong>{{ $deposit->name }}</strong>
                                <div class="bank-meta">ID {{ $deposit->id }} · {{ $deposit->deposit_type_label }}{{ $deposit->is_visible ? '' : ' · скрыт' }}</div>
                            </td>
                            <td>{{ $deposit->project_name }}</td>
                            <td><span class="bank-pill bank-pill--currency">{{ $deposit->currency }}</span></td>
                            <td class="text-end fw-semibold">{{ number_format((float) $deposit->balance, 2, '.', ' ') }}</td>
                            <td class="text-end">{{ $deposit->limit > 0 ? number_format((float) $deposit->limit, 2, '.', ' ') : '—' }}</td>
                            <td class="text-end">{{ $usage !== null ? number_format($usage, 1, '.', ' ') . '%' : '—' }}</td>
                            <td>
                                <span class="bank-status {{ $deposit->is_active ? '' : 'bank-status--reversed' }}">{{ $deposit->status_label }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">Депозиты пока не созданы.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
        </div>

        <div class="tab-pane fade" id="bankDepositsTransferPane" role="tabpanel" aria-labelledby="bankDepositsTransferTab">
            <section class="bank-panel bank-table-panel">
                <div class="bank-table-header">
                    <div>
                        <div class="bank-label">Трансфер</div>
                        <div class="bank-meta">Перевод с операционного счета банка на депозит. Валюта депозита и счета должна совпадать.</div>
                    </div>
                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#depositTransferModal">
                        Создать
                    </button>
                </div>
                <div class="table-responsive bank-table-scroll bank-table-scroll--compact">
                    <table class="table table-dark table-hover table-sm align-middle bank-table">
                        <thead>
                            <tr>
                                <th>Депозит</th>
                                <th>Валюта</th>
                                <th class="text-end">Остаток депозита</th>
                                <th class="text-end">Операционных счетов в валюте</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($deposits as $deposit)
                                <tr>
                                    <td>{{ $deposit->name }}</td>
                                    <td><span class="bank-pill bank-pill--currency">{{ $deposit->currency }}</span></td>
                                    <td class="text-end fw-semibold">{{ number_format((float) $deposit->balance, 2, '.', ' ') }}</td>
                                    <td class="text-end">{{ $operationalAccounts->where('currency', $deposit->currency)->count() }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">Депозиты пока не созданы.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>

    <div class="modal fade bank-order-modal" id="depositMovementModal" tabindex="-1" aria-labelledby="depositMovementModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <div class="bank-label">Движение средств по депозиту</div>
                        <h5 class="modal-title" id="depositMovementModalLabel">Депозит</h5>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
                </div>
                <div class="modal-body">
                    <ul class="nav nav-tabs bank-modal-tabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="depositOperationsTab" data-bs-toggle="tab" data-bs-target="#depositOperationsPane" type="button" role="tab">
                                Операции
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="depositSettingsTab" data-bs-toggle="tab" data-bs-target="#depositSettingsPane" type="button" role="tab">
                                Настройки
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content pt-3">
                        <div class="tab-pane fade show active" id="depositOperationsPane" role="tabpanel" aria-labelledby="depositOperationsTab">
                            <div class="bank-order-modal__summary">
                                <div>
                                    <span>Текущий остаток</span>
                                    <strong data-deposit-summary="balance"></strong>
                                </div>
                                <div>
                                    <span>Пополнения</span>
                                    <strong class="text-success" data-deposit-summary="topups"></strong>
                                </div>
                                <div>
                                    <span>Выводы</span>
                                    <strong class="text-danger" data-deposit-summary="withdrawals"></strong>
                                </div>
                                <div>
                                    <span>Чистое движение</span>
                                    <strong data-deposit-summary="net"></strong>
                                </div>
                            </div>

                            <div class="table-responsive bank-deposit-movement-table">
                                <table class="table table-dark table-hover table-sm align-middle bank-table">
                                    <thead>
                                        <tr>
                                            <th class="bank-table__num">№</th>
                                            <th>Дата / документ</th>
                                            <th>Операция</th>
                                            <th>Владелец</th>
                                            <th class="text-end">Сумма</th>
                                            <th>Статус</th>
                                        </tr>
                                    </thead>
                                    <tbody data-deposit-movements></tbody>
                                </table>
                            </div>
                            <div class="bank-empty bank-deposit-movement-empty" data-deposit-movements-empty hidden>
                                По этому депозиту движения средств не найдены.
                            </div>
                        </div>

                        <div class="tab-pane fade" id="depositSettingsPane" role="tabpanel" aria-labelledby="depositSettingsTab">
                            <form method="post" data-deposit-settings-form>
                                @csrf
                                <input type="hidden" name="_method" value="POST" data-deposit-settings-method>
                                <div class="row g-3">
                                    <div class="col-md-8">
                                        <label class="form-label">Название</label>
                                        <input type="text" name="name" class="form-control" data-deposit-settings-name required maxlength="200">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Тип депозита</label>
                                        <select name="deposit_type" class="form-select" data-deposit-settings-type required>
                                            <option value="bank">Банковский</option>
                                            <option value="personal">Личный</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Валюта</label>
                                        <select name="currency" class="form-select" data-deposit-settings-currency required>
                                            @foreach(['UAH', 'USD', 'EUR', 'USDC', 'USDT', 'AV8', 'SUI'] as $currency)
                                                <option value="{{ $currency }}">{{ $currency }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-end gap-2 mt-3">
                                    <button type="submit" class="btn btn-primary" data-deposit-settings-submit>Сохранить</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade bank-order-modal" id="depositTransferModal" tabindex="-1" aria-labelledby="depositTransferModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" action="{{ route('bank.deposit.transfer.store') }}" data-deposit-transfer-form>
                    @csrf
                    <input type="hidden" name="direction" value="account_to_deposit" data-deposit-transfer-direction>
                    <div class="modal-header">
                        <div>
                            <div class="bank-label">Трансфер</div>
                            <h5 class="modal-title" id="depositTransferModalLabel">Создать трансфер</h5>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Маршрут</label>
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    <span class="bank-pill bank-pill--currency" data-deposit-transfer-route-label>Счет → депозит</span>
                                    <button type="button" class="btn btn-sm btn-outline-light" data-deposit-transfer-toggle-route>
                                        Изменить маршрут
                                    </button>
                                </div>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Депозит</label>
                                <select name="deposit_id" class="form-select" required data-deposit-transfer-deposit>
                                    <option value="">Выберите депозит</option>
                                    @foreach($deposits as $deposit)
                                        <option value="{{ $deposit->id }}" data-currency="{{ $deposit->currency }}" data-balance="{{ number_format((float) $deposit->balance, 2, '.', '') }}">
                                            {{ $deposit->name }} · {{ $deposit->currency }} · {{ number_format((float) $deposit->balance, 2, '.', ' ') }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Операционный счет</label>
                                <select name="operational_account_id" class="form-select" required data-deposit-transfer-account>
                                    <option value="">Выберите счет</option>
                                    @foreach($operationalAccounts as $account)
                                        <option value="{{ $account->id }}" data-currency="{{ $account->currency }}" data-balance="{{ number_format((float) $account->balance, 2, '.', '') }}">
                                            {{ $account->label }} · {{ $account->currency }} · {{ number_format((float) $account->balance, 2, '.', ' ') }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="bank-meta" data-deposit-transfer-account-meta></div>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Сумма</label>
                                <input type="number" name="amount" class="form-control" min="0.01" step="0.01" required data-deposit-transfer-amount>
                            </div>
                        </div>
                        <div class="alert alert-danger mt-3 mb-0" data-deposit-transfer-error hidden></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                        <button type="submit" class="btn btn-primary" data-deposit-transfer-submit>Выполнить</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>

@include('bank.partials.styles')
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const modal = document.getElementById('depositMovementModal');
        if (!modal) {
            return;
        }

        const operations = @json($operations->values());
        const movementsBody = modal.querySelector('[data-deposit-movements]');
        const emptyState = modal.querySelector('[data-deposit-movements-empty]');
        const settingsForm = modal.querySelector('[data-deposit-settings-form]');
        const settingsMethod = modal.querySelector('[data-deposit-settings-method]');
        const settingsName = modal.querySelector('[data-deposit-settings-name]');
        const settingsType = modal.querySelector('[data-deposit-settings-type]');
        const settingsCurrency = modal.querySelector('[data-deposit-settings-currency]');
        const settingsSubmit = modal.querySelector('[data-deposit-settings-submit]');
        const operationsTab = modal.querySelector('#depositOperationsTab');
        const settingsTab = modal.querySelector('#depositSettingsTab');
        const transferForm = document.querySelector('[data-deposit-transfer-form]');
        const transferDirection = document.querySelector('[data-deposit-transfer-direction]');
        const transferRouteLabel = document.querySelector('[data-deposit-transfer-route-label]');
        const transferToggleRoute = document.querySelector('[data-deposit-transfer-toggle-route]');
        const transferDeposit = document.querySelector('[data-deposit-transfer-deposit]');
        const transferAccount = document.querySelector('[data-deposit-transfer-account]');
        const transferAmount = document.querySelector('[data-deposit-transfer-amount]');
        const transferError = document.querySelector('[data-deposit-transfer-error]');
        const transferSubmit = document.querySelector('[data-deposit-transfer-submit]');
        const transferAccountMeta = document.querySelector('[data-deposit-transfer-account-meta]');

        function formatAmount(value) {
            return Number(value || 0).toLocaleString('ru-RU', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            });
        }

        function escapeHtml(value) {
            const element = document.createElement('div');
            element.textContent = String(value ?? '');
            return element.innerHTML;
        }

        function setSummary(field, value) {
            const element = modal.querySelector(`[data-deposit-summary="${field}"]`);
            if (element) {
                element.textContent = value;
            }
        }

        function selectedOption(select) {
            return select?.selectedOptions?.[0] || null;
        }

        function validateTransferForm() {
            if (!transferForm || !transferDeposit || !transferAccount || !transferAmount || !transferError || !transferSubmit) {
                return true;
            }

            const depositOption = selectedOption(transferDeposit);
            const accountOption = selectedOption(transferAccount);
            const depositCurrency = depositOption?.dataset.currency || '';
            const direction = transferDirection?.value || 'account_to_deposit';
            const amount = Number(transferAmount.value || 0);
            let message = '';

            Array.from(transferAccount.options).forEach((option) => {
                if (!option.value) {
                    option.hidden = false;
                    return;
                }
                option.hidden = depositCurrency !== '' && option.dataset.currency !== depositCurrency;
            });

            if (accountOption && accountOption.value && depositCurrency !== '' && accountOption.dataset.currency !== depositCurrency) {
                transferAccount.value = '';
            }

            const refreshedAccountOption = selectedOption(transferAccount);
            if (depositCurrency === '') {
                message = 'Выберите депозит.';
            } else if (!refreshedAccountOption?.value) {
                message = 'Выберите операционный счет в валюте ' + depositCurrency + '.';
            } else if (amount <= 0) {
                message = 'Введите сумму трансфера.';
            } else {
                const sourceBalance = direction === 'deposit_to_account'
                    ? Number(depositOption?.dataset.balance || 0)
                    : Number(refreshedAccountOption.dataset.balance || 0);
                if (sourceBalance + 0.000001 < amount) {
                    message = direction === 'deposit_to_account'
                        ? 'Недостаточно средств на депозите.'
                        : 'Недостаточно средств на операционном счете.';
                }
            }

            if (transferAccountMeta) {
                const currentAccount = selectedOption(transferAccount);
                const accountBalance = Number(currentAccount?.dataset.balance || 0);
                const depositBalance = Number(depositOption?.dataset.balance || 0);
                transferAccountMeta.textContent = currentAccount?.value
                    ? (
                        direction === 'deposit_to_account'
                            ? `Источник: депозит, доступно ${formatAmount(depositBalance)} ${depositCurrency}`
                            : `Источник: счет, доступно ${formatAmount(accountBalance)} ${currentAccount.dataset.currency || ''}`
                    )
                    : '';
            }

            transferError.textContent = message;
            transferError.hidden = message === '';
            transferSubmit.disabled = message !== '';

            return message === '';
        }

        function syncTransferRouteLabel() {
            if (!transferDirection || !transferRouteLabel) {
                return;
            }
            transferRouteLabel.textContent = transferDirection.value === 'deposit_to_account'
                ? 'Депозит → счет'
                : 'Счет → депозит';
            validateTransferForm();
        }

        transferToggleRoute?.addEventListener('click', () => {
            if (!transferDirection) {
                return;
            }
            transferDirection.value = transferDirection.value === 'deposit_to_account'
                ? 'account_to_deposit'
                : 'deposit_to_account';
            syncTransferRouteLabel();
        });

        [transferDeposit, transferAccount, transferAmount].forEach((element) => {
            element?.addEventListener('input', validateTransferForm);
            element?.addEventListener('change', validateTransferForm);
        });

        transferForm?.addEventListener('submit', (event) => {
            if (!validateTransferForm()) {
                event.preventDefault();
            }
        });

        document.getElementById('depositTransferModal')?.addEventListener('shown.bs.modal', () => {
            syncTransferRouteLabel();
        });

        modal.addEventListener('show.bs.modal', (event) => {
            const trigger = event.relatedTarget;
            if (!(trigger instanceof HTMLElement) || !movementsBody || !emptyState) {
                return;
            }

            const isCreate = trigger.dataset.depositCreate === '1';
            if (isCreate) {
                modal.querySelector('#depositMovementModalLabel').textContent = 'Новый депозит';
                setSummary('balance', '0.00 UAH');
                setSummary('topups', '+0.00 UAH');
                setSummary('withdrawals', '−0.00 UAH');
                setSummary('net', '+0.00 UAH');
                movementsBody.replaceChildren();
                emptyState.hidden = false;

                if (settingsForm && settingsMethod && settingsName && settingsType && settingsCurrency && settingsSubmit) {
                    settingsForm.action = trigger.dataset.storeUrl || '';
                    settingsMethod.value = 'POST';
                    settingsName.value = '';
                    settingsType.value = 'bank';
                    settingsCurrency.value = 'UAH';
                    settingsSubmit.textContent = 'Создать депозит';
                }
                bootstrap.Tab.getOrCreateInstance(settingsTab).show();
                return;
            }

            const depositId = String(trigger.dataset.depositId || '');
            const currency = String(trigger.dataset.depositCurrency || 'UAH');
            const movements = operations.filter((operation) => String(operation.deposit_id) === depositId);
            const topups = movements
                .filter((operation) => operation.mode === 'topup')
                .reduce((total, operation) => total + Number(operation.amount || 0), 0);
            const withdrawals = movements
                .filter((operation) => operation.mode === 'withdraw')
                .reduce((total, operation) => total + Number(operation.amount || 0), 0);

            modal.querySelector('#depositMovementModalLabel').textContent = trigger.dataset.depositName || 'Депозит';
            setSummary('balance', `${trigger.dataset.depositBalance || '0.00'} ${currency}`);
            setSummary('topups', `+${formatAmount(topups)} ${currency}`);
            setSummary('withdrawals', `−${formatAmount(withdrawals)} ${currency}`);
            setSummary('net', `${topups - withdrawals >= 0 ? '+' : '−'}${formatAmount(Math.abs(topups - withdrawals))} ${currency}`);
            if (settingsForm && settingsMethod && settingsName && settingsType && settingsCurrency && settingsSubmit) {
                settingsForm.action = trigger.dataset.updateUrl || '';
                settingsMethod.value = 'PUT';
                settingsName.value = trigger.dataset.depositName || '';
                settingsType.value = trigger.dataset.depositType || 'bank';
                settingsCurrency.value = currency;
                settingsSubmit.textContent = 'Сохранить';
            }
            bootstrap.Tab.getOrCreateInstance(operationsTab).show();

            movementsBody.replaceChildren();
            emptyState.hidden = movements.length > 0;

            movements.forEach((movement, index) => {
                const row = document.createElement('tr');
                const isWithdraw = movement.mode === 'withdraw';
                const statusClass = `bank-status--${movement.status}`;
                row.innerHTML = `
                    <td class="bank-table__num bank-mono">${index + 1}</td>
                    <td>
                        <strong>${escapeHtml(movement.date || '—')}</strong>
                        <div class="bank-meta">PP №${escapeHtml(movement.number)} · ID ${escapeHtml(movement.id)}</div>
                    </td>
                    <td><span class="bank-pill ${isWithdraw ? 'bank-pill--outgoing' : ''}">${escapeHtml(movement.mode_label)}</span></td>
                    <td>${escapeHtml(movement.owner_name || 'Не указан')}</td>
                    <td class="text-end fw-semibold ${isWithdraw ? 'text-danger' : 'text-success'}">
                        ${isWithdraw ? '−' : '+'}${formatAmount(movement.amount)} ${escapeHtml(movement.currency)}
                    </td>
                    <td>
                        <span class="bank-status ${statusClass}">${escapeHtml(movement.status_label)}</span>
                        <div class="bank-meta">${movement.ledger_id > 0 ? `TX #${escapeHtml(movement.ledger_id)}` : 'Ledger TX отсутствует'}</div>
                    </td>
                `;
                movementsBody.appendChild(row);
            });
        });

        document.querySelectorAll('.bank-deposit-row').forEach((row) => {
            row.addEventListener('keydown', (event) => {
                if (event.key !== 'Enter' && event.key !== ' ') {
                    return;
                }

                event.preventDefault();
                row.click();
            });
        });
    });
</script>
@endpush
