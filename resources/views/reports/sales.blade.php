@extends('home')

@section('title', 'Звіт по продажах')
@section('header_actions')
    @include('partials.report_panel')
@endsection

@section('content')
<div class="container mt-4 reports-page" data-bs-theme="dark">
    @include('reports.period_form', [
        'periodFormAction' => route('reports.sales'),
        'periodResetUrl' => route('reports.sales'),
    ])

    <div class="card shadow-sm mb-4 bg-transparent border-secondary bg-opacity-10">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <div>
                    <h3 class="mb-1 text-light">Операційний звіт по продажах</h3>
                    <div class="text-muted small">Період: {{ $monthLabel }}</div>
                </div>
                <div class="text-muted small">Виручка, кількість продажів, середній чек та конверсія</div>
            </div>

            <div class="row g-3">
                <div class="col-md-3">
                    <div class="rounded border p-3 h-100">
                        <div class="text-muted small mb-1">Виручка</div>
                        <div class="fs-4 fw-bold text-primary">{{ number_format((float) $salesRevenueTotal, 2, '.', ' ') }} грн</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="rounded border p-3 h-100">
                        <div class="text-muted small mb-1">Кількість продажів</div>
                        <div class="fs-4 fw-bold text-light">{{ (int) $salesDocsCount }}</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="rounded border p-3 h-100">
                        <div class="text-muted small mb-1">Середній чек</div>
                        <div class="fs-4 fw-bold text-success">{{ number_format((float) $averageCheck, 2, '.', ' ') }} грн</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="rounded border p-3 h-100">
                        <div class="text-muted small mb-1">Конверсія замовлень у продаж</div>
                        <div class="fs-4 fw-bold text-warning">{{ number_format((float) $conversionRate, 1, '.', ' ') }}%</div>
                        <div class="text-muted small mt-1">Для онлайн-замовлень через ZOUT</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card shadow-sm h-100 bg-transparent border-secondary">
                <div class="card-body">
                    <div class="text-muted small mb-1">Продано одиниць</div>
                    <div class="fs-3 fw-bold text-secondary">{{ number_format((float) $soldUnitsTotal, 3, '.', ' ') }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm h-100 bg-transparent border-secondary">
                <div class="card-body">
                    <div class="text-muted small mb-1">Замовлень у періоді</div>
                    <div class="fs-3 fw-bold text-light">{{ (int) $salesOrdersCount }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm h-100 bg-transparent border-secondary">
                <div class="card-body">
                    <div class="text-muted small mb-1">Середня виручка на день</div>
                    <div class="fs-3 fw-bold text-primary">
                        {{ number_format(($salesByDay ?? collect())->count() > 0 ? ((float) $salesRevenueTotal / max(($salesByDay ?? collect())->count(), 1)) : 0, 2, '.', ' ') }} грн
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card shadow-sm h-100 bg-transparent border-secondary">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                        <h4 class="card-title mb-0 text-light">По днях</h4>
                        <div class="text-muted small">Оперативний зріз</div>
                    </div>
                    @if(($salesByDay ?? collect())->isEmpty())
                    <div class="text-muted">Продажів за період не знайдено.</div>
                    @else
                    <div class="table-responsive">
                        <table class="table table-sm table-dark table-hover align-middle mb-0 bg-transparent">
                            <thead class="table-dark">
                                <tr>
                                    <th>Період</th>
                                    <th class="text-end">Продажів</th>
                                    <th class="text-end">Виручка</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($salesByDay as $bucket)
                                <tr>
                                    <td>{{ $bucket->label }}</td>
                                    <td class="text-end">{{ (int) $bucket->sales_count }}</td>
                                    <td class="text-end fw-semibold">{{ number_format((float) $bucket->revenue_sum, 2, '.', ' ') }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow-sm h-100 bg-transparent border-secondary">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                        <h4 class="card-title mb-0 text-light">По тижнях</h4>
                        <div class="text-muted small">Ритм продажів</div>
                    </div>
                    @if(($salesByWeek ?? collect())->isEmpty())
                    <div class="text-muted">Тижневих даних за період немає.</div>
                    @else
                    <div class="table-responsive">
                        <table class="table table-sm table-dark table-hover align-middle mb-0 bg-transparent">
                            <thead class="table-dark">
                                <tr>
                                    <th>Період</th>
                                    <th class="text-end">Продажів</th>
                                    <th class="text-end">Сер. чек</th>
                                    <th class="text-end">Виручка</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($salesByWeek as $bucket)
                                <tr>
                                    <td>{{ $bucket->label }}</td>
                                    <td class="text-end">{{ (int) $bucket->sales_count }}</td>
                                    <td class="text-end">{{ number_format((float) $bucket->avg_check, 2, '.', ' ') }}</td>
                                    <td class="text-end fw-semibold">{{ number_format((float) $bucket->revenue_sum, 2, '.', ' ') }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow-sm h-100 bg-transparent border-secondary">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                        <h4 class="card-title mb-0 text-light">По місяцях</h4>
                        <div class="text-muted small">План-факт динаміка</div>
                    </div>
                    @if(($salesByMonth ?? collect())->isEmpty())
                    <div class="text-muted">Місячних даних за період немає.</div>
                    @else
                    <div class="table-responsive">
                        <table class="table table-sm table-dark table-hover align-middle mb-0 bg-transparent">
                            <thead class="table-dark">
                                <tr>
                                    <th>Період</th>
                                    <th class="text-end">Продажів</th>
                                    <th class="text-end">Сер. чек</th>
                                    <th class="text-end">Виручка</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($salesByMonth as $bucket)
                                <tr>
                                    <td>{{ $bucket->label }}</td>
                                    <td class="text-end">{{ (int) $bucket->sales_count }}</td>
                                    <td class="text-end">{{ number_format((float) $bucket->avg_check, 2, '.', ' ') }}</td>
                                    <td class="text-end fw-semibold text-primary">{{ number_format((float) $bucket->revenue_sum, 2, '.', ' ') }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
