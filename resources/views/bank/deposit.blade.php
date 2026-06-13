@extends('home')

@section('title')
Депозиты
@endsection

@section('content')
<div class="bank-page">
    @include('bank.partials.nav')

    <section class="bank-hero">
        <div>
            <div class="bank-label">Bank Deposits</div>
            <h1>Депозиты</h1>
            <p>Депозитные счета проектов, текущие остатки, лимиты и история пополнений и выводов.</p>
        </div>
        <div class="bank-hero__metrics">
            <div>
                <span>Активных депозитов</span>
                <strong>{{ number_format((int) $summary['active'], 0, '.', ' ') }}</strong>
            </div>
            <div>
                <span>Ожидают проводку</span>
                <strong>{{ number_format((int) $summary['pending'], 0, '.', ' ') }}</strong>
            </div>
        </div>
    </section>

    <section class="bank-grid bank-grid--summary">
        <div class="bank-panel bank-panel--accent">
            <div class="bank-label">Депозитов</div>
            <div class="bank-value">{{ $deposits->count() }}</div>
            <div class="bank-meta">Во всех проектах холдинга</div>
        </div>
        <div class="bank-panel">
            <div class="bank-label">Пополнения</div>
            <div class="bank-value">{{ number_format((float) $summary['topups'], 2, '.', ' ') }}</div>
            <div class="bank-meta">Последние {{ $operations->count() }} операций</div>
        </div>
        <div class="bank-panel">
            <div class="bank-label">Выводы</div>
            <div class="bank-value">{{ number_format((float) $summary['withdrawals'], 2, '.', ' ') }}</div>
            <div class="bank-meta">Последние {{ $operations->count() }} операций</div>
        </div>
        <div class="bank-panel">
            <div class="bank-label">Управление</div>
            <div class="bank-value">PP</div>
            <div class="bank-meta"><a href="{{ route('deposit.index') }}" class="bank-account-link">Открыть операции депозитов</a></div>
        </div>
    </section>

    <section class="bank-panel bank-currency-strip">
        <div class="bank-currency-list">
            @forelse($totalByCurrency as $currency => $total)
                <div class="bank-currency-item">
                    <span>{{ $currency }}</span>
                    <strong>
                        {{ number_format((float) $total, 2, '.', ' ') }}
                        <small> / лимит {{ number_format((float) ($limitByCurrency[$currency] ?? 0), 2, '.', ' ') }}</small>
                    </strong>
                </div>
            @empty
                <div class="bank-empty">Депозитные счета пока не созданы.</div>
            @endforelse
        </div>
    </section>

    <section class="bank-panel bank-table-panel">
        <div class="bank-table-header">
            <div>
                <div class="bank-label">Портфель депозитов</div>
                <div class="bank-meta">Текущие остатки и установленные лимиты депозитных счетов.</div>
            </div>
            <div class="bank-meta">{{ $deposits->count() }} счетов</div>
        </div>
        <div class="table-responsive bank-table-scroll bank-table-scroll--compact">
            <table class="table table-dark table-hover table-sm align-middle bank-table bank-table--deposits">
                <thead>
                    <tr>
                        <th class="bank-table__num">№</th>
                        <th>Депозит</th>
                        <th>Проект</th>
                        <th>Валюта</th>
                        <th class="text-end">Остаток</th>
                        <th class="text-end">Лимит</th>
                        <th class="text-end">Использовано</th>
                        <th>Статус</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($deposits as $deposit)
                        @php
                            $usage = $deposit->limit > 0 ? min(100, max(0, $deposit->balance / $deposit->limit * 100)) : null;
                        @endphp
                        <tr>
                            <td class="bank-table__num bank-mono">{{ $loop->iteration }}</td>
                            <td>
                                <strong>{{ $deposit->name }}</strong>
                                <div class="bank-meta">ID {{ $deposit->id }}{{ $deposit->is_visible ? '' : ' · скрыт' }}</div>
                            </td>
                            <td>{{ $deposit->project_name }}</td>
                            <td><span class="bank-pill bank-pill--currency">{{ $deposit->currency }}</span></td>
                            <td class="text-end fw-semibold">{{ number_format((float) $deposit->balance, 2, '.', ' ') }}</td>
                            <td class="text-end">{{ $deposit->limit > 0 ? number_format((float) $deposit->limit, 2, '.', ' ') : '—' }}</td>
                            <td class="text-end">{{ $usage !== null ? number_format($usage, 1, '.', ' ') . '%' : '—' }}</td>
                            <td>
                                <span class="bank-status {{ $deposit->is_active ? '' : 'bank-status--reversed' }}">{{ $deposit->status_label }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">Депозиты пока не созданы.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="bank-panel bank-table-panel">
        <div class="bank-table-header">
            <div>
                <div class="bank-label">Операции по депозитам</div>
                <div class="bank-meta">Последние пополнения и выводы с состоянием бухгалтерской проводки.</div>
            </div>
            <div class="bank-meta">{{ $operations->count() }} операций</div>
        </div>
        <div class="table-responsive bank-table-scroll">
            <table class="table table-dark table-hover table-sm align-middle bank-table bank-table--deposit-operations">
                <thead>
                    <tr>
                        <th>Дата / документ</th>
                        <th>Операция</th>
                        <th>Депозит</th>
                        <th>Проект</th>
                        <th>Владелец</th>
                        <th class="text-end">Сумма</th>
                        <th>Статус</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($operations as $operation)
                        <tr>
                            <td>
                                <strong>{{ $operation->date ?: '—' }}</strong>
                                <div class="bank-meta">PP №{{ $operation->number }} · ID {{ $operation->id }}</div>
                            </td>
                            <td>
                                <span class="bank-pill {{ $operation->mode === 'withdraw' ? 'bank-pill--outgoing' : '' }}">
                                    {{ $operation->mode_label }}
                                </span>
                            </td>
                            <td>{{ $operation->deposit_name }}</td>
                            <td>{{ $operation->project_name }}</td>
                            <td>
                                <div>{{ $operation->owner_name }}</div>
                                @if($operation->description !== '')
                                    <div class="bank-meta">{{ $operation->description }}</div>
                                @endif
                            </td>
                            <td class="text-end fw-semibold {{ $operation->mode === 'withdraw' ? 'text-danger' : 'text-success' }}">
                                {{ $operation->mode === 'withdraw' ? '−' : '+' }}{{ number_format((float) $operation->amount, 2, '.', ' ') }}
                                {{ $operation->currency }}
                            </td>
                            <td>
                                <span class="bank-status bank-status--{{ $operation->status }}">{{ $operation->status_label }}</span>
                                <div class="bank-meta">{{ $operation->ledger_id > 0 ? 'TX #' . $operation->ledger_id : 'Ledger TX отсутствует' }}</div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">Операций по депозитам пока нет.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>

@include('bank.partials.styles')
@endsection
