@if(($documents ?? collect())->isEmpty())
<div style="text-align:center;padding:20px;color:#CC0000;font-size:1.2em">
    {{ $emptyMessage ?? __('deposit.no_documents') }}
</div>
@else
<div class="document-compact-list">
    @foreach($documents as $doc)
    @php
        $mode = $doc->docum ?? 'topup';
        $modeLabel = match ($mode) {
            'withdraw' => __('deposit.op_withdraw'),
            default => __('deposit.op_topup'),
        };
        $modeIcon = match ($mode) {
            'withdraw' => '📤',
            default => '📥',
        };
        $modeBg = match ($mode) {
            'withdraw' => '#dc3545',
            default => '#28a745',
        };
        $moneyKey = (string) ($doc->money ?? '');
        $depositLabel = str_starts_with($moneyKey, 'pool:')
            ? ($poolMap[$moneyKey] ?? ($moneyKey ?: '—'))
            : ($depositMap[$moneyKey] ?? ($moneyKey ?: '—'));
        $linkUrl = route('deposit.show', ['id' => $doc->id]);
    @endphp
    <div class="txtbox-price-docs">
        <div class="order-card__header">
            <div class="numdoc-docs">
                <a href="{{ $linkUrl }}" title="{{ __('document.open') }}">#{{ $doc->num }}</a>
            </div>
            <div class="status-docs-icons--mobile">
                {{ $modeIcon }}
            </div>
            <div class="status-docs4 compact-date">
                <span class="compact-date-line">{{ $doc->data ?? '—' }}</span>
                <span class="compact-date-line">{{ $doc->time ?? '' }}</span>
            </div>
        </div>
        <div class="captionbox-docs">
            <a href="{{ $linkUrl }}" class="title">
                <span class="compact-client-line compact-main">{{ $depositLabel }}</span>
            </a>
        </div>
        <div class="status-docs3" style="background:{{ $modeBg }}; color:#fff;">
            {{ $modeIcon }} {{ $modeLabel }}
        </div>
        <div class="pricebox-docs1">
            <span class="money">{{ number_format($doc->summa ?? 0, 2, '.', ' ') }}</span>
        </div>
        <div class="captionbox-docs2">{{ $doc->content ?? '' }}</div>
        <div class="status-docs-icons">
            {!! $doc->provodka ? '✅' : '<span style="color:#999">⏳</span>' !!}
        </div>
    </div>
    @endforeach
</div>
@endif
