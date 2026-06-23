@extends('home')

@section('title')
Депозиты
@endsection

@section('content')
<div class="bank-page">
    @include('bank.partials.nav')

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
                                <div class="bank-deposit-status-cell">
                                    <span class="bank-status {{ $deposit->is_active ? '' : 'bank-status--reversed' }}">{{ $deposit->status_label }}</span>
                                    <button type="button"
                                        class="bank-icon-button bank-deposit-settings-button"
                                        title="Редактировать депозит"
                                        aria-label="Редактировать депозит"
                                        data-deposit-settings-open="1"
                                        data-deposit-id="{{ $deposit->id }}"
                                        data-deposit-name="{{ $deposit->name }}"
                                        data-deposit-currency="{{ $deposit->currency }}"
                                        data-deposit-balance="{{ number_format((float) $deposit->balance, 2, '.', ' ') }}"
                                        data-deposit-type="{{ $deposit->deposit_type }}"
                                        data-deposit-type-label="{{ $deposit->deposit_type_label }}"
                                        data-update-url="{{ route('bank.deposit.update', ['deposit' => $deposit->id]) }}">
                                        &#9881;
                                    </button>
                                </div>
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

        <div class="tab-pane fade" id="bankDepositsPoolsPane" role="tabpanel" aria-labelledby="bankDepositsPoolsTab">
            <section class="bank-panel bank-table-panel">
                <div class="bank-table-header">
                    <div>
                        <div class="bank-label">Пулы</div>
                        <div class="bank-meta">Пулы из fund_pools с депозитными агрегатами по валюте пула.</div>
                    </div>
                    <div class="bank-meta">{{ $depositPools->count() }} пулов</div>
                </div>
                <div class="table-responsive bank-table-scroll bank-table-scroll--compact">
                    <table class="table table-dark table-hover table-sm align-middle bank-table bank-table--deposit-pools">
                        <thead>
                            <tr>
                                <th class="bank-table__num">№</th>
                                <th>Пул</th>
                                <th>Статус сети</th>
                                <th class="text-end">Депозиты</th>
                                <th class="text-end">Лимит депозитов</th>
                                <th class="text-end">Учетный остаток</th>
                                <th class="text-end">On-chain остаток</th>
                                <th class="text-end">Разница</th>
                                <th class="text-end">APY</th>
                                <th>Сеть</th>
                                <th>Статус</th>
                                <th class="text-end">Действие</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($depositPools as $pool)
                                <tr>
                                    <td class="bank-table__num bank-mono">{{ $loop->iteration }}</td>
                                    <td>
                                        <button type="button"
                                            class="bank-account-link bank-pool-transfer-link"
                                            data-bs-toggle="modal"
                                            data-bs-target="#poolTransferModal"
                                            data-pool-transfer-create="1"
                                            data-pool-asset-key="{{ $pool->asset_key }}"
                                            data-pool-name="{{ $pool->name }}"
                                            data-pool-balance="{{ number_format((float) ($pool->accounting_operations_count > 0 ? $pool->accounting_balance_usd : $pool->balance_usdc), 8, '.', '') }}"
                                            data-pool-currency="USDC">
                                            <strong>{{ $pool->name }}</strong>
                                        </button>
                                        <div class="bank-meta">
                                            {{ $pool->symbol ?: '—' }}
                                            @if($pool->chain_status === 'onchain')
                                                · {{ $pool->pool_object_short }}
                                            @endif
                                            @if($pool->is_default_deposit)
                                                · default deposit
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <span class="bank-status {{ $pool->chain_status === 'onchain' ? '' : 'bank-status--pending' }}">
                                            {{ $pool->chain_status_label }}
                                        </span>
                                    </td>
                                    <td class="text-end fw-semibold">
                                        {{ number_format((float) $pool->deposit_balance, 2, '.', ' ') }} {{ $pool->deposit_currency }}
                                        <div class="bank-meta">{{ $pool->deposit_count }} депозитов</div>
                                    </td>
                                    <td class="text-end">
                                        {{ $pool->deposit_limit > 0 ? number_format((float) $pool->deposit_limit, 2, '.', ' ') . ' ' . $pool->deposit_currency : '—' }}
                                    </td>
                                    <td class="text-end fw-semibold">
                                        {{ number_format((float) $pool->accounting_balance_usd, 2, '.', ' ') }} USDC
                                        <div class="bank-meta">{{ $pool->accounting_operations_count }} операций</div>
                                    </td>
                                    <td class="text-end">
                                        {{ number_format((float) $pool->balance_usdc, 2, '.', ' ') }} USDC
                                    </td>
                                    <td class="text-end {{ abs((float) $pool->accounting_difference_usd) > 0.000001 ? 'text-warning' : '' }}">
                                        {{ number_format((float) $pool->accounting_difference_usd, 2, '.', ' ') }} USDC
                                    </td>
                                    <td class="text-end">{{ number_format(((int) $pool->apy_bps) / 100, 2, '.', ' ') }}%</td>
                                    <td>
                                        <span class="bank-pill bank-pill--currency">{{ $pool->network ?: '—' }}</span>
                                    </td>
                                    <td>
                                        <span class="bank-status {{ $pool->active ? '' : 'bank-status--reversed' }}">{{ $pool->active ? 'active' : 'paused' }}</span>
                                    </td>
                                    <td class="text-end">
                                        <button type="button"
                                            class="btn btn-sm btn-outline-light"
                                            data-bs-toggle="modal"
                                            data-bs-target="#poolTransferModal"
                                            data-pool-transfer-create="1"
                                            data-pool-asset-key="{{ $pool->asset_key }}"
                                            data-pool-name="{{ $pool->name }}"
                                            data-pool-balance="{{ number_format((float) ($pool->accounting_operations_count > 0 ? $pool->accounting_balance_usd : $pool->balance_usdc), 8, '.', '') }}"
                                            data-pool-currency="USDC">
                                            Трансфер
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="12" class="text-center text-muted py-4">Пулы пока не созданы.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>

    <div class="modal fade bank-order-modal" id="poolTransferModal" tabindex="-1" aria-labelledby="poolTransferModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" action="{{ route('bank.invest-operations.store') }}" data-pool-transfer-form>
                    @csrf
                    <input type="hidden" name="direction" value="asset_to_account" data-pool-transfer-direction>
                    <input type="hidden" name="asset_key" data-pool-transfer-asset>
                    <input type="hidden" name="currency" value="USDC">
                    <input type="hidden" name="quantity" value="0">
                    <input type="hidden" name="update_account_balance" value="1">
                    <input type="hidden" name="redirect_to" value="bank.deposit">
                    <div class="modal-header">
                        <div>
                            <div class="bank-label">Пул ↔ операционный счет</div>
                            <h5 class="modal-title" id="poolTransferModalLabel" data-pool-transfer-title>Трансфер пула</h5>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Маршрут</label>
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    <button type="button" class="btn btn-sm btn-outline-light" data-pool-transfer-direction-button="account_to_asset">
                                        Счет → пул
                                    </button>
                                    <button type="button" class="btn btn-sm btn-primary" data-pool-transfer-direction-button="asset_to_account">
                                        Пул → счет
                                    </button>
                                </div>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Пул</label>
                                <select class="form-select" required data-pool-transfer-pool>
                                    <option value="">Выберите пул</option>
                                    @foreach($depositPools as $pool)
                                        <option value="{{ $pool->asset_key }}"
                                            data-name="{{ $pool->name }}"
                                            data-balance="{{ number_format((float) ($pool->accounting_operations_count > 0 ? $pool->accounting_balance_usd : $pool->balance_usdc), 8, '.', '') }}"
                                            data-currency="USDC">
                                            {{ $pool->name }} · {{ $pool->chain_status_label }} · {{ number_format((float) $pool->accounting_balance_usd, 2, '.', ' ') }} USDC
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Операционный счет</label>
                                <select name="account_id" class="form-select" required data-pool-transfer-account>
                                    <option value="">Выберите счет</option>
                                    @foreach($operationalAccounts as $account)
                                        <option value="{{ $account->id }}" data-currency="{{ $account->currency }}" data-balance="{{ number_format((float) $account->balance, 2, '.', '') }}">
                                            {{ $account->label }} · {{ $account->currency }} · {{ number_format((float) $account->balance, 2, '.', ' ') }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="bank-meta" data-pool-transfer-account-meta></div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Сумма USDC</label>
                                <input type="number" name="amount" class="form-control" min="0.00000001" step="0.00000001" required data-pool-transfer-amount>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Дата</label>
                                <input type="date" name="operated_at" class="form-control" data-pool-transfer-date>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Комментарий</label>
                                <textarea name="note" class="form-control" rows="3" data-pool-transfer-note></textarea>
                            </div>
                        </div>
                        <div class="alert alert-danger mt-3 mb-0" data-pool-transfer-error hidden></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                        <button type="submit" class="btn btn-primary" data-pool-transfer-submit>Выполнить</button>
                    </div>
                </form>
            </div>
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
                            <button class="nav-link active" id="depositSettingsTab" data-bs-toggle="tab" data-bs-target="#depositSettingsPane" type="button" role="tab">
                                Настройки
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content pt-3">
                        <div class="tab-pane fade show active" id="depositSettingsPane" role="tabpanel" aria-labelledby="depositSettingsTab">
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

</div>

@include('bank.partials.styles')
@include('bank.partials.terminal_amount_inputs')
<style>
    .bank-page .bank-table--deposits {
        table-layout: fixed;
        min-width: 920px;
    }

    .bank-page .bank-table--deposit-transfers {
        table-layout: fixed;
        min-width: 860px;
    }

    .bank-page .bank-table--deposit-pools {
        table-layout: fixed;
        min-width: 1120px;
    }

    .bank-page .bank-table--deposits th,
    .bank-page .bank-table--deposits td,
    .bank-page .bank-table--deposit-transfers th,
    .bank-page .bank-table--deposit-transfers td,
    .bank-page .bank-table--deposit-pools th,
    .bank-page .bank-table--deposit-pools td {
        overflow: hidden;
        padding-right: 0.35rem;
        padding-left: 0.35rem;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .bank-page .bank-table--deposits th:not(.bank-table__num),
    .bank-page .bank-table--deposits td:not(.bank-table__num) {
        min-width: 0;
    }

    .bank-page .bank-table--deposits th:nth-child(1),
    .bank-page .bank-table--deposits td:nth-child(1),
    .bank-page .bank-table--deposit-transfers th:nth-child(1),
    .bank-page .bank-table--deposit-transfers td:nth-child(1),
    .bank-page .bank-table--deposit-pools th:nth-child(1),
    .bank-page .bank-table--deposit-pools td:nth-child(1) {
        width: 38px;
    }

    .bank-page .bank-table--deposits th:nth-child(2),
    .bank-page .bank-table--deposits td:nth-child(2) {
        width: 220px;
        min-width: 0;
    }

    .bank-page .bank-table--deposits th:nth-child(3),
    .bank-page .bank-table--deposits td:nth-child(3) {
        width: 190px;
        min-width: 0;
    }

    .bank-page .bank-table--deposits th:nth-child(4),
    .bank-page .bank-table--deposits td:nth-child(4) {
        width: 74px;
    }

    .bank-page .bank-table--deposits th:nth-child(5),
    .bank-page .bank-table--deposits td:nth-child(5),
    .bank-page .bank-table--deposits th:nth-child(6),
    .bank-page .bank-table--deposits td:nth-child(6) {
        width: 112px;
    }

    .bank-page .bank-table--deposits th:nth-child(7),
    .bank-page .bank-table--deposits td:nth-child(7),
    .bank-page .bank-table--deposits th:nth-child(8),
    .bank-page .bank-table--deposits td:nth-child(8) {
        width: 92px;
    }

    .bank-page .bank-table--deposit-transfers th:nth-child(2),
    .bank-page .bank-table--deposit-transfers td:nth-child(2) {
        width: 130px;
    }

    .bank-page .bank-table--deposit-transfers th:nth-child(3),
    .bank-page .bank-table--deposit-transfers td:nth-child(3) {
        width: 112px;
    }

    .bank-page .bank-table--deposit-transfers th:nth-child(4),
    .bank-page .bank-table--deposit-transfers td:nth-child(4),
    .bank-page .bank-table--deposit-transfers th:nth-child(5),
    .bank-page .bank-table--deposit-transfers td:nth-child(5) {
        width: 190px;
    }

    .bank-page .bank-table--deposit-transfers th:nth-child(6),
    .bank-page .bank-table--deposit-transfers td:nth-child(6) {
        width: 130px;
    }

    .bank-page .bank-table--deposit-transfers th:nth-child(7),
    .bank-page .bank-table--deposit-transfers td:nth-child(7) {
        width: 86px;
    }

    .bank-page .bank-table--deposit-pools th:nth-child(2),
    .bank-page .bank-table--deposit-pools td:nth-child(2) {
        width: 180px;
    }

    .bank-page .bank-table--deposit-pools th:nth-child(3),
    .bank-page .bank-table--deposit-pools td:nth-child(3),
    .bank-page .bank-table--deposit-pools th:nth-child(10),
    .bank-page .bank-table--deposit-pools td:nth-child(10),
    .bank-page .bank-table--deposit-pools th:nth-child(11),
    .bank-page .bank-table--deposit-pools td:nth-child(11) {
        width: 82px;
    }

    .bank-page .bank-table--deposit-pools th:nth-child(4),
    .bank-page .bank-table--deposit-pools td:nth-child(4),
    .bank-page .bank-table--deposit-pools th:nth-child(5),
    .bank-page .bank-table--deposit-pools td:nth-child(5),
    .bank-page .bank-table--deposit-pools th:nth-child(6),
    .bank-page .bank-table--deposit-pools td:nth-child(6),
    .bank-page .bank-table--deposit-pools th:nth-child(7),
    .bank-page .bank-table--deposit-pools td:nth-child(7),
    .bank-page .bank-table--deposit-pools th:nth-child(8),
    .bank-page .bank-table--deposit-pools td:nth-child(8) {
        width: 108px;
    }

    .bank-page .bank-table--deposit-pools th:nth-child(9),
    .bank-page .bank-table--deposit-pools td:nth-child(9) {
        width: 64px;
    }

    .bank-page .bank-table--deposit-pools th:nth-child(12),
    .bank-page .bank-table--deposit-pools td:nth-child(12) {
        width: 92px;
    }

    .bank-page .bank-table--deposits .bank-meta,
    .bank-page .bank-table--deposit-transfers .bank-meta,
    .bank-page .bank-table--deposit-pools .bank-meta {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .bank-pool-transfer-link {
        display: inline;
        padding: 0;
        border: 0;
        background: transparent;
        text-align: left;
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

    .bank-deposit-status-cell {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
    }

    .bank-icon-button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 28px;
        height: 28px;
        border: 1px solid rgba(148, 163, 184, 0.3);
        border-radius: 6px;
        background: rgba(15, 23, 42, 0.55);
        color: rgba(226, 232, 240, 0.92);
        font-size: 15px;
        line-height: 1;
        transition: border-color 0.15s ease, background-color 0.15s ease, color 0.15s ease;
    }

    .bank-icon-button:hover,
    .bank-icon-button:focus {
        border-color: rgba(96, 165, 250, 0.75);
        background: rgba(30, 64, 175, 0.38);
        color: #fff;
        outline: none;
    }
</style>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const modal = document.getElementById('depositMovementModal');
        if (!modal) {
            return;
        }

        const initialDepositTab = new URLSearchParams(window.location.search).get('tab');
        const initialDepositTabId = {
            portfolio: 'bankDepositsPortfolioTab',
            pools: 'bankDepositsPoolsTab',
        }[initialDepositTab || ''];
        if (initialDepositTabId) {
            const initialDepositTabButton = document.getElementById(initialDepositTabId);
            if (initialDepositTabButton && window.bootstrap?.Tab) {
                bootstrap.Tab.getOrCreateInstance(initialDepositTabButton).show();
            }
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
        const settingsTab = modal.querySelector('#depositSettingsTab');
        const transferForm = document.querySelector('[data-deposit-transfer-form]');
        const transferMethod = document.querySelector('[data-deposit-transfer-method]');
        const transferTitle = document.querySelector('[data-deposit-transfer-title]');
        const transferDirection = document.querySelector('[data-deposit-transfer-direction]');
        const transferRouteLabel = document.querySelector('[data-deposit-transfer-route-label]');
        const transferToggleRoute = document.querySelector('[data-deposit-transfer-toggle-route]');
        const transferDeposit = document.querySelector('[data-deposit-transfer-deposit]');
        const transferAccount = document.querySelector('[data-deposit-transfer-account]');
        const transferAmount = document.querySelector('[data-deposit-transfer-amount]');
        const transferError = document.querySelector('[data-deposit-transfer-error]');
        const transferSubmit = document.querySelector('[data-deposit-transfer-submit]');
        const transferAccountMeta = document.querySelector('[data-deposit-transfer-account-meta]');
        const transferDelete = document.querySelector('[data-deposit-transfer-delete]');
        const transferReverse = document.querySelector('[data-deposit-transfer-reverse]');
        const transferPostLedger = document.querySelector('[data-deposit-transfer-post-ledger]');
        const transferPostLedgerField = document.querySelector('[data-deposit-transfer-post-ledger-field]');
        const transferDeleteForm = document.querySelector('[data-deposit-transfer-delete-form]');
        const transferStoreAction = transferForm ? transferForm.action : '';
        const depositTransferModalElement = document.getElementById('depositTransferModal');
        const depositMovementsUrl = @json(route('bank.pool-movements', ['tab' => 'deposits']));
        const poolTransferModal = document.getElementById('poolTransferModal');
        const poolTransferForm = document.querySelector('[data-pool-transfer-form]');
        const poolTransferTitle = document.querySelector('[data-pool-transfer-title]');
        const poolTransferDirection = document.querySelector('[data-pool-transfer-direction]');
        const poolTransferDirectionButtons = document.querySelectorAll('[data-pool-transfer-direction-button]');
        const poolTransferAsset = document.querySelector('[data-pool-transfer-asset]');
        const poolTransferPool = document.querySelector('[data-pool-transfer-pool]');
        const poolTransferAccount = document.querySelector('[data-pool-transfer-account]');
        const poolTransferAccountMeta = document.querySelector('[data-pool-transfer-account-meta]');
        const poolTransferAmount = document.querySelector('[data-pool-transfer-amount]');
        const poolTransferDate = document.querySelector('[data-pool-transfer-date]');
        const poolTransferNote = document.querySelector('[data-pool-transfer-note]');
        const poolTransferError = document.querySelector('[data-pool-transfer-error]');
        const poolTransferSubmit = document.querySelector('[data-pool-transfer-submit]');

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
            const isEdit = Boolean(transferMethod && !transferMethod.disabled);
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
            } else if (!isEdit) {
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

        function validatePoolTransferForm() {
            if (!poolTransferForm || !poolTransferAccount || !poolTransferAmount || !poolTransferError || !poolTransferSubmit || !poolTransferDirection || !poolTransferPool || !poolTransferAsset) {
                return true;
            }

            Array.from(poolTransferAccount.options).forEach((option) => {
                if (!option.value) {
                    option.hidden = false;
                    return;
                }
                option.hidden = option.dataset.currency !== 'USDC';
            });

            const accountOption = selectedOption(poolTransferAccount);
            const poolOption = selectedOption(poolTransferPool);
            const amount = Number(poolTransferAmount.value || 0);
            const direction = poolTransferDirection.value || 'asset_to_account';
            let message = '';

            poolTransferAsset.value = poolOption?.value || '';

            if (!poolOption?.value) {
                message = 'Выберите пул.';
            } else if (!accountOption?.value) {
                message = 'Выберите операционный счет USDC.';
            } else if (accountOption.dataset.currency !== 'USDC') {
                message = 'Для перевода из пула нужен операционный счет USDC.';
            } else if (amount <= 0) {
                message = 'Введите сумму перевода.';
            } else if (direction === 'account_to_asset' && Number(accountOption.dataset.balance || 0) + 0.00000001 < amount) {
                message = 'Недостаточно средств на операционном счете.';
            }

            if (poolTransferAccountMeta) {
                poolTransferAccountMeta.textContent = accountOption?.value
                    ? (
                        direction === 'account_to_asset'
                            ? `После выполнения остаток счета будет уменьшен на ${formatAmount(amount)} USDC, учетный остаток пула увеличится.`
                            : `После выполнения остаток счета будет увеличен на ${formatAmount(amount)} USDC, учетный остаток пула уменьшится.`
                    )
                    : '';
            }

            poolTransferError.textContent = message;
            poolTransferError.hidden = message === '';
            poolTransferSubmit.disabled = message !== '';

            return message === '';
        }

        function syncPoolTransferPool(defaultAmount = false) {
            const poolOption = selectedOption(poolTransferPool);
            const poolName = poolOption?.dataset.name || '';
            if (poolTransferAsset) {
                poolTransferAsset.value = poolOption?.value || '';
            }
            if (poolTransferTitle) {
                poolTransferTitle.textContent = poolName !== '' ? `Трансфер пула ${poolName}` : 'Трансфер пула';
            }
            if (defaultAmount && poolTransferAmount) {
                const balance = Number(poolOption?.dataset.balance || 0);
                poolTransferAmount.value = balance > 0 ? balance.toFixed(8) : '';
            }
            if (poolTransferNote && poolName !== '') {
                poolTransferNote.value = `Трансфер пул ↔ операционный счет: ${poolName}`;
            }
            validatePoolTransferForm();
        }

        function setPoolTransferDirection(direction) {
            if (!poolTransferDirection) {
                return;
            }
            poolTransferDirection.value = direction;
            poolTransferDirectionButtons.forEach((button) => {
                const active = button.dataset.poolTransferDirectionButton === direction;
                button.classList.toggle('btn-primary', active);
                button.classList.toggle('btn-outline-light', !active);
            });
            validatePoolTransferForm();
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

        function setTransferReadOnly(readOnly) {
            [transferDeposit, transferAccount, transferAmount].forEach((field) => {
                if (field) {
                    field.disabled = readOnly;
                }
            });
            if (transferToggleRoute) {
                transferToggleRoute.disabled = readOnly;
            }
            if (transferPostLedger) {
                transferPostLedger.disabled = readOnly;
            }
        }

        function resetTransferForm(trigger = null) {
            if (transferForm) {
                transferForm.reset();
                transferForm.action = trigger?.dataset.storeUrl || transferStoreAction;
                transferForm.dataset.mode = 'save';
                transferForm.dataset.saveAction = transferForm.action;
                transferForm.dataset.reverseAction = '';
            }
            if (transferMethod) {
                transferMethod.disabled = true;
            }
            setTransferReadOnly(false);
            if (transferDirection) {
                transferDirection.value = 'account_to_deposit';
            }
            if (transferTitle) {
                transferTitle.textContent = 'Создать трансфер';
            }
            if (transferSubmit) {
                transferSubmit.textContent = 'Выполнить';
                transferSubmit.hidden = false;
            }
            if (transferDelete) {
                transferDelete.hidden = true;
            }
            if (transferReverse) {
                transferReverse.hidden = true;
                transferReverse.disabled = false;
            }
            if (transferPostLedger) {
                transferPostLedger.checked = true;
                transferPostLedger.disabled = false;
            }
            if (transferPostLedgerField) {
                transferPostLedgerField.hidden = false;
            }
            if (transferDeleteForm) {
                transferDeleteForm.action = '';
            }
            syncTransferRouteLabel();
        }

        function fillTransferForm(trigger) {
            if (!transferForm || !(trigger instanceof HTMLElement)) {
                return;
            }
            const posted = trigger.dataset.transferPosted === '1';
            transferForm.action = trigger.dataset.transferUpdateUrl || transferStoreAction;
            transferForm.dataset.mode = 'save';
            transferForm.dataset.saveAction = trigger.dataset.transferUpdateUrl || transferStoreAction;
            transferForm.dataset.reverseAction = trigger.dataset.transferReverseUrl || '';
            if (transferMethod) {
                transferMethod.disabled = false;
                transferMethod.value = 'PUT';
            }
            if (transferDirection) {
                transferDirection.value = trigger.dataset.transferDirection || 'account_to_deposit';
            }
            if (transferDeposit) {
                transferDeposit.value = trigger.dataset.transferDeposit || '';
            }
            if (transferAccount) {
                transferAccount.value = trigger.dataset.transferAccount || '';
            }
            if (transferAmount) {
                transferAmount.value = trigger.dataset.transferAmount || '';
            }
            if (transferTitle) {
                transferTitle.textContent = 'Изменить трансфер';
            }
            if (transferSubmit) {
                transferSubmit.textContent = 'Сохранить';
                transferSubmit.hidden = posted;
            }
            if (transferDelete) {
                transferDelete.hidden = posted;
            }
            if (transferReverse) {
                transferReverse.hidden = !posted;
                transferReverse.disabled = !posted;
            }
            if (transferPostLedger) {
                transferPostLedger.checked = posted;
                transferPostLedger.disabled = posted;
            }
            if (transferPostLedgerField) {
                transferPostLedgerField.hidden = posted;
            }
            if (transferDeleteForm) {
                transferDeleteForm.action = trigger.dataset.transferDeleteUrl || '';
            }
            setTransferReadOnly(posted);
            syncTransferRouteLabel();
        }

        function openDepositTransferModal(trigger) {
            if (!depositTransferModalElement) {
                window.location.href = depositMovementsUrl;
                return;
            }

            bootstrap.Modal.getOrCreateInstance(depositTransferModalElement).show(trigger);
        }

        function openDepositTransferModalFromMovement(trigger) {
            const movementModal = bootstrap.Modal.getInstance(modal);
            if (movementModal && modal.classList.contains('show')) {
                modal.addEventListener('hidden.bs.modal', () => openDepositTransferModal(trigger), { once: true });
                movementModal.hide();
                return;
            }

            openDepositTransferModal(trigger);
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
            if (transferForm.dataset.mode === 'reverse') {
                return;
            }
            if (!validateTransferForm()) {
                event.preventDefault();
            }
        });

        transferSubmit?.addEventListener('click', () => {
            if (!transferForm) {
                return;
            }
            transferForm.dataset.mode = 'save';
            transferForm.action = transferForm.dataset.saveAction || transferStoreAction;
            if (transferMethod) {
                transferMethod.disabled = transferForm.action === transferStoreAction;
                transferMethod.value = 'PUT';
            }
        });

        transferReverse?.addEventListener('click', () => {
            if (!transferForm) {
                return;
            }
            transferForm.dataset.mode = 'reverse';
            transferForm.action = transferForm.dataset.reverseAction || transferForm.action;
            if (transferMethod) {
                transferMethod.disabled = true;
            }
        });

        document.getElementById('depositTransferModal')?.addEventListener('show.bs.modal', (event) => {
            const trigger = event.relatedTarget;
            if (trigger instanceof HTMLElement && trigger.dataset.depositTransferCreate === '1') {
                resetTransferForm(trigger);
            } else if (trigger instanceof HTMLElement && trigger.dataset.transferUpdateUrl) {
                fillTransferForm(trigger);
            } else {
                syncTransferRouteLabel();
            }
        });

        poolTransferModal?.addEventListener('show.bs.modal', (event) => {
            const trigger = event.relatedTarget;
            if (!(trigger instanceof HTMLElement) || trigger.dataset.poolTransferCreate !== '1') {
                return;
            }

            if (poolTransferForm) {
                poolTransferForm.reset();
            }
            setPoolTransferDirection('asset_to_account');
            if (poolTransferPool) {
                poolTransferPool.value = trigger.dataset.poolAssetKey || '';
            }
            if (poolTransferPool?.value) {
                syncPoolTransferPool(true);
            } else if (poolTransferTitle) {
                poolTransferTitle.textContent = 'Трансфер пула';
            }
            if (poolTransferDate) {
                poolTransferDate.value = new Date().toISOString().slice(0, 10);
            }
            validatePoolTransferForm();
        });

        poolTransferDirectionButtons.forEach((button) => {
            button.addEventListener('click', () => {
                setPoolTransferDirection(button.dataset.poolTransferDirectionButton || 'asset_to_account');
            });
        });

        [poolTransferPool, poolTransferAccount, poolTransferAmount].forEach((element) => {
            element?.addEventListener('input', validatePoolTransferForm);
            element?.addEventListener('change', validatePoolTransferForm);
        });

        poolTransferPool?.addEventListener('change', () => {
            syncPoolTransferPool(true);
        });

        poolTransferForm?.addEventListener('submit', (event) => {
            if (!validatePoolTransferForm()) {
                event.preventDefault();
            }
        });

        transferDelete?.addEventListener('click', (event) => {
            if (!confirm('Удалить трансфер и выполнить обратное движение остатков?')) {
                event.preventDefault();
            }
        });

        modal.addEventListener('show.bs.modal', (event) => {
            const trigger = event.relatedTarget;
            if (!(trigger instanceof HTMLElement)) {
                return;
            }

            const isCreate = trigger.dataset.depositCreate === '1';
            if (isCreate) {
                modal.querySelector('#depositMovementModalLabel').textContent = 'Новый депозит';
                setSummary('balance', '0.00 UAH');
                setSummary('topups', '+0.00 UAH');
                setSummary('withdrawals', '−0.00 UAH');
                setSummary('net', '+0.00 UAH');
                if (movementsBody && emptyState) {
                    movementsBody.replaceChildren();
                    emptyState.hidden = false;
                }

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
            bootstrap.Tab.getOrCreateInstance(settingsTab).show();

            if (!movementsBody || !emptyState) {
                return;
            }

            movementsBody.replaceChildren();
            emptyState.hidden = movements.length > 0;

            movements.forEach((movement, index) => {
                const row = document.createElement('tr');
                const isWithdraw = movement.mode === 'withdraw';
                const statusClass = `bank-status--${movement.status}`;
                row.className = 'bank-deposit-movement-row';
                row.tabIndex = 0;
                row.setAttribute('role', 'button');
                row.dataset.transferDirection = movement.transfer_direction || (isWithdraw ? 'deposit_to_account' : 'account_to_deposit');
                row.dataset.transferDeposit = movement.transfer_deposit_id || movement.deposit_id || '';
                row.dataset.transferAccount = movement.transfer_account_id || '';
                row.dataset.transferAmount = String(Number(movement.amount || 0).toFixed(2));
                row.dataset.transferPosted = movement.transfer_posted ? '1' : '0';
                row.dataset.transferUpdateUrl = movement.transfer_update_url || '';
                row.dataset.transferReverseUrl = movement.transfer_reverse_url || '';
                row.dataset.transferDeleteUrl = movement.transfer_delete_url || '';
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
                const openMovementTransfer = () => openDepositTransferModalFromMovement(row);
                row.addEventListener('click', openMovementTransfer);
                row.addEventListener('keydown', (event) => {
                    if (event.key !== 'Enter' && event.key !== ' ') {
                        return;
                    }

                    event.preventDefault();
                    openMovementTransfer();
                });
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

        document.querySelectorAll('.bank-deposit-settings-button').forEach((button) => {
            button.addEventListener('click', (event) => {
                event.stopPropagation();
                bootstrap.Modal.getOrCreateInstance(modal).show(button);
            });
        });

        document.querySelectorAll('.bank-deposit-transfer-row').forEach((row) => {
            const openTransferModal = () => openDepositTransferModal(row);

            row.addEventListener('click', openTransferModal);
            row.addEventListener('keydown', (event) => {
                if (event.key !== 'Enter' && event.key !== ' ') {
                    return;
                }

                event.preventDefault();
                openTransferModal();
            });
        });
    });
</script>
@endpush
