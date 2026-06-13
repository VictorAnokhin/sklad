@extends('home')

@section('title')
Клиентские счета
@endsection

@section('content')
@php
    $companyStats = $ownerTypeTotals['company'] ?? ['count' => 0, 'balance' => 0];
    $personStats = $ownerTypeTotals['person'] ?? ['count' => 0, 'balance' => 0];
    $primaryCurrency = $totalByCurrency->keys()->first() ?? 'UAH';
    $primaryTotal = (float) ($totalByCurrency[$primaryCurrency] ?? 0);
@endphp
<div class="bank-page">
    @include('bank.partials.nav')

    <section class="bank-hero">
        <div>
            <div class="bank-label">Финансовая организация</div>
            <h1>Счета клиентов {{ $project->name ?? ('#' . $project->id) }}</h1>
            <p>Реестр счетов, открытых для компаний и физических лиц. Остатки ведутся по клиентским счетам, операционные кассы используются как счета обслуживания.</p>
        </div>
        <div class="bank-hero__metrics">
            <div>
                <span>Клиентских счетов</span>
                <strong>{{ $clientAccounts->count() }}</strong>
            </div>
            <div>
                <span>Основной остаток</span>
                <strong>{{ number_format($primaryTotal, 2, '.', ' ') }} {{ $primaryCurrency }}</strong>
            </div>
        </div>
    </section>

    <section class="bank-grid bank-grid--summary">
        <div class="bank-panel bank-panel--accent">
            <div class="bank-label">Компании</div>
            <div class="bank-value">{{ $companyStats['count'] ?? 0 }}</div>
            <div class="bank-meta">Открыто счетов для юридических лиц</div>
        </div>
        <div class="bank-panel">
            <div class="bank-label">Физические лица</div>
            <div class="bank-value">{{ $personStats['count'] ?? 0 }}</div>
            <div class="bank-meta">Открыто персональных счетов</div>
        </div>
        <div class="bank-panel">
            <div class="bank-label">Операционные счета</div>
            <div class="bank-value">{{ $cashAccounts->count() }}</div>
            <div class="bank-meta">Кассы и счета обслуживания</div>
        </div>
        <div class="bank-panel">
            <div class="bank-label">Валюты</div>
            <div class="bank-value">{{ $totalByCurrency->count() }}</div>
            <div class="bank-meta">Валюты клиентских остатков</div>
        </div>
    </section>

    <section class="bank-panel bank-currency-strip">
        <div class="bank-table-header">
            <div>
                <div class="bank-label">Остатки клиентов</div>
                <div class="bank-meta">Сводка по валютам на счетах компаний и физлиц</div>
            </div>
        </div>
        <div class="bank-currency-list">
            @forelse($totalByCurrency as $currency => $total)
                <div class="bank-currency-item">
                    <span>{{ $currency }}</span>
                    <strong>{{ number_format((float) $total, 2, '.', ' ') }}</strong>
                </div>
            @empty
                <div class="bank-empty">Клиентские счета еще не открыты.</div>
            @endforelse
        </div>
    </section>

    <section class="bank-panel bank-table-panel">
        <div class="bank-table-header">
            <div>
                <div class="bank-label">Реестр счетов</div>
                <div class="bank-meta">Источник остатков: users_cashe. Владелец счета: users.</div>
            </div>
            <div class="bank-meta">{{ $clientAccounts->count() }} счетов</div>
        </div>

        <div class="table-responsive bank-table-scroll">
            <table class="table table-dark table-hover table-sm align-middle bank-table bank-table--client-accounts">
                <thead>
                    <tr>
                        <th class="bank-table__num">№</th>
                        <th class="bank-table__account">Счет</th>
                        <th>Клиент</th>
                        <th>Тип</th>
                        <th>Валюта</th>
                        <th class="text-end">Остаток</th>
                        <th>Статус</th>
                        <th>Счет обслуживания</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($clientAccounts as $account)
                        <tr>
                            <td class="bank-table__num bank-mono">{{ $loop->iteration }}</td>
                            <td>
                                <div class="bank-mono">{{ $account->account_number }}</div>
                                <div class="bank-meta">client id {{ $account->owner_id }}</div>
                            </td>
                            <td>
                                <div class="fw-semibold">{{ $account->owner_name }}</div>
                                <div class="bank-meta">
                                    {{ $account->contact }}
                                    @if($account->tax_code !== '')
                                        · код {{ $account->tax_code }}
                                    @endif
                                </div>
                            </td>
                            <td>
                                <span class="bank-pill {{ $account->owner_type === 'company' ? 'bank-pill--company' : 'bank-pill--person' }}">
                                    {{ $account->owner_type_label }}
                                </span>
                            </td>
                            <td><span class="bank-pill bank-pill--currency">{{ $account->currency }}</span></td>
                            <td class="text-end fw-semibold">{{ number_format((float) $account->balance, 2, '.', ' ') }}</td>
                            <td><span class="bank-status">{{ $account->status }}</span></td>
                            <td>{{ $account->service_account }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">Клиентские счета не найдены.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="bank-panel bank-table-panel">
        <div class="bank-table-header">
            <div>
                <div class="bank-label">Операционные счета организации</div>
                <div class="bank-meta">Корреспондентские кассы и кошельки для обслуживания клиентских счетов</div>
            </div>
            <div class="bank-meta">{{ $cashAccounts->count() }} записей</div>
        </div>

        <div class="table-responsive">
            <table class="table table-dark table-hover table-sm align-middle bank-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Название</th>
                        <th>Валюта</th>
                        <th class="text-end">Остаток</th>
                        <th>Документы</th>
                        <th>Реквизит</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($cashAccounts as $account)
                        <tr>
                            <td class="bank-mono">{{ $account->id }}</td>
                            <td>
                                <div class="fw-semibold">{{ $account->label }}</div>
                                <div class="bank-meta">firma {{ $account->firma }}</div>
                            </td>
                            <td><span class="bank-pill bank-pill--currency">{{ $account->currency }}</span></td>
                            <td class="text-end fw-semibold">{{ number_format((float) $account->balance, 2, '.', ' ') }}</td>
                            <td>{{ $account->doc !== '' ? $account->doc : '—' }}</td>
                            <td class="bank-mono">{{ $account->color !== '' ? $account->color : '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">Операционные счета не настроены.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>

@include('bank.partials.styles')
@endsection
