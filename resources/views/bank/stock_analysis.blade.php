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
    $dividendFrequencyOptions = [
        '' => 'Все',
        'never' => 'Никогда',
        'month' => 'Месяц',
        'quarter' => 'Квартал',
        'year' => 'Год',
    ];
    $defaultMetricGroups = [
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
    $metricGroups = $stockFormParameterGroups ?? $defaultMetricGroups;
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
        <div class="glass-card" style="width:700px; max-width:90vw; max-height:80vh; overflow:visible; position:relative; margin:0 auto; padding:24px;">
            <div onclick="stockFilterToggle()" style="position:absolute; top:12px; right:16px; cursor:pointer; font-size:1.5rem; color:var(--muted-foreground); transition:color 0.2s; z-index:10;">✕</div>

            <h3 style="margin:0 0 16px 0; color:var(--foreground); font-family:var(--header); font-size:1.25rem;">🔍 Фильтр акций</h3>

            <form action="{{ route('bank.stock-analysis') }}" method="get" name="stockfilterform">
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
                    <div>
                        <label style="display:block; margin-bottom:4px; font-size:0.85rem;">Sector</label>
                        <div class="bank-stock-filter-field" data-stock-filter-combobox>
                            <input type="text"
                                   name="sector"
                                   autocomplete="off"
                                   placeholder="Выберите или введите сектор"
                                   value="{{ $stockFilters['sector'] ?? '' }}"
                                   data-stock-filter-input
                                   style="width:100%; padding:8px 12px; font-size:0.9rem;">
                            <div class="bank-stock-filter-options" data-stock-filter-options hidden>
                                @foreach(($stockFilterOptions['sector'] ?? collect()) as $option)
                                    <button type="button" data-stock-filter-option data-value="{{ $option }}">{{ $option }}</button>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div>
                        <label style="display:block; margin-bottom:4px; font-size:0.85rem;">Industry</label>
                        <div class="bank-stock-filter-field" data-stock-filter-combobox>
                            <input type="text"
                                   name="industry"
                                   autocomplete="off"
                                   placeholder="Выберите или введите индустрию"
                                   value="{{ $stockFilters['industry'] ?? '' }}"
                                   data-stock-filter-input
                                   style="width:100%; padding:8px 12px; font-size:0.9rem;">
                            <div class="bank-stock-filter-options" data-stock-filter-options hidden>
                                @foreach(($stockFilterOptions['industry'] ?? collect()) as $option)
                                    <button type="button" data-stock-filter-option data-value="{{ $option }}">{{ $option }}</button>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div>
                        <label style="display:block; margin-bottom:4px; font-size:0.85rem;">Country</label>
                        <div class="bank-stock-filter-field" data-stock-filter-combobox>
                            <input type="text"
                                   name="country"
                                   autocomplete="off"
                                   placeholder="Выберите или введите страну"
                                   value="{{ $stockFilters['country'] ?? '' }}"
                                   data-stock-filter-input
                                   style="width:100%; padding:8px 12px; font-size:0.9rem;">
                            <div class="bank-stock-filter-options" data-stock-filter-options hidden>
                                @foreach(($stockFilterOptions['country'] ?? collect()) as $option)
                                    <button type="button" data-stock-filter-option data-value="{{ $option }}">{{ $option }}</button>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div>
                        <label style="display:block; margin-bottom:4px; font-size:0.85rem;">Платит дивиденды</label>
                        <select name="dividend_frequency" style="width:100%; padding:8px 12px; font-size:0.9rem;">
                            @foreach($dividendFrequencyOptions as $value => $label)
                                <option value="{{ $value }}" @selected(($stockFilters['dividend_frequency'] ?? '') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
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
            <div class="bank-stock-table-header-actions">
                <a class="btn btn-sm btn-outline-light" href="{{ route('bank.stock-analysis.parameters') }}">Параметры</a>
                <button type="button" class="btn btn-sm btn-primary" data-stock-open>Добавить акцию</button>
            </div>
        </div>
        <div class="bank-stock-table-wrap">
            <table class="table table-dark table-hover table-sm align-middle bank-table bank-stock-table">
                <thead>
                    <tr>
                        <th class="bank-table__num">No.</th>
                        <th>Ticker</th>
                        <th>Company</th>
                        <th class="text-end">Market</th>
                        <th class="text-end">Параметры</th>
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
                            $latestChange = $stockChanges[(int) $stock->id] ?? ['date' => '', 'fields' => []];
                            $changedFields = $latestChange['fields'] ?? [];
                            $isChanged = fn (array|string $fields) => collect((array) $fields)->intersect($changedFields)->isNotEmpty();
                        @endphp
                        <tr>
                            <td class="bank-table__num bank-mono">{{ ($stocks->firstItem() ?? 1) + $loop->index }}</td>
                            <td class="{{ $isChanged('ticker') ? 'bank-stock-cell--changed' : '' }}"><span class="bank-pill bank-pill--currency">{{ $stock->ticker }}</span></td>
                            <td class="{{ $isChanged(['company', 'sector', 'industry', 'country']) ? 'bank-stock-cell--changed' : '' }}">
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
                            <td class="text-end bank-mono {{ $isChanged(['market', 'market_cap']) ? 'bank-stock-cell--changed' : '' }}">{{ $stock->market ?: '—' }}</td>
                            <td class="text-end bank-mono bank-stock-params-cell {{ $isChanged('pe') ? 'bank-stock-cell--changed' : '' }}">
                                @if(!empty($stockTableMultipliers[(int) $stock->id] ?? []))
                                    {{ implode(', ', $stockTableMultipliers[(int) $stock->id]) }}
                                @else
                                    P/E: {{ $stock->pe ?: '—' }}
                                @endif
                            </td>
                            <td class="text-end bank-mono {{ $isChanged('price') ? 'bank-stock-cell--changed' : '' }}">
                                {{ $stock->price ?: '—' }}
                                @if(($latestChange['date'] ?? '') && $changedFields !== [])
                                    <div class="bank-stock-change-note">Изм. {{ $latestChange['date'] }}</div>
                                @endif
                            </td>
                            <td class="text-end bank-mono {{ $changeClass }} {{ $isChanged('change_percent') ? 'bank-stock-cell--changed' : '' }}">{{ $change ?: '—' }}</td>
                            <td class="text-end bank-mono {{ $isChanged('volume') ? 'bank-stock-cell--changed' : '' }}">{{ $stock->volume ?: '—' }}</td>
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
                                                data-stock-pull-url="{{ route('bank.stock-analysis.adapter.pull', $stock->id) }}"
                                                data-stock-adapter-url="{{ route('bank.stock-analysis.adapter.update', $stock->id) }}"
                                                data-stock-snapshot-date="{{ $latestChange['date'] ?: now()->toDateString() }}"
                                                data-stock-json="{{ base64_encode(json_encode($stock, JSON_UNESCAPED_UNICODE)) }}">
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
        @if($stocks->hasPages())
            <div class="bank-stock-pagination">
                {{ $stocks->links() }}
            </div>
        @endif
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
                <textarea name="adapter_config" data-stock-adapter-config-field hidden>{{ old('adapter_config') }}</textarea>
                <input type="hidden" name="net_debt_ebitda" value="{{ old('net_debt_ebitda') }}">
                <input type="hidden" name="roe" value="{{ old('roe') }}">
                <input type="hidden" name="roic" value="{{ old('roic') }}">
                <div class="bank-stock-adapter-row">
                    <label>
                        <span>Дата</span>
                        <input type="date" name="snapshot_date" value="{{ old('snapshot_date', now()->toDateString()) }}" data-stock-snapshot-date-field>
                    </label>
                    <label>
                        <span>Адаптер</span>
                        <select name="adapter" data-stock-adapter-select>
                            <option value="manual">Manual</option>
                            <option value="finviz_elite">Finviz Elite API</option>
                            <option value="fmp">Financial Modeling Prep</option>
                            <option value="finnhub">Finnhub</option>
                        </select>
                    </label>
                    <button type="button" class="btn btn-sm btn-outline-light" data-stock-pull disabled>Подтянуть данные</button>
                    <button type="button" class="btn btn-sm btn-outline-light" data-stock-adapter-settings disabled>Настройки адаптера</button>
                    <div class="bank-meta" data-stock-pull-status></div>
                </div>
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
                        <label>
                            <span>Платит дивиденды</span>
                            <select name="dividend_frequency">
                                @foreach($dividendFrequencyOptions as $value => $label)
                                    <option value="{{ $value }}" @selected(old('dividend_frequency', '') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </label>
                    </div>
                </div>

                @foreach($metricGroups as $groupTitle => $fields)
                    <div class="bank-stock-form-section">
                        <div class="bank-label">{{ $groupTitle }}</div>
                        <div class="bank-form-grid">
                            @foreach($fields as [$name, $label, $placeholder])
                                @php($parameterDescription = trim((string) $placeholder))
                                <label>
                                    <span class="bank-stock-form-label">
                                        {{ $label }}
                                        @if($parameterDescription !== '')
                                            <button type="button"
                                                    class="bank-stock-form-info"
                                                    data-stock-form-info
                                                    aria-label="Описание параметра {{ $label }}">
                                                i
                                                <span>{{ $parameterDescription }}</span>
                                            </button>
                                        @endif
                                    </span>
                                    <input type="text" name="{{ $name }}" value="{{ old($name) }}" placeholder="{{ Str::limit($placeholder, 90) }}">
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

    <div class="bank-modal" data-stock-adapter-modal hidden>
        <div class="bank-modal__backdrop" data-stock-adapter-close></div>
        <div class="bank-modal__dialog bank-stock-adapter-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="stockAdapterModalTitle">
            <div class="bank-modal__header">
                <div>
                    <div class="bank-label">Адаптер</div>
                    <h2 id="stockAdapterModalTitle">Настройки адаптера</h2>
                    <div class="bank-meta">Настройки сохраняются для текущей акции и используются при подтягивании данных.</div>
                </div>
                <button type="button" class="bank-modal__close" data-stock-adapter-close aria-label="Закрыть">×</button>
            </div>
            <form method="POST" class="bank-requisites-form" data-stock-adapter-form>
                @csrf
                <label>
                    <span>Адаптер</span>
                    <select name="adapter" data-stock-adapter-modal-select>
                        <option value="manual">Manual</option>
                        <option value="finviz_elite">Finviz Elite API</option>
                        <option value="fmp">Financial Modeling Prep</option>
                        <option value="finnhub">Finnhub</option>
                    </select>
                </label>
                <label>
                    <span>Adapter config JSON</span>
                    <textarea name="adapter_config" data-stock-adapter-modal-config rows="8" placeholder='{"api_key":"...","base_url":"..."}'></textarea>
                </label>
                <div class="bank-modal__actions">
                    <button type="button" class="btn btn-secondary" data-stock-adapter-close>Отмена</button>
                    <button type="submit" class="btn btn-primary">Сохранить настройки</button>
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

    .bank-stock-table-header-actions {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
        justify-content: flex-end;
    }

    .bank-stock-filter-field {
        position: relative;
    }

    .bank-stock-filter-options {
        position: absolute;
        top: calc(100% + 4px);
        left: 0;
        right: 0;
        z-index: 10001;
        max-height: 180px;
        overflow-y: auto;
        padding: 4px;
        border: 1px solid rgba(255, 255, 255, 0.16);
        border-radius: 8px;
        background: #101827;
        box-shadow: 0 16px 32px rgba(0, 0, 0, 0.35);
    }

    .bank-stock-filter-options[hidden] {
        display: none;
    }

    .bank-stock-filter-options button {
        display: block;
        width: 100%;
        min-height: 34px;
        padding: 7px 10px;
        border: 0;
        border-radius: 6px;
        background: transparent;
        color: var(--foreground);
        font: inherit;
        font-size: 0.88rem;
        text-align: left;
        cursor: pointer;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .bank-stock-filter-options button:hover,
    .bank-stock-filter-options button:focus {
        outline: none;
        background: rgba(251, 191, 36, 0.14);
        color: #fbbf24;
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
        width: 26%;
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
        width: 130px;
        overflow: visible;
        white-space: normal;
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

    .bank-stock-pagination {
        display: flex;
        justify-content: center;
        margin-top: 16px;
    }

    .bank-stock-pagination nav {
        width: 100%;
    }

    .bank-stock-pagination .pagination {
        justify-content: center;
        gap: 8px;
        margin-bottom: 0;
        flex-wrap: wrap;
    }

    .bank-stock-pagination .page-link {
        min-width: 38px;
        height: 38px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 1px solid rgba(148, 163, 184, .28);
        border-radius: 10px !important;
        background: rgba(15, 23, 42, .76);
        color: #d7dee9;
        box-shadow: none;
        font-weight: 600;
    }

    .bank-stock-pagination svg {
        width: 16px;
        height: 16px;
        flex: 0 0 16px;
    }

    .bank-stock-pagination .page-link:hover,
    .bank-stock-pagination .page-link:focus {
        border-color: rgba(251, 191, 36, .72);
        background: rgba(251, 191, 36, .13);
        color: #fbbf24;
    }

    .bank-stock-pagination .page-item.active .page-link {
        border-color: #fbbf24;
        background: #fbbf24;
        color: #111827;
    }

    .bank-stock-pagination .page-item.disabled .page-link {
        border-color: rgba(148, 163, 184, .16);
        background: rgba(15, 23, 42, .48);
        color: rgba(203, 213, 225, .42);
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

    .bank-stock-cell--changed {
        background: rgba(251, 191, 36, 0.12) !important;
        box-shadow: inset 0 0 0 1px rgba(251, 191, 36, 0.22);
    }

    .bank-stock-change-note {
        margin-top: 3px;
        color: #fbbf24;
        font-size: 0.68rem;
        font-weight: 700;
    }

    .bank-stock-params-cell {
        color: rgba(226, 232, 240, 0.86);
        font-size: 0.72rem;
        line-height: 1.35;
        overflow-wrap: anywhere;
    }

    .bank-stock-form-label {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        max-width: 100%;
        position: relative;
    }

    .bank-stock-form-info {
        position: relative;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 auto;
        width: 17px;
        min-width: 17px;
        max-width: 17px;
        height: 17px;
        min-height: 17px;
        max-height: 17px;
        padding: 0;
        border: 1px solid rgba(251, 191, 36, 0.42);
        border-radius: 50%;
        box-sizing: border-box;
        background: rgba(251, 191, 36, 0.12);
        color: #fbbf24;
        font-size: 0.68rem;
        font-weight: 900;
        line-height: 1;
        cursor: pointer;
    }

    .bank-stock-form-info > span {
        position: absolute;
        z-index: 10040;
        left: 50%;
        bottom: calc(100% + 8px);
        width: min(260px, 72vw);
        padding: 8px 10px;
        border: 1px solid rgba(148, 163, 184, 0.22);
        border-radius: 8px;
        background: #101827;
        box-shadow: 0 16px 32px rgba(0, 0, 0, 0.35);
        color: #e5e7eb;
        font-size: 0.76rem;
        font-weight: 600;
        line-height: 1.35;
        text-align: left;
        text-transform: none;
        white-space: normal;
        transform: translateX(-50%);
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
    }

    .bank-stock-form-info.is-open > span {
        opacity: 1;
        visibility: visible;
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
        width: min(760px, calc(100vw - 28px));
        max-height: min(760px, calc(100vh - 28px));
        overflow: auto;
    }

    .bank-stock-modal__dialog .bank-modal__header {
        padding: 14px 16px 10px;
        gap: 12px;
    }

    .bank-stock-modal__dialog .bank-label {
        margin-bottom: 4px;
        font-size: 0.7rem;
    }

    .bank-stock-modal__dialog h2 {
        margin: 0;
        font-size: 1.15rem;
        line-height: 1.2;
    }

    .bank-stock-modal__dialog .bank-meta {
        margin-top: 4px;
        font-size: 0.76rem;
        line-height: 1.25;
    }

    .bank-stock-modal__dialog .bank-requisites-form {
        padding: 0 16px 14px;
    }

    .bank-stock-modal__dialog .bank-form-grid {
        grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
        gap: 8px 10px;
    }

    .bank-stock-modal__dialog .bank-form-grid label {
        gap: 4px;
    }

    .bank-stock-modal__dialog .bank-form-grid label span {
        font-size: 0.74rem;
    }

    .bank-stock-modal__dialog input[type="text"] {
        min-height: 34px;
        padding: 7px 9px;
        font-size: 0.84rem;
    }

    .bank-stock-modal__dialog input[type="date"] {
        min-height: 34px;
        padding: 7px 9px;
        border: 1px solid rgba(148, 163, 184, 0.26);
        border-radius: 8px;
        background: rgba(15, 23, 42, 0.72);
        color: #f8fafc;
        font-size: 0.84rem;
    }

    .bank-stock-modal__dialog select,
    .bank-stock-adapter-modal__dialog select,
    .bank-stock-adapter-modal__dialog textarea {
        width: 100%;
        border: 1px solid rgba(148, 163, 184, 0.26);
        border-radius: 8px;
        background: rgba(15, 23, 42, 0.72);
        color: #f8fafc;
    }

    .bank-stock-modal__dialog select {
        min-height: 34px;
        padding: 7px 9px;
        font-size: 0.84rem;
    }

    .bank-stock-adapter-row {
        display: grid;
        grid-template-columns: 148px minmax(180px, 1fr) auto auto;
        gap: 8px;
        align-items: end;
        padding: 10px 0;
        border-top: 1px solid rgba(148, 163, 184, 0.14);
    }

    .bank-stock-adapter-row label {
        display: grid;
        gap: 4px;
        margin: 0;
    }

    .bank-stock-adapter-row label span,
    .bank-stock-adapter-modal__dialog label span {
        color: rgba(148, 163, 184, 0.9);
        font-size: 0.74rem;
        font-weight: 800;
        text-transform: uppercase;
    }

    .bank-stock-adapter-row .bank-meta {
        grid-column: 1 / -1;
        min-height: 18px;
        margin: 0;
    }

    .bank-stock-adapter-modal__dialog {
        width: min(560px, calc(100vw - 28px));
        max-height: calc(100vh - 28px);
        overflow: auto;
    }

    .bank-stock-adapter-modal__dialog .bank-requisites-form {
        display: grid;
        gap: 12px;
        padding: 0 16px 16px;
    }

    .bank-stock-adapter-modal__dialog label {
        display: grid;
        gap: 5px;
        margin: 0;
    }

    .bank-stock-adapter-modal__dialog select,
    .bank-stock-adapter-modal__dialog textarea {
        padding: 8px 10px;
        font-size: 0.86rem;
    }

    .bank-stock-adapter-modal__dialog textarea {
        min-height: 160px;
        resize: vertical;
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace;
    }

    .bank-stock-form-section {
        padding: 9px 0;
        border-top: 1px solid rgba(148, 163, 184, 0.14);
    }

    .bank-stock-form-section:first-child {
        border-top: 0;
        padding-top: 0;
    }

    .bank-stock-modal__dialog .bank-modal__actions {
        position: sticky;
        bottom: 0;
        margin-top: 10px;
        padding-top: 10px;
        background: rgba(15, 23, 42, 0.96);
    }

    .bank-stock-modal__dialog .bank-modal__actions .btn {
        min-height: 34px;
        padding: 6px 12px;
        font-size: 0.86rem;
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

        .bank-stock-modal__dialog {
            width: min(430px, calc(100vw - 18px));
            max-height: calc(100vh - 18px);
        }

        .bank-stock-modal__dialog .bank-modal__header {
            padding: 12px 12px 8px;
        }

        .bank-stock-modal__dialog .bank-requisites-form {
            padding: 0 12px 12px;
        }

        .bank-stock-modal__dialog .bank-form-grid {
            grid-template-columns: 1fr;
            gap: 7px;
        }

        .bank-stock-adapter-row {
            grid-template-columns: 1fr;
            align-items: stretch;
        }

        .bank-stock-form-section {
            padding: 8px 0;
        }

        .bank-stock-modal__dialog .bank-modal__actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
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

        const closeFilterOptionLists = (exceptList = null) => {
            root.querySelectorAll('[data-stock-filter-options]').forEach((list) => {
                if (list !== exceptList) {
                    list.hidden = true;
                }
            });
        };

        root.querySelectorAll('[data-stock-filter-combobox]').forEach((combobox) => {
            const input = combobox.querySelector('[data-stock-filter-input]');
            const list = combobox.querySelector('[data-stock-filter-options]');
            const options = Array.from(combobox.querySelectorAll('[data-stock-filter-option]'));

            if (!input || !list) {
                return;
            }

            const showMatchingOptions = () => {
                const search = input.value.trim().toLowerCase();
                let visibleCount = 0;

                options.forEach((option) => {
                    const value = (option.dataset.value || option.textContent || '').toLowerCase();
                    const visible = !search || value.includes(search);
                    option.hidden = !visible;
                    if (visible) {
                        visibleCount += 1;
                    }
                });

                list.hidden = visibleCount === 0;
            };

            input.addEventListener('focus', () => {
                closeFilterOptionLists(list);
                showMatchingOptions();
            });
            input.addEventListener('click', () => {
                closeFilterOptionLists(list);
                showMatchingOptions();
            });
            input.addEventListener('input', showMatchingOptions);

            options.forEach((option) => {
                option.addEventListener('click', () => {
                    input.value = option.dataset.value || option.textContent.trim();
                    list.hidden = true;
                    input.dispatchEvent(new Event('change', { bubbles: true }));
                });
            });
        });

        const modal = root.querySelector('[data-stock-modal]');
        const openButton = root.querySelector('[data-stock-open]');
        const closeButtons = root.querySelectorAll('[data-stock-close]');
        const form = root.querySelector('[data-stock-form]');
        const methodInput = root.querySelector('[data-stock-form-method]');
        const modalTitle = root.querySelector('[data-stock-modal-title]');
        const tickerInput = modal?.querySelector('input[name="ticker"]');
        const snapshotDateField = root.querySelector('[data-stock-snapshot-date-field]');
        const adapterSelect = root.querySelector('[data-stock-adapter-select]');
        const adapterConfigField = root.querySelector('[data-stock-adapter-config-field]');
        const pullButton = root.querySelector('[data-stock-pull]');
        const adapterSettingsButton = root.querySelector('[data-stock-adapter-settings]');
        const pullStatus = root.querySelector('[data-stock-pull-status]');
        const adapterModal = root.querySelector('[data-stock-adapter-modal]');
        const adapterModalForm = root.querySelector('[data-stock-adapter-form]');
        const adapterModalSelect = root.querySelector('[data-stock-adapter-modal-select]');
        const adapterModalConfig = root.querySelector('[data-stock-adapter-modal-config]');
        const adapterCloseButtons = root.querySelectorAll('[data-stock-adapter-close]');
        const menuToggles = root.querySelectorAll('[data-stock-menu-toggle]');
        const editButtons = root.querySelectorAll('[data-stock-edit]');
        const defaultAdapterConfigs = {
            finnhub: {
                api_key: 'd9rgeupr01qkdnrf0lmgd9rgeupr01qkdnrf0ln0',
                base_url: 'https://finnhub.io/api/v1',
            },
            fmp: {
                api_key: '0vDr9hgPu8RskbzxMVGJXBPi9eG0F6jo',
                base_url: 'https://financialmodelingprep.com/stable',
            },
        };
        let currentPullUrl = '';
        let currentAdapterUrl = '';
        let currentSnapshotDate = '';

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

            form.querySelectorAll('input[name]:not([name="_token"]):not([name="_method"]), select[name], textarea[name]').forEach((input) => {
                input.value = stock ? (stock[input.name] ?? '') : '';
            });
            if (form.elements?.dividend_frequency) {
                form.elements.dividend_frequency.value = stock?.dividend_frequency || '';
            }
            if (adapterSelect) {
                adapterSelect.value = stock?.adapter || 'manual';
            }
            if (adapterConfigField) {
                adapterConfigField.value = stock?.adapter_config || '';
            }
            if (snapshotDateField) {
                snapshotDateField.value = mode === 'edit'
                    ? (stock?.snapshot_date || currentSnapshotDate || new Date().toISOString().slice(0, 10))
                    : new Date().toISOString().slice(0, 10);
            }
            if (pullStatus) {
                pullStatus.textContent = '';
            }
            if (pullButton) {
                pullButton.disabled = mode !== 'edit' || !currentPullUrl;
            }
            if (adapterSettingsButton) {
                adapterSettingsButton.disabled = mode !== 'edit' || !currentAdapterUrl;
            }
        };

        const adapterConfigString = (adapter) => JSON.stringify(defaultAdapterConfigs[adapter] || {}, null, 2);
        const defaultAdapterByConfig = (configValue) => {
            const normalized = (configValue || '').trim();

            return Object.keys(defaultAdapterConfigs).find((adapter) => normalized === adapterConfigString(adapter).trim()) || '';
        };
        const ensureAdapterConfig = (adapter, force = false) => {
            if (!adapterConfigField || !defaultAdapterConfigs[adapter]) {
                return;
            }

            if (adapter === 'fmp' && adapterConfigField.value.includes('financialmodelingprep.com/api/v3')) {
                adapterConfigField.value = adapterConfigField.value.replace('https://financialmodelingprep.com/api/v3', 'https://financialmodelingprep.com/stable');
            }
            const currentDefaultAdapter = defaultAdapterByConfig(adapterConfigField.value);
            if (force || !adapterConfigField.value.trim() || (currentDefaultAdapter && currentDefaultAdapter !== adapter)) {
                adapterConfigField.value = adapterConfigString(adapter);
            }
        };

        const openModal = (mode = 'create', stock = null, updateUrl = '', pullUrl = '', adapterUrl = '', snapshotDate = '') => {
            currentPullUrl = pullUrl;
            currentAdapterUrl = adapterUrl;
            currentSnapshotDate = snapshotDate;
            setFormMode(mode === 'edit' ? 'edit' : 'create', stock, updateUrl);
            ensureAdapterConfig(adapterSelect?.value || 'manual');
            modal.hidden = false;
            document.body.style.overflow = 'hidden';
            setTimeout(() => tickerInput?.focus(), 0);
            if (mode === 'edit' && pullButton && currentPullUrl) {
                setTimeout(() => pullButton.click(), 0);
            }
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
            button.addEventListener('click', (event) => {
                event.preventDefault();
                event.stopPropagation();

                let stock = {};
                try {
                    stock = JSON.parse(atob(button.dataset.stockJson || 'e30='));
                } catch (error) {
                    console.error('Stock edit payload parse failed:', error);
                }

                closeMenus();
                openModal(
                    'edit',
                    stock,
                    button.dataset.stockUpdateUrl || '',
                    button.dataset.stockPullUrl || '',
                    button.dataset.stockAdapterUrl || '',
                    button.dataset.stockSnapshotDate || ''
                );
            });
        });
        const fillStockForm = (data) => {
            Object.entries(data || {}).forEach(([key, value]) => {
                const field = form?.elements?.[key];
                if (field && key !== '_token' && key !== '_method') {
                    field.value = value ?? '';
                }
            });
        };
        pullButton?.addEventListener('click', async () => {
            if (!currentPullUrl || !form || !adapterSelect) {
                return;
            }

            pullButton.disabled = true;
            if (pullStatus) {
                pullStatus.textContent = 'Подтягиваем данные...';
            }

            try {
                const response = await fetch(currentPullUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': form.querySelector('input[name="_token"]')?.value || '',
                    },
                    body: JSON.stringify({
                        adapter: adapterSelect.value,
                        adapter_config: adapterConfigField?.value || '',
                        snapshot_date: snapshotDateField?.value || '',
                        ticker: form.elements?.ticker?.value || '',
                    }),
                });
                const result = await response.json();
                if (!response.ok || !result.success) {
                    throw new Error(result.message || 'Не удалось подтянуть данные.');
                }

                fillStockForm(result.data || {});
                if (pullStatus) {
                    pullStatus.textContent = result.message || 'Данные подтянуты. Нажмите Сохранить, чтобы записать snapshot на выбранную дату.';
                }
            } catch (error) {
                if (pullStatus) {
                    pullStatus.textContent = error instanceof Error ? error.message : 'Ошибка подтягивания данных.';
                }
            } finally {
                pullButton.disabled = false;
            }
        });
        const openAdapterModal = () => {
            if (!adapterModal || !adapterModalForm || !currentAdapterUrl) {
                return;
            }

            adapterModalForm.action = currentAdapterUrl;
            if (adapterModalSelect && adapterSelect) {
                adapterModalSelect.value = adapterSelect.value || 'manual';
            }
            if (adapterModalConfig && adapterConfigField) {
                ensureAdapterConfig(adapterModalSelect?.value || 'manual');
                adapterModalConfig.value = adapterConfigField.value || '';
            }
            adapterModal.hidden = false;
            document.body.style.overflow = 'hidden';
        };
        const closeAdapterModal = () => {
            if (!adapterModal) {
                return;
            }
            adapterModal.hidden = true;
            document.body.style.overflow = modal && !modal.hidden ? 'hidden' : '';
        };
        adapterSettingsButton?.addEventListener('click', openAdapterModal);
        adapterSelect?.addEventListener('change', () => {
            ensureAdapterConfig(adapterSelect.value);
        });
        adapterModalSelect?.addEventListener('change', () => {
            if (!adapterModalConfig || !defaultAdapterConfigs[adapterModalSelect.value]) {
                return;
            }

            if (adapterModalSelect.value === 'fmp' && adapterModalConfig.value.includes('financialmodelingprep.com/api/v3')) {
                adapterModalConfig.value = adapterModalConfig.value.replace('https://financialmodelingprep.com/api/v3', 'https://financialmodelingprep.com/stable');
            }
            const currentDefaultAdapter = defaultAdapterByConfig(adapterModalConfig.value);
            if (!adapterModalConfig.value.trim() || (currentDefaultAdapter && currentDefaultAdapter !== adapterModalSelect.value)) {
                adapterModalConfig.value = adapterConfigString(adapterModalSelect.value);
            }
        });
        adapterCloseButtons.forEach((button) => button.addEventListener('click', closeAdapterModal));
        adapterModal?.addEventListener('click', (event) => {
            if (event.target === adapterModal) {
                closeAdapterModal();
            }
        });
        root.querySelectorAll('[data-stock-delete-form]').forEach((deleteForm) => {
            deleteForm.addEventListener('submit', (event) => {
                if (!window.confirm('Удалить акцию из анализа?')) {
                    event.preventDefault();
                }
            });
        });
        document.addEventListener('click', (event) => {
            closeMenus();
            if (!event.target.closest('[data-stock-filter-combobox]')) {
                closeFilterOptionLists();
            }
            if (!event.target.closest('[data-stock-form-info]')) {
                root.querySelectorAll('[data-stock-form-info]').forEach((button) => button.classList.remove('is-open'));
            }
        });
        root.querySelectorAll('[data-stock-form-info]').forEach((button) => {
            button.addEventListener('click', (event) => {
                event.preventDefault();
                event.stopPropagation();
                root.querySelectorAll('[data-stock-form-info]').forEach((item) => {
                    if (item !== button) {
                        item.classList.remove('is-open');
                    }
                });
                button.classList.toggle('is-open');
            });
        });
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
                closeFilterOptionLists();
                root.querySelectorAll('[data-stock-form-info]').forEach((button) => button.classList.remove('is-open'));
            }
        });
    });
</script>
@endpush
