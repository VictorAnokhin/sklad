@extends('home')

@section('title')
Клиентские счета
@endsection

@section('content')
@php
    $personStats = $ownerTypeTotals['person'] ?? ['count' => 0, 'balance' => 0];
    $primaryCurrency = $totalByCurrency->keys()->first() ?? 'UAH';
    $primaryTotal = (float) ($totalByCurrency[$primaryCurrency] ?? 0);
@endphp
<div class="bank-page" data-bank-accounts-page>
    @include('bank.partials.nav')

    <section class="bank-hero">
        <div>
            <div class="bank-label">Финансовая организация</div>
            <h1>Счета клиентов {{ $project->name ?? ('#' . $project->id) }}</h1>
            <p>Реестр счетов, открытых для проектов и физических лиц. В проектах раскрываются кассы, у физлиц раскрываются клиентские счета.</p>
        </div>
        <div class="bank-hero__metrics">
            <div>
                <span>Проектов</span>
                <strong>{{ $projectAccounts->count() }}</strong>
            </div>
            <div>
                <span>Физлиц</span>
                <strong>{{ $personOwners->count() }}</strong>
            </div>
        </div>
    </section>

    <section class="bank-grid bank-grid--summary">
        <button type="button" class="bank-panel bank-panel--button bank-panel--accent is-active" data-bank-section-trigger="projects">
            <span class="bank-label">Проекты</span>
            <span class="bank-value">{{ $projectAccounts->count() }}</span>
            <span class="bank-meta">Проекты с кассами и счетами обслуживания</span>
        </button>
        <button type="button" class="bank-panel bank-panel--button" data-bank-section-trigger="persons">
            <span class="bank-label">Физические лица</span>
            <span class="bank-value">{{ $personOwners->count() }}</span>
            <span class="bank-meta">Персональные клиенты и открытые счета</span>
        </button>
        <div class="bank-panel">
            <div class="bank-label">Операционные счета</div>
            <div class="bank-value">{{ $cashAccounts->count() }}</div>
            <div class="bank-meta">Кассы банка для обслуживания операций</div>
        </div>
        <div class="bank-panel">
            <div class="bank-label">Основной остаток</div>
            <div class="bank-value">{{ number_format($primaryTotal, 2, '.', ' ') }}</div>
            <div class="bank-meta">{{ $primaryCurrency }} на клиентских счетах физлиц</div>
        </div>
    </section>

    <section class="bank-panel bank-currency-strip">
        <div class="bank-table-header">
            <div>
                <div class="bank-label">Остатки физических лиц</div>
                <div class="bank-meta">Сводка по валютам клиентских счетов</div>
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

    <section class="bank-panel bank-table-panel" data-bank-section-panel="projects">
        <div class="bank-table-header">
            <div>
                <div class="bank-label">Проекты</div>
                <div class="bank-meta">Нажмите на проект, чтобы раскрыть кассы, баланс, валюту учета и реквизиты.</div>
            </div>
            <div class="bank-meta">{{ $projectAccounts->count() }} проектов</div>
        </div>

        <div class="table-responsive bank-table-scroll">
            <table class="table table-dark table-hover table-sm align-middle bank-table bank-table--projects">
                <thead>
                    <tr>
                        <th class="bank-table__num">№</th>
                        <th class="bank-table__wide">Проект</th>
                        <th>Тип</th>
                        <th>Контакт</th>
                        <th class="text-end">Касс</th>
                        <th>Остатки</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($projectAccounts as $projectRow)
                        <tr class="bank-accordion-row" data-bank-accordion-trigger="project-{{ $projectRow->id }}">
                            <td class="bank-table__num bank-mono">{{ $loop->iteration }}</td>
                            <td>
                                <button type="button" class="bank-row-button">
                                    <span class="bank-row-caret">›</span>
                                    <span>
                                        <strong>{{ $projectRow->name }}</strong>
                                        <small>ID {{ $projectRow->id }}{{ $projectRow->holding_name !== '' ? ' · ' . $projectRow->holding_name : '' }}</small>
                                    </span>
                                </button>
                            </td>
                            <td><span class="bank-pill bank-pill--company">{{ $projectRow->type }}</span></td>
                            <td>
                                <div>{{ $projectRow->email !== '' ? $projectRow->email : '—' }}</div>
                                <div class="bank-meta">{{ $projectRow->phone !== '' ? $projectRow->phone : '' }}</div>
                            </td>
                            <td class="text-end fw-semibold">{{ $projectRow->cash_count }}</td>
                            <td>
                                @forelse($projectRow->total_by_currency as $currency => $total)
                                    <span class="bank-inline-balance">{{ number_format((float) $total, 2, '.', ' ') }} {{ $currency }}</span>
                                @empty
                                    <span class="bank-meta">Нет касс</span>
                                @endforelse
                            </td>
                        </tr>
                        <tr class="bank-accordion-detail" data-bank-accordion-detail="project-{{ $projectRow->id }}" hidden>
                            <td colspan="6">
                                <div class="bank-detail-block">
                                    <table class="table table-dark table-sm align-middle bank-table bank-table--nested">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Касса / счет</th>
                                                <th>Валюта учета</th>
                                                <th class="text-end">Баланс</th>
                                                <th>Документы</th>
                                                <th>Реквизит / адрес</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($projectRow->cash_accounts as $account)
                                                <tr>
                                                    <td class="bank-mono">{{ $account->id }}</td>
                                                    <td>{{ $account->label }}</td>
                                                    <td><span class="bank-pill bank-pill--currency">{{ $account->currency }}</span></td>
                                                    <td class="text-end fw-semibold">{{ number_format((float) $account->balance, 2, '.', ' ') }}</td>
                                                    <td>{{ $account->doc !== '' ? $account->doc : '—' }}</td>
                                                    <td class="bank-mono">{{ $account->color !== '' ? $account->color : '—' }}</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="6" class="text-center text-muted py-3">Кассы проекта не настроены.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">Проекты не найдены.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="bank-panel bank-table-panel" data-bank-section-panel="persons" hidden>
        <div class="bank-table-header bank-table-header--search">
            <div>
                <div class="bank-label">Физические лица</div>
                <div class="bank-meta">Нажмите на клиента, чтобы раскрыть открытые счета и остатки.</div>
            </div>
            <label class="bank-search">
                <span>Поиск</span>
                <input type="search" placeholder="Фамилия, имя, телефон, email" data-bank-person-search>
            </label>
        </div>

        <div class="table-responsive bank-table-scroll">
            <table class="table table-dark table-hover table-sm align-middle bank-table bank-table--persons">
                <thead>
                    <tr>
                        <th class="bank-table__num">№</th>
                        <th class="bank-table__wide">Физлицо</th>
                        <th>Телефон / Email</th>
                        <th>Город</th>
                        <th class="text-end">Счетов</th>
                        <th>Остатки</th>
                        <th>Статус</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($personOwners as $person)
                        <tr class="bank-accordion-row" data-bank-person-row data-bank-person-search-text="{{ $person->search_text }}" data-bank-accordion-trigger="person-{{ $person->owner_id }}">
                            <td class="bank-table__num bank-mono">{{ $loop->iteration }}</td>
                            <td>
                                <button type="button" class="bank-row-button">
                                    <span class="bank-row-caret">›</span>
                                    <span>
                                        <strong>{{ $person->owner_name }}</strong>
                                        <small>client id {{ $person->owner_id }}</small>
                                    </span>
                                </button>
                            </td>
                            <td>{{ $person->contact }}</td>
                            <td>{{ $person->city !== '' ? $person->city : '—' }}</td>
                            <td class="text-end fw-semibold">{{ $person->accounts_count }}</td>
                            <td>
                                @foreach($person->total_by_currency as $currency => $total)
                                    <span class="bank-inline-balance">{{ number_format((float) $total, 2, '.', ' ') }} {{ $currency }}</span>
                                @endforeach
                            </td>
                            <td><span class="bank-status">{{ $person->status }}</span></td>
                        </tr>
                        <tr class="bank-accordion-detail" data-bank-person-detail data-bank-accordion-detail="person-{{ $person->owner_id }}" hidden>
                            <td colspan="7">
                                <div class="bank-detail-block">
                                    <table class="table table-dark table-sm align-middle bank-table bank-table--nested">
                                        <thead>
                                            <tr>
                                                <th class="bank-table__account">Счет</th>
                                                <th>Валюта</th>
                                                <th class="text-end">Остаток</th>
                                                <th>Счет обслуживания</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($person->accounts as $account)
                                                <tr>
                                                    <td>
                                                        <div class="bank-mono">{{ $account->account_number }}</div>
                                                        <div class="bank-meta">client id {{ $account->owner_id }}</div>
                                                    </td>
                                                    <td><span class="bank-pill bank-pill--currency">{{ $account->currency }}</span></td>
                                                    <td class="text-end fw-semibold">{{ number_format((float) $account->balance, 2, '.', ' ') }}</td>
                                                    <td>{{ $account->service_account }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">Физические лица со счетами не найдены.</td>
                        </tr>
                    @endforelse
                    <tr data-bank-person-empty hidden>
                        <td colspan="7" class="text-center text-muted py-4">Поиск не дал результатов.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>
</div>

@include('bank.partials.styles')

<script>
document.addEventListener('DOMContentLoaded', () => {
    const root = document.querySelector('[data-bank-accounts-page]');
    if (!root) return;

    const sectionTriggers = root.querySelectorAll('[data-bank-section-trigger]');
    const sectionPanels = root.querySelectorAll('[data-bank-section-panel]');

    sectionTriggers.forEach((trigger) => {
        trigger.addEventListener('click', () => {
            const target = trigger.dataset.bankSectionTrigger;
            sectionTriggers.forEach((item) => item.classList.toggle('is-active', item === trigger));
            sectionPanels.forEach((panel) => {
                panel.hidden = panel.dataset.bankSectionPanel !== target;
            });
        });
    });

    root.querySelectorAll('[data-bank-accordion-trigger]').forEach((row) => {
        row.addEventListener('click', () => {
            const key = row.dataset.bankAccordionTrigger;
            const detail = root.querySelector(`[data-bank-accordion-detail="${key}"]`);
            if (!detail) return;

            const isOpen = !detail.hidden;
            detail.hidden = isOpen;
            row.classList.toggle('is-open', !isOpen);
        });
    });

    const search = root.querySelector('[data-bank-person-search]');
    if (search) {
        search.addEventListener('input', () => {
            const value = search.value.trim().toLowerCase();
            let visibleCount = 0;

            root.querySelectorAll('[data-bank-person-row]').forEach((row) => {
                const matched = value === '' || (row.dataset.bankPersonSearchText || '').includes(value);
                const detail = root.querySelector(`[data-bank-accordion-detail="${row.dataset.bankAccordionTrigger}"]`);
                row.hidden = !matched;
                if (detail) {
                    detail.hidden = true;
                }
                row.classList.remove('is-open');
                if (matched) visibleCount += 1;
            });

            const empty = root.querySelector('[data-bank-person-empty]');
            if (empty) {
                empty.hidden = visibleCount !== 0;
            }
        });
    }
});
</script>
@endsection
