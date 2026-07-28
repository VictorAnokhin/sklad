@extends('home')

@section('title')
Счета
@endsection

@section('content')
<div class="bank-page" data-bank-accounts-page>
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <div class="bank-tabs" role="tablist" aria-label="Счета">
        <button type="button" class="bank-tab is-active" data-bank-section-trigger="projects" role="tab" aria-selected="true">Проекты</button>
        <button type="button" class="bank-tab" data-bank-section-trigger="persons" role="tab" aria-selected="false">Клиенты</button>
    </div>

    <section class="bank-panel bank-table-panel" data-bank-section-panel="projects">
        <div class="bank-table-header">
            <div>
                <div class="bank-label">Проекты</div>
                <div class="bank-meta">Нажмите на проект, чтобы открыть счета, баланс, валюту учета и реквизиты.</div>
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
                        <tr class="bank-accordion-row" data-bank-project-modal-open="project-{{ $projectRow->id }}">
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
                <div class="bank-meta">Нажмите на клиента, чтобы открыть счета и остатки.</div>
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
                        <tr class="bank-accordion-row" data-bank-person-row data-bank-person-search-text="{{ $person->search_text }}" data-bank-person-modal-open="person-{{ $person->owner_id }}">
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

    @foreach($projectAccounts as $projectRow)
        <div class="bank-modal" data-bank-project-modal="project-{{ $projectRow->id }}" hidden>
            <div class="bank-modal__backdrop" data-bank-project-modal-close></div>
            <div class="bank-modal__dialog bank-modal__dialog--accounts" role="dialog" aria-modal="true" aria-labelledby="bankProjectAccountsTitle{{ $projectRow->id }}">
                <div class="bank-modal__header">
                    <div>
                        <div class="bank-label">Счета проекта</div>
                        <h2 id="bankProjectAccountsTitle{{ $projectRow->id }}">{{ $projectRow->name }}</h2>
                        <div class="bank-meta">ID {{ $projectRow->id }}{{ $projectRow->holding_name !== '' ? ' · ' . $projectRow->holding_name : '' }}</div>
                    </div>
                    <button type="button" class="bank-modal__close" data-bank-project-modal-close aria-label="Закрыть">×</button>
                </div>
                <div class="bank-modal__body">
                    <table class="table table-dark table-sm align-middle bank-table bank-table--project-accounts-modal">
                        <thead>
                            <tr>
                                <th class="bank-table__num">№</th>
                                <th>ID</th>
                                <th>Касса / счет</th>
                                <th>Валюта учета</th>
                                <th class="text-end">Баланс</th>
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
                                        <select name="currency" class="form-select" required>
                                            @foreach(['UAH', 'USD', 'EUR', 'USDC', 'USDT', 'AV8', 'SUI'] as $currency)
                                                <option value="{{ $currency }}">{{ $currency }}</option>
                                            @endforeach
                                        </select>
                                        <label class="form-check d-inline-flex align-items-center gap-2 mb-0">
                                            <input class="form-check-input mt-0" type="checkbox" name="exchange_enabled" value="1">
                                            <span>Обмен</span>
                                        </label>
                                        <button type="submit" class="btn btn-sm btn-primary">+ Добавить счёт</button>
                                    </form>
                                </td>
                            </tr>
                            @forelse($projectRow->cash_accounts as $account)
                                <tr>
                                    <td class="bank-table__num bank-mono">{{ $loop->iteration }}</td>
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
                                            data-account-balance-raw="{{ (float) $account->balance }}"
                                            data-account-doc="{{ $account->doc }}"
                                            data-account-address="{{ $account->color }}"
                                            data-account-bank-name="{{ $account->bank_name }}"
                                            data-account-bank-code="{{ $account->bank_code }}"
                                            data-account-company-name="{{ $account->company_name }}"
                                            data-account-company-code="{{ $account->company_code }}"
                                            data-account-payment-purpose="{{ $account->payment_purpose }}"
                                            data-account-exchange-enabled="{{ $account->exchange_enabled ? '1' : '0' }}"
                                            data-account-update-url="{{ route('bank.project-accounts.update', ['project' => $projectRow->id, 'account' => $account->id]) }}">
                                            {{ $account->label }}
                                            @if($account->exchange_enabled)
                                                <span class="bank-pill bank-pill--currency ms-2">Обмен</span>
                                            @endif
                                        </button>
                                    </td>
                                    <td><span class="bank-pill bank-pill--currency">{{ $account->currency }}</span></td>
                                    <td class="text-end fw-semibold">{{ number_format((float) $account->balance, 2, '.', ' ') }}</td>
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
            </div>
        </div>
    @endforeach

    @foreach($personOwners as $person)
        @php
            $zkLoginWallets = $person->google_wallets
                ->filter(fn ($wallet) => $wallet->source === 'Google zkLogin')
                ->values();
        @endphp
        <div class="bank-modal" data-bank-person-modal="person-{{ $person->owner_id }}" hidden>
            <div class="bank-modal__backdrop" data-bank-person-modal-close></div>
            <div class="bank-modal__dialog bank-modal__dialog--accounts" role="dialog" aria-modal="true" aria-labelledby="bankPersonAccountsTitle{{ $person->owner_id }}">
                <div class="bank-modal__header">
                    <div>
                        <div class="bank-label">Счета клиента</div>
                        <h2 id="bankPersonAccountsTitle{{ $person->owner_id }}">{{ $person->owner_name }}</h2>
                        <div class="bank-meta">client id {{ $person->owner_id }}{{ $person->contact !== '' ? ' · ' . $person->contact : '' }}</div>
                    </div>
                    <button type="button" class="bank-modal__close" data-bank-person-modal-close aria-label="Закрыть">×</button>
                </div>
                <div class="bank-modal__body">
                    <table class="table table-dark table-sm align-middle bank-table bank-table--person-accounts-modal">
                        <thead>
                            <tr>
                                <th class="bank-table__num">№</th>
                                <th>Счет</th>
                                <th>Валюта</th>
                                <th class="text-end">Остаток</th>
                                <th>zk-login кошелек</th>
                                <th>Счет обслуживания</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="bank-account-action-row">
                                <td colspan="6">
                                    <form method="POST" action="{{ route('bank.person-accounts.store', ['person' => $person->owner_id]) }}" class="bank-inline-account-form">
                                        @csrf
                                        <strong>Добавить счёт</strong>
                                        <select name="currency" class="form-select" required>
                                            @foreach(['UAH', 'USD', 'EUR', 'USDC', 'USDT', 'AV8', 'SUI'] as $currency)
                                                <option value="{{ $currency }}">{{ $currency }}</option>
                                            @endforeach
                                        </select>
                                        <button type="submit" class="btn btn-sm btn-primary">+ Добавить счёт</button>
                                    </form>
                                </td>
                            </tr>
                            @forelse($person->accounts as $account)
                                <tr>
                                    <td class="bank-table__num bank-mono">{{ $loop->iteration }}</td>
                                    <td>
                                        <div class="bank-mono">{{ $account->account_number }}</div>
                                        <div class="bank-meta">client id {{ $account->owner_id }}</div>
                                    </td>
                                    <td><span class="bank-pill bank-pill--currency">{{ $account->currency }}</span></td>
                                    <td class="text-end fw-semibold">{{ number_format((float) $account->balance, 2, '.', ' ') }}</td>
                                    <td>
                                        @forelse($zkLoginWallets as $wallet)
                                            <div class="bank-mono">{{ $wallet->address }}</div>
                                            <div class="bank-meta">{{ $wallet->network !== '' ? strtoupper($wallet->network) : 'SUI' }}</div>
                                        @empty
                                            <span class="bank-meta">—</span>
                                        @endforelse
                                    </td>
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
                                    <td colspan="6" class="text-center text-muted py-3">У физлица пока нет открытых счетов в users_cashe.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endforeach

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
            <form class="bank-requisites-form" method="POST" data-bank-requisites-form>
                @csrf
                @method('PUT')
                <div class="bank-form-grid">
                    <label>
                        <span>Компания</span>
                        <input type="text" data-bank-requisites-project autocomplete="organization" readonly>
                    </label>
                    <label>
                        <span>Название счета</span>
                        <input type="text" name="name" data-bank-requisites-account required>
                    </label>
                    <label>
                        <span>IBAN / номер счета</span>
                        <input type="text" name="address" data-bank-requisites-address placeholder="UA00 000000 000000000000000000000" autocomplete="off">
                    </label>
                    <label>
                        <span>Банк</span>
                        <input type="text" name="bank_name" data-bank-requisites-bank-name placeholder="Название банка" autocomplete="off">
                    </label>
                    <label>
                        <span>МФО / BIC / SWIFT</span>
                        <input type="text" name="bank_code" data-bank-requisites-bank-code placeholder="Код банка" autocomplete="off">
                    </label>
                    <label>
                        <span>Название компании</span>
                        <input type="text" name="company_name" data-bank-requisites-company-name placeholder="ООО Компания" autocomplete="organization">
                    </label>
                    <label>
                        <span>ЕДРПОУ / ИНН</span>
                        <input type="text" name="company_code" data-bank-requisites-company-code placeholder="Код компании" autocomplete="off">
                    </label>
                    <label>
                        <span>Валюта учета</span>
                        <select name="currency" data-bank-requisites-currency required>
                            @foreach(['UAH', 'USD', 'EUR', 'USDC', 'USDT', 'AV8', 'SUI'] as $currency)
                                <option value="{{ $currency }}">{{ $currency }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label>
                        <span>Текущий баланс</span>
                        <input type="number" step="0.01" name="amount" data-bank-requisites-balance>
                    </label>
                </div>
                <label class="bank-form-full d-flex align-items-center gap-2">
                    <input type="checkbox" name="exchange_enabled" value="1" data-bank-requisites-exchange>
                    <span>Обмен</span>
                </label>
                <label class="bank-form-full">
                    <span>Назначение / комментарий</span>
                    <textarea rows="3" name="payment_purpose" data-bank-requisites-payment-purpose placeholder="Условия обслуживания, лимиты, назначение счета"></textarea>
                </label>
                <div class="bank-modal__actions">
                    <button type="button" class="btn btn-secondary" data-bank-requisites-close>Отмена</button>
                    <button type="submit" class="btn btn-primary">Сохранить</button>
                </div>
            </form>
        </div>
    </div>

</div>

@include('bank.partials.styles')
@include('bank.partials.terminal_amount_inputs')

<script>
document.addEventListener('DOMContentLoaded', () => {
    const root = document.querySelector('[data-bank-accounts-page]');
    if (!root) return;

    const sectionTriggers = root.querySelectorAll('[data-bank-section-trigger]');
    const sectionPanels = root.querySelectorAll('[data-bank-section-panel]');

    sectionTriggers.forEach((trigger) => {
        trigger.addEventListener('click', () => {
            const target = trigger.dataset.bankSectionTrigger;
            sectionTriggers.forEach((item) => {
                const isActive = item === trigger;
                item.classList.toggle('is-active', isActive);
                item.setAttribute('aria-selected', isActive ? 'true' : 'false');
            });
            sectionPanels.forEach((panel) => {
                panel.hidden = panel.dataset.bankSectionPanel !== target;
            });
        });
    });

    root.querySelectorAll('[data-bank-project-modal-open]').forEach((row) => {
        row.addEventListener('click', () => {
            const modal = root.querySelector(`[data-bank-project-modal="${row.dataset.bankProjectModalOpen}"]`);
            if (!modal) return;

            modal.hidden = false;
            modal.querySelector('[data-bank-project-modal-close]')?.focus();
        });
    });

    root.querySelectorAll('[data-bank-project-modal-close]').forEach((button) => {
        button.addEventListener('click', () => {
            const modal = button.closest('[data-bank-project-modal]');
            if (modal) {
                modal.hidden = true;
            }
        });
    });

    const requisitesModal = root.querySelector('[data-bank-requisites-modal]');
    const requisitesForm = root.querySelector('[data-bank-requisites-form]');
    const requisitesContext = root.querySelector('[data-bank-requisites-context]');
    const requisitesProject = root.querySelector('[data-bank-requisites-project]');
    const requisitesAccount = root.querySelector('[data-bank-requisites-account]');
    const requisitesCurrency = root.querySelector('[data-bank-requisites-currency]');
    const requisitesBalance = root.querySelector('[data-bank-requisites-balance]');
    const requisitesAddress = root.querySelector('[data-bank-requisites-address]');
    const requisitesBankName = root.querySelector('[data-bank-requisites-bank-name]');
    const requisitesBankCode = root.querySelector('[data-bank-requisites-bank-code]');
    const requisitesCompanyName = root.querySelector('[data-bank-requisites-company-name]');
    const requisitesCompanyCode = root.querySelector('[data-bank-requisites-company-code]');
    const requisitesPaymentPurpose = root.querySelector('[data-bank-requisites-payment-purpose]');
    const requisitesExchange = root.querySelector('[data-bank-requisites-exchange]');

    root.querySelectorAll('[data-bank-requisites-open]').forEach((button) => {
        button.addEventListener('click', (event) => {
            event.stopPropagation();
            if (!requisitesModal) return;

            if (requisitesForm) {
                requisitesForm.action = button.dataset.accountUpdateUrl || '';
            }
            requisitesProject.value = button.dataset.projectName || '';
            requisitesAccount.value = button.dataset.accountName || '';
            requisitesCurrency.value = button.dataset.accountCurrency || '';
            requisitesBalance.value = button.dataset.accountBalanceRaw || '0';
            if (requisitesAddress) {
                requisitesAddress.value = button.dataset.accountAddress || '';
            }
            if (requisitesBankName) {
                requisitesBankName.value = button.dataset.accountBankName || '';
            }
            if (requisitesBankCode) {
                requisitesBankCode.value = button.dataset.accountBankCode || '';
            }
            if (requisitesCompanyName) {
                requisitesCompanyName.value = button.dataset.accountCompanyName || button.dataset.projectName || '';
            }
            if (requisitesCompanyCode) {
                requisitesCompanyCode.value = button.dataset.accountCompanyCode || '';
            }
            if (requisitesPaymentPurpose) {
                requisitesPaymentPurpose.value = button.dataset.accountPaymentPurpose || '';
            }
            if (requisitesExchange) {
                requisitesExchange.checked = button.dataset.accountExchangeEnabled === '1';
            }
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

    root.querySelectorAll('[data-bank-person-modal-open]').forEach((row) => {
        row.addEventListener('click', () => {
            const modal = root.querySelector(`[data-bank-person-modal="${row.dataset.bankPersonModalOpen}"]`);
            if (!modal) return;

            modal.hidden = false;
            modal.querySelector('[data-bank-person-modal-close]')?.focus();
        });
    });

    root.querySelectorAll('[data-bank-person-modal-close]').forEach((button) => {
        button.addEventListener('click', () => {
            const modal = button.closest('[data-bank-person-modal]');
            if (modal) {
                modal.hidden = true;
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
                row.hidden = !matched;
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
