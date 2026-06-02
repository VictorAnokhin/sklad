@extends('home')

@section('title', 'План прибыли')
@section('header_actions')
    @include('partials.report_panel')
@endsection

@section('content')
<div class="container mt-4 reports-page" data-bs-theme="dark">
    @include('reports.period_form', ['periodFormAction' => route('reports.profitplan'), 'periodResetUrl' => route('reports.profitplan')])

    <div class="card shadow-sm border-dark-subtle">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <div><h3 class="mb-1 text-light">План прибыли</h3><div class="text-muted small">Період: {{ $monthLabel }}</div></div>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <div class="text-muted small">Побудовано від forecast виручки, поточної маржі та питомих OPEX</div>
                    <a href="{{ route('reports.strategic.export', ['report' => 'profitplan', 'format' => 'csv', 'date_from' => $dateFrom, 'date_to' => $dateTo]) }}" class="btn btn-sm btn-outline-primary">CSV</a>
                    <a href="{{ route('reports.strategic.export', ['report' => 'profitplan', 'format' => 'xls', 'date_from' => $dateFrom, 'date_to' => $dateTo]) }}" class="btn btn-sm btn-outline-secondary">XLS</a>
                </div>
            </div>
            <div class="row g-3">
                <div class="col-md-3"><div class="rounded border p-3 h-100"><div class="text-muted small mb-1">Планова виручка @include('reports.hint', ['text' => 'Forecast виручки на період, розрахований з історії продажів і тренду зростання/падіння.'])</div><div class="fs-4 fw-bold text-primary">{{ number_format((float) $plannedRevenue, 2, '.', ' ') }} грн</div></div></div>
                <div class="col-md-3"><div class="rounded border p-3 h-100"><div class="text-muted small mb-1">Планова валова прибуток @include('reports.hint', ['text' => 'Очікувана валова прибуток: планова виручка помножена на поточний норматив валової маржі.'])</div><div class="fs-4 fw-bold text-success">{{ number_format((float) $plannedGrossProfit, 2, '.', ' ') }} грн</div></div></div>
                <div class="col-md-3"><div class="rounded border p-3 h-100"><div class="text-muted small mb-1">Плановий OPEX @include('reports.hint', ['text' => 'Очікувані операційні витрати: планова виручка помножена на поточну частку OPEX.'])</div><div class="fs-4 fw-bold text-warning">{{ number_format((float) $plannedOpex, 2, '.', ' ') }} грн</div></div></div>
                <div class="col-md-3"><div class="rounded border p-3 h-100"><div class="text-muted small mb-1">Планова чиста прибуток @include('reports.hint', ['text' => 'Планова валова прибуток мінус плановий OPEX.'])</div><div class="fs-4 fw-bold {{ $plannedNetProfit >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format((float) $plannedNetProfit, 2, '.', ' ') }} грн</div></div></div>
            </div>
            <div class="row g-3 mt-1">
                <div class="col-md-6"><div class="rounded border p-3 h-100"><div class="text-muted small mb-1">Норматив валової маржі @include('reports.hint', ['text' => 'Поточна валова маржа у відсотках: валова прибуток / виручка. Використовується для плану прибутку.'])</div><div class="fs-5 fw-bold">{{ number_format((float) $marginRate, 1, '.', ' ') }}%</div></div></div>
                <div class="col-md-6"><div class="rounded border p-3 h-100"><div class="text-muted small mb-1">Норматив OPEX @include('reports.hint', ['text' => 'Частка операційних витрат у виручці: OPEX / виручка. Використовується для прогнозу витрат.'])</div><div class="fs-5 fw-bold">{{ number_format((float) $opexRate, 1, '.', ' ') }}%</div></div></div>
            </div>
            <div class="row g-3 mt-1">
                <div class="col-md-3"><div class="rounded border p-3 h-100"><div class="text-muted small mb-1">Факт виручки @include('reports.hint', ['text' => 'Фактична виручка за вибраний період по проведених продажах.'])</div><div class="fs-5 fw-bold text-light">{{ number_format((float) $actualRevenue, 2, '.', ' ') }} грн</div></div></div>
                <div class="col-md-3"><div class="rounded border p-3 h-100"><div class="text-muted small mb-1">Факт валової прибутку @include('reports.hint', ['text' => 'Фактична валова прибуток: виручка мінус собівартість проданих товарів.'])</div><div class="fs-5 fw-bold text-success">{{ number_format((float) $actualGrossProfit, 2, '.', ' ') }} грн</div></div></div>
                <div class="col-md-3"><div class="rounded border p-3 h-100"><div class="text-muted small mb-1">Факт OPEX @include('reports.hint', ['text' => 'Фактичні операційні витрати за період.'])</div><div class="fs-5 fw-bold text-warning">{{ number_format((float) $actualOpex, 2, '.', ' ') }} грн</div></div></div>
                <div class="col-md-3"><div class="rounded border p-3 h-100"><div class="text-muted small mb-1">Факт чистої прибутку @include('reports.hint', ['text' => 'Фактична чиста прибуток: валова прибуток мінус операційні витрати.'])</div><div class="fs-5 fw-bold {{ $actualNetProfit >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format((float) $actualNetProfit, 2, '.', ' ') }} грн</div></div></div>
            </div>
            <div class="row g-3 mt-1">
                <div class="col-md-6"><div class="rounded border p-3 h-100"><div class="text-muted small mb-1">План/факт виручки @include('reports.hint', ['text' => 'Відсоток виконання плану виручки: фактична виручка / планова виручка.'])</div><div class="fs-5 fw-bold">{{ number_format((float) $planFactRevenuePercent, 1, '.', ' ') }}%</div></div></div>
                <div class="col-md-6"><div class="rounded border p-3 h-100"><div class="text-muted small mb-1">План/факт чистої прибутку @include('reports.hint', ['text' => 'Відсоток виконання плану чистої прибутку: фактична чиста прибуток / планова чиста прибуток.'])</div><div class="fs-5 fw-bold">{{ number_format((float) $planFactNetProfitPercent, 1, '.', ' ') }}%</div></div></div>
            </div>
        </div>
    </div>
</div>
@endsection
