@extends('home')

@section('title')
Dashboard — {{ session('name1') }}
@endsection

@section('content')
<div class="container mt-4">
    <div class="row g-4">
        <div class="col-12">
            <div class="card shadow-sm border-success">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="card-title mb-1">📈 Надходження коштів за день</h4>
                        <div class="text-muted small">{{ $today }}</div>
                    </div>
                    <div class="fs-4 fw-bold text-success">{{ number_format((float)($dailyIncome ?? 0), 2, '.', ' ') }} грн</div>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h4 class="card-title mb-3">💰 Стан кас</h4>

                    @if(($cashboxes ?? collect())->isEmpty())
                    <div class="text-muted">Каси не налаштовані.</div>
                    @else
                    <div class="list-group list-group-flush">
                        @foreach($cashboxes as $cashbox)
                        <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <div>
                                <div class="fw-semibold">{{ $cashbox->name }}</div>
                                <div class="text-muted small">ID: {{ $cashbox->id }}</div>
                            </div>
                            <div class="fw-bold">{{ number_format((float)($cashbox->value ?? 0), 2, '.', ' ') }} грн</div>
                        </div>
                        @endforeach
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="card-title mb-0">🛒 Нові замовлення</h4>
                        <span class="badge bg-danger">{{ count($newOrders ?? []) }}</span>
                    </div>

                    @if(($newOrders ?? collect())->isEmpty())
                    <div class="text-muted">Нових замовлень немає.</div>
                    @else
                    <div class="list-group list-group-flush">
                        @foreach($newOrders as $order)
                        <a href="{{ route('document.show', ['doc' => 'ZOUT', 'doc_id' => $order->id, 'num' => $order->num, 'year' => substr((string)($order->data ?? date('d-m-Y')), -4)]) }}"
                            class="list-group-item list-group-item-action px-0">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="fw-semibold">Замовлення №{{ $order->num }}</div>
                                    <div class="text-muted small">{{ $order->data }} {{ $order->time }}</div>
                                    <div class="small">{{ $order->content }}</div>
                                </div>
                                <div class="fw-bold">{{ number_format((float)($order->summa ?? 0), 2, '.', ' ') }} грн</div>
                            </div>
                        </a>
                        @endforeach
                    </div>

                    <div class="mt-3">
                        <a href="{{ route('document.index', ['doc' => 'ZOUT']) }}" class="btn btn-outline-primary">Всі замовлення</a>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm mt-4">
        <div class="card-body">
            <h5 class="card-title mb-3">Сесія</h5>
            <div class="row row-cols-1 row-cols-md-3 g-2 text-muted small">
                <div>user-id: {{ session('id') }}</div>
                <div>idfirma: {{ session('fid') }}</div>
                <div>doc: {{ session('doc') }}</div>
                <div>balans: {{ session('balans') }}</div>
                <div>name: {{ session('name1') }}</div>
                <div>login: {{ session('login') }}</div>
            </div>
        </div>
    </div>
</div>
@endsection
