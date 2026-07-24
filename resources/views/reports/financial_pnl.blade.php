@extends('home')

@section('title', 'Фінансовий P&L')
@section('header_actions')
    @include('partials.report_panel')
@endsection

@section('content')
@php
    $formatMoney = static fn ($value) => number_format((float) $value, 2, '.', ' ');
    $pnlMonths = $pnlMonths ?? [];
    $pnlRows = $pnlRows ?? [];
@endphp

<div class="container mt-4 reports-page" data-bs-theme="dark">
    @include('reports.period_form', [
        'periodFormAction' => route('reports.financialpnl'),
        'periodResetUrl' => route('reports.financialpnl'),
    ])

    <div class="card shadow-sm mb-4 bg-transparent border-secondary bg-opacity-10">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <div>
                    <h3 class="mb-1 text-light">Отчет о прибылях и убытках</h3>
                    <div class="text-muted small">Період: {{ $monthLabel }}</div>
                </div>
                <div class="text-muted small">Доходы по категориям и подкатегориям, расходы по видам платежей</div>
            </div>

            <div class="row g-3">
                <div class="col-md-2"><div class="rounded border p-3 h-100"><div class="text-muted small mb-1">Виручка</div><div class="fs-5 fw-bold text-primary">{{ $formatMoney($revenueTotal) }} грн</div></div></div>
                <div class="col-md-2"><div class="rounded border p-3 h-100"><div class="text-muted small mb-1">Переменные</div><div class="fs-5 fw-bold text-light">{{ $formatMoney($cogsTotal) }} грн</div></div></div>
                <div class="col-md-2"><div class="rounded border p-3 h-100"><div class="text-muted small mb-1">Маржинальный доход</div><div class="fs-5 fw-bold {{ $grossProfitTotal >= 0 ? 'text-success' : 'text-danger' }}">{{ $formatMoney($grossProfitTotal) }} грн</div></div></div>
                <div class="col-md-2"><div class="rounded border p-3 h-100"><div class="text-muted small mb-1">Маржа</div><div class="fs-5 fw-bold {{ $grossMarginTotal >= 0 ? 'text-warning' : 'text-danger' }}">{{ number_format((float) $grossMarginTotal, 1, '.', ' ') }}%</div></div></div>
                <div class="col-md-2"><div class="rounded border p-3 h-100"><div class="text-muted small mb-1">Постоянные</div><div class="fs-5 fw-bold text-warning">{{ $formatMoney($operatingExpensesTotal) }} грн</div></div></div>
                <div class="col-md-2"><div class="rounded border p-3 h-100"><div class="text-muted small mb-1">Чистий прибуток</div><div class="fs-5 fw-bold {{ $netProfit >= 0 ? 'text-success' : 'text-danger' }}">{{ $formatMoney($netProfit) }} грн</div></div></div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm bg-transparent border-secondary">
        <div class="card-body">
            <h4 class="card-title mb-3 text-light">Детализация P&amp;L</h4>
            <div class="table-responsive">
                <table class="table table-sm table-dark table-hover align-middle mb-0 bg-transparent financial-pnl-table">
                    <thead class="table-dark">
                        <tr>
                            <th>Статья</th>
                            <th class="text-end">% от выручки</th>
                            @foreach($pnlMonths as $month)
                                <th class="text-end">{{ $month['label'] }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($pnlRows as $row)
                            @if($row['type'] === 'spacer')
                                <tr class="financial-pnl-table__spacer">
                                    <td colspan="{{ count($pnlMonths) + 2 }}"></td>
                                </tr>
                            @elseif($row['type'] === 'title')
                                <tr class="table-active financial-pnl-table__title">
                                    <td colspan="{{ count($pnlMonths) + 2 }}" class="fw-bold text-light">{{ $row['label'] }}</td>
                                </tr>
                            @elseif($row['type'] === 'section')
                                <tr class="table-secondary financial-pnl-table__section">
                                    <td class="fw-bold text-dark">{{ $row['label'] }}</td>
                                    <td></td>
                                    @foreach($pnlMonths as $month)
                                        <td></td>
                                    @endforeach
                                </tr>
                            @elseif($row['type'] === 'subsection')
                                <tr class="financial-pnl-table__subsection">
                                    <td class="fw-semibold text-light ps-4">{{ $row['label'] }}</td>
                                    <td></td>
                                    @foreach($pnlMonths as $month)
                                        <td></td>
                                    @endforeach
                                </tr>
                            @else
                                @php
                                    $level = (int) ($row['level'] ?? 0);
                                    $groupKey = (string) ($row['group_key'] ?? '');
                                    $parentKey = (string) ($row['parent_key'] ?? '');
                                    $hasChildren = (bool) ($row['has_children'] ?? false);
                                @endphp
                                <tr class="financial-pnl-table__{{ $row['type'] }} {{ $level > 0 ? 'financial-pnl-table__child-row' : '' }}"
                                    @if($parentKey !== '') data-parent-key="{{ $parentKey }}" hidden @endif>
                                    @php
                                        $labelClass = $row['type'] === 'item'
                                            ? ($level > 0 ? 'ps-5 financial-pnl-table__nested-item' : 'ps-4')
                                            : 'fw-bold';
                                        $percent = $row['percent'] ?? null;
                                    @endphp
                                    <td class="{{ $labelClass }}">
                                        @if($hasChildren && $groupKey !== '')
                                            <button type="button"
                                                    class="financial-pnl-toggle"
                                                    data-group-key="{{ $groupKey }}"
                                                    aria-expanded="false"
                                                    aria-label="Показать подкатегории">+</button>
                                        @elseif($level > 0)
                                            <span class="financial-pnl-toggle-placeholder"></span>
                                        @endif
                                        <span>{{ $row['label'] }}</span>
                                    </td>
                                    <td class="text-end text-muted">{{ $percent === null ? '' : number_format((float) $percent, 1, '.', ' ') . '%' }}</td>
                                    @foreach($pnlMonths as $month)
                                        @php $value = (float) ($row['values'][$month['key']] ?? 0); @endphp
                                        <td class="text-end {{ $value < 0 ? 'text-danger' : '' }}">{{ $formatMoney($value) }}</td>
                                    @endforeach
                                </tr>
                            @endif
                        @empty
                            <tr>
                                <td colspan="{{ count($pnlMonths) + 2 }}" class="text-muted">Данных для P&amp;L за выбранный период не найдено.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
    .financial-pnl-table {
        min-width: 860px;
        table-layout: fixed;
    }

    .financial-pnl-table th:first-child,
    .financial-pnl-table td:first-child {
        width: 42%;
    }

    .financial-pnl-table th:nth-child(2),
    .financial-pnl-table td:nth-child(2) {
        width: 120px;
    }

    .financial-pnl-table__nested-item {
        color: rgba(255, 255, 255, 0.78);
        font-size: 0.92rem;
    }

    .financial-pnl-toggle,
    .financial-pnl-toggle-placeholder {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 22px;
        height: 22px;
        margin-right: 0.45rem;
        vertical-align: middle;
    }

    .financial-pnl-toggle {
        border: 1px solid rgba(255, 255, 255, 0.35);
        border-radius: 4px;
        background: rgba(255, 255, 255, 0.08);
        color: #fff;
        font-weight: 700;
        line-height: 1;
    }

    .financial-pnl-toggle:hover {
        background: rgba(255, 255, 255, 0.18);
    }

    .financial-pnl-table__summary td {
        font-weight: 700;
        border-top: 2px solid rgba(255, 255, 255, 0.35);
    }

    .financial-pnl-table__total td {
        font-weight: 700;
    }

    .financial-pnl-table__spacer td {
        padding: 0.35rem 0;
        border: 0;
        background: transparent;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('.financial-pnl-toggle').forEach((button) => {
            button.addEventListener('click', () => {
                const groupKey = button.dataset.groupKey || '';
                const expanded = button.getAttribute('aria-expanded') === 'true';
                if (!groupKey) return;

                document.querySelectorAll('.financial-pnl-table__child-row')
                    .forEach((row) => {
                        if (row.dataset.parentKey === groupKey) {
                            row.hidden = expanded;
                        }
                    });

                button.textContent = expanded ? '+' : '-';
                button.setAttribute('aria-expanded', expanded ? 'false' : 'true');
                button.setAttribute('aria-label', expanded ? 'Показать подкатегории' : 'Скрыть подкатегории');
            });
        });
    });
</script>
@endsection
