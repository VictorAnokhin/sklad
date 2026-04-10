@extends('home')

@section('title', 'Закупки')
@section('header_actions')
    @include('partials.report_panel')
@endsection

@section('content')
<div class="container mt-4">
    @include('reports.period_form', [
        'periodFormAction' => route('reports.purchases'),
        'periodResetUrl' => route('reports.purchases'),
    ])

    <div class="card shadow-sm mb-4 border-dark-subtle">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <div>
                    <h3 class="mb-1">Операційний звіт по закупках</h3>
                    <div class="text-muted small">Період: {{ $monthLabel }}</div>
                </div>
                <div class="text-muted small">Постачальники, закупівельні ціни та виконання замовлень</div>
            </div>

            <div class="row g-3">
                <div class="col-md-4">
                    <div class="rounded border p-3 h-100">
                        <div class="text-muted small mb-1">Замовлень на закупку</div>
                        <div class="fs-4 fw-bold text-primary">{{ (int) $purchaseOrdersCount }}</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="rounded border p-3 h-100">
                        <div class="text-muted small mb-1">Замовлено</div>
                        <div class="fs-4 fw-bold text-dark">{{ number_format((float) $purchaseOrderedTotal, 2, '.', ' ') }} грн</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="rounded border p-3 h-100">
                        <div class="text-muted small mb-1">Отримано</div>
                        <div class="fs-4 fw-bold text-success">{{ number_format((float) $purchaseReceivedTotal, 2, '.', ' ') }} грн</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                        <h4 class="card-title mb-0">Постачальники</h4>
                        <div class="text-muted small">Обсяг закупок</div>
                    </div>
                    @if(($supplierSummary ?? collect())->isEmpty())
                    <div class="text-muted">Закупок за період немає.</div>
                    @else
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Постачальник</th>
                                    <th class="text-end">Замовлень</th>
                                    <th class="text-end">Сума</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($supplierSummary as $supplier)
                                <tr>
                                    <td>{{ $supplier->supplier_name }}</td>
                                    <td class="text-end">{{ (int) $supplier->orders_count }}</td>
                                    <td class="text-end fw-semibold">{{ number_format((float) $supplier->ordered_sum, 2, '.', ' ') }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                        <h4 class="card-title mb-0">Закупівельні ціни</h4>
                        <div class="text-muted small">Фактично отримані товари через PN</div>
                    </div>
                    @if(($purchasePrices ?? collect())->isEmpty())
                    <div class="text-muted">Отримань товару за період немає.</div>
                    @else
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Товар</th>
                                    <th>Код</th>
                                    <th class="text-end">Кількість</th>
                                    <th class="text-end">Сер. ціна</th>
                                    <th class="text-end">Сума</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($purchasePrices as $item)
                                <tr>
                                    <td>{{ $item->product_name }}</td>
                                    <td>{{ $item->pnum }}</td>
                                    <td class="text-end">{{ number_format((float) $item->purchased_qty, 3, '.', ' ') }}</td>
                                    <td class="text-end">{{ number_format((float) $item->avg_purchase_price, 2, '.', ' ') }}</td>
                                    <td class="text-end fw-semibold">{{ number_format((float) $item->purchased_sum, 2, '.', ' ') }}</td>
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

    <div class="card shadow-sm mt-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <h4 class="card-title mb-0">Виконання замовлень постачальникам</h4>
                <div class="text-muted small">Строки поставок та повнота отримання</div>
            </div>

            @if(($orders ?? collect())->isEmpty())
            <div class="text-muted">Замовлень постачальникам за період немає.</div>
            @else
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Дата</th>
                            <th>№</th>
                            <th>Постачальник</th>
                            <th class="text-end">Замовлено</th>
                            <th class="text-end">Отримано</th>
                            <th class="text-end">Виконання</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($orders as $order)
                        @php
                            $fulfillmentRate = (float) ($order->fulfillment_rate ?? 0);
                        @endphp
                        <tr class="{{ $fulfillmentRate < 100 ? 'table-warning' : 'table-success' }}">
                            <td>{{ $order->data }}</td>
                            <td>{{ $order->num }}</td>
                            <td>{{ $order->supplier_name }}</td>
                            <td class="text-end">{{ number_format((float) ($order->summa ?? 0), 2, '.', ' ') }}</td>
                            <td class="text-end">{{ number_format((float) ($order->received_sum ?? 0), 2, '.', ' ') }}</td>
                            <td class="text-end fw-semibold">{{ number_format($fulfillmentRate, 1, '.', ' ') }}%</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
