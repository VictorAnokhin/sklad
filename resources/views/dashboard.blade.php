@extends('home')

@section('title')
{{ __('dashboard.title', ['name' => session('name1')]) }}
@endsection

@section('content')
<style>
    /* Dark Theme Dashboard Styles */
    .dashboard-container {
        color: #e4e6eb;
    }
    .glass-card-dashboard {
        background: var(--glass-bg, rgba(30, 30, 45, 0.7));
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        border: 1px solid var(--border, #323248);
        border-radius: var(--radius-lg, 12px);
        color: #e4e6eb;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
    }
    .glass-card-dashboard .card-title {
        color: #f1f1f1;
        font-weight: 600;
    }
    .glass-card-dashboard .text-muted {
        color: #a0a0b0 !important;
    }
    
    /* List groups inside dark cards */
    .glass-card-dashboard .list-group-item {
        background: transparent;
        border-color: #323248;
        color: #e4e6eb;
    }
    .glass-card-dashboard .list-group-item-action:hover {
        background: rgba(255, 255, 255, 0.05);
        color: #fff;
    }
    .glass-card-dashboard .list-group-item:last-child {
        border-bottom: none;
    }

    /* Accent colors adjusted for dark theme */
    .text-success-accent {
        color: #28c76f !important;
    }
    .badge-accent {
        background-color: var(--accent, #ea5455) !important;
        color: #fff;
    }
    .btn-outline-accent {
        color: var(--accent, #f9a826);
        border-color: var(--accent, #f9a826);
    }
    .btn-outline-accent:hover {
        background-color: var(--accent, #f9a826);
        color: #fff;
        border-color: var(--accent, #f9a826);
    }
    
    .border-success-accent {
        border-color: #28c76f !important;
        border-left: 4px solid #28c76f !important;
    }
    .transport-lookup-panel .form-control {
        background: rgba(255, 255, 255, 0.06);
        border-color: #323248;
        color: #f1f1f1;
    }
    .transport-lookup-panel .form-control:focus {
        background: rgba(255, 255, 255, 0.08);
        border-color: var(--accent, #f9a826);
        color: #fff;
        box-shadow: 0 0 0 .2rem rgba(249, 168, 38, .15);
    }
    .transport-lookup-panel .transport-result {
        min-height: 180px;
        border: 1px solid #323248;
        border-radius: 10px;
        background: rgba(0, 0, 0, 0.22);
        padding: 1rem;
        overflow: auto;
    }
    .transport-lookup-panel .transport-result__grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: .75rem;
    }
    .transport-lookup-panel .transport-result__item {
        border: 1px solid rgba(255, 255, 255, .08);
        border-radius: 8px;
        padding: .75rem;
        background: rgba(255, 255, 255, .035);
    }
    .transport-lookup-panel .transport-result__label {
        color: #a0a0b0;
        font-size: .78rem;
        margin-bottom: .25rem;
        text-transform: uppercase;
    }
    .transport-lookup-panel pre {
        color: #d1d5db;
        white-space: pre-wrap;
        word-break: break-word;
        margin: 0;
    }
</style>

<div class="container mt-4 dashboard-container pb-5">
    <div class="row g-4">
        <div class="col-12">
            <div class="card glass-card-dashboard border-success-accent">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="card-title mb-1">📈 {{ __('dashboard.daily_income_title') }}</h4>
                        <div class="text-muted small">{{ $today }}</div>
                    </div>
                    <div class="fs-4 fw-bold text-success-accent">{{ number_format((float)($dailyIncome ?? 0), 2, '.', ' ') }} грн</div>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card glass-card-dashboard h-100">
                <div class="card-body">
                    <h4 class="card-title mb-3">💰 {{ __('dashboard.cashbox_balances') }}</h4>

                    @if(($cashboxes ?? collect())->isEmpty())
                    <div class="text-muted">{{ __('dashboard.cashboxes_not_configured') }}</div>
                    @else
                    <div class="list-group list-group-flush">
                        @foreach($cashboxes as $cashbox)
                        <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <div>
                                <div class="fw-semibold">{{ $cashbox->name }}</div>
                                <div class="text-muted small">{{ __('dashboard.cashbox_id') }}: {{ $cashbox->id }}</div>
                            </div>
                            <div class="fw-bold fs-5">{{ number_format((float)($cashbox->value ?? 0), 2, '.', ' ') }} грн</div>
                        </div>
                        @endforeach
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card glass-card-dashboard h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="card-title mb-0">🛒 {{ __('dashboard.new_orders') }}</h4>
                        <span class="badge badge-accent">{{ count($newOrders ?? []) }}</span>
                    </div>

                    @if(($newOrders ?? collect())->isEmpty())
                    <div class="text-muted">{{ __('dashboard.no_new_orders') }}</div>
                    @else
                    <div class="list-group list-group-flush mb-3">
                        @foreach($newOrders as $order)
                        @php
                            $clientName = trim(($order->orgname ?? '') ?: (trim(($order->secondname ?? '') . ' ' . ($order->u_name ?? '') . ' ' . ($order->fathername ?? ''))));
                        @endphp
                        <a href="{{ route('document.show', ['doc' => 'ZOUT', 'doc_id' => $order->id, 'num' => $order->num, 'year' => substr((string)($order->data ?? date('d-m-Y')), -4)]) }}"
                            class="list-group-item list-group-item-action px-2 rounded mb-1">
                            <div class="d-flex justify-content-between align-items-start gap-2">
                                <div style="min-width:0;">
                                    <div class="d-flex align-items-center gap-2 flex-wrap">
                                        <span class="fw-semibold">{{ __('dashboard.order_number', ['num' => $order->num]) }}</span>
                                        <span class="text-muted small">{{ $order->data }} {{ $order->time }}</span>
                                    </div>
                                    @if($clientName)
                                        <div class="small mt-1" style="color:#a0c4ff;">👤 {{ $clientName }}</div>
                                    @endif
                                    @if(!empty($order->phone))
                                        <div class="small" style="color:#9ca3af;">📞 {{ $order->phone }}</div>
                                    @endif
                                    @if(!empty($order->content))
                                        <div class="small mt-1" style="color:#d1d5db;font-style:italic;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:280px;"
                                             title="{{ $order->content }}">💬 {{ $order->content }}</div>
                                    @endif
                                </div>
                                <div class="fw-bold text-success-accent text-nowrap">{{ number_format((float)($order->summa ?? 0), 2, '.', ' ') }} грн</div>
                            </div>
                        </a>
                        @endforeach
                    </div>

                    <div class="mt-2">
                        <a href="{{ route('document.index', ['doc' => 'ZOUT']) }}" class="btn btn-outline-accent w-100">{{ __('dashboard.view_all_orders') }}</a>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="card glass-card-dashboard mt-4 transport-lookup-panel">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-3">
                <div>
                    <h5 class="card-title mb-1">🚘 {{ __('dashboard.transport_lookup_title') }}</h5>
                    <div class="text-muted small">{{ __('dashboard.transport_lookup_hint') }}</div>
                </div>
                <a href="https://auto.ria.com/uk/check-car/" target="_blank" rel="noopener" class="btn btn-sm btn-outline-accent">
                    API Auto.RIA
                </a>
            </div>

            <form id="transport-lookup-form" class="row g-3 align-items-end mb-3">
                @csrf
                <div class="col-12 col-md-8 col-lg-9">
                    <label for="transport-plate-input" class="form-label text-muted small mb-1">{{ __('dashboard.transport_plate_label') }}</label>
                    <input
                        type="text"
                        id="transport-plate-input"
                        name="plate"
                        class="form-control form-control-lg text-uppercase"
                        placeholder="AB2628IH або VIN"
                        maxlength="64"
                        autocomplete="off"
                    >
                </div>
                <div class="col-12 col-md-4 col-lg-3">
                    <button type="submit" id="transport-lookup-submit" class="btn btn-outline-accent btn-lg w-100">
                        {{ __('dashboard.transport_lookup_submit') }}
                    </button>
                </div>
            </form>

            <div id="transport-lookup-result" class="transport-result text-muted">
                {{ __('dashboard.transport_lookup_empty') }}
            </div>
        </div>
    </div>

    <div class="card glass-card-dashboard mt-4">
        <div class="card-body">
            <h5 class="card-title mb-3">⚙️ {{ __('dashboard.session') }}</h5>
            <div class="row row-cols-1 row-cols-md-3 g-3 text-muted small">
                <div><strong class="text-light">{{ __('dashboard.session_user_id') }}:</strong> {{ session('id') }}</div>
                <div><strong class="text-light">{{ __('dashboard.session_firma') }}:</strong> {{ session('fid') }}</div>
                <div><strong class="text-light">{{ __('dashboard.session_doc') }}:</strong> {{ session('doc') }}</div>
                <div><strong class="text-light">{{ __('dashboard.session_balans') }}:</strong> {{ session('balans') }}</div>
                <div><strong class="text-light">{{ __('dashboard.session_name') }}:</strong> {{ session('name1') }}</div>
                <div><strong class="text-light">{{ __('dashboard.session_login') }}:</strong> {{ session('login') }}</div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('transport-lookup-form');
    const input = document.getElementById('transport-plate-input');
    const submit = document.getElementById('transport-lookup-submit');
    const result = document.getElementById('transport-lookup-result');
    const endpoint = @json(route('dashboard.transportLookup'));
    const csrf = form?.querySelector('input[name="_token"]')?.value || '';

    const labels = {
        loading: @json(__('dashboard.transport_lookup_loading')),
        empty: @json(__('dashboard.transport_lookup_empty')),
        error: @json(__('dashboard.transport_lookup_error')),
    };

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function flattenObject(value, prefix = '', output = {}) {
        if (Array.isArray(value)) {
            value.forEach((item, index) => flattenObject(item, `${prefix}[${index}]`, output));
            return output;
        }

        if (value && typeof value === 'object') {
            Object.entries(value).forEach(([key, item]) => {
                const nextPrefix = prefix ? `${prefix}.${key}` : key;
                flattenObject(item, nextPrefix, output);
            });
            return output;
        }

        if (prefix) {
            output[prefix] = value;
        }

        return output;
    }

    function renderPayload(payload) {
        const data = payload?.data ?? payload;
        const flattened = flattenObject(data);
        const entries = Object.entries(flattened).filter(([, value]) => value !== null && value !== '');
        const rawJson = JSON.stringify(data, null, 2);

        if (!entries.length) {
            return `<div class="text-muted">${escapeHtml(labels.empty)}</div><pre class="mt-3">${escapeHtml(rawJson)}</pre>`;
        }

        const fields = entries.slice(0, 24).map(([key, value]) => `
            <div class="transport-result__item">
                <div class="transport-result__label">${escapeHtml(key)}</div>
                <div class="fw-semibold text-light">${escapeHtml(value)}</div>
            </div>
        `).join('');

        return `
            <div class="transport-result__grid">${fields}</div>
            <details class="mt-3">
                <summary class="text-muted" style="cursor:pointer;">JSON</summary>
                <pre class="mt-2">${escapeHtml(rawJson)}</pre>
            </details>
        `;
    }

    form?.addEventListener('submit', async function (event) {
        event.preventDefault();
        const plate = input.value.trim().toUpperCase().replace(/[^A-ZА-ЯІЇЄҐ0-9]/g, '');
        input.value = plate;

        if (!plate) {
            result.innerHTML = `<div class="text-warning">${escapeHtml(labels.empty)}</div>`;
            return;
        }

        submit.disabled = true;
        result.innerHTML = `<div class="text-muted">${escapeHtml(labels.loading)}</div>`;

        try {
            const response = await fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ plate }),
            });
            const payload = await response.json().catch(() => ({}));

            if (!response.ok || payload.success === false) {
                const message = payload.message || labels.error;
                result.innerHTML = `<div class="text-danger">${escapeHtml(message)}</div><pre class="mt-3">${escapeHtml(JSON.stringify(payload, null, 2))}</pre>`;
                return;
            }

            result.innerHTML = renderPayload(payload);
        } catch (error) {
            result.innerHTML = `<div class="text-danger">${escapeHtml(labels.error)}</div><pre class="mt-3">${escapeHtml(error.message || error)}</pre>`;
        } finally {
            submit.disabled = false;
        }
    });
});
</script>
@endpush
