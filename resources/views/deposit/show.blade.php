@extends('home')

@section('title', $document->id ? (__('deposit.deposit_no') . $document->num) : __('deposit.deposit_operation'))

@section('content')
@include('deposit.partials.top-actions')

<div class="ttable deposit-show-page" style="padding: 20px; max-width: 760px; margin: 0 auto; border-radius: 8px;">
    @php
    $isNew = empty($document->id);
    $mode = $document->docum ?? request('mode', 'topup');
    $mode = in_array($mode, ['topup', 'withdraw'], true) ? $mode : 'topup';
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
            <label>Выбери депозит</label>
            <select name="money" id="depositMoneySelect" class="form-control" required>
                <option value="" data-currency="">{{ __('deposit.select_deposit') }}</option>
                @foreach($deposits as $deposit)
                <option value="{{ $deposit->id }}" data-currency="{{ $deposit->currency ?? 'UAH' }}" {{ (string) old('money', $document->money ?? '') === (string) $deposit->id ? 'selected' : '' }}>
                    {{ $deposit->name }} @if(isset($deposit->value)) | {{ number_format((float) $deposit->value, 2, '.', ' ') }} {{ $deposit->currency ?? 'UAH' }} @endif
                </option>
                @endforeach
            </select>
            <small class="text-muted" id="depositCurrencyHint"></small>
        </div>

        <div class="mb-3">
            <label>{{ __('deposit.field_sum') }}</label>
            <input type="number" step="0.01" min="0" name="summa" class="form-control" value="{{ old('summa', $document->summa ?? 0) }}">
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
                        ? `Показаны депозиты в валюте ${selectedCurrency}`
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
