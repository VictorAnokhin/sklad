@extends('home')

@section('title')
Операционные счета
@endsection

@section('content')
<div class="bank-page" data-bank-operational-accounts-page>
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

    <section class="bank-panel bank-currency-strip">
        <div class="bank-table-header">
            <div>
                <div class="bank-label">Остатки</div>
                <div class="bank-meta">Сводка по валютам операционных счетов.</div>
            </div>
        </div>
        <div class="bank-currency-list">
            @forelse($operationalTotalByCurrency as $currency => $total)
                <div class="bank-currency-item">
                    <span>{{ $currency }}</span>
                    <strong>{{ number_format((float) $total, 2, '.', ' ') }}</strong>
                </div>
            @empty
                <div class="bank-empty">Операционные счета пока не созданы.</div>
            @endforelse
        </div>
    </section>

    <section class="bank-panel bank-table-panel">
        <div class="bank-table-header">
            <div>
                <div class="bank-label">Операционные счета</div>
                <div class="bank-meta">Список счетов с account type bank.</div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <div class="bank-meta">{{ $cashAccounts->count() }} счетов</div>
                <button type="button" class="btn btn-sm btn-primary" data-bank-operational-account-open>Создать</button>
            </div>
        </div>

        <div class="table-responsive bank-table-scroll">
            <table class="table table-dark table-hover table-sm align-middle bank-table">
                <thead>
                    <tr>
                        <th class="bank-table__num">ID</th>
                        <th>Название счета</th>
                        <th>Валюта</th>
                        <th class="text-end">Сумма</th>
                        <th>Google Auth</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($cashAccounts as $account)
                        <tr class="bank-account-edit-row"
                            data-bank-operational-account-open
                            data-account-id="{{ $account->id }}"
                            data-account-name="{{ $account->label }}"
                            data-account-currency="{{ $account->currency }}"
                            data-account-amount="{{ number_format((float) $account->balance, 2, '.', '') }}"
                            data-account-google-auth="{{ trim((string) ($account->google_map ?? '')) }}"
                            data-update-url="{{ route('bank.operational-accounts.update', ['account' => $account->id]) }}"
                            data-delete-url="{{ route('bank.operational-accounts.destroy', ['account' => $account->id]) }}">
                            <td class="bank-table__num bank-mono">{{ $account->id }}</td>
                            <td>{{ $account->label }}</td>
                            <td><span class="bank-pill bank-pill--currency">{{ $account->currency }}</span></td>
                            <td class="text-end fw-semibold">{{ number_format((float) $account->balance, 2, '.', ' ') }}</td>
                            <td class="bank-mono">{{ trim((string) ($account->google_map ?? '')) !== '' ? $account->google_map : '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">Операционные счета не созданы.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <div class="bank-modal" data-bank-operational-account-modal hidden>
        <div class="bank-modal__backdrop" data-bank-operational-account-close></div>
        <div class="bank-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="bankOperationalAccountTitle">
            <div class="bank-modal__header">
                <div>
                    <div class="bank-label">Операционные счета</div>
                    <h2 id="bankOperationalAccountTitle">Создать счет</h2>
                </div>
                <button type="button" class="bank-modal__close" data-bank-operational-account-close aria-label="Закрыть">×</button>
            </div>
            <form method="POST" action="{{ route('bank.operational-accounts.store') }}" class="bank-requisites-form" data-bank-operational-account-form>
                @csrf
                <input type="hidden" name="_method" value="POST" data-bank-operational-account-method>
                <input type="hidden" name="account_type" value="bank">
                <input type="hidden" name="redirect_to" value="bank.operational-accounts">
                <div class="bank-form-grid">
                    <label>
                        <span>Название счета</span>
                        <input type="text" name="name" required maxlength="255" autocomplete="off" data-bank-operational-account-name>
                    </label>
                    <label>
                        <span>Валюта</span>
                        <select name="currency" required data-bank-operational-account-currency>
                            @foreach(['UAH', 'USD', 'EUR', 'USDC', 'USDT', 'AV8', 'SUI'] as $currency)
                                <option value="{{ $currency }}">{{ $currency }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label>
                        <span>Сумма</span>
                        <input type="text" name="amount" value="0.00" required inputmode="numeric" data-terminal-amount data-bank-operational-account-amount>
                    </label>
                    <label>
                        <span>Google Auth</span>
                        <input type="text" name="google_auth" maxlength="255" autocomplete="off" data-bank-operational-account-google-auth>
                    </label>
                </div>
                <div class="bank-modal__actions">
                    <button type="submit" form="bankOperationalAccountDeleteForm" class="btn btn-outline-danger me-auto" data-bank-operational-account-delete hidden onclick="return confirm('Удалить операционный счёт?');">Удалить</button>
                    <button type="button" class="btn btn-secondary" data-bank-operational-account-close>Отмена</button>
                    <button type="submit" class="btn btn-primary" data-bank-operational-account-submit>Создать</button>
                </div>
            </form>
            <form method="POST" id="bankOperationalAccountDeleteForm" data-bank-operational-account-delete-form>
                @csrf
                @method('DELETE')
                <input type="hidden" name="redirect_to" value="bank.operational-accounts">
            </form>
        </div>
    </div>
</div>

@include('bank.partials.styles')
@include('bank.partials.terminal_amount_inputs')

<script>
document.addEventListener('DOMContentLoaded', () => {
    const root = document.querySelector('[data-bank-operational-accounts-page]');
    if (!root) return;

    const operationalModal = root.querySelector('[data-bank-operational-account-modal]');
    const operationalForm = root.querySelector('[data-bank-operational-account-form]');
    const operationalMethod = root.querySelector('[data-bank-operational-account-method]');
    const operationalTitle = root.querySelector('#bankOperationalAccountTitle');
    const operationalName = root.querySelector('[data-bank-operational-account-name]');
    const operationalCurrency = root.querySelector('[data-bank-operational-account-currency]');
    const operationalAmount = root.querySelector('[data-bank-operational-account-amount]');
    const operationalGoogleAuth = root.querySelector('[data-bank-operational-account-google-auth]');
    const operationalSubmit = root.querySelector('[data-bank-operational-account-submit]');
    const operationalDelete = root.querySelector('[data-bank-operational-account-delete]');
    const operationalDeleteForm = root.querySelector('[data-bank-operational-account-delete-form]');
    const operationalStoreUrl = @json(route('bank.operational-accounts.store'));

    function openOperationalAccountModal(trigger) {
        if (!operationalModal || !operationalForm || !operationalMethod) {
            return;
        }

        const isEdit = trigger.dataset.accountId !== undefined;
        operationalForm.action = isEdit ? (trigger.dataset.updateUrl || '') : operationalStoreUrl;
        operationalMethod.value = isEdit ? 'PUT' : 'POST';
        if (operationalTitle) {
            operationalTitle.textContent = isEdit ? 'Редактировать счет' : 'Создать счет';
        }
        if (operationalName) {
            operationalName.value = isEdit ? (trigger.dataset.accountName || '') : '';
        }
        if (operationalCurrency) {
            operationalCurrency.value = isEdit ? (trigger.dataset.accountCurrency || 'UAH') : 'UAH';
        }
        if (operationalAmount) {
            operationalAmount.value = isEdit ? (trigger.dataset.accountAmount || '0.00') : '0';
        }
        if (operationalGoogleAuth) {
            operationalGoogleAuth.value = isEdit ? (trigger.dataset.accountGoogleAuth || '') : '';
        }
        if (operationalSubmit) {
            operationalSubmit.textContent = isEdit ? 'Сохранить' : 'Создать';
        }
        if (operationalDelete && operationalDeleteForm) {
            operationalDelete.hidden = !isEdit;
            operationalDeleteForm.action = isEdit ? (trigger.dataset.deleteUrl || '') : '';
        }

        operationalModal.hidden = false;
        operationalName?.focus();
    }

    root.querySelectorAll('[data-bank-operational-account-open]').forEach((trigger) => {
        trigger.addEventListener('click', () => {
            openOperationalAccountModal(trigger);
        });
    });

    root.querySelectorAll('[data-bank-operational-account-close]').forEach((button) => {
        button.addEventListener('click', () => {
            if (operationalModal) {
                operationalModal.hidden = true;
            }
        });
    });
});
</script>
@endsection
