@extends('home')

@section('title')
Товари ({{ $total ?? 0 }})
@endsection

@section('content')
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <a href="{{ route('goods.show', ['pnum' => 0]) }}" class="btn btn-primary">➕ Додати</a>
    </div>

    <form action="{{ route('goods.index') }}" method="GET" class="mb-3">
        <div class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Пошук (назва, ключі)</label>
                <input type="text" name="fName" class="form-control" placeholder="Назва товару..."
                    value="{{ $filters['fName'] ?? '' }}">
            </div>
            <div class="col-md-2">
                <label class="form-label">Ціна від</label>
                <input type="number" step="0.01" name="priceFrom" class="form-control" placeholder="0.00"
                    value="{{ $filters['priceFrom'] ?? '' }}">
            </div>
            <div class="col-md-2">
                <label class="form-label">Ціна до</label>
                <input type="number" step="0.01" name="priceTo" class="form-control" placeholder="0.00"
                    value="{{ $filters['priceTo'] ?? '' }}">
            </div>
            <div class="col-md-2">
                <label class="form-label">Розділ</label>
                <select name="idcapt" class="form-select">
                    <option value="">— Всі —</option>
                    @foreach($sections as $s)
                    <option value="{{ $s->id }}" {{ ($idcaption ?? '' )==$s->id ? 'selected' : '' }}>
                        {{ $s->val }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2 flex-wrap">
                <div class="form-check mt-2">
                    <input class="form-check-input" type="checkbox" name="skladNone" value="1" id="skladNone" {{
                        ($filters['skladNone'] ?? '' )==='1' ? 'checked' : '' }}>
                    <label class="form-check-label" for="skladNone">Показати без залишку</label>
                </div>
            </div>
        </div>
        <div class="row g-2 mt-2">
            <div class="col-md-12 d-flex gap-2">
                <button class="btn btn-outline-secondary" type="submit">🔍 Знайти</button>
                <a href="{{ route('goods.index') }}?fName=&priceFrom=&priceTo=&idcapt=&skladNone="
                    class="btn btn-outline-danger">✕ Скинути</a>
            </div>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-bordered table-striped table-hover">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Код</th>
                    <th>Назва (Name)</th>
                    <th>Ціна (Pay)</th>
                    <th>Ціна 1 (Pay1)</th>
                    <th>Стара ціна</th>
                    <th>К-сть (Count)</th>
                    <th>Склад</th>
                    <th>Бренд (TGroup)</th>
                </tr>
            </thead>
            <tbody>
                @forelse($comps as $comp)
                <tr>
                    <td>{{ $comp->id }}</td>
                    <td>{{ $comp->cod }}</td>
                    <td>
                        <a href="{{ route('goods.show', ['pnum' => $comp->id]) }}">
                            {{ $comp->name ?? $comp->nickname }}
                        </a>
                    </td>
                    <td>{{ $comp->pay }}</td>
                    <td>{{ $comp->pay1 }}</td>
                    <td>{{ $comp->oldpay }}</td>
                    <td>{{ $comp->count }}</td>
                    <td>{{ $comp->price_sklad }}</td>
                    <td>{{ $comp->tgroup }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="text-center">Немає товарів для відображення</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    @php
        $currentPage = $pos2 > 0 ? (int)floor($pos / $pos2) + 1 : 1;
        $totalPages = $pos2 > 0 ? (int)ceil($total / $pos2) : 1;
        $startPage = max(1, $currentPage - 1);
        $endPage = min($totalPages, $startPage + 2);
        $startPage = max(1, $endPage - 2);
        $pageParams = array_merge($filters, ['idcapt' => $idcaption ?? '', 'sort' => $sort ?? '']);
    @endphp
    @if($totalPages > 1)
    <nav class="mt-3">
        <ul class="pagination justify-content-center">
            <li class="page-item {{ $currentPage <= 1 ? 'disabled' : '' }}">
                <a class="page-link" href="{{ route('goods.index', array_merge($pageParams, ['pos' => 0])) }}">«</a>
            </li>
            <li class="page-item {{ $currentPage <= 1 ? 'disabled' : '' }}">
                <a class="page-link" href="{{ route('goods.index', array_merge($pageParams, ['pos' => $pos - $pos2])) }}">‹</a>
            </li>
            @for($p = $startPage; $p <= $endPage; $p++)
            <li class="page-item {{ $p == $currentPage ? 'active' : '' }}">
                <a class="page-link" href="{{ route('goods.index', array_merge($pageParams, ['pos' => ($p - 1) * $pos2])) }}">{{ $p }}</a>
            </li>
            @endfor
            <li class="page-item {{ $currentPage >= $totalPages ? 'disabled' : '' }}">
                <a class="page-link" href="{{ route('goods.index', array_merge($pageParams, ['pos' => $pos + $pos2])) }}">›</a>
            </li>
            <li class="page-item {{ $currentPage >= $totalPages ? 'disabled' : '' }}">
                <a class="page-link" href="{{ route('goods.index', array_merge($pageParams, ['pos' => ($totalPages - 1) * $pos2])) }}">»</a>
            </li>
        </ul>
    </nav>
    @endif
</div>
@endsection