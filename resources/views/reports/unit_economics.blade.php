@extends('home')

@section('title', 'Unit-економіка')
@section('header_actions')
    @include('partials.report_panel')
@endsection

@section('content')
<div class="container mt-4" data-bs-theme="dark">
    @include('reports.period_form', [
        'periodFormAction' => route('reports.uniteconomics'),
        'periodResetUrl' => route('reports.uniteconomics'),
    ])

    <div class="card shadow-sm mb-4 bg-transparent border-secondary bg-opacity-10">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <div>
                    <h3 class="mb-1 text-light">Unit-економіка</h3>
                    <div class="text-muted small">Період: {{ $monthLabel }}</div>
                </div>
                <div class="text-muted small">SKU-маржа, CAC, LTV та ROI маркетингу</div>
            </div>

            <div class="row g-3">
                <div class="col-md-3"><div class="rounded border p-3 h-100"><div class="text-muted small mb-1">Маркетинг витрати</div><div class="fs-4 fw-bold text-danger">{{ number_format((float) $marketingSpend, 2, '.', ' ') }} грн</div></div></div>
                <div class="col-md-3"><div class="rounded border p-3 h-100"><div class="text-muted small mb-1">CAC</div><div class="fs-4 fw-bold text-warning">{{ number_format((float) $cac, 2, '.', ' ') }} грн</div></div></div>
                <div class="col-md-3"><div class="rounded border p-3 h-100"><div class="text-muted small mb-1">LTV</div><div class="fs-4 fw-bold text-primary">{{ number_format((float) $ltv, 2, '.', ' ') }} грн</div></div></div>
                <div class="col-md-3"><div class="rounded border p-3 h-100"><div class="text-muted small mb-1">ROI маркетингу</div><div class="fs-4 fw-bold {{ $marketingRoi >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format((float) $marketingRoi, 1, '.', ' ') }}%</div></div></div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-4"><div class="card shadow-sm h-100 bg-transparent border-secondary"><div class="card-body"><div class="text-muted small mb-1">Нові клієнти</div><div class="fs-3 fw-bold text-light">{{ (int) $newCustomersCount }}</div></div></div></div>
        <div class="col-md-4"><div class="card shadow-sm h-100 bg-transparent border-secondary"><div class="card-body"><div class="text-muted small mb-1">Активні клієнти</div><div class="fs-3 fw-bold text-secondary">{{ (int) $activeCustomersCount }}</div></div></div></div>
        <div class="col-md-4"><div class="card shadow-sm h-100 bg-transparent border-secondary"><div class="card-body"><div class="text-muted small mb-1">Валова прибуток періоду</div><div class="fs-3 fw-bold {{ $periodGrossProfit >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format((float) $periodGrossProfit, 2, '.', ' ') }} грн</div></div></div></div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <h4 class="card-title mb-0 text-light">Маржа по SKU</h4>
                <div class="text-muted small">{{ $marketingAssumption }}</div>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-dark table-hover align-middle mb-0 bg-transparent">
                    <thead class="table-dark">
                        <tr>
                            <th>Товар</th>
                            <th>Код</th>
                            <th class="text-end">Продано</th>
                            <th class="text-end">Виручка</th>
                            <th class="text-end">Валова прибуток</th>
                            <th class="text-end">Маржа</th>
                            <th class="text-end">Дохід / од.</th>
                            <th class="text-end">Прибуток / од.</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($skuEconomics as $item)
                        <tr class="{{ $item->gross_profit < 0 ? 'table-danger' : '' }}">
                            <td>{{ $item->product_name }}</td>
                            <td>{{ $item->pnum }}</td>
                            <td class="text-end">{{ number_format((float) $item->qty, 3, '.', ' ') }}</td>
                            <td class="text-end">{{ number_format((float) $item->revenue, 2, '.', ' ') }}</td>
                            <td class="text-end {{ $item->gross_profit >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format((float) $item->gross_profit, 2, '.', ' ') }}</td>
                            <td class="text-end">{{ number_format((float) $item->gross_margin, 1, '.', ' ') }}%</td>
                            <td class="text-end">{{ number_format((float) $item->avg_unit_revenue, 2, '.', ' ') }}</td>
                            <td class="text-end">{{ number_format((float) $item->avg_unit_profit, 2, '.', ' ') }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="8" class="text-muted">Немає даних.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
