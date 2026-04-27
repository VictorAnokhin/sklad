@extends('home')

@section('title')
    {{ __('client.title') }}
@endsection

@section('content')
    <style>
        /* Dark theme styles for Client index */
        .client-card {
            background: var(--glass-bg, rgba(255, 255, 255, 0.05));
            border: 1px solid var(--border, #323248);
            border-radius: var(--radius-lg, 12px);
            padding: 16px;
            margin-bottom: 16px;
            color: var(--foreground, #e4e6eb);
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
        }

        .client-card:hover {
            border-color: var(--accent-amber-border, rgba(251, 191, 36, 0.5));
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        }

        .client-card__header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
            padding-bottom: 8px;
            border-bottom: 1px solid var(--border, #323248);
        }

        .client-card__org {
            font-weight: 600;
            font-size: 1.1rem;
            color: var(--accent-amber, #f59e0b);
            text-decoration: none;
        }

        .client-card__org:hover {
            text-decoration: underline;
        }

        .client-card__id {
            font-size: 0.85rem;
            color: var(--muted-foreground, #9ca3af);
            background: rgba(255, 255, 255, 0.05);
            padding: 2px 8px;
            border-radius: 12px;
        }

        .client-card__name {
            font-size: 1rem;
            margin-bottom: 12px;
            font-weight: 500;
        }

        .client-card__info {
            display: flex;
            flex-direction: column;
            gap: 6px;
            font-size: 0.9rem;
            color: var(--muted-foreground, #9ca3af);
            margin-bottom: 16px;
        }

        .client-card__actions {
            display: flex;
            justify-content: flex-end;
        }

        .client-card--empty {
            text-align: center;
            color: var(--muted-foreground, #9ca3af);
            padding: 32px 16px;
            background: transparent;
            border-style: dashed;
        }

        /* Table overrides */
        .client-table--desktop .table {
            border-radius: var(--radius-lg, 12px);
            overflow: hidden;
            border: 1px solid var(--border, #323248);
            --bs-table-color: #e4e6eb;
            --bs-table-bg: #1e1e2d;
            --bs-table-border-color: #323248;
            --bs-table-striped-bg: #2b2b40;
            --bs-table-hover-bg: #323248;
        }

        .client-table--desktop .table td {
            color: #e4e6eb !important;
            vertical-align: middle;
        }

        .client-table--desktop a {
            color: var(--accent-amber, #f59e0b);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s;
        }

        .client-table--desktop a:hover {
            color: var(--accent-amber-dark, #d97706);
            text-decoration: underline;
        }

        .client-table--desktop .table-dark th {
            background-color: #151521 !important;
            border-bottom: 2px solid var(--border, #323248) !important;
            color: var(--accent-amber, #f59e0b) !important;
        }

        /* Modal specific dark styles */
        #clientOrdersModal .modal-content {
            background: #1e1e2d;
            color: #e4e6eb;
            border: 1px solid var(--border, #323248);
        }

        #clientOrdersModal .modal-header {
            border-bottom: 1px solid var(--border, #323248);
        }

        #clientOrdersModal .btn-close {
            filter: invert(1) grayscale(100%) brightness(200%);
        }

        #clientOrdersTableWrap th {
            color: var(--accent-amber, #f59e0b) !important;
            border-bottom: 1px solid var(--border, #323248) !important;
        }

        #clientOrdersTableWrap td {
            border-bottom: 1px solid var(--border, #323248) !important;
            color: #e4e6eb !important;
        }

        #clientOrdersTableWrap a {
            color: var(--accent-amber, #f59e0b);
            text-decoration: none;
        }

        #clientOrdersTableWrap a:hover {
            text-decoration: underline;
        }

        #clientOrdersModal .modal-title {
            color: var(--foreground, #ffffff);
            font-family: var(--header);
        }
    </style>

    @php
        $activeFilters = array_filter($filters ?? [], fn($v) => $v !== '' && $v !== null);
        $clientFilterBtnCls = !empty($activeFilters) ? 'button_submit_start' : 'button_submit_start0';
    @endphp

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="d-flex align-items-center gap-3">
                {{-- Filter button — document style --}}
                <div onclick="clientFilterToggle()" class="{{ $clientFilterBtnCls }}"
                    style="width:70px;height:70px;cursor:pointer;background:linear-gradient(135deg,#fbbf24,#f59e0b);border:none;border-radius:16px;box-shadow:0 4px 12px rgba(251,191,36,0.3);transition:all 0.3s ease;display:flex;flex-direction:column;align-items:center;justify-content:center;">
                    <img src="/img/icon-category.png" alt="{{ __('document.filter.icon_alt') }}"
                        style="width:32px;filter:brightness(0);">
                    <span
                        style="font-size:0.7rem;font-weight:600;color:#000;margin-top:4px;">{{ __('document.filter.search') }}</span>
                </div>
            </div>
            <a href="{{ route('client.show', ['id' => 0]) }}" class="btn btn-primary">➕ {{ __('client.add') }}</a>
        </div>

        @if(!empty($activeFilters))
            <div class="alert alert-warning" style="display:flex;align-items:center;gap:8px;">
                🔍 {{ __('client.filter_active') ?? 'Фільтр активний' }}
                <a href="{{ route('client.index', ['search' => '', 'city' => '', 'phone' => '', 'idstatus' => '', 'pos' => 0]) }}" style="margin-left:8px;">{{ __('client.reset') }}</a>
            </div>
        @endif

        <!-- Desktop: table -->
        <div class="table-responsive client-table--desktop">
            <table class="table table-bordered table-striped table-hover table-dark">
                <thead class="table-dark">
                    <tr>
                        <th>{{ __('client.table_id') }}</th>
                        <th>{{ __('client.table_organization') }}</th>
                        <th>{{ __('client.table_contact') }}</th>
                        <th>{{ __('client.table_phone') }}</th>
                        <th>{{ __('client.table_city') }}</th>
                        <th>{{ __('client.table_orders') }}</th>
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
                                <a href="{{ route('client.show', ['id' => $client->id]) }}">
                                    {{ trim(($client->secondname ?? '') . ' ' . ($client->name ?? '') . ' ' . ($client->fathername ?? '')) ?: ($client->name2 ?? '—') }}
                                </a>
                            </td>
                            <td>{{ $client->phone }}</td>
                            <td>{{ $client->city }}</td>
                            <td>
                                <button type="button" class="btn btn-sm btn-outline-primary client-orders-btn"
                                    data-client-id="{{ $client->id }}"
                                    data-client-name="{{ trim(($client->orgname ?: '') . ' ' . (($client->secondname ?? '') . ' ' . ($client->name ?? ''))) }}"
                                    data-bs-toggle="modal"
                                    data-bs-target="#clientOrdersModal">{{ __('client.view_orders') }}</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center">{{ __('client.no_clients') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Mobile: card list -->
        <div class="client-list--mobile">
            @forelse($clients as $client)
                <div class="client-card">
                    <div class="client-card__header">
                        <a href="{{ route('client.show', ['id' => $client->id]) }}" class="client-card__org">
                            {{ $client->orgname ?: '—' }}
                        </a>
                        <span class="client-card__id">#{{ $client->id }}</span>
                    </div>
                    <div class="client-card__name">
                        <a href="{{ route('client.show', ['id' => $client->id]) }}" class="client-card__name">
                            {{ trim(($client->secondname ?? '') . ' ' . ($client->name ?? '') . ' ' . ($client->fathername ?? '')) ?: ($client->name2 ?? '—') }}
                        </a>
                    </div>
                    <div class="client-card__info">
                        <span class="client-card__phone">📞 {{ $client->phone }}</span>
                        <span class="client-card__city">📍 {{ $client->city }}</span>
                    </div>
                    <div class="client-card__actions">
                        <button type="button" class="btn btn-outline-primary btn-sm client-orders-btn"
                            data-client-id="{{ $client->id }}"
                            data-client-name="{{ trim(($client->orgname ?: '') . ' ' . (($client->secondname ?? '') . ' ' . ($client->name ?? ''))) }}"
                            data-bs-toggle="modal" data-bs-target="#clientOrdersModal">{{ __('client.view_orders') }}</button>
                    </div>
                </div>
            @empty
                <div class="client-card client-card--empty">
                    <div class="text-center text-muted">{{ __('client.no_clients') }}</div>
                </div>
            @endforelse
        </div>

        {{-- Pagination --}}
        @php
        @include('partials.navigator', [
            'pos' => $pos,
            'pos2' => $pos2,
            'max' => $total,
            'routeName' => 'client.index',
            'routeParams' => $filters ?? [],
        ])
    </div>

    {{-- Filter Modal --}}
    <div id="clientFilterModal"
        style="display:none;position:fixed;top:0;left:0;width:100vw;height:100vh;background:rgba(0,0,0,0.7);backdrop-filter:blur(8px);z-index:9999;justify-content:center;align-items:center;">
        <div class="glass-card"
            style="width:700px;max-width:90vw;max-height:80vh;overflow-y:auto;position:relative;margin:0 auto;padding:24px;">
            <div onclick="clientFilterToggle()"
                style="position:absolute;top:12px;right:16px;cursor:pointer;font-size:1.5rem;color:var(--muted-foreground);transition:color 0.2s;z-index:10;">
                ✕</div>

            <h3 style="margin:0 0 16px 0;color:var(--foreground);font-family:var(--header);font-size:1.25rem;">🔍
                {{ __('document.filter.title') }}
            </h3>

            <form action="{{ route('client.index') }}" method="GET">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                    <div style="grid-column: 1 / -1;">
                        <label
                            style="display:block;margin-bottom:4px;font-size:0.85rem;">{{ __('client.search_label') }}</label>
                        <input type="text" name="search" autocomplete="off"
                            placeholder="{{ __('client.search_placeholder') }}" value="{{ $filters['search'] ?? '' }}"
                            style="width:100%;padding:8px 12px;font-size:0.9rem;">
                    </div>

                    <div>
                        <label style="display:block;margin-bottom:4px;font-size:0.85rem;">{{ __('client.city') }}</label>
                        <input type="text" name="city" autocomplete="off" placeholder="{{ __('client.city') }}"
                            value="{{ $filters['city'] ?? '' }}" style="width:100%;padding:8px 12px;font-size:0.9rem;">
                    </div>

                    <div>
                        <label style="display:block;margin-bottom:4px;font-size:0.85rem;">{{ __('client.phone') }}</label>
                        <input type="text" name="phone" autocomplete="off" placeholder="{{ __('client.phone') }}"
                            value="{{ $filters['phone'] ?? '' }}" style="width:100%;padding:8px 12px;font-size:0.9rem;">
                    </div>

                    <div>
                        <label style="display:block;margin-bottom:4px;font-size:0.85rem;">{{ __('client.status') }}</label>
                        <select name="idstatus" style="width:100%;padding:8px 12px;font-size:0.9rem;">
                            <option value="">{{ __('client.all_statuses') }}</option>
                            @foreach($statuses as $s)
                                <option value="{{ $s->id }}" {{ ($filters['idstatus'] ?? '') == $s->id ? 'selected' : '' }}>
                                    {{ $s->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div style="display:flex;gap:10px;margin-top:20px;">
                    <button type="submit"
                        style="flex:1;padding:10px 16px;background:linear-gradient(135deg,#fbbf24,#f59e0b);border:none;border-radius:8px;box-shadow:0 4px 12px rgba(251,191,36,0.3);color:#000;font-weight:600;font-size:0.9rem;cursor:pointer;transition:all 0.3s ease;display:flex;align-items:center;justify-content:center;gap:6px;">
                        <span>🔍</span> {{ __('client.find') }}
                    </button>
                    <a href="{{ route('client.index') }}?search=&city=&phone=&idstatus="
                        style="flex:1;padding:10px 16px;background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.2);border-radius:8px;color:var(--foreground);font-weight:600;font-size:0.9rem;cursor:pointer;transition:all 0.3s ease;display:flex;align-items:center;justify-content:center;gap:6px;text-decoration:none;">
                        <span>✕</span> {{ __('client.reset') }}
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="clientOrdersModal" tabindex="-1" aria-labelledby="clientOrdersModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content"
                style="background-color: #1e1e2d; border: 1px solid #323248; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.5);">
                <div class="modal-header" style="border-bottom: 1px solid #323248;">
                    <h5 class="modal-title" id="clientOrdersModalLabel" style="color: #ffffff; font-family: var(--header);">
                        {{ __('client.modal_title') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                        aria-label="{{ __('client.modal_close') }}"
                        style="filter: invert(1) grayscale(100%) brightness(200%);"></button>
                </div>
                <div class="modal-body" style="background-color: #1e1e2d; color: #e4e6eb;">
                    <div id="clientOrdersLoading" class="text-muted">{{ __('client.modal_loading') }}</div>
                    <div id="clientOrdersEmpty" class="text-muted" style="display:none;">{{ __('client.modal_no_orders') }}
                    </div>
                    <div class="table-responsive" id="clientOrdersTableWrap" style="display:none;">
                        <table class="table table-sm table-hover align-middle table-dark"
                            style="border-radius: 8px; overflow: hidden; --bs-table-bg: transparent; --bs-table-color: #e4e6eb;">
                            <thead style="background-color: #151521;">
                                <tr>
                                    <th style="color: var(--accent-amber, #f59e0b); border-bottom: 1px solid #323248;">
                                        {{ __('client.modal_table_no') }}</th>
                                    <th style="color: var(--accent-amber, #f59e0b); border-bottom: 1px solid #323248;">
                                        {{ __('client.modal_table_date') }}</th>
                                    <th style="color: var(--accent-amber, #f59e0b); border-bottom: 1px solid #323248;">
                                        {{ __('client.modal_table_sum') }}</th>
                                    <th style="color: var(--accent-amber, #f59e0b); border-bottom: 1px solid #323248;">
                                        {{ __('client.modal_table_status') }}</th>
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
        function clientFilterToggle() {
            var d = document.getElementById('clientFilterModal');
            if (d.style.display === 'none' || d.style.display === '') {
                d.style.display = 'flex';
                document.body.style.overflow = 'hidden';
            } else {
                d.style.display = 'none';
                document.body.style.overflow = '';
            }
        }

        document.getElementById('clientFilterModal').addEventListener('click', function (e) {
            if (e.target === this) clientFilterToggle();
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                var d = document.getElementById('clientFilterModal');
                if (d && d.style.display === 'flex') clientFilterToggle();
            }
        });

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
                const clientName = button.getAttribute('data-client-name') || '{{ __('client.client_not_selected') }}';

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
                        empty.textContent = '{{ __('client.modal_error') }}';
                        empty.style.display = 'block';
                    });
            });
        });
    </script>
@endpush
