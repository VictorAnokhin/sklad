@extends('home')

@section('title')
Анализ Акций
@endsection

@section('content')
@php
    $baseFields = [
        ['company', 'Компания', 'Coca-Cola Co', true],
        ['ticker', 'Тикер', 'KO', true],
        ['sector', 'Sector', 'Consumer Defensive', false],
        ['industry', 'Industry', 'Beverages - Non-Alcoholic', false],
        ['country', 'Country', 'USA', false],
        ['market', 'Market', '372.39B', false],
        ['pe', 'P/E', '26.08', false],
        ['price', 'Price', '86.55', false],
        ['change_percent', 'Change %', '-0.35%', false],
        ['volume', 'Volume', '75,988', false],
    ];
    $metricGroups = [
        'Оценка и баланс' => [
            ['market_cap', 'Market Cap', '374.54B'],
            ['enterprise_value', 'Enterprise Value', '403.87B'],
            ['income', 'Income', '14.32B'],
            ['sales', 'Sales', '50.57B'],
            ['book_per_share', 'Book/sh', '8.40'],
            ['cash_per_share', 'Cash/sh', '3.80'],
        ],
        'Дивиденды' => [
            ['dividend_est', 'Dividend Est.', '2.19 (2.52%)'],
            ['dividend_ttm', 'Dividend TTM', '2.08 (2.39%)'],
            ['dividend_ex_date', 'Dividend Ex-Date', 'Sep 15, 2026'],
            ['dividend_growth_3_5y', 'Dividend Gr. 3/5Y', '5.04% 4.46%'],
            ['payout', 'Payout', '67.13%'],
        ],
        'Мультипликаторы' => [
            ['employees', 'Employees', '65900'],
            ['ipo', 'IPO', 'Jan 26, 1950'],
            ['forward_pe', 'Forward P/E', '24.65'],
            ['peg', 'PEG', '3.00'],
            ['ps', 'P/S', '7.41'],
            ['pb', 'P/B', '10.36'],
            ['pc', 'P/C', '22.88'],
            ['pfcf', 'P/FCF', '26.20'],
            ['ev_ebitda', 'EV/EBITDA', '23.51'],
            ['ev_sales', 'EV/Sales', '7.99'],
        ],
        'Ликвидность и долг' => [
            ['quick_ratio', 'Quick Ratio', '1.12'],
            ['current_ratio', 'Current Ratio', '1.30'],
            ['debt_eq', 'Debt/Eq', '1.20'],
            ['lt_debt_eq', 'LT Debt/Eq', '1.02'],
            ['option_short', 'Option/Short', 'Yes / Yes'],
        ],
        'EPS и продажи' => [
            ['eps_ttm', 'EPS (ttm)', '3.32'],
            ['eps_next_y_value', 'EPS next Y', '3.53'],
            ['eps_next_q', 'EPS next Q', '0.88'],
            ['eps_this_y_growth', 'EPS this Y', '10.17%'],
            ['eps_next_y_growth', 'EPS next Y %', '6.86%'],
            ['eps_next_5y_growth', 'EPS next 5Y', '8.21%'],
            ['eps_past_3_5y', 'EPS past 3/5Y', '11.48% 11.14%'],
            ['sales_past_3_5y', 'Sales past 3/5Y', '4.14% 7.94%'],
            ['eps_yy_ttm', 'EPS Y/Y TTM', '17.58%'],
            ['sales_yy_ttm', 'Sales Y/Y TTM', '7.42%'],
            ['eps_qq', 'EPS Q/Q', '16.19%'],
            ['sales_qq', 'Sales Q/Q', '5.93%'],
            ['earnings', 'Earnings', 'Jul 28 BMO'],
        ],
    ];
@endphp

<div class="bank-page bank-stock-page" data-bank-stock-page>
    @include('bank.partials.invest_nav')

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">Проверьте поля формы и попробуйте снова.</div>
    @endif

    <section class="bank-grid bank-grid--summary">
        <div class="bank-panel bank-panel--accent">
            <div class="bank-label">Акций</div>
            <div class="bank-value">{{ $summary['stocks'] }}</div>
            <div class="bank-meta">Записи в таблице анализа.</div>
        </div>
        <div class="bank-panel">
            <div class="bank-label">Страны</div>
            <div class="bank-value">{{ $summary['countries'] }}</div>
            <div class="bank-meta">Уникальные страны эмитентов.</div>
        </div>
        <div class="bank-panel">
            <div class="bank-label">Секторы</div>
            <div class="bank-value">{{ $summary['sectors'] }}</div>
            <div class="bank-meta">Уникальные сектора.</div>
        </div>
        <div class="bank-panel">
            <div class="bank-label">Tickers</div>
            <div class="bank-value bank-stock-tickers">{{ $summary['tickers'] ?: '—' }}</div>
            <div class="bank-meta">Список тикеров для анализа.</div>
        </div>
    </section>

    <section class="bank-panel bank-table-panel">
        <div class="bank-table-header">
            <div>
                <div class="bank-label">Анализ Акций</div>
                <div class="bank-meta">Фундаментальные показатели и рыночная сводка по тикерам.</div>
            </div>
            <button type="button" class="btn btn-sm btn-primary" data-stock-open>Добавить акцию</button>
        </div>
        <div class="table-responsive bank-table-scroll">
            <table class="table table-dark table-hover table-sm align-middle bank-table bank-stock-table">
                <thead>
                    <tr>
                        <th class="bank-table__num">No.</th>
                        <th>Ticker</th>
                        <th>Company</th>
                        <th>Sector</th>
                        <th>Industry</th>
                        <th>Country</th>
                        <th class="text-end">Market</th>
                        <th class="text-end">P/E</th>
                        <th class="text-end">Price</th>
                        <th class="text-end">Change %</th>
                        <th class="text-end">Volume</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($stocks as $stock)
                        @php
                            $change = trim((string) ($stock->change_percent ?? ''));
                            $changeClass = str_starts_with($change, '-')
                                ? 'text-danger'
                                : ($change !== '' && $change !== '0' && $change !== '0%' ? 'text-success' : 'text-muted');
                        @endphp
                        <tr>
                            <td class="bank-table__num bank-mono">{{ $loop->iteration }}</td>
                            <td><span class="bank-pill bank-pill--currency">{{ $stock->ticker }}</span></td>
                            <td>
                                <strong>{{ $stock->company }}</strong>
                                @if((int) $stock->project_id === 0)
                                    <div class="bank-meta">Пример</div>
                                @endif
                            </td>
                            <td>{{ $stock->sector ?: '—' }}</td>
                            <td>{{ $stock->industry ?: '—' }}</td>
                            <td>{{ $stock->country ?: '—' }}</td>
                            <td class="text-end bank-mono">{{ $stock->market ?: '—' }}</td>
                            <td class="text-end bank-mono">{{ $stock->pe ?: '—' }}</td>
                            <td class="text-end bank-mono">{{ $stock->price ?: '—' }}</td>
                            <td class="text-end bank-mono {{ $changeClass }}">{{ $change ?: '—' }}</td>
                            <td class="text-end bank-mono">{{ $stock->volume ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="text-center text-muted py-4">Акции пока не добавлены.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <div class="bank-modal" data-stock-modal @if(! $errors->any()) hidden @endif>
        <div class="bank-modal__backdrop" data-stock-close></div>
        <div class="bank-modal__dialog bank-stock-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="stockModalTitle">
            <div class="bank-modal__header">
                <div>
                    <div class="bank-label">Анализ Акций</div>
                    <h2 id="stockModalTitle">Добавить акцию</h2>
                    <div class="bank-meta">Сохранение тикера и фундаментальных показателей для банковского проекта.</div>
                </div>
                <button type="button" class="bank-modal__close" data-stock-close aria-label="Закрыть">×</button>
            </div>
            <form method="POST" action="{{ route('bank.stock-analysis.store') }}" class="bank-requisites-form">
                @csrf
                <div class="bank-stock-form-section">
                    <div class="bank-label">Таблица</div>
                    <div class="bank-form-grid">
                        @foreach($baseFields as [$name, $label, $placeholder, $required])
                            <label>
                                <span>{{ $label }}</span>
                                <input type="text"
                                       name="{{ $name }}"
                                       value="{{ old($name) }}"
                                       maxlength="{{ $name === 'ticker' ? 20 : 255 }}"
                                       placeholder="{{ $placeholder }}"
                                       {{ $required ? 'required' : '' }}>
                            </label>
                        @endforeach
                    </div>
                </div>

                @foreach($metricGroups as $groupTitle => $fields)
                    <div class="bank-stock-form-section">
                        <div class="bank-label">{{ $groupTitle }}</div>
                        <div class="bank-form-grid">
                            @foreach($fields as [$name, $label, $placeholder])
                                <label>
                                    <span>{{ $label }}</span>
                                    <input type="text" name="{{ $name }}" value="{{ old($name) }}" placeholder="{{ $placeholder }}">
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endforeach

                <div class="bank-modal__actions">
                    <button type="button" class="btn btn-secondary" data-stock-close>Отмена</button>
                    <button type="submit" class="btn btn-primary">Сохранить</button>
                </div>
            </form>
        </div>
    </div>
</div>

@include('bank.partials.styles')

<style>
    .bank-stock-tickers {
        font-size: 1.1rem;
        overflow-wrap: anywhere;
    }

    .bank-stock-table {
        min-width: 1280px;
    }

    .bank-stock-table th,
    .bank-stock-table td {
        white-space: nowrap;
    }

    .bank-stock-table th:nth-child(3),
    .bank-stock-table td:nth-child(3),
    .bank-stock-table th:nth-child(5),
    .bank-stock-table td:nth-child(5) {
        min-width: 220px;
    }

    .bank-stock-modal__dialog {
        width: min(1120px, calc(100vw - 32px));
        max-height: calc(100vh - 48px);
        overflow: auto;
    }

    .bank-stock-form-section {
        padding: 14px 0;
        border-top: 1px solid rgba(148, 163, 184, 0.14);
    }

    .bank-stock-form-section:first-child {
        border-top: 0;
        padding-top: 0;
    }

    @media (max-width: 760px) {
        .bank-table-header {
            align-items: flex-start;
            flex-direction: column;
        }
    }
</style>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const root = document.querySelector('[data-bank-stock-page]');
        if (!root) {
            return;
        }

        const modal = root.querySelector('[data-stock-modal]');
        const openButton = root.querySelector('[data-stock-open]');
        const closeButtons = root.querySelectorAll('[data-stock-close]');
        const tickerInput = modal?.querySelector('input[name="ticker"]');

        if (modal && !modal.hidden) {
            document.body.style.overflow = 'hidden';
        }

        const openModal = () => {
            modal.hidden = false;
            document.body.style.overflow = 'hidden';
            setTimeout(() => tickerInput?.focus(), 0);
        };
        const closeModal = () => {
            modal.hidden = true;
            document.body.style.overflow = '';
        };

        openButton?.addEventListener('click', openModal);
        closeButtons.forEach((button) => button.addEventListener('click', closeModal));
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && modal && !modal.hidden) {
                closeModal();
            }
        });
    });
</script>
@endpush
