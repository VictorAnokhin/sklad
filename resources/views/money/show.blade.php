@extends('home')

@section('title', $document->id ? __('money.edit_title', ['num' => $document->num]) : __('money.create_title'))

@section('content')
@php
    $activeTab = ($tab ?? 'orders') === 'transfers' ? 'transfers' : 'orders';
    $indexRouteName = $indexRouteName ?? ($activeTab === 'transfers' ? 'money.transfers' : 'money.index');
    $showRouteName = $showRouteName ?? 'money.show';
@endphp

@include('money.partials.top-actions', ['returnFilters' => $returnFilters ?? [], 'tab' => $activeTab, 'indexRouteName' => $indexRouteName, 'showRouteName' => $showRouteName])

<div class="ttable money-show-page" style="padding: 20px; max-width: 760px; margin: 0 auto; border-radius: 8px;">
    @php
        $isNew = empty($document->id);
        $backUrl = route($indexRouteName, $returnFilters ?? []);
        $documentDateValue = (string) ($document->data ?? '');
        if (preg_match('/^\d{2}-\d{2}-\d{4}$/', $documentDateValue) === 1) {
            $documentDateValue = \DateTimeImmutable::createFromFormat('d-m-Y', $documentDateValue)?->format('Y-m-d') ?? '';
        }
    @endphp

    @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @if($activeTab === 'transfers')
    <h3 style="color:#0d6efd;">
        🔄 {{ __('money.heading_transfer') }} @if(!$isNew) № {{ $document->num }} @endif
    </h3>

    <form action="{{ route('money.save') }}" method="post" class="compact-form">
        @csrf
        <input type="hidden" name="id" value="{{ $document->id ?? 0 }}">
        <input type="hidden" name="tab" value="transfers">
        <input type="hidden" name="return_q" value="{{ $returnFilters['q'] ?? '' }}">
        <input type="hidden" name="return_money" value="{{ $returnFilters['money'] ?? '' }}">
        <input type="hidden" name="return_date_from" value="{{ $returnFilters['date_from'] ?? '' }}">
        <input type="hidden" name="return_date_to" value="{{ $returnFilters['date_to'] ?? '' }}">
        <input type="hidden" name="return_pos" value="{{ $returnFilters['pos'] ?? '' }}">

        <div class="row">
            <div class="col-md-4 mb-3">
                <label>{{ __('money.field_date') }}</label>
                <input type="date" name="data" class="form-control" value="{{ $documentDateValue }}">
            </div>
            <div class="col-md-4 mb-3">
                <label>{{ __('money.field_amount_from') }}</label>
                <div class="input-group">
                    <input type="text" name="summa" id="transferAmountFrom" class="form-control" value="{{ old('summa', $document->summa ?? 0) }}"
                        inputmode="decimal" autocomplete="off" data-decimal-input="1">
                    <input type="text" name="currency_from" id="transferCurrencyFrom" class="form-control" style="max-width:90px;" value="{{ old('currency_from', $document->currency_from ?? 'UAH') }}" maxlength="10">
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <label>{{ __('money.field_status') }}</label>
                <input type="text" class="form-control" value="{{ (int)($document->provodka ?? 0) === 1 ? __('money.status_posted') : __('money.status_draft') }}" disabled>
            </div>
        </div>

        <div class="row">
            <div class="col-md-4 mb-3">
                <label>{{ __('money.field_exchange_rate') }}</label>
                <input type="text" name="exchange_rate" id="transferExchangeRate" class="form-control" value="{{ old('exchange_rate', $document->exchange_rate ?? 1) }}"
                    inputmode="decimal" autocomplete="off" data-decimal-input="1">
                <small class="text-muted" id="transferRateHint"></small>
            </div>
            <div class="col-md-4 mb-3">
                <label>{{ __('money.field_amount_to') }}</label>
                <div class="input-group">
                    <input type="text" name="summa2" id="transferAmountTo" class="form-control" value="{{ old('summa2', ($document->summa2 ?? 0) > 0 ? $document->summa2 : ($document->summa ?? 0)) }}"
                        inputmode="decimal" autocomplete="off" data-decimal-input="1">
                    <input type="text" name="currency_to" id="transferCurrencyTo" class="form-control" style="max-width:90px;" value="{{ old('currency_to', $document->currency_to ?? 'UAH') }}" maxlength="10">
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <label>{{ __('money.field_commission') }}</label>
                <div class="input-group">
                    <input type="text" name="commission_amount" class="form-control" value="{{ old('commission_amount', $document->commission_amount ?? 0) }}"
                        inputmode="decimal" autocomplete="off" data-decimal-input="1">
                    <input type="text" name="commission_currency" id="transferCommissionCurrency" class="form-control" style="max-width:90px;" value="{{ old('commission_currency', $document->commission_currency ?? ($document->currency_from ?? 'UAH')) }}" maxlength="10" readonly>
                </div>
            </div>
        </div>

        <div class="glass-card" style="margin-bottom:12px; border:1px solid rgba(13, 110, 253, 0.15);">
            <div style="font-size:0.82rem; text-transform:uppercase; letter-spacing:0.08em; color:#0a58ca; margin-bottom:8px;">{{ __('money.label_from_cashbox') }}</div>
            <select name="oplata" id="transferCashboxFrom" class="form-control" required>
                <option value="">{{ __('money.select_cashbox') }}</option>
                @foreach($kassas as $kassa)
                <option value="{{ $kassa->id }}" data-currency="{{ $kassa->currency ?? 'UAH' }}" {{ (string) old('oplata', $document->oplata ?? '') === (string) $kassa->id ? 'selected' : '' }}>
                    {{ $kassa->name }} ({{ number_format((float) ($kassa->balance ?? 0), 2, '.', ' ') }} {{ $kassa->currency ?? 'UAH' }})
                </option>
                @endforeach
            </select>
        </div>

        <div style="text-align:center; font-size:1.6rem; color:#0d6efd; margin:6px 0 12px;">→</div>

        <div class="glass-card" style="margin-bottom:16px; border:1px solid rgba(13, 110, 253, 0.15);">
            <div style="font-size:0.82rem; text-transform:uppercase; letter-spacing:0.08em; color:#0a58ca; margin-bottom:8px;">{{ __('money.label_to_cashbox') }}</div>
            <select name="oplata2" id="transferCashboxTo" class="form-control" required>
                <option value="">{{ __('money.select_cashbox') }}</option>
                @foreach($targetKassas ?? $kassas as $kassa)
                <option value="{{ $kassa->id }}" data-currency="{{ $kassa->currency ?? 'UAH' }}" {{ (string) old('oplata2', $document->oplata2 ?? '') === (string) $kassa->id ? 'selected' : '' }}>
                    {{ $kassa->name }} ({{ number_format((float) ($kassa->balance ?? 0), 2, '.', ' ') }} {{ $kassa->currency ?? 'UAH' }})
                </option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label>{{ __('money.field_comment') }}</label>
            <input type="text" name="content" class="form-control" value="{{ old('content', $document->content ?? '') }}">
        </div>

        @if((int)($document->provodka ?? 0) === 0)
        <div class="mb-3 form-check">
            <input type="hidden" name="post_after_save" value="0">
            <input type="checkbox" class="form-check-input" id="post_after_save" name="post_after_save" value="1" checked>
            <label class="form-check-label" for="post_after_save">{{ __('money.checkbox_post') }}</label>
        </div>
        @endif

        <div class="d-flex gap-2 align-items-center flex-wrap">
            <a href="{{ $backUrl }}" class="btn btn-outline-secondary">← {{ __('money.btn_back') }}</a>
            @if((int)($document->provodka ?? 0) === 1)
            <button type="submit" formaction="{{ route('money.provodka') }}" formmethod="post" class="btn btn-success">
                ↺ {{ __('money.btn_unpost') }}
            </button>
            @else
            <button type="submit" class="btn">💾 {{ __('money.btn_save') }}</button>
            @endif
            @if((int)($document->provodka ?? 0) === 0 && !$isNew)
            <button type="button" class="btn btn-danger" onclick="if(confirm('{{ __('money.confirm_delete') }}')) { document.getElementById('deleteMoneyForm').submit(); }">
                🗑 {{ __('money.btn_delete') }}
            </button>
            @endif
        </div>
    </form>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const amountFrom = document.getElementById('transferAmountFrom');
            const amountTo = document.getElementById('transferAmountTo');
            const rate = document.getElementById('transferExchangeRate');
            const currencyFrom = document.getElementById('transferCurrencyFrom');
            const currencyTo = document.getElementById('transferCurrencyTo');
            const commissionCurrency = document.getElementById('transferCommissionCurrency');
            const cashboxFrom = document.getElementById('transferCashboxFrom');
            const cashboxTo = document.getElementById('transferCashboxTo');
            const rateHint = document.getElementById('transferRateHint');
            let editedFields = [];

            const parseDecimal = (value) => {
                const normalized = String(value || '').replace(/\s/g, '').replace(',', '.');
                const parsed = Number.parseFloat(normalized);

                return Number.isFinite(parsed) ? parsed : 0;
            };
            const formatDecimal = (value, digits = 2) => {
                if (!Number.isFinite(value)) {
                    return '';
                }

                return value.toFixed(digits).replace(/\.?0+$/, '');
            };
            const normalizeCurrency = (value) => {
                const normalized = String(value || '').toUpperCase().replace(/[^A-Z0-9]/g, '').slice(0, 10);

                return normalized || 'UAH';
            };
            const selectedCurrency = (select) => select?.selectedOptions?.[0]?.dataset?.currency || '';
            const syncRateHint = () => {
                const from = normalizeCurrency(currencyFrom.value);
                const to = normalizeCurrency(currencyTo.value);
                const rateValue = parseDecimal(rate.value) || 1;

                rateHint.textContent = `1 ${from} = ${formatDecimal(rateValue, 8)} ${to}`;
            };
            const recalculate = () => {
                const from = parseDecimal(amountFrom.value);
                const to = parseDecimal(amountTo.value);
                const rateValue = parseDecimal(rate.value);
                const target = ['amount_from', 'amount_to', 'rate'].find((field) => !editedFields.slice(-2).includes(field)) || 'amount_to';

                if (target === 'amount_from' && to > 0 && rateValue > 0) {
                    amountFrom.value = formatDecimal(to / rateValue);
                } else if (target === 'amount_to' && from > 0 && rateValue > 0) {
                    amountTo.value = formatDecimal(from * rateValue);
                } else if (target === 'rate' && from > 0 && to > 0) {
                    rate.value = formatDecimal(to / from, 8);
                }

                syncRateHint();
            };
            const markEdited = (field) => {
                editedFields = editedFields.filter((item) => item !== field);
                editedFields.push(field);
            };
            const syncCurrencyFromCashbox = (select, input) => {
                const currency = selectedCurrency(select);
                if (currency && (!input.value || input.dataset.userEdited !== '1')) {
                    input.value = currency;
                }
            };

            [currencyFrom, currencyTo].forEach((input) => {
                input.addEventListener('input', () => {
                    input.dataset.userEdited = '1';
                    input.value = normalizeCurrency(input.value);
                    if (input === currencyFrom) {
                        commissionCurrency.value = input.value;
                    }
                    syncRateHint();
                });
            });
            amountFrom.addEventListener('input', () => { markEdited('amount_from'); recalculate(); });
            amountTo.addEventListener('input', () => { markEdited('amount_to'); recalculate(); });
            rate.addEventListener('input', () => { markEdited('rate'); recalculate(); });
            cashboxFrom.addEventListener('change', () => {
                syncCurrencyFromCashbox(cashboxFrom, currencyFrom);
                commissionCurrency.value = currencyFrom.value;
                syncRateHint();
            });
            cashboxTo.addEventListener('change', () => {
                syncCurrencyFromCashbox(cashboxTo, currencyTo);
                syncRateHint();
            });

            syncCurrencyFromCashbox(cashboxFrom, currencyFrom);
            syncCurrencyFromCashbox(cashboxTo, currencyTo);
            commissionCurrency.value = currencyFrom.value;
            syncRateHint();
        });
    </script>
    @else
    @php
        $type = request('type', $document->type ?? 'PPO');
        $isPO = $type === 'PPO';
        $selectedBalanceCurrency = old('balance_currency', $document->currency_from ?? (($ownerBalances[0]['currency'] ?? 'UAH')));
        $ownerBalanceLabel = collect($ownerBalances ?? [])->map(function ($balance) {
            return ($balance['amount'] ?? '0') . ' ' . ($balance['currency'] ?? 'UAH');
        })->implode(' | ');
        $currentUserId = (string) (\Illuminate\Support\Facades\Auth::id() ?: session('userid', '0'));
        $isDocumentOwner = (string) ($document->client2 ?? '') !== '' && (string) ($document->client2 ?? '') === $currentUserId;
        $authorName = trim(implode(' ', array_filter([
            $document->owner_orgname ?? '',
            $document->owner_secondname ?? '',
            $document->owner_name ?? '',
            $document->owner_fathername ?? '',
        ])));
    @endphp

    @if($type === 'PPP')
    @php
        $selectedCurrencyFrom = old('currency_from', $document->currency_from ?? (($ownerBalances[0]['currency'] ?? 'UAH')));
        $selectedCurrencyTo = old('currency_to', $document->currency_to ?? (($ownerBalances[1]['currency'] ?? ($ownerBalances[0]['currency'] ?? 'USD'))));
        if ($selectedCurrencyTo === $selectedCurrencyFrom) {
            $selectedCurrencyTo = collect($ownerBalances ?? [])->first(fn ($balance) => ($balance['currency'] ?? '') !== $selectedCurrencyFrom)['currency'] ?? $selectedCurrencyTo;
        }
        $summaFrom = old('summa', $document->summa ?? 0);
        $summaTo = old('summa2', ($document->summa2 ?? 0) > 0 ? $document->summa2 : 0);
        $exchangeRateValue = old('exchange_rate', $document->exchange_rate ?? 1);
    @endphp

    <h3 style="color:#0d6efd;">
        🔁 Обмін валют між балансами @if(!$isNew) № {{ $document->num }} @endif
    </h3>
    @if($isDocumentOwner)
    <div class="text-muted mb-3">Ваш баланс: {{ $ownerBalanceLabel !== '' ? $ownerBalanceLabel : '0 UAH' }}</div>
    @elseif($authorName !== '')
    <div class="text-muted mb-3">Автор: {{ $authorName }}</div>
    @endif

    <form action="{{ route('money.save') }}" method="post" class="compact-form">
        @csrf
        <input type="hidden" name="id" value="{{ $document->id ?? 0 }}">
        <input type="hidden" name="type" value="PPP">
        <input type="hidden" name="tab" value="orders">
        <input type="hidden" name="return_q" value="{{ $returnFilters['q'] ?? '' }}">
        <input type="hidden" name="return_filter_type" value="{{ $returnFilters['type'] ?? '' }}">
        <input type="hidden" name="return_money" value="{{ $returnFilters['money'] ?? '' }}">
        <input type="hidden" name="return_reestr" value="{{ $returnFilters['reestr'] ?? '' }}">
        <input type="hidden" name="return_date_from" value="{{ $returnFilters['date_from'] ?? '' }}">
        <input type="hidden" name="return_date_to" value="{{ $returnFilters['date_to'] ?? '' }}">
        <input type="hidden" name="return_pos" value="{{ $returnFilters['pos'] ?? '' }}">

        <div class="row">
            <div class="col-md-4 mb-3">
                <label>{{ __('money.field_date') }}</label>
                <input type="date" name="data" class="form-control" value="{{ $documentDateValue }}" placeholder="{{ __('money.date_placeholder') }}">
            </div>
            <div class="col-md-4 mb-3">
                <label>Статус</label>
                <input type="text" class="form-control" value="{{ (int)($document->provodka ?? 0) === 1 ? __('money.status_posted') : __('money.status_draft') }}" disabled>
            </div>
        </div>

        <div class="glass-card" style="margin-bottom:12px; border:1px solid rgba(13, 110, 253, 0.15);">
            <div style="font-size:0.82rem; text-transform:uppercase; letter-spacing:0.08em; color:#0a58ca; margin-bottom:8px;">З балансу</div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <select name="currency_from" id="balanceExchangeFromCurrency" class="form-control" required>
                        @foreach(($ownerBalances ?? []) as $balance)
                        <option value="{{ $balance['currency'] }}" data-amount="{{ $balance['amount'] }}" {{ (string) $selectedCurrencyFrom === (string) $balance['currency'] ? 'selected' : '' }}>
                            {{ $balance['currency'] }} — {{ $balance['amount'] }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <input type="text" name="summa" id="balanceExchangeAmountFrom" class="form-control" value="{{ $summaFrom }}"
                        inputmode="numeric" autocomplete="off" data-terminal-input="1" placeholder="Сума списання">
                </div>
            </div>
        </div>

        <div style="text-align:center; font-size:1.6rem; color:#0d6efd; margin:6px 0 12px;">→</div>

        <div class="glass-card" style="margin-bottom:16px; border:1px solid rgba(13, 110, 253, 0.15);">
            <div style="font-size:0.82rem; text-transform:uppercase; letter-spacing:0.08em; color:#0a58ca; margin-bottom:8px;">На баланс</div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <select name="currency_to" id="balanceExchangeToCurrency" class="form-control" required>
                        @foreach(($ownerBalances ?? []) as $balance)
                        <option value="{{ $balance['currency'] }}" data-amount="{{ $balance['amount'] }}" {{ (string) $selectedCurrencyTo === (string) $balance['currency'] ? 'selected' : '' }}>
                            {{ $balance['currency'] }} — {{ $balance['amount'] }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <input type="text" name="summa2" id="balanceExchangeAmountTo" class="form-control" value="{{ $summaTo }}"
                        inputmode="numeric" autocomplete="off" data-terminal-input="1" placeholder="Сума зарахування">
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-4 mb-3">
                <label>Курс</label>
                <div class="input-group">
                    <input type="text" name="exchange_rate" id="balanceExchangeRate" class="form-control" value="{{ $exchangeRateValue }}"
                        inputmode="decimal" autocomplete="off" data-decimal-input="1">
                    <button type="button" class="btn btn-outline-secondary" id="balanceExchangeRateDirection" title="Змінити напрямок курсу">
                        ⇄
                    </button>
                </div>
                <small class="text-muted" id="balanceExchangeRateHint"></small>
            </div>
        </div>

        <div class="mb-3">
            <label>{{ __('money.field_comment') }}</label>
            <input type="text" name="content" class="form-control" value="{{ $document->content ?? '' }}">
        </div>

        @if((int)($document->provodka ?? 0) === 0)
        <div class="mb-3 form-check">
            <input type="hidden" name="post_after_save" value="0">
            <input type="checkbox" class="form-check-input" id="post_after_save" name="post_after_save" value="1" checked>
            <label class="form-check-label" for="post_after_save">{{ __('money.checkbox_post') }}</label>
        </div>
        @endif

        <div class="d-flex gap-2 align-items-center flex-wrap">
            <a href="{{ $backUrl }}" class="btn btn-outline-secondary">← {{ __('money.btn_back') }}</a>
            @if((int)($document->provodka ?? 0) === 1)
            <button type="submit" formaction="{{ route('money.provodka') }}" formmethod="post" class="btn btn-success">
                ↺ {{ __('money.btn_unpost') }}
            </button>
            @else
            <button type="submit" class="btn">💾 {{ __('money.btn_save') }}</button>
            @endif
            @if((int)($document->provodka ?? 0) === 0 && !$isNew)
            <button type="button" class="btn btn-danger" onclick="if(confirm('{{ __('money.confirm_delete') }}')) { document.getElementById('deleteMoneyForm').submit(); }">
                🗑 {{ __('money.btn_delete') }}
            </button>
            @endif
        </div>
    </form>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const amountFrom = document.getElementById('balanceExchangeAmountFrom');
            const amountTo = document.getElementById('balanceExchangeAmountTo');
            const rate = document.getElementById('balanceExchangeRate');
            const rateDirection = document.getElementById('balanceExchangeRateDirection');
            const currencyFrom = document.getElementById('balanceExchangeFromCurrency');
            const currencyTo = document.getElementById('balanceExchangeToCurrency');
            const hint = document.getElementById('balanceExchangeRateHint');
            const form = rate?.closest('form');
            let editedFields = [];
            let syncingTerminalAmount = false;
            let rateMode = 'from_to';

            const parseDecimal = (value) => {
                const parsed = Number.parseFloat(String(value || '').replace(/\s/g, '').replace(',', '.'));
                return Number.isFinite(parsed) ? parsed : 0;
            };
            const formatDecimal = (value, digits = 2) => {
                if (!Number.isFinite(value)) return '';
                return value.toFixed(digits).replace(/\.?0+$/, '');
            };
            const syncTerminalAmountState = (input) => {
                if (input?.dataset.terminalInputBound === '1') {
                    input.dataset.terminalAmountCents = String(Math.round(parseDecimal(input.value) * 100));
                }
            };
            const getEffectiveRate = () => {
                const rateValue = parseDecimal(rate.value);

                if (rateValue <= 0) {
                    return 0;
                }

                return rateMode === 'from_to' ? rateValue : 1 / rateValue;
            };
            const formatDisplayRate = (effectiveRate) => {
                if (effectiveRate <= 0) {
                    return '';
                }

                return rateMode === 'from_to' ? formatDecimal(effectiveRate, 8) : formatDecimal(1 / effectiveRate, 8);
            };
            const syncHint = () => {
                const from = currencyFrom.value || '—';
                const to = currencyTo.value || '—';
                const displayRate = parseDecimal(rate.value) || 1;

                if (rateMode === 'from_to') {
                    hint.textContent = `1 ${from} = ${formatDecimal(displayRate, 8)} ${to}`;
                    rateDirection.textContent = `${from} → ${to}`;
                    return;
                }

                hint.textContent = `1 ${to} = ${formatDecimal(displayRate, 8)} ${from}`;
                rateDirection.textContent = `${to} → ${from}`;
            };
            const recalculate = () => {
                const from = parseDecimal(amountFrom.value);
                const to = parseDecimal(amountTo.value);
                const rateValue = getEffectiveRate();
                const target = ['amount_from', 'amount_to', 'rate'].find((field) => !editedFields.slice(-2).includes(field)) || 'amount_to';

                if (target === 'amount_from' && to > 0 && rateValue > 0) {
                    amountFrom.value = formatDecimal(to / rateValue);
                    syncTerminalAmountState(amountFrom);
                } else if (target === 'amount_to' && from > 0 && rateValue > 0) {
                    amountTo.value = formatDecimal(from * rateValue);
                    syncTerminalAmountState(amountTo);
                } else if (target === 'rate' && from > 0 && to > 0) {
                    rate.value = formatDisplayRate(to / from);
                }

                syncHint();
            };
            const markEdited = (field) => {
                editedFields = editedFields.filter((item) => item !== field);
                editedFields.push(field);
            };
            const bindTerminalAmountInput = (input, field) => {
                if (!input || input.dataset.terminalInputBound === '1') {
                    return;
                }

                input.dataset.terminalInputBound = '1';

                const formatTerminalAmount = (cents) => (Math.max(0, cents) / 100).toFixed(2);
                const parseAmountToCents = (value) => Math.round(parseDecimal(value) * 100);
                const syncValue = (cents, recalc = true) => {
                    syncingTerminalAmount = true;
                    input.dataset.terminalAmountCents = String(Math.max(0, cents));
                    input.value = formatTerminalAmount(cents);
                    syncingTerminalAmount = false;

                    if (recalc) {
                        markEdited(field);
                        recalculate();
                    }
                };
                const getDigits = () => {
                    const cents = parseInt(input.dataset.terminalAmountCents || '0', 10) || 0;

                    return String(cents);
                };
                const appendDigit = (digit) => {
                    const currentDigits = input.dataset.terminalAmountFresh === '1' ? '' : getDigits();
                    const nextDigits = (currentDigits + digit).replace(/^0+(?=\d)/, '');

                    syncValue(parseInt(nextDigits || '0', 10));
                    input.dataset.terminalAmountFresh = '0';
                };
                const removeLastDigit = () => {
                    const nextDigits = getDigits().slice(0, -1);

                    syncValue(parseInt(nextDigits || '0', 10));
                    input.dataset.terminalAmountFresh = '0';
                };

                syncValue(parseAmountToCents(input.value), false);

                input.addEventListener('focus', () => {
                    input.dataset.terminalAmountFresh = '1';
                    syncValue(parseAmountToCents(input.value), false);
                    input.select();
                });
                input.addEventListener('beforeinput', (event) => {
                    if (event.inputType === 'insertText' && /^\d$/.test(event.data || '')) {
                        event.preventDefault();
                        appendDigit(event.data);
                        return;
                    }

                    if (event.inputType === 'deleteContentBackward') {
                        event.preventDefault();
                        removeLastDigit();
                        return;
                    }

                    if (event.inputType === 'deleteContentForward') {
                        event.preventDefault();
                        syncValue(0);
                        input.dataset.terminalAmountFresh = '0';
                    }
                });
                input.addEventListener('keydown', (event) => {
                    if (event.ctrlKey || event.metaKey || event.altKey) {
                        return;
                    }

                    if (/^\d$/.test(event.key)) {
                        event.preventDefault();
                        appendDigit(event.key);
                        return;
                    }

                    if (event.key === 'Backspace') {
                        event.preventDefault();
                        removeLastDigit();
                        return;
                    }

                    if (event.key === 'Delete') {
                        event.preventDefault();
                        syncValue(0);
                        input.dataset.terminalAmountFresh = '0';
                    }
                });
                input.addEventListener('paste', (event) => {
                    event.preventDefault();
                    const text = event.clipboardData?.getData('text') || '';
                    const digits = text.replace(/\D/g, '');

                    syncValue(parseInt(digits || '0', 10));
                    input.dataset.terminalAmountFresh = '0';
                });
                input.addEventListener('input', () => {
                    if (syncingTerminalAmount) {
                        return;
                    }

                    syncValue(parseAmountToCents(input.value));
                    input.dataset.terminalAmountFresh = '0';
                });
            };

            bindTerminalAmountInput(amountFrom, 'amount_from');
            bindTerminalAmountInput(amountTo, 'amount_to');
            rate.addEventListener('input', () => { markEdited('rate'); recalculate(); });
            rateDirection.addEventListener('click', () => {
                const effectiveRate = getEffectiveRate();
                rateMode = rateMode === 'from_to' ? 'to_from' : 'from_to';
                rate.value = formatDisplayRate(effectiveRate || 1);
                syncHint();
            });
            currencyFrom.addEventListener('change', syncHint);
            currencyTo.addEventListener('change', syncHint);
            form?.addEventListener('submit', () => {
                rate.value = formatDecimal(getEffectiveRate() || parseDecimal(rate.value) || 1, 8);
                rateMode = 'from_to';
            });
            syncHint();
        });
    </script>
    @else

    <h3 style="color:{{ $isPO ? 'green' : 'red' }};">
        {{ $isPO ? '📥 ' . __('money.heading_income') : '📤 ' . __('money.heading_outcome') }}
        @if(!$isNew) № {{ $document->num }} @endif
    </h3>
    @if($isDocumentOwner)
    <div class="text-muted mb-3">Ваш баланс: {{ $ownerBalanceLabel !== '' ? $ownerBalanceLabel : '0 UAH' }}</div>
    @elseif($authorName !== '')
    <div class="text-muted mb-3">Автор: {{ $authorName }}</div>
    @endif

    <form action="{{ route('money.save') }}" method="post" class="compact-form">
        @csrf
        <input type="hidden" name="id" value="{{ $document->id ?? 0 }}">
        <input type="hidden" name="type" value="{{ $type }}">
        <input type="hidden" name="tab" value="orders">
        <input type="hidden" name="return_q" value="{{ $returnFilters['q'] ?? '' }}">
        <input type="hidden" name="return_filter_type" value="{{ $returnFilters['type'] ?? '' }}">
        <input type="hidden" name="return_money" value="{{ $returnFilters['money'] ?? '' }}">
        <input type="hidden" name="return_reestr" value="{{ $returnFilters['reestr'] ?? '' }}">
        <input type="hidden" name="return_date_from" value="{{ $returnFilters['date_from'] ?? '' }}">
        <input type="hidden" name="return_date_to" value="{{ $returnFilters['date_to'] ?? '' }}">
        <input type="hidden" name="return_pos" value="{{ $returnFilters['pos'] ?? '' }}">

        <div class="row">
            <div class="col-md-4 mb-3">
                <label>{{ __('money.field_date') }}</label>
                <input type="date" name="data" class="form-control" value="{{ $documentDateValue }}" placeholder="{{ __('money.date_placeholder') }}">
            </div>
            <div class="col-md-4 mb-3">
                <label>{{ __('money.field_payment_type') }}</label>
                <select name="reestr" class="form-control">
                    <option value="">{{ __('money.select_payment_type') }}</option>
                    @foreach(($reestrList ?? []) as $re)
                    <option value="{{ $re->id }}" {{ (string) old('reestr', $document->reestr ?? '') === (string) $re->id ? 'selected' : '' }}>
                        {{ $re->name }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4 mb-3">
                <label>{{ __('money.field_sum') }}</label>
                <input type="text" name="summa" class="form-control" value="{{ $document->summa ?? 0 }}"
                    inputmode="numeric" autocomplete="off">
            </div>
        </div>

        <div class="mb-3">
            <label>{{ $isPO ? 'Баланс для зарахування' : 'Баланс для списання' }}</label>
            <select name="balance_currency" class="form-control" required>
                @foreach(($ownerBalances ?? []) as $balance)
                <option value="{{ $balance['currency'] }}" {{ (string) $selectedBalanceCurrency === (string) $balance['currency'] ? 'selected' : '' }}>
                    {{ $balance['currency'] }} — {{ $balance['amount'] }}
                </option>
                @endforeach
            </select>
            <small class="text-muted">Валюта операції буде застосована до балансу автора і клієнта.</small>
        </div>

        <div class="mb-3">
            <label>{{ __('money.field_client') }}</label>
            <div id="selectedClientDetails"
                class="alert {{ (!$isNew && !empty($document->id) && !empty($document->client1)) ? 'alert-secondary selected-client-details--filled' : 'alert-warning selected-client-details--empty' }} py-1 mt-1 selected-client-details"
                style="{{ (!$isNew && !empty($document->id) && !empty($document->client1)) ? 'border:1px solid var(--border);' : '' }}">
                @if(!$isNew && !empty($document->id) && !empty($document->client1))
                <strong>{{ $document->orgname ?? '' }}</strong> |
                {{ trim(($document->secondname ?? '') . ' ' . ($document->name ?? '') . ' ' . ($document->name2 ?? '')) }}<br>
                {{ $document->phone ?? '' }} | {{ $document->region ? $document->region . ' | ' : '' }}{{ $document->city ?? '' }}{{ $document->poshta ? ' | ' . $document->poshta : '' }}
                @else
                {{ __('money.client_not_selected') }}
                @endif
            </div>

            <div class="client-search-row d-flex gap-1 mb-2">
                <input type="text" id="clientSearchInput" class="form-control flex-grow-1" placeholder="{{ __('money.search_client') }}" autocomplete="off">
                <button type="button" class="btn btn-outline-secondary" id="editClientBtn" style="{{ !empty($document->client1) ? '' : 'display:none;' }}">
                    Изменить
                </button>
                <button type="button" class="btn btn-outline-primary" id="newClientBtn">
                    Новый
                </button>
            </div>
            <div id="clientSearchResults" class="list-group client-search-results mb-2" style="display:none;"></div>
            <input type="hidden" name="client1" id="client1_id"
                value="{{ $document->client1 ?? '' }}"
                data-orgname="{{ $document->orgname ?? '' }}"
                data-name="{{ $document->name ?? '' }}"
                data-secondname="{{ $document->secondname ?? '' }}"
                data-phone="{{ $document->phone ?? '' }}"
                data-city="{{ $document->city ?? '' }}"
                data-region="{{ $document->region ?? '' }}"
                data-poshta="{{ $document->poshta ?? '' }}"
                data-status="{{ $document->idstatus ?? '' }}">
        </div>

        <div class="mb-3">
            <label>{{ __('money.field_comment') }}</label>
            <input type="text" name="content" class="form-control" value="{{ $document->content ?? '' }}">
        </div>

        @if((int)($document->provodka ?? 0) === 0)
        <div class="mb-3 form-check">
            <input type="hidden" name="post_after_save" value="0">
            <input type="checkbox" class="form-check-input" id="post_after_save" name="post_after_save" value="1" checked>
            <label class="form-check-label" for="post_after_save">{{ __('money.checkbox_post') }}</label>
        </div>
        @endif

        <div class="d-flex gap-2 align-items-center flex-wrap">
            <a href="{{ $backUrl }}" class="btn btn-outline-secondary">← {{ __('money.btn_back') }}</a>
            @if((int)($document->provodka ?? 0) === 1)
            <button type="submit" formaction="{{ route('money.provodka') }}" formmethod="post" class="btn btn-success">
                ↺ {{ __('money.btn_unpost') }}
            </button>
            @else
            <button type="submit" class="btn">💾 {{ __('money.btn_save') }}</button>
            @endif
            @if((int)($document->provodka ?? 0) === 0 && !$isNew)
            <button type="button" class="btn btn-danger" onclick="if(confirm('{{ __('money.confirm_delete') }}')) { document.getElementById('deleteMoneyForm').submit(); }">
                🗑 {{ __('money.btn_delete') }}
            </button>
            @endif
        </div>
    </form>

    <div class="modal fade" id="newClientModal" tabindex="-1" aria-labelledby="newClientModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable modal-fullscreen-sm-down">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="newClientModalLabel">Новый клиент</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрити"></button>
                </div>
                <div class="modal-body py-2">
                    <input type="hidden" id="newClientId" value="0">
                    <div class="row g-2">
                        <div class="col-12">
                            <label class="form-label small mb-0">Организация</label>
                            <input type="text" class="form-control form-control-sm" id="newClientOrgname">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label small mb-0">Фамилия</label>
                            <input type="text" class="form-control form-control-sm" id="newClientSecondname">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label small mb-0">Имя</label>
                            <input type="text" class="form-control form-control-sm" id="newClientName">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label small mb-0">Телефон</label>
                            <input type="text" class="form-control form-control-sm" id="newClientPhone" placeholder="+38 (000) 00-00-000" maxlength="19" inputmode="tel">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label small mb-0">Город</label>
                            <input type="text" class="form-control form-control-sm" id="newClientCity">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label small mb-0">Область</label>
                            <input type="text" class="form-control form-control-sm" id="newClientRegion">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label small mb-0">Отделение НП</label>
                            <input type="text" class="form-control form-control-sm" id="newClientPoshta">
                        </div>
                        <div class="col-12">
                            <label class="form-label small mb-0">Статус клиента</label>
                            <select class="form-select form-select-sm" id="newClientStatus">
                                <option value="">Оберіть статус</option>
                                @foreach(($clientStatuses ?? collect()) as $statusOption)
                                <option value="{{ $statusOption->id }}">{{ $statusOption->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div id="newClientError" class="text-danger small mt-2" style="display: none;"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="button" class="btn btn-primary" id="saveNewClientBtn">Сохранить</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const searchInput = document.getElementById('clientSearchInput');
            const editClientBtn = document.getElementById('editClientBtn');
            const newClientBtn = document.getElementById('newClientBtn');
            const resultsContainer = document.getElementById('clientSearchResults');
            const client1Id = document.getElementById('client1_id');
            const clientDetails = document.getElementById('selectedClientDetails');

            function escapeHtml(value) {
                const div = document.createElement('div');
                div.textContent = value || '';
                return div.innerHTML;
            }

            function performSearch() {
                const q = searchInput.value.trim();
                if (q.length < 2) { resultsContainer.style.display = 'none'; return; }

                fetch("{{ route('client.search') }}?q=" + encodeURIComponent(q))
                    .then(res => res.json())
                    .then(data => {
                        resultsContainer.innerHTML = '';
                        if (!data.length) {
                            resultsContainer.innerHTML = '<div class="list-group-item text-muted">{{ addslashes(__('money.search_no_results')) }}</div>';
                        } else {
                            data.forEach(user => {
                                const a = document.createElement('a');
                                a.href = '#';
                                a.className = 'list-group-item list-group-item-action';
                                a.innerHTML = `
                                    <strong>${escapeHtml(user.orgname || '')}</strong> |
                                    ${escapeHtml(user.name2 || '')} ${escapeHtml(user.name || '')} ${escapeHtml(user.secondname || '')}
                                    <br>
                                    <small>${escapeHtml(user.phone || '')} | ${user.region ? escapeHtml(user.region) + ' | ' : ''}${escapeHtml(user.city || '')}${user.poshta ? ' | ' + escapeHtml(user.poshta) : ''}</small>
                                `;
                                a.addEventListener('click', function (e) {
                                    e.preventDefault();
                                    const selectedLabel = [user.orgname || '', user.secondname || '', user.name || ''].filter(Boolean).join(' ').trim();
                                    client1Id.value = user.id;
                                    client1Id.dataset.orgname = user.orgname || '';
                                    client1Id.dataset.name = user.name || '';
                                    client1Id.dataset.secondname = user.secondname || '';
                                    client1Id.dataset.phone = user.phone || '';
                                    client1Id.dataset.city = user.city || '';
                                    client1Id.dataset.region = user.region || '';
                                    client1Id.dataset.poshta = user.poshta || '';
                                    client1Id.dataset.status = user.idstatus || '';
                                    if (editClientBtn) {
                                        editClientBtn.style.display = 'inline-block';
                                    }
                                    clientDetails.className = 'alert alert-secondary py-1 mt-1 selected-client-details selected-client-details--filled';
                                    clientDetails.style.border = '1px solid var(--border)';
                                    clientDetails.innerHTML = a.innerHTML;
                                    resultsContainer.style.display = 'none';
                                    searchInput.value = selectedLabel;
                                });
                                resultsContainer.appendChild(a);
                            });
                        }
                        resultsContainer.style.display = 'block';
                    });
            }

            let t = null;
            searchInput.addEventListener('input', () => { clearTimeout(t); t = setTimeout(performSearch, 400); });
            searchInput.addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); performSearch(); } });
            document.addEventListener('click', e => {
                if (!searchInput.contains(e.target) && !resultsContainer.contains(e.target)) {
                    resultsContainer.style.display = 'none';
                }
            });

            const newClientIdField = document.getElementById('newClientId');
            const newClientOrgnameField = document.getElementById('newClientOrgname');
            const newClientNameField = document.getElementById('newClientName');
            const newClientSecondnameField = document.getElementById('newClientSecondname');
            const newClientPhoneField = document.getElementById('newClientPhone');
            const newClientCityField = document.getElementById('newClientCity');
            const newClientRegionField = document.getElementById('newClientRegion');
            const newClientPoshtaField = document.getElementById('newClientPoshta');
            const newClientStatusField = document.getElementById('newClientStatus');
            const newClientError = document.getElementById('newClientError');
            const saveNewClientBtn = document.getElementById('saveNewClientBtn');
            const newClientModalElement = document.getElementById('newClientModal');
            if (newClientModalElement && newClientModalElement.parentElement !== document.body) {
                document.body.appendChild(newClientModalElement);
            }
            const newClientModal = (typeof bootstrap !== 'undefined' && newClientModalElement)
                ? new bootstrap.Modal(newClientModalElement, {
                    backdrop: true,
                    keyboard: true,
                    focus: true
                })
                : null;

            const formatPhoneInput = (value) => {
                const digits = String(value || '').replace(/\D/g, '').slice(0, 12);
                if (digits.length === 0) {
                    return '';
                }
                if (digits.length <= 3) {
                    return `+${digits}`;
                }
                if (digits.length <= 5) {
                    return `+${digits.slice(0, 3)} (${digits.slice(3)}`;
                }
                if (digits.length <= 8) {
                    return `+${digits.slice(0, 3)} (${digits.slice(3, 5)}) ${digits.slice(5)}`;
                }
                if (digits.length <= 10) {
                    return `+${digits.slice(0, 3)} (${digits.slice(3, 5)}) ${digits.slice(5, 8)}-${digits.slice(8)}`;
                }
                return `+${digits.slice(0, 3)} (${digits.slice(3, 5)}) ${digits.slice(5, 8)}-${digits.slice(8, 10)}-${digits.slice(10)}`;
            };

            const resetClientModal = () => {
                newClientIdField.value = '0';
                newClientOrgnameField.value = '';
                newClientNameField.value = '';
                newClientSecondnameField.value = '';
                newClientPhoneField.value = '';
                newClientCityField.value = '';
                newClientRegionField.value = '';
                newClientPoshtaField.value = '';
                newClientStatusField.value = '';
                newClientError.style.display = 'none';
            };

            if (newClientBtn) {
                newClientBtn.addEventListener('click', () => {
                    resultsContainer.style.display = 'none';
                    document.getElementById('newClientModalLabel').textContent = 'Новый клиент';
                    resetClientModal();
                    if (newClientModal) {
                        newClientModal.show();
                    }
                });
            }

            if (editClientBtn) {
                editClientBtn.addEventListener('click', () => {
                    resultsContainer.style.display = 'none';
                    document.getElementById('newClientModalLabel').textContent = 'Изменить клиента';
                    newClientIdField.value = client1Id.value || '0';
                    newClientOrgnameField.value = client1Id.dataset.orgname || '';
                    newClientNameField.value = client1Id.dataset.name || '';
                    newClientSecondnameField.value = client1Id.dataset.secondname || '';
                    newClientPhoneField.value = client1Id.dataset.phone || '';
                    newClientCityField.value = client1Id.dataset.city || '';
                    newClientRegionField.value = client1Id.dataset.region || '';
                    newClientPoshtaField.value = client1Id.dataset.poshta || '';
                    newClientStatusField.value = client1Id.dataset.status || '';
                    newClientError.style.display = 'none';
                    newClientPhoneField.dispatchEvent(new Event('input'));
                    if (newClientModal) {
                        newClientModal.show();
                    }
                });
            }

            if (newClientPhoneField) {
                newClientPhoneField.addEventListener('input', function () {
                    this.value = formatPhoneInput(this.value);
                });
            }

            if (saveNewClientBtn) {
                saveNewClientBtn.addEventListener('click', function () {
                    const id = newClientIdField.value || '0';
                    const orgname = newClientOrgnameField.value.trim();
                    const name = newClientNameField.value.trim();
                    const secondname = newClientSecondnameField.value.trim();
                    const phone = newClientPhoneField.value.trim();
                    const city = newClientCityField.value.trim();
                    const region = newClientRegionField.value.trim();
                    const poshta = newClientPoshtaField.value.trim();
                    const idstatus = newClientStatusField.value;

                    [newClientNameField, newClientSecondnameField, newClientPhoneField, newClientStatusField].forEach((field) => field.classList.remove('is-invalid'));

                    if (!name && !secondname && !phone) {
                        newClientNameField.classList.add('is-invalid');
                        newClientSecondnameField.classList.add('is-invalid');
                        newClientPhoneField.classList.add('is-invalid');
                        newClientError.textContent = 'Заполните хотя бы одно поле: имя, фамилию или телефон';
                        newClientError.style.display = 'block';
                        return;
                    }

                    if (!idstatus) {
                        newClientStatusField.classList.add('is-invalid');
                        newClientError.textContent = 'Выберите статус клиента';
                        newClientError.style.display = 'block';
                        return;
                    }

                    newClientError.style.display = 'none';
                    saveNewClientBtn.disabled = true;
                    saveNewClientBtn.textContent = 'Сохраняем...';

                    fetch("{{ route('client.quickStore') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({ id, orgname, name, secondname, phone, city, region, poshta, idstatus })
                    })
                        .then(async (res) => {
                            const payload = await res.json().catch(() => ({}));
                            if (!res.ok) {
                                throw new Error(payload.message || 'Не удалось сохранить клиента');
                            }
                            return payload;
                        })
                        .then((user) => {
                            const selectedLabel = [user.orgname || '', user.secondname || '', user.name || ''].filter(Boolean).join(' ').trim();
                            client1Id.value = user.id;
                            client1Id.dataset.orgname = user.orgname || '';
                            client1Id.dataset.name = user.name || '';
                            client1Id.dataset.secondname = user.secondname || '';
                            client1Id.dataset.phone = user.phone || '';
                            client1Id.dataset.city = user.city || '';
                            client1Id.dataset.region = user.region || '';
                            client1Id.dataset.poshta = user.poshta || '';
                            client1Id.dataset.status = user.idstatus || '';

                            if (editClientBtn) {
                                editClientBtn.style.display = 'inline-block';
                            }

                            clientDetails.className = 'alert alert-secondary py-1 mt-1 selected-client-details selected-client-details--filled';
                            clientDetails.style.border = '1px solid var(--border)';
                            clientDetails.innerHTML = `
                                <strong>${escapeHtml(user.orgname || '')}</strong> |
                                ${escapeHtml(user.name2 || '')} ${escapeHtml(user.name || '')} ${escapeHtml(user.secondname || '')}
                                <br>
                                <small>${escapeHtml(user.phone || '')} | ${user.region ? escapeHtml(user.region) + ' | ' : ''}${escapeHtml(user.city || '')}${user.poshta ? ' | ' + escapeHtml(user.poshta) : ''}</small>
                            `;
                            searchInput.value = selectedLabel;

                            if (newClientModal) {
                                newClientModal.hide();
                            }
                        })
                        .catch((error) => {
                            newClientError.textContent = error.message;
                            newClientError.style.display = 'block';
                        })
                        .finally(() => {
                            saveNewClientBtn.disabled = false;
                            saveNewClientBtn.textContent = 'Сохранить';
                        });
                });
            }
        });
    </script>
    @endif

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const formatTerminalAmount = (cents) => (Math.max(0, cents) / 100).toFixed(2);
            const parseAmountToCents = (value) => {
                const normalized = String(value || '').replace(/\s/g, '').replace(',', '.');
                const amount = parseFloat(normalized);

                return Number.isFinite(amount) ? Math.round(amount * 100) : 0;
            };
            const bindTerminalAmountInput = (input) => {
                if (!input || input.dataset.terminalInputBound === '1') {
                    return;
                }

                input.dataset.terminalInputBound = '1';

                const syncValue = (cents) => {
                    input.dataset.terminalAmountCents = String(cents);
                    input.value = formatTerminalAmount(cents);
                };
                const getDigits = () => {
                    const cents = parseInt(input.dataset.terminalAmountCents || '0', 10) || 0;

                    return String(cents);
                };
                const appendDigit = (digit) => {
                    const currentDigits = input.dataset.terminalAmountFresh === '1' ? '' : getDigits();
                    const nextDigits = (currentDigits + digit).replace(/^0+(?=\d)/, '');

                    syncValue(parseInt(nextDigits || '0', 10));
                    input.dataset.terminalAmountFresh = '0';
                };
                const removeLastDigit = () => {
                    const nextDigits = getDigits().slice(0, -1);

                    syncValue(parseInt(nextDigits || '0', 10));
                    input.dataset.terminalAmountFresh = '0';
                };

                syncValue(parseAmountToCents(input.value));

                input.addEventListener('focus', () => {
                    input.dataset.terminalAmountFresh = '1';
                    syncValue(parseAmountToCents(input.value));
                    input.select();
                });
                input.addEventListener('beforeinput', (event) => {
                    if (event.inputType === 'insertText' && /^\d$/.test(event.data || '')) {
                        event.preventDefault();
                        appendDigit(event.data);
                        return;
                    }

                    if (event.inputType === 'deleteContentBackward') {
                        event.preventDefault();
                        removeLastDigit();
                        return;
                    }

                    if (event.inputType === 'deleteContentForward') {
                        event.preventDefault();
                        syncValue(0);
                        input.dataset.terminalAmountFresh = '0';
                    }
                });
                input.addEventListener('keydown', (event) => {
                    if (event.ctrlKey || event.metaKey || event.altKey) {
                        return;
                    }

                    if (/^\d$/.test(event.key)) {
                        event.preventDefault();
                        appendDigit(event.key);
                        return;
                    }

                    if (event.key === 'Backspace') {
                        event.preventDefault();
                        removeLastDigit();
                        return;
                    }

                    if (event.key === 'Delete') {
                        event.preventDefault();
                        syncValue(0);
                        input.dataset.terminalAmountFresh = '0';
                    }
                });
                input.addEventListener('paste', (event) => {
                    event.preventDefault();
                    const text = event.clipboardData?.getData('text') || '';
                    const digits = text.replace(/\D/g, '');

                    syncValue(parseInt(digits || '0', 10));
                    input.dataset.terminalAmountFresh = '0';
                });
                input.addEventListener('input', () => {
                    syncValue(parseAmountToCents(input.value));
                    input.dataset.terminalAmountFresh = '0';
                });
            };

            document.querySelectorAll('input[name="summa"]:not([data-decimal-input="1"]):not([data-terminal-input="1"])').forEach(bindTerminalAmountInput);
        });
    </script>
    @endif

    @if(!$isNew)
    <form id="deleteMoneyForm" action="{{ route('money.destroy') }}" method="post" style="display:none;">
        @csrf
        <input type="hidden" name="id" value="{{ $document->id }}">
        <input type="hidden" name="tab" value="{{ $activeTab }}">
        <input type="hidden" name="return_q" value="{{ $returnFilters['q'] ?? '' }}">
        <input type="hidden" name="return_filter_type" value="{{ $returnFilters['type'] ?? '' }}">
        <input type="hidden" name="return_money" value="{{ $returnFilters['money'] ?? '' }}">
        <input type="hidden" name="return_reestr" value="{{ $returnFilters['reestr'] ?? '' }}">
        <input type="hidden" name="return_date_from" value="{{ $returnFilters['date_from'] ?? '' }}">
        <input type="hidden" name="return_date_to" value="{{ $returnFilters['date_to'] ?? '' }}">
        <input type="hidden" name="return_pos" value="{{ $returnFilters['pos'] ?? '' }}">
    </form>
    @endif
</div>
@endsection
