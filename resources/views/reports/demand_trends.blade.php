@extends('home')

@section('title', 'Тренды спроса')
@section('header_actions')
    @include('partials.report_panel')
@endsection

@section('content')
<div class="container mt-4 reports-page" data-bs-theme="dark">
    @include('reports.period_form', ['periodFormAction' => route('reports.demandtrends'), 'periodResetUrl' => route('reports.demandtrends')])

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card shadow-sm h-100 bg-transparent border-secondary">
                <div class="card-body">
                    <h4 class="card-title mb-3 text-light">Сезонність</h4>
                    <div class="table-responsive">
                        <table class="table table-sm table-dark table-hover align-middle mb-0 bg-transparent">
                            <thead class="table-dark"><tr><th>Місяць</th><th class="text-end">Одиниць</th><th class="text-end">Виручка</th></tr></thead>
                            <tbody>
                                @forelse($seasonality as $item)
                                <tr>
                                    <td>{{ $item->month_label }}</td>
                                    <td class="text-end">{{ number_format((float) $item->qty, 2, '.', ' ') }}</td>
                                    <td class="text-end fw-semibold">{{ number_format((float) $item->revenue, 2, '.', ' ') }}</td>
                                </tr>
                                @empty
                                <tr><td colspan="3" class="text-muted">Немає даних.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="card shadow-sm h-100 bg-transparent border-secondary">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                        <div><h3 class="mb-1 text-light">Аналіз трендів попиту</h3><div class="text-muted small">Період: {{ $monthLabel }}</div></div>
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <div class="text-muted small">Зростання/падіння попиту та стабільність по SKU</div>
                            <a href="{{ route('reports.strategic.export', ['report' => 'demandtrends', 'format' => 'csv', 'date_from' => $dateFrom, 'date_to' => $dateTo]) }}" class="btn btn-sm btn-outline-primary">CSV</a>
                            <a href="{{ route('reports.strategic.export', ['report' => 'demandtrends', 'format' => 'xls', 'date_from' => $dateFrom, 'date_to' => $dateTo]) }}" class="btn btn-sm btn-outline-secondary">XLS</a>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-dark table-hover align-middle mb-0 bg-transparent">
                            <thead class="table-dark"><tr><th>Товар</th><th>Код</th><th class="text-end">Сер. попит</th><th class="text-end">Зміна</th><th class="text-end">CV</th><th class="text-center">Тренд</th></tr></thead>
                            <tbody>
                                @forelse($items as $item)
                                <tr class="{{ $item->trend_growth > 15 ? 'table-success' : ($item->trend_growth < -15 ? 'table-danger' : '') }}">
                                    <td>{{ $item->product_name }}</td>
                                    <td>{{ $item->pnum }}</td>
                                    <td class="text-end">{{ number_format((float) $item->avg_qty, 2, '.', ' ') }}</td>
                                    <td class="text-end {{ $item->trend_growth >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format((float) $item->trend_growth, 1, '.', ' ') }}%</td>
                                    <td class="text-end">{{ number_format((float) $item->cv, 2, '.', ' ') }}</td>
                                    <td class="text-center">{{ $item->trend_label }}</td>
                                </tr>
                                @empty
                                <tr><td colspan="6" class="text-muted">Недостатньо даних для аналізу трендів.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
