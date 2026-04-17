@extends('home')

@section('title', 'Баланс')
@section('header_actions')
    @include('partials.report_panel')
@endsection

@section('content')
<div class="container mt-4" data-bs-theme="dark">
    @include('reports.period_form', [
        'periodFormAction' => route('reports.balancesheet'),
        'periodResetUrl' => route('reports.balancesheet'),
    ])

    <div class="card shadow-sm mb-4 bg-transparent border-secondary bg-opacity-10">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <div>
                    <h3 class="mb-1 text-light">Balance Sheet</h3>
                    <div class="text-muted small">Дата зрізу: {{ $monthLabel }}</div>
                </div>
                <div class="text-muted small">Ліквідність, стійкість і структура капіталу</div>
            </div>

            <div class="row g-3">
                <div class="col-md-4"><div class="rounded border p-3 h-100"><div class="text-muted small mb-1">Активи</div><div class="fs-4 fw-bold text-primary">{{ number_format((float) $totalAssets, 2, '.', ' ') }} грн</div></div></div>
                <div class="col-md-4"><div class="rounded border p-3 h-100"><div class="text-muted small mb-1">Зобов’язання</div><div class="fs-4 fw-bold text-danger">{{ number_format((float) $totalLiabilities, 2, '.', ' ') }} грн</div></div></div>
                <div class="col-md-4"><div class="rounded border p-3 h-100"><div class="text-muted small mb-1">Капітал</div><div class="fs-4 fw-bold {{ $equity >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format((float) $equity, 2, '.', ' ') }} грн</div></div></div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card shadow-sm h-100 bg-transparent border-secondary">
                <div class="card-body">
                    <h4 class="card-title mb-3 text-light">Активи</h4>
                    <table class="table table-sm table-dark table-hover align-middle mb-0 bg-transparent">
                        <tbody>
                            <tr><td>Товари / запаси</td><td class="text-end fw-semibold">{{ number_format((float) $inventoryValue, 2, '.', ' ') }}</td></tr>
                            <tr><td>Гроші</td><td class="text-end fw-semibold">{{ number_format((float) $cashBalance, 2, '.', ' ') }}</td></tr>
                            <tr><td>Депозити</td><td class="text-end fw-semibold">{{ number_format((float) $depositBalance, 2, '.', ' ') }}</td></tr>
                            <tr><td>Дебіторка</td><td class="text-end fw-semibold">{{ number_format((float) $receivables, 2, '.', ' ') }}</td></tr>
                            <tr class="table-light"><td><strong>Разом активи</strong></td><td class="text-end"><strong>{{ number_format((float) $totalAssets, 2, '.', ' ') }}</strong></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card shadow-sm h-100 bg-transparent border-secondary">
                <div class="card-body">
                    <h4 class="card-title mb-3 text-light">Зобов’язання і капітал</h4>
                    <table class="table table-sm table-dark table-hover align-middle mb-0 bg-transparent">
                        <tbody>
                            <tr><td>Кредиторка</td><td class="text-end fw-semibold">{{ number_format((float) $payables, 2, '.', ' ') }}</td></tr>
                            <tr><td>Кредити / фінансування</td><td class="text-end fw-semibold">{{ number_format((float) $loans, 2, '.', ' ') }}</td></tr>
                            <tr class="table-light"><td><strong>Разом зобов’язання</strong></td><td class="text-end"><strong>{{ number_format((float) $totalLiabilities, 2, '.', ' ') }}</strong></td></tr>
                            <tr><td>Капітал</td><td class="text-end fw-semibold {{ $equity >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format((float) $equity, 2, '.', ' ') }}</td></tr>
                            <tr class="table-light"><td><strong>Разом пасиви</strong></td><td class="text-end"><strong>{{ number_format((float) ($totalLiabilities + $equity), 2, '.', ' ') }}</strong></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
