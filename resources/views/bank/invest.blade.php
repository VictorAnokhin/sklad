@extends('home')

@section('title')
Операции
@endsection

@section('content')
@php
    $formatMoney = static fn ($value): string => number_format((float) $value, 2, '.', ' ');
    $assetTypeLabels = [
        'token' => 'Токен',
        'nft' => 'NFT',
        'pool' => 'Пул',
        'defi' => 'DeFi',
    ];
@endphp

<div class="bank-page bank-invest-page" data-bank-invest-page>
    @include('bank.partials.invest_nav')

    <section class="bank-panel bank-table-panel">
        <div class="bank-table-header">
            <div>
                <div class="bank-label">Операции</div>
                <div class="bank-meta">Список операций без группировки по активам.</div>
            </div>
            <div class="bank-table-header__actions">
                <div class="bank-meta">{{ $investOperations->count() }} операций</div>
                <button type="button" class="btn btn-sm btn-primary" data-invest-operation-open>Создать</button>
            </div>
        </div>
        <div class="table-responsive bank-table-scroll">
            <table class="table table-dark table-hover table-sm align-middle bank-table bank-operation-table">
                <thead>
                    <tr>
                        <th class="bank-table__num">№</th>
                        <th>Дата</th>
                        <th>Операция</th>
                        <th>Актив</th>
                        <th>Счет</th>
                        <th class="text-end">Сумма</th>
                        <th>Проводка</th>
                        <th>Комментарий</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($investOperationRows as $movement)
                        @php
                            $directionClass = $movement['direction'] === 'revaluation'
                                ? 'bank-pill--warning'
                                : ($movement['direction'] === 'asset_to_account' ? 'bank-pill--currency' : 'bank-pill--company');
                            $movementJson = json_encode($movement, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
                        @endphp
                        <tr class="bank-table-row--clickable"
                            data-invest-operation-edit
                            data-operation-movement="{{ $movementJson }}">
                            <td class="bank-table__num bank-mono">{{ $movement['id'] }}</td>
                            <td>{{ $movement['date'] !== '' ? $movement['date'] : '—' }}</td>
                            <td>
                                <span class="bank-pill {{ $directionClass }}">{{ $movement['direction_label'] }}</span>
                                @unless($movement['can_edit'])
                                    <div class="bank-meta">{{ $movement['edit_hint'] }}</div>
                                @endunless
                            </td>
                            <td>
                                <strong>{{ $movement['asset_label'] }}</strong>
                                <div class="bank-meta">{{ $assetTypeLabels[$movement['asset_type']] ?? $movement['asset_type'] }}</div>
                            </td>
                            <td>{{ $movement['account_label'] }}</td>
                            <td class="text-end fw-semibold">{{ $formatMoney($movement['value_usd']) }} USD</td>
                            <td>
                                <span class="bank-status {{ $movement['status'] === 'posted' ? '' : 'bank-status--pending' }}">{{ $movement['status'] }}</span>
                                <div class="bank-meta">{{ $movement['ledger_transaction_id'] > 0 ? 'TX #' . $movement['ledger_transaction_id'] : 'проводки нет' }}</div>
                            </td>
                            <td class="bank-meta">{{ $movement['note'] !== '' ? $movement['note'] : '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">Операции пока не созданы.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <div class="bank-modal" data-invest-operation-modal hidden>
        <div class="bank-modal__backdrop" data-invest-operation-close></div>
        <div class="bank-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="investOperationModalTitle">
            <div class="bank-modal__header">
                <div>
                    <div class="bank-label">Операция</div>
                    <h2 id="investOperationModalTitle" data-invest-operation-title>Создать Счет ↔ Актив</h2>
                    <div class="bank-meta" data-invest-operation-subtitle>Фиксирует распределение операционного счета в инвестиционный актив с двойной записью учета.</div>
                </div>
                <button type="button" class="bank-modal__close" data-invest-operation-close aria-label="Закрыть">×</button>
            </div>
            <form method="POST" action="{{ route('bank.invest-operations.store') }}" class="bank-requisites-form" data-invest-operation-form>
                @csrf
                <input type="hidden" name="_method" value="PUT" data-invest-operation-method disabled>
                <div class="bank-form-grid bank-invest-operation-form">
                    <div class="bank-form-full bank-operation-mode" role="tablist" aria-label="Тип операции">
                        <button type="button" class="bank-operation-mode__button is-active" data-invest-operation-direction-tab="account_to_asset">Купить</button>
                        <button type="button" class="bank-operation-mode__button" data-invest-operation-direction-tab="asset_to_account">Продать</button>
                        <button type="button" class="bank-operation-mode__button" data-invest-operation-direction-tab="revaluation">Переоценка</button>
                        <input type="hidden" name="direction" value="account_to_asset" data-invest-operation-direction>
                    </div>

                    <label class="bank-form-field bank-invest-operation-field" data-invest-operation-field="date">
                        <span>Дата</span>
                        <input type="datetime-local" name="operated_at" data-invest-operation-date>
                    </label>

                    <label class="bank-form-field bank-invest-operation-field" data-invest-operation-field="account" data-invest-operation-account-section>
                        <span>Счет</span>
                        <select name="account_id" required data-invest-operation-account>
                            @forelse($operationalAccounts as $account)
                                <option value="{{ $account->id }}" data-currency="{{ $account->currency }}">{{ $account->label }} · {{ $account->currency }} · {{ $formatMoney($account->balance) }}</option>
                            @empty
                                <option value="">Операционные счета не найдены</option>
                            @endforelse
                        </select>
                    </label>

                    <label class="bank-form-field bank-invest-operation-field" data-invest-operation-field="asset">
                        <span>Актив</span>
                        <select name="asset_key" required data-invest-operation-asset>
                            @forelse($fixedAssetRows as $asset)
                                <option value="{{ $asset->asset_key }}">{{ $assetTypeLabels[$asset->asset_type] ?? $asset->asset_type }} · {{ $asset->name }} · {{ $formatMoney($asset->value_usd) }} USD</option>
                            @empty
                                <option value="">Активы не найдены</option>
                            @endforelse
                        </select>
                    </label>

                    <label class="bank-form-field bank-invest-operation-field" data-invest-operation-field="amount">
                        <span data-invest-operation-amount-label>Сумма</span>
                        <span class="bank-amount-currency-row">
                            <input type="text" name="amount" inputmode="numeric" required data-terminal-amount data-terminal-negative="1" data-invest-operation-amount>
                            <input type="text" name="currency" value="USD" maxlength="20" required data-invest-operation-currency aria-label="Валюта">
                        </span>
                        <small class="bank-field-hint" data-invest-operation-amount-hint>Сумма будет списана со счета и отражена на активе.</small>
                    </label>

                    <label class="bank-form-field bank-invest-operation-field" data-invest-operation-field="comment">
                        <span>Комментарий</span>
                        <textarea name="note" rows="3" data-invest-operation-note></textarea>
                    </label>
                </div>
                <div class="bank-modal__actions">
                    <button type="button" class="btn btn-secondary" data-invest-operation-close>Отмена</button>
                    <button type="submit" class="btn btn-warning" formnovalidate data-invest-operation-reverse hidden>Отменить проводку</button>
                    <label class="bank-operation-post-ledger">
                        <input type="checkbox" name="post_ledger" value="1" checked data-invest-operation-post-ledger>
                        <span>
                            <strong>Проводка</strong>
                        </span>
                    </label>
                    <button type="submit" class="btn btn-primary" data-invest-operation-submit>Сохранить</button>
                    <button type="submit" class="btn btn-outline-danger" formnovalidate data-invest-operation-delete hidden>Удалить</button>
                </div>
            </form>
        </div>
    </div>
</div>

@include('bank.partials.styles')
@include('bank.partials.terminal_amount_inputs')

<style>
    .bank-operation-mode {
        display: grid !important;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 6px;
        align-items: stretch;
        padding: 4px;
        border: 1px solid rgba(148, 163, 184, 0.22);
        border-radius: 8px;
        background: rgba(15, 23, 42, 0.72);
    }

    .bank-operation-mode__button {
        min-height: 38px;
        border: 0;
        border-radius: 6px;
        background: transparent;
        color: rgba(226, 232, 240, 0.82);
        font-weight: 800;
    }

    .bank-operation-mode__button.is-active {
        background: rgba(56, 189, 248, 0.18);
        color: #fff;
        box-shadow: inset 0 0 0 1px rgba(56, 189, 248, 0.36);
    }

    .bank-form-section {
        display: grid;
        gap: 10px;
        padding: 12px;
        border: 1px solid rgba(148, 163, 184, 0.16);
        border-radius: 10px;
        background: rgba(2, 6, 23, 0.22);
    }

    .bank-form-section__title {
        color: rgba(226, 232, 240, 0.88);
        font-size: 12px;
        font-weight: 900;
        letter-spacing: 0.12em;
        text-transform: uppercase;
    }

    .bank-field-hint {
        display: block;
        margin-top: 5px;
        color: rgba(148, 163, 184, 0.9);
        font-size: 12px;
        line-height: 1.4;
    }

    .bank-operation-ledger-note,
    .bank-operation-revaluation-note,
    .bank-operation-post-ledger {
        padding: 12px 14px;
        border: 1px solid rgba(56, 189, 248, 0.22);
        border-radius: 10px;
        background: rgba(8, 47, 73, 0.28);
    }

    .bank-operation-revaluation-note {
        border-color: rgba(251, 191, 36, 0.28);
        background: rgba(120, 53, 15, 0.24);
    }

    .bank-operation-post-ledger {
        display: flex;
        gap: 10px;
        align-items: center;
        margin: 0 0 0 auto;
        min-height: 38px;
        padding: 0 12px;
        border-color: rgba(148, 163, 184, 0.18);
        background: rgba(15, 23, 42, 0.48);
    }

    .bank-operation-ledger-note__title,
    .bank-operation-revaluation-note__title {
        color: #fff;
        font-weight: 900;
    }

    .bank-operation-ledger-note__body,
    .bank-operation-revaluation-note__body,
    .bank-operation-post-ledger small {
        display: block;
        margin-top: 4px;
        color: rgba(226, 232, 240, 0.82);
        font-size: 13px;
        line-height: 1.45;
    }

    .bank-invest-operation-form {
        grid-template-columns: 1fr;
    }

    .bank-invest-operation-field {
        grid-column: 1 / -1;
    }

    .bank-amount-currency-row {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 92px;
        gap: 8px;
        align-items: center;
    }

    .bank-invest-page .bank-table > :not(caption) > * > * {
        height: auto !important;
        min-height: 0 !important;
        padding-top: 0.22rem !important;
        padding-bottom: 0.22rem !important;
        line-height: 1.1 !important;
        vertical-align: middle !important;
    }

    .bank-invest-page .bank-table .bank-meta {
        margin-top: 0 !important;
        line-height: 1.08 !important;
    }

    .bank-invest-page .bank-table .bank-pill,
    .bank-invest-page .bank-table .bank-status {
        min-height: 0 !important;
        padding-top: 0.12rem !important;
        padding-bottom: 0.12rem !important;
        line-height: 1 !important;
    }

    .bank-invest-page .bank-operation-table {
        table-layout: fixed;
        min-width: 870px;
    }

    .bank-invest-page .bank-operation-table th,
    .bank-invest-page .bank-operation-table td {
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .bank-invest-page .bank-operation-table th:nth-child(1),
    .bank-invest-page .bank-operation-table td:nth-child(1) { width: 46px; }
    .bank-invest-page .bank-operation-table th:nth-child(2),
    .bank-invest-page .bank-operation-table td:nth-child(2) { width: 92px; }
    .bank-invest-page .bank-operation-table th:nth-child(3),
    .bank-invest-page .bank-operation-table td:nth-child(3) { width: 118px; }
    .bank-invest-page .bank-operation-table th:nth-child(4),
    .bank-invest-page .bank-operation-table td:nth-child(4) { width: 190px; }
    .bank-invest-page .bank-operation-table th:nth-child(5),
    .bank-invest-page .bank-operation-table td:nth-child(5) { width: 160px; }
    .bank-invest-page .bank-operation-table th:nth-child(6),
    .bank-invest-page .bank-operation-table td:nth-child(6) { width: 118px; }
    .bank-invest-page .bank-operation-table th:nth-child(7),
    .bank-invest-page .bank-operation-table td:nth-child(7) { width: 110px; }
    .bank-invest-page .bank-operation-table th:nth-child(8),
    .bank-invest-page .bank-operation-table td:nth-child(8) { width: 136px; }
</style>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const root = document.querySelector('[data-bank-invest-page]');
        if (!root) {
            return;
        }

        function parseJsonAttribute(value, fallback) {
            try {
                return JSON.parse(value || '');
            } catch (error) {
                return fallback;
            }
        }

        function formatDateTimeLocal(value = new Date()) {
            if (typeof value === 'string') {
                const raw = value.trim();
                if (!raw) {
                    return '';
                }
                if (/^\d{4}-\d{2}-\d{2}$/.test(raw)) {
                    return `${raw}T00:00`;
                }
                return raw.replace(' ', 'T').slice(0, 16);
            }

            const date = value instanceof Date ? value : new Date(value);
            if (Number.isNaN(date.getTime())) {
                return '';
            }
            const pad = (number) => String(number).padStart(2, '0');
            return [
                date.getFullYear(),
                pad(date.getMonth() + 1),
                pad(date.getDate()),
            ].join('-') + 'T' + [pad(date.getHours()), pad(date.getMinutes())].join(':');
        }

        const modal = root.querySelector('[data-invest-operation-modal]');
        const form = root.querySelector('[data-invest-operation-form]');
        const method = root.querySelector('[data-invest-operation-method]');
        const title = root.querySelector('[data-invest-operation-title]');
        const subtitle = root.querySelector('[data-invest-operation-subtitle]');
        const submit = root.querySelector('[data-invest-operation-submit]');
        const deleteButton = root.querySelector('[data-invest-operation-delete]');
        const reverseButton = root.querySelector('[data-invest-operation-reverse]');
        const direction = root.querySelector('[data-invest-operation-direction]');
        const directionTabs = root.querySelectorAll('[data-invest-operation-direction-tab]');
        const postLedger = root.querySelector('[data-invest-operation-post-ledger]');
        const postLedgerField = postLedger ? postLedger.closest('.bank-operation-post-ledger') : null;
        const account = root.querySelector('[data-invest-operation-account]');
        const asset = root.querySelector('[data-invest-operation-asset]');
        const currency = root.querySelector('[data-invest-operation-currency]');
        const amount = root.querySelector('[data-invest-operation-amount]');
        const amountLabel = root.querySelector('[data-invest-operation-amount-label]');
        const amountHint = root.querySelector('[data-invest-operation-amount-hint]');
        const valueSectionTitle = root.querySelector('[data-invest-operation-value-section-title]');
        const accountSection = root.querySelector('[data-invest-operation-account-section]');
        const ledgerCopy = root.querySelector('[data-invest-operation-ledger-copy]');
        const revaluationNote = root.querySelector('[data-invest-operation-revaluation-note]');
        const operatedAt = root.querySelector('[data-invest-operation-date]');
        const note = root.querySelector('[data-invest-operation-note]');
        const storeAction = form ? form.action : '';
        const operationFields = {
            date: root.querySelector('[data-invest-operation-field="date"]'),
            account: root.querySelector('[data-invest-operation-field="account"]'),
            asset: root.querySelector('[data-invest-operation-field="asset"]'),
            amount: root.querySelector('[data-invest-operation-field="amount"]'),
            comment: root.querySelector('[data-invest-operation-field="comment"]'),
        };

        function syncCurrencyFromAccount() {
            if (!account || !currency || direction?.value === 'revaluation') {
                return;
            }
            const selectedOption = account.selectedOptions && account.selectedOptions.length > 0
                ? account.selectedOptions[0]
                : null;
            const accountCurrency = selectedOption?.dataset.currency || '';
            if (accountCurrency) {
                currency.value = accountCurrency;
            }
        }

        function setDirection(nextDirection) {
            if (direction) {
                direction.value = nextDirection;
            }
            directionTabs.forEach((tab) => {
                tab.classList.toggle('is-active', tab.dataset.investOperationDirectionTab === nextDirection);
            });
            if (accountSection) {
                accountSection.hidden = nextDirection === 'revaluation';
            }
            if (account) {
                account.required = nextDirection !== 'revaluation';
            }
            if (revaluationNote) {
                revaluationNote.hidden = nextDirection !== 'revaluation';
            }
            if (amountLabel) {
                amountLabel.textContent = nextDirection === 'revaluation' ? 'Дельта стоимости' : 'Сумма';
            }
            if (currency) {
                currency.readOnly = nextDirection !== 'revaluation';
                if (nextDirection !== 'revaluation') {
                    syncCurrencyFromAccount();
                }
            }
            if (valueSectionTitle) {
                valueSectionTitle.textContent = nextDirection === 'revaluation' ? '2. Дельта стоимости актива' : '3. Сумма и параметры сделки';
            }
            if (amountHint) {
                amountHint.textContent = nextDirection === 'revaluation'
                    ? 'Введите изменение стоимости: + увеличивает актив, - уменьшает актив.'
                    : nextDirection === 'asset_to_account'
                        ? 'Сумма будет возвращена из актива на операционный счет.'
                        : 'Сумма будет списана со счета и отражена на активе.';
            }
            if (ledgerCopy) {
                ledgerCopy.textContent = nextDirection === 'revaluation'
                    ? 'Положительная дельта: Дт Инвестиционный актив · Кт Доход 746. Отрицательная дельта: Дт Расход 975 · Кт Инвестиционный актив.'
                    : nextDirection === 'asset_to_account'
                        ? 'Дт Операционный счет · Кт Инвестиционный актив. Остаток операционного счета увеличится.'
                        : 'Дт Инвестиционный актив · Кт Операционный счет. Остаток операционного счета уменьшится.';
            }

            const fieldOrder = nextDirection === 'asset_to_account'
                ? ['date', 'asset', 'account', 'amount', 'comment']
                : nextDirection === 'revaluation'
                    ? ['date', 'asset', 'amount', 'comment']
                    : ['date', 'account', 'asset', 'amount', 'comment'];

            Object.values(operationFields).forEach((field) => {
                if (field) {
                    field.style.order = '';
                }
            });
            fieldOrder.forEach((fieldName, index) => {
                if (operationFields[fieldName]) {
                    operationFields[fieldName].style.order = String(index + 1);
                }
            });
            if (operationFields.account) {
                operationFields.account.hidden = nextDirection === 'revaluation';
            }
        }

        function setReadOnly(readOnly) {
            [account, asset, currency, amount, operatedAt, note, postLedger].forEach((field) => {
                if (field) {
                    field.disabled = readOnly;
                }
            });
            directionTabs.forEach((tab) => {
                tab.disabled = readOnly;
            });
        }

        function ensureAssetOption(movement) {
            if (!asset || !movement?.asset_key) {
                return;
            }
            const movementAssetKey = String(movement.asset_key);
            const exists = Array.from(asset.options).some((option) => option.value === movementAssetKey);
            if (exists) {
                return;
            }

            const option = document.createElement('option');
            option.value = movementAssetKey;
            option.textContent = [
                movement.asset_type || 'Актив',
                movement.asset_label || movementAssetKey,
                movement.currency || 'USD',
            ].filter(Boolean).join(' · ');
            asset.appendChild(option);
        }

        function resetForm() {
            form?.reset();
            if (form) {
                form.action = storeAction;
                form.dataset.mode = 'save';
                form.dataset.saveAction = storeAction;
                form.dataset.deleteAction = '';
                form.dataset.reverseAction = '';
            }
            if (method) {
                method.disabled = true;
            }
            setReadOnly(false);
            setDirection('account_to_asset');
            if (postLedger) {
                postLedger.checked = true;
                postLedger.disabled = false;
            }
            if (postLedgerField) {
                postLedgerField.hidden = false;
            }
            if (operatedAt) {
                operatedAt.value = formatDateTimeLocal();
            }
            if (title) title.textContent = 'Создать Счет ↔ Актив';
            if (subtitle) subtitle.textContent = 'Фиксирует распределение операционного счета в инвестиционный актив с двойной записью учета.';
            if (submit) {
                submit.textContent = 'Сохранить';
                submit.hidden = false;
            }
            if (deleteButton) deleteButton.hidden = true;
            if (reverseButton) {
                reverseButton.hidden = true;
                reverseButton.disabled = false;
            }
        }

        function fillForm(movement) {
            if (!form || !movement) {
                return;
            }
            form.action = movement.update_action || storeAction;
            form.dataset.mode = 'save';
            form.dataset.saveAction = movement.update_action || storeAction;
            form.dataset.deleteAction = movement.destroy_action || '';
            form.dataset.reverseAction = movement.reverse_action || '';
            if (method) {
                method.disabled = false;
                method.value = 'PUT';
            }
            setDirection(movement.direction || 'account_to_asset');
            if (postLedger) {
                postLedger.checked = Boolean(movement.is_posted);
                postLedger.disabled = Boolean(movement.is_posted) || !movement.can_edit;
            }
            if (postLedgerField) {
                postLedgerField.hidden = Boolean(movement.is_posted);
            }
            if (account) account.value = String(movement.account_id || '');
            ensureAssetOption(movement);
            if (asset) asset.value = String(movement.asset_key || '');
            if (currency) currency.value = movement.currency || 'USD';
            if ((movement.direction || 'account_to_asset') !== 'revaluation') {
                syncCurrencyFromAccount();
            }
            if (amount) amount.value = movement.amount || movement.value_usd || '';
            if (operatedAt) operatedAt.value = formatDateTimeLocal(movement.date || '');
            if (note) note.value = movement.note || '';
            if (title) title.textContent = `Редактировать движение #${movement.id}`;
            if (subtitle) {
                subtitle.textContent = !movement.can_edit
                    ? (movement.edit_hint || 'Редактирование закрыто.')
                    : movement.is_posted
                        ? 'Документ проведен. Доступна только отмена операции, если сторно разрешено.'
                        : 'Документ сохранен без проводки. Включите чекбокс, чтобы создать двойную запись.';
            }
            if (submit) {
                submit.hidden = Boolean(movement.is_posted) || !movement.can_edit;
            }
            if (deleteButton) {
                deleteButton.hidden = Boolean(movement.is_posted) || !movement.can_edit;
            }
            if (reverseButton) {
                reverseButton.textContent = 'Отменить операцию';
                reverseButton.hidden = !movement.is_posted;
                reverseButton.disabled = !movement.can_reverse;
            }
            setReadOnly(Boolean(movement.is_posted) || !movement.can_edit);
        }

        directionTabs.forEach((tab) => {
            tab.addEventListener('click', () => setDirection(tab.dataset.investOperationDirectionTab || 'account_to_asset'));
        });

        account?.addEventListener('change', syncCurrencyFromAccount);

        submit?.addEventListener('click', () => {
            if (!form) return;
            form.dataset.mode = 'save';
            form.action = form.dataset.saveAction || storeAction;
            if (method) {
                method.disabled = form.action === storeAction;
                method.value = 'PUT';
            }
        });

        deleteButton?.addEventListener('click', () => {
            if (!form) return;
            form.dataset.mode = 'delete';
            form.action = form.dataset.deleteAction || form.action;
            if (method) {
                method.disabled = false;
                method.value = 'DELETE';
            }
        });

        reverseButton?.addEventListener('click', () => {
            if (!form) return;
            form.dataset.mode = 'reverse';
            form.action = form.dataset.reverseAction || form.action;
            if (method) {
                method.disabled = true;
            }
        });

        root.querySelectorAll('[data-invest-operation-open]').forEach((button) => {
            button.addEventListener('click', () => {
                resetForm();
                if (modal) {
                    modal.hidden = false;
                }
                account?.focus();
            });
        });

        root.querySelectorAll('[data-invest-operation-edit]').forEach((row) => {
            row.addEventListener('click', () => {
                fillForm(parseJsonAttribute(row.dataset.operationMovement, null));
                if (modal) {
                    modal.hidden = false;
                }
                amount?.focus();
            });
        });

        root.querySelectorAll('[data-invest-operation-close]').forEach((button) => {
            button.addEventListener('click', () => {
                if (modal) {
                    modal.hidden = true;
                }
            });
        });
    });
</script>
@endpush
