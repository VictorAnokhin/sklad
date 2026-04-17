@extends('home')

@section('title', 'План закупок')
@section('header_actions')
    @include('partials.report_panel')
@endsection

@section('content')
<div class="container mt-4" data-bs-theme="dark">
    @include('reports.period_form', ['periodFormAction' => route('reports.purchaseplan'), 'periodResetUrl' => route('reports.purchaseplan')])

    <div class="card shadow-sm mb-4 bg-transparent border-secondary bg-opacity-10">
        <div class="card-body">
            <div class="d-flex justify-content-end align-items-center gap-2 mb-3">
                <a href="{{ route('reports.strategic.export', ['report' => 'purchaseplan', 'format' => 'csv', 'date_from' => $dateFrom, 'date_to' => $dateTo]) }}" class="btn btn-sm btn-outline-primary">CSV</a>
                <a href="{{ route('reports.strategic.export', ['report' => 'purchaseplan', 'format' => 'xls', 'date_from' => $dateFrom, 'date_to' => $dateTo]) }}" class="btn btn-sm btn-outline-secondary">XLS</a>
            </div>
            <div class="row g-3">
                <div class="col-md-6"><div class="rounded border p-3 h-100"><div class="text-muted small mb-1">План закупок, од.</div><div class="fs-4 fw-bold text-light">{{ number_format((float) $plannedPurchaseQtyTotal, 2, '.', ' ') }}</div></div></div>
                <div class="col-md-6"><div class="rounded border p-3 h-100"><div class="text-muted small mb-1">План закупок, сума</div><div class="fs-4 fw-bold text-primary">{{ number_format((float) $plannedPurchaseTotal, 2, '.', ' ') }} грн</div></div></div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <h4 class="card-title mb-3 text-light">Товари до закупівлі</h4>
            <div class="table-responsive">
                <table class="table table-sm table-dark table-hover align-middle mb-0 bg-transparent">
                    <thead class="table-dark"><tr><th>Товар</th><th>Код</th><th class="text-end">Поточний залишок</th><th class="text-end">Сер. попит / міс</th><th class="text-end">Плановий попит</th><th class="text-end">Докупить</th><th class="text-end">Сума</th></tr></thead>
                    <tbody>
                        @forelse($items as $item)
                        <tr>
                            <td>{{ $item->product_name }}</td>
                            <td>{{ $item->pnum }}</td>
                            <td class="text-end">{{ number_format((float) $item->current_stock, 2, '.', ' ') }}</td>
                            <td class="text-end">{{ number_format((float) $item->avg_monthly_qty, 2, '.', ' ') }}</td>
                            <td class="text-end">{{ number_format((float) $item->planned_demand, 2, '.', ' ') }}</td>
                            <td class="text-end fw-semibold">{{ number_format((float) $item->planned_purchase_qty, 2, '.', ' ') }}</td>
                            <td class="text-end fw-semibold text-primary">{{ number_format((float) $item->planned_purchase_sum, 2, '.', ' ') }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="7" class="text-muted">План закупок не потрібен: запасів достатньо.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
