@extends('home')

@section('title', 'Подписки')

@section('content')
<div class="container mt-4 subscriptions-page" data-bs-theme="dark">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h1 class="h3 text-light mb-1">Подписки</h1>
            <p class="text-muted mb-0">Регулярная торговля товарами и услугами с автоматическими начислениями и блокировкой при неоплате.</p>
        </div>
        <a href="{{ route('document.index', ['doc' => 'ZOUT']) }}" class="btn btn-outline-secondary">Документы продаж</a>
    </div>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

    <ul class="nav nav-tabs mb-4" role="tablist">
        <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#plans-pane" type="button">Тарифы</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#subscriptions-pane" type="button">Подписки клиентов</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#invoices-pane" type="button">Начисления</button></li>
    </ul>

    <div class="tab-content">
        <section class="tab-pane fade show active" id="plans-pane">
            <div class="glass-card" id="subscription-plans-list-area">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
                    <div>
                        <h2 class="h5 text-light mb-1">Тарифы</h2>
                        <p class="text-muted mb-0">Выберите тариф для редактирования или создайте новый.</p>
                    </div>
                    <button type="button" class="btn btn-outline-warning" id="btn-subscription-plan-add">+ Добавить</button>
                </div>
                <div class="table-responsive">
                    <table class="table table-dark table-hover table-sm align-middle mb-0 subscription-plans-table">
                        <thead>
                            <tr>
                                <th>№ позиции</th>
                                <th>Название</th>
                                <th>Цена</th>
                                <th>Период</th>
                                <th>Оплата / grace</th>
                                <th>Состав</th>
                                <th>Статус</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($plans as $plan)
                                <tr class="subscription-plan-row" data-plan-target="subscription-plan-form-{{ $plan->id }}" data-plan-title="{{ $plan->name }}" tabindex="0" role="button">
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        <strong>{{ $plan->name }}</strong>
                                        @if($plan->subtitle ?? '')
                                            <div class="text-warning small">{{ $plan->subtitle }}</div>
                                        @endif
                                    </td>
                                    <td>{{ number_format((float) $plan->price, 2, '.', ' ') }} {{ $plan->currency }}</td>
                                    <td>
                                        @php($periodLabels = ['week' => 'Неделя', 'month' => 'Месяц', 'quarter' => 'Квартал', 'year' => 'Год'])
                                        {{ $periodLabels[$plan->billing_period] ?? $plan->billing_period }}
                                        @if((int) $plan->interval_count > 1)
                                            · {{ $plan->interval_count }}
                                        @endif
                                    </td>
                                    <td>{{ $plan->payment_due_days }} / {{ $plan->grace_days }} дней</td>
                                    <td>{{ $plan->items->count() }}</td>
                                    <td><span class="badge {{ $plan->active ? 'bg-success' : 'bg-secondary' }}">{{ $plan->active ? 'Активен' : 'Выключен' }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-center text-muted py-4">Создайте первый тариф подписки.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="glass-card d-none" id="subscription-plan-form-area">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
                    <h2 class="h5 text-light mb-0" id="subscription-plan-form-title">Новый тариф</h2>
                    <button type="button" class="btn btn-secondary" id="btn-subscription-plan-back">Назад</button>
                </div>

                <div class="subscription-plan-form-panel" id="subscription-plan-form-new">
                    @include('subscriptions.partials.plan_form', ['plan' => null])
                </div>

                @foreach($plans as $plan)
                    <div class="subscription-plan-form-panel d-none" id="subscription-plan-form-{{ $plan->id }}">
                        @include('subscriptions.partials.plan_form', ['plan' => $plan])

                        <div class="subscription-items mt-3">
                            <h4>Товары и услуги тарифа</h4>
                            @foreach($plan->items as $item)
                                <div class="subscription-item-row">
                                    <span>{{ $item->product_name }} · {{ $item->item_type === 'service' ? 'Услуга' : 'Товар' }}</span>
                                    <span>{{ number_format((float) $item->quantity, 3, '.', ' ') }} × {{ number_format((float) $item->price, 2, '.', ' ') }}</span>
                                    <form method="POST" action="{{ route('subscriptions.planItems.destroy', ['item' => $item->id]) }}">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger">Удалить</button>
                                    </form>
                                </div>
                            @endforeach

                            <form method="POST" action="{{ route('subscriptions.planItems.store', ['plan' => $plan->id]) }}" class="subscription-inline-form">
                                @csrf
                                <select name="product_id" class="form-select" required>
                                    <option value="">Товар/услуга</option>
                                    @foreach($products as $product)
                                        <option value="{{ $product->id }}">{{ $product->name ?: ($product->nickname ?: 'Товар #' . $product->id) }}</option>
                                    @endforeach
                                </select>
                                <select name="item_type" class="form-select">
                                    <option value="goods">Товар</option>
                                    <option value="service">Услуга</option>
                                </select>
                                <input name="quantity" class="form-control" type="number" step="0.001" value="1" min="0.001">
                                <input name="price" class="form-control" type="number" step="0.01" value="{{ $plan->price }}" min="0">
                                <button class="btn btn-outline-warning">Добавить</button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="tab-pane fade" id="subscriptions-pane">
            <div class="glass-card mb-4">
                <h2 class="h5 text-light mb-3">Новая подписка клиента</h2>
                @include('subscriptions.partials.subscription_form', ['subscription' => null])
            </div>

            <div class="subscription-stack">
                @foreach($subscriptions as $subscription)
                    <article class="glass-card subscription-card">
                        <div class="subscription-card__head">
                            <div>
                                <h3>{{ $subscription->client_name }}</h3>
                                <p>{{ $subscription->plan_name }} · следующее начисление {{ $subscription->next_billing_at ?: 'не задано' }}</p>
                            </div>
                            <span class="badge {{ $subscription->status === 'blocked' ? 'bg-danger' : ($subscription->payment_status === 'overdue' ? 'bg-warning text-dark' : 'bg-success') }}">
                                {{ $subscription->status }} / {{ $subscription->payment_status }}
                            </span>
                        </div>
                        @if($subscription->status === 'blocked')
                            <div class="alert alert-danger">Подписка заблокирована: {{ $subscription->block_reason ?: 'неоплата' }}. Grace до {{ $subscription->grace_until ?: 'не задано' }}.</div>
                        @endif
                        @include('subscriptions.partials.subscription_form', ['subscription' => $subscription])
                        <form method="POST" action="{{ route('subscriptions.bill', ['subscription' => $subscription->id]) }}" class="mt-3">
                            @csrf
                            <button class="btn btn-outline-warning">Создать начисление сейчас</button>
                        </form>
                    </article>
                @endforeach
            </div>
        </section>

        <section class="tab-pane fade" id="invoices-pane">
            <div class="glass-card">
                <div class="table-responsive">
                    <table class="table table-dark table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Клиент</th><th>Тариф</th><th>Период</th><th>Срок оплаты</th><th>Сумма</th><th>Статус</th><th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($invoices as $invoice)
                                <tr>
                                    <td>{{ $invoice->client_name }}</td>
                                    <td>{{ $invoice->plan_name }}</td>
                                    <td>{{ $invoice->period_from }} - {{ $invoice->period_to }}</td>
                                    <td>{{ $invoice->due_at }}</td>
                                    <td>{{ number_format((float) $invoice->amount, 2, '.', ' ') }}</td>
                                    <td><span class="badge {{ $invoice->status === 'paid' ? 'bg-success' : ($invoice->status === 'overdue' ? 'bg-danger' : 'bg-warning text-dark') }}">{{ $invoice->status }}</span></td>
                                    <td class="text-end">
                                        @if($invoice->status !== 'paid')
                                            <form method="POST" action="{{ route('subscriptions.invoices.paid', ['invoice' => $invoice->id]) }}">
                                                @csrf
                                                <button class="btn btn-sm btn-success">Оплачено</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-center text-muted">Начислений пока нет.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const listArea = document.getElementById('subscription-plans-list-area');
    const formArea = document.getElementById('subscription-plan-form-area');
    const formTitle = document.getElementById('subscription-plan-form-title');
    const addButton = document.getElementById('btn-subscription-plan-add');
    const backButton = document.getElementById('btn-subscription-plan-back');
    const panels = Array.from(document.querySelectorAll('.subscription-plan-form-panel'));

    const showList = () => {
        listArea?.classList.remove('d-none');
        formArea?.classList.add('d-none');
        panels.forEach((panel) => panel.classList.add('d-none'));
    };

    const showForm = (targetId, title) => {
        const targetPanel = document.getElementById(targetId);
        if (!targetPanel) {
            return;
        }

        panels.forEach((panel) => panel.classList.add('d-none'));
        targetPanel.classList.remove('d-none');
        listArea?.classList.add('d-none');
        formArea?.classList.remove('d-none');
        if (formTitle) {
            formTitle.textContent = title;
        }

        const firstInput = targetPanel.querySelector('input, select, textarea');
        firstInput?.focus({ preventScroll: true });
    };

    addButton?.addEventListener('click', () => showForm('subscription-plan-form-new', 'Новый тариф'));
    backButton?.addEventListener('click', showList);

    document.querySelectorAll('.subscription-plan-row').forEach((row) => {
        const openRow = () => showForm(row.dataset.planTarget, row.dataset.planTitle || 'Редактирование тарифа');
        row.addEventListener('click', openRow);
        row.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                openRow();
            }
        });
    });
});
</script>

<style>
    .subscriptions-page .glass-card { border: 1px solid rgba(255,255,255,.12); border-radius: 12px; background: rgba(12,16,24,.82); padding: 1.25rem; }
    .subscriptions-page .nav-link { color: rgba(255,255,255,.68); }
    .subscriptions-page .nav-link.active { color: #111827; background: #facc15; border-color: #facc15; }
    .subscription-plans-table th, .subscription-plans-table td { white-space: nowrap; }
    .subscription-plans-table th:first-child, .subscription-plans-table td:first-child { width: 6.5rem; text-align: center; }
    .subscription-plans-table th:nth-child(2), .subscription-plans-table td:nth-child(2) { white-space: normal; min-width: 22rem; width: 42%; }
    .subscription-plan-row { cursor: pointer; }
    .subscription-plan-row:focus { outline: 2px solid rgba(250,204,21,.8); outline-offset: -2px; }
    .subscription-plan-form-panel.d-none { display: none !important; }
    .subscription-stack { display: grid; gap: 1rem; }
    .subscription-card__head { display: flex; justify-content: space-between; gap: 1rem; margin-bottom: 1rem; }
    .subscription-card__head h3 { color: #fff; font-size: 1.1rem; margin: 0 0 .3rem; }
    .subscription-card__head p { color: rgba(255,255,255,.62); margin: 0; }
    .subscription-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: .85rem; }
    .subscription-grid label, .subscription-items label { display: grid; gap: .35rem; color: rgba(255,255,255,.72); font-weight: 600; }
    .subscription-grid .span-2 { grid-column: span 2; }
    .subscription-grid .span-4 { grid-column: 1 / -1; }
    .subscription-items h4 { color: #fff; font-size: 1rem; margin: 0 0 .75rem; }
    .subscription-item-row, .subscription-inline-form { display: grid; grid-template-columns: minmax(0, 1.4fr) .8fr auto; gap: .7rem; align-items: center; margin-top: .55rem; }
    .subscription-inline-form { grid-template-columns: minmax(0, 1.4fr) .7fr .45fr .55fr auto; }
    @media (max-width: 900px) {
        .subscription-grid, .subscription-grid .span-2, .subscription-item-row, .subscription-inline-form { grid-template-columns: 1fr; }
        .subscription-card__head { display: grid; }
    }
</style>
@endsection
