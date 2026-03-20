@extends('home')

@section('title')
Клієнти
@endsection

@section('content')
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>Клієнти ({{ $total ?? 0 }})</h2>
        <a href="{{ route('client.show', ['id' => 0]) }}" class="btn btn-primary">➕ Додати</a>
    </div>

    <form action="{{ route('client.index') }}" method="GET" class="mb-3">
        <div class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Пошук (ПІБ, організація...)</label>
                <input type="text" name="search" class="form-control" placeholder="Пошук..."
                       value="{{ $filters['search'] ?? '' }}">
            </div>
            <div class="col-md-2">
                <label class="form-label">Місто</label>
                <input type="text" name="city" class="form-control" placeholder="Місто"
                       value="{{ $filters['city'] ?? '' }}">
            </div>
            <div class="col-md-2">
                <label class="form-label">Телефон</label>
                <input type="text" name="phone" class="form-control" placeholder="Телефон"
                       value="{{ $filters['phone'] ?? '' }}">
            </div>
            <div class="col-md-2">
                <label class="form-label">Статус</label>
                <select name="idstatus" class="form-select">
                    <option value="">— Всі —</option>
                    @foreach($statuses as $s)
                        <option value="{{ $s->id }}" {{ ($filters['idstatus'] ?? '') == $s->id ? 'selected' : '' }}>
                            {{ $s->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button class="btn btn-outline-secondary" type="submit">🔍 Знайти</button>
                <a href="{{ route('client.index') }}?search=&city=&phone=&idstatus=" class="btn btn-outline-danger">✕ Скинути</a>
            </div>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-bordered table-striped table-hover">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Організація</th>
                    <th>ПІБ (Контактна особа)</th>
                    <th>Телефон</th>
                    <th>Місто</th>
                    <th>Рейтинг (Top)</th>
                </tr>
            </thead>
            <tbody>
                @forelse($clients as $client)
                <tr>
                    <td>{{ $client->id }}</td>
                    <td>
                        <a href="{{ route('client.show', ['id' => $client->id]) }}">
                            {{ $client->orgname ?: '—' }}
                        </a>
                    </td>
                    <td>
                        {{ trim(($client->secondname ?? '') . ' ' . ($client->name ?? '') . ' ' . ($client->fathername ?? '')) ?: ($client->name2 ?? '—') }}
                    </td>
                    <td>{{ $client->phone }}</td>
                    <td>{{ $client->city }}</td>
                    <td>{{ $client->top }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center">Немає клієнтів для відображення</td>
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
    @endphp
    @if($totalPages > 1)
    <nav class="mt-3">
        <ul class="pagination justify-content-center">
            <li class="page-item {{ $currentPage <= 1 ? 'disabled' : '' }}">
                <a class="page-link" href="{{ route('client.index', array_merge($filters, ['pos' => 0])) }}">«</a>
            </li>
            <li class="page-item {{ $currentPage <= 1 ? 'disabled' : '' }}">
                <a class="page-link" href="{{ route('client.index', array_merge($filters, ['pos' => $pos - $pos2])) }}">‹</a>
            </li>
            @for($p = $startPage; $p <= $endPage; $p++)
            <li class="page-item {{ $p == $currentPage ? 'active' : '' }}">
                <a class="page-link" href="{{ route('client.index', array_merge($filters, ['pos' => ($p - 1) * $pos2])) }}">{{ $p }}</a>
            </li>
            @endfor
            <li class="page-item {{ $currentPage >= $totalPages ? 'disabled' : '' }}">
                <a class="page-link" href="{{ route('client.index', array_merge($filters, ['pos' => $pos + $pos2])) }}">›</a>
            </li>
            <li class="page-item {{ $currentPage >= $totalPages ? 'disabled' : '' }}">
                <a class="page-link" href="{{ route('client.index', array_merge($filters, ['pos' => ($totalPages - 1) * $pos2])) }}">»</a>
            </li>
        </ul>
    </nav>
    @endif
</div>
@endsection