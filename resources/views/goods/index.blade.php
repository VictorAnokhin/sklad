@extends('home')

@section('title')
Товари ({{ $total ?? 0 }})
@endsection

@section('content')
<div class="container mt-4">
    @php
        $selectedTop = (string)($idglava ?? '');
        $selectedSub = (string)($idcaption ?? '');
        $availableSubs = collect($subs[$selectedTop] ?? []);
        $isCategoryFiltered = $selectedTop !== '' || $selectedSub !== '';
    @endphp

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>Товари ({{ $total ?? 0 }})</h2>
        <a href="{{ route('goods.show', ['pnum' => 0]) }}" class="btn btn-primary">➕ Додати</a>
    </div>

    @if(!$isCategoryFiltered)
    <div class="alert alert-info">
        Категорії не вибрані. Показані перші 20 товарів, відсортовані за популярністю (`hit desc`).
    </div>
    @endif

    <form action="{{ route('goods.index') }}" method="GET" class="mb-3">
        <div class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Пошук (назва, ключі)</label>
                <input type="text" name="fName" class="form-control" placeholder="Назва товару..."
                    value="{{ $filters['fName'] ?? '' }}">
            </div>
            <div class="col-md-2">
                <label class="form-label">Категорія</label>
                <select name="igla" class="form-select" onchange="const sub=this.form.querySelector('[name=idcapt]'); if(sub){sub.value='';} this.form.submit();">
                    <option value="">— Всі —</option>
                    @foreach(($tops ?? []) as $top)
                    <option value="{{ $top->id }}" {{ $selectedTop === (string)$top->id ? 'selected' : '' }}>
                        {{ $top->val }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Підкатегорія</label>
                <select name="idcapt" class="form-select" onchange="this.form.submit()" {{ $selectedTop === '' ? 'disabled' : '' }}>
                    <option value="">— Всі —</option>
                    @foreach($availableSubs as $sub)
                    <option value="{{ $sub->id }}" {{ $selectedSub === (string)$sub->id ? 'selected' : '' }}>
                        {{ $sub->val }}
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
                <a href="{{ route('goods.index') }}?fName=&igla=&idcapt=&skladNone="
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
                    <td><a href="{{ route('goods.show', ['pnum' => $comp->id]) }}">{{ $comp->id }}</a></td>
                    <td>{{ $comp->cod }}</td>
                    <td>
                        <a href="{{ route('goods.show', ['pnum' => $comp->id]) }}">
                            {{ $comp->name ?? $comp->nickname }}
                        </a>
                    </td>
                    <td>{{ number_format((float)($comp->price_pay ?? 0), 2, '.', ' ') }}</td>
                    <td>{{ number_format((float)($comp->price_pay1 ?? 0), 2, '.', ' ') }}</td>
                    <td>{{ number_format((float)($comp->price_oldpay ?? 0), 2, '.', ' ') }}</td>
                    <td>{{ rtrim(rtrim(number_format((float)($comp->price_count ?? 0), 3, '.', ''), '0'), '.') }}</td>
                    <td>{{ (string)($comp->price_sklad ?? '0') === '1' ? 'Є' : 'Немає' }}</td>
                    <td>{{ $comp->price_tgroup ?? '—' }}</td>
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
        $pageParams = array_merge($filters, ['igla' => $idglava ?? '', 'idcapt' => $idcaption ?? '', 'sort' => $sort ?? '']);
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
