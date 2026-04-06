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
                    <th>Замовлення</th>
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
                    <td>
                        <button
                            type="button"
                            class="btn btn-sm btn-outline-primary client-orders-btn"
                            data-client-id="{{ $client->id }}"
                            data-client-name="{{ trim(($client->orgname ?: '') . ' ' . (($client->secondname ?? '') . ' ' . ($client->name ?? ''))) }}"
                            data-bs-toggle="modal"
                            data-bs-target="#clientOrdersModal"
                        >Переглянути</button>
                    </td>
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

<div class="modal fade" id="clientOrdersModal" tabindex="-1" aria-labelledby="clientOrdersModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="clientOrdersModalLabel">Замовлення клієнта</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрити"></button>
            </div>
            <div class="modal-body">
                <div id="clientOrdersLoading" class="text-muted">Завантаження...</div>
                <div id="clientOrdersEmpty" class="text-muted" style="display:none;">Замовлень не знайдено.</div>
                <div class="table-responsive" id="clientOrdersTableWrap" style="display:none;">
                    <table class="table table-sm table-hover align-middle">
                        <thead>
                            <tr>
                                <th>№</th>
                                <th>Дата</th>
                                <th>Сума</th>
                                <th>Статус</th>
                            </tr>
                        </thead>
                        <tbody id="clientOrdersTbody"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('clientOrdersModal');
    const title = document.getElementById('clientOrdersModalLabel');
    const loading = document.getElementById('clientOrdersLoading');
    const empty = document.getElementById('clientOrdersEmpty');
    const tableWrap = document.getElementById('clientOrdersTableWrap');
    const tbody = document.getElementById('clientOrdersTbody');

    if (!modal || !title || !loading || !empty || !tableWrap || !tbody) {
        return;
    }

    const escapeHtml = (value) => String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');

    modal.addEventListener('show.bs.modal', (event) => {
        const button = event.relatedTarget;
        if (!button) {
            return;
        }

        const clientId = button.getAttribute('data-client-id');
        const clientName = button.getAttribute('data-client-name') || 'клієнта';

        title.textContent = `Замовлення: ${clientName}`;
        loading.style.display = 'block';
        empty.style.display = 'none';
        tableWrap.style.display = 'none';
        tbody.innerHTML = '';

        fetch(`/client/${clientId}/orders`)
            .then((response) => response.json())
            .then((orders) => {
                loading.style.display = 'none';

                if (!orders.length) {
                    empty.style.display = 'block';
                    return;
                }

                tbody.innerHTML = orders.map((order) => `
                    <tr>
                        <td><a href="${escapeHtml(order.link_url)}">${escapeHtml(order.num)}</a></td>
                        <td>${escapeHtml(order.data)}</td>
                        <td>${escapeHtml(order.summa)} грн</td>
                        <td>
                            <span class="badge" style="background:${escapeHtml(order.status_color || '#6b7280')}; color:#fff;">
                                ${escapeHtml(order.status_name)}
                            </span>
                        </td>
                    </tr>
                `).join('');
                tableWrap.style.display = 'block';
            })
            .catch(() => {
                loading.style.display = 'none';
                empty.textContent = 'Не вдалося завантажити замовлення.';
                empty.style.display = 'block';
            });
    });
});
</script>
@endpush
