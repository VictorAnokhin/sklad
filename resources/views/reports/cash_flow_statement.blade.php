@extends('home')

@section('title', 'Cash Flow')
@section('header_actions')
    @include('partials.report_panel')
@endsection

@push('styles')
<style>
    .cash-flow-total-row {
        --bs-table-bg: #2a1b12;
        --bs-table-color: #f4e7d5;
        --bs-table-border-color: rgba(212, 175, 55, 0.28);
        background-color: #2a1b12;
        color: #f4e7d5;
    }

    .cash-flow-total-row > * {
        background-color: #2a1b12 !important;
        color: #f4e7d5 !important;
    }
</style>
@endpush

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
                    <div class="text-muted small">Период: {{ $monthLabel }}</div>
                </div>
                <div class="text-muted small">Остатки, операционная, инвестиционная и финансовая деятельность</div>
            </div>

            <div class="row g-3">
                <div class="col-md-3">
                    <div class="rounded border p-3 h-100">
                        <div class="text-muted small mb-1">Остаток на начало @include('reports.hint', ['text' => 'Сколько денег было на счетах и в кассе на начало выбранного периода.'])</div>
                        <div class="fs-4 fw-bold text-light">{{ number_format((float) $openingCashBalance, 2, '.', ' ') }} грн</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="rounded border p-3 h-100">
                        <div class="text-muted small mb-1">Чистое изменение @include('reports.hint', ['text' => 'Операционный поток + инвестиционный поток + финансовый поток.'])</div>
                        <div class="fs-4 fw-bold {{ $netCashFlow >= 0 ? 'text-primary' : 'text-danger' }}">{{ number_format((float) $netCashFlow, 2, '.', ' ') }} грн</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="rounded border p-3 h-100">
                        <div class="text-muted small mb-1">Остаток на конец @include('reports.hint', ['text' => 'Итоговая сумма денег в кассе и на счетах после всех операций периода.'])</div>
                        <div class="fs-4 fw-bold text-light">{{ number_format((float) $closingCashBalance, 2, '.', ' ') }} грн</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="rounded border p-3 h-100">
                        <div class="text-muted small mb-1">Расчетный остаток @include('reports.hint', ['text' => 'Остаток на начало плюс чистое изменение за период. Помогает сверить движение денег с итоговым остатком.'])</div>
                        <div class="fs-4 fw-bold {{ abs((float) $closingCashBalance - (float) $calculatedClosingCashBalance) <= 0.01 ? 'text-success' : 'text-warning' }}">{{ number_format((float) $calculatedClosingCashBalance, 2, '.', ' ') }} грн</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm bg-transparent border-secondary">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm table-dark table-hover align-middle mb-0 bg-transparent">
                    <thead>
                        <tr>
                            <th>Статья Cash Flow</th>
                            <th class="text-end">Поступления</th>
                            <th class="text-end">Выплаты</th>
                            <th class="text-end">Чистый поток</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="cash-flow-total-row">
                            <td><strong>Остаток на начало периода</strong></td>
                            <td class="text-end text-muted">-</td>
                            <td class="text-end text-muted">-</td>
                            <td class="text-end"><strong>{{ number_format((float) $openingCashBalance, 2, '.', ' ') }}</strong></td>
                        </tr>

                        <tr>
                            <td>
                                <strong>Операционная деятельность</strong>
                                @include('reports.hint', ['text' => 'Деньги от продаж товаров или услуг, оплата труда, аренда, налоги и расчеты с поставщиками.'])
                            </td>
                            <td class="text-end text-success fw-semibold">{{ number_format((float) $operatingInflows, 2, '.', ' ') }}</td>
                            <td class="text-end text-danger fw-semibold">{{ number_format((float) $operatingOutflows, 2, '.', ' ') }}</td>
                            <td class="text-end fw-semibold {{ $operatingNet >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format((float) $operatingNet, 2, '.', ' ') }}</td>
                        </tr>
                        <tr>
                            <td class="ps-4 text-muted">Поступления от клиентов и продаж</td>
                            <td class="text-end text-success">{{ number_format((float) $operatingInflows, 2, '.', ' ') }}</td>
                            <td class="text-end text-muted">-</td>
                            <td class="text-end text-muted">-</td>
                        </tr>
                        <tr>
                            <td class="ps-4 text-muted">Оплата труда, аренда, налоги и поставщики</td>
                            <td class="text-end text-muted">-</td>
                            <td class="text-end text-danger">{{ number_format((float) $operatingOutflows, 2, '.', ' ') }}</td>
                            <td class="text-end text-muted">-</td>
                        </tr>

                        <tr>
                            <td>
                                <strong>Инвестиционная деятельность</strong>
                                @include('reports.hint', ['text' => 'Покупка или продажа оборудования, недвижимости, ценных бумаг или других активов.'])
                            </td>
                            <td class="text-end text-success fw-semibold">{{ number_format((float) $investingInflows, 2, '.', ' ') }}</td>
                            <td class="text-end text-danger fw-semibold">{{ number_format((float) $investingOutflows, 2, '.', ' ') }}</td>
                            <td class="text-end fw-semibold {{ $investingNet >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format((float) $investingNet, 2, '.', ' ') }}</td>
                        </tr>
                        <tr>
                            <td class="ps-4 text-muted">Продажа или возврат активов и инвестиций</td>
                            <td class="text-end text-success">{{ number_format((float) $investingInflows, 2, '.', ' ') }}</td>
                            <td class="text-end text-muted">-</td>
                            <td class="text-end text-muted">-</td>
                        </tr>
                        <tr>
                            <td class="ps-4 text-muted">Покупка оборудования, активов и вложения</td>
                            <td class="text-end text-muted">-</td>
                            <td class="text-end text-danger">{{ number_format((float) $investingOutflows, 2, '.', ' ') }}</td>
                            <td class="text-end text-muted">-</td>
                        </tr>

                        <tr>
                            <td>
                                <strong>Финансовая деятельность</strong>
                                @include('reports.hint', ['text' => 'Получение или возврат кредитов, выплата дивидендов, привлечение новых инвесторов.'])
                            </td>
                            <td class="text-end text-success fw-semibold">{{ number_format((float) $financingInflows, 2, '.', ' ') }}</td>
                            <td class="text-end text-danger fw-semibold">{{ number_format((float) $financingOutflows, 2, '.', ' ') }}</td>
                            <td class="text-end fw-semibold {{ $financingNet >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format((float) $financingNet, 2, '.', ' ') }}</td>
                        </tr>
                        <tr>
                            <td class="ps-4 text-muted">Кредиты, займы, взносы и инвестиции</td>
                            <td class="text-end text-success">{{ number_format((float) $financingInflows, 2, '.', ' ') }}</td>
                            <td class="text-end text-muted">-</td>
                            <td class="text-end text-muted">-</td>
                        </tr>
                        <tr>
                            <td class="ps-4 text-muted">Возврат кредитов, дивиденды и выплаты инвесторам</td>
                            <td class="text-end text-muted">-</td>
                            <td class="text-end text-danger">{{ number_format((float) $financingOutflows, 2, '.', ' ') }}</td>
                            <td class="text-end text-muted">-</td>
                        </tr>

                        <tr class="cash-flow-total-row">
                            <td><strong>Остаток на конец периода</strong></td>
                            <td class="text-end text-muted">-</td>
                            <td class="text-end text-muted">-</td>
                            <td class="text-end"><strong>{{ number_format((float) $closingCashBalance, 2, '.', ' ') }}</strong></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="text-muted small mt-3">{{ $financingAssumption }}</div>
        </div>
    </div>
</div>
@endsection
