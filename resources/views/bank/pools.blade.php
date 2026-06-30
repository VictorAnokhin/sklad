@extends('home')

@section('title')
Пулы
@endsection

@section('content')
@php
    $formatMoney = static fn ($value): string => number_format((float) $value, 2, '.', ' ');
    $formatBps = static fn ($value): string => number_format((float) $value / 100, 2, '.', ' ') . '%';
@endphp

<div class="bank-page bank-pools-page" data-bank-pools-page>
    @include('bank.partials.deposit_nav')

    <section class="bank-grid bank-grid--summary">
        <div class="bank-panel bank-panel--accent">
            <div class="bank-label">Пулы</div>
            <div class="bank-value">{{ $summary['pools'] }}</div>
            <div class="bank-meta">Всего записей в fund_pools.</div>
        </div>
        <div class="bank-panel">
            <div class="bank-label">Активные</div>
            <div class="bank-value">{{ $summary['active'] }}</div>
            <div class="bank-meta">Пулы со статусом active.</div>
        </div>
        <div class="bank-panel">
            <div class="bank-label">On-chain остаток</div>
            <div class="bank-value">{{ $formatMoney($summary['onchain_balance']) }}</div>
            <div class="bank-meta">По последним fund_pool_events.</div>
        </div>
        <div class="bank-panel">
            <div class="bank-label">APY</div>
            <div class="bank-value">{{ $formatBps($summary['avg_apy_bps']) }}</div>
            <div class="bank-meta">Средняя ставка по пулам.</div>
        </div>
    </section>

    <section class="bank-panel bank-table-panel">
        <div class="bank-table-header">
            <div>
                <div class="bank-label">Пулы</div>
                <div class="bank-meta">Только таблица пулов, без депозитов и депозитных операций.</div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <div class="bank-meta">{{ $pools->count() }} записей</div>
                <button type="button" class="btn btn-sm btn-primary" data-pool-open>Создать</button>
            </div>
        </div>
        <div class="table-responsive bank-table-scroll">
            <table class="table table-dark table-hover table-sm align-middle bank-table bank-pools-table">
                <thead>
                    <tr>
                        <th class="bank-table__num">№</th>
                        <th>Пул</th>
                        <th>Сеть</th>
                        <th>Object</th>
                        <th class="text-end">Balance</th>
                        <th class="text-end">APY</th>
                        <th class="text-end">Risk</th>
                        <th>Статус</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pools as $pool)
                        <tr class="bank-table-row--clickable"
                            data-pool-edit
                            data-action="{{ $pool->update_action }}"
                            data-name="{{ $pool->name }}"
                            data-network="{{ $pool->network }}"
                            data-package-id="{{ $pool->package_id }}"
                            data-pool-object-id="{{ $pool->pool_object_id }}"
                            data-coin-type="{{ $pool->coin_type }}"
                            data-symbol="{{ $pool->symbol }}"
                            data-balance="{{ number_format((float) $pool->balance, 8, '.', '') }}"
                            data-description="{{ $pool->description }}"
                            data-risk-level="{{ $pool->risk_level }}"
                            data-target-apy-bps="{{ $pool->target_apy_bps }}"
                            data-realized-apy-bps="{{ $pool->realized_apy_bps }}"
                            data-min-deposit-usdc="{{ $pool->min_deposit_usdc }}"
                            data-min-av8-balance="{{ $pool->min_av8_balance }}"
                            data-max-weight-bps="{{ $pool->max_weight_bps }}"
                            data-logo-url="{{ $pool->logo_url }}"
                            data-notes="{{ $pool->notes ?? '' }}"
                            data-active="{{ $pool->active ? '1' : '0' }}"
                            data-default-deposit="{{ $pool->is_default_deposit ? '1' : '0' }}">
                            <td class="bank-table__num bank-mono">{{ $loop->iteration }}</td>
                            <td>
                                <strong>{{ $pool->name }}</strong>
                                <div class="bank-meta">{{ $pool->symbol ?: '—' }} · {{ $pool->coin_type ?: 'coin type не указан' }}</div>
                            </td>
                            <td><span class="bank-pill bank-pill--currency">{{ $pool->network ?: '—' }}</span></td>
                            <td class="bank-mono" title="{{ $pool->pool_object_id }}">{{ $pool->pool_object_short ?: '—' }}</td>
                            <td class="text-end fw-semibold">
                                {{ $formatMoney($pool->balance) }} {{ $pool->symbol ?: 'USDC' }}
                                <div class="bank-meta">on-chain {{ $formatMoney($pool->balance_usdc) }} USDC</div>
                            </td>
                            <td class="text-end">{{ $formatBps($pool->apy_bps) }}</td>
                            <td class="text-end bank-mono">{{ $pool->risk_level }}</td>
                            <td>
                                <span class="bank-status {{ $pool->active ? '' : 'bank-status--reversed' }}">{{ $pool->active ? 'active' : 'paused' }}</span>
                                @if($pool->is_default_deposit)
                                    <div class="bank-meta">default deposit</div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">Пулы пока не созданы.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <div class="bank-modal" data-pool-modal hidden>
        <div class="bank-modal__backdrop" data-pool-close></div>
        <div class="bank-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="poolModalTitle">
            <div class="bank-modal__header">
                <div>
                    <div class="bank-label">Пулы</div>
                    <h2 id="poolModalTitle" data-pool-title>Создать пул</h2>
                    <div class="bank-meta">Параметры записи fund_pools.</div>
                </div>
                <button type="button" class="bank-modal__close" data-pool-close aria-label="Закрыть">×</button>
            </div>
            <form method="POST" action="{{ route('bank.pools.store') }}" class="bank-requisites-form" data-pool-form>
                @csrf
                <input type="hidden" name="_method" value="PUT" data-pool-method disabled>
                <div class="bank-form-grid">
                    <label>
                        <span>Название</span>
                        <input type="text" name="name" maxlength="120" required data-pool-name>
                    </label>
                    <label>
                        <span>Symbol</span>
                        <input type="text" name="symbol" maxlength="32" required value="USDC" data-pool-symbol>
                    </label>
                    <label>
                        <span>Balance</span>
                        <input type="text" name="balance" value="0" inputmode="decimal" required data-pool-balance>
                    </label>
                    <label>
                        <span>Network</span>
                        <input type="text" name="network" maxlength="40" value="testnet" data-pool-network>
                    </label>
                    <label>
                        <span>Risk level</span>
                        <input type="number" name="risk_level" min="1" max="10" step="1" value="1" data-pool-risk-level>
                    </label>
                    <label class="bank-form-full">
                        <span>Coin type</span>
                        <input type="text" name="coin_type" maxlength="500" placeholder="0x...::module::COIN" data-pool-coin-type>
                    </label>
                    <label class="bank-form-full">
                        <span>Pool object id</span>
                        <input type="text" name="pool_object_id" maxlength="80" placeholder="0x... или оставить пустым для internal-id" data-pool-object-id>
                    </label>
                    <label class="bank-form-full">
                        <span>Package id</span>
                        <input type="text" name="package_id" maxlength="80" data-pool-package-id>
                    </label>
                    <label>
                        <span>Target APY bps</span>
                        <input type="number" name="target_apy_bps" min="0" max="65535" step="1" value="0" data-pool-target-apy-bps>
                    </label>
                    <label>
                        <span>Realized APY bps</span>
                        <input type="number" name="realized_apy_bps" min="0" max="65535" step="1" value="0" data-pool-realized-apy-bps>
                    </label>
                    <label>
                        <span>Min deposit USDC</span>
                        <input type="text" name="min_deposit_usdc" value="0" data-pool-min-deposit-usdc>
                    </label>
                    <label>
                        <span>Min AV8 balance</span>
                        <input type="text" name="min_av8_balance" value="0" data-pool-min-av8-balance>
                    </label>
                    <label>
                        <span>Max weight bps</span>
                        <input type="number" name="max_weight_bps" min="0" max="10000" step="1" value="10000" data-pool-max-weight-bps>
                    </label>
                    <label>
                        <span>Logo URL</span>
                        <input type="text" name="logo_url" maxlength="500" data-pool-logo-url>
                    </label>
                    <label class="bank-form-full">
                        <span>Описание</span>
                        <textarea name="description" rows="3" data-pool-description></textarea>
                    </label>
                    <label class="bank-form-full">
                        <span>Notes</span>
                        <textarea name="notes" rows="3" data-pool-notes></textarea>
                    </label>
                    <label class="bank-checkbox-field">
                        <input type="checkbox" name="active" value="1" checked data-pool-active>
                        <span>Active</span>
                    </label>
                    <label class="bank-checkbox-field">
                        <input type="checkbox" name="is_default_deposit" value="1" data-pool-default-deposit>
                        <span>Default deposit</span>
                    </label>
                </div>
                <div class="bank-modal__actions">
                    <button type="button" class="btn btn-secondary" data-pool-close>Отмена</button>
                    <button type="submit" class="btn btn-primary" data-pool-submit>Создать</button>
                </div>
            </form>
        </div>
    </div>
</div>

@include('bank.partials.styles')

<style>
    .bank-pools-table {
        table-layout: fixed;
        min-width: 1040px;
    }

    .bank-pools-table th,
    .bank-pools-table td {
        vertical-align: middle;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .bank-pools-table th:nth-child(1),
    .bank-pools-table td:nth-child(1) { width: 48px; }
    .bank-pools-table th:nth-child(2),
    .bank-pools-table td:nth-child(2) { width: 300px; }
    .bank-pools-table th:nth-child(3),
    .bank-pools-table td:nth-child(3) { width: 110px; }
    .bank-pools-table th:nth-child(4),
    .bank-pools-table td:nth-child(4) { width: 160px; }
    .bank-pools-table th:nth-child(5),
    .bank-pools-table td:nth-child(5),
    .bank-pools-table th:nth-child(6),
    .bank-pools-table td:nth-child(6),
    .bank-pools-table th:nth-child(7),
    .bank-pools-table td:nth-child(7) { width: 120px; }
    .bank-pools-table th:nth-child(8),
    .bank-pools-table td:nth-child(8) { width: 140px; }
</style>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const root = document.querySelector('[data-bank-pools-page]');
        if (!root) {
            return;
        }

        const modal = root.querySelector('[data-pool-modal]');
        const form = root.querySelector('[data-pool-form]');
        const method = root.querySelector('[data-pool-method]');
        const title = root.querySelector('[data-pool-title]');
        const submit = root.querySelector('[data-pool-submit]');
        const storeAction = form ? form.action : '';
        const fields = {
            name: root.querySelector('[data-pool-name]'),
            symbol: root.querySelector('[data-pool-symbol]'),
            balance: root.querySelector('[data-pool-balance]'),
            network: root.querySelector('[data-pool-network]'),
            packageId: root.querySelector('[data-pool-package-id]'),
            poolObjectId: root.querySelector('[data-pool-object-id]'),
            coinType: root.querySelector('[data-pool-coin-type]'),
            description: root.querySelector('[data-pool-description]'),
            riskLevel: root.querySelector('[data-pool-risk-level]'),
            targetApyBps: root.querySelector('[data-pool-target-apy-bps]'),
            realizedApyBps: root.querySelector('[data-pool-realized-apy-bps]'),
            minDepositUsdc: root.querySelector('[data-pool-min-deposit-usdc]'),
            minAv8Balance: root.querySelector('[data-pool-min-av8-balance]'),
            maxWeightBps: root.querySelector('[data-pool-max-weight-bps]'),
            logoUrl: root.querySelector('[data-pool-logo-url]'),
            notes: root.querySelector('[data-pool-notes]'),
            active: root.querySelector('[data-pool-active]'),
            defaultDeposit: root.querySelector('[data-pool-default-deposit]'),
        };

        function openModal() {
            if (modal) {
                modal.hidden = false;
            }
            fields.name?.focus();
        }

        root.querySelectorAll('[data-pool-open]').forEach((button) => {
            button.addEventListener('click', () => {
                form?.reset();
                if (form) {
                    form.action = storeAction;
                }
                if (method) {
                    method.disabled = true;
                }
                if (title) {
                    title.textContent = 'Создать пул';
                }
                if (submit) {
                    submit.textContent = 'Создать';
                }
                if (fields.network) fields.network.value = 'testnet';
                if (fields.symbol) fields.symbol.value = 'USDC';
                if (fields.balance) fields.balance.value = '0';
                if (fields.riskLevel) fields.riskLevel.value = '1';
                if (fields.targetApyBps) fields.targetApyBps.value = '0';
                if (fields.realizedApyBps) fields.realizedApyBps.value = '0';
                if (fields.minDepositUsdc) fields.minDepositUsdc.value = '0';
                if (fields.minAv8Balance) fields.minAv8Balance.value = '0';
                if (fields.maxWeightBps) fields.maxWeightBps.value = '10000';
                if (fields.active) fields.active.checked = true;
                if (fields.defaultDeposit) fields.defaultDeposit.checked = false;
                openModal();
            });
        });

        root.querySelectorAll('[data-pool-edit]').forEach((row) => {
            row.addEventListener('click', () => {
                if (!form) {
                    return;
                }
                form.action = row.dataset.action || storeAction;
                if (method) {
                    method.disabled = false;
                }
                if (title) {
                    title.textContent = 'Редактировать пул';
                }
                if (submit) {
                    submit.textContent = 'Сохранить';
                }
                if (fields.name) fields.name.value = row.dataset.name || '';
                if (fields.symbol) fields.symbol.value = row.dataset.symbol || 'USDC';
                if (fields.balance) fields.balance.value = row.dataset.balance || '0';
                if (fields.network) fields.network.value = row.dataset.network || 'testnet';
                if (fields.packageId) fields.packageId.value = row.dataset.packageId || '';
                if (fields.poolObjectId) fields.poolObjectId.value = row.dataset.poolObjectId || '';
                if (fields.coinType) fields.coinType.value = row.dataset.coinType || '';
                if (fields.description) fields.description.value = row.dataset.description || '';
                if (fields.riskLevel) fields.riskLevel.value = row.dataset.riskLevel || '1';
                if (fields.targetApyBps) fields.targetApyBps.value = row.dataset.targetApyBps || '0';
                if (fields.realizedApyBps) fields.realizedApyBps.value = row.dataset.realizedApyBps || '0';
                if (fields.minDepositUsdc) fields.minDepositUsdc.value = row.dataset.minDepositUsdc || '0';
                if (fields.minAv8Balance) fields.minAv8Balance.value = row.dataset.minAv8Balance || '0';
                if (fields.maxWeightBps) fields.maxWeightBps.value = row.dataset.maxWeightBps || '10000';
                if (fields.logoUrl) fields.logoUrl.value = row.dataset.logoUrl || '';
                if (fields.notes) fields.notes.value = row.dataset.notes || '';
                if (fields.active) fields.active.checked = row.dataset.active === '1';
                if (fields.defaultDeposit) fields.defaultDeposit.checked = row.dataset.defaultDeposit === '1';
                openModal();
            });
        });

        root.querySelectorAll('[data-pool-close]').forEach((button) => {
            button.addEventListener('click', () => {
                if (modal) {
                    modal.hidden = true;
                }
            });
        });
    });
</script>
@endpush
