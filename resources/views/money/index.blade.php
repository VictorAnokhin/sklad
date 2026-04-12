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

<div class="ttable money-action-bar">
    <div class="money-action-bar__actions">
        <button type="button"
            onclick="moneyFilterToggle()"
            class="btn {{ !empty($activeFilters) ? 'btn-warning' : 'btn-outline-secondary' }}">
            🔍 {{ __('money.filter') }}
        </button>
        <a href="{{ route('money.show', array_merge(['id' => 0, 'type' => 'PO'], [
            'return_q' => $returnFilters['q'] ?? null,
            'return_filter_type' => $returnFilters['type'] ?? null,
            'return_money' => $returnFilters['money'] ?? null,
            'return_reestr' => $returnFilters['reestr'] ?? null,
            'return_date_from' => $returnFilters['date_from'] ?? null,
            'return_date_to' => $returnFilters['date_to'] ?? null,
            'return_pos' => $returnFilters['pos'] ?? null,
        ])) }}" class="btn btn-success">{{ __('money.add_income') }}</a>
        <a href="{{ route('money.show', array_merge(['id' => 0, 'type' => 'RO'], [
            'return_q' => $returnFilters['q'] ?? null,
            'return_filter_type' => $returnFilters['type'] ?? null,
            'return_money' => $returnFilters['money'] ?? null,
            'return_reestr' => $returnFilters['reestr'] ?? null,
            'return_date_from' => $returnFilters['date_from'] ?? null,
            'return_date_to' => $returnFilters['date_to'] ?? null,
            'return_pos' => $returnFilters['pos'] ?? null,
        ])) }}" class="btn btn-danger">{{ __('money.add_outcome') }}</a>
    </div>
</div>
<div class="ttable money-table" style="padding: 16px;">

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



    {{-- Список документів --}}

    @if($documents->isEmpty())
    <div class="money-empty">{{ __('money.no_documents') }}</div>
    @else
    <table class="table table-bordered table-sm money-table">
        <thead>
            <tr>
                <th>#</th>
                <th>{{ __('money.table_type') }}</th>
                <th>{{ __('money.table_date') }}</th>
                <th>{{ __('money.table_client') }}</th>
                <th>{{ __('money.table_cashbox') }}</th>
                <th>{{ __('money.table_sum') }}</th>
                <th>{{ __('money.table_comment') }}</th>
                <th>{{ __('money.table_posted') }}</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @foreach($documents as $doc)
            <tr>
                <td>{{ $doc->num }}</td>
                <td>
                    @if($doc->type === 'PO')
                    <span class="money-doc-type--po">📥 PO</span>
                    @else
                    <span class="money-doc-type--ro">📤 RO</span>
                    @endif
                </td>
                <td>{{ $doc->data ?? '—' }}</td>
                <td class="money-table__client">
                    {{ $doc->orgname ?? '' }}
                    {{ trim(($doc->secondname ?? '') . ' ' . ($doc->name ?? '') . ' ' . ($doc->name2 ?? '')) }}
                    @if($doc->phone)<br><small>{{ $doc->phone }}</small>@endif
                </td>
                <td>{{ $kassasMap[$doc->money ?? ''] ?? ($doc->money ?: '—') }}</td>
                <td class="{{ $doc->type === 'PO' ? 'money-doc-sum--po' : 'money-doc-sum--ro' }}">
                    {{ number_format($doc->summa ?? 0, 2, '.', ' ') }}
                </td>
                <td class="money-table__comment">{{ $doc->content ?? '' }}</td>
                <td style="text-align:center;">{{ $doc->provodka ? '✅' : '' }}</td>
                <td>
                    <a href="{{ route('money.show', array_merge(['id' => $doc->id, 'type' => $doc->type], [
                        'return_q' => $returnFilters['q'] ?? null,
                        'return_filter_type' => $returnFilters['type'] ?? null,
                        'return_money' => $returnFilters['money'] ?? null,
                        'return_reestr' => $returnFilters['reestr'] ?? null,
                        'return_date_from' => $returnFilters['date_from'] ?? null,
                        'return_date_to' => $returnFilters['date_to'] ?? null,
                        'return_pos' => $returnFilters['pos'] ?? null,
                    ])) }}" class="btn btn-sm btn-outline-primary">✏</a>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    @if(($total ?? 0) > $perPage)
    <div class="money-pagination">
        <div class="money-pagination__meta">
            {{ __('money.pagination_showing', ['from' => $from, 'to' => $to, 'total' => $total]) }}
        </div>

        <div class="money-pagination__controls">
            @if(($pos ?? 0) > 0)
            <a href="{{ route('money.index', array_merge($filters ?? [], ['pos' => max(0, ($pos ?? 0) - $perPage)])) }}" class="money-pagination__nav">
                ← {{ __('money.pagination_prev') }}
            </a>
            @endif

            <div class="money-pagination__pages">
                @for($page = $windowStart; $page <= $windowEnd; $page++)
                    @php $pagePos = ($page - 1) * $perPage; @endphp
                    <a href="{{ route('money.index', array_merge($filters ?? [], ['pos' => $pagePos])) }}"
                        class="money-pagination__page {{ $page === $currentPage ? 'is-active' : '' }}">
                        {{ $page }}
                    </a>
                @endfor
            </div>

            @if((($pos ?? 0) + $perPage) < ($total ?? 0))
            <a href="{{ route('money.index', array_merge($filters ?? [], ['pos' => ($pos ?? 0) + $perPage])) }}" class="money-pagination__nav">
                {{ __('money.pagination_next') }} →
            </a>
            @endif
        </div>
    </div>
    @endif
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
