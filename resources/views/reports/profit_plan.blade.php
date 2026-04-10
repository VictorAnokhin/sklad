@extends('home')

@section('title', 'План прибыли')
@section('header_actions')
    @include('partials.report_panel')
@endsection

@section('content')
<div class="container mt-4">
    @include('reports.period_form', ['periodFormAction' => route('reports.profitplan'), 'periodResetUrl' => route('reports.profitplan')])

    <div class="card shadow-sm border-dark-subtle">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <div><h3 class="mb-1">План прибыли</h3><div class="text-muted small">Період: {{ $monthLabel }}</div></div>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <div class="text-muted small">Побудовано від forecast виручки, поточної маржі та питомих OPEX</div>
                    <a href="{{ route('reports.strategic.export', ['report' => 'profitplan', 'format' => 'csv', 'date_from' => $dateFrom, 'date_to' => $dateTo]) }}" class="btn btn-sm btn-outline-primary">CSV</a>
                    <a href="{{ route('reports.strategic.export', ['report' => 'profitplan', 'format' => 'xls', 'date_from' => $dateFrom, 'date_to' => $dateTo]) }}" class="btn btn-sm btn-outline-secondary">XLS</a>
                </div>
            </div>
            <div class="row g-3">
                <div class="col-md-3"><div class="rounded border p-3 h-100"><div class="text-muted small mb-1">Планова виручка</div><div class="fs-4 fw-bold text-primary">{{ number_format((float) $plannedRevenue, 2, '.', ' ') }} грн</div></div></div>
                <div class="col-md-3"><div class="rounded border p-3 h-100"><div class="text-muted small mb-1">Планова валова прибуток</div><div class="fs-4 fw-bold text-success">{{ number_format((float) $plannedGrossProfit, 2, '.', ' ') }} грн</div></div></div>
                <div class="col-md-3"><div class="rounded border p-3 h-100"><div class="text-muted small mb-1">Плановий OPEX</div><div class="fs-4 fw-bold text-warning">{{ number_format((float) $plannedOpex, 2, '.', ' ') }} грн</div></div></div>
                <div class="col-md-3"><div class="rounded border p-3 h-100"><div class="text-muted small mb-1">Планова чиста прибуток</div><div class="fs-4 fw-bold {{ $plannedNetProfit >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format((float) $plannedNetProfit, 2, '.', ' ') }} грн</div></div></div>
            </div>
            <div class="row g-3 mt-1">
                <div class="col-md-6"><div class="rounded border p-3 h-100"><div class="text-muted small mb-1">Норматив валової маржі</div><div class="fs-5 fw-bold">{{ number_format((float) $marginRate, 1, '.', ' ') }}%</div></div></div>
                <div class="col-md-6"><div class="rounded border p-3 h-100"><div class="text-muted small mb-1">Норматив OPEX</div><div class="fs-5 fw-bold">{{ number_format((float) $opexRate, 1, '.', ' ') }}%</div></div></div>
            </div>
            <div class="row g-3 mt-1">
                <div class="col-md-3"><div class="rounded border p-3 h-100"><div class="text-muted small mb-1">Факт виручки</div><div class="fs-5 fw-bold text-dark">{{ number_format((float) $actualRevenue, 2, '.', ' ') }} грн</div></div></div>
                <div class="col-md-3"><div class="rounded border p-3 h-100"><div class="text-muted small mb-1">Факт валової прибутку</div><div class="fs-5 fw-bold text-success">{{ number_format((float) $actualGrossProfit, 2, '.', ' ') }} грн</div></div></div>
                <div class="col-md-3"><div class="rounded border p-3 h-100"><div class="text-muted small mb-1">Факт OPEX</div><div class="fs-5 fw-bold text-warning">{{ number_format((float) $actualOpex, 2, '.', ' ') }} грн</div></div></div>
                <div class="col-md-3"><div class="rounded border p-3 h-100"><div class="text-muted small mb-1">Факт чистої прибутку</div><div class="fs-5 fw-bold {{ $actualNetProfit >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format((float) $actualNetProfit, 2, '.', ' ') }} грн</div></div></div>
            </div>
            <div class="row g-3 mt-1">
                <div class="col-md-6"><div class="rounded border p-3 h-100"><div class="text-muted small mb-1">План/факт виручки</div><div class="fs-5 fw-bold">{{ number_format((float) $planFactRevenuePercent, 1, '.', ' ') }}%</div></div></div>
                <div class="col-md-6"><div class="rounded border p-3 h-100"><div class="text-muted small mb-1">План/факт чистої прибутку</div><div class="fs-5 fw-bold">{{ number_format((float) $planFactNetProfitPercent, 1, '.', ' ') }}%</div></div></div>
            </div>
        </div>
    </div>
</div>
@endsection
