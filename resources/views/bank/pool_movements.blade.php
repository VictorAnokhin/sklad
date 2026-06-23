@extends('home')

@section('title')
Движение средств
@endsection

@section('content')
@php
    $formatMoney = static fn ($value): string => number_format((float) $value, 2, '.', ' ');
@endphp

<div class="bank-page bank-invest-page" data-bank-invest-page>
    @include('bank.partials.nav')

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="bank-tabs" role="tablist" aria-label="Движение средств">
        <button type="button" class="bank-tab is-active" data-bank-movement-tab="pools" role="tab" aria-selected="true">Пулы</button>
        <button type="button" class="bank-tab" data-bank-movement-tab="deposits" role="tab" aria-selected="false">Депозиты</button>
    </div>

    <section class="bank-panel bank-table-panel" data-bank-movement-pane="pools">
        <div class="bank-table-header">
            <div>
                <div class="bank-label">Операции Счет - Пул</div>
                <div class="bank-meta">Движения между пулами из bank/pools и операционными счетами проекта f={{ $accountProjectId ?? 12 }}.</div>
            </div>
            <div class="bank-table-header__actions">
                <div class="bank-meta">{{ $poolOperationRows->count() }} операций</div>
                <button type="button" class="btn btn-sm btn-primary" data-invest-operation-open>Создать</button>
            </div>
        </div>
        <div class="table-responsive bank-table-scroll">
            <table class="table table-dark table-hover table-sm align-middle bank-table bank-operation-table">
                <thead>
                    <tr>
                        <th class="bank-table__num">№</th>
                        <th>Дата</th>
                        <th>Операция</th>
                        <th>Пул</th>
                        <th>Счет</th>
                        <th class="text-end">Сумма</th>
                        <th>Проводка</th>
                        <th>Комментарий</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($poolOperationRows as $movement)
                        @php
                            $directionClass = $movement['direction'] === 'revaluation'
                                ? 'bank-pill--warning'
                                : ($movement['direction'] === 'asset_to_account' ? 'bank-pill--currency' : 'bank-pill--company');
                            $movementJson = json_encode($movement, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
                        @endphp
                        <tr class="bank-table-row--clickable" data-invest-operation-edit data-operation-movement="{{ $movementJson }}">
                            <td class="bank-table__num bank-mono">{{ $movement['id'] }}</td>
                            <td>{{ $movement['date'] !== '' ? $movement['date'] : '—' }}</td>
                            <td>
                                <span class="bank-pill {{ $directionClass }}">{{ $movement['direction_label'] }}</span>
                                @unless($movement['can_edit'])
                                    <div class="bank-meta">{{ $movement['edit_hint'] }}</div>
                                @endunless
                            </td>
                            <td>
                                <strong>{{ $movement['asset_label'] }}</strong>
                                <div class="bank-meta">{{ $movement['asset_key'] }}</div>
                            </td>
                            <td>{{ $movement['account_label'] }}</td>
                            <td class="text-end fw-semibold">{{ $formatMoney($movement['amount']) }} {{ $movement['currency'] }}</td>
                            <td>
                                <span class="bank-status {{ $movement['status'] === 'posted' ? '' : 'bank-status--pending' }}">{{ $movement['status'] }}</span>
                                <div class="bank-meta">{{ $movement['ledger_note'] ?? ($movement['ledger_transaction_id'] > 0 ? 'TX #' . $movement['ledger_transaction_id'] : 'проводки нет') }}</div>
                            </td>
                            <td class="bank-meta">{{ $movement['note'] !== '' ? $movement['note'] : '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">Операции Счет - Пул пока не созданы.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="bank-panel bank-table-panel" data-bank-movement-pane="deposits" hidden>
        <div class="bank-table-header">
            <div>
                <div class="bank-label">Операции по депозитам</div>
                <div class="bank-meta">PP-операции пополнения и вывода по депозитам холдинга.</div>
            </div>
            <div class="bank-table-header__actions">
                <div class="bank-meta" data-deposit-operations-counter>{{ $depositOperations->count() }} операций</div>
                <button type="button"
                    class="btn btn-sm btn-outline-light"
                    data-bs-toggle="modal"
                    data-bs-target="#depositFilterModal">
                    Фильтр
                </button>
                <button type="button"
                    class="btn btn-sm btn-primary"
                    data-bs-toggle="modal"
                    data-bs-target="#depositTransferModal"
                    data-deposit-transfer-create="1"
                    data-store-url="{{ route('bank.deposit.transfer.store') }}">
                    Создать
                </button>
            </div>
        </div>
        <div class="table-responsive bank-table-scroll">
            <table class="table table-dark table-hover table-sm align-middle bank-table bank-operation-table">
                <thead>
                    <tr>
                        <th class="bank-table__num">№</th>
                        <th>Дата / документ</th>
                        <th>Операция</th>
                        <th>Депозит</th>
                        <th>Счет</th>
                        <th class="text-end">Сумма</th>
                        <th>Статус</th>
                        <th>Комментарий</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($depositOperations as $operation)
                        @php
                            $isWithdraw = $operation->mode === 'withdraw';
                        @endphp
                        <tr class="bank-table-row--clickable bank-deposit-transfer-row"
                            role="button"
                            tabindex="0"
                            data-bs-toggle="modal"
                            data-bs-target="#depositTransferModal"
                            data-deposit-operation-row
                            data-deposit-id="{{ $operation->deposit_id }}"
                            data-operation-mode="{{ $operation->mode }}"
                            data-transfer-direction="{{ $operation->transfer_direction }}"
                            data-transfer-deposit="{{ $operation->transfer_deposit_id }}"
                            data-transfer-account="{{ $operation->transfer_account_id }}"
                            data-transfer-amount="{{ number_format((float) $operation->amount, 2, '.', '') }}"
                            data-transfer-note="{{ $operation->description }}"
                            data-transfer-posted="{{ $operation->transfer_posted ? '1' : '0' }}"
                            data-transfer-update-url="{{ $operation->transfer_update_url }}"
                            data-transfer-reverse-url="{{ $operation->transfer_reverse_url }}"
                            data-transfer-delete-url="{{ $operation->transfer_delete_url }}">
                            <td class="bank-table__num bank-mono">{{ $loop->iteration }}</td>
                            <td>
                                <strong>#{{ $operation->number }}</strong>
                                <div class="bank-meta">{{ $operation->date !== '' ? $operation->date : '—' }} · ID {{ $operation->id }}</div>
                            </td>
                            <td>
                                <span class="bank-pill {{ $isWithdraw ? 'bank-pill--currency' : 'bank-pill--company' }}">{{ $operation->mode_label }}</span>
                            </td>
                            <td>
                                <strong>{{ $operation->deposit_name }}</strong>
                                <div class="bank-meta">ID {{ $operation->deposit_id }}</div>
                            </td>
                            <td>{{ $operation->transfer_account_name }}</td>
                            <td class="text-end fw-semibold {{ $isWithdraw ? 'text-danger' : 'text-success' }}">
                                {{ $isWithdraw ? '−' : '+' }}{{ $formatMoney($operation->amount) }} {{ $operation->currency }}
                            </td>
                            <td>
                                <span class="bank-status {{ $operation->status === 'posted' ? '' : 'bank-status--pending' }}">{{ $operation->status_label }}</span>
                                <div class="bank-meta">{{ $operation->ledger_id > 0 ? 'TX #' . $operation->ledger_id : 'Ledger TX отсутствует' }}</div>
                            </td>
                            <td class="bank-meta">{{ $operation->description !== '' ? $operation->description : '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">Операции по депозитам пока не созданы.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="bank-empty mt-3" data-deposit-operations-empty hidden>Операции по выбранному депозиту не найдены.</div>
    </section>

    <div class="bank-modal" data-invest-operation-modal hidden>
        <div class="bank-modal__backdrop" data-invest-operation-close></div>
        <div class="bank-modal__dialog bank-modal__dialog--xl" role="dialog" aria-modal="true" aria-label="Операция пула">
            <div class="bank-modal__header">
                <button type="button" class="bank-modal__close" data-invest-operation-close aria-label="Закрыть">×</button>
            </div>
            <form method="POST" action="{{ route('bank.invest-operations.store') }}" class="bank-requisites-form" data-invest-operation-form>
                @csrf
                <input type="hidden" name="_method" value="PUT" data-invest-operation-method disabled>
                <input type="hidden" name="update_account_balance" value="1" data-invest-operation-update-balance>
                <input type="hidden" name="redirect_to" value="bank.pool-movements">
                <div class="bank-form-grid bank-pool-movement-form">
                    <div class="bank-form-full bank-operation-mode" role="tablist" aria-label="Тип операции">
                        <button type="button" class="bank-operation-mode__button is-active" data-invest-operation-direction-tab="account_to_asset">Счет -> Пул</button>
                        <button type="button" class="bank-operation-mode__button" data-invest-operation-direction-tab="asset_to_account">Пул -> Счет</button>
                        <button type="button" class="bank-operation-mode__button" data-invest-operation-direction-tab="revaluation">Переоценка</button>
                        <input type="hidden" name="direction" value="account_to_asset" data-invest-operation-direction>
                    </div>

                    <label class="bank-form-field bank-pool-movement-field" data-pool-movement-field="date">
                        <span>Дата</span>
                        <input type="datetime-local" name="operated_at" data-invest-operation-date>
                    </label>

                    <label class="bank-form-field bank-pool-movement-field" data-pool-movement-field="account" data-invest-operation-account-section>
                        <span>Счет</span>
                        <select name="account_id" required data-invest-operation-account>
                            @forelse($operationalAccounts as $account)
                                <option value="{{ $account->id }}" data-currency="{{ $account->currency }}">{{ $account->label }} · {{ $account->currency }} · {{ $formatMoney($account->balance) }}</option>
                            @empty
                                <option value="">Операционные счета не найдены</option>
                            @endforelse
                        </select>
                    </label>

                    <label class="bank-form-field bank-pool-movement-field" data-pool-movement-field="pool">
                        <span>Пул</span>
                        <select name="asset_key" required data-invest-operation-asset>
                            @forelse($fixedAssetRows as $asset)
                                <option value="{{ $asset->asset_key }}">{{ $asset->name }} · {{ $formatMoney($asset->value_usd) }} USD</option>
                            @empty
                                <option value="">Пулы не найдены</option>
                            @endforelse
                        </select>
                    </label>

                    <label class="bank-form-field bank-pool-movement-field" data-pool-movement-field="amount">
                        <span data-invest-operation-amount-label>Сумма</span>
                        <span class="bank-amount-currency-row">
                            <input type="text" name="amount" inputmode="numeric" required data-terminal-amount data-terminal-negative="1" data-invest-operation-amount>
                            <input type="text" name="currency" value="USD" maxlength="20" required data-invest-operation-currency aria-label="Валюта">
                        </span>
                        <small class="bank-field-hint" data-invest-operation-amount-hint>Сумма будет списана со счета и отражена на пуле.</small>
                    </label>

                    <label class="bank-form-field bank-pool-movement-field" data-pool-movement-field="comment">
                        <span>Комментарий</span>
                        <textarea name="note" rows="3" data-invest-operation-note></textarea>
                    </label>
                </div>
                <div class="bank-modal__actions">
                    <label class="bank-operation-post-ledger">
                        <input type="checkbox" name="post_ledger" value="1" checked data-invest-operation-post-ledger>
                        <span>
                            <strong>Проводка</strong>
                        </span>
                    </label>
                    <button type="button" class="btn btn-secondary" data-invest-operation-close>Отмена</button>
                    <button type="submit" class="btn btn-warning" formnovalidate data-invest-operation-reverse hidden>Отменить проводку</button>
                    <button type="submit" class="btn btn-primary" data-invest-operation-submit>Сохранить</button>
                    <button type="submit" class="btn btn-outline-danger" formnovalidate data-invest-operation-delete hidden>Удалить</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade bank-order-modal" id="depositFilterModal" tabindex="-1" aria-labelledby="depositFilterModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <div class="bank-label">Фильтр</div>
                        <h5 class="modal-title" id="depositFilterModalLabel">Операции по депозиту</h5>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Депозит</label>
                            <select class="form-select" data-deposit-filter-select>
                                <option value="">Все депозиты</option>
                                @foreach($deposits as $deposit)
                                    <option value="{{ $deposit->id }}">{{ $deposit->name }} · {{ $deposit->currency }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Операция</label>
                            <select class="form-select" data-operation-filter-select>
                                <option value="">Все операции</option>
                                <option value="topup">Сч -> Д</option>
                                <option value="withdraw">Д -> Сч</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-light" data-deposit-filter-reset>Сбросить</button>
                    <button type="button" class="btn btn-primary" data-deposit-filter-apply>Применить</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade bank-order-modal" id="depositTransferModal" tabindex="-1" aria-labelledby="depositTransferModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" action="{{ route('bank.deposit.transfer.store') }}" data-deposit-transfer-form>
                    @csrf
                    <input type="hidden" name="_method" value="PUT" data-deposit-transfer-method disabled>
                    <input type="hidden" name="direction" value="account_to_deposit" data-deposit-transfer-direction>
                    <div class="modal-header">
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Операция</label>
                                <div class="bank-operation-mode" role="tablist" aria-label="Операция депозитного трансфера">
                                    <button type="button" class="bank-operation-mode__button is-active" data-deposit-transfer-direction-tab="account_to_deposit">Сч -> Д</button>
                                    <button type="button" class="bank-operation-mode__button" data-deposit-transfer-direction-tab="deposit_to_account">Д -> Сч</button>
                                </div>
                            </div>
                            <div class="col-12" data-deposit-transfer-field="deposit">
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
                            <div class="col-12" data-deposit-transfer-field="account">
                                <label class="form-label">Счет</label>
                                <select name="operational_account_id" class="form-select" required data-deposit-transfer-account>
                                    <option value="">Выберите счет</option>
                                    @foreach($operationalAccounts as $account)
                                        <option value="{{ $account->id }}" data-currency="{{ $account->currency }}" data-balance="{{ number_format((float) $account->balance, 2, '.', '') }}">
                                            {{ $account->label }} · {{ $account->currency }} · {{ number_format((float) $account->balance, 2, '.', ' ') }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12" data-deposit-transfer-field="amount">
                                <label class="form-label">Сумма</label>
                                <input type="text" name="amount" class="form-control" required inputmode="numeric" data-terminal-amount data-deposit-transfer-amount>
                            </div>
                            <div class="col-12" data-deposit-transfer-field="note">
                                <label class="form-label">Комментарий</label>
                                <textarea name="note" class="form-control" rows="3" data-deposit-transfer-note></textarea>
                            </div>
                        </div>
                        <div class="alert alert-danger mt-3 mb-0" data-deposit-transfer-error hidden></div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-outline-danger me-auto" form="depositTransferDeleteForm" data-deposit-transfer-delete hidden>Удалить</button>
                        <label class="bank-transfer-post-ledger" data-deposit-transfer-post-ledger-field>
                            <input type="checkbox" name="post_ledger" value="1" checked data-deposit-transfer-post-ledger>
                            <span>Проводка</span>
                        </label>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                        <button type="submit" class="btn btn-warning" formnovalidate data-deposit-transfer-reverse hidden>Отменить проводку</button>
                        <button type="submit" class="btn btn-primary" data-deposit-transfer-submit>Выполнить</button>
                    </div>
                </form>
                <form method="POST" id="depositTransferDeleteForm" data-deposit-transfer-delete-form>
                    @csrf
                    <input type="hidden" name="_method" value="DELETE">
                </form>
            </div>
        </div>
    </div>
</div>

@include('bank.partials.styles')
@include('bank.partials.terminal_amount_inputs')

<style>
    .bank-operation-mode {
        display: grid !important;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 6px;
        padding: 4px;
        border: 1px solid rgba(148, 163, 184, 0.22);
        border-radius: 8px;
        background: rgba(15, 23, 42, 0.72);
    }

    .bank-operation-mode__button {
        min-height: 38px;
        border: 0;
        border-radius: 6px;
        background: transparent;
        color: rgba(226, 232, 240, 0.82);
        font-weight: 800;
    }

    .bank-operation-mode__button.is-active {
        background: rgba(56, 189, 248, 0.18);
        color: #fff;
        box-shadow: inset 0 0 0 1px rgba(56, 189, 248, 0.36);
    }

    .bank-form-section,
    .bank-operation-ledger-note,
    .bank-operation-revaluation-note,
    .bank-operation-post-ledger {
        padding: 12px 14px;
        border: 1px solid rgba(148, 163, 184, 0.16);
        border-radius: 10px;
        background: rgba(2, 6, 23, 0.22);
    }

    .bank-form-section {
        display: grid;
        gap: 10px;
    }

    .bank-form-section__title,
    .bank-operation-ledger-note__title,
    .bank-operation-revaluation-note__title {
        color: #fff;
        font-weight: 900;
    }

    .bank-field-hint,
    .bank-operation-ledger-note__body,
    .bank-operation-revaluation-note__body,
    .bank-operation-post-ledger small {
        display: block;
        margin-top: 4px;
        color: rgba(226, 232, 240, 0.82);
        font-size: 13px;
        line-height: 1.45;
    }

    .bank-operation-post-ledger {
        display: flex;
        gap: 10px;
        align-items: center;
        margin: 0 auto 0 0;
        min-height: 38px;
        min-width: 150px;
        padding: 0 14px 0 0;
        border: 0;
        background: transparent;
    }

    .bank-modal__dialog--xl {
        width: min(600px, 100%);
    }

    .bank-transfer-post-ledger {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        margin: 0 8px 0 auto;
        color: rgba(226, 232, 240, 0.9);
        font-size: 0.86rem;
        font-weight: 700;
    }

    .bank-transfer-post-ledger input {
        width: 16px;
        height: 16px;
    }

    .bank-pool-movement-form {
        grid-template-columns: 1fr;
    }

    .bank-pool-movement-field {
        grid-column: 1 / -1;
    }

    .bank-amount-currency-row {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 92px;
        gap: 8px;
        align-items: center;
    }

    .bank-tabs {
        display: inline-flex;
        gap: 4px;
        margin: 0 0 14px;
        padding: 4px;
        border: 1px solid rgba(148, 163, 184, 0.22);
        border-radius: 8px;
        background: rgba(15, 23, 42, 0.72);
    }

    .bank-tab {
        min-width: 112px;
        min-height: 36px;
        border: 0;
        border-radius: 6px;
        background: transparent;
        color: rgba(226, 232, 240, 0.82);
        font-weight: 800;
    }

    .bank-tab.is-active {
        background: rgba(56, 189, 248, 0.18);
        color: #fff;
        box-shadow: inset 0 0 0 1px rgba(56, 189, 248, 0.36);
    }

    .bank-invest-page .bank-table > :not(caption) > * > * {
        height: auto !important;
        min-height: 0 !important;
        padding-top: 0.22rem !important;
        padding-bottom: 0.22rem !important;
        line-height: 1.1 !important;
        vertical-align: middle !important;
    }

    .bank-invest-page .bank-table .bank-meta {
        margin-top: 0 !important;
        line-height: 1.08 !important;
    }

    .bank-invest-page .bank-table .bank-pill,
    .bank-invest-page .bank-table .bank-status {
        min-height: 0 !important;
        padding-top: 0.12rem !important;
        padding-bottom: 0.12rem !important;
        line-height: 1 !important;
    }

    .bank-invest-page .bank-operation-table {
        table-layout: fixed;
        min-width: 900px;
    }

    .bank-invest-page .bank-operation-table th,
    .bank-invest-page .bank-operation-table td {
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .bank-invest-page .bank-operation-table th:nth-child(1),
    .bank-invest-page .bank-operation-table td:nth-child(1) { width: 46px; }
    .bank-invest-page .bank-operation-table th:nth-child(2),
    .bank-invest-page .bank-operation-table td:nth-child(2) { width: 92px; }
    .bank-invest-page .bank-operation-table th:nth-child(3),
    .bank-invest-page .bank-operation-table td:nth-child(3) { width: 118px; }
    .bank-invest-page .bank-operation-table th:nth-child(4),
    .bank-invest-page .bank-operation-table td:nth-child(4) { width: 210px; }
    .bank-invest-page .bank-operation-table th:nth-child(5),
    .bank-invest-page .bank-operation-table td:nth-child(5) { width: 165px; }
    .bank-invest-page .bank-operation-table th:nth-child(6),
    .bank-invest-page .bank-operation-table td:nth-child(6) { width: 118px; }
    .bank-invest-page .bank-operation-table th:nth-child(7),
    .bank-invest-page .bank-operation-table td:nth-child(7) { width: 110px; }
    .bank-invest-page .bank-operation-table th:nth-child(8),
    .bank-invest-page .bank-operation-table td:nth-child(8) { width: 140px; }
</style>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const root = document.querySelector('[data-bank-invest-page]');
        if (!root) return;

        const movementTabs = root.querySelectorAll('[data-bank-movement-tab]');
        const movementPanes = root.querySelectorAll('[data-bank-movement-pane]');

        function setMovementTab(tabName) {
            movementTabs.forEach((tab) => {
                const active = tab.dataset.bankMovementTab === tabName;
                tab.classList.toggle('is-active', active);
                tab.setAttribute('aria-selected', active ? 'true' : 'false');
            });
            movementPanes.forEach((pane) => {
                pane.hidden = pane.dataset.bankMovementPane !== tabName;
            });
        }

        movementTabs.forEach((tab) => {
            tab.addEventListener('click', () => setMovementTab(tab.dataset.bankMovementTab || 'pools'));
        });

        const initialMovementTab = new URLSearchParams(window.location.search).get('tab');
        if (initialMovementTab === 'deposits') {
            setMovementTab('deposits');
        }

        const depositTransferForm = root.querySelector('[data-deposit-transfer-form]');
        const depositTransferMethod = root.querySelector('[data-deposit-transfer-method]');
        const depositTransferDirection = root.querySelector('[data-deposit-transfer-direction]');
        const depositTransferDirectionTabs = root.querySelectorAll('[data-deposit-transfer-direction-tab]');
        const depositTransferDeposit = root.querySelector('[data-deposit-transfer-deposit]');
        const depositTransferAccount = root.querySelector('[data-deposit-transfer-account]');
        const depositTransferAmount = root.querySelector('[data-deposit-transfer-amount]');
        const depositTransferNote = root.querySelector('[data-deposit-transfer-note]');
        const depositTransferError = root.querySelector('[data-deposit-transfer-error]');
        const depositTransferSubmit = root.querySelector('[data-deposit-transfer-submit]');
        const depositTransferDelete = root.querySelector('[data-deposit-transfer-delete]');
        const depositTransferReverse = root.querySelector('[data-deposit-transfer-reverse]');
        const depositTransferPostLedger = root.querySelector('[data-deposit-transfer-post-ledger]');
        const depositTransferPostLedgerField = root.querySelector('[data-deposit-transfer-post-ledger-field]');
        const depositTransferDeleteForm = root.querySelector('[data-deposit-transfer-delete-form]');
        const depositTransferStoreAction = depositTransferForm ? depositTransferForm.action : '';
        const depositFilterSelect = root.querySelector('[data-deposit-filter-select]');
        const operationFilterSelect = root.querySelector('[data-operation-filter-select]');
        const depositFilterApply = root.querySelector('[data-deposit-filter-apply]');
        const depositFilterReset = root.querySelector('[data-deposit-filter-reset]');
        const depositOperationRows = root.querySelectorAll('[data-deposit-operation-row]');
        const depositOperationsCounter = root.querySelector('[data-deposit-operations-counter]');
        const depositOperationsEmpty = root.querySelector('[data-deposit-operations-empty]');

        function selectedOption(select) {
            return select?.selectedOptions?.[0] || null;
        }

        function formatAmount(value) {
            return Number(value || 0).toLocaleString('ru-RU', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            });
        }

        function applyDepositFilter(depositId = '', operationMode = '') {
            let visibleCount = 0;
            depositOperationRows.forEach((row) => {
                const depositMatches = depositId === '' || row.dataset.depositId === depositId;
                const operationMatches = operationMode === '' || row.dataset.operationMode === operationMode;
                const visible = depositMatches && operationMatches;
                row.hidden = !visible;
                if (visible) {
                    visibleCount += 1;
                }
            });

            if (depositOperationsCounter) {
                depositOperationsCounter.textContent = visibleCount + ' операций';
            }
            if (depositOperationsEmpty) {
                depositOperationsEmpty.hidden = visibleCount > 0;
            }
        }

        function validateDepositTransferForm() {
            if (!depositTransferForm || !depositTransferDeposit || !depositTransferAccount || !depositTransferAmount || !depositTransferError || !depositTransferSubmit) {
                return true;
            }

            const depositOption = selectedOption(depositTransferDeposit);
            const accountOption = selectedOption(depositTransferAccount);
            const depositCurrency = depositOption?.dataset.currency || '';
            const directionValue = depositTransferDirection?.value || 'account_to_deposit';
            const amountValue = Number(depositTransferAmount.value || 0);
            const isEdit = Boolean(depositTransferMethod && !depositTransferMethod.disabled);
            let message = '';

            Array.from(depositTransferAccount.options).forEach((option) => {
                if (!option.value) {
                    option.hidden = false;
                    return;
                }
                option.hidden = depositCurrency !== '' && option.dataset.currency !== depositCurrency;
            });

            if (accountOption && accountOption.value && depositCurrency !== '' && accountOption.dataset.currency !== depositCurrency) {
                depositTransferAccount.value = '';
            }

            const refreshedAccountOption = selectedOption(depositTransferAccount);
            if (depositCurrency === '') {
                message = 'Выберите депозит.';
            } else if (!refreshedAccountOption?.value) {
                message = 'Выберите операционный счет в валюте ' + depositCurrency + '.';
            } else if (amountValue <= 0) {
                message = 'Введите сумму трансфера.';
            } else if (!isEdit) {
                const sourceBalance = directionValue === 'deposit_to_account'
                    ? Number(depositOption?.dataset.balance || 0)
                    : Number(refreshedAccountOption.dataset.balance || 0);
                if (sourceBalance + 0.000001 < amountValue) {
                    message = directionValue === 'deposit_to_account'
                        ? 'Недостаточно средств на депозите.'
                        : 'Недостаточно средств на операционном счете.';
                }
            }

            depositTransferError.textContent = message;
            depositTransferError.hidden = message === '';
            depositTransferSubmit.disabled = message !== '';

            return message === '';
        }

        const depositTransferFields = {
            account: root.querySelector('[data-deposit-transfer-field="account"]'),
            deposit: root.querySelector('[data-deposit-transfer-field="deposit"]'),
            amount: root.querySelector('[data-deposit-transfer-field="amount"]'),
            note: root.querySelector('[data-deposit-transfer-field="note"]'),
        };

        function setDepositTransferDirection(nextDirection) {
            if (!depositTransferDirection) {
                return;
            }
            depositTransferDirection.value = nextDirection;
            depositTransferDirectionTabs.forEach((tab) => {
                tab.classList.toggle('is-active', tab.dataset.depositTransferDirectionTab === nextDirection);
            });

            const order = nextDirection === 'deposit_to_account'
                ? ['deposit', 'account', 'amount', 'note']
                : ['account', 'deposit', 'amount', 'note'];
            order.forEach((field, index) => {
                if (depositTransferFields[field]) {
                    depositTransferFields[field].style.order = String(index + 1);
                }
            });
            validateDepositTransferForm();
        }

        function setDepositTransferReadOnly(readOnly) {
            [depositTransferDeposit, depositTransferAccount, depositTransferAmount, depositTransferNote].forEach((field) => {
                if (field) {
                    field.disabled = readOnly;
                }
            });
            depositTransferDirectionTabs.forEach((tab) => {
                tab.disabled = readOnly;
            });
            if (depositTransferPostLedger) {
                depositTransferPostLedger.disabled = readOnly;
            }
        }

        function resetDepositTransferForm(trigger = null) {
            if (depositTransferForm) {
                depositTransferForm.reset();
                depositTransferForm.action = trigger?.dataset.storeUrl || depositTransferStoreAction;
                depositTransferForm.dataset.mode = 'save';
                depositTransferForm.dataset.saveAction = depositTransferForm.action;
                depositTransferForm.dataset.reverseAction = '';
            }
            if (depositTransferMethod) {
                depositTransferMethod.disabled = true;
            }
            setDepositTransferReadOnly(false);
            setDepositTransferDirection('account_to_deposit');
            if (depositTransferSubmit) {
                depositTransferSubmit.textContent = 'Выполнить';
                depositTransferSubmit.hidden = false;
            }
            if (depositTransferDelete) {
                depositTransferDelete.hidden = true;
            }
            if (depositTransferReverse) {
                depositTransferReverse.hidden = true;
                depositTransferReverse.disabled = false;
            }
            if (depositTransferPostLedger) {
                depositTransferPostLedger.checked = true;
                depositTransferPostLedger.disabled = false;
            }
            if (depositTransferPostLedgerField) {
                depositTransferPostLedgerField.hidden = false;
            }
            if (depositTransferDeleteForm) {
                depositTransferDeleteForm.action = '';
            }
        }

        function fillDepositTransferForm(trigger) {
            if (!depositTransferForm || !(trigger instanceof HTMLElement)) {
                return;
            }
            const posted = trigger.dataset.transferPosted === '1';
            depositTransferForm.action = trigger.dataset.transferUpdateUrl || depositTransferStoreAction;
            depositTransferForm.dataset.mode = 'save';
            depositTransferForm.dataset.saveAction = trigger.dataset.transferUpdateUrl || depositTransferStoreAction;
            depositTransferForm.dataset.reverseAction = trigger.dataset.transferReverseUrl || '';
            if (depositTransferMethod) {
                depositTransferMethod.disabled = false;
                depositTransferMethod.value = 'PUT';
            }
            setDepositTransferDirection(trigger.dataset.transferDirection || 'account_to_deposit');
            if (depositTransferDeposit) {
                depositTransferDeposit.value = trigger.dataset.transferDeposit || '';
            }
            if (depositTransferAccount) {
                depositTransferAccount.value = trigger.dataset.transferAccount || '';
            }
            if (depositTransferAmount) {
                depositTransferAmount.value = trigger.dataset.transferAmount || '';
            }
            if (depositTransferNote) {
                depositTransferNote.value = trigger.dataset.transferNote || '';
            }
            if (depositTransferSubmit) {
                depositTransferSubmit.textContent = 'Сохранить';
                depositTransferSubmit.hidden = posted;
            }
            if (depositTransferDelete) {
                depositTransferDelete.hidden = posted;
            }
            if (depositTransferReverse) {
                depositTransferReverse.hidden = !posted;
                depositTransferReverse.disabled = !posted;
            }
            if (depositTransferPostLedger) {
                depositTransferPostLedger.checked = posted;
                depositTransferPostLedger.disabled = posted;
            }
            if (depositTransferPostLedgerField) {
                depositTransferPostLedgerField.hidden = posted;
            }
            if (depositTransferDeleteForm) {
                depositTransferDeleteForm.action = trigger.dataset.transferDeleteUrl || '';
            }
            setDepositTransferReadOnly(posted);
            validateDepositTransferForm();
        }

        depositTransferDirectionTabs.forEach((tab) => {
            tab.addEventListener('click', () => {
                setDepositTransferDirection(tab.dataset.depositTransferDirectionTab || 'account_to_deposit');
            });
        });

        [depositTransferDeposit, depositTransferAccount, depositTransferAmount].forEach((element) => {
            element?.addEventListener('input', validateDepositTransferForm);
            element?.addEventListener('change', validateDepositTransferForm);
        });

        depositTransferForm?.addEventListener('submit', (event) => {
            if (depositTransferForm.dataset.mode === 'reverse') {
                return;
            }
            if (!validateDepositTransferForm()) {
                event.preventDefault();
            }
        });

        root.querySelectorAll('[data-deposit-transfer-create]').forEach((trigger) => {
            trigger.addEventListener('click', () => resetDepositTransferForm(trigger));
        });

        root.querySelectorAll('.bank-deposit-transfer-row').forEach((row) => {
            row.addEventListener('click', () => fillDepositTransferForm(row));
            row.addEventListener('keydown', (event) => {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    row.click();
                }
            });
        });

        document.getElementById('depositTransferModal')?.addEventListener('show.bs.modal', (event) => {
            const trigger = event.relatedTarget;
            if (trigger instanceof HTMLElement && trigger.dataset.depositTransferCreate === '1') {
                resetDepositTransferForm(trigger);
            } else if (trigger instanceof HTMLElement && trigger.dataset.transferUpdateUrl) {
                fillDepositTransferForm(trigger);
            } else {
                setDepositTransferDirection(depositTransferDirection?.value || 'account_to_deposit');
            }
        });

        depositTransferSubmit?.addEventListener('click', () => {
            if (!depositTransferForm) return;
            depositTransferForm.dataset.mode = 'save';
            depositTransferForm.action = depositTransferForm.dataset.saveAction || depositTransferStoreAction;
            if (depositTransferMethod) {
                depositTransferMethod.disabled = depositTransferForm.action === depositTransferStoreAction;
                depositTransferMethod.value = 'PUT';
            }
        });

        depositTransferReverse?.addEventListener('click', () => {
            if (!depositTransferForm) return;
            depositTransferForm.dataset.mode = 'reverse';
            depositTransferForm.action = depositTransferForm.dataset.reverseAction || depositTransferForm.action;
            if (depositTransferMethod) {
                depositTransferMethod.disabled = true;
            }
        });

        depositTransferDelete?.addEventListener('click', (event) => {
            if (!confirm('Удалить трансфер и выполнить обратное движение остатков?')) {
                event.preventDefault();
            }
        });

        depositFilterApply?.addEventListener('click', () => {
            applyDepositFilter(depositFilterSelect?.value || '', operationFilterSelect?.value || '');
            const filterModal = document.getElementById('depositFilterModal');
            if (filterModal && window.bootstrap?.Modal) {
                bootstrap.Modal.getInstance(filterModal)?.hide();
            }
        });

        depositFilterReset?.addEventListener('click', () => {
            if (depositFilterSelect) {
                depositFilterSelect.value = '';
            }
            if (operationFilterSelect) {
                operationFilterSelect.value = '';
            }
            applyDepositFilter('', '');
            const filterModal = document.getElementById('depositFilterModal');
            if (filterModal && window.bootstrap?.Modal) {
                bootstrap.Modal.getInstance(filterModal)?.hide();
            }
        });

        function parseJsonAttribute(value, fallback) {
            try {
                return JSON.parse(value || '');
            } catch (error) {
                return fallback;
            }
        }

        function formatDateTimeLocal(value = new Date()) {
            if (typeof value === 'string') {
                const raw = value.trim();
                if (!raw) {
                    return '';
                }
                if (/^\d{4}-\d{2}-\d{2}$/.test(raw)) {
                    return `${raw}T00:00`;
                }
                return raw.replace(' ', 'T').slice(0, 16);
            }

            const date = value instanceof Date ? value : new Date(value);
            if (Number.isNaN(date.getTime())) {
                return '';
            }
            const pad = (number) => String(number).padStart(2, '0');
            return [
                date.getFullYear(),
                pad(date.getMonth() + 1),
                pad(date.getDate()),
            ].join('-') + 'T' + [pad(date.getHours()), pad(date.getMinutes())].join(':');
        }

        const modal = root.querySelector('[data-invest-operation-modal]');
        const form = root.querySelector('[data-invest-operation-form]');
        const method = root.querySelector('[data-invest-operation-method]');
        const submit = root.querySelector('[data-invest-operation-submit]');
        const deleteButton = root.querySelector('[data-invest-operation-delete]');
        const reverseButton = root.querySelector('[data-invest-operation-reverse]');
        const direction = root.querySelector('[data-invest-operation-direction]');
        const directionTabs = root.querySelectorAll('[data-invest-operation-direction-tab]');
        const updateBalance = root.querySelector('[data-invest-operation-update-balance]');
        const postLedger = root.querySelector('[data-invest-operation-post-ledger]');
        const postLedgerField = postLedger ? postLedger.closest('.bank-operation-post-ledger') : null;
        const account = root.querySelector('[data-invest-operation-account]');
        const asset = root.querySelector('[data-invest-operation-asset]');
        const currency = root.querySelector('[data-invest-operation-currency]');
        const amount = root.querySelector('[data-invest-operation-amount]');
        const amountLabel = root.querySelector('[data-invest-operation-amount-label]');
        const amountHint = root.querySelector('[data-invest-operation-amount-hint]');
        const valueSectionTitle = root.querySelector('[data-invest-operation-value-section-title]');
        const accountSection = root.querySelector('[data-invest-operation-account-section]');
        const ledgerCopy = root.querySelector('[data-invest-operation-ledger-copy]');
        const revaluationNote = root.querySelector('[data-invest-operation-revaluation-note]');
        const operatedAt = root.querySelector('[data-invest-operation-date]');
        const note = root.querySelector('[data-invest-operation-note]');
        const storeAction = form ? form.action : '';
        const movementFields = {
            date: root.querySelector('[data-pool-movement-field="date"]'),
            account: root.querySelector('[data-pool-movement-field="account"]'),
            pool: root.querySelector('[data-pool-movement-field="pool"]'),
            amount: root.querySelector('[data-pool-movement-field="amount"]'),
            comment: root.querySelector('[data-pool-movement-field="comment"]'),
        };

        function syncCurrencyFromAccount() {
            if (!account || !currency || direction?.value === 'revaluation') {
                return;
            }
            const selectedOption = account.selectedOptions && account.selectedOptions.length > 0
                ? account.selectedOptions[0]
                : null;
            const accountCurrency = selectedOption?.dataset.currency || '';
            if (accountCurrency) {
                currency.value = accountCurrency;
            }
        }

        function setDirection(nextDirection) {
            if (direction) direction.value = nextDirection;
            directionTabs.forEach((tab) => {
                tab.classList.toggle('is-active', tab.dataset.investOperationDirectionTab === nextDirection);
            });
            if (updateBalance) updateBalance.disabled = nextDirection === 'revaluation';
            if (accountSection) accountSection.hidden = nextDirection === 'revaluation';
            if (account) account.required = nextDirection !== 'revaluation';
            if (revaluationNote) revaluationNote.hidden = nextDirection !== 'revaluation';
            if (amountLabel) amountLabel.textContent = nextDirection === 'revaluation' ? 'Дельта стоимости' : 'Сумма';
            if (currency) {
                currency.readOnly = nextDirection !== 'revaluation';
                if (nextDirection !== 'revaluation') {
                    syncCurrencyFromAccount();
                }
            }
            if (valueSectionTitle) valueSectionTitle.textContent = nextDirection === 'revaluation' ? '2. Дельта стоимости пула' : '3. Сумма и дата';
            if (amountHint) {
                amountHint.textContent = nextDirection === 'revaluation'
                    ? 'Введите изменение стоимости: + увеличивает пул, - уменьшает пул.'
                    : nextDirection === 'asset_to_account'
                        ? 'Сумма будет возвращена из пула на операционный счет.'
                        : 'Сумма будет списана со счета и отражена на пуле.';
            }
            if (ledgerCopy) {
                ledgerCopy.textContent = nextDirection === 'revaluation'
                    ? 'Положительная дельта: Дт Пул · Кт Доход. Отрицательная дельта: Дт Расход · Кт Пул.'
                    : nextDirection === 'asset_to_account'
                        ? 'Дт Операционный счет · Кт Пул. Остаток операционного счета увеличится.'
                        : 'Дт Пул · Кт Операционный счет. Остаток операционного счета уменьшится.';
            }

            const order = nextDirection === 'asset_to_account'
                ? ['date', 'pool', 'account', 'amount', 'comment']
                : ['date', 'account', 'pool', 'amount', 'comment'];
            if (nextDirection === 'revaluation') {
                order.splice(1, 2, 'pool');
            }
            order.forEach((key, index) => {
                if (movementFields[key]) {
                    movementFields[key].style.order = String(index + 1);
                }
            });
            if (movementFields.account) {
                movementFields.account.hidden = nextDirection === 'revaluation';
            }
        }

        function setReadOnly(readOnly) {
            [account, asset, currency, amount, operatedAt, note, postLedger].forEach((field) => {
                if (field) field.disabled = readOnly;
            });
            directionTabs.forEach((tab) => {
                tab.disabled = readOnly;
            });
        }

        function resetForm() {
            form?.reset();
            if (form) {
                form.action = storeAction;
                form.dataset.mode = 'save';
                form.dataset.saveAction = storeAction;
                form.dataset.deleteAction = '';
                form.dataset.reverseAction = '';
            }
            if (method) method.disabled = true;
            setReadOnly(false);
            setDirection('account_to_asset');
            if (postLedger) {
                postLedger.checked = true;
                postLedger.disabled = false;
            }
            if (postLedgerField) postLedgerField.hidden = false;
            if (operatedAt) {
                operatedAt.value = formatDateTimeLocal();
            }
            if (submit) {
                submit.textContent = 'Сохранить';
                submit.hidden = false;
            }
            if (deleteButton) deleteButton.hidden = true;
            if (reverseButton) {
                reverseButton.hidden = true;
                reverseButton.disabled = false;
            }
        }

        function fillForm(movement) {
            if (!form || !movement) return;
            form.action = movement.update_action || storeAction;
            form.dataset.mode = 'save';
            form.dataset.saveAction = movement.update_action || storeAction;
            form.dataset.deleteAction = movement.destroy_action || '';
            form.dataset.reverseAction = movement.reverse_action || '';
            if (method) {
                method.disabled = false;
                method.value = 'PUT';
            }
            setDirection(movement.direction || 'account_to_asset');
            if (postLedger) {
                postLedger.checked = Boolean(movement.is_posted);
                postLedger.disabled = Boolean(movement.is_posted) || !movement.can_edit;
            }
            if (postLedgerField) postLedgerField.hidden = Boolean(movement.is_posted);
            if (account) account.value = String(movement.account_id || '');
            if (asset) asset.value = movement.asset_key || '';
            if (currency) currency.value = movement.currency || 'USD';
            if ((movement.direction || 'account_to_asset') !== 'revaluation') {
                syncCurrencyFromAccount();
            }
            if (amount) amount.value = movement.amount || movement.value_usd || '';
            if (operatedAt) operatedAt.value = formatDateTimeLocal(movement.date || '');
            if (note) note.value = movement.note || '';
            if (submit) submit.hidden = Boolean(movement.is_posted) || !movement.can_edit;
            if (deleteButton) deleteButton.hidden = Boolean(movement.is_posted) || !movement.can_edit;
            if (reverseButton) {
                reverseButton.textContent = 'Отменить проводку';
                reverseButton.hidden = !movement.is_posted;
                reverseButton.disabled = !movement.can_reverse;
            }
            setReadOnly(Boolean(movement.is_posted) || !movement.can_edit);
        }

        directionTabs.forEach((tab) => {
            tab.addEventListener('click', () => setDirection(tab.dataset.investOperationDirectionTab || 'account_to_asset'));
        });

        account?.addEventListener('change', syncCurrencyFromAccount);

        submit?.addEventListener('click', () => {
            if (!form) return;
            form.dataset.mode = 'save';
            form.action = form.dataset.saveAction || storeAction;
            if (method) {
                method.disabled = form.action === storeAction;
                method.value = 'PUT';
            }
        });

        deleteButton?.addEventListener('click', () => {
            if (!form) return;
            form.dataset.mode = 'delete';
            form.action = form.dataset.deleteAction || form.action;
            if (method) {
                method.disabled = false;
                method.value = 'DELETE';
            }
        });

        reverseButton?.addEventListener('click', () => {
            if (!form) return;
            form.dataset.mode = 'reverse';
            form.action = form.dataset.reverseAction || form.action;
            if (method) method.disabled = true;
        });

        root.querySelectorAll('[data-invest-operation-open]').forEach((button) => {
            button.addEventListener('click', () => {
                resetForm();
                if (modal) modal.hidden = false;
                account?.focus();
            });
        });

        root.querySelectorAll('[data-invest-operation-edit]').forEach((row) => {
            row.addEventListener('click', () => {
                fillForm(parseJsonAttribute(row.dataset.operationMovement, null));
                if (modal) modal.hidden = false;
                amount?.focus();
            });
        });

        root.querySelectorAll('[data-invest-operation-close]').forEach((button) => {
            button.addEventListener('click', () => {
                if (modal) modal.hidden = true;
            });
        });
    });
</script>
@endpush
