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
