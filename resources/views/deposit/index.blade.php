@extends('home')

@section('title', __('deposit.title'))

@section('content')
@php
    $activeFilters = array_filter($filters ?? [], fn($value) => $value !== '' && $value !== null);
@endphp

@include('deposit.partials.top-actions', [
    'showDepositFilter' => true,
    'activeFilters' => $activeFilters,
])

<div class="ttable document-compact-wrap" style="padding: 16px;">
    @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @if(!empty($activeFilters))
    <div class="alert alert-warning money-filter-active-notice">
        {{ __('deposit.filter_active') }}
        <a href="{{ route('deposit.index') }}" style="margin-left: 8px;">{{ __('deposit.reset') }}</a>
    </div>
    @endif

    <div class="money-summary">
        <div class="glass-card money-summary__card">
            <div class="money-summary__icon">🏦</div>
            <div class="money-summary__label">{{ __('deposit.deposit_operations') }}</div>
            <div class="money-summary__value money-summary__value--income">{{ number_format($sumPP ?? 0, 2, '.', ' ') }} грн</div>
        </div>
        <div class="glass-card money-summary__card">
            <div class="money-summary__icon">📄</div>
            <div class="money-summary__label">{{ __('deposit.documents_count') }}</div>
            <div class="money-summary__value money-summary__value--income">{{ $total ?? 0 }}</div>
        </div>
    </div>

    @if(($documents ?? collect())->isEmpty())
    <div style="text-align:center;padding:20px;color:#CC0000;font-size:1.2em">
        {{ __('deposit.no_documents') }}
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
            $fromLabel = match ($mode) {
                'withdraw' => $depositMap[$doc->money ?? ''] ?? ($doc->money ?: '—'),
                default => $oplataMap[$doc->oplata ?? ''] ?? ($doc->oplata ?: '—'),
            };
            $toLabel = match ($mode) {
                'withdraw' => $oplataMap[$doc->oplata2 ?? ''] ?? ($doc->oplata2 ?: '—'),
                default => $depositMap[$doc->money ?? ''] ?? ($doc->money ?: '—'),
            };
            $depositLabel = $depositMap[$doc->money ?? ''] ?? ($doc->money ?: '—');
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

</div>

<div id="depositFilterModal" class="money-filter-modal">
    <div class="glass-card money-filter-modal__content">
        <div onclick="depositFilterToggle()" class="money-filter-modal__close">✕</div>
        <h3 class="money-filter-modal__title">🔍 {{ __('deposit.filter_title') }}</h3>

        <form action="{{ route('deposit.index') }}" method="get">
            <div class="money-filter-modal__grid">
                <div class="money-filter-modal__field">
                    <label>{{ __('money.filter_search') }}</label>
                    <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control">
                </div>

                <div class="money-filter-modal__field">
                    <label>{{ __('deposit.field_operation') }}</label>
                    <select name="mode" class="form-control">
                        <option value="">{{ __('money.filter_all_types') }}</option>
                        <option value="topup" {{ ($filters['mode'] ?? '') === 'topup' ? 'selected' : '' }}>{{ __('deposit.op_topup') }}</option>
                        <option value="withdraw" {{ ($filters['mode'] ?? '') === 'withdraw' ? 'selected' : '' }}>{{ __('deposit.op_withdraw') }}</option>
                    </select>
                </div>

                <div class="money-filter-modal__field">
                    <label>{{ __('money.filter_date_from') }}</label>
                    <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="form-control">
                </div>

                <div class="money-filter-modal__field">
                    <label>{{ __('money.filter_date_to') }}</label>
                    <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="form-control">
                </div>
            </div>

            <div class="money-filter-modal__actions">
                <button type="submit" class="btn btn-warning">{{ __('money.filter_apply') }}</button>
                <a href="{{ route('deposit.index') }}" class="btn btn-outline-secondary">{{ __('money.filter_reset') }}</a>
            </div>
        </form>
    </div>
</div>

<script>
function depositFilterToggle() {
    const modal = document.getElementById('depositFilterModal');
    if (modal.style.display === 'none' || modal.style.display === '') {
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    } else {
        modal.style.display = 'none';
        document.body.style.overflow = '';
    }
}

document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
        const modal = document.getElementById('depositFilterModal');
        if (modal && modal.style.display === 'flex') {
            depositFilterToggle();
        }
    }
});

document.addEventListener('click', function (e) {
    const modal = document.getElementById('depositFilterModal');
    if (modal && e.target === modal) {
        depositFilterToggle();
    }
});
</script>
@endsection
