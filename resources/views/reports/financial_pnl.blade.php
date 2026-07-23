@extends('home')

@section('title', 'P&L')
@section('header_actions')
    @include('partials.report_panel')
@endsection

@section('content')
@php
    $formatMoney = static fn ($value) => number_format((float) $value, 0, '.', ' ') . ' грн';
    $pnlMonths = $pnlMonths ?? [];
    $pnlRows = $pnlRows ?? [];
@endphp

<div class="container mt-4 reports-page pnl-page">
    @include('reports.period_form', [
        'periodFormAction' => route('reports.financialpnl'),
        'periodResetUrl' => route('reports.financialpnl'),
    ])

    <div class="pnl-sheet">
        <div class="pnl-sheet__title">
            <span>P&amp;L</span>
            <small>{{ $monthLabel }}</small>
        </div>

        <div class="table-responsive">
            <table class="pnl-table">
                <thead>
                    <tr>
                        <th>Месяц</th>
                        @foreach($pnlMonths as $month)
                            <th class="pnl-table__amount">{{ $month['label'] }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse($pnlRows as $row)
                        @if($row['type'] === 'spacer')
                            <tr class="pnl-table__spacer">
                                <td colspan="{{ count($pnlMonths) + 1 }}"></td>
                            </tr>
                        @elseif($row['type'] === 'title')
                            <tr class="pnl-table__title-row">
                                <td colspan="{{ count($pnlMonths) + 1 }}">{{ $row['label'] }}</td>
                            </tr>
                        @elseif($row['type'] === 'section')
                            <tr class="pnl-table__section-row">
                                <td>{{ $row['label'] }}</td>
                                @foreach($pnlMonths as $month)
                                    <td class="pnl-table__amount">{{ $month['label'] }}</td>
                                @endforeach
                            </tr>
                        @elseif($row['type'] === 'subsection')
                            <tr class="pnl-table__subsection">
                                <td>{{ $row['label'] }}</td>
                                @foreach($pnlMonths as $month)
                                    <td></td>
                                @endforeach
                            </tr>
                        @else
                            <tr class="pnl-table__{{ $row['type'] }}">
                                <td>{{ $row['label'] }}</td>
                                @foreach($pnlMonths as $month)
                                    <td class="pnl-table__amount">{{ $formatMoney($row['values'][$month['key']] ?? 0) }}</td>
                                @endforeach
                            </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="{{ count($pnlMonths) + 1 }}" class="text-center text-muted py-4">
                                Данных для P&amp;L за выбранный период не найдено.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
    .pnl-page {
        color: #111827;
    }

    .pnl-sheet {
        background: #f7f7f7;
        border: 1px solid rgba(148, 163, 184, 0.25);
        border-radius: 8px;
        padding: 20px;
        box-shadow: 0 16px 40px rgba(15, 23, 42, 0.18);
    }

    .pnl-sheet__title {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 14px;
        margin-bottom: 18px;
        color: #111827;
    }

    .pnl-sheet__title span {
        min-width: 180px;
        border-radius: 10px;
        background: #4f5489;
        color: #fff;
        padding: 7px 22px;
        text-align: center;
        font-size: 1.2rem;
        font-weight: 800;
        line-height: 1.1;
    }

    .pnl-sheet__title small {
        color: #6b7280;
        font-weight: 600;
    }

    .pnl-table {
        width: 100%;
        min-width: 860px;
        table-layout: fixed;
        border-collapse: collapse;
        background: #fff;
        color: #111;
        font-size: 1rem;
    }

    .pnl-table th,
    .pnl-table td {
        border: 1px solid #d1d5db;
        padding: 9px 12px;
        vertical-align: middle;
    }

    .pnl-table th:first-child,
    .pnl-table td:first-child {
        width: 47%;
        border-right-color: #6b7280;
        font-weight: 800;
        text-align: left;
    }

    .pnl-table th {
        background: #13b800;
        color: #fff;
        font-weight: 900;
        font-size: 1.05rem;
    }

    .pnl-table__amount {
        text-align: right;
        font-variant-numeric: tabular-nums;
        white-space: nowrap;
    }

    .pnl-table__section-row td {
        background: #13b800;
        color: #fff;
        font-weight: 900;
        font-size: 1.05rem;
    }

    .pnl-table__section-row .pnl-table__amount {
        text-align: center;
    }

    .pnl-table__title-row td {
        border-left-color: transparent;
        border-right-color: transparent;
        background: #f7f7f7;
        padding: 22px 12px 14px;
        color: #111;
        text-align: center;
        font-size: 1.35rem;
        font-weight: 500;
    }

    .pnl-table__item td {
        background: #fff;
        font-weight: 600;
    }

    .pnl-table__total td {
        background: #f5f5f5;
        border-top-color: #9ca3af;
        font-weight: 900;
    }

    .pnl-table__subsection td {
        background: #d6d6d6;
        font-weight: 900;
    }

    .pnl-table__summary td {
        background: #9b9b9b;
        color: #050505;
        border-color: #9ca3af;
        font-weight: 900;
    }

    .pnl-table__spacer td {
        height: 22px;
        border: 0;
        background: #f7f7f7;
        padding: 0;
    }

    @media (max-width: 768px) {
        .pnl-sheet {
            padding: 12px;
        }

        .pnl-sheet__title {
            flex-direction: column;
            align-items: stretch;
            gap: 8px;
        }

        .pnl-sheet__title span {
            width: 100%;
            min-width: 0;
        }

        .pnl-table {
            font-size: 0.9rem;
        }

        .pnl-table th,
        .pnl-table td {
            padding: 8px 10px;
        }
    }
</style>
@endsection
