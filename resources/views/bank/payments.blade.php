@extends('home')

@section('title')
Платежи
@endsection

@section('content')
<div class="bank-page">
    @include('bank.partials.nav')

    <section class="bank-hero">
        <div>
            <div class="bank-label">Bank Payment Operations</div>
            <h1>Платежи</h1>
            <p>Исходящие и входящие платежи через кассы-кошельки банка, статусы обработки и связанные бухгалтерские проводки.</p>
        </div>
        <div class="bank-hero__metrics">
            <div>
                <span>Проведено</span>
                <strong>{{ number_format((int) $summary['posted'], 0, '.', ' ') }}</strong>
            </div>
            <div>
                <span>Требуют внимания</span>
                <strong>{{ number_format((int) $summary['attention'], 0, '.', ' ') }}</strong>
            </div>
        </div>
    </section>

    <section class="bank-grid bank-grid--summary">
        <div class="bank-panel bank-panel--accent">
            <div class="bank-label">Входящие</div>
            <div class="bank-value">{{ number_format((float) $summary['incoming'], 2, '.', ' ') }}</div>
            <div class="bank-meta">По текущему фильтру</div>
        </div>
        <div class="bank-panel">
            <div class="bank-label">Исходящие</div>
            <div class="bank-value">{{ number_format((float) $summary['outgoing'], 2, '.', ' ') }}</div>
            <div class="bank-meta">По текущему фильтру</div>
        </div>
        <div class="bank-panel">
            <div class="bank-label">Операций</div>
            <div class="bank-value">{{ $paymentRows->count() }}</div>
            <div class="bank-meta">Последние платежные документы</div>
        </div>
        <div class="bank-panel">
            <div class="bank-label">Ledger</div>
            <div class="bank-value">{{ $ledgerRows->count() }}</div>
            <div class="bank-meta">Последние двойные проводки</div>
        </div>
    </section>

    <section class="bank-panel bank-currency-strip">
        <div class="bank-currency-list">
            @forelse($currencyTotals as $currency => $totals)
                <div class="bank-currency-item">
                    <span>{{ $currency }}</span>
                    <strong>
                        +{{ number_format((float) $totals->incoming, 2, '.', ' ') }}
                        / −{{ number_format((float) $totals->outgoing, 2, '.', ' ') }}
                    </strong>
                </div>
            @empty
                <div class="bank-empty">Нет платежей для валютной сводки.</div>
            @endforelse
        </div>
    </section>

    <section class="bank-panel bank-payment-filters">
        <form method="GET" action="{{ route('bank.payments') }}">
            <div>
                <label for="paymentDirection">Направление</label>
                <select id="paymentDirection" name="direction" class="form-select">
                    <option value="">Все</option>
                    <option value="incoming" {{ $filters['direction'] === 'incoming' ? 'selected' : '' }}>Входящие</option>
                    <option value="outgoing" {{ $filters['direction'] === 'outgoing' ? 'selected' : '' }}>Исходящие</option>
                </select>
            </div>
            <div>
                <label for="paymentStatus">Статус</label>
                <select id="paymentStatus" name="status" class="form-select">
                    <option value="">Все</option>
                    <option value="posted" {{ $filters['status'] === 'posted' ? 'selected' : '' }}>Проведен</option>
                    <option value="pending" {{ $filters['status'] === 'pending' ? 'selected' : '' }}>Ожидает проводку</option>
                    <option value="reversed" {{ $filters['status'] === 'reversed' ? 'selected' : '' }}>Проводка отменена</option>
                    <option value="ledger_error" {{ $filters['status'] === 'ledger_error' ? 'selected' : '' }}>Нет ledger-проводки</option>
                </select>
            </div>
            <div>
                <label for="paymentProject">Проект</label>
                <select id="paymentProject" name="project" class="form-select">
                    <option value="">Все проекты холдинга</option>
                    @foreach($holdingProjects as $holdingProject)
                        <option value="{{ $holdingProject->id }}" {{ $filters['project'] === (string) $holdingProject->id ? 'selected' : '' }}>
                            {{ $holdingProject->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="paymentDatePreset">Период</label>
                <select id="paymentDatePreset" name="date_preset" class="form-select" data-date-preset>
                    <option value="today" {{ $filters['date_preset'] === 'today' ? 'selected' : '' }}>Сегодня</option>
                    <option value="yesterday" {{ $filters['date_preset'] === 'yesterday' ? 'selected' : '' }}>Вчера</option>
                    <option value="week" {{ $filters['date_preset'] === 'week' ? 'selected' : '' }}>За неделю</option>
                    <option value="current_month" {{ $filters['date_preset'] === 'current_month' ? 'selected' : '' }}>Текущий месяц</option>
                    <option value="previous_month" {{ $filters['date_preset'] === 'previous_month' ? 'selected' : '' }}>Прошлый месяц</option>
                    <option value="year" {{ $filters['date_preset'] === 'year' ? 'selected' : '' }}>За год</option>
                    <option value="previous_year" {{ $filters['date_preset'] === 'previous_year' ? 'selected' : '' }}>За прошлый год</option>
                    <option value="manual" {{ $filters['date_preset'] === 'manual' ? 'selected' : '' }}>Ручной диапазон дат</option>
                </select>
            </div>
            <div data-manual-date-filter>
                <label for="paymentDateFrom">Дата с</label>
                <input
                    type="date"
                    id="paymentDateFrom"
                    name="date_from"
                    class="form-control"
                    value="{{ $filters['date_from'] }}"
                >
            </div>
            <div data-manual-date-filter>
                <label for="paymentDateTo">Дата по</label>
                <input
                    type="date"
                    id="paymentDateTo"
                    name="date_to"
                    class="form-control"
                    value="{{ $filters['date_to'] }}"
                >
            </div>
            <div class="bank-payment-filters__actions">
                <button type="submit" class="btn btn-primary">Применить</button>
                <a href="{{ route('bank.payments') }}" class="btn btn-outline-light">Сбросить</a>
            </div>
        </form>
    </section>

    <section class="bank-panel bank-table-panel">
        <div class="bank-table-header">
            <div>
                <div class="bank-label">Операции</div>
                <div class="bank-meta">Платежные документы PO/RO и денежные ордера PPO/PRO.</div>
            </div>
            <div class="bank-meta">{{ $paymentRows->count() }} строк</div>
        </div>
        <div class="table-responsive bank-table-scroll bank-table-scroll--payments">
            <table class="table table-dark table-hover table-sm align-middle bank-table bank-table--payments">
                <thead>
                    <tr>
                        <th class="bank-table__num">№</th>
                        <th>Дата / документ</th>
                        <th>Направление</th>
                        <th>Проект / касса</th>
                        <th>Контрагент</th>
                        <th>Вид платежа</th>
                        <th class="text-end">Сумма</th>
                        <th>Статус</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($paymentRows as $payment)
                        <tr>
                            <td class="bank-table__num bank-mono">{{ $loop->iteration }}</td>
                            <td>
                                <strong>{{ $payment->date ?: '—' }}</strong>
                                <div class="bank-meta">{{ $payment->type }} №{{ $payment->number }} · ID {{ $payment->id }}</div>
                            </td>
                            <td>
                                <span class="bank-pill {{ $payment->direction === 'incoming' ? '' : 'bank-pill--outgoing' }}">
                                    {{ $payment->direction_label }}
                                </span>
                            </td>
                            <td>
                                <div>{{ $payment->project_name }}</div>
                                <div class="bank-meta">{{ $payment->cashbox_name }}</div>
                            </td>
                            <td>
                                <div>{{ $payment->counterparty }}</div>
                                @if($payment->description !== '')
                                    <div class="bank-meta">{{ $payment->description }}</div>
                                @endif
                            </td>
                            <td>{{ $payment->payment_type_name }}</td>
                            <td class="text-end fw-semibold {{ $payment->direction === 'incoming' ? 'text-success' : 'text-danger' }}">
                                {{ $payment->direction === 'incoming' ? '+' : '−' }}{{ number_format((float) $payment->amount, 2, '.', ' ') }}
                                {{ $payment->currency }}
                            </td>
                            <td>
                                <span class="bank-status bank-status--{{ $payment->status }}">{{ $payment->status_label }}</span>
                                <div class="bank-meta">
                                    {{ $payment->ledger_id > 0 ? 'TX #' . $payment->ledger_id . ' · ' . $payment->entries_count . ' записей' : 'Ledger TX отсутствует' }}
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">Платежи по выбранным условиям не найдены.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="bank-panel bank-table-panel">
        <div class="bank-table-header">
            <div>
                <div class="bank-label">Журнал проводок</div>
                <div class="bank-meta">Связанные транзакции ledger с расшифровкой счетов дебета и кредита.</div>
            </div>
            <div class="bank-meta">{{ $ledgerRows->count() }} транзакций</div>
        </div>
        <div class="table-responsive bank-table-scroll bank-table-scroll--payments">
            <table class="table table-dark table-hover table-sm align-middle bank-table bank-table--payment-ledger">
                <thead>
                    <tr>
                        <th class="bank-table__num">№</th>
                        <th>TX / дата</th>
                        <th>Проект</th>
                        <th>Дебет</th>
                        <th>Кредит</th>
                        <th class="text-end">Сумма</th>
                        <th>Источник</th>
                        <th>Статус</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($ledgerRows as $ledger)
                        <tr>
                            <td class="bank-table__num bank-mono">{{ $loop->iteration }}</td>
                            <td>
                                <strong class="bank-mono">#{{ $ledger->id }}</strong>
                                <div class="bank-meta">{{ $ledger->date }}</div>
                            </td>
                            <td>{{ $ledger->project_name }}</td>
                            <td>{{ $ledger->debit_accounts ?: '—' }}</td>
                            <td>{{ $ledger->credit_accounts ?: '—' }}</td>
                            <td class="text-end fw-semibold">{{ number_format((float) $ledger->debit_total, 2, '.', ' ') }} {{ $ledger->currency }}</td>
                            <td>
                                <div>{{ $ledger->description ?: '—' }}</div>
                                <div class="bank-meta">{{ $ledger->reference_type }} #{{ $ledger->reference_id }}</div>
                            </td>
                            <td><span class="bank-status bank-status--{{ $ledger->status }}">{{ $ledger->status_label }}</span></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">Ledger-проводки платежей пока отсутствуют.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>

@include('bank.partials.styles')
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const preset = document.querySelector('[data-date-preset]');
        const manualFields = Array.from(document.querySelectorAll('[data-manual-date-filter]'));

        function syncManualDateFields() {
            const isManual = preset?.value === 'manual';
            manualFields.forEach((field) => {
                field.hidden = !isManual;
                field.querySelectorAll('input').forEach((input) => {
                    input.disabled = !isManual;
                });
            });
        }

        preset?.addEventListener('change', syncManualDateFields);
        syncManualDateFields();
    });
</script>
@endpush
