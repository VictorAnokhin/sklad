@extends('home')

@section('title', 'Товарні залишки')
@section('header_actions')
    @include('partials.report_panel')
@endsection

@section('content')
<div class="container mt-4 reports-page" data-bs-theme="dark">
    @include('reports.period_form', [
        'periodFormAction' => route('reports.stocks'),
        'periodResetUrl' => route('reports.stocks'),
        'periodResetLabel' => 'Поточний місяць',
        'periodHiddenFields' => [
            'sklad' => $skladId,
            'q' => $search,
            'sort' => $sort,
            'direction' => $direction,
        ],
    ])

    <div class="card shadow-sm mb-4 bg-transparent border-secondary">
        <div class="card-body">
            <form method="get" action="{{ route('reports.stocks') }}" class="row g-3 align-items-end">
                <input type="hidden" name="date_from" value="{{ $dateFrom }}">
                <input type="hidden" name="date_to" value="{{ $dateTo }}">
                <div class="col-md-4">
                    <label for="sklad" class="form-label">Склад</label>
                    <select id="sklad" name="sklad" class="form-select">
                        <option value="">— Усі склади —</option>
                        @foreach(($sklads ?? collect()) as $sklad)
                        <option value="{{ $sklad->id }}" {{ (string) $skladId === (string) $sklad->id ? 'selected' : '' }}>
                            {{ $sklad->name }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-5">
                    <label for="q" class="form-label">Пошук товару</label>
                    <input type="text" id="q" name="q" value="{{ $search }}" class="form-control" placeholder="Код, назва, артикул">
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Показати</button>
                    <a href="{{ route('reports.stocks') }}" class="btn btn-outline-secondary">Скинути</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm mb-4 bg-transparent border-secondary bg-opacity-10">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <h4 class="card-title mb-0 text-light">Аналітика запасів та продажів</h4>
                <div class="text-muted small">Період: {{ $monthLabel }}</div>
            </div>

            <div class="row g-3">
                <div class="col-md-2">
                    <div class="rounded border p-3 h-100">
                        <div class="text-muted small mb-1">SKU в залишках</div>
                        <div class="fs-5 fw-bold text-primary">{{ $totalCount }}</div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="rounded border p-3 h-100">
                        <div class="text-muted small mb-1">Проданих SKU</div>
                        <div class="fs-5 fw-bold text-secondary">{{ $soldSkuCount }}</div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="rounded border p-3 h-100">
                        <div class="text-muted small mb-1">Залишок</div>
                        <div class="fs-5 fw-bold text-success">{{ number_format((float) $totalQty, 3, '.', ' ') }}</div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="rounded border p-3 h-100">
                        <div class="text-muted small mb-1">Продано одиниць</div>
                        <div class="fs-5 fw-bold text-warning">{{ number_format((float) $soldQtyTotal, 3, '.', ' ') }}</div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="rounded border p-3 h-100">
                        <div class="text-muted small mb-1">Виручка</div>
                        <div class="fs-5 fw-bold text-light">{{ number_format((float) $revenueTotal, 2, '.', ' ') }} грн</div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="rounded border p-3 h-100">
                        <div class="text-muted small mb-1">Валовий прибуток</div>
                        <div class="fs-5 fw-bold {{ $grossProfitTotal >= 0 ? 'text-primary' : 'text-danger' }}">{{ number_format((float) $grossProfitTotal, 2, '.', ' ') }} грн</div>
                        <div class="text-muted small mt-1">Маржа {{ number_format((float) $grossMarginTotal, 1, '.', ' ') }}%</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-1">
        <div class="col-md-4">
            <div class="card shadow-sm h-100 border-primary">
                <div class="card-body">
                    <div class="text-muted small mb-1">Записів</div>
                    <div class="fs-3 fw-bold text-primary">{{ $totalCount }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm h-100 border-success">
                <div class="card-body">
                    <div class="text-muted small mb-1">Загальний залишок</div>
                    <div class="fs-3 fw-bold text-success">{{ number_format((float) $totalQty, 3, '.', ' ') }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm h-100 border-warning">
                <div class="card-body">
                    <div class="text-muted small mb-1">Маржинальність продажів</div>
                    <div class="fs-3 fw-bold {{ $grossMarginTotal >= 0 ? 'text-warning' : 'text-danger' }}">{{ number_format((float) $grossMarginTotal, 1, '.', ' ') }}%</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm mt-4 bg-transparent border-secondary">
        <div class="card-body">
            @if(($items ?? collect())->isEmpty())
            <div class="text-muted">Залишків за вибраними умовами не знайдено.</div>
            @else
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <h4 class="card-title mb-0 text-light">Товарний звіт торгової організації</h4>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <div class="text-muted small">Поточні запаси + реалізація за вибраний період</div>
                    <a href="{{ route('reports.stocks.export', ['sklad' => $skladId, 'q' => $search, 'date_from' => $dateFrom, 'date_to' => $dateTo, 'sort' => $sort, 'direction' => $direction]) }}" class="btn btn-sm btn-outline-primary">
                        Експорт CSV
                    </a>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-dark table-hover align-middle mb-0 bg-transparent">
                    <thead class="table-dark">
                        <tr>
                            <th>
                                <a href="{{ route('reports.stocks', ['sklad' => $skladId, 'q' => $search, 'date_from' => $dateFrom, 'date_to' => $dateTo, 'sort' => 'product_name', 'direction' => ($sort === 'product_name' && $direction === 'asc') ? 'desc' : 'asc']) }}" class="text-decoration-none text-reset">
                                    Товар {{ $sort === 'product_name' ? ($direction === 'asc' ? '↑' : '↓') : '↕' }}
                                </a>
                            </th>
                            <th>Код</th>
                            <th>
                                <a href="{{ route('reports.stocks', ['sklad' => $skladId, 'q' => $search, 'date_from' => $dateFrom, 'date_to' => $dateTo, 'sort' => 'sklad_name', 'direction' => ($sort === 'sklad_name' && $direction === 'asc') ? 'desc' : 'asc']) }}" class="text-decoration-none text-reset">
                                    Склад {{ $sort === 'sklad_name' ? ($direction === 'asc' ? '↑' : '↓') : '↕' }}
                                </a>
                            </th>
                            <th class="text-end">
                                <a href="{{ route('reports.stocks', ['sklad' => $skladId, 'q' => $search, 'date_from' => $dateFrom, 'date_to' => $dateTo, 'sort' => 'count', 'direction' => ($sort === 'count' && $direction === 'asc') ? 'desc' : 'asc']) }}" class="text-decoration-none text-reset">
                                    Залишок {{ $sort === 'count' ? ($direction === 'asc' ? '↑' : '↓') : '↕' }}
                                </a>
                            </th>
                            <th class="text-end">
                                <a href="{{ route('reports.stocks', ['sklad' => $skladId, 'q' => $search, 'date_from' => $dateFrom, 'date_to' => $dateTo, 'sort' => 'sold_qty', 'direction' => ($sort === 'sold_qty' && $direction === 'asc') ? 'desc' : 'asc']) }}" class="text-decoration-none text-reset">
                                    Продано, од. {{ $sort === 'sold_qty' ? ($direction === 'asc' ? '↑' : '↓') : '↕' }}
                                </a>
                            </th>
                            <th class="text-end">
                                <a href="{{ route('reports.stocks', ['sklad' => $skladId, 'q' => $search, 'date_from' => $dateFrom, 'date_to' => $dateTo, 'sort' => 'sold_sum', 'direction' => ($sort === 'sold_sum' && $direction === 'asc') ? 'desc' : 'asc']) }}" class="text-decoration-none text-reset">
                                    Виручка {{ $sort === 'sold_sum' ? ($direction === 'asc' ? '↑' : '↓') : '↕' }}
                                </a>
                            </th>
                            <th class="text-end">
                                <a href="{{ route('reports.stocks', ['sklad' => $skladId, 'q' => $search, 'date_from' => $dateFrom, 'date_to' => $dateTo, 'sort' => 'gross_profit', 'direction' => ($sort === 'gross_profit' && $direction === 'asc') ? 'desc' : 'asc']) }}" class="text-decoration-none text-reset">
                                    Валовий прибуток {{ $sort === 'gross_profit' ? ($direction === 'asc' ? '↑' : '↓') : '↕' }}
                                </a>
                            </th>
                            <th class="text-end">
                                <a href="{{ route('reports.stocks', ['sklad' => $skladId, 'q' => $search, 'date_from' => $dateFrom, 'date_to' => $dateTo, 'sort' => 'gross_margin', 'direction' => ($sort === 'gross_margin' && $direction === 'asc') ? 'desc' : 'asc']) }}" class="text-decoration-none text-reset">
                                    Маржа {{ $sort === 'gross_margin' ? ($direction === 'asc' ? '↑' : '↓') : '↕' }}
                                </a>
                            </th>
                            <th>Гарантія</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($items as $item)
                        @php
                            $isLoss = (float) ($item->gross_profit ?? 0) < 0;
                        @endphp
                        <tr class="{{ $isLoss ? 'table-danger' : '' }}">
                            <td>{{ $item->product_name }}</td>
                            <td>{{ $item->pnum }}</td>
                            <td>{{ $item->sklad_name }}</td>
                            <td class="text-end fw-semibold">{{ number_format((float) $item->count, 3, '.', ' ') }}</td>
                            <td class="text-end">{{ number_format((float) ($item->sold_qty ?? 0), 3, '.', ' ') }}</td>
                            <td class="text-end fw-semibold text-light">{{ number_format((float) ($item->sold_sum ?? 0), 2, '.', ' ') }}</td>
                            <td class="text-end fw-semibold {{ ($item->gross_profit ?? 0) >= 0 ? 'text-primary' : 'text-danger' }}">{{ number_format((float) ($item->gross_profit ?? 0), 2, '.', ' ') }}</td>
                            <td class="text-end {{ ($item->gross_margin ?? 0) < 0 ? 'text-danger fw-semibold' : '' }}">{{ number_format((float) ($item->gross_margin ?? 0), 1, '.', ' ') }}%</td>
                            <td>{{ $item->garant ?: '—' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if(method_exists($items, 'lastPage') && $items->lastPage() > 1)
            <nav class="mt-3">
                <ul class="pagination justify-content-center mb-0">
                    <li class="page-item {{ $items->onFirstPage() ? 'disabled' : '' }}">
                        <a class="page-link" href="{{ $items->onFirstPage() ? '#' : $items->url(1) }}" aria-label="Перша сторінка">«</a>
                    </li>
                    <li class="page-item {{ $items->onFirstPage() ? 'disabled' : '' }}">
                        <a class="page-link" href="{{ $items->onFirstPage() ? '#' : $items->previousPageUrl() }}" aria-label="Попередня сторінка">‹</a>
                    </li>
                    <li class="page-item {{ $items->hasMorePages() ? '' : 'disabled' }}">
                        <a class="page-link" href="{{ $items->hasMorePages() ? $items->nextPageUrl() : '#' }}" aria-label="Наступна сторінка">›</a>
                    </li>
                    <li class="page-item {{ $items->hasMorePages() ? '' : 'disabled' }}">
                        <a class="page-link" href="{{ $items->hasMorePages() ? $items->url($items->lastPage()) : '#' }}" aria-label="Остання сторінка">»</a>
                    </li>
                </ul>
            </nav>
            @endif
            @endif
        </div>
    </div>
</div>
@endsection
