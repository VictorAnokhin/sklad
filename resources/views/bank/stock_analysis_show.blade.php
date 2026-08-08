@extends('home')

@section('title')
Анализ акции
@endsection

@section('content')
@php
    $stockValue = fn (string $field) => (string) ($selectedPayload[$field] ?? $stock->{$field} ?? '');
    $change = trim($stockValue('change_percent'));
    $changeClass = str_starts_with($change, '-')
        ? 'is-negative'
        : ($change !== '' && $change !== '0' && $change !== '0%' ? 'is-positive' : 'is-neutral');
    $identity = array_filter([
        trim($stockValue('sector')),
        trim($stockValue('industry')),
        trim($stockValue('country')),
    ]);
    $snapshotData = ($snapshots ?? collect())->map(function ($snapshot) {
        $payload = json_decode((string) ($snapshot->payload ?? '{}'), true);
        $payload = is_array($payload) ? $payload : [];
        return [
            'date' => (string) $snapshot->snapshot_date,
            'price' => (string) ($payload['price'] ?? $snapshot->price ?? ''),
            'change_percent' => (string) ($payload['change_percent'] ?? $snapshot->change_percent ?? ''),
            'volume' => (string) ($payload['volume'] ?? $snapshot->volume ?? ''),
            'payload' => $payload,
            'changed_fields' => json_decode((string) ($snapshot->changed_fields ?? '[]'), true) ?: [],
        ];
    })->values();
    if ($snapshotData->isEmpty()) {
        $snapshotData = collect([[
            'date' => now()->toDateString(),
            'price' => $stockValue('price'),
            'change_percent' => $change,
            'volume' => $stockValue('volume'),
            'payload' => $selectedPayload,
            'changed_fields' => [],
        ]]);
    }
    $numericPrices = $snapshotData
        ->map(fn ($point) => (float) preg_replace('/[^0-9.\-]/', '', (string) ($point['price'] ?? '')))
        ->filter(fn ($price) => $price > 0)
        ->values();
    $minPrice = $numericPrices->isNotEmpty() ? (float) $numericPrices->min() : 0.0;
    $maxPrice = $numericPrices->isNotEmpty() ? (float) $numericPrices->max() : 1.0;
    if (abs($maxPrice - $minPrice) < 0.0001) {
        $minPrice = max(0, $minPrice - 1);
        $maxPrice += 1;
    }
    $chartPoints = $snapshotData->map(function ($point, $index) use ($snapshotData, $minPrice, $maxPrice) {
        $count = max(1, $snapshotData->count() - 1);
        $price = (float) preg_replace('/[^0-9.\-]/', '', (string) ($point['price'] ?? ''));
        $x = 24 + (($index / $count) * 712);
        $y = 252 - ((max($minPrice, min($maxPrice, $price)) - $minPrice) / ($maxPrice - $minPrice) * 204);

        return $point + ['x' => round($x, 2), 'y' => round($y, 2)];
    })->values();
    $linePath = $chartPoints->map(fn ($point, $index) => ($index === 0 ? 'M' : 'L') . $point['x'] . ' ' . $point['y'])->implode(' ');
    $areaPath = $linePath !== '' ? $linePath . ' L736 280 L24 280 Z' : '';
    $quoteCards = [
        ['Price', $stockValue('price')],
        ['Change', $change ?: '—'],
        ['Volume', $stockValue('volume')],
        ['Market Cap', $stockValue('market_cap') ?: $stockValue('market')],
        ['P/E', $stockValue('pe')],
        ['Dividend', $stockValue('dividend_est') ?: $stockValue('dividend_ttm')],
    ];
    $snapshotRows = [
        [
            ['Index', '—'],
            ['Market Cap', $stockValue('market_cap') ?: $stockValue('market')],
            ['Income', $stockValue('income')],
            ['Sales', $stockValue('sales')],
        ],
        [
            ['Book/sh', $stockValue('book_per_share')],
            ['Cash/sh', $stockValue('cash_per_share')],
            ['Dividend Est.', $stockValue('dividend_est')],
            ['Dividend TTM', $stockValue('dividend_ttm')],
        ],
        [
            ['Dividend Ex-Date', $stockValue('dividend_ex_date')],
            ['Dividend Gr. 3/5Y', $stockValue('dividend_growth_3_5y')],
            ['Payout', $stockValue('payout')],
            ['Employees', $stockValue('employees')],
        ],
        [
            ['P/E', $stockValue('pe')],
            ['Forward P/E', $stockValue('forward_pe')],
            ['PEG', $stockValue('peg')],
            ['P/S', $stockValue('ps')],
        ],
        [
            ['P/B', $stockValue('pb')],
            ['P/C', $stockValue('pc')],
            ['P/FCF', $stockValue('pfcf')],
            ['EV/EBITDA', $stockValue('ev_ebitda')],
        ],
        [
            ['EV/Sales', $stockValue('ev_sales')],
            ['Enterprise Value', $stockValue('enterprise_value')],
            ['Quick Ratio', $stockValue('quick_ratio')],
            ['Current Ratio', $stockValue('current_ratio')],
        ],
        [
            ['Debt/Eq', $stockValue('debt_eq')],
            ['LT Debt/Eq', $stockValue('lt_debt_eq')],
            ['Option/Short', $stockValue('option_short')],
            ['IPO', $stockValue('ipo')],
        ],
        [
            ['EPS (ttm)', $stockValue('eps_ttm')],
            ['EPS next Y', $stockValue('eps_next_y_value')],
            ['EPS next Q', $stockValue('eps_next_q')],
            ['Earnings', $stockValue('earnings')],
        ],
        [
            ['EPS this Y', $stockValue('eps_this_y_growth')],
            ['EPS next Y %', $stockValue('eps_next_y_growth')],
            ['EPS next 5Y', $stockValue('eps_next_5y_growth')],
            ['EPS past 3/5Y', $stockValue('eps_past_3_5y')],
        ],
        [
            ['Sales past 3/5Y', $stockValue('sales_past_3_5y')],
            ['EPS Y/Y TTM', $stockValue('eps_yy_ttm')],
            ['Sales Y/Y TTM', $stockValue('sales_yy_ttm')],
            ['EPS Q/Q', $stockValue('eps_qq')],
        ],
        [
            ['Sales Q/Q', $stockValue('sales_qq')],
            ['Price', $stockValue('price')],
            ['Change', $change ?: '—'],
            ['Volume', $stockValue('volume')],
        ],
    ];
@endphp

<div class="bank-page bank-stock-detail-page">
    @include('bank.partials.invest_nav')

    <section class="bank-stock-detail-header">
        <div>
            <div class="bank-stock-detail-kicker">Анализ акции</div>
            <h1>{{ $stockValue('ticker') }} <span>{{ $stockValue('company') }}</span></h1>
            <div class="bank-stock-detail-meta">{{ $identity ? implode(' · ', $identity) : 'Sector / Industry / Country не указаны' }}</div>
        </div>
        <a class="btn btn-sm btn-outline-light" href="{{ route('bank.stock-analysis') }}">Назад к акциям</a>
    </section>

    <section class="bank-stock-quote-strip">
        @foreach($quoteCards as [$label, $quoteValue])
            <div class="bank-stock-quote-card {{ $label === 'Change' ? $changeClass : '' }}">
                <span>{{ $label }}</span>
                <strong>{{ $quoteValue ?: '—' }}</strong>
            </div>
        @endforeach
    </section>

    <section class="bank-stock-workspace">
        <div class="bank-stock-chart-panel">
            <div class="bank-stock-chart-toolbar">
                <div>
                    <span>Chart</span>
                    <strong>{{ $stockValue('ticker') }}</strong>
                </div>
                <div class="bank-stock-chart-tabs">
                    <span>1D</span>
                    <span>1W</span>
                    <span class="is-active">1M</span>
                    <span>1Y</span>
                </div>
            </div>
            <div class="bank-stock-chart">
                <svg viewBox="0 0 760 300" role="img" aria-label="Price chart">
                    <defs>
                        <linearGradient id="stockChartFill" x1="0" x2="0" y1="0" y2="1">
                            <stop offset="0%" stop-color="#22c55e" stop-opacity="0.32"/>
                            <stop offset="100%" stop-color="#22c55e" stop-opacity="0"/>
                        </linearGradient>
                    </defs>
                    <g class="grid">
                        <line x1="0" y1="60" x2="760" y2="60"/>
                        <line x1="0" y1="120" x2="760" y2="120"/>
                        <line x1="0" y1="180" x2="760" y2="180"/>
                        <line x1="0" y1="240" x2="760" y2="240"/>
                    </g>
                    <path class="area" d="{{ $areaPath }}"/>
                    <path class="line" d="{{ $linePath }}"/>
                    @foreach($chartPoints as $point)
                        <g class="bank-stock-chart-point {{ ($selectedSnapshot?->snapshot_date ?? '') === $point['date'] ? 'is-active' : '' }}"
                           data-stock-snapshot-date="{{ $point['date'] }}"
                           role="button"
                           tabindex="0"
                           aria-label="Показать данные на {{ $point['date'] }}">
                            <circle cx="{{ $point['x'] }}" cy="{{ $point['y'] }}" r="6"/>
                        </g>
                    @endforeach
                </svg>
            </div>
            <div class="bank-stock-chart-foot">
                <span data-stock-chart-date>Date {{ $selectedSnapshot?->snapshot_date ?? $chartPoints->last()['date'] ?? '—' }}</span>
                <span data-stock-chart-price>Price {{ $stockValue('price') ?: '—' }}</span>
                <span data-stock-chart-volume>Volume {{ $stockValue('volume') ?: '—' }}</span>
                <span>Change {{ $change ?: '—' }}</span>
            </div>
        </div>

        <aside class="bank-stock-profile-panel">
            <div class="bank-stock-profile-row">
                <span>Company</span>
                <strong>{{ $stockValue('company') ?: '—' }}</strong>
            </div>
            <div class="bank-stock-profile-row">
                <span>Sector</span>
                <strong>{{ $stockValue('sector') ?: '—' }}</strong>
            </div>
            <div class="bank-stock-profile-row">
                <span>Industry</span>
                <strong>{{ $stockValue('industry') ?: '—' }}</strong>
            </div>
            <div class="bank-stock-profile-row">
                <span>Country</span>
                <strong>{{ $stockValue('country') ?: '—' }}</strong>
            </div>
            <div class="bank-stock-profile-row">
                <span>IPO</span>
                <strong>{{ $stockValue('ipo') ?: '—' }}</strong>
            </div>
            <div class="bank-stock-profile-row">
                <span>Employees</span>
                <strong>{{ $stockValue('employees') ?: '—' }}</strong>
            </div>
        </aside>
    </section>

    <section class="bank-stock-snapshot">
        <div class="bank-stock-snapshot-title">
            <span>Snapshot</span>
            <strong><span data-stock-snapshot-title-date>{{ $selectedSnapshot?->snapshot_date ?? $chartPoints->last()['date'] ?? now()->toDateString() }}</span> · {{ $stockValue('ticker') }} fundamentals</strong>
        </div>
        <div class="bank-stock-snapshot-tabs" role="tablist" aria-label="Snapshot views">
            <button type="button" class="is-active" data-stock-tab="parameters" role="tab" aria-selected="true">Параметры</button>
            <button type="button" data-stock-tab="analysis" role="tab" aria-selected="false">Анализ</button>
        </div>
        <div data-stock-tab-panel="parameters">
            <div class="bank-stock-snapshot-scroll">
                <table class="bank-stock-snapshot-table">
                    <tbody>
                        @foreach($snapshotRows as $row)
                            <tr>
                                @foreach($row as [$label, $rowValue])
                                    <th>{{ $label }}</th>
                                    <td class="{{ $label === 'Change' ? $changeClass : '' }}" data-stock-snapshot-value="{{ $label }}">{{ $rowValue ?: '—' }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <div data-stock-tab-panel="analysis" hidden>
            <div class="bank-stock-analysis-view">
                <div class="bank-stock-analysis-note">
                    <strong>Методика</strong>
                    <span>Мультипликаторы нельзя оценивать в вакууме. Для точного вывода нужны средние по отрасли и главные конкуренты; если таких данных нет в snapshot, вывод ниже использует только базовые ориентиры.</span>
                </div>
                <div class="bank-stock-analysis-grid" data-stock-analysis-results></div>
                <div class="bank-stock-analysis-cheatsheet">
                    <div class="bank-stock-analysis-heading">Сводная таблица-шпаргалка</div>
                    <table>
                        <thead>
                            <tr>
                                <th>Метрика</th>
                                <th>Здоровая норма</th>
                                <th>Красный флаг</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>P/E</td>
                                <td>Ниже среднеотраслевого при хорошем росте</td>
                                <td>Экстремально высокий без оправданного роста</td>
                            </tr>
                            <tr>
                                <td>Net Debt / EBITDA</td>
                                <td>До 2.0-2.5</td>
                                <td>Выше 3.5</td>
                            </tr>
                            <tr>
                                <td>ROE</td>
                                <td>Выше 15%</td>
                                <td>Ниже 5% или отрицательный</td>
                            </tr>
                            <tr>
                                <td>Current Ratio</td>
                                <td>Выше 1.5</td>
                                <td>Ниже 1.0</td>
                            </tr>
                            <tr>
                                <td>Dividend Payout</td>
                                <td>40%-60% от прибыли</td>
                                <td>Выше 100%</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
</div>

@include('bank.partials.styles')

<style>
    .bank-stock-detail-page {
        max-width: 1180px;
    }

    .bank-stock-detail-header,
    .bank-stock-chart-panel,
    .bank-stock-profile-panel,
    .bank-stock-snapshot {
        border: 1px solid rgba(148, 163, 184, 0.2);
        border-radius: 8px;
        background: rgba(15, 23, 42, 0.76);
        box-shadow: 0 12px 30px rgba(0, 0, 0, 0.22);
    }

    .bank-stock-detail-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 14px;
        padding: 16px;
        margin-bottom: 10px;
    }

    .bank-stock-detail-kicker,
    .bank-stock-snapshot-title span,
    .bank-stock-chart-toolbar span,
    .bank-stock-profile-row span {
        color: rgba(148, 163, 184, 0.9);
        font-size: 0.72rem;
        font-weight: 800;
        letter-spacing: 0;
        text-transform: uppercase;
    }

    .bank-stock-detail-header h1 {
        margin: 4px 0;
        color: #f8fafc;
        font-size: 1.55rem;
        line-height: 1.15;
        font-weight: 800;
    }

    .bank-stock-detail-header h1 span {
        color: rgba(226, 232, 240, 0.84);
        font-size: 1rem;
        font-weight: 700;
    }

    .bank-stock-detail-meta {
        color: rgba(203, 213, 225, 0.74);
        font-size: 0.86rem;
    }

    .bank-stock-quote-strip {
        display: grid;
        grid-template-columns: repeat(6, minmax(0, 1fr));
        gap: 8px;
        margin-bottom: 10px;
    }

    .bank-stock-quote-card {
        display: grid;
        gap: 3px;
        min-height: 58px;
        padding: 10px 12px;
        border: 1px solid rgba(148, 163, 184, 0.18);
        border-radius: 8px;
        background: rgba(2, 6, 23, 0.36);
    }

    .bank-stock-quote-card span {
        color: rgba(148, 163, 184, 0.9);
        font-size: 0.72rem;
        font-weight: 700;
        text-transform: uppercase;
    }

    .bank-stock-quote-card strong {
        color: #f8fafc;
        font-size: 1rem;
        overflow-wrap: anywhere;
    }

    .is-positive,
    .is-positive strong {
        color: #22c55e !important;
    }

    .is-negative,
    .is-negative strong {
        color: #f43f5e !important;
    }

    .is-neutral,
    .is-neutral strong {
        color: rgba(226, 232, 240, 0.8) !important;
    }

    .bank-stock-workspace {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 260px;
        gap: 10px;
        margin-bottom: 10px;
    }

    .bank-stock-chart-panel {
        min-width: 0;
        padding: 12px;
    }

    .bank-stock-chart-toolbar,
    .bank-stock-chart-foot,
    .bank-stock-snapshot-title {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
    }

    .bank-stock-chart-toolbar strong,
    .bank-stock-snapshot-title strong {
        color: #f8fafc;
        font-size: 0.94rem;
    }

    .bank-stock-chart-tabs {
        display: inline-flex;
        gap: 4px;
        padding: 3px;
        border: 1px solid rgba(148, 163, 184, 0.18);
        border-radius: 8px;
        background: rgba(2, 6, 23, 0.28);
    }

    .bank-stock-chart-tabs span {
        min-width: 34px;
        padding: 4px 7px;
        border-radius: 6px;
        color: rgba(203, 213, 225, 0.72);
        text-align: center;
        font-size: 0.72rem;
    }

    .bank-stock-chart-tabs .is-active {
        background: rgba(251, 191, 36, 0.18);
        color: #fbbf24;
    }

    .bank-stock-chart {
        height: 320px;
        margin-top: 10px;
        border: 1px solid rgba(148, 163, 184, 0.14);
        border-radius: 8px;
        background:
            linear-gradient(90deg, rgba(148, 163, 184, 0.07) 1px, transparent 1px) 0 0 / 76px 100%,
            linear-gradient(180deg, rgba(148, 163, 184, 0.07) 1px, transparent 1px) 0 0 / 100% 60px,
            rgba(2, 6, 23, 0.42);
        overflow: hidden;
    }

    .bank-stock-chart svg {
        display: block;
        width: 100%;
        height: 100%;
    }

    .bank-stock-chart .grid line {
        stroke: rgba(148, 163, 184, 0.12);
        stroke-width: 1;
    }

    .bank-stock-chart .area {
        fill: url(#stockChartFill);
    }

    .bank-stock-chart .line {
        fill: none;
        stroke: #22c55e;
        stroke-width: 4;
        stroke-linecap: round;
        stroke-linejoin: round;
    }

    .bank-stock-chart-point {
        cursor: pointer;
        outline: none;
    }

    .bank-stock-chart-point circle {
        fill: #0f172a;
        stroke: #fbbf24;
        stroke-width: 3;
        transition: r 0.16s ease, fill 0.16s ease;
    }

    .bank-stock-chart-point:hover circle,
    .bank-stock-chart-point.is-active circle {
        r: 8;
        fill: #fbbf24;
    }

    .bank-stock-chart-foot {
        margin-top: 8px;
        color: rgba(203, 213, 225, 0.74);
        font-size: 0.78rem;
    }

    .bank-stock-profile-panel {
        display: grid;
        align-content: start;
        padding: 10px 12px;
    }

    .bank-stock-profile-row {
        display: grid;
        gap: 4px;
        padding: 10px 0;
        border-bottom: 1px solid rgba(148, 163, 184, 0.14);
    }

    .bank-stock-profile-row:last-child {
        border-bottom: 0;
    }

    .bank-stock-profile-row strong {
        color: #f8fafc;
        font-size: 0.88rem;
        line-height: 1.25;
        overflow-wrap: anywhere;
    }

    .bank-stock-snapshot {
        padding: 12px;
    }

    .bank-stock-snapshot-tabs {
        display: inline-flex;
        gap: 4px;
        margin-top: 10px;
        padding: 3px;
        border: 1px solid rgba(148, 163, 184, 0.18);
        border-radius: 8px;
        background: rgba(2, 6, 23, 0.28);
    }

    .bank-stock-snapshot-tabs button {
        min-width: 96px;
        padding: 6px 10px;
        border: 0;
        border-radius: 6px;
        background: transparent;
        color: rgba(203, 213, 225, 0.74);
        font-size: 0.78rem;
        font-weight: 800;
    }

    .bank-stock-snapshot-tabs button.is-active {
        background: rgba(251, 191, 36, 0.18);
        color: #fbbf24;
    }

    .bank-stock-snapshot-scroll {
        margin-top: 10px;
        overflow-x: auto;
    }

    .bank-stock-snapshot-table {
        width: 100%;
        min-width: 860px;
        border-collapse: collapse;
        table-layout: fixed;
        font-size: 0.8rem;
    }

    .bank-stock-snapshot-table th,
    .bank-stock-snapshot-table td {
        padding: 7px 8px;
        border: 1px solid rgba(148, 163, 184, 0.14);
    }

    .bank-stock-snapshot-table th {
        width: 12.5%;
        background: rgba(15, 23, 42, 0.88);
        color: rgba(148, 163, 184, 0.96);
        font-weight: 800;
        text-align: left;
    }

    .bank-stock-snapshot-table td {
        width: 12.5%;
        background: rgba(2, 6, 23, 0.28);
        color: #f8fafc;
        text-align: right;
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace;
        overflow-wrap: anywhere;
    }

    .bank-stock-analysis-view {
        display: grid;
        gap: 12px;
        margin-top: 12px;
    }

    .bank-stock-analysis-note,
    .bank-stock-analysis-block,
    .bank-stock-analysis-cheatsheet {
        border: 1px solid rgba(148, 163, 184, 0.16);
        border-radius: 8px;
        background: rgba(2, 6, 23, 0.28);
    }

    .bank-stock-analysis-note {
        display: grid;
        gap: 4px;
        padding: 10px 12px;
        color: rgba(203, 213, 225, 0.82);
        font-size: 0.82rem;
        line-height: 1.45;
    }

    .bank-stock-analysis-note strong,
    .bank-stock-analysis-heading {
        color: #f8fafc;
        font-size: 0.9rem;
        font-weight: 800;
    }

    .bank-stock-analysis-grid {
        display: grid;
        gap: 10px;
    }

    .bank-stock-analysis-block {
        padding: 12px;
    }

    .bank-stock-analysis-block h3 {
        margin: 0 0 8px;
        color: #f8fafc;
        font-size: 1rem;
        line-height: 1.25;
    }

    .bank-stock-analysis-items {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 8px;
    }

    .bank-stock-analysis-item {
        display: grid;
        gap: 5px;
        padding: 9px;
        border: 1px solid rgba(148, 163, 184, 0.12);
        border-radius: 8px;
        background: rgba(15, 23, 42, 0.55);
    }

    .bank-stock-analysis-item__top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
    }

    .bank-stock-analysis-item__top strong {
        color: #f8fafc;
        font-size: 0.84rem;
    }

    .bank-stock-analysis-value {
        color: #fbbf24;
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace;
        font-size: 0.82rem;
        font-weight: 800;
    }

    .bank-stock-analysis-verdict {
        width: fit-content;
        padding: 3px 7px;
        border-radius: 999px;
        font-size: 0.68rem;
        font-weight: 900;
        text-transform: uppercase;
    }

    .bank-stock-analysis-verdict.is-good {
        background: rgba(34, 197, 94, 0.16);
        color: #22c55e;
    }

    .bank-stock-analysis-verdict.is-watch {
        background: rgba(251, 191, 36, 0.16);
        color: #fbbf24;
    }

    .bank-stock-analysis-verdict.is-risk {
        background: rgba(244, 63, 94, 0.16);
        color: #f43f5e;
    }

    .bank-stock-analysis-verdict.is-missing {
        background: rgba(148, 163, 184, 0.14);
        color: rgba(203, 213, 225, 0.82);
    }

    .bank-stock-analysis-text {
        color: rgba(203, 213, 225, 0.82);
        font-size: 0.78rem;
        line-height: 1.4;
    }

    .bank-stock-analysis-cheatsheet {
        padding: 12px;
        overflow-x: auto;
    }

    .bank-stock-analysis-cheatsheet table {
        width: 100%;
        min-width: 720px;
        margin-top: 8px;
        border-collapse: collapse;
        font-size: 0.78rem;
    }

    .bank-stock-analysis-cheatsheet th,
    .bank-stock-analysis-cheatsheet td {
        padding: 7px 8px;
        border: 1px solid rgba(148, 163, 184, 0.14);
        color: rgba(226, 232, 240, 0.88);
        text-align: left;
        vertical-align: top;
    }

    .bank-stock-analysis-cheatsheet th {
        background: rgba(15, 23, 42, 0.84);
        color: rgba(148, 163, 184, 0.96);
        font-weight: 900;
    }

    @media (max-width: 900px) {
        .bank-stock-quote-strip {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .bank-stock-workspace {
            grid-template-columns: 1fr;
        }

        .bank-stock-profile-panel {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0 14px;
        }
    }

    @media (max-width: 560px) {
        .bank-stock-detail-header {
            flex-direction: column;
            padding: 12px;
        }

        .bank-stock-detail-header h1 {
            font-size: 1.32rem;
        }

        .bank-stock-detail-header h1 span {
            display: block;
            margin-top: 3px;
        }

        .bank-stock-quote-strip {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .bank-stock-chart {
            height: 240px;
        }

        .bank-stock-chart-toolbar {
            align-items: flex-start;
            flex-direction: column;
        }

        .bank-stock-chart-foot {
            display: grid;
            grid-template-columns: 1fr;
            gap: 4px;
        }

        .bank-stock-profile-panel {
            grid-template-columns: 1fr;
        }

        .bank-stock-snapshot-table {
            min-width: 620px;
            font-size: 0.74rem;
        }

        .bank-stock-analysis-items {
            grid-template-columns: 1fr;
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const snapshots = @json($snapshotData);
        const initialSnapshotDate = @json($selectedSnapshot?->snapshot_date ?? $chartPoints->last()['date'] ?? '');
        const analysisResults = document.querySelector('[data-stock-analysis-results]');
        const fieldByLabel = {
            'Market Cap': 'market_cap',
            'Income': 'income',
            'Sales': 'sales',
            'Book/sh': 'book_per_share',
            'Cash/sh': 'cash_per_share',
            'Dividend Est.': 'dividend_est',
            'Dividend TTM': 'dividend_ttm',
            'Dividend Ex-Date': 'dividend_ex_date',
            'Dividend Gr. 3/5Y': 'dividend_growth_3_5y',
            'Payout': 'payout',
            'Employees': 'employees',
            'P/E': 'pe',
            'Forward P/E': 'forward_pe',
            'PEG': 'peg',
            'P/S': 'ps',
            'P/B': 'pb',
            'P/C': 'pc',
            'P/FCF': 'pfcf',
            'EV/EBITDA': 'ev_ebitda',
            'EV/Sales': 'ev_sales',
            'Enterprise Value': 'enterprise_value',
            'Quick Ratio': 'quick_ratio',
            'Current Ratio': 'current_ratio',
            'Debt/Eq': 'debt_eq',
            'LT Debt/Eq': 'lt_debt_eq',
            'Option/Short': 'option_short',
            'IPO': 'ipo',
            'EPS (ttm)': 'eps_ttm',
            'EPS next Y': 'eps_next_y_value',
            'EPS next Q': 'eps_next_q',
            'Earnings': 'earnings',
            'EPS this Y': 'eps_this_y_growth',
            'EPS next Y %': 'eps_next_y_growth',
            'EPS next 5Y': 'eps_next_5y_growth',
            'EPS past 3/5Y': 'eps_past_3_5y',
            'Sales past 3/5Y': 'sales_past_3_5y',
            'EPS Y/Y TTM': 'eps_yy_ttm',
            'Sales Y/Y TTM': 'sales_yy_ttm',
            'EPS Q/Q': 'eps_qq',
            'Sales Q/Q': 'sales_qq',
            'Price': 'price',
            'Change': 'change_percent',
            'Volume': 'volume',
        };
        const escapeHtml = (value) => String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
        const parseMetric = (value) => {
            const normalized = String(value || '').replace(/,/g, '').replace(/%/g, '').trim();
            const number = Number.parseFloat(normalized.replace(/[^0-9.\-]/g, ''));

            return Number.isFinite(number) ? number : null;
        };
        const verdict = (state, label, text) => ({ state, label, text });
        const missing = (text = 'В snapshot нет данных для расчета. Подтяните этот показатель через адаптер или внесите вручную.') => verdict('missing', 'Нет данных', text);
        const metricValue = (payload, field) => {
            const raw = payload?.[field] || '';
            const number = parseMetric(raw);

            return { raw, number };
        };
        const metricItem = (title, rawValue, result) => `
            <div class="bank-stock-analysis-item">
                <div class="bank-stock-analysis-item__top">
                    <strong>${escapeHtml(title)}</strong>
                    <span class="bank-stock-analysis-value">${escapeHtml(rawValue || '—')}</span>
                </div>
                <span class="bank-stock-analysis-verdict is-${result.state}">${escapeHtml(result.label)}</span>
                <div class="bank-stock-analysis-text">${escapeHtml(result.text)}</div>
            </div>
        `;
        const renderAnalysisBlock = (title, items) => `
            <section class="bank-stock-analysis-block">
                <h3>${escapeHtml(title)}</h3>
                <div class="bank-stock-analysis-items">${items.join('')}</div>
            </section>
        `;
        const renderAnalysis = (payload = {}) => {
            if (!analysisResults) return;

            const pe = metricValue(payload, 'pe');
            const evEbitda = metricValue(payload, 'ev_ebitda');
            const ps = metricValue(payload, 'ps');
            const pb = metricValue(payload, 'pb');
            const netDebtEbitda = metricValue(payload, 'net_debt_ebitda');
            const currentRatio = metricValue(payload, 'current_ratio');
            const roe = metricValue(payload, 'roe');
            const roic = metricValue(payload, 'roic');
            const salesGrowth = metricValue(payload, 'sales_past_3_5y');
            const epsGrowth = metricValue(payload, 'eps_past_3_5y');
            const payout = metricValue(payload, 'payout');

            const peVerdict = pe.number === null
                ? missing('P/E нужен для первичной оценки дешевизны. Сравнение с отраслью и конкурентами в snapshot отсутствует.')
                : pe.number < 0
                    ? verdict('risk', 'Риск', 'Отрицательный P/E означает убыток; обычная оценка окупаемости через прибыль неприменима.')
                    : pe.number <= 15
                        ? verdict('watch', 'Дешево?', 'P/E ниже типичных широких ориентиров, но требуется проверить рост, качество бизнеса, отраслевую норму и конкурентов.')
                        : pe.number <= 30
                            ? verdict('watch', 'Нейтрально', 'P/E умеренный. Без отраслевого среднего и истории компании вывод о дешевизне неполный.')
                            : verdict('risk', 'Дорого', 'Высокий P/E требует сильного роста прибыли; без подтверждения это может быть переплата.');
            const evEbitdaVerdict = evEbitda.number === null
                ? missing('EV/EBITDA отсутствует. Для оценки всего бизнеса с учетом долга нужно подтянуть EBITDA/EV.')
                : evEbitda.number <= 12
                    ? verdict('good', 'Норма', 'EV/EBITDA в зоне, которая часто выглядит привлекательной для стабильных компаний; сектор может менять ориентир.')
                    : evEbitda.number <= 18
                        ? verdict('watch', 'Выше нормы', 'Мультипликатор выше базового ориентира 10-12, нужна проверка роста и отраслевой премии.')
                        : verdict('risk', 'Риск цены', 'Высокий EV/EBITDA без сильного роста и качества бизнеса может указывать на переплату.');
            const psVerdict = ps.number === null
                ? missing('P/S отсутствует. Метрика важна для компаний, где прибыль временно искажена или низкая.')
                : ps.number <= 3
                    ? verdict('good', 'Сдержанно', 'P/S выглядит умеренно, но нужно сравнение с маржинальностью и отраслью.')
                    : ps.number <= 8
                        ? verdict('watch', 'Премия', 'Инвестор платит заметную цену за каждый доллар продаж; нужна высокая маржа или рост.')
                        : verdict('risk', 'Дорого', 'Очень высокий P/S требует сильного роста и высокой будущей прибыльности.');
            const pbVerdict = pb.number === null
                ? missing('P/B отсутствует. Для банков, финансов и капиталоемких компаний эта метрика особенно важна.')
                : pb.number < 1
                    ? verdict('watch', 'Ниже баланса', 'Акция торгуется ниже балансовой стоимости, но нужно проверить скрытые убытки и качество активов.')
                    : pb.number <= 3
                        ? verdict('good', 'Норма', 'P/B выглядит умеренно для многих секторов; для asset-light бизнеса нормальный уровень может быть выше.')
                        : verdict('watch', 'Премия', 'Высокий P/B требует высокой ROE/ROIC и устойчивого конкурентного преимущества.');

            const debtVerdict = netDebtEbitda.number === null
                ? missing('Net Debt / EBITDA пока не сохраняется в snapshot. Для долговой безопасности нужен чистый долг и EBITDA.')
                : netDebtEbitda.number <= 2.5
                    ? verdict('good', 'Безопасно', 'Долговая нагрузка в базовой безопасной зоне до 2.0-2.5.')
                    : netDebtEbitda.number <= 3.5
                        ? verdict('watch', 'Контроль', 'Долг повышенный; стоит проверить ставки, сроки погашения и стабильность cash flow.')
                        : verdict('risk', 'Высокий риск', 'Выше 3.5 - зона повышенного риска, особенно в кризис и при высоких ставках.');
            const currentRatioVerdict = currentRatio.number === null
                ? missing('Current Ratio отсутствует. Нужны краткосрочные активы и обязательства.')
                : currentRatio.number >= 1.5
                    ? verdict('good', 'Ликвидно', 'Коэффициент выше 1.5, краткосрочная платежеспособность выглядит приемлемо.')
                    : currentRatio.number >= 1
                        ? verdict('watch', 'Тонко', 'Выше 1.0, но ниже комфортного ориентира 1.5-2.0.')
                        : verdict('risk', 'Риск кассы', 'Ниже 1.0 - возможен кассовый разрыв или нехватка оборотного капитала.');

            const roeVerdict = roe.number === null
                ? missing('ROE не заполнен. Для оценки эффективности менеджмента нужен return on equity.')
                : roe.number >= 15
                    ? verdict('good', 'Сильный', 'ROE выше 15% указывает на эффективное использование капитала акционеров.')
                    : roe.number >= 5
                        ? verdict('watch', 'Средне', 'ROE положительный, но ниже ориентира сильного бизнеса.')
                        : verdict('risk', 'Слабый', 'ROE ниже 5% или отрицательный - тревожный сигнал эффективности.');
            const roicVerdict = roic.number === null
                ? missing('ROIC не заполнен. Для вывода нужно сравнить ROIC со стоимостью капитала WACC.')
                : roic.number >= 10
                    ? verdict('good', 'Создает стоимость', 'ROIC выглядит сильным; финальный вывод требует сравнения с WACC.')
                    : roic.number >= 5
                        ? verdict('watch', 'Погранично', 'ROIC положительный, но запас к стоимости капитала может быть небольшим.')
                        : verdict('risk', 'Слабый', 'Низкий ROIC может означать, что бизнес плохо конвертирует капитал в прибыль.');

            const growthText = [salesGrowth.raw ? `Выручка 3/5Y: ${salesGrowth.raw}.` : '', epsGrowth.raw ? `EPS 3/5Y: ${epsGrowth.raw}.` : ''].filter(Boolean).join(' ');
            const growthVerdict = growthText
                ? verdict('watch', 'Проверить', `${growthText} Для устойчивой компании желательно видеть рост хотя бы на уровне инфляции и экономики; точный вывод требует сравнения с сектором.`)
                : missing('Нет CAGR выручки/прибыли за 3-5 лет. Без динамики роста мультипликаторы могут вводить в заблуждение.');
            const payoutVerdict = payout.number === null
                ? missing('Dividend Payout отсутствует. Для дивидендного риска нужна доля прибыли, направляемая на выплаты.')
                : payout.number >= 40 && payout.number <= 60
                    ? verdict('good', 'Здорово', 'Payout в ориентире 40-60%, дивиденды выглядят сбалансированными.')
                    : payout.number <= 90
                        ? verdict('watch', 'Допустимо', 'Payout вне идеального диапазона, но ниже критической зоны 90-100%.')
                        : verdict('risk', 'Риск дивидендов', 'Payout около или выше 100% означает риск выплаты дивидендов в долг.');

            analysisResults.innerHTML = [
                renderAnalysisBlock('Блок 1. Оценка дешевизны и справедливости цены', [
                    metricItem('P/E', pe.raw, peVerdict),
                    metricItem('EV/EBITDA', evEbitda.raw, evEbitdaVerdict),
                    metricItem('P/S', ps.raw, psVerdict),
                    metricItem('P/B', pb.raw, pbVerdict),
                ]),
                renderAnalysisBlock('Блок 2. Финансовая безопасность и долги', [
                    metricItem('Net Debt / EBITDA', netDebtEbitda.raw, debtVerdict),
                    metricItem('Current Ratio', currentRatio.raw, currentRatioVerdict),
                ]),
                renderAnalysisBlock('Блок 3. Эффективность бизнеса и менеджмента', [
                    metricItem('ROE', roe.raw, roeVerdict),
                    metricItem('ROIC', roic.raw, roicVerdict),
                ]),
                renderAnalysisBlock('Блок 4. Темпы роста и дивиденды', [
                    metricItem('CAGR выручки и прибыли 3-5Y', growthText || '', growthVerdict),
                    metricItem('Dividend Payout Ratio', payout.raw, payoutVerdict),
                ]),
            ].join('');
        };

        const setSnapshot = (date) => {
            const snapshot = snapshots.find((item) => item.date === date);
            if (!snapshot) return;

            const payload = snapshot.payload || {};
            document.querySelectorAll('[data-stock-snapshot-value]').forEach((cell) => {
                const label = cell.dataset.stockSnapshotValue || '';
                const field = fieldByLabel[label];
                const value = field ? (payload[field] || '—') : '—';
                cell.textContent = value || '—';
                cell.classList.toggle('is-negative', label === 'Change' && String(value).startsWith('-'));
                cell.classList.toggle('is-positive', label === 'Change' && value && !String(value).startsWith('-') && value !== '0' && value !== '0%');
            });

            const titleDate = document.querySelector('[data-stock-snapshot-title-date]');
            if (titleDate) titleDate.textContent = date;
            const chartDate = document.querySelector('[data-stock-chart-date]');
            if (chartDate) chartDate.textContent = `Date ${date}`;
            const chartPrice = document.querySelector('[data-stock-chart-price]');
            if (chartPrice) chartPrice.textContent = `Price ${payload.price || '—'}`;
            const chartVolume = document.querySelector('[data-stock-chart-volume]');
            if (chartVolume) chartVolume.textContent = `Volume ${payload.volume || '—'}`;
            renderAnalysis(payload);

            document.querySelectorAll('[data-stock-snapshot-date]').forEach((point) => {
                point.classList.toggle('is-active', point.dataset.stockSnapshotDate === date);
            });
        };

        document.querySelectorAll('[data-stock-snapshot-date]').forEach((point) => {
            point.addEventListener('click', () => setSnapshot(point.dataset.stockSnapshotDate || ''));
            point.addEventListener('keydown', (event) => {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    setSnapshot(point.dataset.stockSnapshotDate || '');
                }
            });
        });
        document.querySelectorAll('[data-stock-tab]').forEach((tab) => {
            tab.addEventListener('click', () => {
                const target = tab.dataset.stockTab || 'parameters';
                document.querySelectorAll('[data-stock-tab]').forEach((button) => {
                    const active = button.dataset.stockTab === target;
                    button.classList.toggle('is-active', active);
                    button.setAttribute('aria-selected', active ? 'true' : 'false');
                });
                document.querySelectorAll('[data-stock-tab-panel]').forEach((panel) => {
                    panel.hidden = panel.dataset.stockTabPanel !== target;
                });
            });
        });
        const initialSnapshot = snapshots.find((item) => item.date === initialSnapshotDate) || snapshots[snapshots.length - 1];
        renderAnalysis(initialSnapshot?.payload || {});
    });
</script>
@endsection
