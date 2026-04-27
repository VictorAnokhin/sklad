@extends('home')

@section('title', __('money.title'))

@section('content')
@php
    $activeTab = ($tab ?? 'orders') === 'transfers' ? 'transfers' : 'orders';
    $perPage = 30;
    $datesAreDefault = $datesAreDefault ?? false;
    $hasDateFilter = (($filters['date_from'] ?? '') !== '' || ($filters['date_to'] ?? '') !== '');
    $activeFilters = array_filter($filters ?? [], function ($value, $key) use ($activeTab, $datesAreDefault) {
        if ($value === '' || $value === null) {
            return false;
        }

        if ($activeTab === 'transfers' && in_array($key, ['type', 'reestr'], true)) {
            return false;
        }

        if (in_array($key, ['q', 'money'], true)) {
            return false;
        }

        if ($datesAreDefault && in_array($key, ['date_from', 'date_to'], true)) {
            return false;
        }

        return true;
    }, ARRAY_FILTER_USE_BOTH);
    $returnFilters = array_merge($filters ?? [], ['pos' => $pos ?? 0, 'tab' => $activeTab]);
@endphp

@include('money.partials.top-actions', [
    'returnFilters' => $returnFilters,
    'showMoneyFilter' => true,
    'activeFilters' => $activeFilters,
    'tab' => $activeTab,
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
        <a href="{{ route('money.index', ['tab' => $activeTab]) }}" style="margin-left: 8px;">{{ __('money.reset') }}</a>
    </div>
    @endif

    @if($hasDateFilter)
    <div class="alert alert-secondary money-filter-active-notice" style="border:1px solid var(--border); background:rgba(255,255,255,0.04); color:var(--foreground);">
        {{ $datesAreDefault ? 'Показані операції за останні 30 днів:' : 'Показані операції за вибраний період:' }}
        <strong>{{ $filters['date_from'] ?: '—' }}</strong> - <strong>{{ $filters['date_to'] ?: '—' }}</strong>.
    </div>
    @endif

    @if($activeTab === 'transfers')
    <div class="money-summary">
        <div class="glass-card money-summary__card">
            <div class="money-summary__icon">🔄</div>
            <div class="money-summary__label">{{ __('money.summary_transfers') }}</div>
            <div class="money-summary__value money-summary__value--income">{{ number_format($sumTransfers ?? 0, 2, '.', ' ') }} грн</div>
        </div>
        <div class="glass-card money-summary__card">
            <div class="money-summary__icon">📄</div>
            <div class="money-summary__label">{{ __('money.summary_transfer_docs') }}</div>
            <div class="money-summary__value money-summary__value--income">{{ $total ?? 0 }}</div>
        </div>
    </div>

    @if(($documents ?? collect())->isEmpty())
    <div style="text-align:center;padding:20px;color:#CC0000;font-size:1.2em">
        {{ __('money.no_transfer_documents') }}
    </div>
    @else
    <div class="glass-card" style="overflow-x:auto;">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>{{ __('money.table_header') }}</th>
                    <th>{{ __('money.table_date') }}</th>
                    <th>{{ __('money.table_from_cashbox') }}</th>
                    <th>{{ __('money.table_to_cashbox') }}</th>
                    <th>{{ __('money.table_sum') }}</th>
                    <th>{{ __('money.table_comment') }}</th>
                    <th>{{ __('money.table_posted') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($documents as $doc)
                @php
                    $linkUrl = route('money.show', array_merge([
                        'id' => $doc->id,
                        'tab' => 'transfers',
                    ], [
                        'return_q' => $returnFilters['q'] ?? null,
                        'return_money' => $returnFilters['money'] ?? null,
                        'return_date_from' => $returnFilters['date_from'] ?? null,
                        'return_date_to' => $returnFilters['date_to'] ?? null,
                        'return_pos' => $returnFilters['pos'] ?? null,
                    ]));
                    $fromCashbox = $doc->from_cashbox_name ?? ($kassasMap[$doc->oplata ?? ''] ?? ($doc->oplata ?: '—'));
                    $toCashbox = $doc->to_cashbox_name ?? ($kassasMap[$doc->oplata2 ?? ''] ?? ($doc->oplata2 ?: '—'));
                @endphp
                <tr>
                    <td><a href="{{ $linkUrl }}">#{{ $doc->num }}</a></td>
                    <td>{{ $doc->data ?? '—' }}<br><small class="text-muted">{{ $doc->time ?? '' }}</small></td>
                    <td>{{ $fromCashbox }}</td>
                    <td>{{ $toCashbox }}</td>
                    <td><span class="money">{{ number_format($doc->summa ?? 0, 2, '.', ' ') }}</span></td>
                    <td>{{ $doc->content ?? '' }}</td>
                    <td>{!! $doc->provodka ? '✅' : '<span style="color:#999">⏳</span>' !!}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
    @else
    <div class="money-summary">
            <div class="glass-card money-summary__card">
                <div class="money-summary__icon">📥</div>
            <div class="money-summary__label">{{ __('money.summary_income') }}</div>
            <div class="money-summary__value money-summary__value--income">{{ number_format($sumPPO ?? 0, 2, '.', ' ') }} грн</div>
        </div>
        <div class="glass-card money-summary__card">
            <div class="money-summary__icon">📤</div>
            <div class="money-summary__label">{{ __('money.summary_outcome') }}</div>
            <div class="money-summary__value money-summary__value--expense">{{ number_format($sumPRO ?? 0, 2, '.', ' ') }} грн</div>
        </div>
        <div class="glass-card money-summary__card">
            <div class="money-summary__icon">💰</div>
            <div class="money-summary__label">{{ __('money.summary_balance') }}</div>
            <div class="money-summary__value money-summary__value--income">{{ number_format($userBalance ?? 0, 2, '.', ' ') }} грн</div>
        </div>
    </div>

    @if(($documents ?? collect())->isEmpty())
    <div style="text-align:center;padding:20px;color:#CC0000;font-size:1.2em">
        {{ __('money.no_documents') }}
    </div>
    @else
    <div class="document-compact-list">
        @foreach($documents as $doc)
        @php
            $linkUrl = route('money.show', array_merge(['id' => $doc->id, 'type' => $doc->type, 'tab' => 'orders'], [
                'return_q' => $returnFilters['q'] ?? null,
                'return_filter_type' => $returnFilters['type'] ?? null,
                'return_money' => $returnFilters['money'] ?? null,
                'return_reestr' => $returnFilters['reestr'] ?? null,
                'return_date_from' => $returnFilters['date_from'] ?? null,
                'return_date_to' => $returnFilters['date_to'] ?? null,
                'return_pos' => $returnFilters['pos'] ?? null,
            ]));
            $isIncome = $doc->type === 'PPO';
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
                        {{ __('money.filter_payment_type') }}: {{ $paymentTypeName }}
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
    @endif

    @if(($total ?? 0) > $perPage)
    @include('partials.navigator', [
      'pos' => $pos,
      'pos2' => $perPage,
      'max' => $total,
      'routeName' => 'money.index',
      'routeParams' => array_merge($filters ?? [], ['tab' => $activeTab]),
    ])
    @endif
</div>

<div id="moneyFilterModal" class="money-filter-modal">
    <div class="glass-card money-filter-modal__content">
        <div onclick="moneyFilterToggle()" class="money-filter-modal__close">✕</div>
        <h3 class="money-filter-modal__title">🔍 {{ __('money.filter_title') }}</h3>

        @if($hasDateFilter)
        <div style="margin-bottom:16px; padding:10px 12px; border-radius:10px; border:1px solid rgba(251,191,36,0.28); background:rgba(251,191,36,0.08); color:var(--foreground); font-size:0.9rem;">
            {{ $datesAreDefault ? 'За замовчуванням показано останні 30 днів:' : 'Активний діапазон дат:' }}
            <strong>{{ $filters['date_from'] ?: '—' }}</strong> - <strong>{{ $filters['date_to'] ?: '—' }}</strong>.
        </div>
        @endif

        <form action="{{ route('money.index') }}" method="get">
            <input type="hidden" name="tab" value="{{ $activeTab }}">
            <div class="money-filter-modal__grid">
                @if($activeTab === 'orders')
                <div class="money-filter-modal__field">
                    <label>{{ __('money.filter_type') }}</label>
                    <select name="type" class="form-control">
                        <option value="">{{ __('money.filter_all_types') }}</option>
                        <option value="PPO" {{ ($filters['type'] ?? '') === 'PPO' ? 'selected' : '' }}>{{ __('money.filter_income') }}</option>
                        <option value="PRO" {{ ($filters['type'] ?? '') === 'PRO' ? 'selected' : '' }}>{{ __('money.filter_outcome') }}</option>
                    </select>
                </div>
                @endif

                @if($activeTab === 'orders')
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
                @endif

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
                <a href="{{ route('money.index', ['tab' => $activeTab]) }}" class="btn btn-outline-secondary">{{ __('money.filter_reset') }}</a>
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
