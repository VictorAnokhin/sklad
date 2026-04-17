@extends('home')

@section('title', 'Валова прибуток')
@section('header_actions')
    @include('partials.report_panel')
@endsection

@section('content')
<div class="container mt-4" data-bs-theme="dark">
    @include('reports.period_form', [
        'periodFormAction' => route('reports.grossprofit'),
        'periodResetUrl' => route('reports.grossprofit'),
    ])

    <div class="card shadow-sm mb-4 bg-transparent border-secondary bg-opacity-10">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <div>
                    <h3 class="mb-1 text-light">Звіт по валовій прибутку</h3>
                    <div class="text-muted small">Період: {{ $monthLabel }}</div>
                </div>
                <div class="text-muted small">Маржа по товарах, націнка та вплив знижок</div>
            </div>

            <div class="row g-3">
                <div class="col-md-3"><div class="rounded border p-3 h-100"><div class="text-muted small mb-1">Виручка</div><div class="fs-4 fw-bold text-primary">{{ number_format((float) $revenueTotal, 2, '.', ' ') }} грн</div></div></div>
                <div class="col-md-3"><div class="rounded border p-3 h-100"><div class="text-muted small mb-1">Собівартість</div><div class="fs-4 fw-bold text-light">{{ number_format((float) $costTotal, 2, '.', ' ') }} грн</div></div></div>
                <div class="col-md-3"><div class="rounded border p-3 h-100"><div class="text-muted small mb-1">Валова прибуток</div><div class="fs-4 fw-bold {{ $grossProfitTotal >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format((float) $grossProfitTotal, 2, '.', ' ') }} грн</div></div></div>
                <div class="col-md-3"><div class="rounded border p-3 h-100"><div class="text-muted small mb-1">Знижки по замовленнях</div><div class="fs-4 fw-bold text-warning">{{ number_format((float) $discountDocsTotal, 2, '.', ' ') }} грн</div></div></div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm mb-4 bg-transparent border-secondary">
        <div class="card-body">
            <h4 class="card-title mb-3 text-light">Маржа по товарах</h4>
            <div class="table-responsive">
                <table class="table table-sm table-dark table-hover align-middle mb-0 bg-transparent">
                    <thead class="table-dark">
                        <tr>
                            <th>Товар</th>
                            <th>Код</th>
                            <th class="text-end">Продано</th>
                            <th class="text-end">Виручка</th>
                            <th class="text-end">Собівартість</th>
                            <th class="text-end">Валова прибуток</th>
                            <th class="text-end">Маржа</th>
                            <th class="text-end">Націнка</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($byProduct as $item)
                        <tr class="{{ $item->gross_profit < 0 ? 'table-danger' : '' }}">
                            <td>{{ $item->product_name }}</td>
                            <td>{{ $item->pnum }}</td>
                            <td class="text-end">{{ number_format((float) $item->qty, 3, '.', ' ') }}</td>
                            <td class="text-end">{{ number_format((float) $item->revenue, 2, '.', ' ') }}</td>
                            <td class="text-end">{{ number_format((float) $item->cost, 2, '.', ' ') }}</td>
                            <td class="text-end {{ $item->gross_profit >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format((float) $item->gross_profit, 2, '.', ' ') }}</td>
                            <td class="text-end">{{ number_format((float) $item->gross_margin, 1, '.', ' ') }}%</td>
                            <td class="text-end">{{ number_format((float) $item->markup_percent, 1, '.', ' ') }}%</td>
                        </tr>
                        @empty
                        <tr><td colspan="8" class="text-muted">Немає даних.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <h4 class="card-title mb-0 text-light">Вплив знижок</h4>
                <div class="text-muted small">Проведені продажі з прив’язаними замовленнями ZOUT</div>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-dark table-hover align-middle mb-0 bg-transparent">
                    <thead class="table-dark">
                        <tr>
                            <th>Дата</th>
                            <th>№ замовлення</th>
                            <th>Клієнт</th>
                            <th class="text-end">Сума</th>
                            <th class="text-end">Знижка</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($discountDocs as $item)
                        <tr>
                            <td>{{ $item->data }}</td>
                            <td>{{ $item->num }}</td>
                            <td>{{ $item->client_name ?: '—' }}</td>
                            <td class="text-end">{{ number_format((float) $item->summa, 2, '.', ' ') }}</td>
                            <td class="text-end fw-semibold text-warning">{{ number_format((float) $item->discount, 2, '.', ' ') }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-muted">Замовлень зі знижками за період не знайдено.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
