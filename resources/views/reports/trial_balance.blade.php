@extends('home')

@section('title', 'Оборотка')
@section('header_actions')
    @include('partials.report_panel')
@endsection

@section('content')
<div class="container mt-4" data-bs-theme="dark">
    @include('reports.period_form', [
        'periodFormAction' => route('reports.trialbalance'),
        'periodResetUrl' => route('reports.trialbalance'),
    ])

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <div>
                    <h3 class="mb-1 text-light">Оборотно-сальдовая ведомость</h3>
                    <div class="text-muted small">{{ $periodLabel }}</div>
                </div>
            </div>

            @if(($rows ?? collect())->isEmpty())
            <div class="text-muted">За выбранный период бухгалтерских движений не найдено.</div>
            @else
            <div class="table-responsive">
                <table class="table table-sm table-bordered align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>Счет</th>
                            <th>Название</th>
                            <th class="text-end">Сальдо нач. Дт</th>
                            <th class="text-end">Сальдо нач. Кт</th>
                            <th class="text-end">Оборот Дт</th>
                            <th class="text-end">Оборот Кт</th>
                            <th class="text-end">Сальдо кон. Дт</th>
                            <th class="text-end">Сальдо кон. Кт</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rows as $row)
                        <tr>
                            <td class="fw-semibold">{{ $row->code }}</td>
                            <td>{{ $row->name }}</td>
                            <td class="text-end">{{ number_format((float) $row->opening_balance_debit, 2, '.', ' ') }}</td>
                            <td class="text-end">{{ number_format((float) $row->opening_balance_credit, 2, '.', ' ') }}</td>
                            <td class="text-end">{{ number_format((float) $row->period_debit, 2, '.', ' ') }}</td>
                            <td class="text-end">{{ number_format((float) $row->period_credit, 2, '.', ' ') }}</td>
                            <td class="text-end">{{ number_format((float) $row->closing_balance_debit, 2, '.', ' ') }}</td>
                            <td class="text-end">{{ number_format((float) $row->closing_balance_credit, 2, '.', ' ') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="table-light fw-semibold">
                            <td colspan="2">Итого</td>
                            <td class="text-end">{{ number_format((float) ($totals['opening_debit'] ?? 0), 2, '.', ' ') }}</td>
                            <td class="text-end">{{ number_format((float) ($totals['opening_credit'] ?? 0), 2, '.', ' ') }}</td>
                            <td class="text-end">{{ number_format((float) ($totals['period_debit'] ?? 0), 2, '.', ' ') }}</td>
                            <td class="text-end">{{ number_format((float) ($totals['period_credit'] ?? 0), 2, '.', ' ') }}</td>
                            <td class="text-end">{{ number_format((float) ($totals['closing_debit'] ?? 0), 2, '.', ' ') }}</td>
                            <td class="text-end">{{ number_format((float) ($totals['closing_credit'] ?? 0), 2, '.', ' ') }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
