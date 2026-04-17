@extends('home')

@section('title', 'Cash Flow')
@section('header_actions')
    @include('partials.report_panel')
@endsection

@section('content')
<div class="container mt-4" data-bs-theme="dark">
    @include('reports.period_form', [
        'periodFormAction' => route('reports.cashflowstmt'),
        'periodResetUrl' => route('reports.cashflowstmt'),
    ])

    <div class="card shadow-sm mb-4 bg-transparent border-secondary bg-opacity-10">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <div>
                    <h3 class="mb-1 text-light">Отчет о движении денежных средств</h3>
                    <div class="text-muted small">Період: {{ $monthLabel }}</div>
                </div>
                <div class="text-muted small">Операційна, інвестиційна та фінансова діяльність</div>
            </div>

            <div class="row g-3">
                <div class="col-md-3"><div class="rounded border p-3 h-100"><div class="text-muted small mb-1">Операційний потік</div><div class="fs-4 fw-bold {{ $operatingNet >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format((float) $operatingNet, 2, '.', ' ') }} грн</div></div></div>
                <div class="col-md-3"><div class="rounded border p-3 h-100"><div class="text-muted small mb-1">Інвестиційний потік</div><div class="fs-4 fw-bold {{ $investingNet >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format((float) $investingNet, 2, '.', ' ') }} грн</div></div></div>
                <div class="col-md-3"><div class="rounded border p-3 h-100"><div class="text-muted small mb-1">Фінансовий потік</div><div class="fs-4 fw-bold {{ $financingNet >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format((float) $financingNet, 2, '.', ' ') }} грн</div></div></div>
                <div class="col-md-3"><div class="rounded border p-3 h-100"><div class="text-muted small mb-1">Чиста зміна грошей</div><div class="fs-4 fw-bold {{ $netCashFlow >= 0 ? 'text-primary' : 'text-danger' }}">{{ number_format((float) $netCashFlow, 2, '.', ' ') }} грн</div></div></div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card shadow-sm h-100 bg-transparent border-secondary">
                <div class="card-body">
                    <h4 class="card-title mb-3 text-light">Операційна діяльність</h4>
                    <table class="table table-sm table-dark table-hover align-middle mb-0 bg-transparent">
                        <tbody>
                            <tr><td>Надходження</td><td class="text-end text-success fw-semibold">{{ number_format((float) $operatingInflows, 2, '.', ' ') }}</td></tr>
                            <tr><td>Виплати</td><td class="text-end text-danger fw-semibold">{{ number_format((float) $operatingOutflows, 2, '.', ' ') }}</td></tr>
                            <tr class="table-light"><td><strong>Чистий потік</strong></td><td class="text-end"><strong>{{ number_format((float) $operatingNet, 2, '.', ' ') }}</strong></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card shadow-sm h-100 bg-transparent border-secondary">
                <div class="card-body">
                    <h4 class="card-title mb-3 text-light">Інвестиційна діяльність</h4>
                    <table class="table table-sm table-dark table-hover align-middle mb-0 bg-transparent">
                        <tbody>
                            <tr><td>Надходження</td><td class="text-end text-success fw-semibold">{{ number_format((float) $investingInflows, 2, '.', ' ') }}</td></tr>
                            <tr><td>Виплати</td><td class="text-end text-danger fw-semibold">{{ number_format((float) $investingOutflows, 2, '.', ' ') }}</td></tr>
                            <tr class="table-light"><td><strong>Чистий потік</strong></td><td class="text-end"><strong>{{ number_format((float) $investingNet, 2, '.', ' ') }}</strong></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card shadow-sm h-100 bg-transparent border-secondary">
                <div class="card-body">
                    <h4 class="card-title mb-3 text-light">Фінансова діяльність</h4>
                    <table class="table table-sm table-dark table-hover align-middle mb-0 bg-transparent">
                        <tbody>
                            <tr><td>Надходження</td><td class="text-end text-success fw-semibold">{{ number_format((float) $financingInflows, 2, '.', ' ') }}</td></tr>
                            <tr><td>Виплати</td><td class="text-end text-danger fw-semibold">{{ number_format((float) $financingOutflows, 2, '.', ' ') }}</td></tr>
                            <tr class="table-light"><td><strong>Чистий потік</strong></td><td class="text-end"><strong>{{ number_format((float) $financingNet, 2, '.', ' ') }}</strong></td></tr>
                        </tbody>
                    </table>
                    <div class="text-muted small mt-3">{{ $financingAssumption }}</div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
