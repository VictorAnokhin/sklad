@extends('home')

@section('title', $document->id ? (__('deposit.deposit_no') . $document->num) : __('deposit.deposit_operation'))

@section('content')
@include('deposit.partials.top-actions')

<div class="ttable deposit-show-page" style="padding: 20px; max-width: 760px; margin: 0 auto; border-radius: 8px;">
    @php
    $isNew = empty($document->id);
    $mode = $document->docum ?? request('mode', 'topup');
    $mode = in_array($mode, ['topup', 'withdraw'], true) ? $mode : 'topup';
    $target = $target ?? request('target', 'deposit');
    $heading = match ($mode) {
        'withdraw' => __('deposit.op_withdraw'),
        default => __('deposit.op_topup'),
    };
    $selectedBalanceCurrency = old('balance_currency', $document->currency_from ?? (($ownerBalances[0]['currency'] ?? 'UAH')));
    $ownerBalanceLabel = collect($ownerBalances ?? [])->map(function ($balance) {
        return ($balance['amount'] ?? '0') . ' ' . ($balance['currency'] ?? 'UAH');
    })->implode(' | ');
    $authorName = trim(implode(' ', array_filter([
        $document->owner_orgname ?? '',
        $document->owner_secondname ?? '',
        $document->owner_name ?? '',
        $document->owner_fathername ?? '',
    ])));
    $currentUserId = (string) (\Illuminate\Support\Facades\Auth::id() ?: session('userid', '0'));
    $isDocumentOwner = (string) ($document->client2 ?? '') !== '' && (string) ($document->client2 ?? '') === $currentUserId;
    @endphp

    <h3 style="color:#b45309;">🏦 {{ $heading }} @if(!$isNew) № {{ $document->num }} @endif</h3>
    @if($isDocumentOwner)
    <div class="text-muted mb-3">Ваш баланс: {{ $ownerBalanceLabel !== '' ? $ownerBalanceLabel : '0 UAH' }}</div>
    @elseif($authorName !== '')
    <div class="text-muted mb-3">Автор: {{ $authorName }}</div>
    @endif

    @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <form action="{{ route('deposit.save') }}" method="post">
        @csrf
        @php
                $documentDateValue = (string) ($document->data ?? '');
                if (preg_match('/^\d{2}-\d{2}-\d{4}$/', $documentDateValue) === 1) {
                    $documentDateValue = \DateTimeImmutable::createFromFormat('d-m-Y', $documentDateValue)?->format('Y-m-d') ?? '';
                }
        @endphp
        <input type="hidden" name="id" value="{{ $document->id ?? 0 }}">
        <input type="hidden" name="mode" value="{{ $mode }}">
        <input type="hidden" name="target" value="{{ $target }}">

        <div class="row">
            <div class="col-md-4 mb-3">
                <label>{{ __('deposit.field_date') }}</label>
                <input type="date" name="data" class="form-control" value="{{ $documentDateValue }}" placeholder="{{ __('deposit.date_placeholder') }}">
            </div>
            <div class="col-md-4 mb-3">
                <label>{{ __('deposit.field_status') }}</label>
                <input type="text" class="form-control" value="{{ (int)($document->provodka ?? 0) === 1 ? __('deposit.status_posted') : __('deposit.status_draft') }}" disabled>
            </div>
        </div>

        <div class="mb-3">
            <label>{{ $mode === 'topup' ? 'Баланс для списання' : 'Баланс для зарахування' }}</label>
            <select name="balance_currency" id="depositBalanceCurrency" class="form-control" required>
                @foreach(($ownerBalances ?? []) as $balance)
                <option value="{{ $balance['currency'] }}" {{ (string) $selectedBalanceCurrency === (string) $balance['currency'] ? 'selected' : '' }}>
                    {{ $balance['currency'] }} — {{ $balance['amount'] }}
                </option>
                @endforeach
            </select>
            <small class="text-muted">Валюта балансу має збігатися з валютою вибраного депозиту.</small>
        </div>

        <div class="mb-3">
            <label>{{ $target === 'pool' ? 'Выбери пул' : 'Выбери депозит' }}</label>
            <select name="money" id="depositMoneySelect" class="form-control" required>
                <option value="" data-currency="">-- выберите {{ $target === 'pool' ? 'пул' : 'депозит' }} --</option>
                @if(($deposits ?? collect())->isNotEmpty())
                <optgroup label="Депозиты">
                @foreach($deposits as $deposit)
                <option value="{{ $deposit->id }}" data-currency="{{ $deposit->currency ?? 'UAH' }}" {{ (string) old('money', $document->money ?? '') === (string) $deposit->id ? 'selected' : '' }}>
                    {{ $deposit->name }} @if(($deposit->deposit_type ?? '') === 'bank') · банк @endif @if(isset($deposit->value)) | {{ number_format((float) $deposit->value, 2, '.', ' ') }} {{ $deposit->currency ?? 'UAH' }} @endif
                </option>
                @endforeach
                </optgroup>
                @endif
                @if(($depositPools ?? collect())->isNotEmpty())
                <optgroup label="Пулы">
                @foreach($depositPools as $pool)
                <option value="{{ $pool->asset_key }}"
                    data-currency="{{ $pool->currency }}"
                    data-balance="{{ number_format((float) ($pool->balance ?? 0), 2, '.', '') }}"
                    data-apy-bps="{{ (int) ($pool->apy_bps ?? 0) }}"
                    data-description="{{ $pool->description ?? '' }}"
                    {{ (string) old('money', $document->money ?? '') === (string) $pool->asset_key ? 'selected' : '' }}>
                    {{ $pool->name }} | {{ $pool->currency }} @if($pool->is_default_deposit) · default @endif @if(!$pool->active) · inactive @endif
                </option>
                @endforeach
                </optgroup>
                @endif
            </select>
            <small class="text-muted" id="depositCurrencyHint"></small>
        </div>

        <div class="mb-3">
            <label>{{ __('deposit.field_sum') }}</label>
            <input type="text" name="summa" class="form-control" value="{{ old('summa', $document->summa ?? 0) }}"
                inputmode="numeric" autocomplete="off">
        </div>

        <div class="mb-3">
            <label>{{ __('deposit.comment') }}</label>
            <input type="text" name="content" class="form-control" value="{{ old('content', $document->content ?? '') }}">
        </div>

        @if((int)($document->provodka ?? 0) === 0)
        <div class="mb-3 form-check">
            <input type="checkbox" class="form-check-input" id="post_after_save" name="post_after_save" value="1" checked>
            <label class="form-check-label" for="post_after_save">{{ __('deposit.post_after_save') }}</label>
        </div>
        @endif

        <div style="display:flex; gap:10px; flex-wrap:wrap;">
            <a href="{{ route('deposit.index') }}" class="btn btn-outline-secondary">{{ __('deposit.btn_back') }}</a>
            @if((int)($document->provodka ?? 0) === 0)
            <button type="submit" class="btn">{{ __('deposit.btn_save') }}</button>
            @endif
            @if(!$isNew && (int)($document->provodka ?? 0) === 1)
            <button type="submit" formaction="{{ route('deposit.provodka') }}" formmethod="post" class="btn btn-success">
                {{ __('deposit.btn_cancel_posting') }}
            </button>
            @endif
            @if(!$isNew && (int)($document->provodka ?? 0) === 0)
            <button type="button" class="btn btn-danger" onclick="if(confirm('{{ __('deposit.confirm_delete') }}')) { document.getElementById('deleteDepositForm').submit(); }">
                {{ __('deposit.btn_delete') }}
            </button>
            @endif
        </div>
    </form>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const depositSelect = document.getElementById('depositMoneySelect');
            const balanceSelect = document.getElementById('depositBalanceCurrency');
            const hint = document.getElementById('depositCurrencyHint');
            const amountInput = document.querySelector('input[name="summa"]');

            const formatTerminalAmount = (cents) => (Math.max(0, cents) / 100).toFixed(2);
            const parseAmountToCents = (value) => {
                const normalized = String(value || '').replace(/\s/g, '').replace(',', '.');
                const amount = parseFloat(normalized);

                return Number.isFinite(amount) ? Math.round(amount * 100) : 0;
            };
            const bindTerminalAmountInput = (input) => {
                if (!input) {
                    return;
                }

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

            bindTerminalAmountInput(amountInput);

            if (!depositSelect || !balanceSelect) {
                return;
            }

            const filterDeposits = () => {
                const selectedCurrency = balanceSelect.value || '';
                let selectedStillVisible = false;
                let firstVisibleValue = '';

                Array.from(depositSelect.options).forEach((option) => {
                    if (option.value === '') {
                        option.hidden = false;
                        return;
                    }

                    const isVisible = option.dataset.currency === selectedCurrency;
                    option.hidden = !isVisible;

                    if (isVisible && firstVisibleValue === '') {
                        firstVisibleValue = option.value;
                    }
                    if (isVisible && option.selected) {
                        selectedStillVisible = true;
                    }
                });

                if (!selectedStillVisible) {
                    depositSelect.value = firstVisibleValue;
                }

                if (hint) {
                    hint.textContent = selectedCurrency
                        ? `Показаны пулы в валюте ${selectedCurrency}`
                        : '';
                }
            };

            balanceSelect.addEventListener('change', filterDeposits);
            filterDeposits();
        });
    </script>

    @if(!$isNew)
    <form id="deleteDepositForm" action="{{ route('deposit.destroy') }}" method="post" style="display:none;">
        @csrf
        <input type="hidden" name="id" value="{{ $document->id }}">
    </form>
    @endif
</div>
@endsection
