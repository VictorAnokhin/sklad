@extends('home')

@section('title', 'Оплатить')

@section('content')
<div class="container mt-4 pay-page" data-bs-theme="dark">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h1 class="h3 text-light mb-1">Оплатить</h1>
            <p class="text-muted mb-0">Сформируйте начисление по подключенному тарифу подписки.</p>
        </div>
        <a href="{{ route('price') }}" class="btn btn-outline-secondary">Тарифы</a>
    </div>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

    <section class="glass-card pay-form-card mb-4">
        <h2 class="h5 text-light mb-3">Форма оплаты</h2>
        @if($subscriptions->isEmpty())
            <div class="alert alert-warning mb-0">
                У вас нет подключенных тарифов подписки. Откройте страницу тарифов и подключите нужный план.
            </div>
        @else
            <form method="POST" action="{{ route('pay.store') }}" class="pay-form">
                @csrf
                <label>
                    <span>Тариф подписки</span>
                    <select name="subscription_id" class="form-select" required>
                        @foreach($subscriptions as $subscription)
                            <option value="{{ $subscription->id }}" {{ (string) old('subscription_id') === (string) $subscription->id ? 'selected' : '' }}>
                                {{ $subscription->plan_name }}
                                · {{ number_format((float) $subscription->plan_price, 2, '.', ' ') }} {{ $subscription->plan_currency }}
                                · {{ $subscription->status }}
                            </option>
                        @endforeach
                    </select>
                </label>

                <label>
                    <span>Способ оплаты</span>
                    <select name="payment_method" class="form-select" required>
                        @foreach($paymentMethods as $value => $label)
                            <option value="{{ $value }}" {{ old('payment_method', 'av8') === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>

                <button class="btn btn-warning">Сформировать</button>
            </form>

            <div class="pay-requisites mt-4">
                <h3>Реквизиты из settings / Мои компании</h3>
                @forelse($companies as $company)
                    <div class="pay-requisites__item">
                        <strong>{{ $company->name ?: 'Компания #' . $company->id }}</strong>
                        <span>Счет: {{ $company->schet ?: 'не указан' }}</span>
                        <span>Банк: {{ $company->bank ?: 'не указан' }}</span>
                        <span>МФО: {{ $company->mfo ?: 'не указан' }}</span>
                        <span>ЄДРПОУ/Рег. №: {{ $company->regnum ?: 'не указан' }}</span>
                    </div>
                @empty
                    <div class="text-muted">Реквизиты не заполнены. Добавьте компанию в settings / Мои компании.</div>
                @endforelse
            </div>
        @endif
    </section>

    <section class="glass-card">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
            <h2 class="h5 text-light mb-0">Начисления</h2>
            <span class="text-muted small">Только по вашему пользователю</span>
        </div>

        <div class="table-responsive">
            <table class="table table-dark table-hover table-sm align-middle pay-invoices-table">
                <thead>
                    <tr>
                        <th>№</th>
                        <th>Тариф</th>
                        <th>Период</th>
                        <th>Оплата</th>
                        <th>Сумма</th>
                        <th>Способ</th>
                        <th>Статус</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($invoices as $invoice)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $invoice->plan_name }}</td>
                            <td>{{ $invoice->period_from }} - {{ $invoice->period_to }}</td>
                            <td>{{ $invoice->due_at }}</td>
                            <td class="text-end">{{ number_format((float) $invoice->amount, 2, '.', ' ') }}</td>
                            <td>{{ $paymentMethods[$invoice->payment_method] ?? ($invoice->payment_method ?: '—') }}</td>
                            <td>
                                <span class="badge {{ $invoice->status === 'paid' ? 'bg-success' : ($invoice->status === 'overdue' ? 'bg-danger' : 'bg-warning text-dark') }}">
                                    {{ $invoice->status }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-4">Начислений пока нет.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>

<style>
    .pay-page .glass-card {
        border: 1px solid rgba(255,255,255,.12);
        border-radius: 12px;
        background: rgba(12,16,24,.82);
        padding: 1.25rem;
    }

    .pay-form {
        display: grid;
        grid-template-columns: minmax(0, 1.4fr) minmax(0, .9fr) auto;
        gap: 1rem;
        align-items: end;
    }

    .pay-form label {
        display: grid;
        gap: .35rem;
        color: rgba(255,255,255,.72);
        font-weight: 700;
    }

    .pay-requisites {
        border-top: 1px solid rgba(255,255,255,.1);
        padding-top: 1rem;
    }

    .pay-requisites h3 {
        color: #fff;
        font-size: 1rem;
        margin: 0 0 .75rem;
    }

    .pay-requisites__item {
        display: grid;
        grid-template-columns: repeat(5, minmax(0, 1fr));
        gap: .7rem;
        padding: .85rem;
        border: 1px solid rgba(255,255,255,.1);
        border-radius: 10px;
        background: rgba(255,255,255,.03);
        color: rgba(255,255,255,.72);
    }

    .pay-requisites__item + .pay-requisites__item {
        margin-top: .6rem;
    }

    .pay-requisites__item strong {
        color: #fff;
    }

    .pay-invoices-table th,
    .pay-invoices-table td {
        white-space: nowrap;
    }

    .pay-invoices-table th:nth-child(2),
    .pay-invoices-table td:nth-child(2) {
        min-width: 16rem;
        white-space: normal;
    }

    @media (max-width: 900px) {
        .pay-form,
        .pay-requisites__item {
            grid-template-columns: 1fr;
        }
    }
</style>
@endsection
