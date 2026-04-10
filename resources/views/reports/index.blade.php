@extends('home')

@section('title', 'Зведення')
@section('header_actions')
    @include('partials.report_panel')
@endsection

@section('content')
<div class="container mt-4">
    @include('reports.period_form')

    <div class="card shadow-sm mb-4 border-dark-subtle">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <div>
                    <h3 class="mb-1">Управлінське зведення</h3>
                    <div class="text-muted small">Період: {{ $monthLabel }}</div>
                </div>
                <div class="text-muted small">Оперативна панель продажів, руху коштів і касової позиції</div>
            </div>

            <div class="row g-3">
                <div class="col-md-3">
                    <div class="rounded border p-3 h-100">
                        <div class="text-muted small mb-1">Виручка від продажів</div>
                        <div class="fs-4 fw-bold text-primary">{{ number_format((float) $salesRevenueTotal, 2, '.', ' ') }} грн</div>
                        <div class="text-muted small mt-1">Проведені RN за період</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="rounded border p-3 h-100">
                        <div class="text-muted small mb-1">Грошовий притік</div>
                        <div class="fs-4 fw-bold text-success">{{ number_format((float) $monthIncome, 2, '.', ' ') }} грн</div>
                        <div class="text-muted small mt-1">Проведені PO</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="rounded border p-3 h-100">
                        <div class="text-muted small mb-1">Грошовий відтік</div>
                        <div class="fs-4 fw-bold text-danger">{{ number_format((float) $monthExpense, 2, '.', ' ') }} грн</div>
                        <div class="text-muted small mt-1">Проведені RO</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="rounded border p-3 h-100">
                        <div class="text-muted small mb-1">Чистий грошовий потік</div>
                        <div class="fs-4 fw-bold {{ $cashFlowNet >= 0 ? 'text-primary' : 'text-danger' }}">{{ number_format((float) $cashFlowNet, 2, '.', ' ') }} грн</div>
                        <div class="text-muted small mt-1">Притік мінус відтік</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h4 class="card-title mb-3">Ключові показники продажів</h4>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="rounded border p-3 h-100">
                                <div class="text-muted small mb-1">Документів реалізації</div>
                                <div class="fs-4 fw-bold text-dark">{{ $salesDocsCount }}</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="rounded border p-3 h-100">
                                <div class="text-muted small mb-1">Продано одиниць</div>
                                <div class="fs-4 fw-bold text-secondary">{{ number_format((float) $soldUnitsTotal, 3, '.', ' ') }}</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="rounded border p-3 h-100">
                                <div class="text-muted small mb-1">Середній чек реалізації</div>
                                <div class="fs-4 fw-bold text-success">{{ number_format((float) $averageSalesDoc, 2, '.', ' ') }} грн</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="rounded border p-3 h-100">
                                <div class="text-muted small mb-1">Нові замовлення</div>
                                <div class="fs-4 fw-bold text-warning">{{ $newOrdersCount }}</div>
                                <div class="text-muted small mt-1">Поточний відкритий портфель</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="rounded border p-3 h-100">
                                <div class="text-muted small mb-1">Притік за сьогодні</div>
                                <div class="fs-4 fw-bold text-success">{{ number_format((float) $postedIncomeToday, 2, '.', ' ') }} грн</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="rounded border p-3 h-100">
                                <div class="text-muted small mb-1">Відтік за сьогодні</div>
                                <div class="fs-4 fw-bold text-danger">{{ number_format((float) $postedExpenseToday, 2, '.', ' ') }} грн</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h4 class="card-title mb-3">Касова позиція</h4>
                    <div class="rounded border p-3 mb-3">
                        <div class="text-muted small mb-1">Залишок у касах</div>
                        <div class="fs-4 fw-bold text-primary">{{ number_format((float) $cashBalanceTotal, 2, '.', ' ') }} грн</div>
                    </div>
                    <div class="rounded border p-3">
                        <div class="text-muted small mb-1">Найбільша каса</div>
                        @if($largestCashbox)
                        <div class="fw-semibold">{{ $largestCashbox->name }}</div>
                        <div class="fs-5 fw-bold text-dark mt-1">{{ number_format((float) ($largestCashbox->value ?? 0), 2, '.', ' ') }} грн</div>
                        @else
                        <div class="text-muted">Каси не налаштовані.</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mt-1">
        <div class="col-lg-5">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                        <h4 class="card-title mb-0">Структура кас</h4>
                        <div class="text-muted small">Поточні залишки</div>
                    </div>

                    @if(($cashboxes ?? collect())->isEmpty())
                    <div class="text-muted">Каси не налаштовані.</div>
                    @else
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Каса</th>
                                    <th class="text-end">Залишок</th>
                                    <th class="text-end">Частка</th>
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
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                        <h4 class="card-title mb-0">Топ продажів за період</h4>
                        <div class="text-muted small">Проведені RN</div>
                    </div>

                    @if(($topProducts ?? collect())->isEmpty())
                    <div class="text-muted">За вибраний період продажів не знайдено.</div>
                    @else
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Товар</th>
                                    <th>Код</th>
                                    <th class="text-end">Продано</th>
                                    <th class="text-end">Документів</th>
                                    <th class="text-end">Виручка</th>
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

    <div class="card shadow-sm mt-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <h4 class="card-title mb-0">Журнал замовлень</h4>
                <div class="text-muted small">Останні замовлення за період</div>
            </div>

            @if(($recentOrders ?? collect())->isEmpty())
            <div class="text-muted">За вибраний період замовлень не знайдено.</div>
            @else
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Дата</th>
                            <th>№</th>
                            <th>Клієнт</th>
                            <th>Статус</th>
                            <th>Коментар</th>
                            <th class="text-end">Сума</th>
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
                                    {{ $order->status_name ?: 'Новий' }}
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
@endsection
