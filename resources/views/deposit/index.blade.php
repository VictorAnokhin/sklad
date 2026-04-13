@extends('home')

@section('title', __('deposit.title'))

@section('content')
<div class="ttable" style="padding: 12px 16px; margin-bottom: 16px;">
    <div style="display:flex; gap:10px; flex-wrap:wrap;">
        <a href="{{ route('deposit.show', ['id' => 0, 'mode' => 'topup']) }}" class="btn btn-warning">{{ __('deposit.add_deposit') }}</a>
        <a href="{{ route('deposit.show', ['id' => 0, 'mode' => 'withdraw']) }}" class="btn btn-danger">{{ __('deposit.add_withdraw') }}</a>
        <a href="{{ route('deposit.show', ['id' => 0, 'mode' => 'exchange']) }}" class="btn btn-outline-primary">{{ __('deposit.add_transfer') }}</a>
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
            <div style="font-weight:bold;font-size:1.1em;">{{ __('deposit.deposit_operations') }}</div>
            <div style="color:var(--accent-amber);font-size:1.25rem;font-weight:700;">{{ number_format($sumPP ?? 0, 2, '.', ' ') }} грн</div>
        </div>
        <div class="glass-card" style="flex:1;min-width:220px;text-align:center;">
            <div style="font-size:2rem;">📄</div>
            <div style="font-weight:bold;font-size:1.1em;">{{ __('deposit.documents_count') }}</div>
            <div style="color:var(--accent-amber);font-size:1.25rem;font-weight:700;">{{ $total ?? 0 }}</div>
        </div>
    </div>

    {{-- Desktop: table --}}
    <div class="deposit-table--desktop">
    @if(($documents ?? collect())->isEmpty())
    <div style="text-align:center; padding:20px; color:#CC0000;">{{ __('deposit.no_documents') }}</div>
    @else
    <table class="table table-bordered table-sm">
        <thead style="background:#efefef;">
            <tr>
                <th>#</th>
                <th>{{ __('deposit.field_operation') }}</th>
                <th>{{ __('deposit.field_date') }}</th>
                <th>{{ __('deposit.field_from') }}</th>
                <th>{{ __('deposit.field_to') }}</th>
                <th>{{ __('deposit.field_sum') }}</th>
                <th>{{ __('deposit.comment') }}</th>
                <th>{{ __('deposit.field_posted') }}</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @foreach($documents as $doc)
            @php
                $mode = $doc->docum ?? 'topup';
                $modeLabel = match ($mode) {
                    'withdraw' => __('deposit.op_withdraw'),
                    'exchange' => __('deposit.op_exchange'),
                    default => __('deposit.op_topup'),
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

    {{-- Mobile: card list --}}
    <div class="deposit-list--mobile">
    @if(($documents ?? collect())->isEmpty())
    <div class="deposit-empty">{{ __('deposit.no_documents') }}</div>
    @else
    @foreach($documents as $doc)
    @php
        $mode = $doc->docum ?? 'topup';
        $modeLabel = match ($mode) {
            'withdraw' => __('deposit.op_withdraw'),
            'exchange' => __('deposit.op_exchange'),
            default => __('deposit.op_topup'),
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
    <div class="deposit-card deposit-card--{{ $mode }}">
        <div class="deposit-card__header">
            <span class="deposit-card__type">
                @if($mode === 'topup')
                📥 {{ $modeLabel }}
                @elseif($mode === 'withdraw')
                📤 {{ $modeLabel }}
                @else
                🔄 {{ $modeLabel }}
                @endif
            </span>
            <span class="deposit-card__num">#{{ $doc->num }}</span>
            <span class="deposit-card__posted">{{ $doc->provodka ? '✅' : '⏳' }}</span>
        </div>
        <div class="deposit-card__date">{{ $doc->data ?? '—' }}</div>
        <div class="deposit-card__route">
            <span class="deposit-card__from">{{ $fromLabel }}</span>
            <span class="deposit-card__arrow">→</span>
            <span class="deposit-card__to">{{ $toLabel }}</span>
        </div>
        <div class="deposit-card__sum">{{ number_format($doc->summa ?? 0, 2, '.', ' ') }} грн</div>
        @if($doc->content)
        <div class="deposit-card__comment">{{ $doc->content }}</div>
        @endif
        <div class="deposit-card__actions">
            <a href="{{ route('deposit.show', ['id' => $doc->id]) }}" class="btn btn-sm btn-outline-primary">{{ __('deposit.edit') ?? '✏️ Редагувати' }}</a>
        </div>
    </div>
    @endforeach
    @endif
    </div>

</div>
@endsection
