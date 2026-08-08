@extends('home')

@section('title')
Акции
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
    $searchableFields = [
        'sector' => $stockFilterOptions['sector'] ?? collect(),
        'industry' => $stockFilterOptions['industry'] ?? collect(),
        'country' => $stockFilterOptions['country'] ?? collect(),
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

    <div class="ttable top-action-bar bank-stock-filter-bar">
        <div class="top-action-filter">
            <div style="position:relative;margin-top:13px">
                <div onclick="stockFilterToggle()"
                     class="{{ !empty($stockFiltersActive) ? 'button_submit_start' : 'button_submit_start0' }}"
                     style="width:70px;height:70px;margin-top:-3px;cursor:pointer; background: linear-gradient(135deg, #fbbf24, #f59e0b); border: none; border-radius: 16px; box-shadow: 0 4px 12px rgba(251, 191, 36, 0.3); transition: all 0.3s ease; display: flex; flex-direction: column; align-items: center; justify-content: center;">
                    <img src="/img/icon-category.png" alt="Фильтр" style="width:32px;filter: brightness(0);">
                    <span style="font-size: 0.7rem; font-weight: 600; color: #000; margin-top: 4px;">Фильтр</span>
                </div>
            </div>
        </div>
    </div>

    <div id="stockFilterModal" style="display:none; position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(0,0,0,0.7); backdrop-filter:blur(8px); z-index:9999; justify-content:center; align-items:center;">
        <div class="glass-card" style="width:700px; max-width:90vw; max-height:80vh; overflow-y:auto; position:relative; margin:0 auto; padding:24px;">
            <div onclick="stockFilterToggle()" style="position:absolute; top:12px; right:16px; cursor:pointer; font-size:1.5rem; color:var(--muted-foreground); transition:color 0.2s; z-index:10;">✕</div>

            <h3 style="margin:0 0 16px 0; color:var(--foreground); font-family:var(--header); font-size:1.25rem;">🔍 Фильтр акций</h3>

            <form action="{{ route('bank.stock-analysis') }}" method="get" name="stockfilterform">
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
                    <div>
                        <label style="display:block; margin-bottom:4px; font-size:0.85rem;">Sector</label>
                        <input type="text"
                               name="sector"
                               list="stock-filter-sector-options"
                               autocomplete="off"
                               placeholder="Выберите или введите сектор"
                               value="{{ $stockFilters['sector'] ?? '' }}"
                               style="width:100%; padding:8px 12px; font-size:0.9rem;">
                        <datalist id="stock-filter-sector-options">
                            @foreach(($stockFilterOptions['sector'] ?? collect()) as $option)
                                <option value="{{ $option }}"></option>
                            @endforeach
                        </datalist>
                    </div>

                    <div>
                        <label style="display:block; margin-bottom:4px; font-size:0.85rem;">Industry</label>
                        <input type="text"
                               name="industry"
                               list="stock-filter-industry-options"
                               autocomplete="off"
                               placeholder="Выберите или введите индустрию"
                               value="{{ $stockFilters['industry'] ?? '' }}"
                               style="width:100%; padding:8px 12px; font-size:0.9rem;">
                        <datalist id="stock-filter-industry-options">
                            @foreach(($stockFilterOptions['industry'] ?? collect()) as $option)
                                <option value="{{ $option }}"></option>
                            @endforeach
                        </datalist>
                    </div>

                    <div>
                        <label style="display:block; margin-bottom:4px; font-size:0.85rem;">Country</label>
                        <input type="text"
                               name="country"
                               list="stock-filter-country-options"
                               autocomplete="off"
                               placeholder="Выберите или введите страну"
                               value="{{ $stockFilters['country'] ?? '' }}"
                               style="width:100%; padding:8px 12px; font-size:0.9rem;">
                        <datalist id="stock-filter-country-options">
                            @foreach(($stockFilterOptions['country'] ?? collect()) as $option)
                                <option value="{{ $option }}"></option>
                            @endforeach
                        </datalist>
                    </div>
                </div>

                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="submit" style="flex: 1; padding: 10px 16px; background: linear-gradient(135deg, #fbbf24, #f59e0b); border: none; border-radius: 8px; box-shadow: 0 4px 12px rgba(251, 191, 36, 0.3); color: #000; font-weight: 600; font-size: 0.9rem; cursor: pointer; transition: all 0.3s ease; display: flex; align-items: center; justify-content: center; gap: 6px;">
                        <span>🔍</span> Найти
                    </button>
                    <a href="{{ route('bank.stock-analysis') }}" style="flex: 1; padding: 10px 16px; background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.2); border-radius: 8px; color: var(--foreground); font-weight: 600; font-size: 0.9rem; cursor: pointer; transition: all 0.3s ease; display: flex; align-items: center; justify-content: center; gap: 6px; text-decoration:none;">
                        <span>✕</span> Сбросить
                    </a>
                </div>
            </form>
        </div>
    </div>

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
                <div class="bank-label">Акции</div>
                <div class="bank-meta">Фундаментальные показатели и рыночная сводка по тикерам.</div>
            </div>
            <button type="button" class="btn btn-sm btn-primary" data-stock-open>Добавить акцию</button>
        </div>
        <div class="bank-stock-table-wrap">
            <table class="table table-dark table-hover table-sm align-middle bank-table bank-stock-table">
                <thead>
                    <tr>
                        <th class="bank-table__num">No.</th>
                        <th>Ticker</th>
                        <th>Company</th>
                        <th class="text-end">Market</th>
                        <th class="text-end">P/E</th>
                        <th class="text-end">Price</th>
                        <th class="text-end">Change %</th>
                        <th class="text-end">Volume</th>
                        <th class="text-end" aria-label="Действия"></th>
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
                                <strong class="bank-stock-company">{{ $stock->company }}</strong>
                                <div class="bank-meta bank-stock-company-meta">
                                    {{ $stock->sector ?: '—' }}
                                    @if($stock->industry)
                                        · {{ $stock->industry }}
                                    @endif
                                    @if($stock->country)
                                        · {{ $stock->country }}
                                    @endif
                                </div>
                                @if((int) $stock->project_id === 0)
                                    <div class="bank-meta">Пример</div>
                                @endif
                            </td>
                            <td class="text-end bank-mono">{{ $stock->market ?: '—' }}</td>
                            <td class="text-end bank-mono">{{ $stock->pe ?: '—' }}</td>
                            <td class="text-end bank-mono">{{ $stock->price ?: '—' }}</td>
                            <td class="text-end bank-mono {{ $changeClass }}">{{ $change ?: '—' }}</td>
                            <td class="text-end bank-mono">{{ $stock->volume ?: '—' }}</td>
                            <td class="text-end">
                                <div class="bank-stock-actions">
                                    <button type="button"
                                            class="bank-stock-actions__trigger"
                                            data-stock-menu-toggle
                                            aria-label="Открыть меню акции {{ $stock->ticker }}">
                                        ⋮
                                    </button>
                                    <div class="bank-stock-actions__menu" data-stock-menu hidden>
                                        <button type="button"
                                                data-stock-edit
                                                data-stock-update-url="{{ route('bank.stock-analysis.update', $stock->id) }}"
                                                data-stock='{{ e(json_encode($stock, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE)) }}'>
                                            Редактировать
                                        </button>
                                        <a href="{{ route('bank.stock-analysis.show', $stock->id) }}">Анализ</a>
                                        <form method="POST" action="{{ route('bank.stock-analysis.destroy', $stock->id) }}" data-stock-delete-form>
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit">Удалить</button>
                                        </form>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">Акции пока не добавлены.</td>
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
                    <div class="bank-label">Акция</div>
                    <h2 id="stockModalTitle" data-stock-modal-title>Добавить акцию</h2>
                    <div class="bank-meta">Сохранение тикера и фундаментальных показателей для банковского проекта.</div>
                </div>
                <button type="button" class="bank-modal__close" data-stock-close aria-label="Закрыть">×</button>
            </div>
            <form method="POST"
                  action="{{ route('bank.stock-analysis.store') }}"
                  class="bank-requisites-form"
                  data-stock-form
                  data-stock-store-url="{{ route('bank.stock-analysis.store') }}">
                @csrf
                <input type="hidden" name="_method" value="POST" data-stock-form-method>
                <div class="bank-stock-form-section">
                    <div class="bank-label">Таблица</div>
                    <div class="bank-form-grid">
                        @foreach($baseFields as [$name, $label, $placeholder, $required])
                            @php
                                $maxLength = match ($name) {
                                    'ticker' => 20,
                                    'sector' => 160,
                                    'industry' => 190,
                                    'country' => 120,
                                    default => 255,
                                };
                            @endphp
                            <label>
                                <span>{{ $label }}</span>
                                <input type="text"
                                       name="{{ $name }}"
                                       value="{{ old($name) }}"
                                       maxlength="{{ $maxLength }}"
                                       placeholder="{{ $placeholder }}"
                                       @if(isset($searchableFields[$name])) list="stock-{{ $name }}-options" autocomplete="off" @endif
                                       {{ $required ? 'required' : '' }}>
                                @if(isset($searchableFields[$name]))
                                    <datalist id="stock-{{ $name }}-options">
                                        @foreach($searchableFields[$name] as $option)
                                            <option value="{{ $option }}"></option>
                                        @endforeach
                                    </datalist>
                                @endif
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

    .bank-stock-filter-bar {
        margin-bottom: 16px;
    }

    .bank-stock-page .bank-table-panel {
        overflow: visible;
    }

    .bank-stock-table-wrap {
        overflow: visible;
    }

    .bank-stock-table {
        width: 100%;
        table-layout: fixed;
        font-size: 0.78rem;
    }

    .bank-stock-table th,
    .bank-stock-table td {
        padding: 0.42rem 0.35rem;
        vertical-align: middle;
    }

    .bank-stock-table th:not(:last-child),
    .bank-stock-table td:not(:last-child) {
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .bank-stock-table .bank-table__num {
        width: 42px;
    }

    .bank-stock-table th:nth-child(2),
    .bank-stock-table td:nth-child(2) {
        width: 84px;
    }

    .bank-stock-table th:nth-child(3),
    .bank-stock-table td:nth-child(3) {
        width: 30%;
    }

    .bank-stock-table th:nth-child(4),
    .bank-stock-table td:nth-child(4),
    .bank-stock-table th:nth-child(6),
    .bank-stock-table td:nth-child(6),
    .bank-stock-table th:nth-child(7),
    .bank-stock-table td:nth-child(7) {
        width: 82px;
    }

    .bank-stock-table th:nth-child(5),
    .bank-stock-table td:nth-child(5) {
        width: 62px;
    }

    .bank-stock-table th:nth-child(8),
    .bank-stock-table td:nth-child(8) {
        width: 88px;
    }

    .bank-stock-table th:nth-child(9),
    .bank-stock-table td:nth-child(9) {
        width: 44px;
        overflow: visible;
    }

    .bank-stock-company {
        display: block;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .bank-stock-company-meta {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .bank-stock-actions {
        position: relative;
        display: inline-flex;
        justify-content: flex-end;
    }

    .bank-stock-actions__trigger {
        width: 32px;
        height: 32px;
        border: 1px solid rgba(148, 163, 184, 0.24);
        border-radius: 8px;
        background: rgba(15, 23, 42, 0.72);
        color: #e5e7eb;
        font-size: 20px;
        line-height: 1;
        padding: 0;
    }

    .bank-stock-actions__menu {
        position: fixed;
        z-index: 10020;
        min-width: 156px;
        padding: 6px;
        border: 1px solid rgba(148, 163, 184, 0.22);
        border-radius: 8px;
        background: #0f172a;
        box-shadow: 0 18px 40px rgba(2, 6, 23, 0.45);
    }

    .bank-stock-actions__menu a,
    .bank-stock-actions__menu button {
        display: block;
        width: 100%;
        padding: 8px 10px;
        border: 0;
        border-radius: 6px;
        background: transparent;
        color: #e5e7eb;
        text-align: left;
        text-decoration: none;
        font-size: 0.9rem;
    }

    .bank-stock-actions__menu a:hover,
    .bank-stock-actions__menu button:hover {
        background: rgba(59, 130, 246, 0.18);
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

        .bank-stock-table {
            font-size: 0.74rem;
        }

        .bank-stock-table th:nth-child(1),
        .bank-stock-table td:nth-child(1),
        .bank-stock-table th:nth-child(4),
        .bank-stock-table td:nth-child(4),
        .bank-stock-table th:nth-child(8),
        .bank-stock-table td:nth-child(8) {
            display: none;
        }

        .bank-stock-table th:nth-child(3),
        .bank-stock-table td:nth-child(3) {
            width: 42%;
        }
    }
</style>
@endsection

@push('scripts')
<script>
    function stockFilterToggle() {
        const modal = document.getElementById('stockFilterModal');
        if (!modal) {
            return;
        }

        if (modal.style.display === 'none' || modal.style.display === '') {
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        } else {
            modal.style.display = 'none';
            document.body.style.overflow = '';
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        const root = document.querySelector('[data-bank-stock-page]');
        if (!root) {
            return;
        }

        const filterModal = document.getElementById('stockFilterModal');
        filterModal?.addEventListener('click', (event) => {
            if (event.target === filterModal) {
                stockFilterToggle();
            }
        });

        const modal = root.querySelector('[data-stock-modal]');
        const openButton = root.querySelector('[data-stock-open]');
        const closeButtons = root.querySelectorAll('[data-stock-close]');
        const form = root.querySelector('[data-stock-form]');
        const methodInput = root.querySelector('[data-stock-form-method]');
        const modalTitle = root.querySelector('[data-stock-modal-title]');
        const tickerInput = modal?.querySelector('input[name="ticker"]');
        const menuToggles = root.querySelectorAll('[data-stock-menu-toggle]');
        const editButtons = root.querySelectorAll('[data-stock-edit]');

        if (modal && !modal.hidden) {
            document.body.style.overflow = 'hidden';
        }

        const closeMenus = (exceptMenu = null) => {
            root.querySelectorAll('[data-stock-menu]').forEach((menu) => {
                if (menu !== exceptMenu) {
                    menu.hidden = true;
                    menu.style.top = '';
                    menu.style.left = '';
                }
            });
        };

        const positionMenu = (button, menu) => {
            menu.hidden = false;

            const buttonRect = button.getBoundingClientRect();
            const menuRect = menu.getBoundingClientRect();
            const viewportPadding = 8;
            const preferredTop = buttonRect.bottom + 6;
            const top = preferredTop + menuRect.height <= window.innerHeight - viewportPadding
                ? preferredTop
                : Math.max(viewportPadding, buttonRect.top - menuRect.height - 6);
            const left = Math.max(
                viewportPadding,
                Math.min(window.innerWidth - menuRect.width - viewportPadding, buttonRect.right - menuRect.width)
            );

            menu.style.top = `${top}px`;
            menu.style.left = `${left}px`;
        };

        const setFormMode = (mode, stock = null, updateUrl = '') => {
            if (!form || !methodInput) {
                return;
            }

            form.action = mode === 'edit' ? updateUrl : form.dataset.stockStoreUrl;
            methodInput.value = mode === 'edit' ? 'PUT' : 'POST';
            if (modalTitle) {
                modalTitle.textContent = mode === 'edit' ? 'Редактировать акцию' : 'Добавить акцию';
            }

            form.querySelectorAll('input[name]:not([name="_token"]):not([name="_method"])').forEach((input) => {
                input.value = stock ? (stock[input.name] ?? '') : '';
            });
        };

        const openModal = (mode = 'create', stock = null, updateUrl = '') => {
            setFormMode(mode === 'edit' ? 'edit' : 'create', stock, updateUrl);
            modal.hidden = false;
            document.body.style.overflow = 'hidden';
            setTimeout(() => tickerInput?.focus(), 0);
        };
        const closeModal = () => {
            modal.hidden = true;
            document.body.style.overflow = '';
        };

        openButton?.addEventListener('click', () => openModal('create'));
        closeButtons.forEach((button) => button.addEventListener('click', closeModal));
        menuToggles.forEach((button) => {
            button.addEventListener('click', (event) => {
                event.stopPropagation();
                const menu = button.closest('.bank-stock-actions')?.querySelector('[data-stock-menu]');
                if (!menu) {
                    return;
                }

                const shouldOpen = menu.hidden;
                closeMenus(menu);
                if (shouldOpen) {
                    positionMenu(button, menu);
                } else {
                    menu.hidden = true;
                    menu.style.top = '';
                    menu.style.left = '';
                }
            });
        });
        editButtons.forEach((button) => {
            button.addEventListener('click', () => {
                const stock = JSON.parse(button.dataset.stock || '{}');
                closeMenus();
                openModal('edit', stock, button.dataset.stockUpdateUrl || '');
            });
        });
        root.querySelectorAll('[data-stock-delete-form]').forEach((deleteForm) => {
            deleteForm.addEventListener('submit', (event) => {
                if (!window.confirm('Удалить акцию из анализа?')) {
                    event.preventDefault();
                }
            });
        });
        document.addEventListener('click', () => closeMenus());
        window.addEventListener('resize', () => closeMenus());
        window.addEventListener('scroll', () => closeMenus(), true);
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && filterModal && filterModal.style.display === 'flex') {
                stockFilterToggle();
            }
            if (event.key === 'Escape' && modal && !modal.hidden) {
                closeModal();
            }
            if (event.key === 'Escape') {
                closeMenus();
            }
        });
    });
</script>
@endpush
