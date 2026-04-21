@extends('home')

@section('title', $document->id ? (__('deposit.deposit_no') . $document->num) : __('deposit.deposit_operation'))

@section('content')
@include('deposit.partials.top-actions')

<div class="ttable deposit-show-page" style="padding: 20px; max-width: 760px; margin: 0 auto; border-radius: 8px;">
    @php
    $isNew = empty($document->id);
    $mode = $document->docum ?? request('mode', 'topup');
    $mode = in_array($mode, ['topup', 'withdraw', 'exchange'], true) ? $mode : 'topup';
    $heading = match ($mode) {
        'withdraw' => __('deposit.op_withdraw'),
        'exchange' => __('deposit.op_exchange'),
        default => __('deposit.op_topup'),
    };
    $topLabel = match ($mode) {
        'withdraw' => __('deposit.top_account_deposit'),
        default => __('deposit.top_account_cash'),
    };
    $bottomLabel = match ($mode) {
        'withdraw' => __('deposit.bottom_account_cash'),
        'exchange' => __('deposit.bottom_account_cash'),
        default => __('deposit.bottom_account_deposit'),
    };
    @endphp

    <h3 style="color:#b45309;">🏦 {{ $heading }} @if(!$isNew) № {{ $document->num }} @endif</h3>

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
                <label>{{ __('deposit.field_sum') }}</label>
                <input type="number" step="0.01" min="0" name="summa" class="form-control" value="{{ old('summa', $document->summa ?? 0) }}">
            </div>
            <div class="col-md-4 mb-3">
                <label>{{ __('deposit.field_status') }}</label>
                <input type="text" class="form-control" value="{{ (int)($document->provodka ?? 0) === 1 ? __('deposit.status_posted') : __('deposit.status_draft') }}" disabled>
            </div>
        </div>

        <div class="glass-card" style="margin-bottom:12px; border:1px solid rgba(180, 83, 9, 0.15);">
            <div style="font-size:0.82rem; text-transform:uppercase; letter-spacing:0.08em; color:#92400e; margin-bottom:8px;">{{ $topLabel }}</div>
            @if($mode === 'withdraw')
            <select name="money" class="form-control" required>
                <option value="">{{ __('deposit.select_deposit') }}</option>
                @foreach($deposits as $deposit)
                <option value="{{ $deposit->id }}" {{ (string) old('money', $document->money ?? '') === (string) $deposit->id ? 'selected' : '' }}>
                    {{ $deposit->name }} @if(isset($deposit->value)) | {{ number_format((float) $deposit->value, 2, '.', ' ') }} @endif
                </option>
                @endforeach
            </select>
            @else
            <select name="oplata" class="form-control" required>
                <option value="">{{ __('deposit.select_cash') }}</option>
                @foreach($oplatas as $oplata)
                <option value="{{ $oplata->id }}" {{ (string) old('oplata', $document->oplata ?? '') === (string) $oplata->id ? 'selected' : '' }}>
                    {{ $oplata->name }} @if(isset($oplata->value)) | {{ number_format((float) $oplata->value, 2, '.', ' ') }} @endif
                </option>
                @endforeach
            </select>
            @endif
        </div>

        <div style="text-align:center; font-size:1.6rem; color:#b45309; margin:6px 0 12px;">↓</div>

        <div class="glass-card" style="margin-bottom:16px; border:1px solid rgba(180, 83, 9, 0.15);">
            <div style="font-size:0.82rem; text-transform:uppercase; letter-spacing:0.08em; color:#92400e; margin-bottom:8px;">{{ $bottomLabel }}</div>
            @if($mode === 'topup')
            <select name="money" class="form-control" required>
                <option value="">{{ __('deposit.select_deposit') }}</option>
                @foreach($deposits as $deposit)
                <option value="{{ $deposit->id }}" {{ (string) old('money', $document->money ?? '') === (string) $deposit->id ? 'selected' : '' }}>
                    {{ $deposit->name }} @if(isset($deposit->value)) | {{ number_format((float) $deposit->value, 2, '.', ' ') }} @endif
                </option>
                @endforeach
            </select>
            @else
            <select name="oplata2" class="form-control" required>
                <option value="">{{ __('deposit.select_cash') }}</option>
                @foreach($oplatas as $oplata)
                <option value="{{ $oplata->id }}" {{ (string) old('oplata2', $document->oplata2 ?? '') === (string) $oplata->id ? 'selected' : '' }}>
                    {{ $oplata->name }} @if(isset($oplata->value)) | {{ number_format((float) $oplata->value, 2, '.', ' ') }} @endif
                </option>
                @endforeach
            </select>
            @endif
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

    @if(!$isNew)
    <form id="deleteDepositForm" action="{{ route('deposit.destroy') }}" method="post" style="display:none;">
        @csrf
        <input type="hidden" name="id" value="{{ $document->id }}">
    </form>
    @endif
</div>
@endsection
