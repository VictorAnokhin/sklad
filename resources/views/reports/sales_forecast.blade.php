@extends('home')

@section('title', 'Прогноз продаж')
@section('header_actions')
    @include('partials.report_panel')
@endsection

@section('content')
<div class="container mt-4 reports-page" data-bs-theme="dark">
    @include('reports.period_form', ['periodFormAction' => route('reports.salesforecast'), 'periodResetUrl' => route('reports.salesforecast')])

    <div class="card shadow-sm mb-4 bg-transparent border-secondary bg-opacity-10">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <div><h3 class="mb-1 text-light">Forecast продажів</h3><div class="text-muted small">Період бази: {{ $monthLabel }}</div></div>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <div class="text-muted small">Прогноз на основі середнього за 6 місяців і тренду виручки</div>
                    <a href="{{ route('reports.strategic.export', ['report' => 'salesforecast', 'format' => 'csv', 'date_from' => $dateFrom, 'date_to' => $dateTo]) }}" class="btn btn-sm btn-outline-primary">CSV</a>
                    <a href="{{ route('reports.strategic.export', ['report' => 'salesforecast', 'format' => 'xls', 'date_from' => $dateFrom, 'date_to' => $dateTo]) }}" class="btn btn-sm btn-outline-secondary">XLS</a>
                </div>
            </div>
            <div class="row g-3">
                <div class="col-md-3"><div class="rounded border p-3 h-100"><div class="text-muted small mb-1">Forecast виручки</div><div class="fs-4 fw-bold text-primary">{{ number_format((float) $forecastRevenue, 2, '.', ' ') }} грн</div></div></div>
                <div class="col-md-3"><div class="rounded border p-3 h-100"><div class="text-muted small mb-1">Forecast продажів</div><div class="fs-4 fw-bold text-light">{{ number_format((float) $forecastDocs, 0, '.', ' ') }}</div></div></div>
                <div class="col-md-3"><div class="rounded border p-3 h-100"><div class="text-muted small mb-1">Forecast одиниць</div><div class="fs-4 fw-bold text-success">{{ number_format((float) $forecastQty, 2, '.', ' ') }}</div></div></div>
                <div class="col-md-3"><div class="rounded border p-3 h-100"><div class="text-muted small mb-1">Тренд</div><div class="fs-4 fw-bold {{ $trendGrowth >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format((float) $trendGrowth, 1, '.', ' ') }}%</div></div></div>
            </div>
            <div class="row g-3 mt-1">
                <div class="col-md-4"><div class="rounded border p-3 h-100"><div class="text-muted small mb-1">Факт виручки</div><div class="fs-5 fw-bold text-light">{{ number_format((float) $actualRevenue, 2, '.', ' ') }} грн</div><div class="text-muted small mt-1">План/факт {{ number_format((float) $planFactRevenuePercent, 1, '.', ' ') }}%</div></div></div>
                <div class="col-md-4"><div class="rounded border p-3 h-100"><div class="text-muted small mb-1">Факт продажів</div><div class="fs-5 fw-bold text-light">{{ number_format((float) $actualDocs, 0, '.', ' ') }}</div><div class="text-muted small mt-1">План/факт {{ number_format((float) $planFactDocsPercent, 1, '.', ' ') }}%</div></div></div>
                <div class="col-md-4"><div class="rounded border p-3 h-100"><div class="text-muted small mb-1">Факт одиниць</div><div class="fs-5 fw-bold text-light">{{ number_format((float) $actualQty, 2, '.', ' ') }}</div><div class="text-muted small mt-1">План/факт {{ number_format((float) $planFactQtyPercent, 1, '.', ' ') }}%</div></div></div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card shadow-sm h-100 bg-transparent border-secondary">
                <div class="card-body">
                    <h4 class="card-title mb-3 text-light">Сезонність попиту</h4>
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
        <div class="col-lg-7">
            <div class="card shadow-sm h-100 bg-transparent border-secondary">
                <div class="card-body">
                    <h4 class="card-title mb-3 text-light">Forecast по сегментах</h4>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="text-muted small mb-2">Категорії</div>
                            <div class="table-responsive">
                                <table class="table table-sm table-dark table-hover align-middle mb-0 bg-transparent">
                                    <thead class="table-dark"><tr><th>Сегмент</th><th class="text-end">Forecast</th></tr></thead>
                                    <tbody>
                                        @forelse($categoryForecasts as $item)
                                        <tr><td>{{ $item->segment_name }}</td><td class="text-end fw-semibold">{{ number_format((float) $item->forecast_revenue, 2, '.', ' ') }}</td></tr>
                                        @empty
                                        <tr><td colspan="2" class="text-muted">Немає даних.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small mb-2">Канали</div>
                            <div class="table-responsive">
                                <table class="table table-sm table-dark table-hover align-middle mb-0 bg-transparent">
                                    <thead class="table-dark"><tr><th>Сегмент</th><th class="text-end">Forecast</th></tr></thead>
                                    <tbody>
                                        @forelse($channelForecasts as $item)
                                        <tr><td>{{ $item->segment_name }}</td><td class="text-end fw-semibold">{{ number_format((float) $item->forecast_revenue, 2, '.', ' ') }}</td></tr>
                                        @empty
                                        <tr><td colspan="2" class="text-muted">Немає даних.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm mt-4 bg-transparent border-secondary">
        <div class="card-body">
            <h4 class="card-title mb-3 text-light">Історія по місяцях</h4>
            <div class="table-responsive">
                <table class="table table-sm table-dark table-hover align-middle mb-0 bg-transparent">
                    <thead class="table-dark"><tr><th>Місяць</th><th class="text-end">Продажів</th><th class="text-end">Одиниць</th><th class="text-end">Виручка</th></tr></thead>
                    <tbody>
                        @forelse($history as $item)
                        <tr>
                            <td>{{ $item->month_label }}</td>
                            <td class="text-end">{{ $item->sales_docs }}</td>
                            <td class="text-end">{{ number_format((float) $item->qty, 2, '.', ' ') }}</td>
                            <td class="text-end fw-semibold">{{ number_format((float) $item->revenue, 2, '.', ' ') }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="text-muted">Недостатньо історії.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
