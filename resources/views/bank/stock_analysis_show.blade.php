@extends('home')

@section('title')
Анализ акции
@endsection

@section('content')
@php
    $change = trim((string) ($stock->change_percent ?? ''));
    $changeClass = str_starts_with($change, '-')
        ? 'is-negative'
        : ($change !== '' && $change !== '0' && $change !== '0%' ? 'is-positive' : 'is-neutral');
    $identity = array_filter([
        trim((string) ($stock->sector ?? '')),
        trim((string) ($stock->industry ?? '')),
        trim((string) ($stock->country ?? '')),
    ]);
    $quoteCards = [
        ['Price', $stock->price],
        ['Change', $change ?: '—'],
        ['Volume', $stock->volume],
        ['Market Cap', $stock->market_cap ?: $stock->market],
        ['P/E', $stock->pe],
        ['Dividend', $stock->dividend_est ?: $stock->dividend_ttm],
    ];
    $snapshotRows = [
        [
            ['Index', '—'],
            ['Market Cap', $stock->market_cap ?: $stock->market],
            ['Income', $stock->income],
            ['Sales', $stock->sales],
        ],
        [
            ['Book/sh', $stock->book_per_share],
            ['Cash/sh', $stock->cash_per_share],
            ['Dividend Est.', $stock->dividend_est],
            ['Dividend TTM', $stock->dividend_ttm],
        ],
        [
            ['Dividend Ex-Date', $stock->dividend_ex_date],
            ['Dividend Gr. 3/5Y', $stock->dividend_growth_3_5y],
            ['Payout', $stock->payout],
            ['Employees', $stock->employees],
        ],
        [
            ['P/E', $stock->pe],
            ['Forward P/E', $stock->forward_pe],
            ['PEG', $stock->peg],
            ['P/S', $stock->ps],
        ],
        [
            ['P/B', $stock->pb],
            ['P/C', $stock->pc],
            ['P/FCF', $stock->pfcf],
            ['EV/EBITDA', $stock->ev_ebitda],
        ],
        [
            ['EV/Sales', $stock->ev_sales],
            ['Enterprise Value', $stock->enterprise_value],
            ['Quick Ratio', $stock->quick_ratio],
            ['Current Ratio', $stock->current_ratio],
        ],
        [
            ['Debt/Eq', $stock->debt_eq],
            ['LT Debt/Eq', $stock->lt_debt_eq],
            ['Option/Short', $stock->option_short],
            ['IPO', $stock->ipo],
        ],
        [
            ['EPS (ttm)', $stock->eps_ttm],
            ['EPS next Y', $stock->eps_next_y_value],
            ['EPS next Q', $stock->eps_next_q],
            ['Earnings', $stock->earnings],
        ],
        [
            ['EPS this Y', $stock->eps_this_y_growth],
            ['EPS next Y %', $stock->eps_next_y_growth],
            ['EPS next 5Y', $stock->eps_next_5y_growth],
            ['EPS past 3/5Y', $stock->eps_past_3_5y],
        ],
        [
            ['Sales past 3/5Y', $stock->sales_past_3_5y],
            ['EPS Y/Y TTM', $stock->eps_yy_ttm],
            ['Sales Y/Y TTM', $stock->sales_yy_ttm],
            ['EPS Q/Q', $stock->eps_qq],
        ],
        [
            ['Sales Q/Q', $stock->sales_qq],
            ['Price', $stock->price],
            ['Change', $change ?: '—'],
            ['Volume', $stock->volume],
        ],
    ];
@endphp

<div class="bank-page bank-stock-detail-page">
    @include('bank.partials.invest_nav')

    <section class="bank-stock-detail-header">
        <div>
            <div class="bank-stock-detail-kicker">Анализ акции</div>
            <h1>{{ $stock->ticker }} <span>{{ $stock->company }}</span></h1>
            <div class="bank-stock-detail-meta">{{ $identity ? implode(' · ', $identity) : 'Sector / Industry / Country не указаны' }}</div>
        </div>
        <a class="btn btn-sm btn-outline-light" href="{{ route('bank.stock-analysis') }}">Назад к акциям</a>
    </section>

    <section class="bank-stock-quote-strip">
        @foreach($quoteCards as [$label, $value])
            <div class="bank-stock-quote-card {{ $label === 'Change' ? $changeClass : '' }}">
                <span>{{ $label }}</span>
                <strong>{{ $value ?: '—' }}</strong>
            </div>
        @endforeach
    </section>

    <section class="bank-stock-workspace">
        <div class="bank-stock-chart-panel">
            <div class="bank-stock-chart-toolbar">
                <div>
                    <span>Chart</span>
                    <strong>{{ $stock->ticker }}</strong>
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
                    <path class="area" d="M0 220 L70 205 L145 212 L220 180 L310 188 L390 142 L470 158 L550 106 L635 118 L760 74 L760 300 L0 300 Z"/>
                    <path class="line" d="M0 220 L70 205 L145 212 L220 180 L310 188 L390 142 L470 158 L550 106 L635 118 L760 74"/>
                </svg>
            </div>
            <div class="bank-stock-chart-foot">
                <span>Price {{ $stock->price ?: '—' }}</span>
                <span>Volume {{ $stock->volume ?: '—' }}</span>
                <span>Change {{ $change ?: '—' }}</span>
            </div>
        </div>

        <aside class="bank-stock-profile-panel">
            <div class="bank-stock-profile-row">
                <span>Company</span>
                <strong>{{ $stock->company ?: '—' }}</strong>
            </div>
            <div class="bank-stock-profile-row">
                <span>Sector</span>
                <strong>{{ $stock->sector ?: '—' }}</strong>
            </div>
            <div class="bank-stock-profile-row">
                <span>Industry</span>
                <strong>{{ $stock->industry ?: '—' }}</strong>
            </div>
            <div class="bank-stock-profile-row">
                <span>Country</span>
                <strong>{{ $stock->country ?: '—' }}</strong>
            </div>
            <div class="bank-stock-profile-row">
                <span>IPO</span>
                <strong>{{ $stock->ipo ?: '—' }}</strong>
            </div>
            <div class="bank-stock-profile-row">
                <span>Employees</span>
                <strong>{{ $stock->employees ?: '—' }}</strong>
            </div>
        </aside>
    </section>

    <section class="bank-stock-snapshot">
        <div class="bank-stock-snapshot-title">
            <span>Snapshot</span>
            <strong>{{ $stock->ticker }} fundamentals</strong>
        </div>
        <div class="bank-stock-snapshot-scroll">
            <table class="bank-stock-snapshot-table">
                <tbody>
                    @foreach($snapshotRows as $row)
                        <tr>
                            @foreach($row as [$label, $value])
                                <th>{{ $label }}</th>
                                <td class="{{ $label === 'Change' ? $changeClass : '' }}">{{ $value ?: '—' }}</td>
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
@endsection
