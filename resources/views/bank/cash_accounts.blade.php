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

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

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
        <button type="button" class="bank-panel bank-panel--button" data-bank-section-trigger="operational">
            <div class="bank-label">Операционные счета</div>
            <div class="bank-value">{{ $emailWalletBindings->count() }}</div>
            <div class="bank-meta">Привязки email к криптокошелькам</div>
        </button>
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

    <section class="bank-panel bank-table-panel" data-bank-section-panel="operational" hidden>
        <div class="bank-table-header">
            <div>
                <div class="bank-label">Операционные счета</div>
                <div class="bank-meta">Таблица привязки email к криптокошельку. Нажмите на строку, чтобы увидеть монеты и количество.</div>
            </div>
            <div class="bank-meta">{{ $emailWalletBindings->count() }} привязок</div>
        </div>

        <div class="table-responsive bank-table-scroll">
            <table class="table table-dark table-hover table-sm align-middle bank-table bank-table--wallet-bindings">
                <thead>
                    <tr>
                        <th class="bank-table__num">№</th>
                        <th>Email</th>
                        <th>Клиент</th>
                        <th class="bank-table__wallet">Криптокошелек</th>
                        <th>Сеть</th>
                        <th>Источник</th>
                        <th class="text-end">Монет</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($emailWalletBindings as $binding)
                        <tr class="bank-accordion-row" data-bank-accordion-trigger="wallet-binding-{{ $loop->iteration }}">
                            <td class="bank-table__num bank-mono">{{ $loop->iteration }}</td>
                            <td>
                                <button type="button" class="bank-row-button">
                                    <span class="bank-row-caret">›</span>
                                    <span>
                                        <strong>{{ $binding->email !== '' ? $binding->email : '—' }}</strong>
                                        <small>user id {{ $binding->user_id }}</small>
                                    </span>
                                </button>
                            </td>
                            <td>{{ $binding->owner_name }}</td>
                            <td class="bank-mono">{{ $binding->address }}</td>
                            <td><span class="bank-pill bank-pill--currency">{{ $binding->network !== '' ? strtoupper($binding->network) : '—' }}</span></td>
                            <td>{{ $binding->source }}</td>
                            <td class="text-end fw-semibold">{{ $binding->token_count }}</td>
                        </tr>
                        <tr class="bank-accordion-detail" data-bank-accordion-detail="wallet-binding-{{ $loop->iteration }}" hidden>
                            <td colspan="7">
                                <div class="bank-detail-block">
                                    <table class="table table-dark table-sm align-middle bank-table bank-table--nested">
                                        <thead>
                                            <tr>
                                                <th>Монета</th>
                                                <th>Название</th>
                                                <th>Сеть</th>
                                                <th class="text-end">Количество</th>
                                                <th class="text-end">USD</th>
                                                <th>Контракт</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($binding->tokens as $token)
                                                <tr>
                                                    <td><span class="bank-pill bank-pill--currency">{{ $token->symbol }}</span></td>
                                                    <td>{{ $token->name !== '' ? $token->name : '—' }}</td>
                                                    <td>{{ $token->chain !== '' ? strtoupper($token->chain) : '—' }}</td>
                                                    <td class="text-end bank-mono">{{ rtrim(rtrim($token->balance, '0'), '.') !== '' ? rtrim(rtrim($token->balance, '0'), '.') : '0' }}</td>
                                                    <td class="text-end">{{ $token->value_usd > 0 ? number_format((float) $token->value_usd, 2, '.', ' ') : '—' }}</td>
                                                    <td class="bank-mono">{{ $token->token_address !== '' ? $token->token_address : 'native' }}</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="6" class="text-center text-muted py-3">По этому кошельку монеты не найдены.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">Привязки email к криптокошелькам не найдены.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
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
                                            <tr class="bank-account-action-row">
                                                <td colspan="6">
                                                    <form method="POST" action="{{ route('bank.project-accounts.store', ['project' => $projectRow->id]) }}" class="bank-inline-account-form">
                                                        @csrf
                                                        <strong>Добавить счёт</strong>
                                                        <input type="text" name="name" class="form-control" placeholder="Название счёта" required>
                                                        <input type="text" name="currency" class="form-control" value="UAH" placeholder="Валюта" maxlength="20" required>
                                                        <button type="submit" class="btn btn-sm btn-primary">+ Добавить счёт</button>
                                                    </form>
                                                </td>
                                            </tr>
                                            @forelse($projectRow->cash_accounts as $account)
                                                <tr>
                                                    <td class="bank-mono">{{ $account->id }}</td>
                                                    <td>
                                                        <button type="button"
                                                            class="bank-account-link"
                                                            data-bank-requisites-open
                                                            data-project-name="{{ $projectRow->name }}"
                                                            data-account-id="{{ $account->id }}"
                                                            data-account-name="{{ $account->label }}"
                                                            data-account-currency="{{ $account->currency }}"
                                                            data-account-balance="{{ number_format((float) $account->balance, 2, '.', ' ') }}"
                                                            data-account-doc="{{ $account->doc }}"
                                                            data-account-address="{{ $account->color }}">
                                                            {{ $account->label }}
                                                        </button>
                                                    </td>
                                                    <td><span class="bank-pill bank-pill--currency">{{ $account->currency }}</span></td>
                                                    <td class="text-end fw-semibold">{{ number_format((float) $account->balance, 2, '.', ' ') }}</td>
                                                    <td>{{ $account->doc !== '' ? $account->doc : '—' }}</td>
                                                    <td>
                                                        <div class="bank-account-cell-actions">
                                                            <span class="bank-mono">{{ $account->color !== '' ? $account->color : '—' }}</span>
                                                            <form method="POST" action="{{ route('bank.project-accounts.destroy', ['project' => $projectRow->id, 'account' => $account->id]) }}" onsubmit="return confirm('Удалить счёт проекта?');">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="btn btn-sm btn-outline-danger">Удалить</button>
                                                            </form>
                                                        </div>
                                                    </td>
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
                                @if($person->google_wallets_count > 0)
                                    <div class="bank-meta">{{ $person->google_wallets_count }} Google-кош.</div>
                                @endif
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
                                            <tr class="bank-account-action-row">
                                                <td colspan="4">
                                                    <form method="POST" action="{{ route('bank.person-accounts.store', ['person' => $person->owner_id]) }}" class="bank-inline-account-form">
                                                        @csrf
                                                        <strong>Добавить счёт</strong>
                                                        <input type="text" name="currency" class="form-control" value="UAH" placeholder="Валюта" maxlength="20" required>
                                                        <button type="submit" class="btn btn-sm btn-primary">+ Добавить счёт</button>
                                                    </form>
                                                </td>
                                            </tr>
                                            @forelse($person->accounts as $account)
                                                <tr>
                                                    <td>
                                                        <div class="bank-mono">{{ $account->account_number }}</div>
                                                        <div class="bank-meta">client id {{ $account->owner_id }}</div>
                                                    </td>
                                                    <td><span class="bank-pill bank-pill--currency">{{ $account->currency }}</span></td>
                                                    <td class="text-end fw-semibold">{{ number_format((float) $account->balance, 2, '.', ' ') }}</td>
                                                    <td>
                                                        <div class="bank-account-cell-actions">
                                                            <span>{{ $account->service_account }}</span>
                                                            <form method="POST" action="{{ route('bank.person-accounts.destroy', ['person' => $person->owner_id, 'account' => $account->account_id]) }}" onsubmit="return confirm('Удалить счёт физлица?');">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="btn btn-sm btn-outline-danger">Удалить</button>
                                                            </form>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="4" class="text-center text-muted py-3">У физлица пока нет открытых счетов в users_cashe.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                    <div class="bank-wallets-block">
                                        <div class="bank-label">Google-кошельки аккаунта</div>
                                        <table class="table table-dark table-sm align-middle bank-table bank-table--nested">
                                            <thead>
                                                <tr>
                                                    <th>Адрес кошелька</th>
                                                    <th>Сеть</th>
                                                    <th>Источник</th>
                                                    <th>Создан / обновлен</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse($person->google_wallets as $wallet)
                                                    <tr>
                                                        <td class="bank-mono">{{ $wallet->address }}</td>
                                                        <td><span class="bank-pill bank-pill--currency">{{ $wallet->network !== '' ? strtoupper($wallet->network) : '—' }}</span></td>
                                                        <td>{{ $wallet->source }}</td>
                                                        <td>{{ $wallet->connected_at ?: '—' }}</td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="4" class="text-center text-muted py-3">Для Google-аккаунта кошельки не найдены.</td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
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

    <div class="bank-modal" data-bank-requisites-modal hidden>
        <div class="bank-modal__backdrop" data-bank-requisites-close></div>
        <div class="bank-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="bankRequisitesTitle">
            <div class="bank-modal__header">
                <div>
                    <div class="bank-label">Банковские реквизиты компании</div>
                    <h2 id="bankRequisitesTitle">Открытие реквизитов счета</h2>
                    <div class="bank-meta" data-bank-requisites-context></div>
                </div>
                <button type="button" class="bank-modal__close" data-bank-requisites-close aria-label="Закрыть">×</button>
            </div>
            <form class="bank-requisites-form">
                <div class="bank-form-grid">
                    <label>
                        <span>Компания</span>
                        <input type="text" data-bank-requisites-project autocomplete="organization">
                    </label>
                    <label>
                        <span>Счет обслуживания</span>
                        <input type="text" data-bank-requisites-account readonly>
                    </label>
                    <label>
                        <span>IBAN / номер счета</span>
                        <input type="text" placeholder="UA00 000000 000000000000000000000" autocomplete="off">
                    </label>
                    <label>
                        <span>Банк</span>
                        <input type="text" placeholder="Название банка" autocomplete="off">
                    </label>
                    <label>
                        <span>МФО / BIC / SWIFT</span>
                        <input type="text" placeholder="Код банка" autocomplete="off">
                    </label>
                    <label>
                        <span>ЕДРПОУ / ИНН</span>
                        <input type="text" placeholder="Код компании" autocomplete="off">
                    </label>
                    <label>
                        <span>Валюта учета</span>
                        <input type="text" data-bank-requisites-currency readonly>
                    </label>
                    <label>
                        <span>Текущий баланс</span>
                        <input type="text" data-bank-requisites-balance readonly>
                    </label>
                </div>
                <label class="bank-form-full">
                    <span>Назначение / комментарий</span>
                    <textarea rows="3" placeholder="Условия обслуживания, лимиты, назначение счета"></textarea>
                </label>
                <div class="bank-modal__actions">
                    <button type="button" class="btn btn-secondary" data-bank-requisites-close>Отмена</button>
                    <button type="button" class="btn btn-primary" data-bank-requisites-close>Готово</button>
                </div>
            </form>
        </div>
    </div>
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

    const requisitesModal = root.querySelector('[data-bank-requisites-modal]');
    const requisitesContext = root.querySelector('[data-bank-requisites-context]');
    const requisitesProject = root.querySelector('[data-bank-requisites-project]');
    const requisitesAccount = root.querySelector('[data-bank-requisites-account]');
    const requisitesCurrency = root.querySelector('[data-bank-requisites-currency]');
    const requisitesBalance = root.querySelector('[data-bank-requisites-balance]');

    root.querySelectorAll('[data-bank-requisites-open]').forEach((button) => {
        button.addEventListener('click', (event) => {
            event.stopPropagation();
            if (!requisitesModal) return;

            requisitesProject.value = button.dataset.projectName || '';
            requisitesAccount.value = button.dataset.accountName || '';
            requisitesCurrency.value = button.dataset.accountCurrency || '';
            requisitesBalance.value = button.dataset.accountBalance || '';
            requisitesContext.textContent = [
                button.dataset.accountName || '',
                button.dataset.accountCurrency || '',
                button.dataset.accountAddress || ''
            ].filter(Boolean).join(' · ');
            requisitesModal.hidden = false;
            requisitesModal.querySelector('input:not([readonly])')?.focus();
        });
    });

    root.querySelectorAll('[data-bank-requisites-close]').forEach((button) => {
        button.addEventListener('click', () => {
            if (requisitesModal) {
                requisitesModal.hidden = true;
            }
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
