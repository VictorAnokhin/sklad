@extends('home')

@section('title')
Клиринг проектов
@endsection

@section('content')
@php
    $statusLabel = [
        'online' => 'Поток активен',
        'waiting' => 'Ожидание событий',
        'not_configured' => 'Источник не настроен',
    ][$serviceStatus['listener_status'] ?? 'waiting'] ?? 'Ожидание событий';
@endphp
<div class="bank-page">
    @include('bank.partials.nav')

    <section class="bank-grid bank-grid--summary">
        <div class="bank-panel">
            <div class="bank-label">Проекты холдинга</div>
            <div class="bank-value">{{ $holdingProjects->count() }}</div>
            <div class="bank-meta">Участники матрицы взаиморасчетов</div>
        </div>
        <div class="bank-panel">
            <div class="bank-label">Правила клиринга</div>
            <div class="bank-value">{{ $accountMatrix->count() }}</div>
            <div class="bank-meta">Шаблоны двойной записи</div>
        </div>
        <div class="bank-panel">
            <div class="bank-label">Виртуальные долги</div>
            <div class="bank-value">{{ $debtRows->count() }}</div>
            <div class="bank-meta">Открытые пары дебитор-кредитор</div>
        </div>
        <div class="bank-panel">
            <div class="bank-label">Ledger</div>
            <div class="bank-value">{{ $serviceStatus['ledger_ready'] ? 'OK' : '—' }}</div>
            <div class="bank-meta">transactions / entries / accounts</div>
        </div>
    </section>

    <section class="bank-panel bank-service-flow">
        <div class="bank-table-header">
            <div>
                <div class="bank-label">Схема микросервиса</div>
                <div class="bank-meta">Изолированный Laravel-сервис может быть вынесен в очередь или отдельный контейнер.</div>
            </div>
            <div class="bank-meta">Последний tx: {{ $serviceStatus['latest_tx'] !== '' ? $serviceStatus['latest_tx'] : '—' }}</div>
        </div>
        <div class="bank-flow-grid">
            <div class="bank-flow-step">
                <strong>1. Blockchain Listener</strong>
                <span>Фиксирует успешные события покупки/вывода AV8 и USDC.</span>
            </div>
            <div class="bank-flow-step">
                <strong>2. Матрица счетов</strong>
                <span>Определяет проект обмена, проект финансов и счет взаиморасчетов.</span>
            </div>
            <div class="bank-flow-step">
                <strong>3. Двойная запись</strong>
                <span>Генерирует Дт Проект_Обмен - Кт Проект_Финансы.</span>
            </div>
            <div class="bank-flow-step">
                <strong>4. Виртуальный долг</strong>
                <span>Показывает сальдо между проектами холдинга в единой СУБД.</span>
            </div>
        </div>
    </section>

    <section class="bank-panel bank-table-panel">
        <div class="bank-table-header">
            <div>
                <div class="bank-label">Матрица соответствия счетов холдинга</div>
                <div class="bank-meta">Правила, по которым сервис строит intercompany-проводку.</div>
            </div>
            <div class="bank-meta">{{ $accountMatrix->count() }} правил</div>
        </div>

        <div class="table-responsive bank-table-scroll bank-table-scroll--compact">
            <table class="table table-dark table-hover table-sm align-middle bank-table bank-table--clearing">
                <thead>
                    <tr>
                        <th class="bank-table__num">№</th>
                        <th>Операция</th>
                        <th>Дебет</th>
                        <th>Кредит</th>
                        <th>Валюта</th>
                        <th>Источник</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($accountMatrix as $rule)
                        <tr>
                            <td class="bank-table__num bank-mono">{{ $loop->iteration }}</td>
                            <td>
                                <strong>{{ $rule->operation }}</strong>
                                <div class="bank-meta">{{ $rule->rule }}</div>
                            </td>
                            <td>
                                <div>{{ $rule->debit_project->name }}</div>
                                <div class="bank-mono bank-meta">{{ $rule->debit_account }}</div>
                            </td>
                            <td>
                                <div>{{ $rule->credit_project->name }}</div>
                                <div class="bank-mono bank-meta">{{ $rule->credit_account }}</div>
                            </td>
                            <td><span class="bank-pill bank-pill--currency">{{ $rule->currency }}</span></td>
                            <td>{{ $rule->source }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">Матрица счетов не настроена.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="bank-panel bank-table-panel">
        <div class="bank-table-header">
            <div>
                <div class="bank-label">Виртуальные долги между проектами</div>
                <div class="bank-meta">Сальдо рассчитано по успешным blockchain-событиям, подготовленным к двойной записи.</div>
            </div>
            <div class="bank-meta">{{ $debtRows->count() }} пар</div>
        </div>

        <div class="table-responsive bank-table-scroll bank-table-scroll--compact">
            <table class="table table-dark table-hover table-sm align-middle bank-table bank-table--clearing">
                <thead>
                    <tr>
                        <th class="bank-table__num">№</th>
                        <th>Дебитор</th>
                        <th>Кредитор</th>
                        <th class="text-end">Сумма</th>
                        <th>Валюта</th>
                        <th class="text-end">Событий</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($debtRows as $debt)
                        <tr>
                            <td class="bank-table__num bank-mono">{{ $loop->iteration }}</td>
                            <td>{{ $debt->debtor->name }}</td>
                            <td>{{ $debt->creditor->name }}</td>
                            <td class="text-end fw-semibold">{{ number_format((float) $debt->amount, 2, '.', ' ') }}</td>
                            <td><span class="bank-pill bank-pill--currency">{{ $debt->currency }}</span></td>
                            <td class="text-end">{{ $debt->events_count }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">Открытых межпроектных долгов пока нет.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="bank-panel bank-table-panel">
        <div class="bank-table-header">
            <div>
                <div class="bank-label">Журнал подготовленных intercompany-проводок</div>
                <div class="bank-meta">Последние успешные события Blockchain Listener и зеркальная двойная запись.</div>
            </div>
            <div class="bank-meta">{{ $settlementRows->count() }} строк</div>
        </div>

        <div class="table-responsive bank-table-scroll">
            <table class="table table-dark table-hover table-sm align-middle bank-table bank-table--clearing-journal">
                <thead>
                    <tr>
                        <th class="bank-table__num">№</th>
                        <th>Событие</th>
                        <th>Дт</th>
                        <th>Кт</th>
                        <th class="text-end">Сумма</th>
                        <th>TX</th>
                        <th>Статус</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($settlementRows as $row)
                        <tr>
                            <td class="bank-table__num bank-mono">{{ $loop->iteration }}</td>
                            <td>
                                <strong>{{ $row->event_label }}</strong>
                                <div class="bank-meta">{{ $row->event_at !== '' ? $row->event_at : '—' }} · {{ strtoupper($row->network) }}</div>
                            </td>
                            <td>
                                <div>{{ $row->debit_project->name }}</div>
                                <div class="bank-mono bank-meta">{{ $row->debit_account }}</div>
                            </td>
                            <td>
                                <div>{{ $row->credit_project->name }}</div>
                                <div class="bank-mono bank-meta">{{ $row->credit_account }}</div>
                            </td>
                            <td class="text-end fw-semibold">{{ number_format((float) $row->amount, 2, '.', ' ') }} {{ $row->currency }}</td>
                            <td class="bank-mono">{{ $row->tx_digest !== '' ? $row->tx_digest : '—' }}</td>
                            <td><span class="bank-status">{{ $row->status }}</span></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">Blockchain Listener еще не передал успешные события для клиринга.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>

@include('bank.partials.styles')
@endsection
