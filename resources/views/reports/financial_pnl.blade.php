@extends('home')

@section('title', 'P&L')
@section('header_actions')
    @include('partials.report_panel')
@endsection

@section('content')
@php
    $formatMoney = static fn ($value) => number_format((float) $value, 2, ',', ' ');
    $pnlMonths = $pnlMonths ?? [];
    $pnlRows = $pnlRows ?? [];
@endphp

<div class="container mt-4 reports-page pnl-page">
    @include('reports.period_form', [
        'periodFormAction' => route('reports.financialpnl'),
        'periodResetUrl' => route('reports.financialpnl'),
    ])

    <div class="pnl-sheet">
        <div class="pnl-report-head">
            <h1>Отчет о прибылях и убытках</h1>
            <div>За период: {{ $monthLabel }}</div>
        </div>

        <div class="table-responsive">
            <table class="pnl-table">
                <thead>
                    <tr>
                        <th></th>
                        @foreach($pnlMonths as $month)
                            <th>{{ $month['label'] }}</th>
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
                                    <td></td>
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
                            <td colspan="{{ count($pnlMonths) + 1 }}" class="pnl-table__empty">
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
        color: #000;
    }

    .pnl-sheet {
        background: #fff;
        border: 1px solid #111;
        border-radius: 0;
        padding: 18px;
        box-shadow: none;
    }

    .pnl-report-head {
        margin-bottom: 12px;
        text-align: center;
        color: #000;
    }

    .pnl-report-head h1 {
        margin: 0 0 2px;
        font-size: 1.28rem;
        font-weight: 800;
        line-height: 1.15;
    }

    .pnl-report-head div {
        font-size: 0.95rem;
        font-weight: 700;
    }

    .pnl-table {
        width: 100%;
        min-width: 760px;
        table-layout: fixed;
        border-collapse: collapse;
        background: #fff;
        color: #000;
        font-family: Arial, Helvetica, sans-serif;
        font-size: 0.86rem;
        line-height: 1.12;
    }

    .pnl-table th,
    .pnl-table td {
        border: 1px solid #111;
        padding: 2px 6px;
        vertical-align: middle;
    }

    .pnl-table th:first-child,
    .pnl-table td:first-child {
        width: 58%;
        text-align: left;
    }

    .pnl-table th {
        background: #c9c9c9;
        color: #000;
        font-weight: 700;
        text-align: right;
    }

    .pnl-table th:first-child {
        background: #fff;
    }

    .pnl-table__amount {
        text-align: right;
        font-variant-numeric: tabular-nums;
        white-space: nowrap;
    }

    .pnl-table__section-row td {
        background: #fff;
        color: #000;
        font-weight: 800;
        font-size: 0.92rem;
    }

    .pnl-table__title-row td {
        background: #fff;
        padding: 6px;
        color: #000;
        text-align: left;
        font-size: 0.92rem;
        font-weight: 800;
    }

    .pnl-table__subsection td {
        background: #fff;
        font-weight: 700;
    }

    .pnl-table__subsection td:first-child,
    .pnl-table__item td:first-child {
        padding-left: 24px;
    }

    .pnl-table__item td {
        background: #fff;
        font-weight: 400;
    }

    .pnl-table__total td {
        background: #fff;
        font-weight: 800;
    }

    .pnl-table__summary td {
        background: #fff;
        color: #000;
        border-top: 2px solid #111;
        border-bottom: 2px solid #111;
        font-weight: 800;
    }

    .pnl-table tbody tr:last-child td {
        border: 3px solid #dc2626;
    }

    .pnl-table__spacer td {
        height: 8px;
        border: 0;
        background: #fff;
        padding: 0;
    }

    .pnl-table__empty {
        padding: 14px;
        text-align: center;
        color: #6b7280;
    }

    @media (max-width: 768px) {
        .pnl-sheet {
            padding: 10px;
        }

        .pnl-table {
            font-size: 0.78rem;
        }

        .pnl-table th,
        .pnl-table td {
            padding: 2px 5px;
        }
    }
</style>
@endsection
