@extends('home')

@section('title', 'Фінансовий звіт')
@section('header_actions')
    @include('partials.report_panel')
@endsection

@section('content')
<div class="container mt-4 reports-page" data-bs-theme="dark">
    @include('reports.period_form', [
        'periodFormAction' => route('reports.finance'),
        'periodResetUrl' => route('reports.finance'),
        'periodResetLabel' => 'Поточний місяць',
        'periodHiddenFields' => ['oplata' => $oplataId],
    ])

    <div class="card shadow-sm mb-4 bg-transparent border-secondary">
        <div class="card-body">
            <form method="get" action="{{ route('reports.finance') }}" class="row g-3 align-items-end">
                <input type="hidden" name="date_from" value="{{ $dateFrom }}">
                <input type="hidden" name="date_to" value="{{ $dateTo }}">

                <div class="col-md-8">
                    <label for="oplata" class="form-label">Каса</label>
                    <select id="oplata" name="oplata" class="form-select">
                        <option value="">— Усі каси —</option>
                        @foreach(($oplatas ?? collect()) as $oplata)
                        <option value="{{ $oplata->id }}" {{ (string) $oplataId === (string) $oplata->id ? 'selected' : '' }}>
                            {{ $oplata->name }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Фільтрувати</button>
                    <a href="{{ route('reports.finance', ['date_from' => $dateFrom, 'date_to' => $dateTo]) }}" class="btn btn-outline-secondary">Скинути касу</a>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-md-3">
            <div class="card shadow-sm h-100 border-success">
                <div class="card-body">
                    <div class="text-muted small mb-1">Операційний вхідний потік @include('reports.hint', ['text' => 'Сума проведених приходных платежів PO за вибраний період і, якщо обрано, конкретну касу.'])</div>
                    <div class="fs-4 fw-bold text-success">{{ number_format((float) $totalIncome, 2, '.', ' ') }} грн</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm h-100 border-danger">
                <div class="card-body">
                    <div class="text-muted small mb-1">Операційний вихідний потік @include('reports.hint', ['text' => 'Сума проведених расходных платежів RO за вибраний період і, якщо обрано, конкретну касу.'])</div>
                    <div class="fs-4 fw-bold text-danger">{{ number_format((float) $totalExpense, 2, '.', ' ') }} грн</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm h-100 border-primary">
                <div class="card-body">
                    <div class="text-muted small mb-1">Чистий операційний потік @include('reports.hint', ['text' => 'Операційний вхідний потік мінус операційний вихідний потік.'])</div>
                    <div class="fs-4 fw-bold {{ $operatingCashFlow >= 0 ? 'text-primary' : 'text-danger' }}">{{ number_format((float) $operatingCashFlow, 2, '.', ' ') }} грн</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm h-100 border-warning">
                <div class="card-body">
                    <div class="text-muted small mb-1">Проведено платежів @include('reports.hint', ['text' => 'Кількість проведених PO/RO документів, що потрапили у фільтр звіту.'])</div>
                    <div class="fs-4 fw-bold text-warning">{{ $postedCount }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm mt-4 border-dark-subtle">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <h4 class="card-title mb-0 text-light">Фінансова позиція</h4>
                <div class="text-muted small">Формат наближений до казначейського / treasury звіту фінансових організацій</div>
            </div>

            <div class="row g-3">
                <div class="col-md-3">
                    <div class="rounded border p-3 h-100">
                        <div class="text-muted small mb-1">Ліквідні кошти @include('reports.hint', ['text' => 'Поточний залишок у касах, доступний для оперативних платежів.'])</div>
                        <div class="fs-5 fw-bold text-primary">{{ number_format((float) $cashBalanceTotal, 2, '.', ' ') }} грн</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="rounded border p-3 h-100">
                        <div class="text-muted small mb-1">Депозитний портфель @include('reports.hint', ['text' => 'Поточна сума коштів, розміщених у депозитних інструментах або депозитних касах.'])</div>
                        <div class="fs-5 fw-bold text-warning">{{ number_format((float) $depositPortfolioTotal, 2, '.', ' ') }} грн</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="rounded border p-3 h-100">
                        <div class="text-muted small mb-1">Сукупна казна @include('reports.hint', ['text' => 'Ліквідні кошти плюс депозитний портфель. Показує загальний обсяг доступних фінансових ресурсів.'])</div>
                        <div class="fs-5 fw-bold text-light">{{ number_format((float) $treasuryTotal, 2, '.', ' ') }} грн</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="rounded border p-3 h-100">
                        <div class="text-muted small mb-1">Ліміт депозитів @include('reports.hint', ['text' => 'Цільовий або граничний обсяг депозитів, заданий у налаштуваннях депозитних інструментів.'])</div>
                        <div class="fs-5 fw-bold text-secondary">{{ number_format((float) $depositLimitTotal, 2, '.', ' ') }} грн</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm mt-4 bg-transparent border-secondary">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <h4 class="card-title mb-0 text-light">Рух коштів у депозитах</h4>
                <div class="text-muted small">Враховані тільки проведені документи `PP`</div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="rounded border p-3 h-100">
                        <div class="text-muted small mb-1">Загальне поповнення @include('reports.hint', ['text' => 'Сума проведених поповнень депозитів за період.'])</div>
                        <div class="fs-5 fw-bold text-success">{{ number_format((float) $depositTopups, 2, '.', ' ') }} грн</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="rounded border p-3 h-100">
                        <div class="text-muted small mb-1">Загальне зняття @include('reports.hint', ['text' => 'Сума проведених зняттів з депозитів за період.'])</div>
                        <div class="fs-5 fw-bold text-danger">{{ number_format((float) $depositWithdrawals, 2, '.', ' ') }} грн</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="rounded border p-3 h-100">
                        <div class="text-muted small mb-1">Чиста зміна депозитів @include('reports.hint', ['text' => 'Поповнення депозитів мінус зняття. Показує приріст або зменшення депозитного портфеля.'])</div>
                        <div class="fs-5 fw-bold {{ $depositNetFlow >= 0 ? 'text-primary' : 'text-danger' }}">{{ number_format((float) $depositNetFlow, 2, '.', ' ') }} грн</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="rounded border p-3 h-100">
                        <div class="text-muted small mb-1">Внутрішні обміни між касами @include('reports.hint', ['text' => 'Перекази між власними касами або рахунками. Не є зовнішнім доходом чи витратою.'])</div>
                        <div class="fs-5 fw-bold text-secondary">{{ number_format((float) $depositExchanges, 2, '.', ' ') }} грн</div>
                    </div>
                </div>
            </div>

            @if(($depositMovementItems ?? collect())->isEmpty())
            <div class="text-muted">За вибраний період рухів по депозитах не знайдено.</div>
            @else
            <div class="table-responsive">
                <table class="table table-sm table-dark table-hover align-middle mb-0 bg-transparent">
                    <thead class="table-dark">
                        <tr>
                            <th>Депозит @include('reports.hint', ['text' => 'Назва депозитного інструменту або депозитної каси.'])</th>
                            <th class="text-end">Поповнення @include('reports.hint', ['text' => 'Сума поповнень цього депозиту за період.'])</th>
                            <th class="text-end">Зняття @include('reports.hint', ['text' => 'Сума зняттів з цього депозиту за період.'])</th>
                            <th class="text-end">Чиста зміна @include('reports.hint', ['text' => 'Поповнення мінус зняття по цьому депозиту.'])</th>
                            <th class="text-end">Операцій @include('reports.hint', ['text' => 'Кількість проведених операцій по цьому депозиту.'])</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($depositMovementItems as $item)
                        <tr>
                            <td>{{ $item->deposit_name }}</td>
                            <td class="text-end text-success fw-semibold">{{ number_format((float) $item->topup_sum, 2, '.', ' ') }}</td>
                            <td class="text-end text-danger fw-semibold">{{ number_format((float) $item->withdraw_sum, 2, '.', ' ') }}</td>
                            <td class="text-end fw-semibold {{ (float) $item->net_flow >= 0 ? 'text-primary' : 'text-danger' }}">{{ number_format((float) $item->net_flow, 2, '.', ' ') }}</td>
                            <td class="text-end">{{ $item->docs_count }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>
    </div>

    <div class="card shadow-sm mt-4 bg-transparent border-secondary">
        <div class="card-body">
            <h4 class="card-title mb-3 text-light">Структура депозитного портфеля</h4>

            @if(($depositPortfolio ?? collect())->isEmpty())
            <div class="text-muted">Депозити не налаштовані.</div>
            @else
            <div class="table-responsive">
                <table class="table table-sm table-dark table-hover align-middle mb-0 bg-transparent">
                    <thead class="table-dark">
                        <tr>
                            <th>Депозит @include('reports.hint', ['text' => 'Назва депозиту з довідника.'])</th>
                            <th class="text-end">Поточний баланс @include('reports.hint', ['text' => 'Поточне значення залишку депозиту.'])</th>
                            <th class="text-end">Ліміт / ціль @include('reports.hint', ['text' => 'Планова ціль або ліміт для цього депозиту.'])</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($depositPortfolio as $deposit)
                        <tr>
                            <td>{{ $deposit->name }}</td>
                            <td class="text-end fw-semibold text-warning">{{ number_format((float) ($deposit->value ?? 0), 2, '.', ' ') }}</td>
                            <td class="text-end text-secondary">{{ number_format((float) ($deposit->value1 ?? 0), 2, '.', ' ') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>
    </div>

    <div class="card shadow-sm mt-4 bg-transparent border-secondary">
        <div class="card-body">
            <h4 class="card-title mb-3 text-light">Види платежів</h4>

            @if(($paymentTypes ?? collect())->isEmpty())
            <div class="text-muted">За вибраний період види платежів не знайдено.</div>
            @else
            <div class="table-responsive">
                <table class="table table-sm table-dark table-hover align-middle mb-0 bg-transparent">
                    <thead class="table-dark">
                        <tr>
                            <th>Вид платежу @include('reports.hint', ['text' => 'Категорія платежу або реєстр, до якого віднесені PO/RO документи.'])</th>
                            <th class="text-end">Прихід @include('reports.hint', ['text' => 'Сума приходных платежів PO за цим видом.'])</th>
                            <th class="text-end">Витрата @include('reports.hint', ['text' => 'Сума расходных платежів RO за цим видом.'])</th>
                            <th class="text-end">Результат @include('reports.hint', ['text' => 'Прихід мінус витрата по цьому виду платежу.'])</th>
                            <th class="text-end">Документів @include('reports.hint', ['text' => 'Кількість проведених платіжних документів цього виду.'])</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($paymentTypes as $paymentType)
                        <tr>
                            <td>{{ $paymentType->reestr_name }}</td>
                            <td class="text-end text-success fw-semibold">{{ number_format((float) $paymentType->income_sum, 2, '.', ' ') }}</td>
                            <td class="text-end text-danger fw-semibold">{{ number_format((float) $paymentType->expense_sum, 2, '.', ' ') }}</td>
                            <td class="text-end fw-semibold {{ (float) $paymentType->profit_sum >= 0 ? 'text-primary' : 'text-danger' }}">
                                {{ number_format((float) $paymentType->profit_sum, 2, '.', ' ') }}
                            </td>
                            <td class="text-end">{{ $paymentType->docs_count }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>
    </div>

    <div class="card shadow-sm mt-4 bg-transparent border-secondary">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <h4 class="card-title mb-0 text-light">Проведені платежі за {{ $monthLabel }}</h4>
                <div class="text-muted small">У таблиці показані тільки проведені документи PO та RO</div>
            </div>

            @if(($payments ?? collect())->isEmpty())
            <div class="text-muted">За вибраний період проведених платежів не знайдено.</div>
            @else
            <div class="table-responsive">
                <table class="table table-sm table-dark table-hover align-middle mb-0 bg-transparent">
                    <thead class="table-dark">
                        <tr>
                            <th>Дата @include('reports.hint', ['text' => 'Дата платіжного документа.'])</th>
                            <th>№ @include('reports.hint', ['text' => 'Номер документа в системі.'])</th>
                            <th>Тип @include('reports.hint', ['text' => 'PO означає прихід грошей, RO означає витрату грошей.'])</th>
                            <th>Вид платежу @include('reports.hint', ['text' => 'Категорія або реєстр платежу.'])</th>
                            <th>Каса @include('reports.hint', ['text' => 'Каса або рахунок, через який проведено платіж.'])</th>
                            <th>Клієнт @include('reports.hint', ['text' => 'Контрагент, пов’язаний з платежем.'])</th>
                            <th>Коментар @include('reports.hint', ['text' => 'Опис або призначення платежу з документа.'])</th>
                            <th class="text-end">Сума @include('reports.hint', ['text' => 'Сума платежу: зелена для приходу, червона для витрати.'])</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($payments as $payment)
                        @php
                            $clientName = trim(implode(' ', array_filter([
                                $payment->orgname ?? '',
                                trim(implode(' ', array_filter([
                                    $payment->secondname ?? '',
                                    $payment->name ?? '',
                                    $payment->name2 ?? '',
                                ]))),
                            ])));
                        @endphp
                        <tr>
                            <td>{{ $payment->data }}</td>
                            <td>{{ $payment->num }}</td>
                            <td>
                                @if($payment->type === 'PO')
                                <span class="badge bg-success">PO</span>
                                @else
                                <span class="badge bg-danger">RO</span>
                                @endif
                            </td>
                            <td>{{ $payment->reestr_name }}</td>
                            <td>{{ $payment->oplata_name }}</td>
                            <td>{{ $clientName !== '' ? $clientName : '—' }}</td>
                            <td>{{ $payment->content ?: '—' }}</td>
                            <td class="text-end fw-semibold {{ $payment->type === 'PO' ? 'text-success' : 'text-danger' }}">
                                {{ number_format((float) $payment->summa, 2, '.', ' ') }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if(method_exists($payments, 'lastPage') && $payments->lastPage() > 1)
            <nav class="mt-3">
                <ul class="pagination justify-content-center mb-0">
                    <li class="page-item {{ $payments->onFirstPage() ? 'disabled' : '' }}">
                        <a class="page-link" href="{{ $payments->onFirstPage() ? '#' : $payments->url(1) }}" aria-label="Перша сторінка">«</a>
                    </li>
                    <li class="page-item {{ $payments->onFirstPage() ? 'disabled' : '' }}">
                        <a class="page-link" href="{{ $payments->onFirstPage() ? '#' : $payments->previousPageUrl() }}" aria-label="Попередня сторінка">‹</a>
                    </li>
                    <li class="page-item {{ $payments->hasMorePages() ? '' : 'disabled' }}">
                        <a class="page-link" href="{{ $payments->hasMorePages() ? $payments->nextPageUrl() : '#' }}" aria-label="Наступна сторінка">›</a>
                    </li>
                    <li class="page-item {{ $payments->hasMorePages() ? '' : 'disabled' }}">
                        <a class="page-link" href="{{ $payments->hasMorePages() ? $payments->url($payments->lastPage()) : '#' }}" aria-label="Остання сторінка">»</a>
                    </li>
                </ul>
            </nav>
            @endif
            @endif
        </div>
    </div>

    <div class="card shadow-sm mt-4 bg-transparent border-secondary">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <h4 class="card-title mb-0 text-light">Проведені операції по депозитах</h4>
                <div class="text-muted small">Поповнення, зняття і внутрішні обміни за {{ $monthLabel }}</div>
            </div>

            @if(($depositTransactions ?? collect())->isEmpty())
            <div class="text-muted">За вибраний період депозитних операцій не знайдено.</div>
            @else
            <div class="table-responsive">
                <table class="table table-sm table-dark table-hover align-middle mb-0 bg-transparent">
                    <thead class="table-dark">
                        <tr>
                            <th>Дата</th>
                            <th>№</th>
                            <th>Операція</th>
                            <th>Звідки</th>
                            <th>Куди</th>
                            <th>Коментар</th>
                            <th class="text-end">Сума</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($depositTransactions as $transaction)
                        @php
                            $modeLabel = match ($transaction->docum ?? 'topup') {
                                'withdraw' => 'Зняття з депозиту',
                                'exchange' => 'Обмін між касами',
                                default => 'Поповнення депозиту',
                            };
                            $fromLabel = match ($transaction->docum ?? 'topup') {
                                'withdraw' => $transaction->deposit_name,
                                'exchange' => $transaction->cash_from_name,
                                default => $transaction->cash_from_name,
                            };
                            $toLabel = match ($transaction->docum ?? 'topup') {
                                'withdraw' => $transaction->cash_to_name,
                                'exchange' => $transaction->cash_to_name,
                                default => $transaction->deposit_name,
                            };
                        @endphp
                        <tr>
                            <td>{{ $transaction->data }}</td>
                            <td>{{ $transaction->num }}</td>
                            <td>{{ $modeLabel }}</td>
                            <td>{{ $fromLabel ?: '—' }}</td>
                            <td>{{ $toLabel ?: '—' }}</td>
                            <td>{{ $transaction->content ?: '—' }}</td>
                            <td class="text-end fw-semibold">{{ number_format((float) $transaction->summa, 2, '.', ' ') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
