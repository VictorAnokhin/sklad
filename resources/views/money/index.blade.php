@extends('home')

@section('title', __('money.title'))

@section('content')
@php
    $perPage = 30;
    $currentPage = (int) floor(($pos ?? 0) / $perPage) + 1;
    $totalPages = max(1, (int) ceil(($total ?? 0) / $perPage));
    $from = ($total ?? 0) > 0 ? (($pos ?? 0) + 1) : 0;
    $to = min(($pos ?? 0) + $perPage, $total ?? 0);
    $windowStart = max(1, min($currentPage - 1, max(1, $totalPages - 3)));
    $windowEnd = min($totalPages, $windowStart + 3);
    $activeFilters = array_filter($filters ?? [], fn($value) => $value !== '' && $value !== null);
    $returnFilters = array_merge($filters ?? [], ['pos' => $pos ?? 0]);
@endphp

@include('money.partials.top-actions', [
    'returnFilters' => $returnFilters,
    'showMoneyFilter' => true,
    'activeFilters' => $activeFilters,
])

<div class="ttable document-compact-wrap">
    @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @if(!empty($activeFilters))
    <div class="alert alert-warning money-filter-active-notice">
        {{ __('money.filter_active') }}
        <a href="{{ route('money.index') }}" style="margin-left: 8px;">{{ __('money.reset') }}</a>
    </div>
    @endif

    {{-- Зведення --}}
    <div class="money-summary">
        <div class="glass-card money-summary__card">
            <div class="money-summary__icon">📥</div>
            <div class="money-summary__label">{{ __('money.summary_income') }}</div>
            <div class="money-summary__value money-summary__value--income">{{ number_format($sumPO, 2, '.', ' ') }} грн</div>
        </div>
        <div class="glass-card money-summary__card">
            <div class="money-summary__icon">📤</div>
            <div class="money-summary__label">{{ __('money.summary_outcome') }}</div>
            <div class="money-summary__value money-summary__value--expense">{{ number_format($sumRO, 2, '.', ' ') }} грн</div>
        </div>
        <div class="glass-card money-summary__card">
            <div class="money-summary__icon">💰</div>
            <div class="money-summary__label">{{ __('money.summary_balance') }}</div>
            <div class="money-summary__value money-summary__value--income">{{ number_format($sumPO - $sumRO, 2, '.', ' ') }} грн</div>
        </div>
    </div>

    @if($documents->isEmpty())
    <div style="text-align:center;padding:20px;color:#CC0000;font-size:1.2em">
        {{ __('money.no_documents') }}
    </div>
    @else
    <div class="document-compact-list">
        @foreach($documents as $doc)
        @php
            $linkUrl = route('money.show', array_merge(['id' => $doc->id, 'type' => $doc->type], [
                'return_q' => $returnFilters['q'] ?? null,
                'return_filter_type' => $returnFilters['type'] ?? null,
                'return_money' => $returnFilters['money'] ?? null,
                'return_reestr' => $returnFilters['reestr'] ?? null,
                'return_date_from' => $returnFilters['date_from'] ?? null,
                'return_date_to' => $returnFilters['date_to'] ?? null,
                'return_pos' => $returnFilters['pos'] ?? null,
            ]));
            $isIncome = $doc->type === 'PO';
            $typeBg = $isIncome ? '#28a745' : '#dc3545';
            $typeIcon = $isIncome ? '📥' : '📤';
            $typeLabel = $isIncome ? __('money.filter_income') : __('money.filter_outcome');
            $cashboxId = $doc->effective_cashbox_id ?? $doc->money ?? $doc->oplata ?? '';
            $cashboxName = $doc->cashbox_name ?? ($kassasMap[$cashboxId] ?? ($cashboxId ?: '—'));
            $paymentTypeName = $doc->payment_type_name ?? ($reestrMap[$doc->reestr ?? ''] ?? ($doc->reestr ?: '—'));
            $clientName = trim(
                ($doc->orgname ?? '') . ' ' .
                ($doc->secondname ?? '') . ' ' .
                ($doc->name ?? '') . ' ' .
                ($doc->name2 ?? '')
            );
        @endphp
        <div class="txtbox-price-docs">
            <div class="order-card__header">
                <div class="numdoc-docs">
                    <a href="{{ $linkUrl }}" title="{{ __('document.open') }}">#{{ $doc->num }}</a>
                </div>
                <div class="status-docs-icons--mobile">
                    {{ $typeIcon }}
                </div>
                <div class="status-docs4 compact-date">
                    <span class="compact-date-line">{{ $doc->data ?? '—' }}</span>
                    <span class="compact-date-line">{{ $doc->time ?? '' }}</span>
                </div>
            </div>
            <div class="captionbox-docs">
                <a href="{{ $linkUrl }}" class="title">
                    <span class="compact-client-line compact-main">{{ $clientName !== '' ? $clientName : '—' }}</span>
                    <span class="compact-client-line city text-muted">
                        {{ __('money.filter_cashbox') }}: {{ $cashboxName }}
                        | {{ __('money.filter_payment_type') }}: {{ $paymentTypeName }}
                    </span>
                    @if($doc->phone)<span class="phone">{{ $doc->phone }}</span>@endif
                </a>
            </div>
            <div class="status-docs3" style="background:{{ $typeBg }}; color: #fff;">
                {{ $typeIcon }} {{ $typeLabel }}
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

    {{-- Pagination --}}
    @if(($total ?? 0) > $perPage)
    @include('partials.navigator', [
      'pos' => $pos,
      'pos2' => $perPage,
      'max' => $total,
      'doc' => 'money',
      'query' => http_build_query($filters ?? [])
    ])
    @endif
</div>


<div id="moneyFilterModal" class="money-filter-modal">
    <div class="glass-card money-filter-modal__content">
        <div onclick="moneyFilterToggle()" class="money-filter-modal__close">✕</div>
        <h3 class="money-filter-modal__title">🔍 {{ __('money.filter_title') }}</h3>

        <form action="{{ route('money.index') }}" method="get">
            <div class="money-filter-modal__grid">
                <div class="money-filter-modal__field">
                    <label>{{ __('money.filter_search') }}</label>
                    <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control">
                </div>

                <div class="money-filter-modal__field">
                    <label>{{ __('money.filter_type') }}</label>
                    <select name="type" class="form-control">
                        <option value="">{{ __('money.filter_all_types') }}</option>
                        <option value="PO" {{ ($filters['type'] ?? '') === 'PO' ? 'selected' : '' }}>{{ __('money.filter_income') }}</option>
                        <option value="RO" {{ ($filters['type'] ?? '') === 'RO' ? 'selected' : '' }}>{{ __('money.filter_outcome') }}</option>
                    </select>
                </div>

                <div class="money-filter-modal__field">
                    <label>{{ __('money.filter_cashbox') }}</label>
                    <select name="money" class="form-control">
                        <option value="">{{ __('money.filter_all_types') }}</option>
                        @foreach(($kassasMap ?? []) as $moneyName => $moneyLabel)
                        <option value="{{ $moneyName }}" {{ ($filters['money'] ?? '') === (string)$moneyName ? 'selected' : '' }}>
                            {{ $moneyLabel }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <div class="money-filter-modal__field">
                    <label>{{ __('money.filter_payment_type') }}</label>
                    <select name="reestr" class="form-control">
                        <option value="">{{ __('money.filter_all_types') }}</option>
                        @foreach(($paymentTypes ?? []) as $paymentType)
                        <option value="{{ $paymentType->id }}" {{ ($filters['reestr'] ?? '') === (string)$paymentType->id ? 'selected' : '' }}>
                            {{ $paymentType->name }}
                        </option>
                        @endforeach
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
                <a href="{{ route('money.index') }}" class="btn btn-outline-secondary">{{ __('money.filter_reset') }}</a>
            </div>
        </form>
    </div>
</div>

<script>
function moneyFilterToggle() {
    const modal = document.getElementById('moneyFilterModal');
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
        const modal = document.getElementById('moneyFilterModal');
        if (modal && modal.style.display === 'flex') {
            moneyFilterToggle();
        }
    }
});

document.addEventListener('click', function (e) {
    const modal = document.getElementById('moneyFilterModal');
    if (modal && e.target === modal) {
        moneyFilterToggle();
    }
});
</script>
@endsection
