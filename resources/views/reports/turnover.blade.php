@extends('home')

@section('title', 'Оборачуваність товарів')
@section('header_actions')
    @include('partials.report_panel')
@endsection

@section('content')
<div class="container mt-4" data-bs-theme="dark">
    @include('reports.period_form', [
        'periodFormAction' => route('reports.turnover'),
        'periodResetUrl' => route('reports.turnover'),
    ])

    <div class="card shadow-sm mb-4 bg-transparent border-secondary bg-opacity-10">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <div>
                    <h3 class="mb-1 text-light">Звіт по оборачуваності товарів</h3>
                    <div class="text-muted small">Період: {{ $monthLabel }}</div>
                </div>
                <div class="text-muted small">Контроль неликвіду та повільного товару</div>
            </div>

            <div class="row g-3">
                <div class="col-md-4">
                    <div class="rounded border p-3 h-100">
                        <div class="text-muted small mb-1">Dead stock</div>
                        <div class="fs-4 fw-bold text-danger">{{ (int) $deadStockCount }}</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="rounded border p-3 h-100">
                        <div class="text-muted small mb-1">Slow moving</div>
                        <div class="fs-4 fw-bold text-warning">{{ (int) $slowMovingCount }}</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="rounded border p-3 h-100">
                        <div class="text-muted small mb-1">Продано за період</div>
                        <div class="fs-4 fw-bold text-primary">{{ number_format((float) $soldQtyTotal, 3, '.', ' ') }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <h4 class="card-title mb-0 text-light">Реєстр неликвіду та повільної оборачуваності</h4>
                <div class="text-muted small">Понад 90 днів покриття віднесено до slow moving</div>
            </div>

            @if(($items ?? collect())->isEmpty())
            <div class="text-muted">Даних для аналізу немає.</div>
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
                            <th class="text-end">Days inventory</th>
                            <th class="text-center">Dead stock</th>
                            <th class="text-center">Slow moving</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($items as $item)
                        @php
                            $rowClass = ($item->dead_stock ?? false) ? 'table-danger' : (($item->slow_moving ?? false) ? 'table-warning' : '');
                        @endphp
                        <tr class="{{ $rowClass }}">
                            <td>{{ $item->product_name }}</td>
                            <td>{{ $item->pnum }}</td>
                            <td>{{ $item->sklad_name }}</td>
                            <td class="text-end fw-semibold">{{ number_format((float) $item->count, 3, '.', ' ') }}</td>
                            <td class="text-end">{{ number_format((float) ($item->sold_qty ?? 0), 3, '.', ' ') }}</td>
                            <td class="text-end">{{ $item->days_inventory === null ? '∞' : number_format((float) $item->days_inventory, 1, '.', ' ') }}</td>
                            <td class="text-center">
                                @if($item->dead_stock ?? false)
                                <span class="badge bg-danger">Так</span>
                                @else
                                <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if(($item->slow_moving ?? false) && !($item->dead_stock ?? false))
                                <span class="badge bg-warning text-light">Так</span>
                                @else
                                <span class="text-muted">—</span>
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
