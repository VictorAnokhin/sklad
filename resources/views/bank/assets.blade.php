@extends('home')

@section('title')
Активы
@endsection

@section('content')
@php
    $formatMoney = static fn ($value): string => number_format((float) $value, 2, '.', ' ');
    $formatPercent = static fn ($value): string => number_format((float) $value, 2, '.', ' ');
    $assetTypeLabels = [
        'token' => 'Токен',
        'nft' => 'NFT',
        'pool' => 'Пул',
        'defi' => 'DeFi',
    ];
    $portfolioValueUsd = (float) ($summary['value_usd'] ?? 0);
    $assetChartByKey = collect($assetValueChartRows ?? [])->keyBy('asset_key');
@endphp

<div class="bank-page bank-invest-page" data-bank-assets-page>
    @include('bank.partials.invest_nav')

    <section class="bank-grid bank-grid--summary">
        <div class="bank-panel bank-panel--accent">
            <div class="bank-label">Активы</div>
            <div class="bank-value">{{ $summary['assets'] }}</div>
            <div class="bank-meta">Активы, введенные вручную.</div>
        </div>
        <div class="bank-panel">
            <div class="bank-label">Токены</div>
            <div class="bank-value">{{ $summary['tokens'] }}</div>
            <div class="bank-meta">Ручные токены инвестиционного реестра.</div>
        </div>
        <div class="bank-panel">
            <div class="bank-label">Пулы</div>
            <div class="bank-value">{{ $summary['pools'] }}</div>
            <div class="bank-meta">Ручные пулы инвестиционного реестра.</div>
        </div>
        <div class="bank-panel">
            <div class="bank-label">Стоимость</div>
            <div class="bank-value">{{ $formatMoney($summary['value_usd']) }}</div>
            <div class="bank-meta">Итоговая стоимость введенных активов.</div>
        </div>
    </section>

    <section class="bank-panel bank-asset-chart-panel" data-asset-value-chart>
        <div class="bank-table-header">
            <div>
                <div class="bank-label">График стоимости</div>
                <div class="bank-meta">Изменение стоимости выбранного актива по операциям покупки, продажи и переоценки.</div>
            </div>
            <label class="bank-asset-chart-select">
                <span>Актив</span>
                <select data-asset-chart-select>
                    @forelse(($assetValueChartRows ?? collect()) as $assetChart)
                        <option value="{{ $assetChart['asset_key'] }}">{{ $assetChart['name'] }} · {{ $assetTypeLabels[$assetChart['asset_type']] ?? $assetChart['asset_type'] }}</option>
                    @empty
                        <option value="">Нет данных</option>
                    @endforelse
                </select>
            </label>
        </div>
        <div class="bank-asset-chart-summary">
            <div>
                <div class="bank-label">Текущая стоимость</div>
                <div class="bank-value" data-asset-chart-current>—</div>
            </div>
            <div>
                <div class="bank-label">Минимум</div>
                <div class="bank-value" data-asset-chart-min>—</div>
            </div>
            <div>
                <div class="bank-label">Максимум</div>
                <div class="bank-value" data-asset-chart-max>—</div>
            </div>
        </div>
        <div class="bank-asset-chart-canvas">
            <svg viewBox="0 0 720 240" role="img" aria-label="График стоимости актива" preserveAspectRatio="none">
                <line x1="42" y1="18" x2="42" y2="205" class="bank-asset-chart-axis" />
                <line x1="42" y1="205" x2="700" y2="205" class="bank-asset-chart-axis" />
                <polyline points="" class="bank-asset-chart-line" data-asset-chart-line />
                <g data-asset-chart-points></g>
            </svg>
            <div class="bank-asset-chart-empty" data-asset-chart-empty hidden>Недостаточно точек для графика.</div>
        </div>
        <div class="table-responsive bank-table-scroll bank-asset-chart-history">
            <table class="table table-dark table-hover table-sm align-middle bank-table">
                <thead>
                    <tr>
                        <th>Дата</th>
                        <th>Операция</th>
                        <th class="text-end">Стоимость после операции</th>
                    </tr>
                </thead>
                <tbody data-asset-chart-history>
                    <tr><td colspan="3" class="text-center text-muted py-3">Нет данных.</td></tr>
                </tbody>
            </table>
        </div>
    </section>

    <section class="bank-panel bank-table-panel">
        <div class="bank-table-header">
            <div>
                <div class="bank-label">Введенные активы</div>
                <div class="bank-meta">Активы, созданные вручную в инвестиционном реестре.</div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <div class="bank-meta">{{ $fixedAssetRows->count() }} записей</div>
                <button type="button" class="btn btn-sm btn-primary" data-invest-asset-open>Создать</button>
            </div>
        </div>
        <div class="table-responsive bank-table-scroll">
            <table class="table table-dark table-hover table-sm align-middle bank-table bank-assets-table">
                <colgroup>
                    <col class="bank-assets-table__col-num">
                    <col class="bank-assets-table__col-type">
                    <col class="bank-assets-table__col-date">
                    <col class="bank-assets-table__col-address">
                    <col class="bank-assets-table__col-name">
                    <col class="bank-assets-table__col-number">
                    <col class="bank-assets-table__col-price">
                    <col class="bank-assets-table__col-money">
                    <col class="bank-assets-table__col-change">
                    <col class="bank-assets-table__col-percent">
                    <col class="bank-assets-table__col-actions">
                </colgroup>
                <thead>
                    <tr>
                        <th class="bank-table__num">№</th>
                        <th class="bank-assets-table__type">Тип</th>
                        <th class="bank-assets-table__date">Дата</th>
                        <th class="bank-assets-table__address">Адрес объекта</th>
                        <th class="bank-assets-table__name">Наименование</th>
                        <th class="text-end bank-assets-table__number">Количество</th>
                        <th class="text-end bank-assets-table__price">Цена</th>
                        <th class="text-end bank-assets-table__money">Стоимость</th>
                        <th class="text-end bank-assets-table__change">Изм.</th>
                        <th class="text-end bank-assets-table__percent">%</th>
                        <th class="bank-assets-table__actions"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($fixedAssetRows as $asset)
                        @php
                            $assetQuantity = (float) $asset->quantity;
                            $assetValue = (float) $asset->value_usd;
                            $assetReferencePrice = $assetQuantity > 0 ? $assetValue / $assetQuantity : 0.0;
                            $assetPortfolioShare = $portfolioValueUsd > 0 ? $assetValue / $portfolioValueUsd * 100 : 0.0;
                            $assetChart = $assetChartByKey->get($asset->asset_key);
                            $assetPoints = collect($assetChart['points'] ?? []);
                            $firstAssetValue = (float) ($assetPoints->first()['value'] ?? $assetValue);
                            $assetChange = $assetValue - $firstAssetValue;
                            $assetChangePercent = abs($firstAssetValue) > 0.000001 ? ($assetChange / $firstAssetValue) * 100 : 0.0;
                            $assetChangeClass = $assetChange > 0.000001 ? 'text-success' : ($assetChange < -0.000001 ? 'text-danger' : 'text-muted');
                            $assetChangeSign = $assetChange > 0.000001 ? '+' : '';
                        @endphp
                        <tr class="bank-table-row--clickable"
                            data-invest-asset-edit
                            data-action="{{ $asset->update_action }}"
                            data-asset-type="{{ $asset->asset_type }}"
                            data-asset-address="{{ $asset->object_address }}"
                            data-asset-name="{{ $asset->name }}"
                            data-asset-quantity="{{ number_format((float) $asset->quantity, 8, '.', '') }}"
                            data-asset-value="{{ number_format((float) $asset->value_usd, 8, '.', '') }}"
                            data-asset-created-on="{{ $asset->created_on }}">
                            <td class="bank-table__num bank-mono">{{ $loop->iteration }}</td>
                            <td class="bank-assets-table__type"><span class="bank-pill {{ $asset->asset_type === 'pool' ? 'bank-pill--company' : 'bank-pill--currency' }}">{{ $assetTypeLabels[$asset->asset_type] ?? $asset->asset_type }}</span></td>
                            <td class="bank-assets-table__date">{{ $asset->created_on !== '' ? $asset->created_on : '—' }}</td>
                            <td class="bank-mono bank-assets-table__address" title="{{ $asset->object_address }}">{{ $asset->object_short }}</td>
                            <td class="bank-assets-table__name">
                                <strong>{{ $asset->name }}</strong>
                                <div class="bank-meta">{{ $asset->currency }}</div>
                            </td>
                            <td class="text-end bank-mono bank-assets-table__number">{{ number_format((float) $asset->quantity, 8, '.', ' ') }}</td>
                            <td class="text-end bank-assets-table__price">
                                {{ $assetQuantity > 0 ? $formatMoney($assetReferencePrice) : '—' }}
                            </td>
                            <td class="text-end bank-assets-table__money">
                                <div class="fw-semibold">{{ $formatMoney($assetValue) }}</div>
                                <div class="bank-meta">{{ $asset->currency }}</div>
                            </td>
                            <td class="text-end bank-assets-table__change">
                                <div class="fw-semibold {{ $assetChangeClass }}">{{ $assetChangeSign }}{{ $formatPercent($assetChangePercent) }}%</div>
                                <div class="bank-meta {{ $assetChangeClass }}">{{ $assetChangeSign }}{{ $formatMoney($assetChange) }}</div>
                            </td>
                            <td class="text-end bank-mono bank-assets-table__percent">{{ $formatPercent($assetPortfolioShare) }}%</td>
                            <td class="text-end bank-assets-table__actions">
                                <form method="POST"
                                      action="{{ $asset->destroy_action }}"
                                      class="bank-assets-delete-form"
                                      onsubmit="event.stopPropagation(); return confirm('Удалить актив?');"
                                      onclick="event.stopPropagation();">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="bank-assets-delete-button" aria-label="Удалить актив">&times;</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="text-center text-muted py-4">Введенные активы пока не созданы.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <div class="bank-modal" data-invest-asset-modal hidden>
        <div class="bank-modal__backdrop" data-invest-asset-close></div>
        <div class="bank-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="investAssetModalTitle">
            <div class="bank-modal__header">
                <div>
                    <div class="bank-label">Активы</div>
                    <h2 id="investAssetModalTitle" data-invest-asset-title>Создать актив</h2>
                    <div class="bank-meta" data-invest-asset-subtitle>Ручная фиксация инвестиционного актива для распределения средств со счетов.</div>
                </div>
                <button type="button" class="bank-modal__close" data-invest-asset-close aria-label="Закрыть">×</button>
            </div>
            <form method="POST" action="{{ route('bank.invest-assets.store') }}" class="bank-requisites-form" data-invest-asset-form>
                @csrf
                <input type="hidden" name="_method" value="PUT" data-invest-asset-method disabled>
                <div class="bank-form-grid">
                    <label>
                        <span>Тип актива</span>
                        <select name="asset_type" required data-invest-asset-type>
                            <option value="token">Токен</option>
                            <option value="pool">Пул</option>
                        </select>
                    </label>
                    <label>
                        <span>Наименование</span>
                        <input type="text" name="name" maxlength="160" required placeholder="USDC / AV8 Pool" data-invest-asset-name>
                    </label>
                    <label>
                        <span>Дата фиксации</span>
                        <input type="date" name="created_on" data-invest-asset-created-on>
                    </label>
                    <label class="bank-form-full">
                        <span>Адрес объекта</span>
                        <input type="text" name="asset_address" maxlength="190" required placeholder="0x..., mint, pool object id" data-invest-asset-address>
                    </label>
                    <label>
                        <span>Количество</span>
                        <input type="number" name="quantity" min="0" step="0.00000001" inputmode="decimal" data-invest-asset-quantity>
                    </label>
                    <label>
                        <span>Стоимость</span>
                        <input type="text" name="value_usd" inputmode="numeric" data-terminal-amount data-invest-asset-value>
                        <small class="bank-field-hint" data-invest-asset-price-reference>Цена: —</small>
                    </label>
                </div>
                <div class="bank-modal__actions">
                    <button type="button" class="btn btn-secondary" data-invest-asset-close>Отмена</button>
                    <button type="submit" class="btn btn-primary" data-invest-asset-submit>Создать</button>
                </div>
            </form>
        </div>
    </div>
</div>

@include('bank.partials.styles')
@include('bank.partials.terminal_amount_inputs')

<style>
    .bank-asset-chart-panel {
        margin-bottom: 18px;
    }

    .bank-asset-chart-select {
        display: grid;
        gap: 5px;
        min-width: min(360px, 100%);
        color: rgba(226, 232, 240, 0.8);
        font-size: 12px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.1em;
    }

    .bank-asset-chart-select select {
        min-height: 38px;
        border: 1px solid rgba(148, 163, 184, 0.22);
        border-radius: 8px;
        background: rgba(15, 23, 42, 0.82);
        color: #e2e8f0;
        padding: 0 10px;
        text-transform: none;
        letter-spacing: 0;
        font-weight: 700;
    }

    .bank-asset-chart-summary {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 12px;
        margin: 14px 0;
    }

    .bank-asset-chart-summary > div {
        border: 1px solid rgba(148, 163, 184, 0.16);
        border-radius: 8px;
        padding: 12px;
        background: rgba(15, 23, 42, 0.42);
    }

    .bank-asset-chart-canvas {
        position: relative;
        height: 260px;
        border: 1px solid rgba(148, 163, 184, 0.16);
        border-radius: 8px;
        background: linear-gradient(180deg, rgba(15, 23, 42, 0.76), rgba(2, 6, 23, 0.42));
        overflow: hidden;
    }

    .bank-asset-chart-canvas svg {
        display: block;
        width: 100%;
        height: 100%;
    }

    .bank-asset-chart-axis {
        stroke: rgba(148, 163, 184, 0.34);
        stroke-width: 1;
    }

    .bank-asset-chart-line {
        fill: none;
        stroke: #38bdf8;
        stroke-width: 3;
        stroke-linecap: round;
        stroke-linejoin: round;
    }

    .bank-asset-chart-point {
        fill: #e2e8f0;
        stroke: #38bdf8;
        stroke-width: 2;
    }

    .bank-asset-chart-empty {
        position: absolute;
        inset: 0;
        display: grid;
        place-items: center;
        color: rgba(148, 163, 184, 0.92);
        font-weight: 700;
    }

    .bank-asset-chart-history {
        margin-top: 12px;
    }

    .bank-assets-table {
        table-layout: fixed;
        min-width: 1510px;
    }

    .bank-assets-table th,
    .bank-assets-table td {
        vertical-align: middle;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .bank-assets-table__col-num,
    .bank-assets-table .bank-table__num { width: 48px; }
    .bank-assets-table__col-type,
    .bank-assets-table__type { width: 60px; }
    .bank-assets-table__col-date,
    .bank-assets-table__date { width: 120px; }
    .bank-assets-table__col-address,
    .bank-assets-table__address { width: 160px; }
    .bank-assets-table__col-name,
    .bank-assets-table__name { width: 340px; }
    .bank-assets-table__col-number,
    .bank-assets-table__number { width: 130px; }
    .bank-assets-table__col-price,
    .bank-assets-table__price { width: 120px; }
    .bank-assets-table__col-money,
    .bank-assets-table__money { width: 130px; }
    .bank-assets-table__col-change,
    .bank-assets-table__change { width: 130px; }
    .bank-assets-table__col-percent,
    .bank-assets-table__percent { width: 70px; }
    .bank-assets-table__col-actions,
    .bank-assets-table__actions { width: 44px; }
    .bank-assets-table__name .bank-meta { overflow: hidden; text-overflow: ellipsis; }

    .bank-assets-table__money .bank-meta {
        margin-top: 2px;
        line-height: 1.15;
    }

    .bank-assets-delete-form {
        display: inline-flex;
        margin: 0;
    }

    .bank-assets-delete-button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 28px;
        height: 28px;
        border: 0;
        border-radius: 6px;
        background: rgba(239, 68, 68, 0.14);
        color: #fca5a5;
        font-size: 22px;
        font-weight: 700;
        line-height: 1;
    }

    .bank-assets-delete-button:hover {
        background: rgba(239, 68, 68, 0.26);
        color: #fff;
    }
</style>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const root = document.querySelector('[data-bank-assets-page]');
        if (!root) {
            return;
        }

        const modal = root.querySelector('[data-invest-asset-modal]');
        const form = root.querySelector('[data-invest-asset-form]');
        const method = root.querySelector('[data-invest-asset-method]');
        const title = root.querySelector('[data-invest-asset-title]');
        const subtitle = root.querySelector('[data-invest-asset-subtitle]');
        const submit = root.querySelector('[data-invest-asset-submit]');
        const type = root.querySelector('[data-invest-asset-type]');
        const address = root.querySelector('[data-invest-asset-address]');
        const name = root.querySelector('[data-invest-asset-name]');
        const createdOn = root.querySelector('[data-invest-asset-created-on]');
        const quantity = root.querySelector('[data-invest-asset-quantity]');
        const value = root.querySelector('[data-invest-asset-value]');
        const priceReference = root.querySelector('[data-invest-asset-price-reference]');
        const storeAction = form ? form.action : '';

        function parseDecimal(input) {
            const normalized = String(input || '').replace(/\s/g, '').replace(',', '.');
            const number = Number.parseFloat(normalized);
            return Number.isFinite(number) ? number : 0;
        }

        function formatReferencePrice(price) {
            return price.toLocaleString('ru-RU', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 8,
            });
        }

        function syncReferencePrice() {
            if (!priceReference) {
                return;
            }
            const quantityValue = parseDecimal(quantity?.value);
            const valueUsd = parseDecimal(value?.value);
            priceReference.textContent = quantityValue > 0
                ? `Цена: ${formatReferencePrice(valueUsd / quantityValue)}`
                : 'Цена: —';
        }

        function setupAssetValueChart() {
            const chartRoot = root.querySelector('[data-asset-value-chart]');
            if (!chartRoot) {
                return;
            }

            const rows = @json(($assetValueChartRows ?? collect())->values());
            const byKey = new Map(rows.map((row) => [row.asset_key, row]));
            const select = chartRoot.querySelector('[data-asset-chart-select]');
            const line = chartRoot.querySelector('[data-asset-chart-line]');
            const pointsLayer = chartRoot.querySelector('[data-asset-chart-points]');
            const empty = chartRoot.querySelector('[data-asset-chart-empty]');
            const history = chartRoot.querySelector('[data-asset-chart-history]');
            const current = chartRoot.querySelector('[data-asset-chart-current]');
            const min = chartRoot.querySelector('[data-asset-chart-min]');
            const max = chartRoot.querySelector('[data-asset-chart-max]');

            const formatMoney = (amount) => `${Number(amount || 0).toLocaleString('ru-RU', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            })} USD`;
            const directionLabel = (direction) => ({
                account_to_asset: 'Покупка',
                asset_to_account: 'Продажа',
                revaluation: 'Переоценка',
                manual: 'Ручная фиксация',
            })[direction] || direction || 'Операция';

            const render = (assetKey) => {
                const row = byKey.get(assetKey) || rows[0];
                const chartPoints = row?.points || [];
                if (current) current.textContent = row ? formatMoney(row.current_value) : '—';
                if (min) min.textContent = row ? formatMoney(row.min_value) : '—';
                if (max) max.textContent = row ? formatMoney(row.max_value) : '—';

                if (!row || chartPoints.length === 0) {
                    line?.setAttribute('points', '');
                    if (pointsLayer) pointsLayer.innerHTML = '';
                    if (empty) empty.hidden = false;
                    if (history) history.innerHTML = '<tr><td colspan="3" class="text-center text-muted py-3">Нет данных.</td></tr>';
                    return;
                }

                const values = chartPoints.map((point) => Number(point.value || 0));
                const minValue = Math.min(...values);
                const maxValue = Math.max(...values);
                const range = Math.max(maxValue - minValue, 1);
                const width = 658;
                const height = 178;
                const startX = 42;
                const startY = 205;
                const step = chartPoints.length > 1 ? width / (chartPoints.length - 1) : 0;
                const svgPoints = chartPoints.map((point, index) => {
                    const x = chartPoints.length > 1 ? startX + (step * index) : startX + width;
                    const y = startY - ((Number(point.value || 0) - minValue) / range * height);
                    return { x, y, point };
                });

                line?.setAttribute('points', svgPoints.map((item) => `${item.x.toFixed(2)},${item.y.toFixed(2)}`).join(' '));
                if (pointsLayer) {
                    pointsLayer.innerHTML = svgPoints.map((item) => (
                        `<circle class="bank-asset-chart-point" cx="${item.x.toFixed(2)}" cy="${item.y.toFixed(2)}" r="4"><title>${item.point.label}: ${formatMoney(item.point.value)}</title></circle>`
                    )).join('');
                }
                if (empty) empty.hidden = chartPoints.length > 1;
                if (history) {
                    history.innerHTML = chartPoints.slice().reverse().map((point) => (
                        `<tr><td>${point.label || point.date || '—'}</td><td>${directionLabel(point.direction)}</td><td class="text-end fw-semibold">${formatMoney(point.value)}</td></tr>`
                    )).join('');
                }
            };

            select?.addEventListener('change', () => render(select.value));
            render(select?.value || '');
        }

        [quantity, value].forEach((field) => field?.addEventListener('input', syncReferencePrice));
        setupAssetValueChart();

        root.querySelectorAll('[data-invest-asset-open]').forEach((button) => {
            button.addEventListener('click', () => {
                form?.reset();
                if (form) {
                    form.action = storeAction;
                }
                if (method) {
                    method.disabled = true;
                }
                if (title) {
                    title.textContent = 'Создать актив';
                }
                if (subtitle) {
                    subtitle.textContent = 'Ручная фиксация инвестиционного актива для распределения средств со счетов.';
                }
                if (submit) {
                    submit.textContent = 'Создать';
                }
                if (createdOn) {
                    createdOn.value = new Date().toISOString().slice(0, 10);
                }
                syncReferencePrice();
                if (modal) {
                    modal.hidden = false;
                }
                address?.focus();
            });
        });

        root.querySelectorAll('[data-invest-asset-edit]').forEach((row) => {
            row.addEventListener('click', () => {
                if (!modal || !form) {
                    return;
                }
                form.action = row.dataset.action || storeAction;
                if (method) {
                    method.disabled = false;
                }
                if (type) {
                    type.value = row.dataset.assetType || 'token';
                }
                if (address) {
                    address.value = row.dataset.assetAddress || '';
                }
                if (name) {
                    name.value = row.dataset.assetName || '';
                }
                if (createdOn) {
                    createdOn.value = row.dataset.assetCreatedOn || '';
                }
                if (quantity) {
                    quantity.value = row.dataset.assetQuantity || '';
                }
                if (value) {
                    value.value = row.dataset.assetValue || '';
                }
                syncReferencePrice();
                if (title) {
                    title.textContent = 'Редактировать актив';
                }
                if (subtitle) {
                    subtitle.textContent = 'Изменение ручной записи инвестиционного актива.';
                }
                if (submit) {
                    submit.textContent = 'Сохранить';
                }
                modal.hidden = false;
                address?.focus();
            });
        });

        root.querySelectorAll('[data-invest-asset-close]').forEach((button) => {
            button.addEventListener('click', () => {
                if (modal) {
                    modal.hidden = true;
                }
            });
        });
    });
</script>
@endpush
