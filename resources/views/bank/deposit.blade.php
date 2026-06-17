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
        const settingsSubmit = modal.querySelector('[data-deposit-settings-submit]');
        const operationsTab = modal.querySelector('#depositOperationsTab');
        const settingsTab = modal.querySelector('#depositSettingsTab');

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

                if (settingsForm && settingsMethod && settingsName && settingsType && settingsSubmit) {
                    settingsForm.action = trigger.dataset.storeUrl || '';
                    settingsMethod.value = 'POST';
                    settingsName.value = '';
                    settingsType.value = 'bank';
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
            if (settingsForm && settingsMethod && settingsName && settingsType && settingsSubmit) {
                settingsForm.action = trigger.dataset.updateUrl || '';
                settingsMethod.value = 'PUT';
                settingsName.value = trigger.dataset.depositName || '';
                settingsType.value = trigger.dataset.depositType || 'bank';
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
