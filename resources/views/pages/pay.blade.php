@extends('home')

@section('title', 'Оплатить')

@section('content')
<div class="container mt-4 pay-page" data-bs-theme="dark">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h1 class="h3 text-light mb-1">Оплатить</h1>
            <p class="text-muted mb-0">Выберите тариф, подключите его и сформируйте начисление к оплате.</p>
        </div>
        <a href="{{ route('price') }}" class="btn btn-outline-secondary">Тарифы</a>
    </div>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

    <section class="glass-card pay-form-card mb-4">
        <h2 class="h5 text-light mb-3">Форма оплаты</h2>
        @if($projects->isEmpty())
            <div class="alert alert-warning mb-0">
                У вас нет созданных проектов для подключения тарифа.
            </div>
        @elseif($plans->isEmpty())
            <div class="alert alert-warning mb-0">
                Тарифы подписки не настроены.
            </div>
        @else
            <form method="POST" action="{{ route('pay.store') }}" class="pay-form" id="pay-form">
                @csrf
                <div class="pay-connect-row">
                    <label>
                        <span>Проект</span>
                        <select name="project_id" class="form-select" required id="pay-project-select">
                            @foreach($projects as $project)
                                <option
                                    value="{{ $project->id }}"
                                    data-name="{{ ($project->name ?: 'Проект #' . $project->id) . ' · #' . $project->id }}"
                                    {{ (string) old('project_id') === (string) $project->id ? 'selected' : '' }}
                                >
                                    {{ $project->name ?: 'Проект #' . $project->id }} · #{{ $project->id }}
                                </option>
                            @endforeach
                        </select>
                    </label>

                    <label>
                        <span>Тариф подписки</span>
                        <select name="plan_id" class="form-select" required id="pay-plan-select">
                            @foreach($plans as $plan)
                                <option
                                    value="{{ $plan->id }}"
                                    data-name="{{ $plan->name }}"
                                    data-amount="{{ number_format((float) $plan->price, 2, '.', ' ') }} {{ $plan->currency }}"
                                    {{ (string) old('plan_id') === (string) $plan->id ? 'selected' : '' }}
                                >
                                    {{ $plan->name }} · {{ number_format((float) $plan->price, 2, '.', ' ') }} {{ $plan->currency }}
                                </option>
                            @endforeach
                        </select>
                    </label>

                    <button type="button" class="btn btn-outline-warning" id="pay-connect-button">Подключить</button>
                </div>

                <div class="pay-selected-plan d-none" id="pay-selected-project">
                    <span>Подключен проект</span>
                    <strong id="pay-selected-project-name"></strong>
                </div>

                <div class="pay-selected-plan d-none" id="pay-selected-plan">
                    <span>Подключен тариф</span>
                    <strong id="pay-selected-plan-name"></strong>
                </div>

                <div class="pay-amount d-none" id="pay-amount">
                    <span>Сумма к оплате</span>
                    <strong id="pay-amount-value"></strong>
                </div>

                <div class="pay-methods">
                    <h3>Способы оплаты</h3>
                    <label>
                        <span>Способ оплаты</span>
                        <select name="payment_method" class="form-select" required>
                            @foreach($paymentMethods as $value => $label)
                                <option value="{{ $value }}" {{ old('payment_method', 'av8') === $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>

                    <button class="btn btn-warning" id="pay-submit-button" disabled>Сформировать</button>
                </div>
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
                        <th>Проект</th>
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
                            <td>{{ $invoice->project_name ?: 'Проект #' . $invoice->subscription_project_id }}</td>
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
                        <tr><td colspan="8" class="text-center text-muted py-4">Начислений пока нет.</td></tr>
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
        gap: 1rem;
    }

    .pay-connect-row {
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(0, 1fr) auto;
        gap: 1rem;
        align-items: end;
    }

    .pay-methods {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        gap: 1rem;
        align-items: end;
    }

    .pay-form label {
        display: grid;
        gap: .35rem;
        color: rgba(255,255,255,.72);
        font-weight: 700;
    }

    .pay-methods {
        border-top: 1px solid rgba(255,255,255,.1);
        padding-top: 1rem;
    }

    .pay-methods h3 {
        grid-column: 1 / -1;
        color: #fff;
        font-size: 1rem;
        margin: 0;
    }

    .pay-selected-plan,
    .pay-amount {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding: .85rem 1rem;
        border: 1px solid rgba(250,204,21,.3);
        border-radius: 10px;
        background: rgba(250,204,21,.1);
        color: rgba(255,255,255,.72);
    }

    .pay-amount {
        border-color: rgba(56,189,248,.28);
        background: rgba(56,189,248,.08);
    }

    .pay-selected-plan strong,
    .pay-amount strong {
        color: #fff;
        font-size: 1.1rem;
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
        min-width: 12rem;
        white-space: normal;
    }

    .pay-invoices-table th:nth-child(3),
    .pay-invoices-table td:nth-child(3) {
        min-width: 16rem;
        white-space: normal;
    }

    @media (max-width: 900px) {
        .pay-connect-row,
        .pay-methods,
        .pay-requisites__item {
            grid-template-columns: 1fr;
        }
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const projectSelect = document.getElementById('pay-project-select');
    const select = document.getElementById('pay-plan-select');
    const connectButton = document.getElementById('pay-connect-button');
    const submitButton = document.getElementById('pay-submit-button');
    const selectedProject = document.getElementById('pay-selected-project');
    const selectedProjectName = document.getElementById('pay-selected-project-name');
    const selectedPlan = document.getElementById('pay-selected-plan');
    const selectedPlanName = document.getElementById('pay-selected-plan-name');
    const amount = document.getElementById('pay-amount');
    const amountValue = document.getElementById('pay-amount-value');

    const connect = () => {
        const option = select?.selectedOptions?.[0];
        const projectOption = projectSelect?.selectedOptions?.[0];
        if (!option || !projectOption) {
            return;
        }

        if (selectedProjectName) {
            selectedProjectName.textContent = projectOption.dataset.name || projectOption.textContent.trim();
        }
        if (selectedPlanName) {
            selectedPlanName.textContent = option.dataset.name || option.textContent.trim();
        }
        if (amountValue) {
            amountValue.textContent = option.dataset.amount || '';
        }
        selectedProject?.classList.remove('d-none');
        selectedPlan?.classList.remove('d-none');
        amount?.classList.remove('d-none');
        if (submitButton) {
            submitButton.disabled = false;
        }
    };

    connectButton?.addEventListener('click', connect);
    const resetConnection = () => {
        selectedProject?.classList.add('d-none');
        selectedPlan?.classList.add('d-none');
        amount?.classList.add('d-none');
        if (submitButton) {
            submitButton.disabled = true;
        }
    };

    projectSelect?.addEventListener('change', resetConnection);
    select?.addEventListener('change', resetConnection);

    @if(old('plan_id') && old('project_id'))
        connect();
    @endif
});
</script>
@endsection
