@extends('home')

@section('title', $document->id ? ('Депозит №' . $document->num) : 'Операция по депозиту')

@section('content')
<div class="ttable" style="padding: 20px; max-width: 760px; margin: 0 auto; background: #fff; border-radius: 8px;">
    @php
    $isNew = empty($document->id);
    $mode = $document->docum ?? request('mode', 'topup');
    $mode = in_array($mode, ['topup', 'withdraw', 'exchange'], true) ? $mode : 'topup';
    $heading = match ($mode) {
        'withdraw' => 'Снять с депозита',
        'exchange' => 'Обмен между кассами',
        default => 'Пополнить депозит',
    };
    $topLabel = match ($mode) {
        'withdraw' => 'Верхний счет: депозит',
        default => 'Верхний счет: касса',
    };
    $bottomLabel = match ($mode) {
        'withdraw' => 'Нижний счет: касса',
        'exchange' => 'Нижний счет: касса',
        default => 'Нижний счет: депозит',
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
        <input type="hidden" name="id" value="{{ $document->id ?? 0 }}">
        <input type="hidden" name="mode" value="{{ $mode }}">

        <div class="row">
            <div class="col-md-4 mb-3">
                <label>Дата</label>
                <input type="text" name="data" class="form-control" value="{{ old('data', $document->data ?? date('d-m-Y')) }}" placeholder="дд-мм-рррр">
            </div>
            <div class="col-md-4 mb-3">
                <label>Сума</label>
                <input type="number" step="0.01" min="0" name="summa" class="form-control" value="{{ old('summa', $document->summa ?? 0) }}">
            </div>
            <div class="col-md-4 mb-3">
                <label>Статус</label>
                <input type="text" class="form-control" value="{{ (int)($document->provodka ?? 0) === 1 ? 'Проведено' : 'Чернетка' }}" disabled>
            </div>
        </div>

        <div class="glass-card" style="margin-bottom:12px; border:1px solid rgba(180, 83, 9, 0.15);">
            <div style="font-size:0.82rem; text-transform:uppercase; letter-spacing:0.08em; color:#92400e; margin-bottom:8px;">{{ $topLabel }}</div>
            @if($mode === 'withdraw')
            <select name="money" class="form-control" required>
                <option value="">— оберіть депозит —</option>
                @foreach($deposits as $deposit)
                <option value="{{ $deposit->id }}" {{ (string) old('money', $document->money ?? '') === (string) $deposit->id ? 'selected' : '' }}>
                    {{ $deposit->name }} @if(isset($deposit->value)) | {{ number_format((float) $deposit->value, 2, '.', ' ') }} @endif
                </option>
                @endforeach
            </select>
            @else
            <select name="oplata" class="form-control" required>
                <option value="">— оберіть касу —</option>
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
                <option value="">— оберіть депозит —</option>
                @foreach($deposits as $deposit)
                <option value="{{ $deposit->id }}" {{ (string) old('money', $document->money ?? '') === (string) $deposit->id ? 'selected' : '' }}>
                    {{ $deposit->name }} @if(isset($deposit->value)) | {{ number_format((float) $deposit->value, 2, '.', ' ') }} @endif
                </option>
                @endforeach
            </select>
            @else
            <select name="oplata2" class="form-control" required>
                <option value="">— оберіть касу —</option>
                @foreach($oplatas as $oplata)
                <option value="{{ $oplata->id }}" {{ (string) old('oplata2', $document->oplata2 ?? '') === (string) $oplata->id ? 'selected' : '' }}>
                    {{ $oplata->name }} @if(isset($oplata->value)) | {{ number_format((float) $oplata->value, 2, '.', ' ') }} @endif
                </option>
                @endforeach
            </select>
            @endif
        </div>

        <div class="mb-3">
            <label>Коментар</label>
            <input type="text" name="content" class="form-control" value="{{ old('content', $document->content ?? '') }}">
        </div>

        @if((int)($document->provodka ?? 0) === 0)
        <div class="mb-3 form-check">
            <input type="checkbox" class="form-check-input" id="post_after_save" name="post_after_save" value="1" checked>
            <label class="form-check-label" for="post_after_save">Провести документ після збереження</label>
        </div>
        @endif

        <div style="display:flex; gap:10px; flex-wrap:wrap;">
            <a href="{{ route('deposit.index') }}" class="btn">← Назад</a>
            @if((int)($document->provodka ?? 0) === 0)
            <button type="submit" class="btn">💾 Зберегти</button>
            @endif
            @if(!$isNew && (int)($document->provodka ?? 0) === 1)
            <button type="submit" formaction="{{ route('deposit.provodka') }}" formmethod="post" class="btn btn-success">
                ↺ Скасувати проводку
            </button>
            @endif
            @if(!$isNew && (int)($document->provodka ?? 0) === 0)
            <button type="button" class="btn btn-danger" onclick="if(confirm('Дійсно видалити цей документ?')) { document.getElementById('deleteDepositForm').submit(); }">
                🗑 Видалити
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
