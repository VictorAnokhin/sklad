@extends('home')

@section('title', 'Депозиты')

@section('content')
<div class="ttable" style="padding: 12px 16px; margin-bottom: 16px;">
    <div style="display:flex; gap:10px; flex-wrap:wrap;">
        <a href="{{ route('deposit.show', ['id' => 0, 'mode' => 'topup']) }}" class="btn btn-warning">+ Пополнить депозит</a>
        <a href="{{ route('deposit.show', ['id' => 0, 'mode' => 'withdraw']) }}" class="btn btn-danger">+ Снять с депозита</a>
        <a href="{{ route('deposit.show', ['id' => 0, 'mode' => 'exchange']) }}" class="btn btn-outline-primary">+ Обмен между кассами</a>
    </div>
</div>

<div class="ttable" style="padding: 16px;">
    @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div style="display:flex; gap:16px; margin-bottom:20px; flex-wrap:wrap;">
        <div class="glass-card" style="flex:1;min-width:220px;text-align:center;">
            <div style="font-size:2rem;">🏦</div>
            <div style="font-weight:bold;font-size:1.1em;">Операции по депозитам</div>
            <div style="color:var(--accent-amber);font-size:1.25rem;font-weight:700;">{{ number_format($sumPP ?? 0, 2, '.', ' ') }} грн</div>
        </div>
        <div class="glass-card" style="flex:1;min-width:220px;text-align:center;">
            <div style="font-size:2rem;">📄</div>
            <div style="font-weight:bold;font-size:1.1em;">Документів</div>
            <div style="color:var(--accent-amber);font-size:1.25rem;font-weight:700;">{{ $total ?? 0 }}</div>
        </div>
    </div>

    @if(($documents ?? collect())->isEmpty())
    <div style="text-align:center; padding:20px; color:#CC0000;">Документы PP отсутствуют...</div>
    @else
    <table class="table table-bordered table-sm">
        <thead style="background:#efefef;">
            <tr>
                <th>#</th>
                <th>Операція</th>
                <th>Дата</th>
                <th>Звідки</th>
                <th>Куди</th>
                <th>Сума</th>
                <th>Коментар</th>
                <th>Пров.</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @foreach($documents as $doc)
            @php
                $mode = $doc->docum ?? 'topup';
                $modeLabel = match ($mode) {
                    'withdraw' => 'Снять с депозита',
                    'exchange' => 'Обмен между кассами',
                    default => 'Пополнить депозит',
                };
                $fromLabel = match ($mode) {
                    'withdraw' => $depositMap[$doc->money ?? ''] ?? ($doc->money ?: '—'),
                    'exchange' => $oplataMap[$doc->oplata ?? ''] ?? ($doc->oplata ?: '—'),
                    default => $oplataMap[$doc->oplata ?? ''] ?? ($doc->oplata ?: '—'),
                };
                $toLabel = match ($mode) {
                    'withdraw' => $oplataMap[$doc->oplata2 ?? ''] ?? ($doc->oplata2 ?: '—'),
                    'exchange' => $oplataMap[$doc->oplata2 ?? ''] ?? ($doc->oplata2 ?: '—'),
                    default => $depositMap[$doc->money ?? ''] ?? ($doc->money ?: '—'),
                };
            @endphp
            <tr>
                <td>{{ $doc->num }}</td>
                <td>{{ $modeLabel }}</td>
                <td>{{ $doc->data ?? '—' }}</td>
                <td>{{ $fromLabel }}</td>
                <td>{{ $toLabel }}</td>
                <td style="font-weight:bold;">{{ number_format($doc->summa ?? 0, 2, '.', ' ') }}</td>
                <td>{{ $doc->content ?? '' }}</td>
                <td style="text-align:center;">{{ $doc->provodka ? '✅' : '' }}</td>
                <td>
                    <a href="{{ route('deposit.show', ['id' => $doc->id]) }}" class="btn btn-sm btn-outline-primary">✏</a>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif
</div>
@endsection
