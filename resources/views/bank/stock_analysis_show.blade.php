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
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const snapshots = @json($snapshotData);
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
    });
</script>
@endsection
