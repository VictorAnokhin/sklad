@extends('home')

@section('title', __('reports.title'))
@section('header_actions')
    @include('partials.report_panel')
@endsection

@section('content')
<div class="container mt-4" data-bs-theme="dark">
    @include('reports.period_form')

    <div class="card shadow-sm mb-4 bg-transparent border-secondary bg-opacity-10">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <div>
                    <h3 class="mb-1 text-light">{{ __('reports.heading') }}</h3>
                    <div class="text-muted small">{{ __('reports.period', ['month' => $monthLabel]) }}</div>
                </div>
                <div class="text-muted small">{{ __('reports.subtitle') }}</div>
            </div>

            <div class="row g-3">
                <div class="col-md-3">
                    <div class="rounded border p-3 h-100">
                        <div class="text-muted small mb-1">{{ __('reports.kpi_revenue') }}</div>
                        <div class="fs-4 fw-bold text-primary">{{ number_format((float) $salesRevenueTotal, 2, '.', ' ') }} грн</div>
                        <div class="text-muted small mt-1">{{ __('reports.kpi_rn_posted') }}</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="rounded border p-3 h-100">
                        <div class="text-muted small mb-1">{{ __('reports.kpi_cash_inflow') }}</div>
                        <div class="fs-4 fw-bold text-success">{{ number_format((float) $monthIncome, 2, '.', ' ') }} грн</div>
                        <div class="text-muted small mt-1">{{ __('reports.kpi_po_posted') }}</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="rounded border p-3 h-100">
                        <div class="text-muted small mb-1">{{ __('reports.kpi_cash_outflow') }}</div>
                        <div class="fs-4 fw-bold text-danger">{{ number_format((float) $monthExpense, 2, '.', ' ') }} грн</div>
                        <div class="text-muted small mt-1">{{ __('reports.kpi_ro_posted') }}</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="rounded border p-3 h-100">
                        <div class="text-muted small mb-1">{{ __('reports.kpi_net_flow') }}</div>
                        <div class="fs-4 fw-bold {{ $cashFlowNet >= 0 ? 'text-primary' : 'text-danger' }}">{{ number_format((float) $cashFlowNet, 2, '.', ' ') }} грн</div>
                        <div class="text-muted small mt-1">{{ __('reports.kpi_net_flow_desc') }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card shadow-sm h-100 bg-transparent border-secondary">
                <div class="card-body">
                    <h4 class="card-title mb-3 text-light">{{ __('reports.sales_title') }}</h4>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="rounded border border-secondary p-3 h-100">
                                <div class="text-muted small mb-1">{{ __('reports.sales_documents') }}</div>
                                <div class="fs-4 fw-bold text-light">{{ $salesDocsCount }}</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="rounded border p-3 h-100">
                                <div class="text-muted small mb-1">{{ __('reports.sales_units') }}</div>
                                <div class="fs-4 fw-bold text-secondary">{{ number_format((float) $soldUnitsTotal, 3, '.', ' ') }}</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="rounded border p-3 h-100">
                                <div class="text-muted small mb-1">{{ __('reports.sales_avg_check') }}</div>
                                <div class="fs-4 fw-bold text-success">{{ number_format((float) $averageSalesDoc, 2, '.', ' ') }} грн</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="rounded border p-3 h-100">
                                <div class="text-muted small mb-1">{{ __('reports.sales_new_orders') }}</div>
                                <div class="fs-4 fw-bold text-warning">{{ $newOrdersCount }}</div>
                                <div class="text-muted small mt-1">{{ __('reports.sales_open_portfolio') }}</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="rounded border p-3 h-100">
                                <div class="text-muted small mb-1">{{ __('reports.sales_today_inflow') }}</div>
                                <div class="fs-4 fw-bold text-success">{{ number_format((float) $postedIncomeToday, 2, '.', ' ') }} грн</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="rounded border p-3 h-100">
                                <div class="text-muted small mb-1">{{ __('reports.sales_today_outflow') }}</div>
                                <div class="fs-4 fw-bold text-danger">{{ number_format((float) $postedExpenseToday, 2, '.', ' ') }} грн</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow-sm h-100 bg-transparent border-secondary">
                <div class="card-body">
                    <h4 class="card-title mb-3 text-light">{{ __('reports.cash_position_title') }}</h4>
                    <div class="rounded border p-3 mb-3">
                        <div class="text-muted small mb-1">{{ __('reports.cash_position_balance') }}</div>
                        <div class="fs-4 fw-bold text-primary">{{ number_format((float) $cashBalanceTotal, 2, '.', ' ') }} грн</div>
                    </div>
                    <div class="rounded border p-3">
                        <div class="text-muted small mb-1">{{ __('reports.cash_position_largest') }}</div>
                        @if($largestCashbox)
                        <div class="fw-semibold text-light">{{ $largestCashbox->name }}</div>
                        <div class="fs-5 fw-bold text-light mt-1">{{ number_format((float) ($largestCashbox->value ?? 0), 2, '.', ' ') }} грн</div>
                        @else
                        <div class="text-muted">{{ __('reports.cash_position_not_configured') }}</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mt-1">
        <div class="col-lg-5">
            <div class="card shadow-sm h-100 bg-transparent border-secondary">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                        <h4 class="card-title mb-0 text-light">{{ __('reports.cash_structure_title') }}</h4>
                        <div class="text-muted small">{{ __('reports.cash_current_balances') }}</div>
                    </div>

                    @if(($cashboxes ?? collect())->isEmpty())
                    <div class="text-muted">{{ __('reports.cash_position_not_configured') }}</div>
                    @else
                    <div class="table-responsive">
                        <table class="table table-sm table-dark table-hover align-middle mb-0 bg-transparent">
                            <thead class="table-dark">
                                <tr>
                                    <th>{{ __('reports.cash_table_cashbox') }}</th>
                                    <th class="text-end">{{ __('reports.cash_table_balance') }}</th>
                                    <th class="text-end">{{ __('reports.cash_table_share') }}</th>
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

        <div class="col-lg-7">
            <div class="card shadow-sm h-100 bg-transparent border-secondary">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                        <h4 class="card-title mb-0 text-light">{{ __('reports.top_sales_title') }}</h4>
                        <div class="text-muted small">{{ __('reports.top_sales_rn_posted') }}</div>
                    </div>

                    @if(($topProducts ?? collect())->isEmpty())
                    <div class="text-muted">{{ __('reports.top_sales_no_data') }}</div>
                    @else
                    <div class="table-responsive">
                        <table class="table table-sm table-dark table-hover align-middle mb-0 bg-transparent">
                            <thead class="table-dark">
                                <tr>
                                    <th>{{ __('reports.top_sales_table_product') }}</th>
                                    <th>{{ __('reports.top_sales_table_code') }}</th>
                                    <th class="text-end">{{ __('reports.top_sales_table_sold') }}</th>
                                    <th class="text-end">{{ __('reports.top_sales_table_documents') }}</th>
                                    <th class="text-end">{{ __('reports.top_sales_table_revenue') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($topProducts as $item)
                                <tr>
                                    <td>{{ $item->product_name }}</td>
                                    <td>{{ $item->pnum }}</td>
                                    <td class="text-end fw-semibold">{{ number_format((float) $item->sold_qty, 3, '.', ' ') }}</td>
                                    <td class="text-end">{{ (int) $item->documents_count }}</td>
                                    <td class="text-end fw-semibold text-primary">{{ number_format((float) $item->sold_sum, 2, '.', ' ') }} грн</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm mt-4 bg-transparent border-secondary">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <h4 class="card-title mb-0 text-light">{{ __('reports.orders_journal_title') }}</h4>
                <div class="text-muted small">{{ __('reports.orders_journal_subtitle') }}</div>
            </div>

            @if(($recentOrders ?? collect())->isEmpty())
            <div class="text-muted">{{ __('reports.orders_journal_no_data') }}</div>
            @else
            <div class="table-responsive">
                <table class="table table-sm table-dark table-hover align-middle mb-0 bg-transparent">
                    <thead class="table-dark">
                        <tr>
                            <th>{{ __('reports.orders_journal_table_date') }}</th>
                            <th>{{ __('reports.orders_journal_table_number') }}</th>
                            <th>{{ __('reports.orders_journal_table_client') }}</th>
                            <th>{{ __('reports.orders_journal_table_status') }}</th>
                            <th>{{ __('reports.orders_journal_table_comment') }}</th>
                            <th class="text-end">{{ __('reports.orders_journal_table_sum') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentOrders as $order)
                        @php
                            $orderClient = trim(implode(' ', array_filter([
                                $order->orgname ?? '',
                                trim(implode(' ', array_filter([
                                    $order->secondname ?? '',
                                    $order->name ?? '',
                                    $order->name2 ?? '',
                                ]))),
                            ])));
                        @endphp
                        <tr>
                            <td>{{ $order->data }}</td>
                            <td>{{ $order->num }}</td>
                            <td>{{ $orderClient !== '' ? $orderClient : '—' }}</td>
                            <td>
                                <span class="badge {{ (int) ($order->provodka ?? 0) === 1 ? 'bg-success' : 'bg-secondary' }}">
                                    {{ $order->status_name ?: __('reports.status_new') }}
                                </span>
                            </td>
                            <td>{{ $order->content ?: '—' }}</td>
                            <td class="text-end fw-semibold">{{ number_format((float) ($order->summa ?? 0), 2, '.', ' ') }} грн</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>
    </div>
</div>

<style>
    /* Улучшенная мобильная версия таблиц: закрепляем первую колонку */
    .table-responsive {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    .table th:first-child,
    .table td:first-child {
        position: sticky;
        left: 0;
        z-index: 10;
        background-color: #1a1d20 !important; /* Цвет фона для перекрытия при скролле */
        box-shadow: 4px 0 8px rgba(0, 0, 0, 0.4);
        vertical-align: middle;
        min-width: 160px !important;
    }

    /* На десктопах делаем колонку еще шире */
    @media (min-width: 992px) {
        .table th:first-child,
        .table td:first-child {
            min-width: 280px !important;
        }
    }

    .table thead th:first-child {
        z-index: 11; /* Чтобы заголовок был выше ячеек при прокрутке */
    }
</style>
@endsection
