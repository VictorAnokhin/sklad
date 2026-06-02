@extends('home')

@section('title', 'Cash Flow')
@section('header_actions')
    @include('partials.report_panel')
@endsection

@section('content')
<div class="container mt-4 reports-page" data-bs-theme="dark">
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
                <div class="col-md-3"><div class="rounded border p-3 h-100"><div class="text-muted small mb-1">Операційний потік @include('reports.hint', ['text' => 'Чистий рух грошей від основної діяльності: надходження від клієнтів мінус операційні виплати.'])</div><div class="fs-4 fw-bold {{ $operatingNet >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format((float) $operatingNet, 2, '.', ' ') }} грн</div></div></div>
                <div class="col-md-3"><div class="rounded border p-3 h-100"><div class="text-muted small mb-1">Інвестиційний потік @include('reports.hint', ['text' => 'Рух грошей, пов’язаний з інвестиціями: вкладення, купівля активів або повернення інвестицій.'])</div><div class="fs-4 fw-bold {{ $investingNet >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format((float) $investingNet, 2, '.', ' ') }} грн</div></div></div>
                <div class="col-md-3"><div class="rounded border p-3 h-100"><div class="text-muted small mb-1">Фінансовий потік @include('reports.hint', ['text' => 'Рух грошей від фінансування: кредити, внески власників, повернення позик або інші фінансові операції.'])</div><div class="fs-4 fw-bold {{ $financingNet >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format((float) $financingNet, 2, '.', ' ') }} грн</div></div></div>
                <div class="col-md-3"><div class="rounded border p-3 h-100"><div class="text-muted small mb-1">Чиста зміна грошей @include('reports.hint', ['text' => 'Підсумок усіх потоків: операційний + інвестиційний + фінансовий. Показує, на скільки змінився грошовий залишок.'])</div><div class="fs-4 fw-bold {{ $netCashFlow >= 0 ? 'text-primary' : 'text-danger' }}">{{ number_format((float) $netCashFlow, 2, '.', ' ') }} грн</div></div></div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card shadow-sm h-100 bg-transparent border-secondary">
                <div class="card-body">
                    <h4 class="card-title mb-3 text-light">Операційна діяльність @include('reports.hint', ['text' => 'Основна діяльність компанії: продажі, надходження від клієнтів і поточні виплати.'])</h4>
                    <table class="table table-sm table-dark table-hover align-middle mb-0 bg-transparent">
                        <tbody>
                            <tr><td>Надходження @include('reports.hint', ['text' => 'Гроші, що надійшли в межах операційної діяльності.'])</td><td class="text-end text-success fw-semibold">{{ number_format((float) $operatingInflows, 2, '.', ' ') }}</td></tr>
                            <tr><td>Виплати @include('reports.hint', ['text' => 'Гроші, виплачені в межах операційної діяльності.'])</td><td class="text-end text-danger fw-semibold">{{ number_format((float) $operatingOutflows, 2, '.', ' ') }}</td></tr>
                            <tr class="table-light"><td><strong>Чистий потік @include('reports.hint', ['text' => 'Надходження мінус виплати по цьому блоку.'])</strong></td><td class="text-end"><strong>{{ number_format((float) $operatingNet, 2, '.', ' ') }}</strong></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card shadow-sm h-100 bg-transparent border-secondary">
                <div class="card-body">
                    <h4 class="card-title mb-3 text-light">Інвестиційна діяльність @include('reports.hint', ['text' => 'Грошові операції, пов’язані з активами, інвестиціями або довгостроковими вкладеннями.'])</h4>
                    <table class="table table-sm table-dark table-hover align-middle mb-0 bg-transparent">
                        <tbody>
                            <tr><td>Надходження @include('reports.hint', ['text' => 'Повернення або продаж інвестиційних активів.'])</td><td class="text-end text-success fw-semibold">{{ number_format((float) $investingInflows, 2, '.', ' ') }}</td></tr>
                            <tr><td>Виплати @include('reports.hint', ['text' => 'Витрати на інвестиції або активи.'])</td><td class="text-end text-danger fw-semibold">{{ number_format((float) $investingOutflows, 2, '.', ' ') }}</td></tr>
                            <tr class="table-light"><td><strong>Чистий потік @include('reports.hint', ['text' => 'Надходження мінус виплати по інвестиційній діяльності.'])</strong></td><td class="text-end"><strong>{{ number_format((float) $investingNet, 2, '.', ' ') }}</strong></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card shadow-sm h-100 bg-transparent border-secondary">
                <div class="card-body">
                    <h4 class="card-title mb-3 text-light">Фінансова діяльність @include('reports.hint', ['text' => 'Операції фінансування: внески, позики, повернення боргу або інші джерела капіталу.'])</h4>
                    <table class="table table-sm table-dark table-hover align-middle mb-0 bg-transparent">
                        <tbody>
                            <tr><td>Надходження @include('reports.hint', ['text' => 'Гроші, отримані від фінансування.'])</td><td class="text-end text-success fw-semibold">{{ number_format((float) $financingInflows, 2, '.', ' ') }}</td></tr>
                            <tr><td>Виплати @include('reports.hint', ['text' => 'Гроші, виплачені в межах фінансової діяльності.'])</td><td class="text-end text-danger fw-semibold">{{ number_format((float) $financingOutflows, 2, '.', ' ') }}</td></tr>
                            <tr class="table-light"><td><strong>Чистий потік @include('reports.hint', ['text' => 'Надходження мінус виплати по фінансовій діяльності.'])</strong></td><td class="text-end"><strong>{{ number_format((float) $financingNet, 2, '.', ' ') }}</strong></td></tr>
                        </tbody>
                    </table>
                    <div class="text-muted small mt-3">{{ $financingAssumption }}</div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
