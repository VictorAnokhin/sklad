@extends('home')

@section('title')
Анализ акции
@endsection

@section('content')
@php
    $overviewFields = [
        ['company', 'Компания'],
        ['ticker', 'Тикер'],
        ['sector', 'Sector'],
        ['industry', 'Industry'],
        ['country', 'Country'],
        ['market', 'Market'],
        ['pe', 'P/E'],
        ['price', 'Price'],
        ['change_percent', 'Change %'],
        ['volume', 'Volume'],
    ];
    $metricGroups = [
        'Оценка и баланс' => [
            ['market_cap', 'Market Cap'],
            ['enterprise_value', 'Enterprise Value'],
            ['income', 'Income'],
            ['sales', 'Sales'],
            ['book_per_share', 'Book/sh'],
            ['cash_per_share', 'Cash/sh'],
        ],
        'Дивиденды' => [
            ['dividend_est', 'Dividend Est.'],
            ['dividend_ttm', 'Dividend TTM'],
            ['dividend_ex_date', 'Dividend Ex-Date'],
            ['dividend_growth_3_5y', 'Dividend Gr. 3/5Y'],
            ['payout', 'Payout'],
        ],
        'Мультипликаторы' => [
            ['employees', 'Employees'],
            ['ipo', 'IPO'],
            ['forward_pe', 'Forward P/E'],
            ['peg', 'PEG'],
            ['ps', 'P/S'],
            ['pb', 'P/B'],
            ['pc', 'P/C'],
            ['pfcf', 'P/FCF'],
            ['ev_ebitda', 'EV/EBITDA'],
            ['ev_sales', 'EV/Sales'],
        ],
        'Ликвидность и долг' => [
            ['quick_ratio', 'Quick Ratio'],
            ['current_ratio', 'Current Ratio'],
            ['debt_eq', 'Debt/Eq'],
            ['lt_debt_eq', 'LT Debt/Eq'],
            ['option_short', 'Option/Short'],
        ],
        'EPS и продажи' => [
            ['eps_ttm', 'EPS (ttm)'],
            ['eps_next_y_value', 'EPS next Y'],
            ['eps_next_q', 'EPS next Q'],
            ['eps_this_y_growth', 'EPS this Y'],
            ['eps_next_y_growth', 'EPS next Y %'],
            ['eps_next_5y_growth', 'EPS next 5Y'],
            ['eps_past_3_5y', 'EPS past 3/5Y'],
            ['sales_past_3_5y', 'Sales past 3/5Y'],
            ['eps_yy_ttm', 'EPS Y/Y TTM'],
            ['sales_yy_ttm', 'Sales Y/Y TTM'],
            ['eps_qq', 'EPS Q/Q'],
            ['sales_qq', 'Sales Q/Q'],
            ['earnings', 'Earnings'],
        ],
    ];
@endphp

<div class="bank-page bank-stock-show-page">
    @include('bank.partials.invest_nav')

    <section class="bank-panel bank-panel--accent">
        <div class="bank-table-header">
            <div>
                <div class="bank-label">Анализ акции</div>
                <h1 class="bank-stock-show-title">{{ $stock->ticker }} · {{ $stock->company }}</h1>
                <div class="bank-meta">{{ $stock->sector ?: 'Sector не указан' }}{{ $stock->industry ? ' · ' . $stock->industry : '' }}{{ $stock->country ? ' · ' . $stock->country : '' }}</div>
            </div>
            <a class="btn btn-sm btn-outline-light" href="{{ route('bank.stock-analysis') }}">Назад к акциям</a>
        </div>
    </section>

    <section class="bank-grid bank-grid--summary">
        @foreach($overviewFields as [$name, $label])
            <div class="bank-panel">
                <div class="bank-label">{{ $label }}</div>
                <div class="bank-value bank-stock-show-value">{{ $stock->{$name} ?: '—' }}</div>
            </div>
        @endforeach
    </section>

    @foreach($metricGroups as $groupTitle => $fields)
        <section class="bank-panel bank-table-panel">
            <div class="bank-table-header">
                <div>
                    <div class="bank-label">{{ $groupTitle }}</div>
                    <div class="bank-meta">Показатели по {{ $stock->ticker }}.</div>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-dark table-sm align-middle bank-table">
                    <tbody>
                        @foreach($fields as [$name, $label])
                            <tr>
                                <th>{{ $label }}</th>
                                <td class="text-end bank-mono">{{ $stock->{$name} ?: '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endforeach
</div>

@include('bank.partials.styles')

<style>
    .bank-stock-show-title {
        margin: 4px 0;
        color: #f8fafc;
        font-size: 1.55rem;
        font-weight: 700;
    }

    .bank-stock-show-value {
        overflow-wrap: anywhere;
    }

    .bank-stock-show-page .bank-table th {
        color: #94a3b8;
        font-weight: 600;
    }
</style>
@endsection
