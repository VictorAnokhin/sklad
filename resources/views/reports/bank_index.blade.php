@extends('home')

@section('title', __('reports.title'))
@section('header_actions')
    @include('partials.report_panel')
@endsection

@section('content')
<div class="container mt-4 reports-page" data-bs-theme="dark">
    @include('reports.period_form')

    <div class="card shadow-sm mb-4 bg-transparent border-secondary bg-opacity-10">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <div>
                    <h3 class="mb-1 text-light">Bank reporting</h3>
                    <div class="text-muted small">{{ __('reports.period', ['month' => $monthLabel]) }}</div>
                </div>
                <div class="text-muted small">Отчёты для банковского проекта: ликвидность, баланс, cash flow и ledger.</div>
            </div>

            <div class="row g-3">
                <div class="col-md-3">
                    <div class="rounded border p-3 h-100">
                        <div class="text-muted small mb-1">Денежная позиция</div>
                        <div class="fs-4 fw-bold text-primary">{{ number_format((float) $cashBalanceTotal, 2, '.', ' ') }} грн</div>
                        <div class="text-muted small mt-1">Текущий остаток по кассам и счетам</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="rounded border p-3 h-100">
                        <div class="text-muted small mb-1">Поступления</div>
                        <div class="fs-4 fw-bold text-success">{{ number_format((float) $monthIncome, 2, '.', ' ') }} грн</div>
                        <div class="text-muted small mt-1">Проведённые входящие платежи периода</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="rounded border p-3 h-100">
                        <div class="text-muted small mb-1">Списания</div>
                        <div class="fs-4 fw-bold text-danger">{{ number_format((float) $monthExpense, 2, '.', ' ') }} грн</div>
                        <div class="text-muted small mt-1">Проведённые исходящие платежи периода</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="rounded border p-3 h-100">
                        <div class="text-muted small mb-1">Net cash flow</div>
                        <div class="fs-4 fw-bold {{ $cashFlowNet >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format((float) $cashFlowNet, 2, '.', ' ') }} грн</div>
                        <div class="text-muted small mt-1">Поступления минус списания</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        @foreach($bankReportCards as $card)
        <div class="col-md-6 col-xl-4">
            <a href="{{ $card['url'] }}" class="card shadow-sm h-100 bg-transparent border-secondary text-decoration-none">
                <div class="card-body">
                    <div class="small text-muted mb-2">Bank report</div>
                    <h4 class="card-title {{ $card['accent'] }} mb-2">{{ $card['title'] }}</h4>
                    <div class="text-muted">{{ $card['description'] }}</div>
                </div>
            </a>
        </div>
        @endforeach
    </div>

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card shadow-sm h-100 bg-transparent border-secondary">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                        <h4 class="card-title mb-0 text-light">Структура ликвидности</h4>
                        <div class="text-muted small">Текущие остатки</div>
                    </div>

                    @if(($cashboxes ?? collect())->isEmpty())
                    <div class="text-muted">{{ __('reports.cash_position_not_configured') }}</div>
                    @else
                    <div class="table-responsive reports-sticky-first-col">
                        <table class="table table-sm table-dark table-hover align-middle mb-0 bg-transparent reports-cashboxes-table">
                            <thead class="table-dark">
                                <tr>
                                    <th>Счёт / касса</th>
                                    <th class="text-end">Остаток</th>
                                    <th class="text-end">Доля</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($cashboxes as $cashbox)
                                @php
                                    $cashboxValue = (float) ($cashbox->value ?? 0);
                                    $cashboxShare = $cashBalanceTotal > 0 ? ($cashboxValue / $cashBalanceTotal) * 100 : 0;
                                @endphp
                                <tr>
                                    <td>{{ $cashbox->name }}</td>
                                    <td class="text-end fw-semibold">{{ number_format($cashboxValue, 2, '.', ' ') }} грн</td>
                                    <td class="text-end text-muted">{{ number_format($cashboxShare, 1, '.', ' ') }}%</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card shadow-sm h-100 bg-transparent border-secondary">
                <div class="card-body">
                    <h4 class="card-title mb-3 text-light">Ключевой счёт</h4>
                    @if($largestCashbox)
                    <div class="rounded border p-3">
                        <div class="text-muted small mb-1">{{ $largestCashbox->name }}</div>
                        <div class="fs-4 fw-bold text-primary">{{ number_format((float) ($largestCashbox->value ?? 0), 2, '.', ' ') }} грн</div>
                    </div>
                    @else
                    <div class="text-muted">{{ __('reports.cash_position_not_configured') }}</div>
                    @endif

                    <div class="mt-3 text-muted small">
                        Для детальной сверки используйте отчёты Cash Flow, Оборотка и Журнал проводок.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
