@extends('home')

@section('title', 'Операційний звіт по залишках')
@section('header_actions')
    @include('partials.report_panel')
@endsection

@section('content')
<div class="container mt-4" data-bs-theme="dark">
    @include('reports.period_form', [
        'periodFormAction' => route('reports.inventory'),
        'periodResetUrl' => route('reports.inventory'),
    ])

    <div class="card shadow-sm mb-4 bg-transparent border-secondary bg-opacity-10">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <div>
                    <h3 class="mb-1 text-light">Операційний звіт по залишках</h3>
                    <div class="text-muted small">Період: {{ $monthLabel }}</div>
                </div>
                <div class="text-muted small">Контроль наявності, дефіциту та швидкості вибуття</div>
            </div>

            <div class="row g-3">
                <div class="col-md-3">
                    <div class="rounded border p-3 h-100">
                        <div class="text-muted small mb-1">SKU в запасах</div>
                        <div class="fs-4 fw-bold text-primary">{{ (int) $skuCount }}</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="rounded border p-3 h-100">
                        <div class="text-muted small mb-1">Залишок, од.</div>
                        <div class="fs-4 fw-bold text-success">{{ number_format((float) $totalQty, 3, '.', ' ') }}</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="rounded border p-3 h-100">
                        <div class="text-muted small mb-1">Out of stock</div>
                        <div class="fs-4 fw-bold text-danger">{{ (int) $outOfStockCount }}</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="rounded border p-3 h-100">
                        <div class="text-muted small mb-1">Low stock</div>
                        <div class="fs-4 fw-bold text-warning">{{ (int) $lowStockCount }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <h4 class="card-title mb-0 text-light">Залишки по SKU</h4>
                <div class="text-muted small">Days inventory розраховано від продажів за вибраний період</div>
            </div>

            @if(($items ?? collect())->isEmpty())
            <div class="text-muted">Даних по залишках немає.</div>
            @else
            <div class="table-responsive">
                <table class="table table-sm table-dark table-hover align-middle mb-0 bg-transparent">
                    <thead class="table-dark">
                        <tr>
                            <th>Товар</th>
                            <th>Код</th>
                            <th>Склад</th>
                            <th class="text-end">Залишок</th>
                            <th class="text-end">Продано</th>
                            <th class="text-end">Продажів / день</th>
                            <th class="text-end">Days inventory</th>
                            <th class="text-center">Статус</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($items as $item)
                        @php
                            $status = $item->stock_status ?? 'normal';
                            $rowClass = $status === 'out_of_stock' ? 'table-danger' : ($status === 'low_stock' ? 'table-warning' : '');
                        @endphp
                        <tr class="{{ $rowClass }}">
                            <td>{{ $item->product_name }}</td>
                            <td>{{ $item->pnum }}</td>
                            <td>{{ $item->sklad_name }}</td>
                            <td class="text-end fw-semibold">{{ number_format((float) $item->count, 3, '.', ' ') }}</td>
                            <td class="text-end">{{ number_format((float) ($item->sold_qty ?? 0), 3, '.', ' ') }}</td>
                            <td class="text-end">{{ number_format((float) ($item->daily_sales ?? 0), 3, '.', ' ') }}</td>
                            <td class="text-end">{{ $item->days_inventory === null ? '∞' : number_format((float) $item->days_inventory, 1, '.', ' ') }}</td>
                            <td class="text-center">
                                @if($status === 'out_of_stock')
                                <span class="badge bg-danger">Дефіцит</span>
                                @elseif($status === 'low_stock')
                                <span class="badge bg-warning text-light">Мінімум</span>
                                @else
                                <span class="badge bg-success">Норма</span>
                                @endif
                            </td>
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
