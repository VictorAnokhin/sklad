@extends('home')

@section('title', 'Фінансовий P&L')
@section('header_actions')
    @include('partials.report_panel')
@endsection

@section('content')
<div class="container mt-4 reports-page" data-bs-theme="dark">
    @include('reports.period_form', [
        'periodFormAction' => route('reports.financialpnl'),
        'periodResetUrl' => route('reports.financialpnl'),
    ])

    <div class="card shadow-sm mb-4 bg-transparent border-secondary bg-opacity-10">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <div>
                    <h3 class="mb-1 text-light">Отчет о прибылях и убытках</h3>
                    <div class="text-muted small">Період: {{ $monthLabel }}</div>
                </div>
                <div class="text-muted small">Прибутковість бізнесу для власника, банку та інвестора</div>
            </div>

            <div class="row g-3">
                <div class="col-md-2"><div class="rounded border p-3 h-100"><div class="text-muted small mb-1">Виручка @include('reports.hint', ['text' => 'Кредитовий оборот рахунків доходів мінус дебетові сторно за період.'])</div><div class="fs-5 fw-bold text-primary">{{ number_format((float) $revenueTotal, 2, '.', ' ') }} грн</div></div></div>
                <div class="col-md-2"><div class="rounded border p-3 h-100"><div class="text-muted small mb-1">COGS @include('reports.hint', ['text' => 'Фактичний дебетовий оборот рахунку 902 мінус кредитові сторно за період.'])</div><div class="fs-5 fw-bold text-light">{{ number_format((float) $cogsTotal, 2, '.', ' ') }} грн</div></div></div>
                <div class="col-md-2"><div class="rounded border p-3 h-100"><div class="text-muted small mb-1">Валова прибуток @include('reports.hint', ['text' => 'Виручка мінус COGS. Показує прибуток до операційних витрат.'])</div><div class="fs-5 fw-bold {{ $grossProfitTotal >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format((float) $grossProfitTotal, 2, '.', ' ') }} грн</div></div></div>
                <div class="col-md-2"><div class="rounded border p-3 h-100"><div class="text-muted small mb-1">Маржа @include('reports.hint', ['text' => 'Валова маржа у відсотках: валова прибуток / виручка.'])</div><div class="fs-5 fw-bold">{{ number_format((float) $grossMarginTotal, 1, '.', ' ') }}%</div></div></div>
                <div class="col-md-2"><div class="rounded border p-3 h-100"><div class="text-muted small mb-1">OPEX @include('reports.hint', ['text' => 'Операційні витрати бізнесу за період: адміністративні, маркетингові, зарплатні та інші витрати.'])</div><div class="fs-5 fw-bold text-warning">{{ number_format((float) $operatingExpensesTotal, 2, '.', ' ') }} грн</div></div></div>
                <div class="col-md-2"><div class="rounded border p-3 h-100"><div class="text-muted small mb-1">Чистий прибуток @include('reports.hint', ['text' => 'Валова прибуток мінус OPEX. Головний показник фінансового результату періоду.'])</div><div class="fs-5 fw-bold {{ $netProfit >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format((float) $netProfit, 2, '.', ' ') }} грн</div></div></div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <h4 class="card-title mb-3 text-light">Структура операційних витрат</h4>
            <div class="table-responsive">
                <table class="table table-sm table-dark table-hover align-middle mb-0 bg-transparent">
                    <thead class="table-dark">
                        <tr>
                            <th>Стаття витрат @include('reports.hint', ['text' => 'Категорія операційних витрат за типом платежу або налаштуванням у довідниках.'])</th>
                            <th class="text-end">Документів @include('reports.hint', ['text' => 'Кількість проведених документів витрат у цій статті.'])</th>
                            <th class="text-end">Сума @include('reports.hint', ['text' => 'Загальна сума витрат по статті за вибраний період.'])</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($operatingExpensesByType as $item)
                        <tr>
                            <td>{{ $item->expense_name }}</td>
                            <td class="text-end">{{ $item->docs_count }}</td>
                            <td class="text-end fw-semibold text-warning">{{ number_format((float) $item->expense_sum, 2, '.', ' ') }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="3" class="text-muted">Операційних витрат за період не знайдено.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
